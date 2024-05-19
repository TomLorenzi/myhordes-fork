<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessHomeEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @method self defense(?int $v)
 * @property ?int defense
 * @method self storage(?int $v)
 * @property ?int storage
 */
class HomeEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessHomeEffect::class;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'defense', 'storage' => 0,
            default => null
        };
    }

}