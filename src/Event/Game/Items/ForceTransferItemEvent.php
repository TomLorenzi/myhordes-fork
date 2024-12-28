<?php

namespace App\Event\Game\Items;

use App\Event\Game\GameEvent;

/**
 * @property-read ForceTransferItemData $data
 * @mixin ForceTransferItemData
 */
class ForceTransferItemEvent extends GameEvent {
    protected static function configuration(): string { return ForceTransferItemData::class; }
}