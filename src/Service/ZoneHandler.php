<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenProfession;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\Inventory;
use App\Entity\ItemGroup;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\WellCounter;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ZoneHandler
{
    private $entity_manager;
    private $item_factory;
    private $status_factory;
    private $random_generator;
    private $inventory_handler;
    private $citizen_handler;

    public function __construct(
        EntityManagerInterface $em, ItemFactory $if,
        StatusFactory $sf, RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch)
    {
        $this->entity_manager = $em;
        $this->item_factory = $if;
        $this->status_factory = $sf;
        $this->random_generator = $rg;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
    }

    public function updateZone( Zone $zone, ?DateTime $up_to = null ) {

        $now = new DateTime();
        if ($up_to === null || $up_to > $now) $up_to = $now;

        /** @var DigTimer[] $dig_timers */
        $dig_timers = [];
        $cp = 0;
        foreach ($zone->getCitizens() as $citizen) {
            $timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen( $citizen );
            if ($timer && !$timer->getPassive() && $timer->getTimestamp() < $up_to)
                $dig_timers[] = $timer;
            $cp += $this->citizen_handler->getCP( $citizen );
        }

        if ($cp < $zone->getZombies()) {
            foreach ($dig_timers as $timer) {
                $timer->setPassive(true);
                $this->entity_manager->persist($timer);
            }
            $this->entity_manager->flush();
            return;
        }

        $sort_func = function(DigTimer $a, DigTimer $b): int {
            return $a->getTimestamp()->getTimestamp() == $b->getTimestamp()->getTimestamp() ? 0 :
                ($a->getTimestamp() < $b->getTimestamp() ? -1 : 1);
        };

        $empty_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneByName('empty_dig');
        $base_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneByName('base_dig');

        $zone_update = false;
        $not_up_to_date = !empty($dig_timers);
        while ($not_up_to_date) {

            usort( $dig_timers, $sort_func );

            foreach ($dig_timers as &$timer)
                if ($timer->getTimestamp() <= $up_to) {

                    $item_prototype = $this->random_generator->chance($zone->getDigs() > 0 ? 0.333 : 0.25)
                        ? $this->random_generator->pickItemPrototypeFromGroup( $zone->getDigs() > 0 ? $base_group : $empty_group )
                        : null;

                    if ($item_prototype) {
                        $item = $this->item_factory->createItem($item_prototype);
                        if ($this->inventory_handler->placeItem( $timer->getCitizen(), $item, [ $timer->getCitizen()->getInventory(), $timer->getZone()->getFloor() ] )) {
                            $this->entity_manager->persist( $item );
                            $this->entity_manager->persist( $timer->getCitizen()->getInventory() );
                            $this->entity_manager->persist( $timer->getZone()->getFloor() );
                        }
                    }

                    $zone->setDigs( max(0, $zone->getDigs() - 1) );
                    $zone_update = true;

                    try {
                        $timer->setTimestamp(
                            (new DateTime())->setTimestamp(
                                $timer->getTimestamp()->getTimestamp()
                            )->modify($timer->getCitizen()->getProfession()->getName() === 'collec' ? '+1hour30min' : '+2hour') );

                    } catch (Exception $e) {
                        $timer->setTimestamp( new DateTime('+1min') );
                    }
                }
            $not_up_to_date = $dig_timers[0]->getTimestamp() < $up_to;
        }

        if ($zone_update) $this->entity_manager->persist($zone);
        foreach ($dig_timers as $timer) $this->entity_manager->persist( $timer );
        $this->entity_manager->flush();
    }

    const RespawnModeNone = 0;
    const RespawnModeAuto = 1;
    const RespawnModeForce = 2;

    public function dailyZombieSpawn( Town &$town, int $cycles = 1, int $mode = self::RespawnModeAuto ) {

        /** @var Zone[] $zones */
        $zones = $town->getZones()->getValues();
        $zone_db = []; $empty_zones = []; $despair_db = [];
        foreach ($zones as &$zone) {
            $despair = max(0,( $zone->getInitialZombies() - $zone->getZombies() - 1 ) / 2);
            if (!isset($zone_db[$zone->getX()])) $zone_db[$zone->getX()] = [];
            $zone_db[$zone->getX()][$zone->getY()] = $zone->getZombies();
            $despair_db[$zone->getX()][$zone->getY()] = $despair;
            if ($zone_db[$zone->getX()][$zone->getY()] == 0) $empty_zones[] = $zone;
        }

        // Respawn
        $d = $town->getDay();
        if ($mode === self::RespawnModeForce || ($mode === self::RespawnModeAuto && $d < 3 && count($empty_zones) > (count($zones)* 18/20))) {
            $keys = $d == 1 ? [array_rand($empty_zones)] : array_rand($empty_zones, $d);
            foreach ($keys as $spawn_zone_id)
                /** @var Zone $spawn_zone */
                $zone_db[ $zones[$spawn_zone_id]->getX() ][ $zones[$spawn_zone_id]->getY() ] = mt_rand(1,6);
            $cycles += ceil($d/2);
        }


        for ($c = 0; $c < $cycles; $c++) {
            $zone_original_db = $zone_db;
            foreach ($zone_db as $x => &$zone_row)
                foreach ($zone_row as $y => &$current_zone_zombies) {
                    $adj_zones_total = $adj_zones_infected = $zone_zed_difference = 0;

                    for ($dx = -1; $dx <= 1; $dx++)
                        if (isset($zone_original_db[$x + $dx]))
                            for ($dy = -1; $dy <= 1; $dy++) if ($dx !== 0 || $dy !== 0) {
                                if (isset($zone_original_db[$x + $dx][$y + $dy])) {
                                    $adj_zones_total++;
                                    if ($zone_original_db[$x + $dx][$y + $dy] > $zone_original_db[$x][$y]) {
                                        $adj_zones_infected++;
                                        $dif = $zone_original_db[$x + $dx][$y + $dy] - $zone_original_db[$x][$y];
                                        $zone_zed_difference += ( abs($dx) + abs($dy) == 2 ? ceil($dif/2) : $dif );
                                    }
                                }
                            }

                    if ($adj_zones_infected > 0) {

                        $spread_chance = 1 - pow(0.875, $zone_zed_difference);
                        if (mt_rand(0,100) > (100*$spread_chance)) continue;

                        $max_zeds = ceil($zone_zed_difference / 8 );
                        $min_zeds = min($max_zeds, floor($max_zeds * ($adj_zones_infected / $adj_zones_total)));
                        $current_zone_zombies += mt_rand($min_zeds, $max_zeds);
                    }

                }

            foreach ($zone_db as $x => &$zone_row)
                foreach ($zone_row as $y => &$current_zone_zombies) {
                    if ($x === 0 && $y === 0) continue;
                    if ($current_zone_zombies > 0) $current_zone_zombies += max(0,mt_rand(-2, 1));
                }
        }

        foreach ($town->getZones() as &$zone) {
            if ($zone->getX() === 0 && $zone->getY() === 0) continue;

            $zombies = max( 0, $zone_db[$zone->getX()][$zone->getY()] );
            $zone->setZombies( max(0, $zombies - $despair_db[$zone->getX()][$zone->getY()] ));
            $zone->setInitialZombies( $zombies );
        }

    }

    public function check_cp(Zone $zone, ?int &$cp = null): bool {
        $cp = 0;
        foreach ($zone->getCitizens() as $c)
            if ($c->getAlive())
                $cp += $this->citizen_handler->getCP($c);
        return $cp >= $zone->getZombies();
    }

    public function handleCitizenCountUpdate(&$zone, $cp_ok_before) {
        // If no citizens remain in a zone, invalidate all associated escape timers
        if (!count($zone->getCitizens())) foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByZone($zone) as $et)
            $this->entity_manager->remove( $et );
        // If zombies can take control after leaving the zone and there are citizens remaining, install a grace escape timer
        elseif ( $cp_ok_before && !$this->check_cp( $zone ) )
            $zone->addEscapeTimer( (new EscapeTimer())->setTime( new DateTime('+30min') ) );
    }
}