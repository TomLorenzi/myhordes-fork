<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Service\UserHandler;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\FeatureRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessFeatureRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|FeatureRequirement $data): bool
    {
        if (!$data->feature) return true;
        return $this->container->get(UserHandler::class)->checkFeatureUnlock( $cache->citizen->getUser(), $data->feature, false );
    }
}