<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read WellInsertionExecuteData $data
 * @mixin WellInsertionExecuteData
 */
class WellInsertionExecuteEvent extends GameInteractionEvent {

    protected static function configuration(): string { return WellInsertionExecuteData::class; }
}