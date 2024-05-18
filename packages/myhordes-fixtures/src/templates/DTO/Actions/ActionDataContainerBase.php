<?php

namespace MyHordes\Fixtures\DTO\Actions;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;

/**
 * @method ActionDataElementBase[] all()
 * @method ActionDataElementBase add()
 * @method ActionDataElementBase clone(string $id)
 * @method ActionDataElementBase modify(string $id, bool $required = true)
 */
abstract class ActionDataContainerBase extends Container
{
    abstract protected function getElementClass(): string;

    protected function store(ElementInterface|ActionDataElementBase $child, mixed $context = null): string
    {
        if ($context === null && $this->has( $child->identifier )) throw new \Exception("Duplicate identifier '{$child->identifier}'");
        elseif ($context !== null && $context !== $child->identifier) throw new \Exception("Forbidden attempt to change identifier ('$context' > '{$child->identifier}'");
        parent::store( $child, $context ?? $child->identifier );
        return $context ?? $child->identifier;
    }

    protected function findAtom(string $class): array {
        $r = [];
        foreach ( $this->all() as $de )
            foreach ($de->atomList as $atom)
                if (is_a( $atom, $class ))
                    $r[] = $atom;
        return $r;
    }
}