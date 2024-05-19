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
 * @method self order(?int $v)
 * @property ?int order
 */
class MessageEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessMessageEffect::class;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'domain' => 'items',
            'order' => 0,
            default => null
        };
    }

}