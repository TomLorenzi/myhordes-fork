<?php

namespace App\Hooks;

use App\Entity\Citizen;

class ClassHooks extends HooksCore {
    function hookAdditionalCitizenRowClass(array $args): string {
        /** @var Citizen $citizen */
        $citizen = $args[0];
        $cnum = $citizen->getUser()->getId() % 10;
        return $citizen->getHome()->getChest()->hasAnyItem('xmas_gift_#01') ? "crows-garland crows-garland-alt-$cnum" : '';
    }
}