<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessCounterRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self min(?int $v)
 * @property ?int $minDay
 * @method self max(?int $v)
 * @property ?int $maxDay
 * @method self counter(?int $v)
 * @property ?int $counter
 */
class CounterRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessCounterRequirement::class;
    }

    protected function default(string $name): mixed
    {
        return match ($name) {
            'counter' => 0,
            default => parent::default($name)
        };
    }

}