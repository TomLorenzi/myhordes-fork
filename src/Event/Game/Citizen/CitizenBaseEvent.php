<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenBaseData $data
 * @mixin CitizenBaseData
 */
abstract class CitizenBaseEvent extends GameEvent {
    protected static function configuration(): string { return CitizenBaseData::class; }
}