<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessInventorySpaceRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method space(?int $v)
 * @property ?int space
 * @method considerTrunk(?bool $v)
 * @property ?bool considerTrunk
 */
class InventorySpaceRequirement extends RequirementsAtom {

    protected function default(string $name): mixed
    {
        return match ($name) {
            'space' => 1,
            'considerTrunk' => true,
            default => parent::default($name)
        };
    }

    public function getClass(): string
    {
        return ProcessInventorySpaceRequirement::class;
    }
}