<?php

namespace App\Service\Actions\Game\AtomProcessors\Effect;

use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Enum\ActionHandler\ItemDropTarget;
use App\Enum\ItemPoisonType;
use App\Service\ActionHandler;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Structures\ActionHandler\Execution;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use App\Translation\T;
use MyHordes\Fixtures\DTO\Actions\Atoms\Effect\ItemEffect;
use MyHordes\Fixtures\DTO\Actions\Atoms\Requirement\ItemRequirement;
use MyHordes\Fixtures\DTO\Actions\EffectAtom;
use MyHordes\Fixtures\DTO\Actions\RequirementsDataContainer;

class ProcessItemEffect extends AtomEffectProcessor
{
    public function __invoke(Execution $cache, EffectAtom|ItemEffect $data): void
    {
        /** @var InventoryHandler $ih */
        $ih = $this->container->get(InventoryHandler::class);
        /** @var ItemFactory $if */
        $if = $this->container->get(ItemFactory::class);
        /** @var RandomGenerator $rg */
        $rg = $this->container->get(RandomGenerator::class);
        /** @var EventProxyService $proxy */
        $proxy = $this->container->get(EventProxyService::class);

        if (!$cache->citizen->getZone())
            $floor_inventory = $cache->citizen->getHome()->getChest();
        elseif ($cache->citizen->getZone()->getX() === 0 && $cache->citizen->getZone()->getY() === 0)
            $floor_inventory = $cache->citizen->getTown()->getBank();
        elseif (!$cache->getTargetRuinZone())
            $floor_inventory = $cache->citizen->getZone()->getFloor();
        else
            $floor_inventory = $cache->getTargetRuinZone()->getFloor();

        if ($data->consumeItem !== null && $data->consumeItemCount > 0) {
            $source = $cache->citizen->getZone() ? [$cache->citizen->getInventory()] : [$cache->citizen->getInventory(), $cache->citizen->getHome()->getChest()];
            $requirements = $cache->getAction()?->getRequirements() ?? [];

            $item_req = null;
            foreach ($requirements as $requirement)
                if ($requirement->getAtoms()) {
                    $container = (new RequirementsDataContainer())->fromArray([['atomList' => $requirement->getAtoms()]]);
                    foreach ( $container->findRequirements( ItemRequirement::class ) as $item_requirement ) {
                        /** @var ItemRequirement|null $item_requirement */
                        if ($item_requirement->item !== $data->consumeItem) continue;
                        $item_req = $item_requirement;
                    }
                }

            $poison = (
                $item_req?->poison || $cache->conf->get( TownConf::CONF_MODIFIER_POISON_TRANS, false )
            ) ? null : false;
            $items = $ih->fetchSpecificItems( $source,
                [new ItemRequest( name: $data->consumeItem, count: $data->consumeItemCount, poison: $poison )]);

            foreach ($items as $consume_item) {

                if ($consume_item->getPoison()->poisoned()) {
                    if ($consume_item->getPoison() === ItemPoisonType::Deadly && ($cache->getAction()->getPoisonHandler() & ItemAction::PoisonHandlerConsume) > 0) $cache->addFlag('kill_by_poison');
                    if ($consume_item->getPoison() === ItemPoisonType::Infectious && ($cache->getAction()->getPoisonHandler() & ItemAction::PoisonHandlerConsume) > 0) $cache->addFlag('infect_by_poison');
                    if ($cache->conf->get( TownConf::CONF_MODIFIER_POISON_TRANS, false ) && ($cache->getAction()->getPoisonHandler() & ItemAction::PoisonHandlerTransgress)) $cache->addFlag("transgress_poison_{$consume_item->getPoison()->value}");
                }

                $ih->forceRemoveItem( $consume_item );
                $cache->addConsumedItem($consume_item);
                $cache->addTag('item-consumed');
            }
        }

        if (!empty($data->spawn) && $data->spawnCount > 0) {
            for ($i = 0; $i < $data->spawnCount; $i++ ) {
                [$proto,$count] = $rg->pickEntryFromRawRandomArray( $data->spawn );
                $force = false;

                switch ($data->spawnAt) {
                    case ItemDropTarget::DropTargetFloor:
                        $targetInv = [ $floor_inventory, $cache->citizen->getInventory(), $floor_inventory ];
                        $force = true;
                        break;
                    case ItemDropTarget::DropTargetFloorOnly:
                        $targetInv = [ $floor_inventory ];
                        $force = true;
                        break;
                    case ItemDropTarget::DropTargetRucksack:
                        $targetInv = [ $cache->citizen->getInventory() ];
                        $force = true;
                        break;
                    case ItemDropTarget::DropTargetPreferRucksack:
                        $targetInv = [ $cache->citizen->getInventory(), $floor_inventory ];
                        $force = true;
                        break;
                    case ItemDropTarget::DropTargetDefault:
                    default:
                        $targetInv = [$cache->originalInventory ?? null, $cache->citizen->getInventory(), $floor_inventory, $cache->citizen->getZone() ? null : $cache->citizen->getTown()->getBank() ];
                        break;
                }

                if ($proto && $count > 0) {
                    for ($j = 0; $j < $count; $j++) {
                        if ($proxy->placeItem( $cache->citizen, $item = $if->createItem( $proto ), $targetInv, $force)) {
                            $cache->addSpawnedItem($item);
                        } else {
                            $cache->registerError( ActionHandler::ErrorActionImpossible );
                            return;
                        }
                    }
                }
            }
        }

        if ($data->consumeSource && $cache->item) {
            $ih->forceRemoveItem( $cache->item );
            $cache->addConsumedItem($cache->item);
        } elseif ($data->morphSource && $cache->item) {
            if ($data->morphSourceType) {
                $prototype = $cache->em->getRepository(ItemPrototype::class)->findOneBy(['name' => $data->morphSourceType]);
                if ($prototype) {
                    $cache->setItemMorph($cache->originalPrototype, $prototype);
                    $cache->item->setPrototype( $prototype );
                }
            }

            if ($data->breakSource !== null) $cache->item->setBroken( $data->breakSource );
            if ($data->poisonSource !== null) $cache->item->setPoison( $data->poisonSource );
            if ($data->equipSource !== null) {
                $cache->item->setEssential( $data->equipSource );
                if ($data->equipSource) $ih->forceMoveItem( $cache->citizen->getInventory(), $cache->item );
            }
        }

        if ($data->spawnTarget && is_a($cache->target, ItemPrototype::class)) {
            if ($i = $proxy->placeItem( $cache->citizen, $if->createItem( $cache->target ), [ $cache->citizen->getInventory(), $floor_inventory ], true)) {
                if ($i !== $cache->citizen->getInventory())
                    $cache->addMessage( T::__('Der Gegenstand, den du soeben gefunden hast, passt nicht in deinen Rucksack, darum bleibt er erstmal am Boden...', 'game'), translationDomain: 'game' );
                $cache->addSpawnedItem($cache->target);
            }
        } elseif ($data->consumeTarget && is_a($cache->target, Item::class)) {
            $ih->forceRemoveItem( $cache->target);
            $cache->addConsumedItem($cache->target);
        } elseif ($data->morphTarget && is_a($cache->target, Item::class)) {
            if ($data->morphTargetType) {
                $prototype = $cache->em->getRepository(ItemPrototype::class)->findOneBy(['name' => $data->morphTargetType]);
                if ($prototype) {
                    $cache->setItemMorph($cache->originalTargetPrototype, $prototype, true);
                    $cache->target->setPrototype( $prototype );
                }
            }

            if ($data->breakTarget !== null) $cache->target->setBroken( $data->breakTarget );
            if ($data->poisonTarget !== null) $cache->target->setPoison( $data->poisonTarget );
            if ($data->equipTarget !== null) {
                $cache->target->setEssential( $data->equipTarget );
                if ($data->equipTarget) $ih->forceMoveItem( $cache->citizen->getInventory(), $cache->target );
            }
        }
    }
}