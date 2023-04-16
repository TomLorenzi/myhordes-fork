<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read WellInsertionCheckData $data
 * @mixin WellInsertionCheckData
 */
class WellInsertionCheckEvent extends GameInteractionEvent {
    protected static function configuration(): string { return WellInsertionCheckData::class; }
}