<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Enum\SortDefinitionWord;
use App\Service\Actions\Game\AtomProcessors\Require\AtomRequirementProcessor;
use App\Structures\SortDefinition;
use MyHordes\Fixtures\DTO\ArrayDecoratorReadInterface;

abstract class RequirementsAtom implements ArrayDecoratorReadInterface {

    protected array $data;

    public function __construct(
        public readonly SortDefinition $sort = new SortDefinition(),
        $data = []
    ) {
        $this->data = static::afterSerialization( $data );
    }

    abstract public function getClass(): string;

    protected static function beforeSerialization(array $data): array {
        return $data;
    }

    protected static function afterSerialization(array $data): array {
        return $data;
    }

    final public function toArray(): array {
        return [
            'sort' => [
                $this->sort->word->value,
                $this->sort->reference,
                $this->sort->priority
            ],
            'processor' => $this->getClass(),
            'atom' => get_class($this),
            'payload' => static::beforeSerialization( $this->data )
        ];
    }

    public static function fromArray(array $data): RequirementsAtom {
        [
            'sort' => [
                $sort_word, $sort_ref, $sort_priority
            ],
            'processor' => $processor,
            'atom' => $atom,
            'payload' => $payload
        ] = $data;

        if (!is_a( $atom, RequirementsAtom::class, true ))
            throw new \Exception("Requirement atom references invalid self class '$atom'.");

        if (!is_a( $processor, AtomRequirementProcessor::class, true ))
            throw new \Exception("Requirement atom references invalid processor class '$processor'.");

        $instance = new $atom(
            new SortDefinition( SortDefinitionWord::from( $sort_word ), $sort_ref, $sort_priority ),
            static::afterSerialization( $payload )
        );
        if ($instance->getClass() !== $processor && !is_a($processor, $instance->getClass(), true))
            throw new \Exception("Expected requirement atom to be processed by '{$instance->getClass()}', got '{$processor}' instead.");

        return $instance;
    }

    protected function default(string $name): mixed {
        return null;
    }

    public function __set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : $this->default($name);
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