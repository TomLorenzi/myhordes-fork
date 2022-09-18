<?php

namespace App\Enum;

enum ZoneActivityMarkerType: int {
    case ShamanRain = 1;
    case RuinDig = 2;
    case ScoutVisit = 3;

    public function daily(): bool {
        return $this !== self::ScoutVisit;
    }

    public static function daylies(): array {
        return array_filter( self::cases(), fn(ZoneActivityMarkerType $t) => $t->daily() );
    }
}