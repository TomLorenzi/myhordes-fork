<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Event\Game\GameEvent;

/**
 * @property-read BuildingQueryNightwatchDefenseBonusData $data
 * @mixin BuildingQueryNightwatchDefenseBonusData
 */
class BuildingQueryNightwatchDefenseBonusEvent extends GameEvent {
    protected static function configuration(): string { return BuildingQueryNightwatchDefenseBonusData::class; }
}