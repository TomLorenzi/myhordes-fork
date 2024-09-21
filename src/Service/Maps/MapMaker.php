<?php

namespace App\Service\Maps;

use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Enum\Configuration\TownSetting;
use App\Enum\HordeSpawnBehaviourType;
use App\Enum\HordeSpawnGovernor;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Structures\TownConf;
use App\Structures\ZombieSpawnBehaviour;
use App\Structures\ZombieSpawnZone;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;

class MapMaker
{
    public function __construct(
        private readonly EntityManagerInterface $entity_manager,
        private readonly RandomGenerator $random,
        private readonly ConfMaster $conf,
        private readonly MazeMaker $maze_maker,
        private readonly InventoryHandler $inventory_handler,
        private readonly ItemFactory $item_factory
    ) { }

    public function createMap( Town $town ): void {
        $conf = $this->conf->getTownConfiguration( $town );

        $defaultTag = $this->entity_manager->getRepository(ZoneTag::class)->findOneBy(['ref' => ZoneTag::TagNone]);
        $map_resolution = $this->getDefaultZoneResolution( $conf, $ox, $oy );
        for ($x = 0; $x < $map_resolution; $x++)
            for ($y = 0; $y < $map_resolution; $y++) {
                $zone = new Zone();
                $n = $conf->get(TownSetting::MapZoneDropCountInitializer);
                //Hordes Dig Count is N + rand(N), where rand is between 0 included and N excluded
                $digCount = ceil($n*0.7) + mt_rand(0,$n-1 ) ;
                $zone
                    ->setX( $x - $ox )
                    ->setY( $y - $oy )
                    ->setDigs($digCount)
                    ->setFloor( new Inventory() )
                    ->setDiscoveryStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::DiscoveryStateCurrent : Zone::DiscoveryStateNone )
                    ->setZombieStatus( ($x - $ox == 0 && $y - $oy == 0) ? Zone::ZombieStateExact : Zone::ZombieStateUnknown )
                    ->setZombies( 0 )
                    ->setInitialZombies( 0 )
                    ->setStartZombies( 0 )
                    ->setTag($defaultTag)
                ;
                $town->addZone( $zone );
            }

        $spawn_ruins = $conf->get(TownConf::CONF_NUM_RUINS, 0);

        $ruin_km_range = [
            $this->entity_manager->getRepository(ZonePrototype::class)->findMinRuinDistance(false),
            $this->entity_manager->getRepository(ZonePrototype::class)->findMaxRuinDistance(false),
        ];

