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
     * @return array<static>
     */
    public function children(): array;

    public function name(): string;
    public function key(): string;

    public function default(): mixed;

    public function fallback(): array;

    /**
     * @template T
     * @param T|null $old
     * @param T $new
     * @return T
     */
    public function merge(mixed $old, mixed $new): mixed;

    public function translationKey(): string;
}