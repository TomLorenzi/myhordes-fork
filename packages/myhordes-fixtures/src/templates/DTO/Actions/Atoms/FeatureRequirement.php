<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessEscortRequirement;
use App\Service\Actions\Game\AtomProcessors\Require\ProcessFeatureRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method feature(?string $v)
 * @property ?string $feature
 */
class FeatureRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessFeatureRequirement::class;
    }
}