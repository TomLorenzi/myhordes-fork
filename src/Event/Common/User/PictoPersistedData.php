<?php

namespace App\Event\Common\User;

use App\Entity\Season;
use App\Entity\User;

readonly class PictoPersistedData
{
    public User $user;
    public ?Season $season;
    public ?bool $old;
    public ?bool $imported;

    /**
     * @param User $user
     * @param Season|null $season
     * @param bool|null $old
     * @param bool|null $imported
     * @return PictoPersistedEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( User $user, ?Season $season = null, ?bool $old = null, ?bool $imported = null ): void {
        $this->user = $user;
        $this->season = $season;
        $this->old = $old;
        $this->imported = $imported;
    }
}