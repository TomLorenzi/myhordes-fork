<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Entity\WellCounter;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;

class RandomGenerator
{

    function chance(float $c): bool {
        if ($c >= 1.0)     return true;
        elseif ($c <= 0.0) return false;
        return mt_rand(0,100) <= (100*$c);
    }

    /**
     * @param array $a
     * @param int $num
     * @return mixed|array|null
     */
    function pick( array $a, int $num = 1 ) {
        if     ($num <=  0) return null;
        elseif ($num === 1) return $a[ array_rand($a, 1) ];
        else return array_map( function($k) use (&$a) { return $a[$k]; }, array_rand( $a, $num ) );
    }

}