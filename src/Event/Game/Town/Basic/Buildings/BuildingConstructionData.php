<?php

namespace App\Event\Game\Town\Basic\Buildings;


use App\Entity\Building;
use App\Entity\Citizen;

class BuildingConstructionData
{

    /**
     * @param Building $building
     * @param string|Citizen|null $method
     * @return BuildingConstructionEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Building $building, null|string|Citizen $method = null ): void {
        $this->building = $building;
        if (is_a($method, Citizen::class)) {
            $this->citizen = $method;
            $this->method = 'manual';
        } else {
            $this->citizen = null;
            $this->method = $method;
        }
    }
    public Building $building;

    public int $spawn_well_water = 0;
    public array $pictos = [];
    public ?string $method;
    public ?Citizen $citizen;
}