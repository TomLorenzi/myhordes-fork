<?php

namespace App\Event\Game\Town\Addon\Dump;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read DumpInsertionCheckData $data
 * @mixin DumpInsertionCheckData
 */
class DumpInsertionCheckEvent extends GameInteractionEvent {
    protected static function configuration(): string { return DumpInsertionCheckData::class; }
}