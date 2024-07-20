<?php

namespace MyHordes\Fixtures\DTO;

abstract class Element implements ElementInterface
{
    public function __construct(
        private readonly Container $parent,
        private readonly \Closure  $commit_callback,
        private          array     $data = []
    ) {
        if (!empty($this->data)) $this->afterSerialization();
    }

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

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]) || !empty($this->provide_default($name));
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

    final public function commit(string &$id = null): ContainerInterface {
        ($this->commit_callback)($this);
        $id = $this->parent->getLastModifiedKey();
        return $this->parent;
    }

    final public function discard(): ContainerInterface {
        return $this->parent;
    }

    protected function beforeSerialization(): void {}
    protected function afterSerialization(): void {}

    final public function toArray(): array {
        $this->beforeSerialization();;
        return $this->data;
    }

    final public function fromArray(array $data): self {
        $this->data = $data;
        $this->afterSerialization();
        return $this;
    }
}