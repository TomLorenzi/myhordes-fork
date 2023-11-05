<?php

namespace App\Event\Game\EventHooks\Easter;

use App\Event\Game\EventHooks\Common\TownToggleData;
use App\Event\Game\EventHooks\Common\TownToggleEvent;

/**
 * @property-read TownToggleData $data
 * @mixin TownToggleData
 */
class TownDeactivateEvent extends TownToggleEvent {
    protected static function configuration(): string { return TownToggleData::class; }
}