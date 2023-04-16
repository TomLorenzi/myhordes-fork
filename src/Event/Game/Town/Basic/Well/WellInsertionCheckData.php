<?php

namespace App\Event\Game\Town\Basic\Well;

use App\Entity\Item;

class WellInsertionCheckData
{
    use WellUpgradesTrait;

    public ?Item $consumable;

    public int $water_content = 0;
}