<?php

namespace App\Event\Game\Citizen;

use App\Entity\Citizen;
use App\Enum\EventStages\CitizenValueQuery;

class CitizenQueryParameterData extends CitizenBaseData
{

    /**
     * @param Citizen $citizen
     * @param CitizenValueQuery $query
     * @param mixed|null $arg
     * @return CitizenQueryParameterData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Citizen $citizen, CitizenValueQuery $query = null, mixed $arg = null ): void {
        parent::setup($citizen);
        $this->query = $query;
        $this->arg = $arg;
    }

    public readonly CitizenValueQuery $query;
    public readonly mixed $arg;

    public float|int $value = 0;
}