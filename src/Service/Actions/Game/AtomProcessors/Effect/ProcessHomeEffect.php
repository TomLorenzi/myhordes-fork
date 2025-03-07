<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\HomeEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class ProcessHomeEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|HomeEffect $data): void
    {
        if ($data->storage <> 0) {
            $cache->citizen->getHome()->setAdditionalStorage( $cache->citizen->getHome()->getAdditionalStorage() + $data->storage );
            $cache->addTranslationKey('home_storage', $data->storage);
        }

        if ($data->defense <> 0 || $data->temporaryDefense <> 0) {
            $cache->citizen->getHome()
                ->setAdditionalDefense( $cache->citizen->getHome()->getAdditionalDefense() + $data->defense )
                ->setTemporaryDefense( $cache->citizen->getHome()->getTemporaryDefense() + $data->temporaryDefense );
            $cache->addTranslationKey('home_defense', $data->defense + $data->temporaryDefense);
        }

        if ($data->temporaryDefense <> 0) {
            $cache->citizen->getHome()->setAdditionalDefense( $cache->citizen->getHome()->getAdditionalDefense() + $data->defense );
            $cache->addTranslationKey('home_defense', $data->defense);
        }

        foreach ($data->tags_temp as $tag) $cache->citizen->getHome()->addTag( $tag, false );
        foreach ($data->tags_perm as $tag) $cache->citizen->getHome()->addTag( $tag, true );
    }
}