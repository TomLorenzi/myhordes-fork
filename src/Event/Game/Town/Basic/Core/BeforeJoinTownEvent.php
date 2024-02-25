<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Event\Game\GameEvent;

/**
 * @property-read BeforeJoinTownData $data
 * @mixin BeforeJoinTownData
 */
class BeforeJoinTownEvent extends GameEvent {
    protected static function configuration(): string { return BeforeJoinTownData::class; }
}