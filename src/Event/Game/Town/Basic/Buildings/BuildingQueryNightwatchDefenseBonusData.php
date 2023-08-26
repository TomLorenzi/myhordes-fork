<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\Item;

class BuildingQueryNightwatchDefenseBonusData
{

    /**
     * @param Item $item
     * @return BuildingQueryNightwatchDefenseBonusEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( Item $item ): void {
        $this->item = $item;
    }

    public readonly Item $item;

    public int $defense = 0;
    public array $buildings = [];
    public array $bonus = [];
    public array $building_bonus_map = [];
}