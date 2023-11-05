<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\GameEvent;

/**
 * @property-read TownToggleData $data
 * @mixin TownToggleData
 */
class TownToggleEvent extends GameEvent {
    protected static function configuration(): string { return TownToggleData::class; }
}