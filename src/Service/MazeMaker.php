<?php


namespace App\Service;

use App\Entity\Inventory;
use App\Entity\RuinZone;
use App\Entity\RuinZonePrototype;
use App\Entity\Zone;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Vertex;
use Graphp\Algorithms\ConnectedComponents;
use Graphp\Algorithms\MinimumSpanningTree\Kruskal;

class MazeMaker
{
    private $entity_manager;
    private $random;
    private $conf;

    public function __construct(EntityManagerInterface $em, RandomGenerator $r, ConfMaster $c)
    {
        $this->entity_manager = $em;
        $this->random = $r;
        $this->conf = $c;
    }

    public function createField( Zone $base ) {

        $base->addRuinZone((new RuinZone())
            ->setCorridor(RuinZone::CORRIDOR_NONE)
            ->setY(0)
            ->setX(0)
            ->setZombies(0)
            ->setFloor(new Inventory()));

        for ($x = -7; $x <= 5; $x++)
            for ($y = 1; $y <= 13; $y++)
                $base->addRuinZone((new RuinZone())
                    ->setCorridor(RuinZone::CORRIDOR_NONE )
                    ->setY($y)
                    ->setX($x)
                    ->setZombies(0)
                    ->setFloor(new Inventory())
                );
    }

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
        foreach ($cache as &$line)
            foreach ($line as &$zone) {
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

    public function generateMaze( Zone $base ) {

        /** @var RuinZone[][] $cache */
        $cache = []; $binary = []; $n = 0;
        foreach ($base->getRuinZones() as $ruinZone) {
            if (!isset($cache[$ruinZone->getX()])) $cache[$ruinZone->getX()] = [];
            if (!isset($cache[$ruinZone->getX()][$ruinZone->getY()])) {
                $cache[$ruinZone->getX()][$ruinZone->getY()] = $ruinZone
                    ->setCorridor(RuinZone::CORRIDOR_NONE)
                    ->setDistance(9999)
                    ->setRoomDistance(9999)
                    ->setDigs(0)
                    ->setPrototype( null )
                    ->setLocked( false )
                    ->setDoorPosition( 0 )
                    ->setDecals( mt_rand(0,4294967295) )
                    ->setZombies(0)->setKilledZombies(0);

                if ($ruinZone->getRoomFloor()) {
                    $this->entity_manager->remove( $ruinZone->getRoomFloor() );
                    $ruinZone->setRoomFloor(null);
                }

                $binary[$ruinZone->getX()][$ruinZone->getY()] = false;
                $n++;
            }
        }

        $binary[0][0] = true;
        $binary[0][1] = true;

        $intersections = ['0/1' => [0,1]];

        // Returns true if the given coordinates point to an existing zone
        $exists = function(int $x, int $y) use (&$binary): bool {
            return (isset($binary[$x]) && isset($binary[$x][$y]));
        };

        // Returns true if the given coordinates point to an existing zone and the zone is already marked as a corridor
        $corridor = function(int $x, int $y) use (&$binary,&$exists): bool {
            return $exists($x,$y) && $binary[$x][$y];
        };

        // Returns true if the given coordinates point to an existing zone that is not yet a corridor, but could be
        // turned into one without breaking the map rules
        $valid = function(int $x, int $y) use (&$corridor,&$exists): bool {
            return $exists($x,$y) && !$corridor($x,$y) &&
                (!$corridor($x-1,$y) || !$corridor($x,$y-1) || !$corridor($x-1,$y-1)) &&
                (!$corridor($x+1,$y) || !$corridor($x,$y-1) || !$corridor($x+1,$y-1)) &&
                (!$corridor($x-1,$y) || !$corridor($x,$y+1) || !$corridor($x-1,$y+1)) &&
                (!$corridor($x+1,$y) || !$corridor($x,$y+1) || !$corridor($x+1,$y+1));
        };

        // Returns true if the given coordinates point to an existing zone that is a corridor and can spawn additional
        // pathways
        $valid_intersection = function(int $x, int $y) use (&$corridor,&$valid): bool {
            return $corridor($x,$y) && ($valid($x+1,$y) || $valid($x-1,$y) || $valid($x,$y+1) || $valid($x,$y-1));
        };

        // Attempts to mark the coordinates as intersection if possible
        $mark_intersection = function(int $x, int $y) use (&$valid_intersection, &$intersections, &$binary): bool {
            if ($valid_intersection($x,$y) && !isset($intersections["$x/$y"])) {
                $intersections["$x/$y"] = [$x,$y];
                return true;
            } return false;
        };

        // Removes coordinates from intersection list
        $unmark_intersection = function(int $x, int $y) use (&$intersections) {
            unset($intersections["$x/$y"]);
        };

        // Returns a random intersection, or null if none is left
        $get_intersection = function() use (&$intersections): ?array {
            return $this->random->pick($intersections);
        };

        // Turns an existing zone into a corridor
        $add = function($x, $y) use (&$binary,&$exists) {
            if ($exists($x,$y)) $binary[$x][$y] = true;
        };

        // Returns the number of neighbor corridors for a zone
        $neighbors = function($x,$y) use (&$corridor): int {
            return
                ($corridor($x+1,$y) ? 1 : 0) + ($corridor($x-1,$y) ? 1 : 0) +
                ($corridor($x,$y+1) ? 1 : 0) + ($corridor($x,$y-1) ? 1 : 0);
        };

        $conf =  $this->conf->getTownConfiguration( $base->getTown() );
        $complexity = $conf->get(TownConf::CONF_EXPLORABLES_COMPLEXITY, 0.5);
        $convolution = $conf->get(TownConf::CONF_EXPLORABLES_CONVOLUTION, 0.75);
        $cruelty     = $conf->get(TownConf::CONF_EXPLORABLES_CRUELTY, 0.06);

        $c_left = ceil( $n * $complexity );

        // As long as we have more intersections ...
        while ($c_left > 0 && $start = $get_intersection()) {

            // Get coordinates
            list($x,$y) = $start;

            // Make sure the intersection is still valid; otherwise, remove it from the list and continue
            if (!$valid_intersection($x,$y)) {
                $unmark_intersection($x,$y);
                continue;
            }

            // Find possible directions to walk; there must be at least one, otherwise the intersection check would have
            // failed
            $list = array_filter( [[1,0],[-1,0],[0,1],[0,-1]], function (array $coords) use (&$valid,&$x,&$y) {
                return $valid($x+$coords[0],$y+$coords[1]);
            } );

            // Randomly select direction
            list($dx,$dy) = $this->random->pick( $list );

            // Walk in that direction
            while ($c_left > 0 && !$this->random->chance($convolution) && $valid($x+$dx,$y+$dy)) {
                $x += $dx; $y += $dy;
                $add($x,$y);
                $mark_intersection($x,$y);
            }
        }

        // Post-processing - remove corridors to make the layout more challenging
        $remove_nodes = floor( $n * $cruelty );
        $tries = 0;

        /** @var Vertex[] $list */
        $graph = $this->graphyfy($binary, $nodes, $list);
        $removed_nodes = 0;

        // While we still have nodes to remove
        while ($remove_nodes > 0 && $tries < 200) {
            $tries++;

            // Create a working copy of the current graph
            $temp  = $graph->createGraphClone();

            // Select a random node that is not 0/0 and attempt to remove it
            // Also do not remove dead ends with only one neighbor; removing them does not increase complexity
            $nid = array_rand($list);
            if ($list[$nid]->getId() === '0/0') continue;
            list($x,$y) = $list[$nid]->getAttribute('pos');
            if ($neighbors($x,$y) <= 1) continue;
            $temp->getVertex( $list[$nid]->getId() )->destroy();

            // Create a connected component graph with all nodes accessible from 0/0
            $cc = new ConnectedComponents($temp);
            $subgraph = $cc->createGraphComponentVertex( $temp->getVertex("0/0") );

            // If the connected component graph is identical to the source graph, it is still fully traversable
            if ($subgraph->getVertices()->count() == $temp->getVertices()->count()) {
                // Remove the associated corridor
                $binary[$x][$y] = false;

                // Remove the vertex
                unset( $list[$nid] );

                // Update the graph
                $graph = $temp;

                // Count down removed nodes
                $remove_nodes--;
                $removed_nodes++;
            };
        }

        // Attempt to re-adds nodes without forming new corridor connections
        // First, identify corridors that can be added back in
        $add_list = [];
        foreach ($binary as $x => &$line)
            foreach ($line as $y => &$entry)
                if ($neighbors($x,$y) === 1 && !$corridor($x,$y))
                    $add_list[] = [$x,$y];

        // Select and add them back in
        shuffle($add_list);
        while ($removed_nodes > 0 && !empty($add_list)) {
            list($x,$y) = array_pop($add_list);
            if ($valid($x,$y) && $neighbors($x,$y) === 1)
                $add($x,$y);
        }

        // Room candidates
        $room_candidates = [];

        // Build the actual map
        foreach ($cache as $x => $line)
            foreach ($line as $y => $ruinZone)
                if ($corridor($x,$y)) {

                    if ($x !== 0 && $y !== 0) $room_candidates[] = $ruinZone;

                    if ($corridor($x+1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_E );
                    if ($corridor($x-1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_W );
                    if ($corridor($x,$y+1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_S );
                    if ($corridor($x,$y-1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_N );

                } else $ruinZone->setCorridor(RuinZone::CORRIDOR_NONE);

        // Calculate distances
        $cache[0][0]->setDistance(0);
        $this->dykstra($cache,
            function (RuinZone $r): int { return $r->getDistance(); },
            function (RuinZone $r, int $i): void { $r->setDistance( $i ); }
        );

        // Let's add some rooms!
        $lock_distance = $conf->get(TownConf::CONF_EXPLORABLES_LOCKDIST,  10);
        $room_dist     = $conf->get(TownConf::CONF_EXPLORABLES_ROOM_DIST,  4);
        $room_count    = $conf->get(TownConf::CONF_EXPLORABLES_ROOMS,     10);

        // Get room types
        $locked_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findLocked();
        $unlocked_room_types = $this->entity_manager->getRepository(RuinZonePrototype::class)->findUnlocked();

        while ($room_count > 0 && !empty($room_candidates)) {
            /** @var RuinZone $room_corridor */
            $room_corridor = $this->random->pick( $room_candidates, 1 );
            $far = $room_corridor->getDistance() > $lock_distance;

            // Determine possible locations for the door
            $valid_locations = [];
            if ( $room_corridor->hasCorridor( RuinZone::CORRIDOR_E )) $valid_locations = array_merge($valid_locations, [3,8]); else $valid_locations[] = 5;
            if ( $room_corridor->hasCorridor( RuinZone::CORRIDOR_W )) $valid_locations = array_merge($valid_locations, [1,6]); else $valid_locations[] = 4;
            if (!$room_corridor->hasCorridor( RuinZone::CORRIDOR_N )) $valid_locations[] = 2;
            if (!$room_corridor->hasCorridor( RuinZone::CORRIDOR_S )) $valid_locations[] = 7;

            $room_corridor
                ->setRoomDistance(0)
                ->setLocked( $far )
                ->setRoomFloor( (new Inventory())->setRuinZoneRoom( $room_corridor ) )
                ->setPrototype( $this->random->pick( $far ? $locked_room_types : $unlocked_room_types ) )
                ->setDoorPosition( $this->random->pick( $valid_locations ) );

            $this->dykstra($cache,
                function (RuinZone $r): int { return $r->getRoomDistance(); },
                function (RuinZone $r, int $i): void { $r->setRoomDistance( $i ); }
            );

            $room_count--;
            $room_candidates = array_filter( $room_candidates, function(RuinZone $r) use ($room_dist) {
                return $r->getRoomDistance() >= $room_dist;
            } );
        }

        // Place decals
        foreach ($cache as $x => $line)
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

                $ruinZone->setDecals( $ruinZone->getDecals() & (~$decal_filter) );
            }

        $this->populateMaze( $base, 25 );
    }

    /**
     * @param Zone $base
     * @param int $zeds
     * @param bool|false $reposition
     * @param bool $clear_bodies
     * @param RuinZone[] $skip_zone
     */
    public function populateMaze( Zone $base, int $zeds, bool $reposition = false, bool $clear_bodies = true, array $skip_zone = [] ) {
        /** @var RuinZone[] $ruinZones */
        $ruinZones = $base->getRuinZones()->getValues();
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