<?php


namespace App\Service;

use App\Entity\AdminBan;
use App\Entity\AdminDeletion;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\Forum;
use App\Entity\Inventory;
use App\Entity\Picto;
use App\Entity\Post;
use App\Entity\RuinZone;
use App\Entity\Thread;
use App\Entity\User;
use App\Entity\Zone;
use App\Service\DeathHandler;
use App\Structures\TownConf;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function generateMaze( Zone $base ) {

        /** @var RuinZone[][] $cache */
        $cache = []; $binary = []; $n = 0;
        foreach ($base->getRuinZones() as $ruinZone) {
            if (!isset($cache[$ruinZone->getX()])) $cache[$ruinZone->getX()] = [];
            if (!isset($cache[$ruinZone->getX()][$ruinZone->getY()])) {
                $cache[$ruinZone->getX()][$ruinZone->getY()] = $ruinZone->setCorridor(RuinZone::CORRIDOR_NONE);
                $binary[$ruinZone->getX()][$ruinZone->getY()] = false;
                $n++;
            }
        }

        $binary[0][0] = true;
        $binary[0][1] = true;

        $intersections = ['0/1' => [0,1]];

        //$cache[0][0]->setCorridor( RuinZone::CORRIDOR_S );
        //$cache[0][1]->setCorridor( RuinZone::CORRIDOR_N );

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
                (!$corridor($x+1,$y) || !$corridor($x,$y+1) || !$corridor($x-1,$y+1));
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

        $conf =  $this->conf->getTownConfiguration( $base->getTown() );
        $complexity = $conf->get(TownConf::CONF_EXPLORABLES_COMPLEXITY, 0.5);
        $convolution = $conf->get(TownConf::CONF_EXPLORABLES_CONVOLUTION, 0.75);

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

        // Build the actual map
        foreach ($cache as $x => &$line)
            foreach ($line as $y => &$ruinZone)
                if ($corridor($x,$y)) {

                    if ($corridor($x+1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_E );
                    if ($corridor($x-1,$y)) $ruinZone->addCorridor( RuinZone::CORRIDOR_W );
                    if ($corridor($x,$y+1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_S );
                    if ($corridor($x,$y-1)) $ruinZone->addCorridor( RuinZone::CORRIDOR_N );

                } else $ruinZone->setCorridor(RuinZone::CORRIDOR_NONE);
    }
}