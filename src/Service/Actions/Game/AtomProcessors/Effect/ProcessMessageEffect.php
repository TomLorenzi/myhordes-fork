<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\MessageEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessMessageEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|MessageEffect $data): void
    {
        if ($data->escort === null || $data->escort === $cache->getEscortMode())
            $cache->addMessage( $data->text, translationDomain: $data->domain, order: $data->order );
    }
}