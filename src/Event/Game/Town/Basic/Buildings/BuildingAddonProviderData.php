<?php

namespace App\Event\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;

class BuildingAddonProviderData
{

    /**
     * @param ItemPrototype $in
     * @return BuildingAddonProviderData
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( ): void {}

    public function addAddon(string $label, string $name, string $route, ?int $order = null) {
        $this->list[$name] = [$label, $route, $order ?? count($this->list)];
    }

    public array $list = [];
}