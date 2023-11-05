<?php

namespace App\Event\Game\EventHooks\Common;

use App\Event\Game\EmptyEventData;

class DashboardModifierData extends EmptyEventData
{
    public array $additional_bullets = [];
    public array $additional_situation = [];

    public function addBullet(string $bullet, bool $checked): void {
        $this->additional_bullets[] = [$bullet,$checked];
    }

    public function addSituation(string $situation, bool $warning): void {
        $this->additional_situation[] = [$situation,$warning];
    }
}