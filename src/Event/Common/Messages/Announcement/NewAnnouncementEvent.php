<?php

namespace App\Event\Common\Messages\Announcement;

use App\Event\Event;

/**
 * @property-read NewAnnouncementData $data
 * @mixin NewAnnouncementData
 */
class NewAnnouncementEvent extends Event {
    protected static function configuration(): string { return NewAnnouncementData::class; }
}