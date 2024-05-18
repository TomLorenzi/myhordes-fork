<?php

namespace App\Enum\ActionHandler;

enum CountType: int {
    case Well = 1;
    case Zombies = 2;
    case Kills = 3;
    case Bury = 4;
    case Items = 5;

    public function variable(): string {
        return match ($this) {
            self::Well => 'well',
            self::Zombies => 'zombies',
            self::Kills => 'kills',
            self::Bury => 'bury_count',
            self::Items => 'items_count'
        };
    }
}