<?php

namespace App\Event\Game\Town\Maintenance;

use App\Event\Game\GameEvent;

/**
 * @property-read TownContentMigrationData $data
 * @mixin TownContentMigrationData
 */
class TownContentMigrationEvent extends GameEvent {
    protected static function configuration(): string { return TownContentMigrationData::class; }
}