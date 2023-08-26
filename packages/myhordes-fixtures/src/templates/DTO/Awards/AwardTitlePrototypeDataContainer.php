<?php

namespace MyHordes\Fixtures\DTO\Awards;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;

/**
 * @method AwardTitlePrototypeDataElement[] all()
 * @method AwardTitlePrototypeDataElement add()
 * @method AwardTitlePrototypeDataElement clone(string $id)
 * @method AwardTitlePrototypeDataElement modify(string $id, bool $required = true)
 */
class AwardTitlePrototypeDataContainer extends Container
{
    protected function getElementClass(): string
    {
        return AwardTitlePrototypeDataElement::class;
    }

    protected function store(ElementInterface|AwardTitlePrototypeDataElement $child, mixed $context = null): string
    {
        return parent::store( $child, $context ?? "{$child->associatedpicto}:{$child->unlockquantity}" );
    }
}