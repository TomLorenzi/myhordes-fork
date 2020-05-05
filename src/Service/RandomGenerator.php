<?php


namespace App\Service;


use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\Result;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Interfaces\RandomEntry;
use App\Interfaces\RandomGroup;
use App\Structures\BetweenFilter;

class RandomGenerator
{

    function chance(float $c): bool {
        if ($c >= 1.0)     return true;
        elseif ($c <= 0.0) return false;
        return mt_rand(0,99) < (100.0*$c);
    }

    /**
     * @param array $a
     * @param int $num
     * @param bool $force_array
     * @return mixed|array|null
     */
    function pick( array $a, int $num = 1, bool $force_array = false ) {
        if     ($num <=  0 || empty($a)) return $force_array ? [] : null;
        elseif ($num === 1) return $force_array ? [$a[ array_rand($a, 1) ]] : $a[ array_rand($a, 1) ];
        else return array_map( function($k) use (&$a) { return $a[$k]; }, array_rand( $a, min($num,count($a)) ) );
    }

    /**
     * @param RandomEntry[] $g
     * @return RandomEntry|null
     */
    function pickEntryFromRandomArray( array $g ): ?RandomEntry {
        if (empty($g)) return null;
        $sum = 0;
        foreach ( $g as $entry )
            $sum += abs($entry->getChance());
        if ($sum === 0) {
            /** @var RandomEntry $pe */
            $pe = $this->pick( $g );
            return $pe;
        }
        $random = mt_rand(0,$sum-1);
        $sum = 0;
        foreach ( $g as $entry ) {
            $sum += abs($entry->getChance());
            if ($sum > $random) return $entry;
        }
        return $g[array_key_last($g)];
    }

    function pickEntryFromRandomGroup( RandomGroup $g ): ?RandomEntry {
        return $this->pickEntryFromRandomArray( $g->getEntries()->getValues() );
    }

    function pickItemPrototypeFromGroup(ItemGroup $g): ?ItemPrototype {
        /** @var ItemGroupEntry|null $result */
        $result = $this->pickEntryFromRandomGroup($g);
        return $result ? $result->getPrototype() : null;
    }

    /**
     * @param AffectResultGroup $g
     * @return Result[]
     */
    function pickResultsFromGroup(AffectResultGroup $g): ?array {
        /** @var AffectResultGroupEntry|null $result */
        $result = $this->pickEntryFromRandomGroup($g);
        return $result ? $result->getResults()->getValues() : null;
    }

    /**
     * @param ZonePrototype[] $g
     * @return ZonePrototype|null
     */
    function pickLocationFromList( array $g ): ?ZonePrototype {
        /** @var ZonePrototype|null $r */
        $r = $this->pickEntryFromRandomArray( $g );
        return $r;
    }

    // Get a random distance from normal distribution
    function getRandomDistance(int $min, int $max): int {
        $range = $max - $min + 1;
        $mean = $min + $range / 2;
        $sd = $range / 8;
        $x = mt_rand() / mt_getrandmax();
        $y = mt_rand() / mt_getrandmax();
        $rd = sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sd + $mean;
        return $rd;
    }
    
    function pickLocationBetweenFromList(array $g, int $min, int $max): ?Zone {
        $rd = $this->getRandomDistance($min, $max);

        /** @var Zone[] $dist_zone_list */
        $dist_zone_list = array_filter($g, new BetweenFilter(floor($rd), ceil($rd)));
        shuffle($dist_zone_list);

        return $dist_zone_list[0];
    }

}