<?php

namespace MyHordes\Plugins\Interfaces;

interface FixtureProcessorInterface
{
    public function process(array &$data, ?string $tag = null): void;
}