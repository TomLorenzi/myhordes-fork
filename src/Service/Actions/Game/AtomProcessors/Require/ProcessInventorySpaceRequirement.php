<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\Item;
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
        $ignore = array_values(array_filter([
            $data->ignoreSource ? $cache->item : null,
            $data->ignoreTarget ? $cache->target : null,
        ], fn($e) => $e !== null && is_a($e, Item::class)));


        $inv_full = $data->ignoreInventory || ($inventoryHandler->getFreeSize( $cache->citizen->getInventory(), $ignore ) < $data->space);
        if ($data->heavy && !$data->ignoreInventory) {
            $inv_has_heavy = $inventoryHandler->countHeavyItems( $cache->citizen->getInventory(), $ignore );
        } else $inv_has_heavy = false;

        $trunk_full = ($data->considerTrunk && $cache->citizen->getZone() === null)
            ? ($inventoryHandler->getFreeSize( $cache->citizen->getHome()->getChest(), $ignore ) < $data->space)
            : null;

        if ($inv_full && $trunk_full === true) {

            if ($data->container)
                $cache->addMessage(T::__('Du brauchst <strong>in deiner Truhe etwas mehr Platz</strong>, wenn du den Inhalt von {item} aufbewahren möchtest.', 'items'), [], 'items');
            else $cache->addMessage(T::__('Du brauchst <strong>in deiner Truhe etwas mehr Platz</strong>.', 'items'), [], 'items');

        } elseif ($inv_full && $trunk_full === null) {

            if ($data->container)
                $cache->addMessage(T::__('Du brauchst <strong>mehr Platz in deinem Rucksack</strong>, um den Inhalt von {item} mitnehmen zu können.', 'items'), [], 'items');
            else $cache->addMessage(T::__('Du brauchst <strong>mehr Platz in deinem Rucksack</strong>.', 'items'), [], 'items');

        } elseif ($inv_has_heavy)
            $cache->addMessage(T::__('Du kannst keinen weiteren schweren Gegenstand tragen!', 'game'), [], 'game');

        return !($inv_full && $trunk_full !== false) && (!$data->heavy || $data->ignoreInventory || !$inv_has_heavy);
    }
}