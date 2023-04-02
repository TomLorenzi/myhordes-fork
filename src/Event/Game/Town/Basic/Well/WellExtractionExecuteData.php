<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Entity\Item;

class WellExtractionExecuteData
{
    public readonly WellExtractionCheckData $check;

    /** @var Item[] */
    public array $created_items = [];

    /**
     * @param WellExtractionCheckData $check
     * @return WellExtractionExecuteEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(WellExtractionCheckData|WellExtractionCheckEvent $check): void {
        $this->check = is_a( $check, WellExtractionCheckData::class ) ? $check : $check->data;
    }

    public function addItem( Item $item ): void {
        $this->created_items[] = $item;
    }
}