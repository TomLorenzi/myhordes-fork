<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingQueryTownParameterData $data
 * @mixin BuildingQueryTownParameterData
 */
class BuildingQueryTownParameterEvent extends GameEvent {
    protected static function configuration(): string { return BuildingQueryTownParameterData::class; }
}