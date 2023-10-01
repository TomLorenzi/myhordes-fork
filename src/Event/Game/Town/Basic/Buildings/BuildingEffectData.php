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
        $this->upgradedBuilding = $upgradedBuilding;
    }

    public readonly Building $building;
    public readonly ?Building $upgradedBuilding;

    public int $buildingDamage = 0;
    public int $waterDeducted = 0;
    public array $dailyProduceItems = [];
    public array $consumedItems = [];

    public array $produceDailyBlueprint = [];
}