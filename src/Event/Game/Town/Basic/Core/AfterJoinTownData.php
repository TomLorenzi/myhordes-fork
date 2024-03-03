<?php

namespace App\Event\Game\Town\Basic\Core;

use App\Entity\Citizen;

readonly class AfterJoinTownData
{
    public BeforeJoinTownData $before;

    /**
     * @param BeforeJoinTownData $before
     * @return AfterJoinTownEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( BeforeJoinTownData $before ): void {
        $this->before = $before;
    }


}