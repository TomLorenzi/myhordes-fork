<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms;

use App\Service\Actions\Game\AtomProcessors\Require\ProcessEscortRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

/**
 * @method minFollowers(?int $v)
 * @property ?int $minFollowers
 * @method maxFollowers(?int $v)
 * @property ?int $maxFollowers
 * @method full(?bool $v)
 * @property ?bool $full
 */
class EscortRequirement extends RequirementsAtom {
    public function getClass(): string
    {
        return ProcessEscortRequirement::class;
    }
}