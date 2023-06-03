<?php

namespace MyHordes\Fixtures\DTO\Actions;

use App\Entity\AwardPrototype;
use App\Entity\PictoPrototype;
use App\Enum\SortDefinitionWord;
use App\Structures\SortDefinition;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Fixtures\DTO\ArrayDecoratorInterface;
use MyHordes\Fixtures\DTO\ArrayDecoratorReadInterface;
use MyHordes\Fixtures\DTO\Element;

abstract class RequirementsAtom implements ArrayDecoratorReadInterface {



    public function __construct(
        public readonly SortDefinition $sort = new SortDefinition(),
        protected array $data = []
    ) {}

    abstract public function getClass(): string;

    final public function toArray(): array {
        return [
            'sort' => [
                $this->sort->word->value,
                $this->sort->reference,
                $this->sort->priority
            ],
            'processor' => $this->getClass(),
            'atom' => get_class($this),
            'payload' => $this->data
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

        $instance = new $atom(
            new SortDefinition( SortDefinitionWord::from( $sort_word ), $sort_ref, $sort_priority ),
            $payload
        );
        if ($instance->getClass() !== $processor && !is_a($processor, $instance->getClass(), true))
            throw new \Exception("Expected requirement atom to be processed by '{$instance->getClass()}', got '{$processor}' instead.");

        return $instance;
    }
}