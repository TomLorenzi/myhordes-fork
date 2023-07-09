<?php

namespace MyHordes\Fixtures\DTO;

interface ArrayDecoratorInterface extends ArrayDecoratorReadInterface {
    public function fromArray(array $data): self;
}