<?php

namespace App\Event\Traits;

use App\Entity\Item;
use App\Entity\ItemPrototype;

trait ItemProducerTrait
{
    use ItemManagerTrait;

    /** @var Item[] */
    protected array $previous_item_instances = [];

    /**
     * @return Item[]
     */
    public function getFinishedInstances(): array {
        return $this->previous_item_instances;
    }


    public function itemCreationCompleted(): void {
        $this->previous_item_instances = array_merge($this->previous_item_instances, $this->created_item_instances);
        $this->created_item_instances = $this->pending_item_prototypes = [];
    }
}