<?php

namespace App\Event\Game\Town\Addon\Dump;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read DumpInsertionExecuteData $data
 * @mixin DumpInsertionExecuteData
 */
class DumpInsertionExecuteEvent extends GameInteractionEvent {

    protected static function configuration(): string { return DumpInsertionExecuteData::class; }
}