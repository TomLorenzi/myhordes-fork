<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;

abstract class RequirementsAtom extends Atom
{

    public static function getAtomClass(): string {
        return RequirementsAtom::class;
    }

    public static function getAtomProcessorClass(): string {
        return AtomRequirementProcessor::class;
    }

}