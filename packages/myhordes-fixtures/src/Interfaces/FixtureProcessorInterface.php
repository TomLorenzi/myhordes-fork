<?php

namespace MyHordes\Fixtures\Interfaces;

interface FixtureProcessorInterface
{
    public function process(array &$data): void;
}