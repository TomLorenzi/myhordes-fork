<?php

namespace App\Event\Game\Items;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read TransferItemData $data
 * @mixin TransferItemData
 */
class TransferItemEvent extends GameInteractionEvent {
    protected static function configuration(): string { return TransferItemData::class; }
}