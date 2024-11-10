<?php


namespace App\Structures;


use App\Entity\Item;
use App\Enum\SortDefinitionWord;

class SortDefinition
{
    public function __construct(
        public readonly SortDefinitionWord $word = SortDefinitionWord::Default,
        public ?string $reference = null,
        public int $priority = 0,
    ) {
        if ($this->word !== SortDefinitionWord::Before && $this->word !== SortDefinitionWord::After)
            $this->reference = null;
    }

    public static function atStart( int $priority = 0 ): SortDefinition {
        return new SortDefinition(SortDefinitionWord::Start, priority: $priority);
    }

    public static function atEnd( int $priority = 0 ): SortDefinition {
        return new SortDefinition(SortDefinitionWord::End, priority: $priority);
    }

    public static function before( string $className, int $priority = 0 ): SortDefinition {
        return new SortDefinition(SortDefinitionWord::Before, $className, $priority);
    }

    public static function after( string $className, int $priority = 0 ): SortDefinition {
        return new SortDefinition(SortDefinitionWord::After, $className, $priority);
    }

    public function stableSort(SortDefinition $b): int {
        if (!$this->word->stable() || !$b->word->stable()) return 0;

        if ($this->word === $b->word) return $b->priority <=> $this->priority;
        if ($this->word === SortDefinitionWord::Start) return -1;
        if ($b->word === SortDefinitionWord::Start) return 1;
        if ($this->word === SortDefinitionWord::End) return 1;
        if ($b->word === SortDefinitionWord::End) return -1;

        return 0;
    }

}