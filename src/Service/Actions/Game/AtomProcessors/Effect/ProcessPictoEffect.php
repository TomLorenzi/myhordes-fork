<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\Citizen;
use App\Service\PictoHandler;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\PictoEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessPictoEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|PictoEffect $data): void
    {
        $targets = $data->forEntireTown
            ? $cache->citizen->getTown()->getCitizens()->filter( fn(Citizen $c) => $c->getAlive() )->toArray()
            : [$cache->citizen];

        /** @var PictoHandler $service */
        $service = $this->container->get(PictoHandler::class);
        foreach ($targets as $target)
            $service->give_picto($target, $data->picto, $data->count);
    }
}