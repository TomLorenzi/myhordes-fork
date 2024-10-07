<?php


namespace MyHordes\Prime\EventListener\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradeEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePreAttackEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Town\Basic\Buildings\BuildingEffectListener;
use App\Structures\ItemRequest;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;

#[AsEventListener(event: BuildingEffectPreAttackEvent::class, method: 'onProcessPreAttackEffect',  priority: -5)]
#[AsEventListener(event: BuildingEffectPostAttackEvent::class, method: 'onProcessPostAttackEffect', priority: -5)]
#[AsEventListener(event: BuildingUpgradePreAttackEvent::class, method: 'onProcessPreAttackUpgradeEffect',  priority: -5)]
#[AsEventListener(event: BuildingUpgradePostAttackEvent::class, method: 'onProcessPostAttackUpgradeEffect', priority: 1)]
final class PrimeBuildingEffectListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            //PictoHandler::class,
            //DoctrineCacheService::class,
            TownHandler::class,
            RandomGenerator::class,
            //GameProfilerService::class
            InventoryHandler::class,
            ItemFactory::class,
            //CitizenHandler::class
        ];
    }

    public function onProcessPreAttackEffect( BuildingEffectEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {
            case 'item_boomfruit_#00':
                if ($event->building->getLevel() <= 0) break;

                // Attempt to deduct explosive grapefruits from the bank to increase defense
                $grapefruits_needed = min(5, $event->building->getLevel());
                $inventory_handler = $this->getService(InventoryHandler::class);

                $items = $inventory_handler->fetchSpecificItems( $event->town->getBank(), [new ItemRequest('boomfruit_#00', $grapefruits_needed)] );

                if ($items) {
                    $n = $grapefruits_needed;
                    while (!empty($items) && $n > 0) {
                        $item = array_pop($items);
                        $c = $item->getCount();
                        $inventory_handler->forceRemoveItem( $item, $n );
                        $n -= $c;
                    }

                    $event->addConsumedItem( 'boomfruit_#00', $grapefruits_needed );

                } else $event->building->setTempDefenseBonus(0 - $event->building->getDefenseBonus());

                break;
            default:
                break;
        }
    }

    public function onProcessPostAttackEffect( BuildingEffectEvent $event ): void {

        $prev_items = $event->dailyProduceItems;

        switch ($event->building->getPrototype()->getName()) {

            case 'small_strategy_#01':
                /** @var RandomGenerator $random */
                $random = $this->container->get(RandomGenerator::class);

                $bps = [
                    ['bplan_u_#00'],
                    ['bplan_u_#00','bplan_c_#00'],
                    ['bplan_u_#00','bplan_r_#00'],
                    ['bplan_r_#00','bplan_r_#00'],
                    array_filter(['bplan_r_#00', $random->chance(0.1) ? 'bplan_e_#00' : null]),
                ];

                $event->produceDailyBlueprint = array_merge( $event->produceDailyBlueprint, $bps[$event->building->getLevel()] );
                break;

            // Remove item spawns by the default effects
            case 'small_appletree_#00':
                unset($prev_items['apple_#00']);
                break;
            case 'small_chicken_#00':
                unset($prev_items['egg_#00']);
                break;
            case 'item_vegetable_tasty_#00':
                unset($prev_items['vegetable_#00']);
                unset($prev_items['vegetable_tasty_#00']);
                break;
        }

        $event->dailyProduceItems = $prev_items;

		$inventoryHandler = $this->container->get(InventoryHandler::class);
		if($event->building->getPrototype()->getName() === "item_vegetable_tasty_#00") {
			$cadavers = $inventoryHandler->fetchSpecificItems($event->building->getInventory(), [new ItemRequest('cadaver_#00')]);
			$maximizeProd = count($cadavers) > 0;
			foreach ($cadavers as $cadaver) {
				$inventoryHandler->forceRemoveItem($cadaver, $cadaver->getCount());
			}
		} else {
			$maximizeProd = false;
		}

        $items = match ($event->building->getPrototype()->getName()) {
            'small_appletree_#00'   => in_array( 'item_digger_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                ? [ 'apple_#00'     => mt_rand(3,5) ]    // with fertilizer
                : [ 'apple_#00'     => mt_rand(2,4) ],   // without fertilizer
            'small_chicken_#00'     => [ 'egg_#00' => mt_rand(2,4) ],
            'item_vegetable_tasty_#00' => in_array( 'item_digger_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                // with fertilizer
                ? [
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)) ], // Level 0
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)), 'fruit_sub_part_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'ryebag_#00' => ($maximizeProd ? 4 : mt_rand(2,4)) ], // Level 1
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)), 'fruit_sub_part_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'ryebag_#00' => ($maximizeProd ? 4 : mt_rand(2,4)), 'fungus_#00' => ($maximizeProd ? 3 : mt_rand(1,3)) ], // Level 2
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)), 'fruit_sub_part_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'ryebag_#00' => ($maximizeProd ? 4 : mt_rand(2,4)), 'fungus_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'boomfruit_#00' => ($maximizeProd ? 3 : mt_rand(2,3)) ], // Level 3
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)), 'fruit_sub_part_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'ryebag_#00' => ($maximizeProd ? 4 : mt_rand(2,4)), 'fungus_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'boomfruit_#00' => ($maximizeProd ? 3 : mt_rand(2,3)), 'apple_#00' => ($maximizeProd ? 3 : mt_rand(1,3)) ], // Level 4
                    [ 'vegetable_#00' => ($maximizeProd ? 8 : mt_rand(6,8)), 'vegetable_tasty_#00' => ($maximizeProd ? 5 : mt_rand(3,5)), 'fruit_sub_part_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'ryebag_#00' => ($maximizeProd ? 4 : mt_rand(2,4)), 'fungus_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'boomfruit_#00' => ($maximizeProd ? 3 : mt_rand(2,3)), 'apple_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'pumpkin_tasty_#00' => ($maximizeProd ? 2 : mt_rand(1,2)) ], // Level 5
                ][$event->building->getLevel()] ?? []
                // without fertilizer
                : [
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)) ], // Level 0
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)), 'fruit_sub_part_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'ryebag_#00' => ($maximizeProd ? 3 : mt_rand(1,3)) ], // Level 1
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)), 'fruit_sub_part_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'ryebag_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'fungus_#00' => ($maximizeProd ? 2 : mt_rand(1,2)) ], // Level 2
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)), 'fruit_sub_part_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'ryebag_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'fungus_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'boomfruit_#00' => ($maximizeProd ? 2 : mt_rand(1,2)) ], // Level 3
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)), 'fruit_sub_part_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'ryebag_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'fungus_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'boomfruit_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'apple_#00' => ($maximizeProd ? 2 : mt_rand(1,2)) ], // Level 4
                    [ 'vegetable_#00' => ($maximizeProd ? 7 : mt_rand(4,7)), 'vegetable_tasty_#00' => ($maximizeProd ? 2 : mt_rand(0,2)), 'fruit_sub_part_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'ryebag_#00' => ($maximizeProd ? 3 : mt_rand(1,3)), 'fungus_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'boomfruit_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'apple_#00' => ($maximizeProd ? 2 : mt_rand(1,2)), 'pumpkin_tasty_#00' => 1 ], // Level 5
                ][$event->building->getLevel()] ?? [],
            'item_pet_pig_#00'        => [ 'meat_#00' => mt_rand(2,4) ],
            'item_pumpkin_raw_#00' => in_array( 'item_digger_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                // with fertilizer
                ? ( in_array( 'small_scarecrow_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                    ? [ 'pumpkin_tasty_#00'     => mt_rand(2,4) ]   // With scarecrow
                    : [ 'pumpkin_tasty_#00'     => mt_rand(1,3) ]   // Without scarecrow
                )
                // without fertilizer
                : ( in_array( 'small_scarecrow_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                    ? [ 'pumpkin_tasty_#00'     => mt_rand(1,3) ]   // With scarecrow
                    : [ 'pumpkin_tasty_#00'     => mt_rand(1,2) ]   // Without scarecrow
                ),
            default => []
        };

        if (!empty($items)) {
            foreach ($event->dailyProduceItems as $item => $count)
                $items[$item] = ($items[$item] ?? 0) + $count;
            $event->dailyProduceItems = $items;
            $event->markModified();
        }

    }

    public function onProcessPreAttackUpgradeEffect( BuildingUpgradeEvent $event ): void {
        $event->defenseIncrement = match ($event->building->getPrototype()->getName()) {
            'item_boomfruit_#00'  => [0,30,75,150,240,340][ $event->building->getLevel() ] ?? 0,
            'small_door_closed_#00'  => [0,15,30,45][ $event->building->getLevel() ] ?? 0,
            default => 0
        };

        $event->defenseMultiplier = match ($event->building->getPrototype()->getName()) {
            'item_tube_#00' => [0, 0.8, 1.6, 2.4, 3.6, 4.8][ $event->building->getLevel() ] ?? 1.0,
            default => 1.0
        };
    }

    public function onProcessPostAttackUpgradeEffect( BuildingUpgradeEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {
            default:
                break;
        }
    }
}