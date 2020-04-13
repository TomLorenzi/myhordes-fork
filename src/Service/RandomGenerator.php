<?php


namespace App\Service;


use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\Result;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\WellCounter;
use App\Entity\ZonePrototype;
use App\Interfaces\RandomEntry;
use App\Interfaces\RandomGroup;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

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

}