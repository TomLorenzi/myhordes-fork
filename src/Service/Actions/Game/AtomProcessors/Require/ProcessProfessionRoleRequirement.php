<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\CitizenRole;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ProfessionRoleRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessProfessionRoleRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|ProfessionRoleRequirement $data): bool
    {
        if ($data->hero !== null && $cache->citizen->getProfession()->getHeroic() !== $data->hero) return false;

        $needed_jobs = $data->getNeededJobs();
        $forbidden_jobs = $data->getForbiddenJobs();
        if (!empty($needed_jobs) && !in_array( $cache->citizen->getProfession()->getName(), $needed_jobs )) return false;
        if (!empty($forbidden_jobs) && in_array( $cache->citizen->getProfession()->getName(), $forbidden_jobs )) return false;

        $roles = array_map( fn(CitizenRole $r) => $r->getName(), $cache->citizen->getRoles()->toArray() );
        $needed_roles = $data->getNeededRoles();
        $forbidden_roles = $data->getForbiddenRoles();
        foreach ($needed_roles as $role) if (!in_array( $role, $roles )) return false;
        foreach ($forbidden_roles as $role) if (in_array( $role, $roles )) return false;

        return true;
    }
}