<?php

namespace App\Event\Game\Items;

use App\Event\Game\GameEvent;

/**
 * @property-read TransferItemData $data
 * @mixin TransferItemData
 */
class TransferItemEvent extends GameEvent {
    protected static function configuration(): string { return TransferItemData::class; }
}