<?php

namespace MyHordes\Fixtures\DTO;

abstract class Element implements ElementInterface
{
    public function __construct(
        private readonly Container $parent,
        private readonly \Closure  $commit_callback,
        private                    $data = []
    ) { }

    protected function provide_default(string $name): mixed {
        return null;
    }

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? $this->provide_default($name);
    }

    /**
     * @throws \Exception
     */
    public function __call(string $name, array $arguments): self
    {
        if (count($arguments) !== 1) throw new \Exception('Element exception: Invalid __call payload.');
        $this->$name = $arguments[0];
        return $this;
    }

    final public function commit(): ContainerInterface {
        ($this->commit_callback)($this);
        return $this->parent;
    }

    final public function discard(): ContainerInterface {
        return $this->parent;
    }

    final public function toArray(): array {
        return $this->data;
    }

    final public function fromArray(array $data): self {
        $this->data = $data;
        return $this;
    }
}