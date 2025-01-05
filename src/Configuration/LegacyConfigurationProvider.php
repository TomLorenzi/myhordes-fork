<?php

namespace App\Configuration;

use MyHordes\Plugins\Interfaces\ConfigurationProviderInterface;

abstract class LegacyConfigurationProvider implements ConfigurationProviderInterface
{
    public function __construct(private readonly array $yaml) {}

    final public function data(): array
    {
        return $this->yaml;
    }
}