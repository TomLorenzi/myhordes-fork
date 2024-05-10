<?php

namespace App\Event\Common\Messages\GlobalPrivateMessage;

use App\Event\Event;

/**
 * @property-read GPDirectMessageData $data
 * @mixin GPDirectMessageData
 */
abstract class GPDirectMessageEvent extends Event {
    protected static function configuration(): string { return GPDirectMessageData::class; }
}