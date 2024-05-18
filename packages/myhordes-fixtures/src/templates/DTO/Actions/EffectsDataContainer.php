<?php

namespace MyHordes\Fixtures\DTO\Actions;

/**
 * @method EffectsDataElement[] all()
 * @method EffectsDataElement add()
 * @method EffectsDataElement clone(string $id)
 * @method EffectsDataElement modify(string $id, bool $required = true)
 * @method string store(EffectsDataElement $child, mixed $context = null)
 */
class EffectsDataContainer extends ActionDataContainerBase
{
    protected function getElementClass(): string
    {
        return EffectsDataElement::class;
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
    public function findEffects(string $class): array {
        return $this->findAtom($class);
    }
}