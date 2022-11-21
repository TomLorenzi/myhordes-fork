<?php


namespace App\Service\Maps;

use App\Entity\Inventory;
use App\Entity\RuinZone;
use App\Entity\RuinZonePrototype;
use App\Entity\Zone;
use App\Service\AdminLog;
use App\Service\ConfMaster;
use App\Service\RandomGenerator;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ConnectedComponents;

class MazeMaker
{

    // -----------------------------------------------------------------------------------------
    // 
    // -----------------------------------------------------------------------------------------

    private EntityManagerInterface $entity_manager;
    private RandomGenerator $random;
    private ConfMaster $conf;
    private float $skipMazeDirectionProbability = 0;
    private float $joinPathProbability = 0.2;
    private bool $enableOpenArea = false;

    private Zone $targetZone;

    private AdminLog $logger;

    const mazeSizeX = 13;
    const mazeSizeY = 13;
    const mazeOffsetX = -7;
    const mazeOffsetY = 1;
        
    const minStepDirection = 3;
    
    // --------

    public function __construct(EntityManagerInterface $em, RandomGenerator $r, ConfMaster $c, AdminLog $l)
    {
        $this->logger = $l;
        $this->entity_manager = $em;
        $this->random = $r;
        $this->conf = $c;
    }

    // -----------------------------------------------------------------------------------------
    // Parameter Setters
    // -----------------------------------------------------------------------------------------

    public function setTargetZone(Zone $zone) {
        $this->targetZone = $zone;
    }

    // -----------------------------------------------------------------------------------------
    // Setup function
    // -----------------------------------------------------------------------------------------

    private function createAndAddRuinZone(int $x, int $y, int $z) {
        $this->logger->invoke("[{$x},{$y},{$z}]");
        $this->targetZone->addRuinZone((new RuinZone())
                ->setCorridor(RuinZone::CORRIDOR_NONE)
                ->setY($y)
                ->setX($x)
                ->setZ($z)
                ->setZombies(0)
                ->setFloor(new Inventory()));
    }
    
    // --------

    private function resetOneRuinZone(RuinZone $ruinZone)
    {
        $ruinZone->setCorridor(RuinZone::CORRIDOR_NONE);
        $ruinZone->setConnect(0);
        $ruinZone->setDistance(9999);
        $ruinZone->setRoomDistance(9999);
        $ruinZone->setDigs(0);
        $ruinZone->setPrototype( null );
        $ruinZone->setLocked( false );
        $ruinZone->setDoorPosition( 0 );
        $ruinZone->setUnifiedDecals( mt_rand(0,0xFFFFFFFF) );
        $ruinZone->setZombies(0)->setKilledZombies(0);

        if ($ruinZone->getRoomFloor()) {
            $this->entity_manager->remove( $ruinZone->getRoomFloor() );
            $ruinZone->setRoomFloor(null);
        }
    }

    // --------

    private function resetMazeContent()
    {
        if ($this->targetZone == null) {
            return;
        }

        foreach ($this->targetZone->getRuinZones() as $ruinZone) {
            $this->resetOneRuinZone($ruinZone);
        }
    }
    
    // --------

    public function createField() {

        if ($this->targetZone == null) {
            return;
        }

        // Create a cache. The idea is if we regenerate the maze, we don't destroy existing case, we just create the missing one. 
        // So we check the one that already exist
        $cache = [];
        foreach ($this->targetZone->getRuinZones() as $ruinZone) {
            $this->logger->invoke("ADD TO CACHE :: [{$ruinZone->getX()},{$ruinZone->getY()},{$ruinZone->getZ()}]");
            if (!isset($cache[$ruinZone->getZ()]))  {
                $cache[$ruinZone->getZ()] = [];
            }
            if (!isset($cache[$ruinZone->getZ()][$ruinZone->getX()])) {
                $cache[$ruinZone->getZ()][$ruinZone->getX()] = [];
            }
            if (!isset($cache[$ruinZone->getZ()][$ruinZone->getX()][$ruinZone->getY()])) {
                $cache[$ruinZone->getZ()][$ruinZone->getX()][$ruinZone->getY()] = $ruinZone;
            }
        }

        if (!isset($cache[0][0][0]))
        {
            $this->createAndAddRuinZone(0, 0, 0);
        }

        for ($x = self::mazeOffsetX; $x < self::mazeOffsetX + self::mazeSizeX; $x++) {
            for ($y = self::mazeOffsetY; $y < self::mazeOffsetY + self::mazeSizeY; $y++) {
                for ($z = 0; $z < $this->targetZone->getExplorableFloors(); $z++) {
                    
                    if (!isset($cache[$z][$x][$y]))
                    {
                        $this->createAndAddRuinZone($x, $y, $z);
                    }
                }
            }
        }
    }

