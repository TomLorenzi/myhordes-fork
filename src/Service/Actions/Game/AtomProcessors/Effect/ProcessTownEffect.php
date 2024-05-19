<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Enum\ActionHandler\CountType;
use App\Service\LogTemplateHandler;
use App\Structures\ActionHandler\Execution;
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

        if ($data->soulDefense !== 0)
            $cache->citizen->getTown()->setSoulDefense($cache->citizen->getTown()->getSoulDefense() + $data->soulDefense);
    }
}