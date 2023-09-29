<?php


namespace App\EventListener\Game\Town\Addon\Dump;


use App\Controller\Town\TownController;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckData;
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
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpAvailability', priority: -10)]
#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckItems', priority: -20)]

#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onItemHandling', priority: 0)]
#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onLogMessages', priority: 10)]
#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onFlashMessages', priority: 10)]
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
			TownHandler::class,
        ];
    }

    public function onCheckDumpAvailability(DumpInsertionCheckEvent $event ): void {
		if ($event->citizen->getBanished() || !$event->dump_built) {
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();
			return;
		}

		$cache = [];
		foreach ($event->citizen->getTown()->getBank()->getItems() as $item) {
			if ($item->getBroken()) continue;

			$dumpDef = $this->get_dump_def_for( $item->getPrototype(), $event );
			if ($dumpDef == 0) continue;

			if (!isset($cache[$item->getPrototype()->getId()]))
				$cache[$item->getPrototype()->getId()] = [
					$item->getPrototype(),
					$item->getCount(),
					$dumpDef
				];
			else $cache[$item->getPrototype()->getId()][1] += $item->getCount();
		}

		usort( $cache, function(array $a, array $b) {
			return ($a[2] === $b[2]) ? ( $a[0]->getId() < $b[0]->getId() ? -1 : 1 ) : ($a[2] < $b[2] ? 1 : -1);
		} );

		$event->dumpableItems = $cache;
    }

	/**
	 * @param DumpInsertionCheckEvent $event
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function onCheckItems( DumpInsertionCheckEvent $event ): void {

		if ($event->citizen->getBanished() || !$event->dump_built)
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();

		$proto = $event->consumable;

		if (!$proto) {
			$event->pushErrorCode( ErrorHelper::ErrorInvalidRequest )->stopPropagation();
			return;
		}

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);

		// It's not free, and you don't have enough AP
		if ($event->ap_cost > 0 && $event->citizen->getAp() < $event->quantity * $event->ap_cost) {
			$event->pushErrorCode(ErrorHelper::ErrorNoAP)->stopPropagation();
			return;
		}

		// Check if items are available
		$items = $inventoryHandler->fetchSpecificItems( $event->citizen->getTown()->getBank(), [new ItemRequest($proto->getName(), $event->quantity)] );
		if (!$items) {
			$event->pushErrorCode(ErrorHelper::ErrorItemsMissing)->stopPropagation();
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

		$dump_def = $this->get_dump_def_for($event->check->consumable, $event);

		$items = $inventoryHandler->fetchSpecificItems( $event->citizen->getTown()->getBank(), [new ItemRequest($event->check->consumable->getName(), $event->quantity)] );
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
			$event->citizen->setAp( $event->citizen->getAp() - $event->quantity * $event->check->ap_cost );

		// Increase def
		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");
		$dump->setTempDefenseBonus( $dump->getTempDefenseBonus() + $event->quantity * $dump_def );

        $event->markModified();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onLogMessages(DumpInsertionExecuteEvent $event ): void {
		$itemsForLog = [
			$event->check->consumable->getId() => [
				'item' => $event->check->consumable,
				'count' => $event->quantity
			]
		];
		$dump_def = $this->get_dump_def_for($event->check->consumable, $event);
        $this->container->get(EntityManagerInterface::class)->persist(
            $this->container->get(LogTemplateHandler::class)->dumpItems( $event->citizen, $itemsForLog, $event->quantity * $dump_def )
        );

        $event->markModified();
    }

    public function onFlashMessages(DumpInsertionExecuteEvent $event ): void {
		$dump_def = $this->get_dump_def_for($event->check->consumable, $event);

        $event->addFlashMessage(
            T::__('Du hast {count} x {item} auf der öffentlichen Müllhalde abgeladen. <strong>Die Stadt hat {def} Verteidigungspunkt(e) dazugewonnen.</strong>', 'game'), 'notice',
            'game', ['item' => $event->check->consumable, 'count' => $event->check->quantity, 'def' => $event->check->quantity * $dump_def]);
    }

	protected function get_dump_def_for( ItemPrototype $proto, DumpInsertionExecuteEvent|DumpInsertionCheckEvent $event ): int {

		$check = $event;
		if (is_a($event, DumpInsertionExecuteEvent::class)) $check = $event->check;

		$improved = $check->dump_upgrade_built;
		/** @var DumpInsertionCheckData $check */

		// Weapons
		if ($proto->hasProperty('weapon') || in_array( $proto->getName(), [
				'machine_gun_#00', 'gun_#00', 'chair_basic_#00', 'machine_1_#00', 'machine_2_#00', 'machine_3_#00', 'pc_#00'
			] ) )
			return $check->weapon_dump_built ? ($improved ? 1 : 0) + 5 : 0;

		// Defense
		if ($proto->hasProperty('defence') && $proto->getName() !== 'tekel_#00' && $proto->getName() !== 'pet_dog_#00'
			&& $proto->getName() !== 'concrete_wall_#00' && $proto->getName() !== 'table_#00')
			return ($improved ? 5 : 4) + ( $check->defense_dump_built ? 2 : 0 );

		// Food
		if ($proto->hasProperty('food'))
			return $check->food_dump_built ? ($improved ? 1 : 0) + 3 : 0;

		// Wood
		if ($proto->getName() === 'wood_bad_#00' || $proto->getName() === 'wood2_#00')
			return $check->wood_dump_built ? ($improved ? 1 : 0) + 1 : 0;

		// Metal
		if ($proto->getName() === 'metal_bad_#00' || $proto->getName() === 'metal_#00')
			return $check->metal_dump_built ? ($improved ? 1 : 0) + 1 : 0;

		// Animals
		if ($proto->hasProperty('pet'))
			return $check->animal_dump_built ? ($improved ? 1 : 0) + 6 :  0;

		return 0;
	}
}