    // -----------------------------------------------------------------------------------------
    // graphyfy
    // -----------------------------------------------------------------------------------------

    private function graphyfy(array $use_binary, ?array &$nodes, ?array &$flat_nodes): Graph {
        $graph = new Graph();
        /** @var Vertex[][] $nodes */
        $nodes = $flat_nodes = [];

        // Returns true if the given coordinates point to an existing zone
        $exists = function(int $x, int $y) use (&$use_binary): bool {
            return (isset($use_binary[$x]) && isset($use_binary[$x][$y]));
        };

        // Returns true if the given coordinates point to an existing zone and the zone is already marked as a corridor
        $corridor = function(int $x, int $y) use (&$use_binary,&$exists): bool {
            return $exists($x,$y) && $use_binary[$x][$y];
        };

        foreach ($use_binary as $x => $bin_line)
            foreach ($bin_line as $y => $active)
                if ($active) {
                    if (!isset($nodes[$x])) $nodes[$x] = [];
                    if (!isset($nodes[$x][$y])) $nodes[$x][$y] = $graph->createVertex("$x/$y");
                    $nodes[$x][$y]->setAttribute('pos', [$x,$y]);
                    $flat_nodes[] = $nodes[$x][$y];
                }

            foreach ($nodes as $x => $nodes_line)
                foreach ($nodes_line as $y => $node) {
                    if ($corridor($x+1,$y)) $node->createEdgeTo( $nodes[$x+1][$y] );
                    if ($corridor($x-1,$y)) $node->createEdgeTo( $nodes[$x-1][$y] );
                    if ($corridor($x,$y+1)) $node->createEdgeTo( $nodes[$x][$y+1] );
                    if ($corridor($x,$y-1)) $node->createEdgeTo( $nodes[$x][$y-1] );
                }

        return $graph;
    }

    // -----------------------------------------------------------------------------------------
    // dykstra(Did you mean Dijkstra ?)
    // -----------------------------------------------------------------------------------------

    /**
     * @param RuinZone[][] $cache
     * @param callable $get_dist
     * @param callable $set_dist
     */
    private function dykstra( array &$cache, callable $get_dist, callable $set_dist) {

        /**
         * @param array $c
         * @param RuinZone $r
         * @return RuinZone[]
         */
        $get_neighbors = function(array &$c, RuinZone $r): array {
            $neighbors = [];
            if ($r->getCorridor() === 0) return $neighbors;
            if ($r->hasCorridor( RuinZone::CORRIDOR_E )) $neighbors[] = $c[$r->getX()+1][$r->getY()];
            if ($r->hasCorridor( RuinZone::CORRIDOR_W )) $neighbors[] = $c[$r->getX()-1][$r->getY()];
            if ($r->hasCorridor( RuinZone::CORRIDOR_S )) $neighbors[] = $c[$r->getX()][$r->getY()+1];
            if ($r->hasCorridor( RuinZone::CORRIDOR_N )) $neighbors[] = $c[$r->getX()][$r->getY()-1];
            return $neighbors;
        };

        /** @var RuinZone[] $distance_stack */
        $distance_stack = [ ];

        // Identify sinks
        foreach ($cache as $line)
            foreach ($line as $zone) {
                $dist = $get_dist($zone);
                foreach ($get_neighbors($cache,$zone) as $n_zone)
                    if ($get_dist($n_zone) > ($dist+1)) {
                        $distance_stack[] = $zone;
                        continue 2;
                    }

            }

        // Calculate distances
        while (!empty($distance_stack)) {
            $current = array_pop($distance_stack);
            $dist = $get_dist( $current );

            foreach ($get_neighbors($cache,$current) as $neighbor)
                if ($get_dist( $neighbor ) > ($dist+1)) {
                    $set_dist( $neighbor, $dist+1 );
                    $distance_stack[] = $neighbor;
                }
        }

    }

    // -----------------------------------------------------------------------------------------
    // Global generation
    // -----------------------------------------------------------------------------------------

