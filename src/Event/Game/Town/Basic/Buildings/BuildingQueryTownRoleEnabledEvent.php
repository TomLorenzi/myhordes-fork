<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingQueryTownRoleEnabledData $data
 * @mixin BuildingQueryTownRoleEnabledData
 */
class BuildingQueryTownRoleEnabledEvent extends GameEvent {
    protected static function configuration(): string { return BuildingQueryTownRoleEnabledData::class; }
}