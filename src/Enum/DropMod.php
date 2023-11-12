<?php

namespace App\Enum;

enum DropMod: int
{
    case None = 0;
    case Camp = 1;
    case NightMode = 2;
    case Ghouls = 3;
    case Anzac = 4;
    case HordesS6 = 5;
    case Infective = 51;
    case EventEaster = 101;
    case EventChristmas = 102;
    case EventChristmasAlt1 = 1021;
    case EventChristmasAlt2 = 1022;
    case EventStPatrick = 103;
    case EventHalloween = 104;

    case RegionalDV = 201;

    public function isDefaultMod(): bool {
        return match ($this) {
            self::None, self::Camp, self::NightMode, self::Ghouls, self::Anzac, self::HordesS6, self::RegionalDV => true,
            default => false
        };
    }

    public static function defaultMods(): array {
        return array_filter( self::cases(), fn(self $c) => $c->isDefaultMod() );
    }
}
