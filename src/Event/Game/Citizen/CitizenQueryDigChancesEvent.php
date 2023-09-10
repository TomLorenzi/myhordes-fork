<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameEvent;

/**
 * @property-read CitizenDigData $data
 * @mixin CitizenDigData
 */
class CitizenQueryDigChancesEvent extends GameEvent {
	protected static function configuration(): string { return CitizenDigData::class; }
}