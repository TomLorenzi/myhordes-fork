<?php


namespace App\EventListener\Game\Town\Addon\Dump;


use App\Entity\ActionCounter;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckData;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionExecuteEvent;
use App\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpRetrieveExecuteEvent;
use App\Service\TownHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpExtensions', priority: 1)]
#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCalculateDumpDef', priority: 0)]
#[AsEventListener(event: DumpRetrieveCheckEvent::class, method: 'onCheckDumpExtensions', priority: 1)]
#[AsEventListener(event: DumpRetrieveCheckEvent::class, method: 'onCalculateDumpDef', priority: 0)]
final readonly class DumpUpgradesCheckListener implements ServiceSubscriberInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TownHandler::class,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onCheckDumpExtensions(DumpInsertionCheckEvent|DumpRetrieveCheckEvent $event ): void {
        $dump = $this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#00');
        $event->dump_built = (bool)$dump;
        $event->wood_dump_built = $dump?->getLevel() >= 3;
        $event->metal_dump_built = $dump?->getLevel() >= 3;
        $event->animal_dump_built = $dump?->getLevel() >= 2;
        $event->free_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trashclean_#00', true);
        $event->weapon_dump_built = $dump?->getLevel() >= 1;
        $event->food_dump_built = $dump?->getLevel() >= 1;
        $event->defense_dump_built = $dump?->getLevel() >= 2;
        $event->dump_upgrade_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#06', true);
        $event->ap_cost = $event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) == 0 ? 0 : ($event->free_dump_built ? 1 : 2);
    }

    public static function getDumpItemDef(ItemPrototype $proto, DumpInsertionCheckEvent|DumpRetrieveCheckEvent $event): int {
        $baseDef = 0;

        $dumpableItemProperties = [
            'weapon' => [
                'bonus' => $event->weapon_dump_built ? 5 : 0,
                'add' => [
                    'machine_gun_#00',
                    'gun_#00',
                    'chair_basic_#00',
                    'machine_1_#00',
                    'machine_2_#00',
                    'machine_3_#00',
                    'pc_#00'
                ],
                'exclude' => []
            ],
            'defence' => [
                'bonus' => $event->defense_dump_built ? 2 : 0,
                'add' => [],
                'exclude' => []
            ],
            'food' => [
                'bonus' => $event->food_dump_built ? 3 : 0,
                'add' => [],
                'exclude' => []
            ],
            'pet' => [
                'bonus' => $event->animal_dump_built ? 6 : 0,
                'add' => [
                    'tekel_#00',
                    'pet_dog_#00'
                ],
                'exclude' => []
            ],
            'wood' => [
                'bonus' => $event->wood_dump_built ? 1 : 0,
                'add' => [
                    'wood_bad_#00',
                    'wood2_#00'
                ],
                'exclude' => []
            ],
            'metal' => [
                'bonus' => $event->metal_dump_built ? 1 : 0,
                'add' => [
                    'metal_bad_#00',
                    'metal_#00'
                ],
                'exclude' => []
            ]
        ];

        // Each dumpable item gives 1 def point
        foreach ($dumpableItemProperties as $property => $itemList) {
            if (($proto->hasProperty($property) && !in_array($proto->getName() ?? '-', $itemList['exclude'])) || in_array($proto->getName() ?? '-', $itemList['add'])) {
                $baseDef = 1 + $itemList['bonus'];
            }
        }

        // The dump upgrade adds 1 def point
        if ($baseDef > 0 && $event->dump_upgrade_built)
            return $baseDef + 1;
        else return $baseDef;
    }

    public function onCalculateDumpDef(DumpInsertionCheckEvent|DumpRetrieveCheckEvent $event): void {
        if ($event->consumable)
            $event->defense = self::getDumpItemDef( $event->consumable, $event );
    }
}