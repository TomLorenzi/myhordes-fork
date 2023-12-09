<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Enum\EventStages\BuildingValueQuery;

class BuildingQueryTownParameterData
{

    /**
     * @param BuildingValueQuery $query
     * @param mixed|null $arg
     * @return BuildingQueryTownParameterData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( BuildingValueQuery $query, mixed $arg = null ): void {
        $this->query = $query;
        $this->arg = $arg;
    }

    public readonly BuildingValueQuery $query;
    public readonly mixed $arg;

    public float|int $value = 0;
}