<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;

class BuildingCatapultItemTransformData
{

    /**
     * @param ItemPrototype $in
     * @return BuildingCatapultItemTransformData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( ItemPrototype $in ): void {
        $this->in = $in;
    }

    public readonly ItemPrototype $in;

    public ?ItemPrototype $out = null;
}