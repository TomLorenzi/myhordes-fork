<?php

namespace MyHordes\Fixtures\DTO\Items;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;
use MyHordes\Fixtures\DTO\UniqueIDGeneratorTrait;

/**
 * @method ItemPrototypeDataElement[] all()
 * @method ItemPrototypeDataElement add()
 * @method ItemPrototypeDataElement clone(string $id)
 * @method ItemPrototypeDataElement modify(string $id, bool $required = true)
 */
class ItemPrototypeDataContainer extends Container
{
    use UniqueIDGeneratorTrait;

    protected function getElementClass(): string
    {
        return ItemPrototypeDataElement::class;
    }
}