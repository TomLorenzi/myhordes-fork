<?php

namespace App\Event\Common\Messages\Announcement;

use App\Entity\CommunityEvent;

readonly class NewEventAnnouncementData
{
    public CommunityEvent $communityEvent;

    /**
     * @param CommunityEvent $communityEvent
     * @return NewAnnouncementEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( CommunityEvent $communityEvent ): void {
        $this->communityEvent = $communityEvent;
    }
}