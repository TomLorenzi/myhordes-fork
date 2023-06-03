<?php


namespace App\Structures\ActionHandler;


use App\Entity\Citizen;
use App\Entity\Item;
use App\Entity\ItemPrototype;

class Evaluation
{
    private array $missing_items;

    public function __construct(
        public readonly Citizen $citizen,
        public readonly ?Item $item
    ) { }

    public function addMissingItem(ItemPrototype $prototype): void {
        $this->missing_items[] = $prototype;
    }

    public function getMissingItems(): array {
        return $this->missing_items;
    }
}