<?php


namespace App\Service;


use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\CitizenWatch;
use App\Entity\Complaint;
use App\Entity\EventActivationMarker;
use App\Entity\ExpeditionRoute;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Inventory;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\Town;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Entity\ZoneTag;
use App\Enum\ZoneActivityMarkerType;
use App\Structures\EventConf;
use App\Structures\HomeDefenseSummary;
use App\Structures\TownDefenseSummary;
use App\Structures\TownConf;
use App\Structures\WatchtowerEstimation;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TownHandler
{
    private EntityManagerInterface $entity_manager;
    private InventoryHandler $inventory_handler;
    private ItemFactory $item_factory;
    private LogTemplateHandler $log;
    private TimeKeeperService $timeKeeper;
    private CitizenHandler $citizen_handler;
    private PictoHandler $picto_handler;
    private RandomGenerator $random;
    private ConfMaster $conf;
    private CrowService $crowService;
    private TranslatorInterface $translator;
	private Packages $asset;
	private ContainerInterface $container;

    private $protoSingletons = [];
    private $protoDefenceItems = null;


    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, ItemFactory $if, LogTemplateHandler $lh,
        TimeKeeperService $tk, CitizenHandler $ch, PictoHandler $ph, ConfMaster $conf, RandomGenerator $rand,
        CrowService $armbrust, TranslatorInterface $translator, ContainerInterface $container, Packages $asset)
    {
        $this->entity_manager = $em;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->log = $lh;
        $this->timeKeeper = $tk;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->conf = $conf;
        $this->random = $rand;
        $this->crowService = $armbrust;
        $this->translator = $translator;
		$this->asset = $asset;
		$this->container = $container;
    }

    /**
     * @return mixed
     */
    public function getProtoSingleton($repository, $name)
    {
        if(!array_key_exists($name, $this->protoSingletons)){
            $this->protoSingletons[$name] = $this->entity_manager->getRepository($repository)->findOneByName($name);
        }
        return $this->protoSingletons[$name];
    }

    /**
     * @return ItemPrototype[]
     */
    public function getPrototypesForDefenceItems(): array
    {
        if($this->protoDefenceItems == null){
            $this->protoDefenceItems = $this->inventory_handler->resolveItemProperties('defence');
        }
        return $this->protoDefenceItems;
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

    /**
     * Triggers that should be always run once a day
     *
     * @param Town $town The town on which we run the triggers
     * @return boolean Did something changed ?
     */
    public function triggerAlways( Town $town, bool $attack = false ): bool {
        $changed = false;

        if ( $town->getDoor() && !$town->getDevastated() && (($s = $attack ? 0 : $this->timeKeeper->secondsUntilNextAttack(null, true))) <= 1800 ) {

            $close_ts = null;
            if ($this->getBuilding( $town, 'small_door_closed_#02' )) {
                if ($s <= 60)
                    $close_ts = $this->timeKeeper->getCurrentAttackTime()->sub(DateInterval::createFromDateString('1min'));
            } elseif ($this->getBuilding( $town, 'small_door_closed_#01' )) {
                if ($s <= 1800)
                    $close_ts = $this->timeKeeper->getCurrentAttackTime()->sub(DateInterval::createFromDateString('30min'));
            }

            if ($close_ts !== null) {
                $town->setDoor( false );
                $this->entity_manager->persist( $this->log->doorControlAuto( $town, false, $close_ts) );
                $zeroZero = $this->entity_manager->getRepository(Zone::class)->findOneBy(['town' => $town, 'x' => 0, 'y' => 0]);
                $proxy = $town->getCitizens()[0] ?? null;
                if ($zeroZero && $proxy) {
                    $zeroZero->addActivityMarker( (new ZoneActivityMarker())
                                                      ->setCitizen( $proxy )
                                                      ->setTimestamp( $close_ts )
                                                      ->setType(ZoneActivityMarkerType::DoorAutoClosed));
                    $this->entity_manager->persist($zeroZero);
                }
                $changed = true;
            }

        }

        if ( !$town->getDoor() && $town->getDevastated() ) {
            $town->setDoor( true );
            $changed = true;
        }

        return $changed;
    }

    /**
     * Triggers the events that should happen upon a building completion
     *
     * @param Town $town The town concerned by the trigger
     * @param Building $building The building that has just been finished
     * @return void
     */
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
            /*case 'small_fireworks_#00':*/case 'small_balloon_#00':
                $all = $building->getPrototype()->getName() === 'small_balloon_#00';
                $state = $this->getBuilding($town, 'item_electro_#00', true) ? Zone::ZombieStateExact : Zone::ZombieStateEstimate;
                foreach ($town->getZones() as &$zone)
                    if ($all || $zone->getPrototype()) {
                        $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                        $zone->setZombieStatus( max( $zone->getZombieStatus(), $state ) );
                    }
                break;
            case 'small_rocket_#00':
                foreach ($town->getZones() as &$zone)
                    if ($zone->getX() === 0 || $zone->getY() === 0) {
                        $zone->setZombies(0)->setInitialZombies(0);
                        $zone->getEscapeTimers()->clear();
                    }
                $this->entity_manager->persist( $this->log->constructionsBuildingCompleteZombieKill( $building ) );
                break;
            case 'small_cafet_#00':
                $proto = $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => 'woodsteak_#00'] );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $this->item_factory->createItem( $proto ) );
                $this->inventory_handler->forceMoveItem( $town->getBank(), $this->item_factory->createItem( $proto ) );
                $this->entity_manager->persist( $town->getBank() );
                $this->entity_manager->persist( $this->log->constructionsBuildingCompleteSpawnItems( $building, [ ['item'=>$proto,'count'=>2] ] ) );
                break;
            case 'r_dhang_#00':case 'small_fleshcage_#00':case 'small_eastercross_#00':
                // Only insta-kill on building completion when shunning is enabled
                if ($this->conf->getTownConfiguration($town)->get(TownConf::CONF_FEATURE_SHUN, true))
                    foreach ($town->getCitizens() as $citizen)
                        if ($this->citizen_handler->updateBanishment( $citizen, ($building->getPrototype()->getName() === 'r_dhang_#00' || $building->getPrototype()->getName() === 'small_eastercross_#00') ? $building : ($this->getBuilding( $town, 'r_dhang_#00', true ) ?? $this->getBuilding( $town, 'small_eastercross_#00', true )), $building->getPrototype()->getName() === 'small_fleshcage_#00' ? $building : $this->getBuilding( $town, 'small_fleshcage_#00', true ) ))
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
                        $this->citizen_handler->inflictStatus( $citizen, 'tg_unban_altar' );
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
            case "item_courroie_#00":
                $this->assignCatapultMaster($town);
                break;
            case "small_novlamps_#00":
                // If the novelty lamps are built, it's effect must be applied immediately
                $novlamp_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_novlamps']);
                foreach ($town->getCitizens() as $citizen) {
                    if ($citizen->getAlive()) $this->citizen_handler->inflictStatus($citizen, $novlamp_status);
                    $this->entity_manager->persist($citizen);
                }

                break;
            default: break;
        }

        // If this is a child of fundament, give a picto
        $parent = $building->getPrototype()->getParent();
        while($parent != null) {
            if ($parent->getName() === "small_building_#00") {
                $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_wondrs_#00"]);
                break;
            }
            $parent = $parent->getParent();
        }

        /*if($building->getPrototype()->getParent() !== null && $building->getPrototype()->getParent()->getName() === 'small_building_#00'){
            $pictos[] = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_wondrs_#00"]);
        }*/

        foreach ($town->getCitizens() as $target_citizen) {
            if (!$target_citizen->getAlive()) continue;

            foreach ($pictos as $picto) {
                $this->picto_handler->give_picto($target_citizen, $picto);
            }
        }
    }

    public function assignCatapultMaster(Town $town, bool $allow_multi = false): ?Citizen {

        $choice = [];
        $current = 0;

        foreach($town->getCitizens() as $citizen) {
            if (!$citizen->getAlive() || $citizen->getBanished()) continue;
            if (!$allow_multi && $citizen->hasRole('cata')) return null;

            $level = 0;
            if($this->citizen_handler->hasStatusEffect($citizen, 'tg_chk_forum')) $level++;
            if($this->citizen_handler->hasStatusEffect($citizen, 'tg_chk_active')) $level++;
            if($this->citizen_handler->hasStatusEffect($citizen, 'tg_chk_workshop')) $level++;
            if($this->citizen_handler->hasStatusEffect($citizen, 'tg_chk_build')) $level++;
            if($this->citizen_handler->hasStatusEffect($citizen, 'tg_chk_movewb')) $level++;

            if ($citizen->getProfession()->getHeroic()) $level *= 10;

            if ($level > $current) {
                $choice = [$citizen];
                $current = $level;
            } elseif ($level === $current) {
                $choice[] = $citizen;
            }
        }

        /** @var Citizen|null $selected */
        $selected = empty($choice) ? null : $this->random->pick($choice);

        if ($selected) {
            $selected->addRole( $this->getProtoSingleton(CitizenRole::class, 'cata'));
            $this->crowService->postAsPM($selected, '', '', PrivateMessage::TEMPLATE_CROW_CATAPULT, $selected->getId());
        }

        return $selected;
    }

    /**
     * Add a building to the list of unlocked building
     *
     * @param Town $town The town we want to add a building to
     * @param BuildingPrototype $prototype The prototype we want to add
     * @return Building|null The building of the town, or null if it is not unlockable yet
     */
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

    /**
     * Remove a building from the list of unlocked building
     *
     * @param Town $town The town we want to add a building to
     * @param BuildingPrototype $prototype The prototype we want to add
     * @return bool If the removal succeeded
     */
    public function removeBuilding( Town &$town, BuildingPrototype $prototype ): bool {

        $building = $this->entity_manager->getRepository(Building::class)->findOneBy(['town' => $town, 'prototype' => $prototype]);
        if($building){
            $children = $prototype->getChildren();
            foreach ($children as $child) {
                $this->removeBuilding($town, $child);
            }
            $town->removeBuilding($building);
        }

        return true;
    }

    /**
     * Return the wanted building
     *
     * @param Town $town The town we're looking the building into
     * @param String|BuildingPrototype $prototype The prototype of the building (name of prototype or Prototype Entity)
     * @param boolean $finished Do we want the building if is finished, null otherwise ?
     * @return Building|null
     */
    public function getBuilding(Town $town, $prototype, $finished = true): ?Building {
        if (is_string($prototype))
            $prototype = $this->getProtoSingleton(BuildingPrototype::class, $prototype);

        if (!$prototype) return null;
        foreach ($town->getBuildings() as $b)
            if ($b->getPrototype()->getId() === $prototype->getId())
                return (!$finished || $b->getComplete()) ? $b : null;
        return null;
    }

    public function getBuildingPrototype(string $prototype, bool $cache = false): ?BuildingPrototype {
        return $this->entity_manager->getRepository(BuildingPrototype::class)->findOneByName($prototype, $cache);
    }

    /**
     * Calculate the citizen's home defense
     *
     * @param CitizenHome $home The citizen home
     * @param HomeDefenseSummary|null $summary The defense summary
     * @return integer The total home defense
     */
    public function calculate_home_def( CitizenHome $home, ?HomeDefenseSummary &$summary = null): int {
        $town = $home->getCitizen()->getTown();
        $homeUpgrades = $home->getCitizenHomeUpgrades()->count() > 0 ? $home->getCitizenHomeUpgrades()->getValues() : [];

        $homeUpgradesPrototypes =
            array_map(
                function($item) {
                    return $item->getPrototype();
                },
                $homeUpgrades
            );

        $summary = new HomeDefenseSummary();
        if (!$home->getCitizen()->getAlive())
            return 0;

        $summary->house_defense = $home->getPrototype()->getDefense();

        if ($home->getCitizen()->getProfession()->getHeroic()) {
            $summary->job_defense += 2;
            if ($home->getCitizen()->getProfession()->getName() === 'guardian')
                $summary->job_guard_defense += 1;
        }

        if ($this->getBuilding($town, 'small_city_up_#00', true))
            $summary->house_defense += 4;

        $summary->upgrades_defense = $home->getAdditionalDefense();

        if ($home->getCitizen()->getProfession()->getHeroic()) {
            /** @var CitizenHomeUpgrade|null $n */
            $defenseIndex = array_search($this->getProtoSingleton(CitizenHomeUpgradePrototype::class,"defense"), $homeUpgradesPrototypes);

            if($defenseIndex) {
                $n = $homeUpgrades[$defenseIndex];
                if($n->getLevel() <= 6)
                    $summary->upgrades_defense += $n->getLevel();
                else {
                    $summary->upgrades_defense += 6 + 2 * ($n->getLevel() - 6);
                }
            }

            $n = in_array($this->getProtoSingleton(CitizenHomeUpgradePrototype::class,"fence"), $homeUpgradesPrototypes);
            $summary->upgrades_defense += ($n ? 3 : 0);
        }


        $summary->item_defense = $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->getPrototypesForDefenceItems(), false, false
        );

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->getProtoSingleton(ItemPrototype::class, "soul_blue_#00")
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
                $this->getProtoSingleton(ItemPrototype::class, "soul_blue_#01")
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
                $this->getProtoSingleton(ItemPrototype::class, "soul_red_#00")
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

        } elseif ($building->getPrototype()->getName() === 'small_cemetery_#00') {

            $c = 0;
            foreach ($town->getCitizens() as $citizen) if (!$citizen->getAlive()) $c++;
            $ratio = 10;
            if ($this->getBuilding($town, 'small_coffin_#00'))
                $ratio = 20;
            $d += ( $ratio * $c + $building->getDefenseBonus() + $building->getDefense() );

        }
        else $d += ( $building->getDefenseBonus() + $building->getDefense() );
        // $d += $building->getTempDefenseBonus();
        // Temp defense is handled separately

        return $d;
    }

    public function calculate_town_def( Town &$town, ?TownDefenseSummary &$summary = null ): int {
        $summary = new TownDefenseSummary();
        $summary->base_defense = $town->getBaseDefense();
        $summary->base_defense += $town->getStrangerPower();

        $f_house_def = 0.0;
        $summary->guardian_defense = 0;

        $home_def_factor = $this->getBuilding( $town, 'small_strategy_#00', true ) ? 0.8 : 0.4;

        $pentagon = $this->getBuilding( $town, 'item_shield_#00', true );
        if ($pentagon) {
            if     ($pentagon->getLevel() === 2) $summary->overall_scale += 0.14;
            elseif ($pentagon->getLevel() === 1) $summary->overall_scale += 0.12;
            else                                 $summary->overall_scale += 0.10;
        }

        $guardian_bonus = $this->getBuilding($town, 'small_watchmen_#00', true) ? 10 : 5;

        $deadCitizens = 0;

        foreach ($town->getCitizens() as $citizen) {
            if ($citizen->getAlive()) {
                $home = $citizen->getHome();
                $this->calculate_home_def($home, $home_summary);
                /** @var HomeDefenseSummary $home_summary */
                $f_house_def += ($home_summary->house_defense + $home_summary->job_defense + $home_summary->upgrades_defense) * $home_def_factor;

                if ($citizen->getProfession()->getName() === 'guardian' && !$citizen->getZone())
                    $summary->guardian_defense += $guardian_bonus;
            } else {
                $deadCitizens++;
            }
        }
        $summary->house_defense = floor($f_house_def);
        $item_def_factor = 1.0;
        foreach ($town->getBuildings() as $building)
            if ($building->getComplete()) {

                $summary->building_defense += $this->calculate_building_def( $town, $building );
                $summary->building_def_base += $building->getDefense();
                $summary->building_def_vote += $building->getDefenseBonus();
                $summary->temp_defense += $building->getTempDefenseBonus();

                if ($building->getPrototype()->getName() === 'item_meca_parts_#00')
                    $item_def_factor += (1+$building->getLevel()) * 0.5;
                else if ($building->getPrototype()->getName() === "small_cemetery_#00") {
                    $ratio = 10;
                    if ($this->getBuilding($town, 'small_coffin_#00'))
                        $ratio = 20;
                    $summary->cemetery = $ratio * $deadCitizens;
                }
            }

        $summary->temp_defense += $town->getTempDefenseBonus();


        $summary->item_defense = min(500, floor($this->inventory_handler->countSpecificItems( $town->getBank(),
                $this->getPrototypesForDefenceItems(), false, false
        ) * $item_def_factor));

        $summary->soul_defense = $town->getSoulDefense();

        $summary->nightwatch_defense = $this->calculate_watch_def($town);
        
        return $summary->sum();
    }

    public function calculate_watch_def(Town $town, int $day = 0){
        $total_def = 0;
        $has_counsel = false;

        if ($day <= 0) $day = ($town->getDay() - $day);
        $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchersOfDay($town,$day);

        $has_shooting_gallery = (bool)$this->getBuilding($town, 'small_tourello_#00', true);
        $has_trebuchet        = (bool)$this->getBuilding($town, 'small_catapult3_#00', true);
        $has_ikea             = (bool)$this->getBuilding($town, 'small_ikea_#00', true);
        $has_armory           = (bool)$this->getBuilding($town, 'small_armor_#00', true);

        $count = 0;
        foreach ($watchers as $watcher) {
            if ($watcher->getCitizen()->getZone() !== null) continue;
            $count++;
            $total_def += $this->citizen_handler->getNightWatchDefense($watcher->getCitizen(), $has_shooting_gallery, $has_trebuchet, $has_ikea, $has_armory);
            foreach ($watcher->getCitizen()->getInventory()->getItems() as $item)
                if($item->getPrototype()->getName() == 'chkspk_#00') {
                    $has_counsel = true;
                    break;
                }
        }

        if($has_counsel)
            $total_def += 20 * $count;

        return $total_def;
    }

    public function get_zombie_estimation(Town &$town, int $day = null, $watchtower_offset = null): array {
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town, $day ?? $town->getDay());
        /** @var ZombieEstimation $est */
        if (!$est) return [];

        $ratio = 1;
        if ($this->getBuilding($town, 'item_tagger_#01') || $this->inventory_handler->countSpecificItems($town->getBank(), 'scope_#00', false, false) > 0) {
            $ratio *= 2;
        }

        $offsetMin = $est->getOffsetMin();
        $offsetMax = $est->getOffsetMax();

        $rand_backup = mt_rand(PHP_INT_MIN, PHP_INT_MAX);
        mt_srand($est->getSeed() ?? $town->getDay() + $town->getId());
        $cc_offset = $watchtower_offset ?? $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_WT_OFFSET, 0);
        $this->calculate_offsets($offsetMin, $offsetMax, $est->getCitizens()->count() * $ratio + $cc_offset, $this->conf->getTownConfiguration($town)->get(TownConf::CONF_ESTIM_SPREAD, 10) - $this->conf->getTownConfiguration($town)->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0));

        $min = round($est->getTargetMin() - ($est->getTargetMin() * $offsetMin / 100));
        $max = round($est->getTargetMax() + ($est->getTargetMax() * $offsetMax / 100));

        $soulFactor = min(1 + (0.04 * $this->get_red_soul_count($town)), (float)$this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_RED_SOUL_FACTOR, 1.2));

        $min = round($min * $soulFactor);
        $max = round($max * $soulFactor);

        $quality = min(($cc_offset + $est->getCitizens()->count()*$ratio) / 24, 1);
        $message = null;
        foreach ($this->conf->getCurrentEvents($town) as $e)
            $e->hook_watchtower_estimations($min,$max, $town, 0, $quality, $message);

        $estim = new WatchtowerEstimation();
        $estim->setMin($min);
        $estim->setMax($max);
        $estim->setEstimation($quality);
        $estim->setFuture(0);
        $estim->setMessage($message);

        $result = [$estim];

        if (!$this->getBuilding($town, 'item_tagger_#02')) {
            return $result;
        }

        if (($est->getCitizens()->count() * $ratio + $cc_offset) >= 24 && !empty($this->getBuilding($town, 'item_tagger_#02'))) {
            $estim->setEstimation(1);
            $calculateUntil = ($est->getCitizens()->count() * $ratio + $cc_offset) - 24;
            $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town, $town->getDay() + 1);

            /** @var ZombieEstimation $est */
            if (!$est) return  $result;

            $offsetMin = $est->getOffsetMin();
            $offsetMax = $est->getOffsetMax();

            $this->calculate_offsets($offsetMin, $offsetMax, $calculateUntil,  $this->conf->getTownConfiguration($town)->get(TownConf::CONF_ESTIM_SPREAD, 10));

            $min2 = round($est->getTargetMin() - ($est->getTargetMin() * $offsetMin / 100));
            $max2 = round($est->getTargetMax() + ($est->getTargetMax() * $offsetMax / 100));

            $min2 = round($min2 * $soulFactor);
            $max2 = round($max2 * $soulFactor);

            $min2 = floor($min2 / 25) * 25;
            $max2 = ceil($max2 / 25) * 25;

            $quality2 = min($calculateUntil / 24, 1);

            $message2 = null;
            foreach ($this->conf->getCurrentEvents($town) as $e)
                $e->hook_watchtower_estimations($min2,$max2, $town, 1, $quality2, $message2);

            $estim2 = new WatchtowerEstimation();
            $estim2->setMin($min2);
            $estim2->setMax($max2);
            $estim2->setEstimation($quality2);
            $estim2->setFuture(1);
            $estim2->setMessage($message2);
            $result[] = $estim2;
        }

        // We've set a pre-defined seed before, which will impact randomness of all mt_rand calls after this function
        // We're trying to set a new random seed to combat side effects
        try {
            mt_srand( random_int(PHP_INT_MIN, PHP_INT_MAX) );
        } catch (\Exception $e) {
            mt_srand($rand_backup);
        }

        return $result;
    }

    public function calculate_offsets(&$offsetMin, &$offsetMax, $nbRound, $min_spread = 10){
        $end = min($nbRound, 24);

        for ($i = 0; $i < $end; $i++) {
            $spendable = (max(0, $offsetMin - 3) + max(0, $offsetMax - 3)) / (24 - $i);
            $calc_next = fn() => mt_rand( floor($spendable * 250), floor($spendable * 1000) ) / 1000.0;

            if ($offsetMin + $offsetMax > $min_spread) {
                $increase_min = $this->random->chance($offsetMin / ($offsetMin + $offsetMax));
                $alter = $calc_next();
                if ($this->random->chance(0.25)){
                    $alterMax = $calc_next();
                    if($offsetMin > 3)
                        $offsetMin -= $alter;
                    if($offsetMax > 3)
                        $offsetMax -= $alterMax;
                } else {
                    if ($increase_min && $offsetMin > 3) $offsetMin -= $alter;
                    elseif ( $offsetMax > 3 ) $offsetMax -= $alter;
                }
            }
        }
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

                $off_min = mt_rand(
                    $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_OFFSET_MIN, 15) - $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0),
                    $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_OFFSET_MAX, 36) - $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0)
                );

                $off_max = $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_VARIANCE, 48) - (2*$this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0)) - $off_min;

                $shift_min = mt_rand(0, $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0) * 100) / 10000;
                $shift_max = ($this->conf->getTownConfiguration( $town )->get(TownConf::CONF_ESTIM_INITIAL_SHIFT, 0) / 100) - $shift_min;

                $town->addZombieEstimation(
                    (new ZombieEstimation())
                        ->setDay( $current_day )
                        ->setZombies( $value )
                        ->setOffsetMin( $off_min )
                        ->setOffsetMax( $off_max )
                        ->setTargetMin( round($value - ($value * $shift_min)) )
                        ->setTargetMax( round($value + ($value * $shift_max)) )
                );
            }
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

        $building->setComplete(false)->setAp(0)->setDefense(0)->setHp(0)->setLevel(0);

        $this->entity_manager->persist($building);

        //foreach ($building->getPrototype()->getChildren() as $childBuilding) {
        //    $this->destroy_building($town, $childBuilding);
        //}
        
        if($trigger_after) $trigger_after();
    }

    public function get_red_soul_count(Town &$town): int {
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
        return $this->entity_manager->createQueryBuilder()
            ->select('SUM(i.count)')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory IN (:invs)')->setParameter('invs', array_merge($zone_invs, [$town->getBank()->getId()], $chest_invs, $citizens_inv))
            ->andWhere('i.prototype IN (:protos)')->setParameter('protos', [
                $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('soul_red_#00')
            ])
            ->getQuery()->getSingleScalarResult() ?? 0;
    }

    /**
     * @param Citizen $citizen
     * @param EventConf[] $events
     * @return bool
     */
    protected function updateCurrentCitizenEvents( Citizen $citizen, array $events): bool {
        // Names of events that should be active after calling this function
        $active_names = array_map( fn(EventConf $e) => $e->name(), array_filter( $events, fn(EventConf $e) => $e->active() ));

        /** @var EventActivationMarker[] $citizen_markers */
        $old_events = $this->conf->getCurrentEvents($citizen, $citizen_markers);

        // Names of events that are currently active
        $current_names = array_map( fn(EventConf $e) => $e->name(), array_filter( $old_events, fn(EventConf $e) => $e->active() ));

        $pc = [];

        // Disable all old events that are not in the list of the new events
        foreach ($old_events as $old_event)
            if (!in_array($old_event->name(), $active_names) && $old_event->active()) {
                if (!$old_event->hook_disable_citizen($citizen)) return false;
                foreach ($citizen_markers as $marker)
                    if ($marker->getEvent() === $old_event->name())
                        $pc[] = $marker->setActive(false);
            }

        // Enable all new events that are not in the list of the old events
        foreach ($events as $event)
            if (!in_array($event->name(), $current_names) && $event->active()) {
                if (!$event->hook_enable_citizen($citizen)) return false;
                $pc[] = ( (new EventActivationMarker())
                    ->setCitizen($citizen)
                    ->setActive(true)
                    ->setEvent( $event->name() )
                );
            }

        // We're persisting all changes at the end, when it is sure that no activation failed
        foreach ($pc as $p) $this->entity_manager->persist($p);

        return true;
    }

    /**
     * @param Town $town
     * @param EventConf[] $events
     * @return bool
     */
    public function updateCurrentEvents(Town $town, array $events): bool {
        // Names of events that should be active after calling this function
        $active_names = array_map( fn(EventConf $e) => $e->name(), array_filter( $events, fn(EventConf $e) => $e->active() ));

        /** @var EventActivationMarker[] $town_markers */
        $old_events = $this->conf->getCurrentEvents($town, $town_markers);

        // Names of events that are currently active
        $current_names = array_map( fn(EventConf $e) => $e->name(), array_filter( $old_events, fn(EventConf $e) => $e->active() ));

        // First, toggle the events for all citizens
        foreach ($town->getCitizens() as $citizen)
            if (!$this->updateCurrentCitizenEvents( $citizen, $events )) return false;

        $pc = [];

        // Disable all old events that are not in the list of the new events
        foreach ($old_events as $old_event)
            if (!in_array($old_event->name(), $active_names) && $old_event->active()) {
                if (!$old_event->hook_disable_town($town)) return false;
                foreach ($town_markers as $marker)
                    if ($marker->getEvent() === $old_event->name())
                        $pc[] = $marker->setActive(false);
            }

        // Enable all new events that are not in the list of the old events
        foreach ($events as $event)
            if (!in_array($event->name(), $current_names) && $event->active()) {
                if (!$event->hook_enable_town($town)) return false;
                $pc[] = ( (new EventActivationMarker())
                    ->setTown($town)
                    ->setActive(true)
                    ->setEvent( $event->name() )
                );
            }

        // We're persisting all changes at the end, when it is sure that no activation failed
        foreach ($pc as $p) $this->entity_manager->persist($p);

        return true;
    }

    /**
     * @param Town $town
     * @param string|CitizenRole $role
     * @param bool $duringNightly
     * @return bool
     */
    public function is_vote_needed(Town $town, CitizenRole|string $role, bool $duringNightly = false): bool {
        // No votes needed before the town is full or during chaos
        if ($town->getChaos() || ($town->isOpen() && !$town->getForceStartAhead())) return false;

        // Resolve the role; if it does not exist or is not votable, no votes are needed
        if (is_string($role)) $role =  $this->getProtoSingleton(CitizenRole::class, $role);;
        if (!$role || !$role->getVotable()) return false;

        // If the role is disabled, no vote is needed
        if (in_array( $role->getName(), $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_ROLES, []) ))
            return false;

        $limit = ($duringNightly ? 1 : 0);

        // Check if the role has already been given
        /** @var Citizen $last_one */
        $last_one = $this->entity_manager->getRepository(Citizen::class)->findLastOneByRoleAndTown($role, $town);
        if ($last_one) {
            if ($last_one->getAlive()) return false;
            // Re-election when the role dies during attack: Next day; otherwise, one penalty day
            $DoD = $last_one->getCauseOfDeath()->getRef() === CauseOfDeath::NightlyAttack ? ($last_one->getDayOfDeath() - 1) : $last_one->getDayOfDeath();
            if ($DoD >= ($town->getDay() - $limit))
                return false;

        }
        return true;
    }

    public function getWorkshopBonus(Town $town, &$constructionBonus, &$repairBonus) {
        $constructionBonus = 0;
        $repairBonus = 0;
        if (($workshop = $this->getBuilding($town, "small_refine_#00")) !== null) {
            $constructionBonus = min(0.06 * $workshop->getLevel(), 0.28);
            if ($workshop->getLevel() >= 4) {
                $repairBonus = $workshop->getLevel() - 3;
            }
        }
    }

    public function devastateTown(Town $town) {
        if ($town->getDevastated()) return;

        $town->setDevastated(true)->setChaos(true)->setDoor( true );

        $lv0_home = $this->entity_manager->getRepository( CitizenHomePrototype::class )->findOneBy(['level' => 0]);
        foreach ($town->getCitizens() as $c)
            if ($c->getHome()->getPrototype()->getLevel() > 0)
                $this->entity_manager->persist( $c->getHome()->setPrototype($lv0_home) );
    }

	public function get_public_map_blob(Town $town, ?Citizen $activeCitizen, string $displayType, string $class = "day", string $endpoint = "radar", bool $admin = false): array {
		return [
			'displayType' => $displayType,
			'className' => $class,
			'etag'  => time(),
            'endpoint' => $endpoint,
			'fx' => !$admin && !$activeCitizen->getUser()->getDisableFx(),
		];
	}
}