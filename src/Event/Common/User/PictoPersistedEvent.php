<?php

namespace App\Event\Common\User;

use App\Event\Event;

/**
 * @property-read PictoPersistedData $data
 * @mixin PictoPersistedData
 */
class PictoPersistedEvent extends Event {
    protected static function configuration(): string { return PictoPersistedData::class; }
}