<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingUpgradeData $data
 * @mixin BuildingUpgradeData
 */
abstract class BuildingUpgradeEvent extends GameEvent {
    protected static function configuration(): string { return BuildingUpgradeData::class; }
}