<?php

namespace MyHordes\Fixtures\DTO;

use Exception;

abstract class Container implements ContainerInterface
{
    public function __construct(
        private array $data = []
    ) { }

    protected function store(ElementInterface $child, mixed $context = null): void
    {
        $this->writeDataFor( $context, $child->toArray() );
    }

    protected function generate(?string $from = null, bool $allow_commit = true): ElementInterface
    {
        return new ($this->getElementClass())(
            $this,
            fn(ElementInterface $c) => $this->store( $c, $from ),
            $this->getDataFor( $from )
        );
    }

    abstract protected function getElementClass(): string;

    protected function getDataFor(?string $id): array {
        return $id === null ? [] : ($this->data[$id] ?? []);
    }

    protected function writeDataFor(string $id, array $data): void {
        $this->data[$id] = $data;
    }

    public function add(): ElementInterface {
        return $this->generate();
    }

    /**
     * @throws Exception
     */
    public function modify(string $id, bool $required = true): ElementInterface {
        $exists = $this->has($id);
        if ($required && !$exists) throw new Exception("Attempt to modify non-existant element '$id' in container.");
        return $exists ? $this->generate(from: $id) : $this->generate(allow_commit: false);
    }

    public function delete(string $id): self {
        unset($this->data[$id]);
        return $this;
    }

    public function has(string $id): bool {
        return array_key_exists($id, $this->data);
    }

    final public function toArray(): array {
        return $this->data;
    }

    final public function fromArray(array $data): self {
        $this->data = $data;
        return $this;
    }
}