<?php

namespace App\Service\Maps;

use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Structures\TownConf;
use App\Structures\ZombieSpawnZone;
use Doctrine\ORM\EntityManagerInterface;

class MapMaker
{
    private EntityManagerInterface $entity_manager;
    private RandomGenerator $random;
    private ConfMaster $conf;
    private MazeMaker $maze_maker;
    private InventoryHandler $inventory_handler;
    private ItemFactory $item_factory;

    public function __construct(EntityManagerInterface $em, RandomGenerator $r, ConfMaster $c, MazeMaker $m,
                                InventoryHandler $ih, ItemFactory $if)
    {
        $this->entity_manager = $em;
        $this->random = $r;
        $this->conf = $c;
        $this->maze_maker = $m;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
    }

    public function createMap( Town $town ): void {
        $conf = $this->conf->getTownConfiguration( $town );

        $defaultTag = $this->entity_manager->getRepository(ZoneTag::class)->findOneBy(['ref' => ZoneTag::TagNone]);
        $map_resolution = $this->getDefaultZoneResolution( $conf, $ox, $oy );
        for ($x = 0; $x < $map_resolution; $x++)
            for ($y = 0; $y < $map_resolution; $y++) {
                $zone = new Zone();
                $zone
                    ->setX( $x - $ox )
                    ->setY( $y - $oy )
                    ->setDigs( mt_rand( $conf->get(TownConf::CONF_ZONE_ITEMS_MIN, 5), $conf->get(TownConf::CONF_ZONE_ITEMS_MAX, 10) ) )
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
        for ($i = 0; $i < $spawn_ruins + $conf->get(TownConf::CONF_MAP_FREE_SPAWN_COUNT, 2); $i++) {

            $zombies_base = 0;
            do {
                if (($i+$o) >= count($zone_list)) continue 2;
                $b = $cl_get( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );
                if ($b <= 1) $keep_location = true;
                else if ($b === 2) $keep_location = $this->random->chance(0.25);
                else $keep_location = false;

                if (!$keep_location) $o++;
            } while ( !$keep_location );

            $cl_set( $zone_list[$i+$o]->getX(), $zone_list[$i+$o]->getY() );

            if ($i < $spawn_ruins) {

                $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )/18) * 18);

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

                $zone_list[$i+$o]
                    ->setPrototype( $target_ruin )
                    ->setRuinDigs( mt_rand( $conf->get(TownConf::CONF_RUIN_ITEMS_MIN, 10), $conf->get(TownConf::CONF_RUIN_ITEMS_MAX, 10) ) );

                if ($conf->get(TownConf::CONF_FEATURE_CAMPING, false))
                    $zone_list[$i+$o]->setBlueprint(Zone::BlueprintAvailable);

                if ($this->random->chance($conf->get(TownConf::CONF_MAP_BURIED_PROB, 0.5)))
                    $zone_list[$i+$o]->setBuryCount( mt_rand($conf->get(TownConf::CONF_MAP_BURIED_DIGS_MIN, 6), $conf->get(TownConf::CONF_MAP_BURIED_DIGS_MAX, 20)) );

            } else
                if ($this->random->chance( $conf->get(TownConf::CONF_MAP_FREE_SPAWN_PROB, 0.1) ))
                    $zombies_base = 1 + floor(min(1,sqrt( pow($zone_list[$i+$o]->getX(),2) + pow($zone_list[$i+$o]->getY(),2) )/18) * 3);

            if ($zombies_base > 0) {
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $zone_list[$i+$o]->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
            }
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

            $maxDistance = $conf->get(TownConf::CONF_EXPLORABLES_MAX_DISTANCE, 100);
            $spawn_zone = $this->random->pickLocationBetweenFromList($zone_list, $spawning_ruin->getMinDistance(), $maxDistance, ['prototype_id' => null]);

            if ($spawn_zone) {
                $spawn_zone->setPrototype($spawning_ruin);
                $this->maze_maker->setTargetZone($spawn_zone);
                $spawn_zone->setExplorableFloors($conf->get(TownConf::CONF_EXPLORABLES_FLOORS, 1));
                $this->maze_maker->createField();
                $this->maze_maker->generateCompleteMaze();

                $zombies_base = 1 + floor(min(1,sqrt( pow($spawn_zone->getX(),2) + pow($spawn_zone->getY(),2) )/18) * 3);
                $zombies_base = max(1, mt_rand( floor($zombies_base * 0.8), ceil($zombies_base * 1.2) ) );
                $spawn_zone->setZombies( $zombies_base )->setInitialZombies( $zombies_base );
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

        $this->dailyZombieSpawn( $town, 1, self::RespawnModeNone );
        foreach ($town->getZones() as $zone) $zone->setStartZombies( $zone->getZombies() );
    }

