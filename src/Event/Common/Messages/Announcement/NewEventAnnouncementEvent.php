<?php

namespace App\Event\Common\Messages\Announcement;

use App\Event\Event;

/**
 * @property-read NewEventAnnouncementData $data
 * @mixin NewEventAnnouncementData
 */
class NewEventAnnouncementEvent extends Event {
    protected static function configuration(): string { return NewEventAnnouncementData::class; }
}