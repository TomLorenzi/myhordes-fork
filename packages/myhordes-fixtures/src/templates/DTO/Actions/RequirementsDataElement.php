<?php

namespace MyHordes\Fixtures\DTO\Actions;

/**
 * @property RequirementsAtom[] $atomList
 * @method self identifier(string $v)
 * @method self add(RequirementsAtom $atom)
 * @method self atomList(RequirementsAtom[] $atom)
 * @method RequirementsDataContainer commit(string &$id = null)
 * @method RequirementsDataContainer discard()
 */
class RequirementsDataElement extends ActionDataElementBase {

    public static function getAtomClass(): string {
        return RequirementsAtom::class;
    }

}