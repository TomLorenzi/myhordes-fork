<?php

namespace MyHordes\Fixtures\DTO\Actions\Atoms\Effect;

use App\Service\Actions\Game\AtomProcessors\Effect\ProcessRolePlayTextEffect;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;

class RolePlayTextEffect extends EffectAtom {
    public function getClass(): string
    {
        return ProcessRolePlayTextEffect::class;
    }
}