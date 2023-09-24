<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use MyHordes\Fixtures\DTO\Actions\Atoms\ItemRequirement;
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

    protected function store(ElementInterface|RequirementsDataElement $child, mixed $context = null): string
    {
        if ($context === null && $this->has( $child->identifier )) throw new \Exception("Duplicate requirement identifier '{$child->identifier}'");
        elseif ($context !== null && $context !== $child->identifier) throw new \Exception("Forbidden attempt to change requirement identifier ('$context' > '{$child->identifier}'");
        parent::store( $child, $context ?? $child->identifier );
        return $context ?? $child->identifier;
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
        $r = [];
        foreach ( $this->all() as $de )
            foreach ($de->atomList as $atom)
                if (is_a( $atom, $class ))
                    $r[] = $atom;
        return $r;
    }
}