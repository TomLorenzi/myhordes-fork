<?php

namespace MyHordes\Fixtures\Interfaces;

abstract class FixtureChainInterface
{
    /**
     * @var FixtureProcessorInterface[]
     */
    private array $processors = [];

    public function addProcessor( FixtureProcessorInterface $if ): void {
        $this->processors[] = $if;
    }

    public function data(): array {
        $data = [];
        foreach ($this->processors as $processor) $processor->process($data);
        return $data;
    }
}