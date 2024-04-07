<?php

namespace App\Event\Common\Messages\Announcement;

use App\Entity\Announcement;

readonly class NewAnnouncementData
{
    public Announcement $announcement;

    /**
     * @param Announcement $announcement
     * @return NewAnnouncementEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Announcement $announcement ): void {
        $this->announcement = $announcement;
    }
}