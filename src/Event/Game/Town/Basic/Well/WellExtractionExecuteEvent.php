<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read WellExtractionExecuteData $data
 * @mixin WellExtractionExecuteData
 */
class WellExtractionExecuteEvent extends GameInteractionEvent {
    protected static function configuration(): string { return WellExtractionExecuteData::class; }
}