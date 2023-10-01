<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Enum\EventStages\BuildingValueQuery;

class BuildingQueryTownParameterData
{

    /**
     * @param BuildingValueQuery $query
     * @return BuildingQueryTownParameterData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( BuildingValueQuery $query ): void {
        $this->query = $query;
    }

    public readonly BuildingValueQuery $query;

    public float|int $value = 0;
}