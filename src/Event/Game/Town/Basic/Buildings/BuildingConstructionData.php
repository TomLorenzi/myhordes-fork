<?php

namespace App\Event\Game\Town\Basic\Buildings;


use App\Entity\Building;

class BuildingConstructionData
{

    /**
     * @param Building $building
     * @return BuildingConstructionEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Building $building ): void {
        $this->building = $building;
    }
    public Building $building;

    public int $spawn_well_water = 0;
    public array $pictos = [];
}