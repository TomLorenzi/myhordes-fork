<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Event\Game\GameEvent;

/**
 * @property-read AfterJoinTownData $data
 * @mixin AfterJoinTownData
 */
class AfterJoinTownEvent extends GameEvent {
    protected static function configuration(): string { return AfterJoinTownData::class; }
}