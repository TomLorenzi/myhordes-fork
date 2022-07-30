<?php

namespace MyHordes\Plugins\Interfaces;

abstract class FixtureChainInterface
{
    /**
     * @var FixtureProcessorInterface[]
     */
    private array $processors = [];

    public function addProcessor( FixtureProcessorInterface $if ): void {
        $this->processors[] = $if;
    }

    /**
     * @throws \Exception
     */
    public function data(): array {
        if (empty($this->processors))
            throw new \Exception('Fixture chain has no processors!');
        $data = [];
        foreach ($this->processors as $processor) $processor->process($data);
        return $data;
    }
}