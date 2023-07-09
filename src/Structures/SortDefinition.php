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

}