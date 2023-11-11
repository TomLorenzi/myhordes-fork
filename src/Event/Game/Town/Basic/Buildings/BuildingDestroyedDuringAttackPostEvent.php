<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingDestructionData $data
 * @mixin BuildingDestructionData
 */
class BuildingDestroyedDuringAttackPostEvent extends GameEvent {
    protected static function configuration(): string { return BuildingDestructionData::class; }
}