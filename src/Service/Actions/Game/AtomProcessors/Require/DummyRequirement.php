<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Structures\ActionHandler\Evaluation;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class DummyRequirement extends AtomRequirementProcessor
{
	/**
	 * @throws \Exception
	 */
	public function __invoke(Evaluation $cache, RequirementsAtom $data): bool
    {
        throw new \Exception('DummyRequirement has been invoked.');
    }
}