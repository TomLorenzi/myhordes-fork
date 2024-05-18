<?php

namespace App\Service\Actions\Game\AtomProcessors\Require\Custom;

use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Service\TownHandler;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\CustomClassRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class RoleVote extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|CustomClassRequirement $data): bool
    {
        $needed = $data->args['needed'] ?? null;
        $hasNotVoted = $data->args['hasNotVoted'] ?? null;

        $roleName = $needed ?? $hasNotVoted ?? null;
        if (!$roleName) return false;

        $role = $cache->em->getRepository(CitizenRole::class)->findOneBy(['name' => $roleName]);
        if (!$role) return false;

        if ($needed && !$this->container->get(TownHandler::class)->is_vote_needed( $cache->citizen->getTown(), $role ))
            return false;

        if ($hasNotVoted && $cache->em->getRepository(CitizenVote::class)->findOneByCitizenAndRole($cache->citizen, $role))
            return false;


        return true;
    }
}