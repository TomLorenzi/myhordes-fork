<?php

namespace MyHordes\Plugins\Management;

use ArrayHelpers\Arr;
use MyHordes\Plugins\Interfaces\ConfigurationProviderInterface;

class ConfigurationStorage
{
    private array $segments = [];

    public function addSegment( string $segment, ConfigurationProviderInterface $provider): void {
        $this->segments[$segment] = array_replace_recursive(
            $provider->data(),
            $this->segments[$segment] ?? []
        );
    }

    public function getSegment( string $segment, mixed $default = null ): mixed {
        return Arr::get( $this->segments, $segment, $default );
    }

    public function hasSegment( string $segment ): bool {
        return Arr::has( $this->segments, $segment );
    }
}