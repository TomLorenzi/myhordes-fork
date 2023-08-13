<?php

namespace App\Event\Game\Citizen;


use App\Entity\Citizen;

class CitizenBaseData
{

    /**
     * @param Citizen $citizen
     * @return CitizenBaseData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Citizen $citizen ): void {
        $this->citizen = $citizen;
    }

    public readonly Citizen $citizen;
}