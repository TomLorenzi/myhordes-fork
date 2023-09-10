<?php

namespace App\Event\Game\Actions;

use App\Event\Game\GameEvent;

/**
 * @property-read ActionData $data
 * @mixin ActionData
 */
class CustomActionProcessorEvent extends GameEvent {
	protected static function configuration(): string { return ActionData::class; }
}