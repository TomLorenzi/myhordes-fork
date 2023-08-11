<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\Building;

class BuildingDestructionData
{

    /**
     * @param Building $building
     * @param string $method
     * @return BuildingDestructionEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Building $building, string $method ): void {
        $this->building = $building;
        $this->method = $method;
    }

    public readonly Building $building;
    public readonly string $method;
}