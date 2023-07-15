<?php

namespace MyHordes\Fixtures\DTO\Buildings;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;
use MyHordes\Fixtures\DTO\UniqueIDGeneratorTrait;

/**
 * @method BuildingPrototypeDataElement[] all()
 * @method BuildingPrototypeDataElement add()
 * @method BuildingPrototypeDataElement clone(string $id)
 * @method BuildingPrototypeDataElement modify(string $id, bool $required = true)
 */
class BuildingPrototypeDataContainer extends Container
{
    use UniqueIDGeneratorTrait;

    protected function getElementClass(): string
    {
        return BuildingPrototypeDataElement::class;
    }
}