<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessTimeRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self minDay(?int $v)
 * @property ?int $minDay
 * @method self maxDay(?int $v)
 * @property ?int $maxDay
 * @method self atNight()
 * @method self atDay()
 * @method self atAnyTime()
 */
class TimeRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessTimeRequirement::class;
    }

    public function __call(string $name, array $arguments): self
    {
        return match (count($arguments) === 0 ? $name : '_') {
            'atNight' => parent::__call('time', ['night']),
            'atDay' => parent::__call('time', ['day']),
            'atAnyTime' => parent::__call('time', [null]),
            default => parent::__call( $name, $arguments )
        };
    }

    public function allowDay(): bool {
        return ($this->time ?? 'day') === 'day';
    }

    public function allowNight(): bool {
        return ($this->time ?? 'night') === 'night';
    }
}