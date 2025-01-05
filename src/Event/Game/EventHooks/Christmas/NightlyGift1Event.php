<?php

namespace App\Event\Game\EventHooks\Christmas;

use App\Event\Game\EmptyEventData;
use App\Event\Game\GameEvent;

/**
 * @property-read EmptyEventData $data
 * @mixin EmptyEventData
 */
class NightlyGift1Event extends GameEvent {
    protected static function configuration(): string { return EmptyEventData::class; }
}