<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessTownEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @property-read ?int wellMin
 * @property-read ?int wellMax
 * @method self soulDefense(?int $v)
 * @property ?int soulDefense
 * @method
 */
class TownEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessTownEffect::class;
    }

    public function well(int $min, ?int $max = null): self {
        $this->wellMin = $min;
        $this->wellMax = $max ?? $min;
        return $this;
    }

    public function hasWellEffect(): bool {
        return $this->wellMin !== 0 || $this->wellMax !== 0;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'wellMin', 'wellMax', 'soulDefense' => 0,
            default => null
        };
    }

}