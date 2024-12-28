<?php

namespace App\Event\Game\Items;


use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Item;

readonly class ForceTransferItemData
{
    /**
     * @param Item $item
     * @param Inventory|null $from
     * @param Inventory|null $to
     * @return ForceTransferItemEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(
        Item $item,
        ?Inventory $from,
        ?Inventory $to
    ): void {
        $this->item = $item;
        $this->from = $from;
        $this->to = $to;
    }

    public Item $item;
    public ?Inventory $from;
    public ?Inventory $to;

}