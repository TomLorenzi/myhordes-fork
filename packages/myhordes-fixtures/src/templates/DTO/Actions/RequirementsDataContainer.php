<?php

namespace MyHordes\Fixtures\DTO\Actions;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;

/**
 * @method RequirementsDataElement[] all()
 * @method RequirementsDataElement add()
 * @method RequirementsDataElement clone(string $id)
 * @method RequirementsDataElement modify(string $id, bool $required = true)
 */
class RequirementsDataContainer extends Container
{
    protected function getElementClass(): string
    {
        return RequirementsDataElement::class;
    }

    protected function store(ElementInterface|RequirementsDataElement $child, mixed $context = null): void
    {
        if ($context === null && $this->has( $child->identifier )) throw new \Exception("Duplicate requirement identifier '{$child->identifier}'");
        elseif ($context !== null && $context !== $child->identifier) throw new \Exception("Forbidden attempt to change requirement identifier ('$context' > '{$child->identifier}'");
        parent::store( $child, $context ?? $child->identifier );
    }
}