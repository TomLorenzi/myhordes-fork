<?php

namespace MyHordes\Fixtures\DTO;

interface ContainerInterface extends ArrayDecoratorInterface
{
    public function add(): ElementInterface;
    public function modify(string $id, bool $required = true): ElementInterface;
    public function delete(string $id): self;
    public function has(string $id): bool;
}