<?php

namespace App\Event\Game\Town\Addon\Dump;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read DumpRetrieveExecuteData $data
 * @mixin DumpRetrieveExecuteData
 */
class DumpRetrieveExecuteEvent extends GameInteractionEvent {

    protected static function configuration(): string { return DumpRetrieveExecuteData::class; }
}