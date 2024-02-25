<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Event\Game\GameEvent;

/**
 * @property-read JoinTownData $data
 * @mixin JoinTownData
 */
class JoinTownEvent extends GameEvent {
    protected static function configuration(): string { return JoinTownData::class; }
}