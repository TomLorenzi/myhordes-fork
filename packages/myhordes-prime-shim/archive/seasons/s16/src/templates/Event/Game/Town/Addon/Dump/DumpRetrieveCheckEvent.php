<?php

namespace MyHordes\Prime\Event\Game\Town\Addon\Dump;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read DumpRetrieveCheckData $data
 * @mixin DumpRetrieveCheckData
 */
class DumpRetrieveCheckEvent extends GameInteractionEvent {
    protected static function configuration(): string { return DumpRetrieveCheckData::class; }
}