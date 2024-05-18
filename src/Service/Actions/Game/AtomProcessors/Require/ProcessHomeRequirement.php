<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\CitizenHomeUpgrade;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\HomeRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessHomeRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|HomeRequirement $data): bool
    {
        $relevant_level = $data->upgrade === null
            ? $cache->citizen->getHome()->getPrototype()->getLevel()
            : $cache->em
                ->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($cache->citizen->getHome(), $data->upgrade)
                ?->getLevel() ?? 0;

        if ($data->minLevel !== null && $relevant_level < $data->minLevel) return false;
        if ($data->maxLevel !== null && $relevant_level > $data->maxLevel) return false;

        return true;
    }
}