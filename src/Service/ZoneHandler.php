<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\ItemGroup;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Translation\T;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class ZoneHandler
{
    private $entity_manager;
    private $item_factory;
    private $status_factory;
    private $random_generator;
    private $inventory_handler;
    private $citizen_handler;
    private $picto_handler;
    private $trans;
    private $log;
    private $asset;

    public function __construct(
        EntityManagerInterface $em, ItemFactory $if, LogTemplateHandler $lh, TranslatorInterface $t,
        StatusFactory $sf, RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch, PictoHandler $ph, Packages $a)
    {
        $this->entity_manager = $em;
        $this->item_factory = $if;
        $this->status_factory = $sf;
        $this->random_generator = $rg;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->trans = $t;
        $this->log = $lh;
        $this->asset = $a;
    }

    public function updateZone( Zone $zone, ?DateTime $up_to = null, ?Citizen $active = null ): ?string {

        $now = new DateTime();
        if ($up_to === null || $up_to > $now) $up_to = $now;

        $chances_by_player = 0;
        $found_by_player = [];

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
            return null;
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

                    $factor = 1.0;
                    if ($timer->getCitizen()->getProfession()->getName() === 'collec') $factor += 0.2;
                    if ($this->citizen_handler->hasStatusEffect( $timer->getCitizen(), 'camper' )) $factor += 0.2;
                    $item_prototype = $this->random_generator->chance($factor * ($zone->getDigs() > 0 ? 0.40 : 0.25))
                        ? $this->random_generator->pickItemPrototypeFromGroup( $zone->getDigs() > 0 ? $base_group : $empty_group )
                        : null;

                    if ($active && $timer->getCitizen()->getId() === $active->getId()) {
                        $chances_by_player++;
                        if ($item_prototype){
                            $found_by_player[] = $item_prototype;
                            // If we get a Chest XL
                            if ($item_prototype->getName() == 'chest_xl_#00') {
                                $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_chstxl_#00");
                                $this->picto_handler->give_picto($citizen, $pictoPrototype);
                            }
                        }
                    }

                    $this->entity_manager->persist( $this->log->outsideDig( $timer->getCitizen(), $item_prototype, $timer->getTimestamp() ) );
                    if ($item_prototype) {
                        $item = $this->item_factory->createItem($item_prototype);
                        if ($this->inventory_handler->placeItem( $timer->getCitizen(), $item, [ $timer->getCitizen()->getInventory(), $timer->getZone()->getFloor() ] )) {
                            $this->entity_manager->persist( $item );
                            $this->entity_manager->persist( $timer->getCitizen()->getInventory() );
                            $this->entity_manager->persist( $timer->getZone()->getFloor() );
                        }
                    }

                    $zone->setDigs( max($item_prototype ? 0 : 1, $zone->getDigs() - 1) );
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

        if ($chances_by_player > 0) {

            if (empty($found_by_player)) return $this->trans->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' );
            elseif (count($found_by_player) === 1) return $this->trans->trans( 'Nach einigen Anstrengungen hast du folgendes gefunden: %item%!', [
                '%item%' => "<span><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $found_by_player[0]->getIcon() . '.gif' )}'> {$this->trans->trans($found_by_player[0]->getLabel(), [], 'items')}</span>"
            ], 'game' );
            else return $this->trans->trans( 'Du gräbst schon seit einiger Zeit und hast mehrere Gegenstände gefunden.', [], 'game' );

        } else return null;
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

            $zone->setScoutEstimationOffset( mt_rand(-2,2) );
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
        $guide_present = false;
        $roleGuide = $this->entity_manager->getRepository(CitizenRole::class)->findOneByName("guide");
        foreach ($zone->getCitizens() as $c){
            if ($c->getAlive()){
                $cp += $this->citizen_handler->getCP($c);
            }
            if($c->getRoles()->contains($roleGuide))
                $guide_present = true;
        }
        if($guide_present)
            $cp += count($zone->getCitizens());
        
        return $cp >= $zone->getZombies();
    }

    /**
     * @param Zone $zone
     * @param $cp_ok_before
     */
    public function handleCitizenCountUpdate(&$zone, $cp_ok_before) {
        // If no citizens remain in a zone, invalidate all associated escape timers and clear the log
        if (!count($zone->getCitizens())) {
            foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByZone($zone) as $et)
                $this->entity_manager->remove( $et );
            foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter( $zone->getTown(), null, null, $zone, null, null ) as $entry)
                /** @var TownLogEntry $entry */
                if ($entry->getClass() !== TownLogEntry::ClassCritical)
                    $this->entity_manager->remove( $entry );
        }
        // If zombies can take control after leaving the zone and there are citizens remaining, install a grace escape timer
        elseif ( $cp_ok_before && !$this->check_cp( $zone ) ) {
            $zone->addEscapeTimer( (new EscapeTimer())->setTime( new DateTime('+30min') ) );
            // Disable all dig timers
            foreach ($zone->getDigTimers() as $dig_timer) {
                $dig_timer->setPassive(true);
                $this->entity_manager->persist( $dig_timer );
            }
        }

    }

    public function getZoneClasses(Zone $zone, ?Citizen $citizen = null) {
        $attributes = ['zone'];
        if ($zone->getX() == 0 && $zone->getY() == 0) {
            $attributes[] = 'town';
        }
        if ($zone->getX() == 0 && $zone->getY() == 0 && $zone->getTown()->getDevastated()) {
            $attributes[] = 'devast';
        }
        if ($citizen && $zone === $citizen->getZone()) {
            $attributes[] = 'active';
        }
        if ($zone->getDiscoveryStatus() === Zone::DiscoveryStateNone) {
            $attributes[] = 'unknown';
        }
        else {
            if ($zone->getDiscoveryStatus() === Zone::DiscoveryStatePast) {
                $attributes[] = 'past';
            }
            if ($zone->getPrototype()) {
                $attributes[] = 'ruin';
                if ($zone->getBuryCount() > 0) {
                    $attributes[] = 'buried';
                }
            }
        }
        if ($zone->getZombieStatus() >= Zone::ZombieStateEstimate) {
            if ($zone->getZombies() == 0) {
                $attributes[] = 'danger-0';
            }
            else if ($zone->getZombies() <= 2) {
                $attributes[] = 'danger-1';
            }
            else if ($zone->getZombies() <= 5) {
                $attributes[] = 'danger-2';
            }
            else {
                $attributes[] = 'danger-3';
            }
        }

        return $attributes;
    }

    public function getZoneAttributes(Zone $zone, ?Citizen $citizen = null) {
        $attributes = ['zone'];
        $attributes['town'] = ($zone->getX() == 0 && $zone->getY() == 0) ? 1 : 0;
        $attributes['devast'] = ($zone->getX() == 0 && $zone->getY() == 0 && $zone->getTown()->getDevastated()) ? 1 : 0;
        $attributes['active'] = $citizen && $zone === $citizen->getZone() ? 1 : 0;
        $attributes['discovery'] = $zone->getDiscoveryStatus();
        if ($zone->getDiscoveryStatus() && $zone->getPrototype()) {
            if ($zone->getBuryCount() > 0) {
                $attributes['building'] = [
                    'name' => T::__("Ein nicht freigeschaufeltes Gebäude.", "game"),
                    'type' => -1,
                    'dig' => $zone->getBuryCount(),
                ];
            }
            else {
                $attributes['building'] = [
                    'name' => T::__($zone->getPrototype()->getLabel(), "game"),
                    'type' => $zone->getPrototype()->getId(),
                    'dig' => 0,
                ];
            }
        }

        if ($zone->getDiscoveryStatus() && $zone->getZombieStatus() === Zone::ZombieStateEstimate) {
            if ($zone->getZombies() == 0) {
                $attributes['danger'] = '0';
            }
            else if ($zone->getZombies() <= 2) {
                $attributes['danger'] = '1';
            }
            else if ($zone->getZombies() <= 5) {
                $attributes['danger'] = '2';
            }
            else {
                $attributes['danger'] = '3';
            }
        }

        return $attributes;
    }

    public function getZoneKm(Zone $zone): int {
        return round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));
    }

    public function getZoneAp(Zone $zone): int {
        return abs($zone->getX()) + abs($zone->getY());
    }
}