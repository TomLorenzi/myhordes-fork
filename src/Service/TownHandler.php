<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenWatch;
use App\Entity\Complaint;
use App\Entity\Gazette;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Inventory;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Structures\HomeDefenseSummary;
use App\Structures\TownDefenseSummary;
use App\Service\ConfMaster;
use App\Structures\TownConf;
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
    private $picto_handler;
    private $conf;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, ItemFactory $if, LogTemplateHandler $lh, TimeKeeperService $tk, CitizenHandler $ch, PictoHandler $ph, ConfMaster $conf)
    {
        $this->entity_manager = $em;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->log = $lh;
        $this->timeKeeper = $tk;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->conf = $conf;
    }

    private function internalAddBuilding( Town &$town, BuildingPrototype $prototype ): ?Building {

        // Add building
        $town->addBuilding( $b = (new Building())->setPrototype( $prototype )->setPosition($prototype->getOrderBy()) );

        $blocked = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_BUILDINGS);

        // Add all children that do not require blueprints
        if ($b)
            foreach ( $prototype->getChildren() as $child )
                if ($child->getBlueprint() == 0 && !in_array($child->getName(), $blocked)) $this->internalAddBuilding( $town, $child );
        return $b;
    }

    public function triggerAlways( Town $town ): bool {
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

        if ( !$town->getDoor() && $town->getDevastated() ) {
            $town->setDoor( true );
            $changed = true;
        }

        return $changed;
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

        $pictos = [];

        $building->setHp($building->getPrototype()->getHp());
        
        $building->setDefense($building->getPrototype()->getDefense());

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
                $this->entity_manager->persist( $this->log->constructionsBuildingCompleteSpawnItems( $building, [ ['item'=>$proto,'count'=>2] ] ) );
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
            case "small_lastchance_#00":
                $destroyedItems = 0;
                $bank = $town->getBank();               
                foreach ($bank->getItems() as $bankItem) {
                    $count = $bankItem->getcount();
                    $this->inventory_handler->forceRemoveItem($bankItem, $count);
                    $destroyedItems+= $count;
                }
                $this->getBuilding($town, "small_lastchance_#00")->setTempDefenseBonus($destroyedItems);
                $this->entity_manager->persist( $this->log->constructionsBuildingCompleteAllOrNothing($town, $destroyedItems ) );
                break;
            case "small_castle_#00":
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebcstl_#00"]);
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebuild_#00"]);
                break;
            case "small_pmvbig_#00":
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebpmv_#00"]);
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebuild_#00"]);
                break;
            case "small_wheel_#00":
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebgros_#00"]);
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebuild_#00"]);
                break;
            case "small_crow_#00":
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebcrow_#00"]);
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_ebuild_#00"]);
                break;
            case "item_electro_#00":
                $zones = $town->getZones();
                foreach ($zones as $zone) {
                    $zone->setZombieStatus(Zone::ZombieStateExact);
                    $this->entity_manager->persist($zone);
                }
                break;
            default: break;
        }

        // If this is a child of fundament, give a picto
        if($building->getPrototype()->getParent() !== null && $building->getPrototype()->getParent()->getName() === 'small_building_#00'){
            $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_wondrs_#00"]);
        }

        foreach ($town->getCitizens() as $target_citizen) {
            if (!$target_citizen->getAlive()) continue;

            foreach ($pictos as $picto) {
                $this->picto_handler->give_picto($target_citizen, $picto);
            }
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

        $summary->upgrades_defense = $home->getAdditionalDefense();

        if ($home->getCitizen()->getProfession()->getHeroic()) {
            /** @var CitizenHomeUpgrade|null $n */
            $n = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $home,
                $this->entity_manager->getRepository( CitizenHomeUpgradePrototype::class )->findOneByName( 'defense' )
            );

            if($n) {
                if($n->getLevel() <= 6)
                    $summary->upgrades_defense += $n->getLevel();
                else {
                    $summary->upgrades_defense += 6 + 2 * ($n->getLevel() - 6);
                }
            }

            $n = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $home,
                $this->entity_manager->getRepository( CitizenHomeUpgradePrototype::class )->findOneByName( 'fence' )
            );
            $summary->upgrades_defense += ($n ? 3 : 0);
        }


        $summary->item_defense = $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->inventory_handler->resolveItemProperties( 'defence' ), false, false
        );

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
            'soul_blue_#00'
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
            'soul_blue_#01'
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
            'soul_red_#00'
        ) * 2;

        return $summary->sum();
    }

    public function calculate_building_def( Town &$town, Building $building ): int {
        $d = 0;

        if ($building->getPrototype()->getName() === 'item_tube_#00' && $building->getLevel() > 0) {
            $n = [0,2,4,6,9,12];

            if ($town->getWell() >= $n[ $building->getLevel() ])
                $d += $building->getDefenseBonus();
            $d += $building->getDefense();

        } elseif ($building->getPrototype()->getName() === 'small_cemetery_#00' || $building->getPrototype()->getName() === 'small_coffin_#00') {

            $c = 0;
            foreach ($town->getCitizens() as $citizen) if (!$citizen->getAlive()) $c++;
            $d += ( 10*$c + $building->getDefenseBonus() + $building->getDefense() );

        }
        else $d += ( $building->getDefenseBonus() + $building->getDefense() );
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
            $this->inventory_handler->resolveItemProperties( 'defence' ), false, false
        ) * $item_def_factor);

        if ($summary->item_defense > 500)
            $summary->item_defense = 500;

        $summary->soul_defense = $town->getSoulDefense();

        $summary->nightwatch_defense = $this->calculate_watch_def($town);
        
        return $summary->sum();
    }

    public function calculate_watch_def(Town $town){
        $total_def = 0;
        $has_counsel = false;

        $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findCurrentWatchers($town);

        $has_shooting_gallery = (bool)$this->getBuilding($town, 'small_tourello_#00', true);
        $has_trebuchet        = (bool)$this->getBuilding($town, 'small_catapult3_#00', true);
        $has_ikea             = (bool)$this->getBuilding($town, 'small_ikea_#00', true);
        $has_armory           = (bool)$this->getBuilding($town, 'small_armor_#00', true);

        foreach ($watchers as $watcher) {
            $total_def += $this->citizen_handler->getNightWatchDefense($watcher->getCitizen(), $has_shooting_gallery, $has_trebuchet, $has_ikea, $has_armory);
            foreach ($watcher->getCitizen()->getInventory()->getItems() as $item) {
                if($item->getPrototype()->getName() == 'chkspk_#00') {
                    $has_counsel = true;
                    break;
                }
            }
        }

        if($has_counsel)
            $total_def += 20 * count($watchers);

        return $total_def;
    }

    public function get_zombie_estimation_quality(Town &$town, int $future = 0, ?int &$min = null, ?int &$max = null): float {
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town, $town->getDay() + $future);
        if (!$est) return 0;

        $offsetMin = $est->getOffsetMin();
        $offsetMax = $est->getOffsetMax();

        $min = round($est->getZombies() - ($est->getZombies() * $offsetMin / 100));
        $max = round($est->getZombies() + ($est->getZombies() * $offsetMax / 100));

        $soulFactor = min(1 + (0.04 * $this->get_red_soul_count($town)), (float)$this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_RED_SOUL_FACTOR, 1.2));

        $min = round($min * $soulFactor, 0);
        $max = round($max * $soulFactor, 0);

        return min((1 - (($offsetMin + $offsetMax) - 10) / 24), 1);
    }

    public function calculate_zombie_attacks(Town &$town, int $future = 2) {
        if ($future < 0) return;
        $d = $town->getDay();
        for ($current_day = $d; $current_day <= ($d+$future); $current_day++)
            if (!$this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$current_day)) {
                $mode = $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_FEATURE_ATTACKS, 'normal');
                switch($mode){
                    case "hard":
                        $max_ratio = 3.0;
                        break;
                    case "easy":
                        $max_ratio = 0.66;
                        break;
                    case "normal":
                    default:
                        $max_ratio = 1.0;
                        break;
                }
                $ratio_min = ($current_day <= 3 ? 0.66 : $max_ratio);
                $ratio_max = ($current_day <= 3 ? ($current_day <= 1 ? 0.4 : 0.66) : $max_ratio);

                $min = round( $ratio_min * pow(max(1,$current_day-1) * 0.75 + 2.5,3) );
                $max = round( $ratio_max * pow($current_day * 0.75 + 3.5,3) );

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

    public function check_gazettes(Town $town) {
        $need = [ $town->getDay() => true, $town->getDay() + 1 => true, $town->getDay() + 2 => true ];

        foreach ($town->getGazettes() as $gazette)
            if (isset($need[$gazette->getDay()])) $need[$gazette->getDay()] = false;

        foreach ($need as $day => $create)
            if ($create) $town->addGazette((new Gazette())->setDay($day));

    }

    public function get_alive_citizens(Town &$town){
        $citizens = [];
        foreach ($town->getCitizens() as $citizen) {
            if($citizen->getAlive())
                $citizens[] = $citizen;
        }

        return $citizens;
    }

    public function destroy_building(Town &$town, Building $building, ?callable $trigger_after = null){
        if(!$building->getComplete()) return;

        $building->setComplete(false)->setAp(0)->setDefense(0)->setHp(0);

        $this->entity_manager->persist($building);

        //foreach ($building->getPrototype()->getChildren() as $childBuilding) {
        //    $this->destroy_building($town, $childBuilding);
        //}
        
        if($trigger_after) $trigger_after();
    }

    public function get_red_soul_count(Town &$town){
        // Get all inventory IDs from the town
        // We're just getting IDs, because we don't want to actually hydrate the inventory instances
        $zone_invs = array_column($this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i')
            ->join("i.zone", "z")
            ->andWhere('z.id IN (:zones)')->setParameter('zones', $town->getZones())
            ->getQuery()->getScalarResult(), 'id');

        $chest_invs = array_column($this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i')
            ->join("i.home", "h")
            ->join('h.citizen', 'c')
            ->where('c.id IN (:citizens)')->setParameter('citizens', $town->getCitizens())
            ->getQuery()->getScalarResult(), 'id');

        $citizens_inv = array_column($this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i')
            ->join('i.citizen', 'c')
            ->where('c.id IN (:citizens)')->setParameter('citizens', $town->getCitizens())
            ->getQuery()->getScalarResult(), 'id');

        // Get all red soul items within these inventories
        $query = $this->entity_manager->createQueryBuilder()
            ->select('SUM(i.count)')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory IN (:invs)')->setParameter('invs', array_merge($zone_invs, [$town->getBank()->getId()], $chest_invs, $citizens_inv))
            ->andWhere('i.prototype IN (:protos)')->setParameter('protos', [
                $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('soul_red_#00')
            ])
            ->getQuery();

        $redSoulsCount = $query->getSingleScalarResult();

        return $redSoulsCount;
    }
}