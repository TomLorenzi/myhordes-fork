<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessLocationRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self town(?bool $v)
 * @property ?bool $town
 * @method self beyond(?bool $v)
 * @property ?bool $beyond
 * @method self exploring(?bool $v)
 * @property ?bool $exploring
 * @method self minKm(?int $v)
 * @property ?int $minKm
 * @method self maxKm(?int $v)
 * @property ?int $maxKm
 * @method self minAp(?int $v)
 * @property ?int $minAp
 * @method self maxAp(?int $v)
 * @property ?int $maxAp
 * @method self minZombies(?int $v)
 * @property ?int $minZombies
 * @method self maxZombies(?int $v)
 * @property ?int $maxZombies
 * @method self minLevel(?float $v)
 * @property ?float $minLevel
 * @method self maxLevel(?float $v)
 * @property ?float $maxLevel
 * @method self atRuin(?bool $v)
 * @property ?bool $atRuin
 * @method self atBuriedRuin(?bool $v)
 * @property ?bool $atBuriedRuin
 * @method self isControlled(?bool $v)
 * @property ?bool $isControlled
 * @method self isTempControlled(?bool $v)
 * @property ?bool $isTempControlled
 * @method self isControlledOrTempControlled(?bool $v)
 * @property ?bool $isControlledOrTempControlled
 */
class LocationRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessLocationRequirement::class;
    }



    public function requiresZone(): bool {
        return $this->minKm !== null || $this->maxKm !== null || $this->minAp !== null || $this->maxAp !== null ||
            $this->minLevel !== null || $this->maxLevel !== null ||
            $this->atRuin !== null || $this->atBuriedRuin !== null ||
            $this->requiresZombieCheck();
    }

    public function requiresZombieCheck(): bool {
        return $this->minZombies !== null || $this->maxZombies !== null || $this->requiresCPCheck();
    }

    public function requiresCPCheck(): bool {
        return $this->isControlled !== null || $this->isTempControlled !== null || $this->isControlledOrTempControlled !== null;
    }

    protected function default(string $name): mixed
    {
        return match ($name) {
            'town', 'beyond', 'exploring' => false,
            default => parent::default($name)
        };
    }
}