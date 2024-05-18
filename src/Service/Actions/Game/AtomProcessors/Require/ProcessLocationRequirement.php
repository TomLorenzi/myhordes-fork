<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\EscapeTimer;
use App\Entity\RuinZone;
use App\Service\CitizenHandler;
use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\LocationRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessLocationRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|LocationRequirement $data): bool
    {
        if ($data->town !== null && $data->town === !!$cache->citizen->getZone())
            return false;

        if ($data->beyond !== null && $data->beyond !== !!$cache->citizen->getZone())
            return false;

        if ($data->exploring !== null && $data->exploring !== !!$cache->citizen->activeExplorerStats())
            return false;

        if ($data->requiresZone()) {

            $zone = $cache->citizen->getZone();
            if (!$zone) return false;

            $cache->addTranslationKey('km_from_town', $zone->getDistance());
            $cache->addTranslationKey('ap_from_town', $zone->getApDistance());

            $zombies = $zone->getZombies();
            if ($data->requiresZombieCheck() && $cache->citizen->activeExplorerStats())
                $zombies = $cache->em->getRepository(RuinZone::class)->findOneByExplorerStats( $cache->citizen->activeExplorerStats() )->getZombies();

            $cp = 0;
            if ($data->requiresCPCheck() && !$cache->citizen->activeExplorerStats()) {
                $citizenHandler = $this->container->get(CitizenHandler::class);
                foreach ($cache->citizen->getZone()->getCitizens() as $c)
                    $cp += $citizenHandler->getCP($c);
            }

            if ($data->minKm !== null && $zone->getDistance() < $data->minKm) return false;
            if ($data->maxKm !== null && $zone->getDistance() > $data->maxKm) return false;
            if ($data->minAp !== null && $zone->getApDistance() < $data->minAp) return false;
            if ($data->maxAp !== null && $zone->getApDistance() > $data->maxAp) return false;

            if ($data->atRuin !== null && $data->atRuin !== !!$zone->getPrototype()) return false;
            if ($data->atBuriedRuin !== null && ($data->atBuriedRuin !== !!$zone->getPrototype() || $data->atBuriedRuin !== ($zone->getBuryCount() > 0))) return false;

            if ($data->minZombies !== null && $zombies < $data->minZombies) return false;
            if ($data->maxZombies !== null && $zombies > $data->maxZombies) return false;

            if ($data->minLevel !== null && $zone->getImprovementLevel() < $data->minLevel) return false;
            if ($data->maxLevel !== null && $zone->getImprovementLevel() >= $data->maxLevel) return false;

            if ($data->isControlled !== null && $data->isControlled !== ($cp >= $zombies)) return false;
            if ($data->isTempControlled !== null && $data->isTempControlled !== !!$cache->em->getRepository( EscapeTimer::class )->findActiveByCitizen( $cache->citizen )) return false;
            if ($data->isControlledOrTempControlled !== null && $data->isControlledOrTempControlled !== ($cp >= $zombies || !!$cache->em->getRepository( EscapeTimer::class )->findActiveByCitizen( $cache->citizen ))) return false;

        }

        return true;
    }
}