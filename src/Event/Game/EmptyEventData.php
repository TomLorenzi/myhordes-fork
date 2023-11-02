<?php

namespace App\Event\Game;


use App\Entity\Building;
use App\Entity\Citizen;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;

class EmptyEventData
{
    /**
     * @return GameEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup(): void { }
}