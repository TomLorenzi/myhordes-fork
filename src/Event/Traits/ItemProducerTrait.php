<?php

namespace App\Event\Traits;

use App\Entity\Item;
use App\Entity\ItemPrototype;

trait ItemProducerTrait
{
    /** @var Item[] */
    protected array $previous_item_instances = [];

    /** @var Item[] */
    protected array $created_item_instances = [];

    /**
     * @var (ItemPrototype|string)[]
     */
    protected array $pending_item_prototypes = [];

    /**
     * @return Item[]
     */
    public function getFinishedInstances(): array {
        return $this->previous_item_instances;
    }

    /**
     * @return Item[]
     */
    public function getCreatedInstances(): array {
        return $this->created_item_instances;
    }

    /**
     * @return (ItemPrototype|string)[]
     */
    public function getPendingInstances(): array {
        return $this->pending_item_prototypes;
    }


    /**
     * Adds an item to the list of items created at the end of the event chain
     * @param Item|ItemPrototype|string $item
     * @param int $count
     * @return void
     */
    public function addItem( Item|ItemPrototype|string $item, int $count = 1 ): void {
        // If count is zero, do nothing
        // If count is below zero, redirect to removeItem()
        if ($count === 0) return;
        elseif ($count < 0) {
            $this->removeItem( $item, -$count );
            return;
        }

        if (is_a( $item, Item::class )) {
            // If the passed object is an instanced item, the count must be one, because there is no way to insert the
            // same instance multiple times
            if ($count !== 1) throw new \LogicException('Cannot add multiple versions of an already instanced item.');

            // If the item is already in the instance list, cancel
            if (array_filter( $this->created_item_instances, fn(Item $i) => $i === $item )) return;

            // Add item to instance list
            $this->created_item_instances[] = $item;

        } else for ($i = 0; $i < $count; ++$i) $this->pending_item_prototypes[] = $item;
    }

    public function removeItem( Item|ItemPrototype|string $item, int $count = 1 ): void
    {
        // If count is zero, do nothing
        // If count is below zero, redirect to addItem()
        if ($count === 0) return;
        elseif ($count < 0) {
            $this->addItem( $item, -$count );
            return;
        }

        if (is_a( $item, Item::class )) {
            // If the passed object is an instanced item, the count must be one, because the same item can not have been
            // added multiple times
            if ($count !== 1) throw new \LogicException('Cannot remove multiple versions of an already instanced item.');

            // Remove item from the list if it has been added before
            $this->created_item_instances = array_filter( $this->created_item_instances, fn(Item $i) => $i !== $item );
        } else $this->pending_item_prototypes = array_filter( $this->pending_item_prototypes, function( string|ItemPrototype $p ) use ($item, &$count) {
            // If we have no more items to remove, cancel
            if ($count <= 0) return true;

            // Normalize to class name strings
            $item = is_string($item) ? $item : $item::class;
            $p = is_string($p) ? $p : $p::class;

            // If we have found a matching item, reduce the count and remove it from the list
            if ($item === $p) {
                --$count;
                return false;
            } else return true;
        });
    }

    public function itemCreationCompleted(): void {
        $this->previous_item_instances = array_merge($this->previous_item_instances, $this->created_item_instances);
        $this->created_item_instances = $this->pending_item_prototypes = [];
    }
}