    private function getDefaultZoneResolution( TownConf $conf, ?int &$offset_x, ?int &$offset_y ): int {
        $resolution = mt_rand( $conf->get(TownConf::CONF_MAP_MIN, 0), $conf->get(TownConf::CONF_MAP_MAX, 0) );
        $safe_border = ceil($resolution * $conf->get(TownConf::CONF_MAP_MARGIN, 0.25));

        if ($safe_border >= $resolution/2) {
            $offset_x = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
            $offset_y = mt_rand(floor(($resolution-1)/2), ceil(($resolution-1)/2));
        } else {
            $offset_x = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
            $offset_y = $safe_border + mt_rand(0, max(0,$resolution - 2*$safe_border));
        }

        return $resolution;
    }

    const RespawnModeNone = 0;
    const RespawnModeAuto = 1;
    const RespawnModeForce = 2;

    public function dailyZombieSpawn( Town $town, int $cycles = 1, int $mode = self::RespawnModeAuto, ?int $override_day = null ): void
    {
        $govMH = true;

        if ($govMH) $this->zombieSpawnGovernorMH( $town, $cycles, $mode, $override_day );
        else for ($i = 0; $i < $cycles; ++$i) $this->zombieSpawnGovernorHordes( $town );
    }

    private function zombieSpawnGovernorMH( Town $town, int $cycles = 1, int $mode = self::RespawnModeAuto, ?int $override_day = null ): void {
        /** @var Zone[] $zones */
        $zones = $town->getZones()->getValues();
        $zone_db = []; $despair_db = [];
        $killedZombies = 0;

        $total_zombies = 0;
        foreach ($zones as &$zone) {
            $total_zombies += $zone->getZombies();
            $killedZombies += ($zone->getInitialZombies() - $zone->getZombies());

            $despair = floor(max(0,( $zone->getInitialZombies() - $zone->getZombies() - 1 ) / 2));
            if (!isset($zone_db[$zone->getX()])) $zone_db[$zone->getX()] = [];
            $zone_db[$zone->getX()][$zone->getY()] = $zone->getZombies();
            $despair_db[$zone->getX()][$zone->getY()] = $despair;

            $zone->setScoutEstimationOffset( mt_rand(-2,2) );
        }

        $factor = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_RESPAWN_FACTOR, 1);

        $town->getMapSize($map_x,$map_y);

        $fun_cycle = function(bool $observe_despair = false) use (&$zone_db,$despair_db): int {
            $cycle_result = 0;
            $zone_original_db = $zone_db;
            foreach ($zone_db as $x => &$zone_row)
                foreach ($zone_row as $y => &$current_zone_zombies) {

                    if (($x === 0 && $y === 0) || ($despair_db[$x][$y] > 0 && $observe_despair)) continue;

                    $before = $current_zone_zombies;

                    // We're iterating over the 4 directly adjacent zones
                    $adj_zones_total = $adj_zones_infected = $neighboring_zombies = $max_neighboring_zombies = 0;
                    for ($dx = -1; $dx <= 1; $dx++)
                        if (isset($zone_original_db[$x + $dx]))
                            for ($dy = -1; $dy <= 1; $dy++) if (abs($dx) !== abs($dy)) {
                                if (isset($zone_original_db[$x + $dx][$y + $dy])) {
                                    // If the zone exist, increase number of neighboring zones
                                    $adj_zones_total++;

                                    // Count the number of neighboring zombies
                                    $neighboring_zombies += $zone_original_db[$x + $dx][$y + $dy];
                                    $max_neighboring_zombies = max( $max_neighboring_zombies, $zone_original_db[$x + $dx][$y + $dy] );

                                    // If the zone has zombies, increase the number of infected neighboring zones
                                    if ($zone_original_db[$x + $dx][$y + $dy] > $zone_original_db[$x][$y])
                                        $adj_zones_infected++;
                                }
                            }

                    if ($current_zone_zombies > 0) {
                        $avg_dif = max(0, floor($neighboring_zombies / $adj_zones_total) - $current_zone_zombies) + 2;

                        // If the zone already has zombies, increase count by 0 - 2
                        // We're using -1 instead of 0 to increase the bias towards 0
                        $current_zone_zombies += max(0, mt_rand(-1, $avg_dif));
                    } else {
                        // Otherwise, count the total number of adjacent zones with zombies

                        // If we have infected neighboring zones
                        if ($adj_zones_infected > 0) {
                            // Number of zones with zombies, balanced by total number of neighboring zones
                            $target_number = (int)round($adj_zones_infected * (4.0 / $adj_zones_total));

                            // Depending on the number of neighboring zombies, we create a bias towards not spawning
                            // any new zombies. More neighboring zombies = less bias.
                            $bias = 0;
                            if ($max_neighboring_zombies >= 15)    $bias = -1;
                            elseif ($max_neighboring_zombies >= 8) $bias =  0;
                            elseif ($neighboring_zombies < 5)  $bias = 4;
                            elseif ($neighboring_zombies < 10) $bias = 3;
                            elseif ($neighboring_zombies < 15) $bias = 2;
                            elseif ($neighboring_zombies < 20) $bias = 1;

                            // Calculate random value between bias and 4
                            $new_zeds = mt_rand(-$bias, 4);

                            // Repeat if the result is > 0 and not the same as the number of neighboring infected zones
                            // This created a bias towards spawning the same number of zombies as there are infected zones
                            if ($new_zeds > 0 && $new_zeds !== $target_number)
                                $new_zeds = mt_rand(-$bias, 4);

                            // Clamp the result to a 0 - 4 range.
                            $current_zone_zombies += max(0, min(4, $new_zeds));
                        }

                    }

                    $cycle_result += ($current_zone_zombies - $before);
                }

            return $cycle_result;
        };

