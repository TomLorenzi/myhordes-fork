<?php

namespace App\Event\Game\Town\Basic\Well;

class WellExtractionCheckData
{
    use WellUpgradesTrait;

    /**
     * @param int $taking
     */
    public function setup( int $taking = 1 ): void {
        $this->trying_to_take = $taking;
    }
    public int $trying_to_take = 1;

    public int $already_taken = 0;

    public int $allowed_to_take = 0;
}