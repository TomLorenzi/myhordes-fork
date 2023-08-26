<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingConstructionData $data
 * @mixin BuildingConstructionData
 */
class BuildingConstructionEvent extends GameEvent {
    protected static function configuration(): string { return BuildingConstructionData::class; }
}