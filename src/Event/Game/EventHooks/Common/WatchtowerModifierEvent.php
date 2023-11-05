<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\GameEvent;

/**
 * @property-read WatchtowerModifierData $data
 * @mixin WatchtowerModifierData
 */
class WatchtowerModifierEvent extends GameEvent {
    protected static function configuration(): string { return WatchtowerModifierData::class; }
}