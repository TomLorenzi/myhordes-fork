<?php

namespace App\Event\Common\Messages\Forum;

use App\Event\Event;

/**
 * @property-read ForumMessageData $data
 * @mixin ForumMessageData
 */
abstract class ForumMessageEvent extends Event {
    protected static function configuration(): string { return ForumMessageData::class; }
}