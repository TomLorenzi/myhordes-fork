<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Entity\Citizen;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Configuration\TownSetting;
use App\Enum\SortDefinitionWord;
use App\Structures\SortDefinition;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\ArrayDecoratorReadInterface;

abstract class Atom implements ArrayDecoratorReadInterface {

    protected array $data;
    public readonly SortDefinition $sort;

    protected ?Citizen $contextCitizen = null;
    protected ?TownConf $contextTown = null;

    protected static function defaultSortDefinition(): SortDefinition {
        return new SortDefinition();
    }

    public function __construct(
        ?SortDefinition $sort = null,
        $data = []
    ) {
        $this->sort = $sort ?? self::defaultSortDefinition();
        $this->data = static::afterSerialization( $data );
    }

    final public function withContext(Citizen $citizen, TownConf $town): self {
        $this->contextCitizen = $citizen;
        $this->contextTown = $town;
        return $this;
    }

    abstract public function getClass(): string;

    public static function getAtomClass(): string {
        return Atom::class;
    }

    public static function getAtomProcessorClass(): string {
        return '__';
    }

    protected static function beforeSerialization(array $data): array {
        return $data;
    }

    protected static function afterSerialization(array $data): array {
        return $data;
    }

    final public function toArray(): array {
        $payload = static::beforeSerialization( $this->data );
        array_walk_recursive( $payload, fn(&$value) => $value = match (true) {
            is_a( $value, CitizenProperties::class ) => "cfg://ctp/{$value->value}",
            is_a( $value, TownSetting::class ) => "cfg://twn/{$value->value}",
            default => $value,
        });

        return [
            'sort' => [
                $this->sort->word->value,
                $this->sort->reference,
                $this->sort->priority
            ],
            'processor' => $this->getClass(),
            'atom' => get_class($this),
            'payload' => $payload
        ];
    }

    final public static function fromArray(array $data): Atom {
        [
            'sort' => [
                $sort_word, $sort_ref, $sort_priority
            ],
            'processor' => $processor,
            'atom' => $atom,
            'payload' => $payload
        ] = $data;

        if (!is_a($atom, static::getAtomClass(), true ))
            throw new \Exception("Atom references invalid self class '$atom' (expected to be instance of '" . static::getAtomClass() . "').");

        if (!is_a( $processor, static::getAtomProcessorClass(), true ))
            throw new \Exception("Atom references invalid processor class '$processor' (expected to be instance of '" . static::getAtomProcessorClass() . "')..");

        array_walk_recursive( $payload, fn(&$value) => $value = match (true) {
            is_string( $value ) && str_starts_with( $value, 'cfg://ctp/' ) => CitizenProperties::from( substr( $value, 10 ) ),
            is_string( $value ) && str_starts_with( $value, 'cfg://twn/' ) => TownSetting::from( substr( $value, 10 ) ),
            default => $value
        } );

        $instance = new $atom(
            new SortDefinition( SortDefinitionWord::from( $sort_word ), $sort_ref, $sort_priority ),
            static::afterSerialization( $payload )
        );
        if ($instance->getClass() !== $processor && !is_a($processor, $instance->getClass(), true))
            throw new \Exception("Expected atom to be processed by '{$instance->getClass()}', got '{$processor}' instead.");

        return $instance;
    }

    protected function default(string $name): mixed {
        return null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data) || $this->default($name) !== null;
    }

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        $value = array_key_exists($name, $this->data) ? $this->data[$name] : $this->default($name);

        return match (true) {
            is_a( $value, CitizenProperties::class ) => $this->contextCitizen
                ? $this->contextCitizen->property( $value )
                : $value->default(),
            is_a( $value, TownConf::class ) => $this->contextTown
                ? $this->contextTown->get( $value )
                : $value->default(),
            default => $value
        };
    }

    /**
     * @throws \Exception
     */
    public function __call(string $name, array $arguments): self
    {
        if (count($arguments) !== 1) throw new \Exception('Atom exception: Invalid __call payload.');
        $this->$name = $arguments[0];
        return $this;
    }
}