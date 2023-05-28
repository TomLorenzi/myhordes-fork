<?php

namespace MyHordes\Fixtures\DTO;

interface ArrayDecoratorInterface {
    public function toArray(): array;
    public function fromArray(array $data): self;
}