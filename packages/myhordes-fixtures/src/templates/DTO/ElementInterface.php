<?php

namespace MyHordes\Fixtures\DTO;

interface ElementInterface extends ArrayDecoratorInterface {

    public function commit(): ContainerInterface;
    public function discard(): ContainerInterface;

    public function toArray(): array;
    public function fromArray(array $element): self;

}