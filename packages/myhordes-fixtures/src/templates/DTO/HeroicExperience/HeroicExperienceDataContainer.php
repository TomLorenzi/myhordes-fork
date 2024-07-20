<?php

namespace MyHordes\Fixtures\DTO\HeroicExperience;

use MyHordes\Fixtures\DTO\Container;
use MyHordes\Fixtures\DTO\ElementInterface;
use MyHordes\Fixtures\DTO\UniqueIDGeneratorTrait;

/**
 * @method HeroicExperienceDataElement[] all()
 * @method HeroicExperienceDataElement add()
 * @method HeroicExperienceDataElement clone(string $id)
 * @method HeroicExperienceDataElement modify(string $id, bool $required = true)
 */
class HeroicExperienceDataContainer extends Container
{
    protected function getElementClass(): string
    {
        return HeroicExperienceDataElement::class;
    }

    protected function store(ElementInterface|HeroicExperienceDataElement $child, mixed $context = null): string
    {
        $this->writeDataFor( $key = ($context ?? $child->name), $child->toArray() );
        return $key;
    }
}