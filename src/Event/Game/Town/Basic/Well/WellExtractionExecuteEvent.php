<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Event\Game\GameInteractionEvent;
use App\Event\Game\TestEventTrait;

/**
 * @property-read WellExtractionExecuteData $data
 * @mixin WellExtractionExecuteData
 */
class WellExtractionExecuteEvent extends GameInteractionEvent {

    protected static function configuration(): string { return WellExtractionExecuteData::class; }
}