        $fun_check_respawn = function(int $zombies, int $mapx, int $mapy, int $day, float $f) : bool {
            return $day > 3 && ($zombies < sqrt($mapx * $mapy) * $day * 2 * $f);
        };

        // Respawn
        $d = $override_day ?? $town->getDay();
        if ($mode === self::RespawnModeForce ||
            ($mode === self::RespawnModeAuto && $fun_check_respawn($total_zombies,$map_x,$map_y,$d,$factor))) {

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
            while ( $fun_check_respawn($total_zombies,$map_x,$map_y,$d,$factor*2) )
                $total_zombies += $fun_cycle();

            // Step 4: Add the original zombies back onto the map
            foreach ($zones as &$zone)
                $zone_db[$zone->getX()][$zone->getY()] =
                    $zone_db[$zone->getX()][$zone->getY()] + $zone_db_before_respawn[$zone->getX()][$zone->getY()];
        }


        for ($c = 0; $c < $cycles; $c++)
            $fun_cycle($c == 0);

        foreach ($town->getZones() as &$zone) {
            if ($zone->getX() === 0 && $zone->getY() === 0) continue;

            $zombies = max( 0, $zone_db[$zone->getX()][$zone->getY()] );
            $zone->setZombies( max(0, floor($zombies - $despair_db[$zone->getX()][$zone->getY()] )));
            $zone->setInitialZombies( $zombies );
            $zone->setPlayerDeaths(0 );
        }
    }

    // INCOMPLETE
    private function zombieSpawnGovernorHordes( Town $town ): void {

        $mapGrid = [];

        /** @var ZombieSpawnZone[] $baseZones */
        $baseZones = [];    // All zones

        /** @var ZombieSpawnZone[] $zones */
        $zones = [];        // All zones with zombies

        // Built data structures
        foreach ($town->getZones() as $zone) {
            $container = new ZombieSpawnZone($zone);
            $baseZones[] = $container;
            if (!isset( $mapGrid[$zone->getX()] )) $mapGrid[$zone->getX()] = [];
            if (!isset( $mapGrid[$zone->getX()][$zone->getY()] )) $mapGrid[$zone->getX()][$zone->getY()] = $container;
        }

        // ### Helper functions

        // Writes all zones with zombies into the $zones array
        $group_zones = function() use (&$zones, &$baseZones) {
            $zones = array_filter( $baseZones, fn(ZombieSpawnZone $z) => $z->zombies > 0 );
        };

        // Returns a ZombieSpawnZone from coordinates or from a Zone instance; returns null for invalid coords
        $grid = fn(int|Zone $x, ?int $y = null): ?ZombieSpawnZone => is_a($x, Zone::class)
            ? ($mapGrid[$x->getX()][$x->getY()] ?? null)
            : ($mapGrid[$x][$y] ?? null)
        ;

        // Returns all zones adjacent to the given zone that are not the town zone
        $adjacent = fn(ZombieSpawnZone $z): array => array_filter( [
            $grid($z->x + 1, $z->y), $grid($z->x, $z->y + 1),
            $grid($z->x - 1, $z->y), $grid($z->x, $z->y - 1),
        ], fn( ?ZombieSpawnZone $zz ) => $zz && !$zz->town );

        // ### Config

        $mt_ZombieGrowThreshold = 3;
        $mt_ZombieSpread = 2;
        $mt_OverThresholdGrowChance = 0.75;

        // ### Online

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

        // Final

        foreach ($baseZones as $zone) {
            if ($zone->town) continue;

            $zone->zone
                ->setZombies( $zone->zombies )
                ->setInitialZombies( $zone->zombies )
                ->setPlayerDeaths(0 )
                ->setScoutEstimationOffset( mt_rand(-2,2) );
        }
    }
}