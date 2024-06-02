<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenQueryParameterData $data
 * @mixin CitizenQueryParameterData
 */
class CitizenQueryParameterEvent extends GameEvent {
	protected static function configuration(): string { return CitizenQueryParameterData::class; }
}