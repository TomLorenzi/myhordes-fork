<?php

namespace App\Structures;

use Symfony\Component\PropertyAccess\PropertyAccess;


class PropertyFilter {
    private $options;

    function __construct($options) {
        $this->options = $options;
    }
    function hasPropertyValues($i) {
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();

        foreach ($this->options as $property => $value) {
            if (!$propertyAccessor->isReadable($i, $property)) {
                return false;
            }
            else {

                if ($propertyAccessor->getValue($i, $property) !== $value) {
                    return false;
                }
                else {
                    return true;
                }
            }
        }
    }
    function __invoke($i) {
        return $this->hasPropertyValues($i);
    }
}