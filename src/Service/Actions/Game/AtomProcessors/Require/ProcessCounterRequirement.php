<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\CounterRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessCounterRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|CounterRequirement $data): bool
    {
        if ($data->counter === 0) return true;

        if ($data->min !== null) {
            $cache->addTranslationKey("counter_{$data->counter}_min", $data->min);
            $cache->addTranslationKey("counter__min", $data->min);
        }
        if ($data->max !== null) {
            $cache->addTranslationKey("counter_max", $data->max);
            $cache->addTranslationKey("counter_{$data->counter}_max", $data->max);
        }

        $counter_value = $cache->citizen->getSpecificActionCounterValue( $data->counter );
        if ($data->min !== null && $counter_value < $data->min) return false;
        if ($data->max !== null && $counter_value > $data->max) return false;

        return true;
    }
}