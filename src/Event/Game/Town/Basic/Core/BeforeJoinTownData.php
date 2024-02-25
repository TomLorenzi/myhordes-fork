<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Entity\User;

class BeforeJoinTownData
{
    readonly public User $subject;
    readonly public bool $auto;
    public bool $shoutbox_clean_needed = false;

    /**
     * @param User $subject
     * @param bool $auto
     * @return BeforeJoinTownEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( User $subject, bool $auto ): void {
        $this->subject = $subject;
        $this->auto = $auto;
    }
}