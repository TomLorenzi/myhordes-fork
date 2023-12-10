<?php

namespace App\Event\Common\Social;


use App\Entity\User;

readonly class FriendData
{
    public User $actor;
    public User $subject;

    public bool $added;

    /**
     * @param bool $added
     * @param User $actor
     * @param User $subject
     * @return FriendEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( bool $added, User $actor, User $subject ): void {
        $this->added = $added;
        $this->actor = $actor;
        $this->subject = $subject;
    }
}