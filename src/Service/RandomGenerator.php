<?php


namespace App\Service;


use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\Citizen;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\Result;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Interfaces\RandomEntry;
use App\Interfaces\RandomGroup;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\TownHandler;
use App\Structures\PropertyFilter;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;

class RandomGenerator
{
    private EntityManagerInterface $em;

    function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    function chance(float $c): bool {
        if ($c >= 1.0)     return true;
        elseif ($c <= 0.0) return false;
        return mt_rand(0,99) < (100.0*$c);
    }

    /**
     * Randomly selects N elements from an array
     * @param array $a
     * @param int $num
     * @param bool $force_array
     * @return mixed|array|null
     */
    function pick( array $a, int $num = 1, bool $force_array = false ): mixed {
        if     ($num <=  0 || empty($a)) return $force_array ? [] : null;
        elseif ($num === 1) return $force_array ? [$a[ array_rand($a, 1) ]] : $a[ array_rand($a, 1) ];
        elseif (count($a) === 1) return array_values($a);
        else return array_map( function($k) use (&$a) { return $a[$k]; }, array_rand( $a, min($num,count($a)) ) );
    }

    /**
     * Randomly selects N elements from an array and removes these elements from the array.
     * @param array $a
     * @param int $num
     * @param bool $force_array
     * @return mixed|array|null
     */
    function draw( array &$a, int $num = 1, bool $force_array = false ): mixed {
        $pick = $this->pick( $a, $num, $force_array );
        foreach ((is_array($pick) ? $pick : [$pick]) as $picked) {
            $index = array_search( $picked, $a, true );
            if ($index !== false) unset($a[$index]);
        }
        return $pick;
    }

    function pickEntryFromRawRandomArray( array $g ) {
        if (empty($g)) return null;
        $sum = 0;
        foreach ( $g as $entry )
            $sum += abs($entry[1]);
        if ($sum === 0) {
            $pe = $this->pick( $g );
            return $pe[0];
        }
        $random = mt_rand(0,$sum-1);
        $sum = 0;
        foreach ( $g as $entry ) {
            $sum += abs($entry[1]);
            if ($sum > $random) return $entry[0];
        }
        return $g[array_key_last($g)][0];
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

    function pickItemPrototypeFromGroup(ItemGroup $g, ?TownConf $tc = null): ?ItemPrototype {
        if ($tc && $g->getName() && ($replace = $tc->getSubKey(TownConf::CONF_OVERRIDE_ITEM_GROUP, $g->getName())) )
            return $this->pickItemPrototypeFromGroup( $this->em->getRepository(ItemGroup::class)->findOneByName($replace), $tc );

        /** @var ItemGroupEntry|null $result */
        $result = $this->pickEntryFromRandomGroup($g);
        return $result?->getPrototype();
    }

    function resolveChance( $group, $principal ): float {
        $chance = 0.0; $sum = 0.0;

        if (!$group) return 0.0;

        if (is_object($group) && is_a( $group, RandomGroup::class )) $group = $group->getEntries()->getValues();
        if (is_array($group)) {
            foreach ( $group as $entry ) {
                $sum += abs($entry->getChance());
                if ( is_a( $principal, ItemPrototype::class ) && is_a( $entry, ItemGroupEntry::class ) && $entry->getPrototype() === $principal )
                    $chance += $entry->getChance();
                if ( is_a( $principal, ZonePrototype::class ) && $entry === $principal )
                    $chance += $entry->getChance();
            }
        } else return 0;

        if ($chance <= 0) return 0;
        if ($sum === 0) return 1.0/(float)count($group);
        else return $chance/$sum;
    }

    /**
     * @param AffectResultGroup $g
     * @return Result[]|null
     */
    function pickResultsFromGroup(AffectResultGroup $g): ?array {
        /** @var AffectResultGroupEntry|null $result */
        $result = $this->pickEntryFromRandomGroup($g);
        return $result?->getResults()->getValues();
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

    function pickLocationBetweenFromList(array $zone_list, int $min, int $max, array $options = []): ?Zone {
        $zone_list = array_filter( $zone_list, function (Zone $z) use ($min,$max) {
            $d = $z->getDistance();
            return $d >= $min && $d <= $max;
        } );

        if (count($options) > 0)
            $zone_list = array_filter($zone_list, new PropertyFilter($options));

        shuffle($zone_list);

        return $zone_list[0] ?? null;
    }

    function getFactor(Citizen $citizen, CitizenHandler $citizenHandler, TownHandler $townHandler, InventoryHandler $inventoryHandler, Zone $zone, TownConf $conf): float {
        $time = new DateTime();
        $factor = 1.0;
        if ($citizen->getProfession()->getName() === 'collec') $factor += 0.2; // based on 769 search made as scavenger
        if ($citizenHandler->hasStatusEffect( $citizen, 'camper' )) $factor += 0.1; // if we use gathered stats, this value should be around 0.15
        if ($citizenHandler->hasStatusEffect( $citizen, 'wound5' )) $factor -= 0.5; // based on 30 searchs made with eye injury
        if ($citizenHandler->hasStatusEffect( $citizen, 'drunk'  )) $factor -= 0.2; // based on 51 search made while being drunk

        if ($conf->isNightMode($time)) {

            // If there are items that prevent night mode present, the night malus is set to 0
            $night_mode_malue = ($inventoryHandler->countSpecificItems($zone->getFloor(), 'prevent_night', true) == 0) ? 0.25 : 0.0; // based on 733 searchs made during night

            if ($citizen->hasStatus('tg_novlamps')) {
                // Night mode is active, but so are the Novelty Lamps; we must check if they apply
                $novelty_lamps = $townHandler->getBuilding( $citizen->getTown(), 'small_novlamps_#00', true );

                // Novelty Lamps are not built; apply malus
                if (!$novelty_lamps) $factor -= $night_mode_malue;
                // Novelty Lamps are at lv0 and the zone distance is above 2km; apply malus
                elseif ($novelty_lamps->getLevel() === 0 && $zone->getDistance() > 2) $factor -= $night_mode_malue;
                // Novelty Lamps are at lv1 and the zone distance is above 6km; apply malus
                elseif ($novelty_lamps->getLevel() === 1 && $zone->getDistance() > 6) $factor -= $night_mode_malue;
                // Novelty Lamps are at lv2 and the zone distance is above 10km; apply malus
                elseif ($novelty_lamps->getLevel() === 2 && $zone->getDistance() > 999) $factor -= $night_mode_malue;
                // Novelty Lamps are at lv4 and the zone distance is within 10km; apply bonus
                // elseif ($novelty_lamps->getLevel() === 4 && $zone->getDistance() <= 10) $factor += 0.2;

            } else $factor -= $night_mode_malue; // Night mode is active; apply malus

        }

        return $factor;
    }

}