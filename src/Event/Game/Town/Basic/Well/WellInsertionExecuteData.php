<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Entity\ItemPrototype;
use App\Event\Traits\FlashMessageTrait;

class WellInsertionExecuteData
{
    use FlashMessageTrait;

    public readonly WellInsertionCheckData $check;

    public readonly ?ItemPrototype $original_prototype;

    /**
     * @param WellInsertionCheckData $check
     * @return WellInsertionExecuteEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(WellInsertionCheckData|WellInsertionCheckEvent $check): void {
        $this->check = is_a( $check, WellInsertionCheckData::class ) ? $check : $check->data;
        $this->original_prototype = $this->check->consumable?->getPrototype();
    }
}