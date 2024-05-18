<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Service\InventoryHandler;
use App\Structures\ActionHandler\Evaluation;
use App\Translation\T;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\InventorySpaceRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessInventorySpaceRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|InventorySpaceRequirement $data): bool
    {
        if ($data->space <= 0) return true;

        $inventoryHandler = $this->container->get(InventoryHandler::class);

        $inv_full = $data->ignoreInventory || ($inventoryHandler->getFreeSize( $cache->citizen->getInventory() ) < $data->space);
        $trunk_full = ($data->considerTrunk && $cache->citizen->getZone() === null)
            ? ($inventoryHandler->getFreeSize( $cache->citizen->getHome()->getChest() ) < $data->space)
            : null;

        if ($inv_full && $trunk_full === true) {

            if ($data->container)
                $cache->addMessage(T::__('Du brauchst <strong>in deiner Truhe etwas mehr Platz</strong>, wenn du den Inhalt von {item} aufbewahren möchtest.', 'items'), [], 'items');
            else $cache->addMessage(T::__('Du brauchst <strong>in deiner Truhe etwas mehr Platz</strong>.', 'items'), [], 'items');

        } elseif ($inv_full && $trunk_full === null) {

            if ($data->container)
                $cache->addMessage(T::__('Du brauchst <strong>mehr Platz in deinem Rucksack</strong>, um den Inhalt von {item} mitnehmen zu können.', 'items'), [], 'items');
            else $cache->addMessage(T::__('Du brauchst <strong>mehr Platz in deinem Rucksack</strong>.', 'items'), [], 'items');
        }

        return !($inv_full && $trunk_full !== false);
    }
}