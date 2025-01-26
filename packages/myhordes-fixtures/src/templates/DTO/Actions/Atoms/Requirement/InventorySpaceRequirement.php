<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessInventorySpaceRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method space(?int $v)
 * @property ?int $space
 * @method self considerTrunk(?bool $v)
 * @property ?bool $considerTrunk
 * @method self container(?bool $v)
 * @property ?bool $container
 * @method self ignoreInventory(?bool $v)
 * @property ?bool $ignoreInventory
 * @method self ignoreSource(?bool $v)
 * @property ?bool $ignoreSource
 * @method self ignoreTarget(?bool $v)
 * @property ?bool $ignoreTarget
 * @method self heavy(?bool $v)
 * @property ?bool $heavy
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