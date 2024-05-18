<?php

namespace MyHordes\Fixtures\DTO\Actions;

/**
 * @property EffectAtom[] $atomList
 * @method self add(EffectAtom $atom)
 * @method self atomList(EffectAtom[] $atom)
 * @method EffectsDataContainer commit(string &$id = null)
 * @method EffectsDataContainer discard()
 */
class EffectsDataElement extends ActionDataElementBase {

    public static function getAtomClass(): string {
        return EffectAtom::class;
    }

}