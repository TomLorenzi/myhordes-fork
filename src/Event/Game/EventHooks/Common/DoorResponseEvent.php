<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\GameEvent;

/**
 * @property-read DoorResponseData $data
 * @mixin DoorResponseData
 */
class DoorResponseEvent extends GameEvent {
    protected static function configuration(): string { return DoorResponseData::class; }
}