<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\Complaint;
use App\Entity\ItemPrototype;
use App\Entity\Town;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Structures\HomeDefenseSummary;
use App\Structures\TownDefenseSummary;
use Doctrine\ORM\EntityManagerInterface;
use function Couchbase\basicEncoderV1;

class TownHandler
{
    private $entity_manager;
    private $inventory_handler;
    private $item_factory;
    private $log;
    private $timeKeeper;
    private $citizen_handler;

    private $building_cache = null;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, ItemFactory $if, LogTemplateHandler $lh, TimeKeeperService $tk, CitizenHandler $ch)
    {
        $this->entity_manager = $em;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->log = $lh;
        $this->timeKeeper = $tk;
        $this->citizen_handler = $ch;
    }

    private function internalAddBuilding( Town &$town, BuildingPrototype $prototype ): ?Building {

        // Add building
        $town->addBuilding( $b = (new Building())->setPrototype( $prototype )->setPosition($prototype->getOrderBy()) );

        // Add all children that do not require blueprints
        if ($b)
            foreach ( $prototype->getChildren() as $child )
                if ($child->getBlueprint() == 0) $this->internalAddBuilding( $town, $child );
        return $b;
    }

    public function triggerAlways( Town $town, bool $flush = false ) {
        $changed = false;

        if ( $town->getDoor() && !$town->getDevastated() && (($s = $this->timeKeeper->secondsUntilNextAttack(null, true)) <= 1800) ) {

            $close_ts = null;
            if ($this->getBuilding( $town, 'small_door_closed_#02', true )) {
                if ($s <= 60)
                    $close_ts = $this->timeKeeper->getCurrentAttackTime()->modify('-1min');
            } elseif ($this->getBuilding( $town, 'small_door_closed_#01', true )) {
                if ($s <= 1800)
                    $close_ts = $this->timeKeeper->getCurrentAttackTime()->modify('-30min');
            }

            if ($close_ts !== null) {
                $town->setDoor( false );
                $this->entity_manager->persist( $this->log->doorControlAuto( $town, false, $close_ts ) );
                $changed = true;
            }

        }

        if ($changed) {
            $this->entity_manager->persist( $town );
            if ($flush) $this->entity_manager->flush();
        }
    }

    public function triggerBuildingCompletion( Town &$town, Building $building ) {
        $well = 0;

        $water_db = [
            'small_derrick_#00'      =>  50,
            'small_water_#01'        =>  40,
            'small_eden_#00'         =>  70,
            'small_water_#00'        =>   5,
            'small_derrick_#01'      => 150,
            'item_tube_#01'          =>   2,
            'item_firework_tube_#00' =>  15,
            'small_rocketperf_#00'   =>  60,
            'small_waterdetect_#00'  => 100,
        ];

        if (isset($water_db[$building->getPrototype()->getName()]))
            $well += $water_db[$building->getPrototype()->getName()];


        $town->setWell( $town->getWell() + $well );
        if ($well > 0)
            $this->entity_manager->persist( $this->log->constructionsBuildingCompleteWell( $building, $well ) );

        switch ($building->getPrototype()->getName()) {
            case 'small_fireworks_#00':case 'small_balloon_#00':
                $all = $building->getPrototype()->getName() === 'small_balloon_#00';
                foreach ($town->getZones() as &$zone)
                    if ($all || $zone->getPrototype()) {
                        $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                        $zone->setZombieStatus( max( $zone->getZombieStatus(), Zone::ZombieStateEstimate ) );
                    }
                break;
            case 'small_rocket_#00':
                foreach ($town->getZones() as &$zone)
                    if ($zone->getX() === 0 || $zone->getY() === 0) {
                        $zone->setZombies(0);
                        $zone->getEscapeTimers()->clear();
                    }
                break;
            case 'small_cafet_#00':
                $proto = $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'woodsteak_#00' );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $this->item_factory->createItem( $proto ) );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $this->item_factory->createItem( $proto ) );
                $this->entity_manager->persist( $this->log->constructionsBuildingCompleteSpawnItems( $building, [ [$proto,2] ] ) );
                break;
            case 'r_dhang_#00':case 'small_fleshcage_#00':
                foreach ($town->getCitizens() as $citizen)
                    if ($this->citizen_handler->updateBanishment( $citizen, $building->getPrototype()->getName() === 'r_dhang_#00' ? $building : $this->getBuilding( $town, 'r_dhang_#00', true ), $building->getPrototype()->getName() === 'small_fleshcage_#00' ? $building : $this->getBuilding( $town, 'small_fleshcage_#00', true ) ))
                        $this->entity_manager->persist($town);
                break;
            case 'small_redemption_#00':
                foreach ($town->getCitizens() as $citizen)
                    if ($citizen->getBanished()) {
                        foreach ($this->entity_manager->getRepository(Complaint::class)->findByCulprit($citizen) as $complaint) {
                            /** @var $complaint Complaint */
                            $complaint->setSeverity(0);
                            $this->entity_manager->persist($complaint);
                        }
                        $citizen->setBanished(false);
                        $this->entity_manager->persist($citizen);
                    }
                break;
            case "small_castle_#00":
            case "small_pmvbig_#00":
            case "small_wheel_#00":
            case "small_crow_#00":
                $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_ebuild_#00");
                foreach ($town->getCitizens() as $citizen)
                    if ($citizen->getAlive()) {
                        $this->picto_handler->give_picto($citizen, $picto);
                    }
                break;
            default: break;
        }
    }

    public function addBuilding( Town &$town, BuildingPrototype $prototype ): ?Building {

        // Do not add a building that already exist
        $parent_available = empty($prototype->getParent());
        foreach ($town->getBuildings() as $b) {
            if ($b->getPrototype()->getId() === $prototype->getId())
                return $b;
            $parent_available = $parent_available || ($b->getPrototype()->getId() === $prototype->getParent()->getId());
        }

        // Do not add building if parent does not exist; skip for buildings without parent
        if (!$parent_available) return null;

        return $this->internalAddBuilding( $town, $prototype );
    }

    public function getBuilding(Town $town, $prototype, $finished = true): ?Building {
        //if (isset($this->building_cache[]))

        if (is_string($prototype))
            $prototype = $this->entity_manager->getRepository(BuildingPrototype::class)->findOneByName($prototype);

        if (!$prototype) return null;
        foreach ($town->getBuildings() as $b)
            if ($b->getPrototype()->getId() === $prototype->getId())
                return (!$finished || $b->getComplete()) ? $b : null;
        return null;
    }

    public function calculate_home_def( CitizenHome &$home, ?HomeDefenseSummary &$summary = null): int {
        $town = $home->getCitizen()->getTown();

        $summary = new HomeDefenseSummary();
        if (!$home->getCitizen()->getAlive())
            return 0;

        $summary->house_defense = $home->getPrototype()->getDefense();


        if ($home->getCitizen()->getProfession()->getHeroic())
            $summary->job_defense += 2;

        if ($this->getBuilding($town, 'small_city_up_#00', true))
            $summary->house_defense += 4;

        if ($home->getCitizen()->getProfession()->getHeroic()) {
            /** @var CitizenHomeUpgrade|null $n */
            $n = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $home,
                $this->entity_manager->getRepository( CitizenHomeUpgradePrototype::class )->findOneByName( 'defense' )
            );
            $summary->upgrades_defense = ($n ? $n->getLevel() : 0) + $home->getAdditionalDefense();
        } else $summary->upgrades_defense = $home->getAdditionalDefense();


        $summary->item_defense = $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->inventory_handler->resolveItemProperties( 'defence' )
        );

        return $summary->sum();
    }

    public function calculate_building_def( Town &$town, Building $building ): int {
        $d = 0;

        if ($building->getPrototype()->getName() === 'item_tube_#00' && $building->getLevel() > 0) {
            $n = [0,2,4,6,9,12];

            if ($town->getWell() >= $n[ $building->getLevel() ])
                $d += $building->getDefenseBonus();
            $d += $building->getPrototype()->getDefense();

        } elseif ($building->getPrototype()->getName() === 'small_cemetery_#00' || $building->getPrototype()->getName() === 'small_coffin_#00') {

            $c = 0;
            foreach ($town->getCitizens() as $citizen) if (!$citizen->getAlive()) $c++;
            $d += ( 10*$c + $building->getDefenseBonus() + $building->getPrototype()->getDefense() );

        }
        else $d += ( $building->getDefenseBonus() + $building->getPrototype()->getDefense() );
        $d += $building->getTempDefenseBonus();

        return $d;
    }

    public function calculate_town_def( Town &$town, ?TownDefenseSummary &$summary = null ): int {
        $summary = new TownDefenseSummary();

        $f_house_def = 0.0;
        $summary->guardian_defense = 0;

        $home_def_factor = $this->getBuilding( $town, 'small_strategy_#00', true ) ? 0.8 : 0.4;

        $pentagon = $this->getBuilding( $town, 'item_shield_#00', true );
        if ($pentagon) {
            if     ($pentagon->getLevel() === 2) $summary->overall_scale += 0.14;
            elseif ($pentagon->getLevel() === 1) $summary->overall_scale += 0.12;
            else                                 $summary->overall_scale += 0.10;
        }

        $guardian_bonus = $this->getBuilding($town, 'small_watchmen_#00', true) ? 15 : 5;

        foreach ($town->getCitizens() as $citizen)
            if ($citizen->getAlive()) {
                $home = $citizen->getHome();
                $f_house_def += $this->calculate_home_def( $home ) * $home_def_factor;

                if (!$citizen->getZone() && $citizen->getProfession()->getName() === 'guardian')
                    $summary->guardian_defense += $guardian_bonus;
            }
        $summary->house_defense = floor($f_house_def);
        $summary->building_defense = 0;
        $item_def_factor = 1.0;
        foreach ($town->getBuildings() as $building)
            if ($building->getComplete()) {

                $summary->building_defense += $this->calculate_building_def( $town, $building );

                if ($building->getPrototype()->getName() === 'item_meca_parts_#00')
                    $item_def_factor += (1+$building->getLevel()) * 0.5;
            }


        $summary->item_defense = floor($this->inventory_handler->countSpecificItems( $town->getBank(),
            $this->inventory_handler->resolveItemProperties( 'defence' )
        ) * $item_def_factor);

        if ($summary->item_defense > 500)
            $summary->item_defense = 500;

        $summary->soul_defense = $town->getSoulDefense();
        
        return $summary->sum();
    }

    public function get_zombie_estimation_quality(Town &$town, int $future = 0, ?int &$min = null, ?int &$max = null): float {
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()+$future);
        if (!$est) return 0;

        $min = round( $est->getZombies() - $est->getZombies() * $est->getOffsetMin()/100);
        $max = round( $est->getZombies() + $est->getZombies() * $est->getOffsetMax()/100);
        return 1 - (($est->getOffsetMin() + $est->getOffsetMax()) - 10) / 24;
    }

    public function calculate_zombie_attacks(Town &$town, int $future = 2) {
        if ($future < 0) return;
        $d = $town->getDay();
        for ($current_day = $d; $current_day <= ($d+$future); $current_day++)
            if (!$this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$current_day)) {
                $min = round( ($current_day <= 3 ? 0.66 : 1.0) * pow(max(1,$current_day-1) * 0.75 + 2.5,3) );
                $max = round( ($current_day <= 3 ? ($current_day <= 1 ? 0.4 : 0.66) : 1.0) * pow($current_day * 0.75 + 3.5,3) );

                $value = mt_rand($min,$max);
                if ($value > ($min + 0.5 * ($max-$min))) $value = mt_rand($min,$max);

                $off_min = mt_rand( 10, 24 );
                $off_max = 34 - $off_min;

                $town->addZombieEstimation(
                    (new ZombieEstimation())->setDay( $current_day )->setZombies( $value )->setOffsetMin( $off_min )
                    ->setOffsetMax( $off_max )
                );
            }

    }
}