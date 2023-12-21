<?php

namespace App\Enum\EventStages;

use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreDefaultEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreUpgradeEvent;

enum BuildingValueQuery {
    case GuardianDefenseBonus;
    case NightWatcherCap;
    case NightWatcherWeaponsAllowed;
    case TownDoorOpeningCost;
    case TownDoorClosingCost;
    case MissingItemDefenseLoss;
    case ConstructionAPRatio;
    case RepairAPRatio;
    case OverallTownDefenseScale;
    case NightlyZoneDiscoveryRadius;
    case NightlyZoneRecoveryChance;
    case NightlyRecordWindDirection;
    case NightlyRedSoulPenalty;
    case MaxItemDefense;
    case ScoutMarkingsEnabled;
    case BeyondTeleportRadius;

    case MaxActiveZombies;
}