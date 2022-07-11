<?php

namespace App\Enum;

enum GameProfileEntryType: int {

    case TownCreated = 1;
    case TownEnded   = 2;

    case CitizenJoined = 3;
    case CitizenProfessionSelected = 4;
    case CitizenDied = 5;
    case CitizenProfessionChanged = 6;

    case BuildingDiscovered = 7;
    case BuildingConstructionInvested = 8;
    case BuildingConstructed = 9;
    case BuildingRepairInvested = 10;
    case BuildingCollapsed = 11;
    case BuildingDamaged = 12;
    case BuildingDestroyed = 13;

    case RecipeExecuted = 14;

    case ItemFound = 15;
    case DigFailed = 16;
    case EventItemFound = 17;
    case RegularItemFound = 18;

    case BeyondLostHood = 19;

    public static function latest_version(): int {
        return 2;
    }

    public function version(): int {
        if ( $this->value <= 15 ) return 1;
        else if ($this->value <= 19) return 2;
        else return PHP_INT_MAX;
    }


}