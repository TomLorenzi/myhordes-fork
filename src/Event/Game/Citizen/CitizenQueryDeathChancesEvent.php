<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameInteractionEvent;

class CitizenQueryDeathChancesEvent extends GameInteractionEvent {
	protected static function configuration(): string { return CitizenBaseData::class; }
}