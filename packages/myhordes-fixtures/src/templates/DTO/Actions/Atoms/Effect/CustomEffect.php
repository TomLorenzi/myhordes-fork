<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessCustomEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @method self effectIndex(?int $v)
 * @property ?int effectIndex
 */
class CustomEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessCustomEffect::class;
    }
}