<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\Building;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\BuildingRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessBuildingRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|BuildingRequirement $data): bool
    {
        $buildings = array_map( fn(Building $b) => $b->getPrototype()->getName(), array_filter($cache->citizen->getTown()->getBuildings()->toArray(), fn(Building $b) => $b->getComplete() ) );
        $needed_buildings = $data->getNeededBuildings();
        $forbidden_buildings = $data->getForbiddenBuildings();
        foreach ($needed_buildings as $building) if (!in_array( $building, $buildings )) return false;
        foreach ($forbidden_buildings as $building) if (in_array( $building, $buildings )) return false;

        return true;
    }
}