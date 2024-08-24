<?php

namespace App\Event\Common\User;

use App\Event\Event;

/**
 * @property-read DeathConfirmedData $data
 * @mixin DeathConfirmedData
 */
abstract class DeathConfirmedEvent extends Event {
    protected static function configuration(): string { return DeathConfirmedData::class; }
}