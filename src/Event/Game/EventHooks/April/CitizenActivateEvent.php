<?php

namespace App\Event\Game\EventHooks\April;

use App\Event\Game\EventHooks\Common\CitizenToggleData;
use App\Event\Game\EventHooks\Common\CitizenToggleEvent;

/**
 * @property-read CitizenToggleData $data
 * @mixin CitizenToggleData
 */
class CitizenActivateEvent extends CitizenToggleEvent {
    protected static function configuration(): string { return CitizenToggleData::class; }
}