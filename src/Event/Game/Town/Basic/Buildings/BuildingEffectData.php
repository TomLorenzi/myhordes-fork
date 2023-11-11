<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\Building;
use App\Entity\ItemPrototype;

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

    public function addConsumedItem( string|ItemPrototype $item, int $count ): void {
        $name = is_string($item) ? $item : $item->getName();
        $this->consumedItems[ $name ] = ($this->consumedItems[ $name ] ?? 0) + $count;
    }

    public readonly Building $building;
    public readonly ?Building $upgradedBuilding;

    public int $buildingDamage = 0;
    public int $waterDeducted = 0;
    public array $dailyProduceItems = [];
    public array $consumedItems = [];
    public array $destroyed_buildings = [];
    public array $produceDailyBlueprint = [];
}