<?php

namespace MyHordes\Fixtures\DTO;

trait UniqueIDGeneratorTrait
{
    abstract protected function has(string $key): bool;

    abstract protected function writeDataFor(string $key, array $data): void;

    protected function store(ElementInterface|LabeledIconElementInterface $child, mixed $context = null): string
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
        return $key;
    }
}