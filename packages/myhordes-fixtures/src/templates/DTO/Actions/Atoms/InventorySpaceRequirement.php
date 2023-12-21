<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessInventorySpaceRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method space(?int $v)
 * @property ?int space
 * @method self considerTrunk(?bool $v)
 * @property ?bool considerTrunk
 * @method self container(?bool $v)
 * @property ?bool container
 * @method self ignoreInventory(?bool $v)
 * @property ?bool ignoreInventory
 */
class InventorySpaceRequirement extends RequirementsAtom {

    protected function default(string $name): mixed
    {
        return match ($name) {
            'space' => 1,
            'considerTrunk', 'container' => true,
            default => parent::default($name)
        };
    }

    public function getClass(): string
    {
        return ProcessInventorySpaceRequirement::class;
    }
}