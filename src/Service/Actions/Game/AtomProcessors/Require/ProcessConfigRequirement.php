<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Service\ConfMaster;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ConfigRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessConfigRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|ConfigRequirement $data): bool
    {
        foreach ($data->getConfigRequirements() as list( $setting, $expected ))
            if ($cache->conf->get( $setting ) !== $expected)
                return false;

        if ($data->event) {
            $confMaster = $this->container->get(ConfMaster::class);

            $events = $confMaster->getCurrentEvents($cache->citizen->getTown());
            $found = false;
            foreach ($events as $event)
                if ($event->name() == $data->event && $event->active()) {
                    $found = true;
                    break;
                }
            if (!$found) return false;
        }

        return true;
    }
}