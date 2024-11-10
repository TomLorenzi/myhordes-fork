<?php


namespace MyHordes\Prime\EventListener\Game\Town\Addon\Dump;


use App\Entity\ActionCounter;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use MyHordes\Prime\Helpers\PrimeLogTemplateHandler;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveExecuteEvent;
use MyHordes\Prime\Helpers\TownHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: DumpRetrieveCheckEvent::class, method: 'onCheckDumpAvailability', priority: -9)]
#[AsEventListener(event: DumpRetrieveCheckEvent::class, method: 'onCheckItems', priority: -19)]

#[AsEventListener(event: DumpRetrieveExecuteEvent::class, method: 'onItemHandling', priority: 11)]
#[AsEventListener(event: DumpRetrieveExecuteEvent::class, method: 'onLogMessages', priority: 0)]
#[AsEventListener(event: DumpRetrieveExecuteEvent::class, method: 'onFlashMessages', priority: 0)]
final class DumpRetrieveCommonListener implements ServiceSubscriberInterface
{
	public function __construct(
		private readonly ContainerInterface $container,
	) {}

	public static function getSubscribedServices(): array
	{
		return [
			InventoryHandler::class,
			EntityManagerInterface::class,
			TownHandler::class,
			TranslatorInterface::class,
			PrimeLogTemplateHandler::class
		];
	}

	public function onCheckDumpAvailability(DumpRetrieveCheckEvent $event ): void {
		if ($event->citizen->getBanished() || !$event->dump_built) {
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();
		}
	}


	/**
	 * @param DumpRetrieveCheckEvent $event
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function onCheckItems( DumpRetrieveCheckEvent $event ): void {
		if ($event->citizen->getBanished() || !$event->dump_built)
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();

		$proto = $event->consumable;

		if (!$proto) {
			$event->pushErrorCode( ErrorHelper::ErrorInvalidRequest )->stopPropagation();
			return;
		}

		$translator = $this->container->get(TranslatorInterface::class);

		if ($event->quantity > 20) {
			$event->pushError( ErrorHelper::ErrorActionNotAvailable, $translator->trans('Du kannst nicht so viele Gegenstände auf die Müllhalde wiederherstellen.', [], 'game') )->stopPropagation();
			return;
		}

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);

		// You don't have enough AP (the first action is free)
		if ($event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) > 0 && $event->citizen->getAp() < $event->ap_cost) {
			$event->pushErrorCode(ErrorHelper::ErrorNoAP)->stopPropagation();
			return;
		}

		// Check if items are available
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);
		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");
		$items = $inventoryHandler->fetchSpecificItems( $dump->getInventory(), [new ItemRequest($proto->getName(), $event->quantity)] );
		if (!$items) {
			$event->pushErrorCode(ErrorHelper::ErrorItemsMissing)->stopPropagation();
		}
	}

	/**
	 * @param DumpRetrieveExecuteEvent $event
	 * @return void
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function onItemHandling(DumpRetrieveExecuteEvent $event ): void {
		if (!$event->check->consumable) return;

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);

		$dump_def = TownHelper::get_dump_def_for($event->check->consumable, $event);

		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");

		$items = $inventoryHandler->fetchSpecificItems( $dump->getInventory(), [new ItemRequest($event->check->consumable->getName(), $event->quantity)] );
		// Move items back into the bank
		$n = $event->quantity;
		while (!empty($items) && $n > 0) {
			$item = array_pop($items);
			$c = $item->getCount();
			$qtyMoved = min($c, $n);
			$inventoryHandler->forceMoveItem($event->citizen->getTown()->getBank(), $item, $qtyMoved);
			$n -= $c;
		}

		// Reduce AP
		if ($event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) > 0)
			$event->citizen->setAp( $event->citizen->getAp() - $event->check->ap_cost );

		// Decrease def
		$dump->setTempDefenseBonus( $dump->getTempDefenseBonus() - $event->quantity * $dump_def );

		$event->removedDefense = $event->quantity * $dump_def;

		// Set ActionCounter
		$counter = $event->citizen->getSpecificActionCounter(ActionCounter::ActionTypeDumpInsertion);
		$counter->setCount($counter->getCount() + 1);

		$event->markModified();
	}


	public function onFlashMessages(DumpRetrieveExecuteEvent $event) {
		$event->addFlashMessage(
			T::__('Du hast {count} x {item} auf der öffentlichen Müllhalde abgerufen. <strong>Die Stadt hat {def} Verteidigungspunkt(e) verloren.</strong>', 'game'), 'notice',
			'game', ['item' => $event->check->consumable, 'count' => $event->check->quantity, 'def' => $event->removedDefense]);
	}

	public function onLogMessages(DumpRetrieveExecuteEvent $event) {
		$itemsForLog = [
			$event->check->consumable->getId() => [
				'item' => $event->check->consumable,
				'count' => $event->quantity
			]
		];
		$dump_def = TownHelper::get_dump_def_for($event->check->consumable, $event);
		$this->container->get(EntityManagerInterface::class)->persist(
			$this->container->get(PrimeLogTemplateHandler::class)->dumpItemsRecover( $event->citizen, $itemsForLog, $event->removedDefense )
		);

		$event->markModified();
	}
}