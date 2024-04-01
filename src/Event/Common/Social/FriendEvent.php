<?php

namespace App\Event\Common\Social;

use App\Event\Event;

/**
 * @property-read FriendData $data
 * @mixin FriendData
 */
class FriendEvent extends Event {
    protected static function configuration(): string { return FriendData::class; }
}