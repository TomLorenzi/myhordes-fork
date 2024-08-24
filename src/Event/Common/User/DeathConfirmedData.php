<?php

namespace App\Event\Common\User;

use App\Entity\CitizenRankingProxy;
use App\Entity\User;

class DeathConfirmedData
{
    public readonly User $user;
    public readonly CitizenRankingProxy $death;
    public string $lastWords;

    /**
     * @param User $user
     * @param CitizenRankingProxy $death
     * @param string $lastWords
     * @return DeathConfirmedEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( User $user, CitizenRankingProxy $death, string $lastWords ): void {
        $this->user = $user;
        $this->death = $death;
        $this->lastWords = $lastWords;
    }
}