<?php


namespace App\EventListener\Game\Town\Addon\Dump;


use App\Controller\Town\TownController;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionExecuteEvent;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpExtension', priority: -10)]
#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckItems', priority: -20)]

#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onItemHandling', priority: 100)]
#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onLogMessages', priority: 90)]
#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onFlashMessages', priority: 80)]
final class DumpInsertionCommonListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            InventoryHandler::class,
            EntityManagerInterface::class,
            LogTemplateHandler::class,
			TownHandler::class
        ];
    }

    public function onCheckDumpExtension(DumpInsertionCheckEvent $event ): void {
        if (!$event->dump_built)
            $event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();
    }

    /**
     * @param DumpInsertionCheckEvent $event
     * @return void
     */
    public function onCheckItems( DumpInsertionCheckEvent $event ): void {
		if ($event->citizen->getBanished() || !$event->dump_built)
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();

		$proto = $event->consumable;

		if (!$proto) {
			$event->pushErrorCode( ErrorHelper::ErrorInvalidRequest )->stopPropagation();
		}
    }

    /**
     * @param DumpInsertionExecuteEvent $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onItemHandling(DumpInsertionExecuteEvent $event ): void {
        if (!$event->check->consumable) return;

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);
		/** @var EntityManagerInterface $entityManager */
		$entityManager = $this->container->get(EntityManagerInterface::class);

		// TODO: Get Dump Def for Prototype
		$dump_def = $this->get_dump_def_for($event->check->consumable, $event);

		// It's not free, and you don't have enough AP
		if (!$event->check->free_dump_built && $event->citizen->getAp() < $event->quantity) {
			$event->pushErrorCode(ErrorHelper::ErrorNoAP)->stopPropagation();
			return;
		}

		// Check if items are available
		$items = $inventoryHandler->fetchSpecificItems( $event->citizen->getTown()->getBank(), [new ItemRequest($event->original_prototype->getName(), $event->quantity)] );
		if (!$items) {
			$event->pushErrorCode(ErrorHelper::ErrorItemsMissing)->stopPropagation();
			return;
		}

		// Remove items
		$n = $event->quantity;
		while (!empty($items) && $n > 0) {
			$item = array_pop($items);
			$c = $item->getCount();
			$inventoryHandler->forceRemoveItem( $item, $n );
			$n -= $c;
		}

		// Reduce AP
		if (!$event->check->free_dump_built)
			$event->citizen->setAp( $event->citizen->getAp() - $event->quantity );

		// Increase def
		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");
		$dump->setTempDefenseBonus( $dump->getTempDefenseBonus() + $event->quantity * $dump_def );

		/*$entityManager->persist($event->citizen);
		$entityManager->flush();*/

        $event->markModified();
    }

	protected function get_dump_def_for( ItemPrototype $proto, DumpInsertionExecuteEvent $event ): int {
		$improved = $event->check->dump_upgrade_built;
		// Weapons
		if ($proto->hasProperty('weapon') || in_array( $proto->getName(), [
				'machine_gun_#00', 'gun_#00', 'chair_basic_#00', 'machine_1_#00', 'machine_2_#00', 'machine_3_#00', 'pc_#00'
			] ) )
			return ($improved ? 2 : 1) + ( $event->check->weapon_dump_built ? 5 : 0 );

		// Defense
		if ($proto->hasProperty('defence') && $proto->getName() !== 'tekel_#00' && $proto->getName() !== 'pet_dog_#00'
			&& $proto->getName() !== 'concrete_wall_#00' && $proto->getName() !== 'table_#00')
			return ($improved ? 5 : 4) + ( $event->check->defense_dump_built ? 2 : 0 );

		// Food
		if ($proto->hasProperty('food'))
			return ($improved ? 2 : 1) + ( $event->check->food_dump_built ? 3 : 0 );

		// Wood
		if ($proto->getName() === 'wood_bad_#00' || $proto->getName() === 'wood2_#00')
			return ($improved ? 2 : 1) + ( $event->check->wood_dump_built ? 1 : 0 );

		// Metal
		if ($proto->getName() === 'metal_bad_#00' || $proto->getName() === 'metal_#00')
			return ($improved ? 2 : 1) + ( $event->check->metal_dump_built ? 1 : 0 );

		// Animals
		if ($proto->hasProperty('pet'))
			return ($improved ? 2 : 1) + ( $event->check->animal_dump_built ? 6 : 0 );

		return 0;
	}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onLogMessages(DumpInsertionExecuteEvent $event ): void {
		$itemsForLog = [
			$event->original_prototype->getId() => [
				'item' => $event->original_prototype,
				'count' => $event->quantity
			]
		];
		$dump_def = $this->get_dump_def_for($event->original_prototype, $event);
        $this->container->get(EntityManagerInterface::class)->persist(
            $this->container->get(LogTemplateHandler::class)->dumpItems( $event->citizen, $itemsForLog, $event->quantity * $dump_def )
        );

        $event->markModified();
    }

    public function onFlashMessages(DumpInsertionExecuteEvent $event ): void {
		$dump_def = $this->get_dump_def_for($event->original_prototype, $event);

        $event->addFlashMessage(
            T::__('Du hast {count} x {item} auf der öffentlichen Müllhalde abgeladen. <strong>Die Stadt hat {def} Verteidigungspunkt(e) dazugewonnen.</strong>', 'game'), 'notice',
            'game', ['item' => $event->original_prototype, 'count' => $event->check->quantity, 'def' => $event->check->quantity * $dump_def]);
    }
}