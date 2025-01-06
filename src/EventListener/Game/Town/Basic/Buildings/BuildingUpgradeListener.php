<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Event\Game\Town\Basic\Buildings\BuildingUpgradeEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingUpgradePreAttackEvent;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingUpgradePreAttackEvent::class, method: 'onProcessPreAttackUpgrade',  priority: 0)]
#[AsEventListener(event: BuildingUpgradePostAttackEvent::class, method: 'onProcessPostAttackUpgrade', priority: 0)]

#[AsEventListener(event: BuildingUpgradePreAttackEvent::class, method: 'onApplyUpgrade', priority: -100)]
#[AsEventListener(event: BuildingUpgradePostAttackEvent::class, method: 'onApplyUpgrade', priority: -100)]
final class BuildingUpgradeListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            RandomGenerator::class,
            InventoryHandler::class,
            ItemFactory::class,
        ];
    }

    public function onProcessPreAttackUpgrade( BuildingUpgradeEvent $event ): void {
        $event->defenseIncrement = match ($event->building->getPrototype()->getName()) {
            'small_gather_#00'  => [0,20,45,75,110,150][ $event->building->getLevel() ] ?? 0,
            'item_home_def_#00' => [0,30,65,115,180,260][ $event->building->getLevel() ] ?? 0,
            'item_boomfruit_#00'  => [0,30,75,150,240,340][ $event->building->getLevel() ] ?? 0,
            'small_door_closed_#00'  => [0,15,45,75][ $event->building->getLevel() ] ?? 0,
            default => $event->defenseIncrement
        };

        $event->defenseMultiplier = match ($event->building->getPrototype()->getName()) {
            'item_tube_#00' => [0, 0.8, 1.6, 2.4, 3.6, 4.8][ $event->building->getLevel() ] ?? 0.0,
            default => $event->defenseMultiplier
        };

        $event->waterIncrement = match ($event->building->getPrototype()->getName()) {
            'small_water_#00'  => [5,20,20,30,30,40][ $event->building->getLevel() ] ?? 0,
            default => $event->waterIncrement
        };
    }

    public function onProcessPostAttackUpgrade( BuildingUpgradeEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {
            case 'small_refine_#01':
                $bps = [
                    [],
                    ['bplan_c_#00' => 4],
                    ['bplan_c_#00' => 2,'bplan_u_#00' => 2],
                    ['bplan_u_#00' => 2,'bplan_r_#00' => 2],
                ];
                $opt_bp = [null,null,'bplan_r_#00','bplan_e_#00'];

                $plans = [];
                foreach (($bps[$event->building->getLevel()] ?? []) as $id => $count)
                    $plans[$id] = [
                        'item' => $id,
                        'count' => ($plans[$id]['count'] ?? 0) + $count
                    ];

                /** @var RandomGenerator $random */
                $random = $this->container->get(RandomGenerator::class);

                $opt = $opt_bp[$event->building->getLevel()] ?? null;
                if ( $opt !== null && $random->chance( 0.5 ) )
                    $plans[$opt] = [
                        'item' => $opt,
                        'count' => ($plans[$opt]['count'] ?? 0) + 1
                    ];

                $event->spawnedBlueprints = $plans;
                break;
            default:
                break;
        }
    }

    public function onApplyUpgrade( BuildingUpgradeEvent $event ): void {
        if ($event->defenseIncrement !== 0 || $event->defenseMultiplier !== 0.0) {
            $event->building->setDefenseBonus(($event->defenseMultiplier * $event->building->getPrototype()->getDefense()) + $event->defenseIncrement );
            $event->markModified();
        }

        if ($event->waterIncrement !== 0) {
            $event->town->setWell( $event->town->getWell() + $event->waterIncrement );
            if ($event->waterIncrement > 0) {
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);
                /** @var LogTemplateHandler $log */
                $log = $this->container->get(LogTemplateHandler::class);

                $em->persist($log->nightlyAttackUpgradeBuildingWell($event->building, $event->waterIncrement));
                $event->markModified();
            }
        }

        if (!empty($event->spawnedBlueprints) && !$event->town->findGazette( $event->town->getDay(), false )?->getReactorExplosion()) {
            /** @var EntityManagerInterface $em */
            $em = $this->container->get(EntityManagerInterface::class);
            /** @var LogTemplateHandler $log */
            $log = $this->container->get(LogTemplateHandler::class);
            /** @var InventoryHandler $inventory */
            $inventory = $this->container->get(InventoryHandler::class);
            /** @var ItemFactory $factory */
            $factory = $this->container->get(ItemFactory::class);

            $plans = [];
            foreach ($event->spawnedBlueprints as ['item' => $item, 'count' => $count]) {
                $plan = ['item' => $item, 'count' => $count];
                for ($i = 0; $i < $count; $i++) {
                    $inventory->forceMoveItem($event->town->getBank(), $itemInstance = $factory->createItem($item));
                    $plan['item'] = $itemInstance->getPrototype()->getId();
                }
                $plans[] = $plan;
            }

            $em->persist( $log->nightlyAttackUpgradeBuildingItems( $event->building, $plans ));

        }
    }

}