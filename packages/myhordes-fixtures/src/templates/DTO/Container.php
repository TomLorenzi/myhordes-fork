<?php

namespace MyHordes\Fixtures\DTO;

use Exception;

abstract class Container implements ContainerInterface
{
    public function __construct(
        private array $data = []
    ) { }

    protected function store(ElementInterface $child, mixed $context = null): string
    {
        $this->writeDataFor( $context, $child->toArray() );
        return $context;
    }

    protected function generate(?string $from = null, bool $allow_commit = true, bool $clone = false): ElementInterface
    {
        $data = $this->getDataFor( $from );
        if ($data && $clone) unset($data['identifier']);

        return new ($this->getElementClass())(
            $this,
            fn(ElementInterface $c, ?string &$id = null) => $this->store( $c, $clone ? null : $from ),
            $data
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
     * @return ElementInterface[]
     */
    public function all(): array {
        $keys = array_keys( $this->data );
        return array_combine( $keys, array_map( fn(string $key) => $this->generate(from: $key), $keys ) );
    }

    public function unpackFirst(): ?array {
        return empty($this->data) ? null : $this->data[array_key_first( $this->data )];
    }

    /**
     * @throws Exception
     */
    public function modify(string $id, bool $required = true): ElementInterface {
        $exists = $this->has($id);
        if ($required && !$exists) throw new Exception("Attempt to modify non-existent element '$id' in container.");
        return $exists ? $this->generate(from: $id) : $this->generate(allow_commit: false);
    }

    public function clone(string $id): ElementInterface {
        if (!$this->has($id)) throw new Exception("Attempt to clone non-existent element '$id' in container.");
        return $this->generate(from: $id, clone: true);
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