    public function generateCompleteMaze() {

        if ($this->targetZone == null) {
            return;
        }

        $this->resetMazeContent();

        $levels = $this->targetZone->getExplorableFloors();
        $invert = $this->targetZone->getPrototype()->getExplorableSkin() === 'bunker';

        $origin = [0,0];
        $originOffset = 0;

        for ($i = 0; $i < $levels; $i++) {
            $this->generateMaze($i, $origin, $originOffset);
            $originZone = $this->generateRoom($i, $origin, $originOffset, $i < ($levels-1), $invert);
            if (!$originZone) break;
            $origin = [ $originZone->getX(), $originZone->getY() ];
            $originOffset += $originZone->getDistance() + 1;
        }

        $this->populateMaze($this->conf->getTownConfiguration( $this->targetZone->getTown() )->get(TownConf::CONF_EXPLORABLES_ZOMBIES_INI, 25) * $levels );
    }

    // -----------------------------------------------------------------------------------------
    // Maze Corridor generation
    // -----------------------------------------------------------------------------------------

    /**
     * @param int $level
     * @param array $origin
     * @param int $offset_distance
     */
    protected function generateMaze( int $level = 0, array $origin = [0,0], int $offset_distance = 0 ) {

        /** @var RuinZone[][] $cache */
        $cache = []; $binary = []; 
        foreach ($this->targetZone->getRuinZonesOnLevel($level) as $ruinZone) {
            if (!isset($cache[$ruinZone->getX()])) $cache[$ruinZone->getX()] = [];
            if (!isset($cache[$ruinZone->getX()][$ruinZone->getY()])) {
                $cache[$ruinZone->getX()][$ruinZone->getY()] = $ruinZone;
                $binary[$ruinZone->getX()][$ruinZone->getY()] = false;
            }
        }

        $binary[$origin[0]][$origin[1]] = true;

        // Returns true if the given coordinates point to an existing zone
        $exists = function(int $x, int $y) use (&$cache): bool {
            return (isset($cache[$x]) && isset($cache[$x][$y]));
        };

        // Returns true if the given coordinates point to an existing zone and the zone is already marked as a corridor
        $corridor = function(int $x, int $y) use (&$binary,&$exists): bool {
            return $exists($x,$y) && $binary[$x][$y];
        };

        // Invalidated max ? Kezako ?
        $head = $cache[$origin[0]][$origin[1]];
        $binary[$origin[0]][$origin[1]] = true;

        $walker = [];
        array_push($walker,$head);

        $dirs = [ [ 1, 0 ], [ 0, 1 ], [ -1, 0 ], [ 0, -1 ] ];
        $dTime = 0;

        $countcorridor = 0;
        while (count($walker) > 0)
        {
			// Sometimes, we change order in which directions are checks, because why not?
            $dTime--;
            if ($dTime < 0)
            {
                $indexToChange = 1+mt_rand(0, 2);
                $d = $dirs[$indexToChange];
                array_splice($dirs, $indexToChange, 1);
                array_unshift($dirs, $d);
                $dTime = self::minStepDirection + mt_rand(0, 1);
            }

            $next = null;
            // Check all directions
            foreach ($dirs as &$dir)
            {
				// Sometime we skip a direction
                if((mt_rand() / mt_getrandmax()) < $this->skipMazeDirectionProbability) continue;

                $tx = $head->getX() + $dir[0];
                $ty = $head->getY() + $dir[1];
				// Is the direction we check has valide coordinate (no oob)
				if ( $exists($tx, $ty) ) {
                    $next = $cache[$tx][$ty];
					// Is the data at the coordinate in the direction we are checking is a a wall
                    if ($binary[$tx][$ty] == false)
                    {
                        $wallCount = 0;
                        $wDirX = 0;
                        $wDirY = 0;
						// We check walls around the case we want to check, count them, and keep track in which axis we found them
                        foreach ($dirs as &$dir2)
                        {
                            $ty = $next->getY() + $dir2[1];
                            $tx = $next->getX() + $dir2[0];
							if ( $exists($tx, $ty) == false || $binary[$tx][$ty] == false ) {
                                $wallCount++;
                                $wDirX += $dir2[0] * $dir2[0];
                                $wDirY += $dir2[1] * $dir2[1];
                            }
                        }

						// If we break to an other path
                        if ($wallCount < 3)
                        {
                            if ( (mt_rand() / mt_getrandmax()) < $this->joinPathProbability && ($this->enableOpenArea || ($wallCount == 2 && ($wDirX == 0 || $wDirY == 0) ))) {
                                $binary[$next->getX()][$next->getY()] = true;
                                $countcorridor++;
                                //next.distance = head.distance + 1;
                                //we break path, so we need to update distance of nodes !
                                //updateDistance(next);
                            }
                            $next = null;
                        }
                    } else {
                        $next = null;
                    }
                }
				if ( $next != null ) break;
            }
            if ( $next == null ) {
				$head = array_pop($walker);
				$dTime = 0;
			} else {
                $binary[$next->getX()][$next->getY()] = true;
                $countcorridor++;
				//next.distance = head.distance + 1;
				array_push($walker, $next);
				$head = $next;
			}	
        }

        // Build the actual map
        foreach ($cache as $x => $line)
        foreach ($line as $y => $ruinZone)
        {
            if ($corridor($x,$y)) {
                if ($corridor($x+1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_E );
                if ($corridor($x-1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_W );
                if ($corridor($x,$y+1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_S );
                if ($corridor($x,$y-1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_N );
            } else $ruinZone->setCorridor(RuinZone::CORRIDOR_NONE);
        }

        // Calculate distances
        $cache[$origin[0]][$origin[1]]->setDistance($offset_distance);
        $this->dykstra($cache,
            function (RuinZone $r): int { return $r->getDistance(); },
            function (RuinZone $r, int $i): void { $r->setDistance( $i ); }
        );
    }

    // -----------------------------------------------------------------------------------------
    // Maze Room generation
    // -----------------------------------------------------------------------------------------
    /**
     * @param int $level
     * @param array $origin
     * @param int $offset_distance
     * @param bool $go_up
     * @param bool $invertDirections 
     */
    public function generateRoom(int $level = 0, array $origin = [0,0], int $offset_distance = 0, bool $go_up = false, bool $invertDirections = false): ?RuinZone {
        $cache = [];
        foreach ($this->targetZone->getRuinZonesOnLevel($level) as $ruinZone) {
            if ($ruinZone->getCorridor() != RuinZone::CORRIDOR_NONE)
            {
                if (!isset($cache[$ruinZone->getX()]))  {
                    $cache[$ruinZone->getX()] = [];
                }
                if (!isset($cache[$ruinZone->getX()][$ruinZone->getY()])) {
                    $cache[$ruinZone->getX()][$ruinZone->getY()] = $ruinZone;
                }
            }
        }

        // Returns true if the given coordinates point to an existing corridor
        $exists = function(int $x, int $y) use (&$cache): bool {
            return (isset($cache[$x]) && isset($cache[$x][$y]));
        };


        $conf =  $this->conf->getTownConfiguration( $this->targetZone->getTown() );
        
        // Let's add some rooms!
        $room_distance = $conf->get(TownConf::CONF_EXPLORABLES_ROOMDIST,   5) + $offset_distance;
        $lock_distance = $conf->get(TownConf::CONF_EXPLORABLES_LOCKDIST,  10);
        $room_dist     = $conf->get(TownConf::CONF_EXPLORABLES_ROOM_DIST,  4);
        $room_count    = $conf->get(TownConf::CONF_EXPLORABLES_ROOMS,     10);

        // Room candidates
        $room_candidates = [];
        foreach ($cache as $x => $line) {
            foreach ($line as $y => $ruinZone) {
                if ($exists($x,$y) && ($x * $y !== 0) && $ruinZone->getDistance() > $room_distance) {
                    $room_candidates[] = $ruinZone;
                }
            }
        }

        // Get room types
        $locked_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findLocked();
        $unlocked_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findUnlocked();
        $up_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findUp();
        $down_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findDown();

        $up_count = $go_up ? 1 : 0;
        $down_count = $level > 0 ? 1 : 0;

        $up_candidates = [];
        $up_position = null;

        while (($room_count > 0 || $up_count > 0 || $down_count > 0) && !empty($room_candidates)) {
            $place_down = $down_count > 0;
            $place_up   = !$place_down && $up_count > 0;

            $room_candidates = array_filter( $room_candidates, function(RuinZone $r) use ($room_dist) {
                return $r->getRoomDistance() >= $room_dist;
            } );

            if ($place_up) $up_candidates = array_filter( $room_candidates, function(RuinZone $r) use ($lock_distance) {
                return $r->getDistance() > $lock_distance;
            } );

            /** @var RuinZone $room_corridor */
            $room_corridor = $place_down ? $cache[$origin[0]][$origin[1]] : $this->random->pick( $place_up && !empty($up_candidates) ? $up_candidates : $room_candidates, 1 );
            $far = $room_corridor->getDistance() > $lock_distance;

            // Determine possible locations for the door
            $valid_locations = [];
            if ( $room_corridor->hasCorridor( RuinZone::CORRIDOR_E )) $valid_locations = array_merge($valid_locations, [3,8]); else $valid_locations[] = 5;
            if ( $room_corridor->hasCorridor( RuinZone::CORRIDOR_W )) $valid_locations = array_merge($valid_locations, [1,6]); else $valid_locations[] = 4;
            if (!$room_corridor->hasCorridor( RuinZone::CORRIDOR_N )) $valid_locations[] = 2;
            if (!$room_corridor->hasCorridor( RuinZone::CORRIDOR_S )) $valid_locations[] = 7;

            $room_corridor
                ->setRoomDistance(0)
                ->setDoorPosition( $this->random->pick( $valid_locations ) );

            if ($place_down) $room_corridor
                ->setPrototype( $this->random->pick( $invertDirections ? $up_room_types : $down_room_types ) )
                ->setConnect(-1 );
            elseif ($place_up) $room_corridor
                ->setPrototype( $this->random->pick( $invertDirections ? $down_room_types : $up_room_types ) )
                ->setConnect( 1 );
            else $room_corridor
                ->setPrototype( $this->random->pick( $far ? $locked_room_types : $unlocked_room_types ) )
                ->setRoomFloor( (new Inventory())->setRuinZoneRoom( $room_corridor ) )
                ->setLocked( $far );

            $this->dykstra($cache,
                function (RuinZone $r): int { return $r->getRoomDistance(); },
                function (RuinZone $r, int $i): void { $r->setRoomDistance( $i ); }
            );

            if ($place_up) $up_position = $room_corridor;

            if ($place_down) $down_count--;
            elseif ($place_up) $up_count--;
            else $room_count--;
        }

        // Place decals
        foreach ($cache as $x => $line) {
            foreach ($line as $y => $ruinZone) {
                // Get decal value
                $decal_filter = 0;

                // Determine possible locations for the decal
                if (!$ruinZone->hasCorridor( RuinZone::CORRIDOR_E )) $decal_filter |= ((1<< 6) + (1<< 8) + (1<<12));
                if (!$ruinZone->hasCorridor( RuinZone::CORRIDOR_W )) $decal_filter |= ((1<< 3) + (1<< 7) + (1<< 9));
                if (!$ruinZone->hasCorridor( RuinZone::CORRIDOR_N )) $decal_filter |= ((1<< 0) + (1<< 1) + (1<< 2));
                if (!$ruinZone->hasCorridor( RuinZone::CORRIDOR_S )) $decal_filter |= ((1<<13) + (1<<14) + (1<<15));

                // Do not overlay the entrance
                if ($x === 0 && $y === 0) $decal_filter |= ((1<< 4) + (1<< 5));

                // Do not overlay door with decals!
                switch ($ruinZone->getDoorPosition()) {
                    case 1: $decal_filter |= (1<< 3);  break;
                    case 3: $decal_filter |= (1<< 6);  break;
                    case 6: $decal_filter |= (1<< 9);  break;
                    case 8: $decal_filter |= (1<<12); break;
                }

                $ruinZone->setUnifiedDecals( $ruinZone->getUnifiedDecals() & (~$decal_filter) );
            }
        }

        return $up_position;
    }


    // -----------------------------------------------------------------------------------------
    // Maze Population generation
    // -----------------------------------------------------------------------------------------
    /**
     * @param int $zeds
     * @param bool|false $reposition
     * @param bool $clear_bodies
     * @param RuinZone[] $skip_zone
     */
    public function populateMaze( int $zeds, bool $reposition = false, bool $clear_bodies = true, array $skip_zone = [] ) {
        /** @var RuinZone[] $ruinZones */
        $ruinZones = $this->targetZone->getRuinZones()->getValues();
        if ($reposition || $clear_bodies)
            foreach ($ruinZones as $ruinZone) {
                if ($clear_bodies) $ruinZone->setKilledZombies(0);
                if ($reposition) {
                    $zeds += $ruinZone->getZombies();
                    $ruinZone->setZombies(0);
                }
            }

        // We only need to look at relevant ruin zones
        $ruinZones = array_filter( $ruinZones, function(RuinZone $r) use ($skip_zone) {
            return $r->getCorridor() > 0 && $r->getZombies() < 4 && !in_array( $r, $skip_zone, true ) && ($r->getX() !== 0 || $r->getY() !== 0);
        } );
        shuffle( $ruinZones );

        while ( $zeds > 0 && !empty($ruinZones) ) {
            $current = array_pop( $ruinZones );
            $spawn = mt_rand(1, min($zeds, 4 - $current->getZombies()) );
            $current->setZombies( $current->getZombies() + $spawn );
            $zeds -= $spawn;
        }

    }
}
