<?php

namespace App\Event\Common\Messages\GlobalPrivateMessage;

use App\Event\Event;

/**
 * @property-read GPMessageData $data
 * @mixin GPMessageData
 */
abstract class GPMessageEvent extends Event {
    protected static function configuration(): string { return GPMessageData::class; }
}