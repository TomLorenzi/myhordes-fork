<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameInteractionEvent;

/**
 * @property-read CitizenWatchData $data
 * @mixin CitizenWatchData
 */
class CitizenQueryNightwatchInfoEvent extends GameInteractionEvent {
	protected static function configuration(): string { return CitizenWatchData::class; }
}