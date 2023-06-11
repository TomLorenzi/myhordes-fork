<?php

namespace MyHordes\Fixtures\DTO\Items;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;

/**
 * @method ItemPrototypeDataElement[] all()
 * @method ItemPrototypeDataElement add()
 * @method ItemPrototypeDataElement clone(string $id)
 * @method ItemPrototypeDataElement modify(string $id, bool $required = true)
 */
class ItemPrototypeDataContainer extends Container
{
    protected function getElementClass(): string
    {
        return ItemPrototypeDataElement::class;
    }

    protected function store(ElementInterface|ItemPrototypeDataElement $child, mixed $context = null): void
    {
        $key = $context;
        if ($key === null) {
            // Generate unique ID
            $i = 0; do {
                $key = "{$child->icon}_#" . str_pad("$i",2, '0',STR_PAD_LEFT);
                $i++;
            } while ( $this->has($key) );
        }

        $this->writeDataFor( $key, $child->toArray() );
    }
}