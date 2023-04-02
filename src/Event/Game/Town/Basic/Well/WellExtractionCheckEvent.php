<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read WellExtractionCheckData $data
 * @mixin WellExtractionCheckData
 */
class WellExtractionCheckEvent extends GameInteractionEvent {
    protected static function configuration(): string { return WellExtractionCheckData::class; }
}