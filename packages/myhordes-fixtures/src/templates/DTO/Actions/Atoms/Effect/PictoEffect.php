<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessPictoEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @method self picto(?string $v)
 * @property ?string $picto
 * @method self forEntireTown(?bool $v)
 * @property ?bool forEntireTown
 * @method self count(?int $v)
 * @property ?int count
 */
class PictoEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessPictoEffect::class;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'count' => 1,
            'forEntireTown' => false,
            default => null
        };
    }

}