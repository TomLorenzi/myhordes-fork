<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\DummyRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self requirement(?string $v)
 * @property ?string $requirement
 * @method self args(?array $v)
 * @property ?array $args
 */
class CustomClassRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return $this->requirement;
    }

    protected function default(string $name): mixed
    {
        return match ($name) {
            'requirement' => DummyRequirement::class,
            'args' => [],
            default => parent::default($name)
        };
    }
}