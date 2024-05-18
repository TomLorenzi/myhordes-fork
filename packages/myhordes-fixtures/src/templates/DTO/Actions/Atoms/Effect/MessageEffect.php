<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessMessageEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @method self text(?string $v)
 * @property ?string $text
 * @method self domain(?string $v)
 * @property ?string domain
 * @method self escort(?bool $v)
 * @property ?bool escort
 */
class MessageEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessMessageEffect::class;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'domain' => 'items',
            default => null
        };
    }

}