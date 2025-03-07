<?php

namespace MyHordes\Fixtures\DTO\Actions;

/**
 * @method RequirementsDataElement[] all()
 * @method RequirementsDataElement add()
 * @method RequirementsDataElement clone(string $id)
 * @method RequirementsDataElement modify(string $id, bool $required = true)
 * @method string store(RequirementsDataElement $child, mixed $context = null)
 */
class RequirementsDataContainer extends ActionDataContainerBase
{
    protected function getElementClass(): string
    {
        return RequirementsDataElement::class;
    }

    /**
     * Fetches specific requirements from the container
     *
     * @param string $class The name of the requirement class.
     * @psalm-param class-string<T> $service
     *
     * @return array List of requirements
     * @psalm-return T[]
     *
     * @template T as object
     */
    public function findRequirements(string $class): array {
        return $this->findAtom($class);
    }
}