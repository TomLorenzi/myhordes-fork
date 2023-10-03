<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenWorkshopOptionsData $data
 * @mixin CitizenWorkshopOptionsData
 */
class CitizenWorkshopOptionsEvent extends GameEvent {
	protected static function configuration(): string { return CitizenWorkshopOptionsData::class; }
}