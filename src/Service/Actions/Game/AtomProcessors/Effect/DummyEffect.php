<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Structures\ActionHandler\Evaluation;
use App\Structures\ActionHandler\Execution;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class DummyEffect extends AtomEffectProcessor
{
	/**
	 * @throws \Exception
	 */
	public function __invoke(Execution $cache, EffectAtom $data): void
    {
        throw new \Exception('DummyEffect has been invoked.');
    }
}