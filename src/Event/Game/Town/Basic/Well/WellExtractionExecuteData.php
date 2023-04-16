<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Entity\Item;
use App\Event\Traits\FlashMessageTrait;
use App\Event\Traits\ItemProducerTrait;

class WellExtractionExecuteData
{
    use ItemProducerTrait, FlashMessageTrait;

    public readonly WellExtractionCheckData $check;

    /**
     * @param WellExtractionCheckData $check
     * @return WellExtractionExecuteEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(WellExtractionCheckData|WellExtractionCheckEvent $check): void {
        $this->check = is_a( $check, WellExtractionCheckData::class ) ? $check : $check->data;
    }
}