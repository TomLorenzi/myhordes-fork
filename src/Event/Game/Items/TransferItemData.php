<?php

namespace App\Event\Game\Items;


use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Enum\Game\TransferItemType;

class TransferItemData
{
    /**
     * @param Item $item
     * @param Citizen|null $actor
     * @param Inventory|null $from
     * @param Inventory|null $to
     * @param TransferItemModality $modality
     * @param array $options
     * @return TransferItemEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(
        Item $item,
        ?Citizen $actor,
        ?Inventory $from,
        ?Inventory $to,
        TransferItemModality $modality = TransferItemModality::None,
        array $options = [],
    ): void {
        $this->item = $item;
        $this->actor = $actor;
        $this->from = $from;
        $this->to = $to;
        $this->modality = $modality;
        $this->options = array_filter( $options, fn(TransferItemOption $o) => $o );
    }

    public readonly Item $item;
    public readonly ?Citizen $actor;
    public readonly ?Inventory $from;
    public readonly ?Inventory $to;
    public readonly TransferItemModality $modality;
    public readonly array $options;

    public int $error_code = 0;

    public bool $invokeBankLock = false;
    public TransferItemType $type_from = TransferItemType::Unknown;
    public TransferItemType $type_to = TransferItemType::Unknown;
}