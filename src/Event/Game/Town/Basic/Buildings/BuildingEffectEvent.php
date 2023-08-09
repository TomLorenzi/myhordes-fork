<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingEffectData $data
 * @mixin BuildingEffectData
 */
abstract class BuildingEffectEvent extends GameEvent {
    protected static function configuration(): string { return BuildingEffectData::class; }
}