<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\BuildingPrototype;
use App\Enum\ActionHandler\CountType;
use App\Service\GameProfilerService;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\ActionHandler\Execution;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\TownEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessTownEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|TownEffect $data): void
    {
        /** @var LogTemplateHandler $log */
        $log = $this->container->get(LogTemplateHandler::class);
        if ($data->hasWellEffect()) {

            $cache->addToCounter( CountType::Well, $add = mt_rand( $data->wellMin, $data->wellMax ) );
            $cache->citizen->getTown()->setWell( $cache->citizen->getTown()->getWell() + $add );

            if ($add > 0)
                $cache->em->persist( $log->wellAdd( $cache->citizen, $cache->originalPrototype, $add) );

        }

        if ($data->unlocksBlueprint()) {
            $town = $cache->citizen->getTown();

            $blocked = $cache->conf->get(TownConf::CONF_DISABLED_BUILDINGS);
            $possible = $cache->em->getRepository(BuildingPrototype::class)->findProspectivePrototypes( $town );
            $filtered = array_filter( $possible, fn(BuildingPrototype $proto) => match(true) {
                in_array($proto->getName(), $blocked) => false,
                $data->unlockBlueprintType !== null && $data->unlockBlueprintType === $proto->getBlueprint() => true,
                default => in_array($proto->getName(), $data->unlockBlueprintList ?? [])
            });

            if (!empty($filtered)) {
                /** @var RandomGenerator $rg */
                $rg = $this->container->get(RandomGenerator::class);

                /** @var TownHandler $th */
                $th = $this->container->get(TownHandler::class);

                /** @var GameProfilerService $gps */
                $gps = $this->container->get(GameProfilerService::class);

                /** @var BuildingPrototype $pick */
                $pick = $rg->pick( $filtered );

                if ($th->addBuilding( $town, $pick )) {
                    $cache->addDiscoveredBlueprint( $pick );
                    $cache->em->persist( $log->constructionsNewSite( $cache->citizen, $pick ) );
                    $gps->recordBuildingDiscovered( $pick, $town, $cache->citizen, 'action' );
                }
            }
        }

        if ($data->soulDefense !== 0)
            $cache->citizen->getTown()->setSoulDefense($cache->citizen->getTown()->getSoulDefense() + $data->soulDefense);
    }
}