<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\CitizenStatus;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\StatusRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessStatusRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|StatusRequirement $data): bool
    {
        if ($data->shunned !== null && $cache->citizen->getBanished() !== $data->shunned) return false;

        $stati = array_map( fn(CitizenStatus $s) => $s->getName(), $cache->citizen->getStatus()->toArray() );
        $needed_status = $data->getNeededStatus();
        $forbidden_status = $data->getForbiddenStatus();
        foreach ($needed_status as $status) if (!in_array( $status, $stati )) return false;
        foreach ($forbidden_status as $status) if (in_array( $status, $stati )) return false;

        return true;
    }
}