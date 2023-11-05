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
use App\Enum\Configuration\TownSetting;
use App\Enum\EventStages\BuildingValueQuery;
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

    private $protoDefenceItems = null;
    private DoctrineCacheService $doctrineCache;
    private EventProxyService $proxy;

    private GameEventService $gameEvents;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, ItemFactory $if, LogTemplateHandler $lh,
        TimeKeeperService $tk, CitizenHandler $ch, PictoHandler $ph, ConfMaster $conf, RandomGenerator $rand,
        CrowService $armbrust, DoctrineCacheService $doctrineCache, EventProxyService $proxy, GameEventService $gs)
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
        $this->doctrineCache = $doctrineCache;
        $this->proxy = $proxy;
        $this->gameEvents = $gs;
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
		$b->setInventory((new Inventory())->setBuilding($b));
        $blocked = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_BUILDINGS);

        // Add all children that do not require blueprints
        if ($b)
            foreach ( $prototype->getChildren() as $child )
                if ($child->getBlueprint() == 0 && !in_array($child->getName(), $blocked) && !$this->getBuilding( $town, $child, false ))
                    $this->internalAddBuilding($town, $child);
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
            $selected->addRole( $this->doctrineCache->getEntityByIdentifier(CitizenRole::class, 'cata'));
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
     * @param string|BuildingPrototype $prototype The prototype of the building (name of prototype or Prototype Entity)
     * @param boolean $finished Do we want the building if is finished, null otherwise ?
     * @return Building|null
     */
    public function getBuilding(Town $town, $prototype, $finished = true): ?Building {
        if (is_string($prototype))
            $prototype = $this->doctrineCache->getEntityByIdentifier(BuildingPrototype::class, $prototype);

        if (!$prototype) return null;
        foreach ($town->getBuildings() as $b)
            if ($b->getPrototype()->getId() === $prototype->getId())
                return (!$finished || $b->getComplete()) ? $b : null;
        return null;
    }

    private array $building_list_cache = [];

    /**
     * Return a list of buildings available in town
     *
     * @param Town $town The town we're looking the building into
     * @param boolean $finished Do we want only the buildings if they finished ?
     * @return array
     */
    public function getCachedBuildingList(Town $town, bool $finished = true): array {
        $key = $finished ? "{$town->getId()}-1" : "{$town->getId()}-0";
        if (array_key_exists( $key, $this->building_list_cache )) return $this->building_list_cache[$key];

        return $this->building_list_cache[$key] = array_map(
            fn(Building $b): string => $b->getPrototype()->getName(),
            $finished ? array_filter( $town->getBuildings()->toArray(), fn(Building $b) => $b->getComplete() ) : $town->getBuildings()->toArray()
        );
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
                fn(CitizenHomeUpgrade $item) => $item->getPrototype(),
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
            $defenseIndex = array_search($this->doctrineCache->getEntityByIdentifier(CitizenHomeUpgradePrototype::class,"defense"), $homeUpgradesPrototypes);

            if($defenseIndex !== false) {
                $n = $homeUpgrades[$defenseIndex];
                if($n->getLevel() <= 6)
                    $summary->upgrades_defense += $n->getLevel();
                else {
                    $summary->upgrades_defense += 6 + 2 * ($n->getLevel() - 6);
                }
            }

            $n = in_array($this->doctrineCache->getEntityByIdentifier(CitizenHomeUpgradePrototype::class,"fence"), $homeUpgradesPrototypes);
            $summary->upgrades_defense += ($n ? 3 : 0);
        }


        $summary->item_defense = $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->getPrototypesForDefenceItems(), false, false
        );

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
            $this->doctrineCache->getEntityByIdentifier(ItemPrototype::class, "soul_blue_#00")
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
                $this->doctrineCache->getEntityByIdentifier(ItemPrototype::class, "soul_blue_#01")
        ) * 2;

        $summary->item_defense += $this->inventory_handler->countSpecificItems( $home->getChest(),
                $this->doctrineCache->getEntityByIdentifier(ItemPrototype::class, "soul_red_#00")
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

        $summary->overall_scale = $this->proxy->queryTownParameter( $town, BuildingValueQuery::OverallTownDefenseScale );

        $guardian_bonus = $this->proxy->queryTownParameter( $town, BuildingValueQuery::GuardianDefenseBonus );

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


        $summary->item_defense = min($this->proxy->queryTownParameter( $town, BuildingValueQuery::MaxItemDefense ), floor($this->inventory_handler->countSpecificItems( $town->getBank(),
                $this->getPrototypesForDefenceItems(), false, false
        ) * $item_def_factor));

        $summary->soul_defense = $town->getSoulDefense();

        $summary->nightwatch_defense = max(0, $this->calculate_watch_def($town) );
        
        return $summary->sum();
    }

    public function calculate_watch_def(Town $town, int $day = 0){
        $total_def = 0;
        $has_counsel = false;

        if ($day <= 0) $day = ($town->getDay() - $day);
        $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findWatchersOfDay($town,$day);

        $count = 0;
        foreach ($watchers as $watcher) {
            if ($watcher->getCitizen()->getZone() !== null) continue;
            $count++;
            $total_def += $this->citizen_handler->getNightWatchDefense($watcher->getCitizen());
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
        $override = $this->gameEvents->triggerWatchtowerModifierHooks( $town, $this->conf->getCurrentEvents($town), $min, $max, 0, $quality );

        $estim = new WatchtowerEstimation();
        $estim->setMin($override?->min ?? $min);
        $estim->setMax($override?->max ?? $max);
        $estim->setEstimation($override?->quality ?? $quality);
        $estim->setFuture(0);
        $estim->setMessage($override?->message);

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

            $override2 = $this->gameEvents->triggerWatchtowerModifierHooks( $town, $this->conf->getCurrentEvents($town), $min2, $max2, 1, $quality2 );

            $estim2 = new WatchtowerEstimation();
            $estim2->setMin($override2?->min ?? $min2);
            $estim2->setMax($override2?->max ?? $max2);
            $estim2->setEstimation($override2?->quality ?? $quality2);
            $estim2->setFuture(1);
            $estim2->setMessage($override2?->message);
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
                $const_ratio_base = 0.5;
                $const_ratio_low = 0.75;

                $max_ratio = match( $this->conf->getTownConfiguration( $town )->get(TownConf::CONF_FEATURE_ATTACKS, 'normal') ) {
                    'hard' => 3.1,
                    'easy' => $const_ratio_low,
                    default => 1.1,
                };

                $ratio_min = ($current_day <= 3 ? $const_ratio_low : $max_ratio);
                $ratio_max = ($current_day <= 3 ? ($current_day <= 1 ? $const_ratio_base : $const_ratio_low) : $max_ratio);

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
                if (!$this->gameEvents->triggerDisableCitizenHooks( $citizen, $old_event )) return false;
                foreach ($citizen_markers as $marker)
                    if ($marker->getEvent() === $old_event->name())
                        $pc[] = $marker->setActive(false);
            }

        // Enable all new events that are not in the list of the old events
        foreach ($events as $event)
            if (!in_array($event->name(), $current_names) && $event->active()) {
                if (!$this->gameEvents->triggerEnableCitizenHooks( $citizen, $event )) return false;
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
                if (!$this->gameEvents->triggerDisableTownHooks( $town, $old_event )) return false;
                foreach ($town_markers as $marker)
                    if ($marker->getEvent() === $old_event->name())
                        $pc[] = $marker->setActive(false);
            }

        // Enable all new events that are not in the list of the old events
        foreach ($events as $event)
            if (!in_array($event->name(), $current_names) && $event->active()) {
                if (!$this->gameEvents->triggerEnableTownHooks( $town, $event )) return false;
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
        if (is_string($role)) $role = $this->doctrineCache->getEntityByIdentifier(CitizenRole::class, $role);
        if (!$role || !$role->getVotable()) return false;

        if (!$this->proxy->queryTownRoleEnabled( $town, $role )) return false;

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

    public function door_is_locked(Town $town): bool|BuildingPrototype {
        if ( !$town->getDoor() ) {

            if ($town->isOpen() && $this->conf->getTownConfiguration($town)->get(TownSetting::LockDoorUntilTownIsFull)) return true;

            if((($s = $this->timeKeeper->secondsUntilNextAttack(null, true)) <= 1800)) {
                if ($b = $this->getBuilding( $town, 'small_door_closed_#02', true )) {
                    if ($s <= 60) return $b->getPrototype();
                } elseif ($b = $this->getBuilding( $town, 'small_door_closed_#01', true )) {
                    if ($s <= 1800) return $b->getPrototype();
                } elseif ($b = $this->getBuilding( $town, 'small_door_closed_#00', true )) {
                    if ($s <= 1200) return $b->getPrototype();
                }
            }
        }
        return false;
    }
}