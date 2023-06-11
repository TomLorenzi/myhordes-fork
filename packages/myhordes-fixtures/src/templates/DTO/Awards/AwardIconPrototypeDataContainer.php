<?php

namespace MyHordes\Fixtures\DTO\Awards;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;

/**
 * @method AwardIconPrototypeDataElement[] all()
 * @method AwardIconPrototypeDataElement add()
 * @method AwardIconPrototypeDataElement clone(string $id)
 * @method AwardIconPrototypeDataElement modify(string $id, bool $required = true)
 */
class AwardIconPrototypeDataContainer extends Container
{
    protected function getElementClass(): string
    {
        return AwardIconPrototypeDataElement::class;
    }

    protected function store(ElementInterface|AwardIconPrototypeDataElement $child, mixed $context = null): void
    {
        parent::store( $child, $context ?? "{$child->associatedpicto}:{$child->unlockquantity}" );
    }
}