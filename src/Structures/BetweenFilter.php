<?php

namespace App\Structures;

class BetweenFilter {
    private $min;
    private $max;

    function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }
    function isBetween($i) {
        $d = round(sqrt( pow($i->getX(),2) + pow($i->getY(),2) ));
        return $d >= $this->min && $d <= $this->max;
    }
    function __invoke($i) {
        return $this->isBetween($i);
    }
}