<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessItemRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self item(?string $v)
 * @property ?string $item
 * @method self property(?string $v)
 * @property ?string $property
 * @method self count(?int $v)
 * @property ?int $count
 * @method self poison(?bool $v)
 * @property ?bool $poison
 * @method self broken(?bool $v)
 * @property ?bool $broken
 * @method self store(?string $v)
 * @property ?string $store
 */
class ItemRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessItemRequirement::class;
    }

    public function isPropertyRequirement(): bool {
        return $this->property ?? false;
    }

    protected function default(string $name): mixed
    {
        return match ($name) {
            'count' => 1,
            'poison', 'broken' => false,
            default => parent::default($name)
        };
    }
}