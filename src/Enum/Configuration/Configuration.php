<?php

namespace App\Enum\Configuration;

interface Configuration
{
    public function abstract(): bool;
    public function parent(): ?Configuration;

    /**
     * @return static[]
     */
    public static function validCases(): array;

    /**
     * @return array<Configuration>
     */
    public function children(): array;

    public function name(): string;
    public function key(): string;

    public function default(): mixed;

    public function fallback(): array;
}