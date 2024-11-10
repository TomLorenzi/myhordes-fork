<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingAddonProviderData $data
 * @mixin BuildingAddonProviderData
 */
class BuildingAddonProviderEvent extends GameEvent {
    protected static function configuration(): string { return BuildingAddonProviderData::class; }
}