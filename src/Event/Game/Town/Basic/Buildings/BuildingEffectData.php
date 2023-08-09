<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\Building;

class BuildingEffectData
{
    /**
     * @param Building $building
     * @param ?Building $upgradedBuilding
     * @return BuildingEffectEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Building $building, ?Building $upgradedBuilding ): void {
        $this->building = $building;
        $this->upgradedBuilding = $building;
    }

    public readonly Building $building;
    public readonly ?Building $upgradedBuilding;

    public int $waterDeducted = 0;
    public array $dailyProduceItems = [];

    public bool $produceDailyBlueprint = false;
}