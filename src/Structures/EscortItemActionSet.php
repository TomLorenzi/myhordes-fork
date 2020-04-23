<?php


namespace App\Structures;


use App\Entity\EscortActionGroup;
use App\Entity\Item;
use App\Entity\ItemAction;

class EscortItemActionSet
{
    private $escort_action;
    private $items = [];
    private $available_actions = [];
    private $crossed_actions = [];
    private $content = false;
    private $content_available = false;

    public function __construct(EscortActionGroup $set)
    {
        $this->escort_action = $set;
    }

    public function addAction(ItemAction $action, Item $item, bool $available) {

        $this->content = true;
        if ($available) $this->content_available = true;

        if (!isset($this->items[$item->getId()])) {
            $this->items[$item->getId()] = $item;
            $this->available_actions[$item->getId()] = [];
            $this->crossed_actions[$item->getId()] = [];
        }

        if ($available) $this->available_actions[$item->getId()][] = $action;
        else $this->crossed_actions[$item->getId()][] = $action;
    }

    public function getGroup(): EscortActionGroup {
        return $this->escort_action;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array {
        return array_values($this->items);
    }

    public function hasActions(): bool {
        return $this->content;
    }

    public function hasAvailableActions(): bool {
        return $this->content_available;
    }

    /**
     * @param Item $item
     * @return ItemAction[]
     */
    public function getAvailableActions(Item $item): array {
        return $this->available_actions[$item->getId()] ?? [];
    }

    /**
     * @param Item $item
     * @return ItemAction[]
     */
    public function getCrossedActions(Item $item): array {
        return $this->crossed_actions[$item->getId()] ?? [];
    }
}