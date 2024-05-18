<?php

namespace App\Service\Actions\Game\AtomProcessors\Require;

use App\Entity\ItemPrototype;
use App\Service\InventoryHandler;
use App\Structures\ActionHandler\Evaluation;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ItemRequirement;
use MyHordes\Fixtures\DTO\Actions\RequirementsAtom;

class ProcessItemRequirement extends AtomRequirementProcessor
{
    public function __invoke(Evaluation $cache, RequirementsAtom|ItemRequirement $data): bool
    {

        $item_str = $data->property ?? $data->item;
        $source = $cache->citizen->getZone() ? [$cache->citizen->getInventory()] : [$cache->citizen->getInventory(), $cache->citizen->getHome()->getChest()];

        $inventoryHandler = $this->container->get(InventoryHandler::class);

        if ( $cache->conf->get( TownConf::CONF_MODIFIER_POISON_TRANS, false ) && $data->poison !== true )
            $poison = null;
        else $poison = $data->poison;

        $result = $inventoryHandler->fetchSpecificItems( $source, [new ItemRequest($item_str, max($data->count, 1), $data->broken, $poison, $data->isPropertyRequirement())] );

        if ($data->count > 0 && empty($result)) {
            if (!$data->isPropertyRequirement()) {
                $prototype = $cache->em->getRepository(ItemPrototype::class)->findOneByName($item_str);
                if ($prototype) for ($i = 0; $i < $data->count; $i++) $cache->addMissingItem($prototype);
            }
            return false;
        } elseif ($data->count === 0 && !empty($result)) return false;

        if ($data->store)
            foreach ($result as $item)
                for ($i = 0; $i < $item->getCount(); $i++)
                    $cache->addProcessedItem( $data->store, $item->getPrototype() );

        return true;
    }
}