<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\EmptyEventData;
use App\Event\Game\GameEvent;

/**
 * @property-read EmptyEventData $data
 * @mixin EmptyEventData
 */
class AutoDoorEvent extends GameEvent {
    protected static function configuration(): string { return EmptyEventData::class; }
}