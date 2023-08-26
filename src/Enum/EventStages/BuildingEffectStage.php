<?php

namespace App\Enum\EventStages;

use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreDefaultEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreUpgradeEvent;

enum BuildingEffectStage {
    case BeforeDailyUpgrade;
    case BeforeAttack;
    case BeforeDefaultEvents;
    case AfterAttack;


    /**
     * @return string
     * @psalm-return class-string<BuildingEffectEvent>
     */
    public function eventClass(): string {
        return match ($this) {
            self::BeforeDailyUpgrade => BuildingEffectPreUpgradeEvent::class,
            self::BeforeAttack => BuildingEffectPreAttackEvent::class,
            self::BeforeDefaultEvents => BuildingEffectPreDefaultEvent::class,
            self::AfterAttack => BuildingEffectPostAttackEvent::class,
        };
    }
}