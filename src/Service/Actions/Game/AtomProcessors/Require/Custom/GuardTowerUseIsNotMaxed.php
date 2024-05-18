<?php

namespace App\Service\Actions\Game\AtomProcessors\Require\Custom;

use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Service\TownHandler;
use App\Structures\ActionHandler\Evaluation;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\CustomClassRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class GuardTowerUseIsNotMaxed extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|CustomClassRequirement $data): bool
    {
        $cn = $this->container->get(TownHandler::class)->getBuilding( $cache->citizen->getTown(), 'small_watchmen_#00', true );
        $max = $cache->conf->get( TownConf::CONF_MODIFIER_GUARDTOWER_MAX, 150 );
        return !($cn && $max > 0 && $cn->getTempDefenseBonus() >= $max);
    }
}