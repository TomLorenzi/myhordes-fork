<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Service\EventProxyService;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\CustomEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessCustomEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|CustomEffect $data): void
    {
        /** @var EventProxyService $proxy */
        $proxy = $this->container->get( EventProxyService::class );
        $message = null;
        $proxy->executeCustomAction( $data->effectIndex, $cache->citizen, $cache->item, $cache->target, $cache->getAction(), $message, $remove, $cache );
        if (!empty($message)) $cache->addMessage( $message );
    }
}