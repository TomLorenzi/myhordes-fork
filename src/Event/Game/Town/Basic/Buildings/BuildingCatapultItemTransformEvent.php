<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingCatapultItemTransformData $data
 * @mixin BuildingCatapultItemTransformData
 */
class BuildingCatapultItemTransformEvent extends GameEvent {
    protected static function configuration(): string { return BuildingCatapultItemTransformData::class; }
}