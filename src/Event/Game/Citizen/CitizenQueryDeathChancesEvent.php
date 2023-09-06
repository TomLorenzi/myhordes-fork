<?php

namespace App\Event\Game\Citizen;

use App\Event\Game\GameInteractionEvent;

/**
 * @property bool  $during_attack
 * @property float $deathChance
 * @property float $woundChance
 * @property float $terrorChance
 * @property string $hintSentence
 */
class CitizenQueryDeathChancesEvent extends GameInteractionEvent {
	protected static function configuration(): string { return CitizenBaseData::class; }
}