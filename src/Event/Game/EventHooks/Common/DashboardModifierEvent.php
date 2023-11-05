<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\GameEvent;

/**
 * @property-read DashboardModifierData $data
 * @mixin DashboardModifierData
 */
class DashboardModifierEvent extends GameEvent {
    protected static function configuration(): string { return DashboardModifierData::class; }
}