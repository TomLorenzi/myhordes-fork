<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenWatchData $data
 * @mixin CitizenWatchData
 */
class CitizenQueryNightwatchDefenseEvent extends GameEvent {
	protected static function configuration(): string { return CitizenWatchData::class; }
}