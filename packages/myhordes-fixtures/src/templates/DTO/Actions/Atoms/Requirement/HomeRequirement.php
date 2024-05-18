<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Requirement;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessHomeRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method self minLevel(?int $v)
 * @property ?int $minLevel
 * @method self maxLevel(?int $v)
 * @property ?int $maxLevel
 * @method self upgrade(?string $v)
 * @property ?string $upgrade
 */
class HomeRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessHomeRequirement::class;
    }

}