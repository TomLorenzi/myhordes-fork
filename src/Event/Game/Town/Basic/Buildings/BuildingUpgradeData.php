<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\Building;

class BuildingUpgradeData
{
    /**
     * @param Building $building
     * @return BuildingUpgradeEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Building $building ): void {
        $this->building = $building;
    }

    public Building $building;
    public int $defenseIncrement = 0;
    public float $defenseMultiplier = 0.0;
    public int $waterIncrement = 0;
    public array $spawnedBlueprints = [];
}