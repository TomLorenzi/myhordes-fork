<?php

namespace MyHordes\Plugins\Management;

class FixtureSourceLookup
{
    private array $data = [];

    public function addEntry( string $chainClass, string $providerClass, string $tag ): void {
        $this->data[] = [$chainClass,$providerClass,$tag];
    }

    public function findProvidersByTag(string $tag): array {
        return array_values( array_column( array_filter( $this->data, fn($entry) => $entry[2] === $tag ), 1) );
    }

    public function findProvidersByChainClass(string $chainClass): array {
        return array_values( array_column( array_filter( $this->data, fn($entry) => $entry[0] === $chainClass ), 1) );
    }

    public function findChainClassByProvider(string $providerClass): ?string {
        return array_values( array_column( array_filter( $this->data, fn($entry) => $entry[1] === $providerClass ), 0) )[0] ?? null;
    }
}