        /** @var Zone[] $zone_list */
        $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) use ($ruin_km_range) {
            $km = round(sqrt( pow($z->getX(),2) + pow($z->getY(),2) ) );
            // $ap = abs($z->getX()) + abs($z->getY());
            return $km != 0 && $km >= $ruin_km_range[0] && $km <= $ruin_km_range[1];
        });
        shuffle($zone_list);

        $previous = [];

        $co_location_cache = [];
        $cl_get = function(int $x, int $y) use (&$co_location_cache): int {
            $m = 0;
            for ($xo = -1; $xo <= 1; $xo++) for ($yo = -1; $yo <= 1; $yo++)
                if (isset($co_location_cache[$id = (($x+$xo) . '.' . ($y+$yo))]))
                    $m = max($m, count($co_location_cache[$id]));
            return $m;
        };
        $cl_set = function(int $x, int $y) use (&$co_location_cache): void {
            $a = [$x . '.' . $y];
            for ($xo = -1; $xo <= 1; $xo++) for ($yo = -1; $yo <= 1; $yo++)
                if (isset($co_location_cache[$id = (($x+$xo) . '.' . ($y+$yo))]))
                    $a = array_merge($a,$co_location_cache[$id]);
            $a = array_unique($a);
            foreach ($a as $id) $co_location_cache[$id] = $a;
        };

        $o = 0;
        for ($i = 0; $i < $spawn_ruins; $i++) {

            do {
                if (($i+$o) >= count($zone_list)) continue 2;
                $b = $cl_get( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );
                if ($b <= 1) $keep_location = true;
                else if ($b === 2) $keep_location = $this->random->chance(0.25);
                else $keep_location = false;

                if (!$keep_location) $o++;
            } while ( !$keep_location );

            $cl_set( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );

            //$ruin_types = $this->entity_manager->getRepository(ZonePrototype::class)->findByDistance( abs($zone_list[$i]->getX()) + abs($zone_list[$i]->getY()) );
            $ruin_types = $this->entity_manager->getRepository(ZonePrototype::class)->findByDistance(round(sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )));
            if (empty($ruin_types)) continue;

            $iterations = 0;
            do {
                $target_ruin = $this->random->pickLocationFromList( $ruin_types );
                $iterations++;
            } while ( isset( $previous[$target_ruin->getId()] ) && $iterations <= $previous[$target_ruin->getId()] );

            if (!isset( $previous[$target_ruin->getId()] )) $previous[$target_ruin->getId()] = 1;
            else $previous[$target_ruin->getId()]++;

            //Hordes Dig Count is N + rand(N), where rand is between 0 included and N excluded
            $ruinDigCount = ceil($conf->get(TownConf::CONF_RUIN_ITEMS_MIN, 8)*0.7) + mt_rand(0,$conf->get(TownConf::CONF_RUIN_ITEMS_MIN, 8)-1 ) ;

            $zone_list[$i+$o]
                ->setPrototype( $target_ruin )
                ->setRuinDigs($ruinDigCount);

            if ($conf->get(TownConf::CONF_FEATURE_CAMPING, false))
                $zone_list[$i+$o]->setBlueprint(Zone::BlueprintAvailable);

            if ($this->random->chance($conf->get(TownConf::CONF_MAP_BURIED_PROB, 0.5)))
                $zone_list[$i+$o]->setBuryCount( mt_rand($conf->get(TownConf::CONF_MAP_BURIED_DIGS_MIN, 1), $conf->get(TownConf::CONF_MAP_BURIED_DIGS_MAX, 19)) );
        }

        $spawn_explorable_ruins = $conf->get(TownConf::CONF_NUM_EXPLORABLE_RUINS, 0);
        $all_explorable_ruins = $explorable_ruins = [];
        if ($spawn_explorable_ruins > 0)
            $all_explorable_ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findBy( ['explorable' => true] );
        $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) {return $z->getPrototype() === null && ($z->getX() !== 0 || $z->getY() !== 0);});

        for ($i = 0; $i < $spawn_explorable_ruins; $i++) {
            if (empty($explorable_ruins)) {
                $explorable_ruins = $all_explorable_ruins;
                shuffle($explorable_ruins);
            }

            /** @var ZonePrototype $spawning_ruin */
            $spawning_ruin = array_pop($explorable_ruins);
            if (!$spawning_ruin) continue;

            $maxDistance = $conf->get(TownSetting::ERuinMaxDistanceFromTown);
            $spawn_zone = $this->random->pickLocationBetweenFromList($zone_list, $spawning_ruin->getMinDistance(), $maxDistance, ['prototype_id' => null]);

            if ($spawn_zone) {
                $spawn_zone->setPrototype($spawning_ruin);
                $this->maze_maker->setTargetZone($spawn_zone);
                $spawn_zone->setExplorableFloors($conf->get(TownSetting::ERuinSpaceFloors));
                $this->maze_maker->createField();
                $this->maze_maker->generateCompleteMaze();
            }
        }

        $item_spawns = $conf->get(TownConf::CONF_DISTRIBUTED_ITEMS, []);
        $distribution = [];

        $zone_list = $town->getZones()->getValues();
        foreach ($conf->get(TownConf::CONF_DISTRIBUTION_DISTANCE, []) as $dd) {
            $distribution[$dd['item']] = ['min' => $dd['min'], 'max' => $dd['max']];
        }
        for ($i = 0; $i < count($item_spawns); $i++) {
            $item = $item_spawns[$i];
            if (isset($distribution[$item])) {
                $min_distance = $distribution[$item]['min'];
                $max_distance = $distribution[$item]['max'];
            }
            else {
                $min_distance = 1;
                $max_distance = 100;
            }

            $spawnZone = $this->random->pickLocationBetweenFromList($zone_list, $min_distance, $max_distance);
            if ($spawnZone) {
                $this->inventory_handler->forceMoveItem($spawnZone->getFloor(), $this->item_factory->createItem($item_spawns[$i]));
                $zone_list = array_filter( $zone_list, fn(Zone $z) => $z !== $spawnZone );
            }
        }

        $this->initialZombieSpawn( $town );
        foreach ($town->getZones() as $zone) $zone->setStartZombies( $zone->getZombies() );
    }

    private function getDefaultZoneResolution( TownConf $conf, ?int &$offset_x, ?int &$offset_y ): int {
        $resolution = mt_rand( $conf->get(TownSetting::MapSizeMin), $conf->get(TownSetting::MapSizeMax) );

        if($conf->get(TownSetting::MapUseCustomMargin)) {
            $offset_x = mt_rand(
                floor($resolution * $conf->get(TownSetting::MapCustomMarginWest)),
                floor($resolution - ($resolution * $conf->get(TownSetting::MapCustomMarginEast)))
            );
            $offset_y = mt_rand(
                floor($resolution * $conf->get(TownSetting::MapCustomMarginSouth)),
                floor($resolution - ($resolution * $conf->get(TownSetting::MapCustomMarginNorth)))
            );
        } else {
            $safe_border = ceil($resolution * $conf->get(TownSetting::MapSafeMargin));

            if ($safe_border >= $resolution/2) {
                $offset_x = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
                $offset_y = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
            } else {
                $offset_x = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
                $offset_y = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
            }
        }

        return $resolution;
    }

    const RespawnModeNone = 0;
    const RespawnModeAuto = 1;
    const RespawnModeForce = 2;

    public function dailyZombieSpawn( Town $town, int $cycles = 1, int $mode = self::RespawnModeAuto, ?int $override_day = null ): void
    {
        $gov = HordeSpawnGovernor::MyHordes;

        if ($gov->myHordes()) $this->zombieSpawnGovernorMH( $town, $gov, $cycles, $mode, $override_day );
        elseif ($gov->hordes()) for ($i = 0; $i < $cycles; $i++) $this->zombieSpawnGovernorHordes( $town, $gov, $override_day );
    }

    public function initialZombieSpawn( Town $town ): void
    {
        $conf = $this->conf->getTownConfiguration( $town );

        /** @var Zone[] $zones */
        $zones = $town->getZones()->getValues();

        $empty_zones = [];
        $ruin_zones = [];
        $e_ruin_zones = [];
        $zone_db = [];

        $set = function(Zone $zone, int $value) use (&$zone_db) { Arr::set($zone_db, "{$zone->getX()}.{$zone->getY()}", $value); };
        $get = function(Zone $zone) use (&$zone_db): int { return Arr::get($zone_db, "{$zone->getX()}.{$zone->getY()}", 0); };

        foreach ($zones as $zone) {
            if ($zone->getPrototype()?->getExplorable() )
                $e_ruin_zones[] = $zone;
            elseif ($zone->getPrototype())
                $ruin_zones[] = $zone;
            elseif (!$zone->isTownZone()) $empty_zones[] = $zone;

            $set($zone, 0);
        }

        $min_dist = $conf->get(TownConf::CONF_MAP_FREE_SPAWN_DIST, 0);
        $spawn_zones = $this->random->draw(
            $empty_zones,
            $conf->get(TownConf::CONF_MAP_FREE_SPAWN_COUNT, 3),
            true,
            fn(Zone $zone) => $zone->getDistance() >= $min_dist,
        );

        foreach ($ruin_zones as $zone) {
            $zombies_base = $zone->getDistance();
            $set($zone, max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) ) );
        }

        foreach ($spawn_zones as $zone)
            if (!$town->getType()->is(TownClass::EASY) || $zone->getDistance() >= 3)
                $set($zone, mt_rand(0, 2));

        //foreach ($ruin_zones as $zone) {
        //    $empty_surrounding_zones = [];
        //    for ($x = $zone->getX() - 1; $x <= $zone->getX() + 1; $x++)
        //        for ($y = $zone->getY() - 1; $y <= $zone->getY() + 1; $y++)
        //            if (isset( $zone_db[$x] ) && isset($zone_db[$x][$y]) && $zone_db[$x][$y] === 0 && ($x !== 0 || $y !== 0))
        //                $empty_surrounding_zones[] = [$x,$y];

        //    $picked = $this->random->pick( $empty_surrounding_zones, mt_rand( 2, 5 ), true );
        //    foreach ($picked as [$x,$y]) $zone_db[$x][$y] = mt_rand(1, min(5, $zone_db[$zone->getX()][$zone->getY()] ));
        //}

        foreach ($zones as $zone)
            $zone
                ->setZombies( $zone->isTownZone() ? 0 : $get($zone) )
                ->setInitialZombies( $zone->isTownZone() ? 0 : $get($zone) )
                ->setScoutEstimationOffset( mt_rand(-2,2) )
                ->setPlayerDeaths(0);

        $i = 0;
        $this->dailyZombieSpawn($town, cycles: 3, mode: self::RespawnModeNone);
        while ($i < 3 && !$this->aboveMinNumberOfZombies( $zones, $conf, 1 )) {
            $this->dailyZombieSpawn($town, mode: self::RespawnModeNone);
            $i++;
        }

        foreach ($e_ruin_zones as $zone) {
            $zombies = $zone->getDistance();
            $zone->setZombies( $zombies / 2 )->setInitialZombies( $zombies / 2 );
        }
    }

    private function aboveMinNumberOfZombies(int|Town|array $data, TownConf $conf, ?int $day = null, float $factor = 1.0): bool {
        $zombies = match (true) {
            is_a($data, Town::class) => $data->getZones()->reduce( fn(int $carry, Zone $z) => $carry + $z->getZombies(), 0 ),
            is_array( $data ) => array_reduce( $data, fn(int $carry, Zone $z) => $carry + $z->getZombies(), 0 ),
            default => $data
        };

        $day ??= is_a($data, Town::class) ? $data->getDay() : 1;
        return $zombies >= $conf->get( TownConf::CONF_MODIFIER_RESPAWN_THRESHOLD, 50 ) * $day * $conf->get( TownConf::CONF_MODIFIER_RESPAWN_FACTOR, 0.5 ) * $factor;
    }

    private function spreadCycleH(bool $observe_despair, array &$zone_db, array $despair_db, array $ctx_db): int {
        $cycle_result = 0;
        $zone_original_db = $zone_db;

        $spread_db = [];
        foreach ($zone_db as $x => &$zone_row)
            foreach ($zone_row as $y => &$current_zone_zombies) {

                if (($x === 0 && $y === 0) || $ctx_db[$x][$y] > 0 || ($despair_db[$x][$y] > 0 && $observe_despair))
                    continue;

                $before = $current_zone_zombies;

                // We're iterating over the adjacent zones
                $spread_targets = [];
                for ($dx = -1; $dx <= 1; $dx++)
                    if (isset($zone_original_db[$x + $dx]))
                        for ($dy = -1; $dy <= 1; $dy++) if (($dx !== 0 || $dy !== 0) && abs($dx) !== abs($dy))
                            if (isset($zone_original_db[$x + $dx][$y + $dy]) && $zone_original_db[$x + $dx][$y + $dy] < 3)
                                $spread_targets[] = [$x + $dx, $y + $dy];

                if ($current_zone_zombies >= 3) {
                    if (empty($spread_targets)) {
                        $current_zone_zombies += 1;
                    } else {
                        $spread = 2;
                        while ($spread > 0) {
                            $spread_zone = $this->random->pick( $spread_targets );
                            $spread_count = mt_rand(0, $spread);
                            $spread_db[] = [$spread_zone, $spread_count];
                            $spread -= $spread_count;
                        }

                        if ($this->random->chance(0.75))
                            $current_zone_zombies += 1;
                    }

                } elseif ($current_zone_zombies > 0 && $this->random->chance(0.5))
                    $current_zone_zombies += 1;

                $cycle_result += ($current_zone_zombies - $before);
            }

        foreach ($spread_db as [[$x,$y], $num]) {
            $zone_db[$x][$y] += $num;
            $cycle_result += $num;
        }

        return $cycle_result;
    }

    private function spreadCycleMH(bool $observe_despair, bool $diagonal_spawn, array &$zone_db, array $despair_db): int {
        $cycle_result = 0;
        $zone_original_db = $zone_db;
        foreach ($zone_db as $x => &$zone_row)
            foreach ($zone_row as $y => &$current_zone_zombies) {

                if (($x === 0 && $y === 0) || ($despair_db[$x][$y] > 0 && $observe_despair)) continue;

                $before = $current_zone_zombies;

                // We're iterating over the adjacent zones
                $adj_zones_total = $adj_zones_infected = $direct_adj_zones_infected = $neighboring_zombies = $max_neighboring_zombies = 0;
                for ($dx = -1; $dx <= 1; $dx++)
                    if (isset($zone_original_db[$x + $dx]))
                        for ($dy = -1; $dy <= 1; $dy++) if (($dx !== 0 || $dy !== 0) && ( $diagonal_spawn || abs($dx) !== abs($dy) )) {
                            if (isset($zone_original_db[$x + $dx][$y + $dy])) {
                                // If the zone exist, increase number of neighboring zones
                                $adj_zones_total++;

                                // Count the number of neighboring zombies
                                $neighboring_zombies += $zone_original_db[$x + $dx][$y + $dy];
                                $max_neighboring_zombies = max( $max_neighboring_zombies, $zone_original_db[$x + $dx][$y + $dy] );

                                // If the zone has zombies, increase the number of infected neighboring zones
                                if ($zone_original_db[$x + $dx][$y + $dy] > $zone_original_db[$x][$y]) {
                                    $adj_zones_infected++;
                                    if (abs($dx) !== abs($dy)) $direct_adj_zones_infected++;
                                }
                            }
                        }

                if ($current_zone_zombies > 0) {
                    $new_zeds = $this->random->chance(0.9)
                        ? 1
                        : ( $this->random->chance(0.5) ? 0 : 2 );

                    $current_zone_zombies += $new_zeds;
                } else {
                    // Otherwise, count the total number of adjacent zones with zombies

                    // If we have infected neighboring zones
                    if ($adj_zones_infected > 0) {
                        // Number of zones with zombies, balanced by total number of neighboring zones
                        $target_number = (int)round($adj_zones_infected * (($diagonal_spawn ? 8.0 : 4.0) / $adj_zones_total));
                        $limit = ($direct_adj_zones_infected > 0) ? 4 : 3;

                        // Depending on the number of neighboring zombies, we create a bias towards not spawning
                        // any new zombies. More neighboring zombies = less bias.
                        $bias = 0;
                        if ($max_neighboring_zombies >= 5 && $adj_zones_infected >= 2) $bias = -1;
                        elseif ($max_neighboring_zombies >= 15)    $bias = -1;
                        elseif ($max_neighboring_zombies >= 8) $bias =  0;
                        elseif ($neighboring_zombies < 5)  $bias = min(4, $limit);
                        elseif ($neighboring_zombies < 10) $bias = 3;
                        elseif ($neighboring_zombies < 15) $bias = 2;
                        elseif ($neighboring_zombies < 20) $bias = 1;

                        // Calculate random value between bias and 4
                        $new_zeds = mt_rand(-$bias, $limit);

                        // Repeat if the result is > 0 and not the same as the number of neighboring infected zones
                        // This created a bias towards spawning the same number of zombies as there are infected zones
                        if ($new_zeds > 0 && $new_zeds !== $target_number)
                            $new_zeds = mt_rand(-$bias, $limit);

                        // Limit to 1-2, bias towards 1 if using diagonal spread
                        if ($new_zeds > 0 && $diagonal_spawn) $new_zeds = max(1, min(2, mt_rand( -2, 3 )));

                        // Clamp the result to a 0 - 4 range.
                        $current_zone_zombies += max(0, min($limit, $new_zeds));
                    }
                }

                $cycle_result += ($current_zone_zombies - $before);
            }

        return $cycle_result;
    }

    private function zombieSpawnGovernorMH( Town $town, HordeSpawnGovernor $gov, int $cycles = 1, int $mode = self::RespawnModeAuto, ?int $override_day = null ): void {
        /** @var Zone[] $zones */
        $zones = $town->getZones()->getValues();
        $ctx_db = []; $zone_db = []; $despair_db = [];
        $killedZombies = 0;

        $total_zombies = 0;
        foreach ($zones as &$zone) {
            $total_zombies += $zone->getZombies();
            $killedZombies += ($zone->getInitialZombies() - $zone->getZombies());

            $despair = (int)floor(max(0,( ($zone->getInitialZombies() - $zone->getZombies()) - 1 ) / 2));
            if (!isset($zone_db[$zone->getX()])) $zone_db[$zone->getX()] = [];
            $ctx_db[$zone->getX()][$zone->getY()] = $zone->getCitizens()->count();
            $zone_db[$zone->getX()][$zone->getY()] = $zone->getZombies();
            $despair_db[$zone->getX()][$zone->getY()] = $despair;

            $zone->setScoutEstimationOffset( mt_rand(-2,2) );
        }

        $conf = $this->conf->getTownConfiguration($town);

        $town->getMapSize($map_x,$map_y);

        $fun_cycle = function(bool $observe_despair = false, $diagonal_spawn = true) use (&$zone_db,$despair_db,$ctx_db,$gov): int {
            return $gov === HordeSpawnGovernor::Hordes
                ? $this->spreadCycleH( $observe_despair, $zone_db, $despair_db, $ctx_db )
                : $this->spreadCycleMH( $observe_despair, $diagonal_spawn, $zone_db, $despair_db );
        };

        // Respawn
        $d = $override_day ?? $town->getDay();
        if ($mode === self::RespawnModeForce ||
            ($mode === self::RespawnModeAuto && !$this->aboveMinNumberOfZombies($total_zombies, $conf, $d))) {

            //$keys = $d == 1 ? [array_rand($empty_zones)] : array_rand($empty_zones, min($d,count($empty_zones)));
            //foreach ($keys as $spawn_zone_id)
            //    /** @var Zone $spawn_zone */
            //    $zone_db[ $zones[$spawn_zone_id]->getX() ][ $zones[$spawn_zone_id]->getY() ] = mt_rand(1,intval(ceil($d / 2)));
            //$cycles += ceil($d/2);

            // Step 1: Make a backup of the current zombie distribution
            $zone_db_before_respawn = $zone_db;

            // Step 2: Return the map to D1 state and count the zombies
            $total_zombies = 0;
            foreach ($zones as &$zone)
                $total_zombies += ($zone_db[$zone->getX()][$zone->getY()] = $zone->getStartZombies() ?? 0);

            // Step 3: Spread until the min zombie count is reached again
            while ( !$this->aboveMinNumberOfZombies($total_zombies, $conf, $d) )
                $total_zombies += $fun_cycle();

            // Step 4: Add the original zombies back onto the map
            foreach ($zones as &$zone)
                $zone_db[$zone->getX()][$zone->getY()] =
                    $zone_db[$zone->getX()][$zone->getY()] + $zone_db_before_respawn[$zone->getX()][$zone->getY()];
        }


        for ($c = 0; $c < $cycles; $c++)
            $fun_cycle($c == 0, $d >= 2);

        foreach ($town->getZones() as &$zone) {
            if ($zone->isTownZone()) continue;

            $zombies = max( 0, $zone_db[$zone->getX()][$zone->getY()] );
            $zone->setZombies( $despair_db[$zone->getX()][$zone->getY()] >= 1
                                   ? max(0, $zombies - $despair_db[$zone->getX()][$zone->getY()])
                                   : $zombies
            );
            $zone->setInitialZombies( $zombies );
            $zone->setPlayerDeaths(0 );
        }
    }

    // INCOMPLETE
    private function zombieSpawnGovernorHordes( Town $town, HordeSpawnGovernor $gov, ?int $override_day = null ): void {

        $mapGrid = [];

        /** @var ZombieSpawnZone[] $baseZones */
        $baseZones = [];    // All zones

        /** @var ZombieSpawnZone[] $zones */
        $zones = [];        // All zones with zombies

        /** @var ZombieSpawnZone[] $zonesWithDeads */
        $zonesWithDeads = []; // All zones where a citizen has died (sorted descending)

        /** @var ZombieSpawnZone[] $zonesWithZombieKills */
        $zonesWithZombieKills = []; // All zones where a zombie has died (sorted descending)

        /** @var ZombieSpawnZone[] $orderedZones */
        $orderedZones = [];        // All zones with zombies (sorted descending)

        /** @var ZombieSpawnZone[] $emptyZones */
        $emptyZones = [];        // All zones without zombies

        $grid_min = PHP_INT_MAX;
        $grid_max = PHP_INT_MIN;

        // Built data structures
        foreach ($town->getZones() as $zone) {
            $container = new ZombieSpawnZone($zone);
            $baseZones[] = $container;
            if (!isset( $mapGrid[$zone->getX()] )) $mapGrid[$zone->getX()] = [];
            if (!isset( $mapGrid[$zone->getX()][$zone->getY()] )) $mapGrid[$zone->getX()][$zone->getY()] = $container;

            $grid_min = min( $zone->getX(), $zone->getY(), $grid_min );
            $grid_max = max( $zone->getX(), $zone->getY(), $grid_max );
        }

        // ### Helper functions

        // Writes all zones with zombies into the $zones array
        $group_zones = function() use (&$zones, &$orderedZones, &$baseZones, &$emptyZones, &$zonesWithDeads, &$zonesWithZombieKills) {
            $orderedZones = $zones = array_values(array_filter( $baseZones, fn(ZombieSpawnZone $z) => $z->zombies > 0 && !$z->town ));
            $emptyZones = array_values(array_filter( $baseZones, fn(ZombieSpawnZone $z) => $z->zombies == 0 && !$z->town ));
            $zonesWithDeads = array_values(array_filter( $baseZones, fn(ZombieSpawnZone $z) => $z->deads > 0 ));
            $zonesWithZombieKills = array_values(array_filter( $baseZones, fn(ZombieSpawnZone $z) => $z->zombieKills > 0 ));

            usort( $orderedZones, fn(ZombieSpawnZone $a, ZombieSpawnZone $b) => $b->zombies <=> $a->zombies );
            usort( $zonesWithDeads, fn(ZombieSpawnZone $a, ZombieSpawnZone $b) => $b->deads <=> $a->deads );
            usort( $zonesWithZombieKills, fn(ZombieSpawnZone $a, ZombieSpawnZone $b) => $b->zombieKills <=> $a->zombieKills );
        };

        // Returns a ZombieSpawnZone from coordinates or from a Zone instance; returns null for invalid coords
        $grid = fn(int|Zone $x, ?int $y = null): ?ZombieSpawnZone => is_a($x, Zone::class)
            ? ($mapGrid[$x->getX()][$x->getY()] ?? null)
            : ($mapGrid[$x][$y] ?? null)
        ;

        $star = fn(ZombieSpawnZone $z, bool $with_self = false) => [
            $with_self ? $z : null,
            $grid($z->x + 1, $z->y), $grid($z->x, $z->y + 1),
            $grid($z->x - 1, $z->y), $grid($z->x, $z->y - 1),
        ];

        // Returns all zones adjacent to the given zone that are not the town zone
        $adjacent = fn(ZombieSpawnZone $z): array => array_filter( $star($z), fn( ?ZombieSpawnZone $zz ) => $zz && !$zz->town );

        // Concats values of close zones together
        $concat = function (array &$zoneArray, callable $get, callable $set) {
            if (count($zoneArray) > 1) {
                for ($i = 0; $i < count($zoneArray); $i++) {
                    if (($zone = $zoneArray[$i]) === null) continue;
                    for ($j = $i+1; $j < count($zoneArray); $j++) {
                        if (($zone2 = $zoneArray[$j]) === null) continue;

                        $level = ZombieSpawnZone::getZoneLevel( $zone, $zone2 );
                        if ($level <= 2) {
                            $set( $zone, $get($zone) + (int)( $get($zone2) / $level ) );
                            $set( $zone2, 0 );
                            $zoneArray[$j] = null;
                        }
                    }
                }
                $zoneArray = array_values( array_filter( $zoneArray ) );
            }
        };

        // ### Config

        $mt_ZombieGrowThreshold = 3;
        $mt_ZombieSpread = 2;
        $mt_OverThresholdGrowChance = 0.75;
        $mt_distanceAttenuation = [ 1.0, 0.60, 0.25, 0.10, 0.05, 0.05 ];

        switch ($gov) {
            case HordeSpawnGovernor::HordesOnline:
                $group_zones();
                foreach ( array_filter( $zones, fn(ZombieSpawnZone $z) => $z->zombies >= $mt_ZombieGrowThreshold ) as $zone) {
                    /** @var ZombieSpawnZone[] $adjacent_zones Adjacent zones below the zombie growth threshold */
                    $adjacent_zones = array_filter( $adjacent( $zone ), fn( ZombieSpawnZone $z ) => $z->zombies < $mt_ZombieGrowThreshold );

                    if (empty($adjacent_zones)) $zone->addZombie();
                    else {
                        $zombies = $mt_ZombieSpread;
                        while ($zombies > 0) {
                            /** @var ZombieSpawnZone $spreadZone */
                            $spreadZone = $this->random->pick( $adjacent_zones );
                            $spread = mt_rand(1, $zombies);

                            $spreadZone->addZombie( $spread );
                            $zombies -= $spread;
                        }

                        if ($this->random->chance( $mt_OverThresholdGrowChance ))
                            $zone->addZombie();
                    }

                    if ($zone->building || $this->random->chance(0.5))
                        $zone->addZombie();
                }
                break;
            case HordeSpawnGovernor::HordesModDone:
                $doneLimit = mt_rand(3,5);
                $group_zones();
                shuffle( $zones );

                $cZombie = 0;
                $cZombieRatio = 75;

                while (!empty($zones)) {
                    $zone = array_pop( $zones );
                    if ($zone->done) continue;

                    $zone->done = true;
                    if ($zone->zombies >= $mt_ZombieGrowThreshold) {
                        $adjacent_zones = array_filter($adjacent($zone),
                            fn(ZombieSpawnZone $z) => !$z->done && ($z->zombies < $mt_ZombieGrowThreshold || $this->random->chance(0.3))
                        );

                        if (empty($adjacent_zones)) {
                            $cZombie++;
                            $zone->addZombie();
                            continue;
                        }

                        $zombies = $mt_ZombieSpread;
                        while ($zombies > 0) {
                            /** @var ZombieSpawnZone $spreadZone */
                            $spreadZone = $this->random->pick($adjacent_zones);
                            $spread = mt_rand(1, $zombies);

                            $spreadZone->addZombie($spread);
                            $zombies -= $spread;

                            $cZombie += $spread;
                            if ($spreadZone->zombies >= $doneLimit)
                                $spreadZone->done = true;

                            if ((mt_rand( 0, 99 ) - ( $cZombie / $cZombieRatio )) < 50)
                                $zone->killZombie( $zombies );
                        }

                        if ((mt_rand( 0, 99 ) - ( $cZombie / $cZombieRatio )) < 75) {
                            $cZombie++;
                            $zone->addZombie();
                        }
                    }

                    if ($zone->building || $this->random->chance(0.5))
                        $zone->addZombie();
                }
                break;
            case HordeSpawnGovernor::HordesCrowdControl:

                // Prepare
                $group_zones();
                shuffle( $zones );

                // Prepare Lead
                $nbLeader = mt_rand(0, 14) + max( 10, $override_day ?? $town->getDay() );
                /** @var ZombieSpawnBehaviour[] $leaders */
                $leaders = [];

                $concat( $zonesWithDeads,
                    fn(ZombieSpawnZone $zone): int => $zone->deads,
                    fn(ZombieSpawnZone $zone, int $value): int => $zone->deads = $value,
                );
                $concat( $zonesWithZombieKills,
                    fn(ZombieSpawnZone $zone): int => $zone->zombieKills,
                    fn(ZombieSpawnZone $zone, int $value): int => $zone->zombieKills = $value,
                );

                $cDead = 0;
                $cZombieKill = 0;

                while ( count($leaders) < $nbLeader - 2 && (!empty( $zonesWithDeads ) || !empty( $zonesWithZombieKills )) ) {

                    if (!empty( $zonesWithDeads )) {
                        $zone = array_shift( $zonesWithDeads );
                        if (mt_rand(0,99) < $zone->deads * 10) {
                            ZombieSpawnBehaviour::Deads( $leaders, $zone, $zone->deads );
                            $cDead++;
                        } elseif ( mt_rand( 0,99 ) < $zone->deads * 20 )
                            $zonesWithDeads = [];
                    }

                    if (!empty( $zonesWithZombieKills )) {
                        $zone = array_shift( $zonesWithZombieKills );
                        if (mt_rand(0,99) < $zone->zombieKills * 10) {
                            ZombieSpawnBehaviour::ZombieKills( $leaders, $zone, $zone->zombieKills );
                            $cZombieKill++;
                        } elseif ( mt_rand( 0,99 ) < $zone->zombieKills * 20 )
                            $zonesWithZombieKills = [];
                    }

                }

                foreach ($baseZones as $zone)
                    if ($zone->building && $zone->zombies <= 3)
                        $zone->addZombie( mt_rand(1, 4) );

                if (!(count($leaders) > 0 && $nbLeader >= count($leaders))) {
                    while ( count($leaders) < $nbLeader )
                        ZombieSpawnBehaviour::OwnWay( $leaders, $this->random->pick( $zones ), $grid_max - $grid_min + 1, $this->random );

                    $i = 0;
                    while ($i < count($orderedZones) && $i < 10) {
                        $zone = $orderedZones[$i];
                        if ($zone->zombies > 20)
                            $leaders[] = new ZombieSpawnBehaviour( HordeSpawnBehaviourType::Move, $zone,
                                out: true, tx: $zone->x, ty: $zone->y, max: 3, power: $zone->zombies * 2
                            );
                        $i++;
                    }
                }

                // Diffuse leads
                foreach ($leaders as $leader)
                    switch ($leader->type) {
                        case HordeSpawnBehaviourType::Grow:
                            $d = 15 + $leader->power * 10;
                            $leader->zone->addLead( $leader, $d );
                            $max = $leader->power < 4 ? 1 : mt_rand( 1, 2 );
                            for ($x = $leader->zone->x - $max; $x <= $leader->zone->x + $max; $x++)
                                for ($y = $leader->zone->y - $max; $y <= $leader->zone->y + $max; $y++) {
                                    $z = $grid($x,$y);
                                    if (!$z || $z->town) continue;
                                    $z->addLead( $leader, $d * ($mt_distanceAttenuation[ ZombieSpawnZone::getZoneLevel( $leader->zone, $z ) ] ?? 0) );
                                }
                            break;
                        case HordeSpawnBehaviourType::Eat:
                            $leader->zone->addLead( $leader, 15 );
                            break;
                        case HordeSpawnBehaviourType::Move:
                            if ($leader->max <= 0) break;
                            for ($x = $leader->zone->x - $leader->max; $x <= $leader->zone->x + $leader->max; $x++)
                                for ($y = $leader->zone->y - $leader->max; $y <= $leader->zone->y + $leader->max; $y++) {
                                    $z = $grid($x,$y);
                                    if (!$z || $z->town) continue;
                                    $z->addLead( $leader, $leader->power * ($mt_distanceAttenuation[ ZombieSpawnZone::getZoneLevel( $leader->zone, $z ) ] ?? 0) );
                                }

                            break;

                    }

                // Apply leads
                foreach ($baseZones as $zone) {
                    if ($zone->town) continue;

                    $zc = $zone->zombies;

                    while ( $zc > 0 && $zl = $zone->getBehaviour( $this->random ) )
                        switch ($zl->type) {
                            case HordeSpawnBehaviourType::Grow:
                                $p = $zl->power;
                                while ($p > 0) {
                                    /** @var ?ZombieSpawnZone $z2 */
                                    $z2 = $this->random->pick( $star($zl->zone, true) );
                                    if (!$z2 || $z2->town) continue;
                                    $z2->addZombie();
                                    $p--;
                                    $zc -= mt_rand(1,2);
                                }
                                break;
                            case HordeSpawnBehaviourType::Eat:
                                if ($zone->zombies <= 0) break;
                                $k = mt_rand( 0, floor( $zl->power / 10 ) );
                                $zone->killZombie( $k );
                                $zc -= $k * 2;
                                break;
                            case HordeSpawnBehaviourType::Move:

                                if (!($tz = $grid( $zl->tx, $zl->tx ))) break;
                                $tlevel = ZombieSpawnZone::getZoneLevel( $zone, $tz );
                                $v = [ $tz->x - $zone->x, $tz->y - $zone->y ];
                                $motivation = floor( $zl->power / 5 );

                                for ($n = 0; $n <= $zc; $n++) {
                                    if (!$zl->out) {
                                        $m = mt_rand(0, $tlevel + floor($motivation / 2) - 1);
                                        if ($m === 0) continue;

                                        $p = $m / ($tlevel + floor($motivation / 2));
                                        $dx = floor($v[0] * $p);
                                        $dy = floor($v[1] * $p);

                                    } else {

                                        $d = floor(max(1, 15 - $tlevel - floor($motivation / 2)));
                                        $m = mt_rand(0, $d - 1);
                                        if ($m === 0) continue;

                                        $p = 1 - $m / $d;
                                        $dx = $v[0] == 0 ? $this->random->pick([-1, 1]) * mt_rand(0, 1) : floor($v[0] * -$p);
                                        $dy = $v[1] == 0 ? $this->random->pick([-1, 1]) * mt_rand(0, 1) : floor($v[1] * -$p);
                                    }

                                    $z2 = $grid($zone->x + $dx, $zone->y + $dy);
                                    if (!$z2 || $z2->town) {
                                        $r1 = mt_rand(0, 1);
                                        $r2 = 1 - $r1;
                                        $z2 = $grid($zone->x + $dx + $this->random->pick([-1, 1]) * $r1, $zone->y + $dy + $this->random->pick([-1, 1]) * $r2);
                                        if (!$z2 || $z2->town) continue;

                                        $z2->addZombie();
                                        $zone->killZombie();
                                    }

                                    $motivation++;
                                }

                                break;
                        }
                }

                break;

            default: throw new \Exception('Invalid governor.');
        }

        // Final
        //TODO: We can delete this after checking it's unused thanks to new scout behaviour
        foreach ($baseZones as $zone) {
            $zone->zone->setScoutEstimationOffset( $zone->town ? 0 : mt_rand(-2,2) );

            if ($zone->town) continue;

            $zone->zone
                ->setZombies( $zone->zombies )
                ->setInitialZombies( $zone->zombies )
                ->setPlayerDeaths(0 );
        }
    }
}
