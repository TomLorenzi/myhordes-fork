<?php

namespace App\Event\Game\EventHooks\Common;

use App\Entity\Citizen;
use App\Event\Game\SingleValue;

class CitizenToggleData
{
    use SingleValue;

    public readonly Citizen $citizen;

    /**
     * @return CitizenToggleEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(Citizen $citizen): void {
        $this->citizen = $citizen;
    }
}