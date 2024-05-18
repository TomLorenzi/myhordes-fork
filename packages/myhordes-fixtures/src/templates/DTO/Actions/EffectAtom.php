<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Service\Actions\Game\AtomProcessors\Effect\AtomEffectProcessor;

abstract class EffectAtom extends Atom
{

    public static function getAtomClass(): string {
        return EffectAtom::class;
    }

    public static function getAtomProcessorClass(): string {
        return AtomEffectProcessor::class;
    }

}