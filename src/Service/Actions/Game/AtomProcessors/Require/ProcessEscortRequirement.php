<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Structures\ActionHandler\Evaluation;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\EscortRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessEscortRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|EscortRequirement $data): bool
    {
        $followers = count($cache->citizen->getValidLeadingEscorts());
        $cache->addTranslationKey('escortCount', $followers);

        if ($data->minFollowers !== null && $followers < $data->minFollowers) return false;
        if ($data->maxFollowers !== null && $followers > $data->maxFollowers) return false;
        if ($data->full !== null && $data->full !== ( $followers === $cache->conf->get( TownConf::CONF_FEATURE_ESCORT_SIZE, 5 ) ) ) return false;

        return true;
    }
}