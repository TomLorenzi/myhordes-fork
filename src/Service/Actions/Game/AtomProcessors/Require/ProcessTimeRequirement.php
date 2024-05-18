<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\TimeRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessTimeRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|TimeRequirement $data): bool
    {
        if ($data->minDay !== null) $cache->addTranslationKey("day_min", $data->minDay);
        if ($data->maxDay !== null) $cache->addTranslationKey("day_max", $data->maxDay);

        if ($data->minDay !== null && $cache->citizen->getTown()->getDay() < $data->minDay) return false;
        if ($data->maxDay !== null && $cache->citizen->getTown()->getDay() < $data->maxDay) return false;

        $is_night = $cache->conf->isNightMode();
        if (!$data->allowDay() && !$is_night) return false;
        if (!$data->allowNight() && $is_night) return false;

        return true;
    }
}