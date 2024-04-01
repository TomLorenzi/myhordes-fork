<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Entity\Citizen;
use App\Entity\User;

class JoinTownData
{
    public readonly User $subject;
    public readonly bool $auto;
    public Citizen $citizen;

    /**
     * @param User $subject
     * @param bool $auto
     * @return JoinTownEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( User $subject, bool $auto ): void {
        $this->subject = $subject;
        $this->auto = $auto;
    }
}