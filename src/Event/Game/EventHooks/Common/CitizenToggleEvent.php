<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenToggleData $data
 * @mixin CitizenToggleData
 */
class CitizenToggleEvent extends GameEvent {
    protected static function configuration(): string { return CitizenToggleData::class; }
}