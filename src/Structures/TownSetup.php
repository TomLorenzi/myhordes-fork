<?php


namespace App\Structures;

/**
 * @property string $typeDeriveFrom
 * @property string $nameLanguage
 * @property-read bool $derives
 * @property-read bool $seeds
 */
class TownSetup
{
    public function __construct(
        public string $type,
        public ?string $name = null,
        public ?string $language = null,
        protected ?string $nameLanguage = null,
        public ?int $population = null,
        protected ?string $typeDeriveFrom = null,
        public array $customConf = [],
        public int $seed = -1,
        public ?string $nameMutator = null
    ) {}

    /**
     * @throws \Exception
     */
    public function __get(string $name)
    {
        return match ($name) {
            'typeDeriveFrom' => $this->typeDeriveFrom ?? $this->type,
            'nameLanguage' => $this->nameLanguage ?? $this->language,

            'derives' => $this->typeDeriveFrom !== null && $this->typeDeriveFrom !== $this->type,
            'seeds' => $this->seed > 0,
            default => throw new \Exception("Getting invalid property '$name'")
        };
    }

    /**
     * @throws \Exception
     */
    public function __set(string $name, mixed $value): void {
        match ($name) {
            'typeDeriveFrom' => $this->typeDeriveFrom = $value,
            'nameLanguage' => $this->nameLanguage = $value,
            default => throw new \Exception("Setting invalid property '$name'")
        };
    }
}