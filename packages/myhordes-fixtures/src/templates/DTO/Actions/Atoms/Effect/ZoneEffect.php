<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Enum\Configuration\CitizenProperties;
use App\Service\Actions\Game\AtomProcessors\Effect\ProcessZoneEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

/**
 * @property ?int uncover
 * @property-read ?int cleanMin
 * @property-read ?int cleanMax
 * @property-read ?int zombieMin
 * @property-read ?int zombieMax
 * @method self escape(int|CitizenProperties|null $v)
 * @property ?int escape
 * @method self escapeTag(?string $v)
 * @property ?string escapeTag
 * @method self improveLevel(?float $v)
 * @property ?float improveLevel
 * @method self chatSilence(?int $v)
 * @property ?int chatSilence
 */
class ZoneEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessZoneEffect::class;
    }

    public function uncover(int|CitizenProperties|null $radius = 1): self
    {
        $this->uncover = $radius;
        return $this;
    }

    public function clean(int|CitizenProperties $min, int|CitizenProperties|null $max = null): self {
        $this->cleanMin = $min;
        $this->cleanMax = $max ?? $min;
        return $this;
    }

    public function hasCleanupEffect(): bool {
        return $this->cleanMin !== 0 || $this->cleanMax !== 0;
    }

    public function kills(int|CitizenProperties $min, int|CitizenProperties|null $max = null): self {
        $this->zombieMin = $min;
        $this->zombieMax = $max ?? $min;
        return $this;
    }

    public function hasKillEffect(): bool {
        return $this->zombieMin !== 0 || $this->zombieMax !== 0;
    }

    protected function default(string $name): mixed {
        return match($name) {
            'uncover', 'cleanMin', 'cleanMax', 'zombieMin', 'zombieMax', 'escape', 'chatSilence' => 0,
            'improveLevel' => 0.0,
            default => null
        };
    }

}