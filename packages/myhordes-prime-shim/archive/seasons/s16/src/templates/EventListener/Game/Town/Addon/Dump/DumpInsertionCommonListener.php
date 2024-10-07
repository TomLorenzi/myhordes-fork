<?php


namespace MyHordes\Prime\EventListener\Game\Town\Addon\Dump;


use App\Entity\ActionCounter;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckData;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionExecuteEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\EventListener\Game\Town\Addon\Dump\DumpInsertionCommonListener as BaseDumpInsertionCommonListener;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Prime\Helpers\TownHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpAvailability', priority: -9)]
#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckItems', priority: -19)]

#[AsEventListener(event: BuildingEffectPostAttackEvent::class, method: 'onProcessPostAttackEffect', priority: -5)]
#[AsEventListener(event: DumpInsertionExecuteEvent::class, method: 'onItemHandling', priority: 11)]
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
			TranslatorInterface::class
		];
	}

	public function onCheckDumpAvailability(DumpInsertionCheckEvent $event ): void {
		if ($event->citizen->getBanished() || !$event->dump_built) {
			$event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();
			return;
		}

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);
		/** @var TownHandler $townHandler */
		$townHandler = $this->container->get(TownHandler::class);

		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");
		$cache = [];
		foreach ($event->citizen->getTown()->getBank()->getItems() as $item) {
			if ($item->getBroken()) continue;

			$dumpDef = TownHelper::get_dump_def_for( $item->getPrototype(), $event );
			if ($dumpDef == 0) continue;

			$dumpedItems = $inventoryHandler->fetchSpecificItems($dump->getInventory(), [new ItemRequest($item->getPrototype()->getName())]);

			if (!isset($cache[$item->getPrototype()->getId()]))
				$cache[$item->getPrototype()->getId()] = [
					$item->getPrototype(),
					$item->getCount(),
					$dumpDef,
					empty($dumpedItems) ? 0 : $dumpedItems[0]->getCount()
				];
			else $cache[$item->getPrototype()->getId()][1] += $item->getCount();
		}

		usort( $cache, function(array $a, array $b) {
			return ($a[2] === $b[2]) ? ( $a[0]->getId() < $b[0]->getId() ? -1 : 1 ) : ($a[2] < $b[2] ? 1 : -1);
		} );

		$event->dumpableItems = $cache;
		$event->skipPropagationTo(BaseDumpInsertionCommonListener::class, "onCheckDumpAvailability");
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

		$translator = $this->container->get(TranslatorInterface::class);

		if ($event->quantity > 20) {
			$event->pushError( ErrorHelper::ErrorActionNotAvailable, $translator->trans('Du kannst nicht so viele Gegenstände auf die Müllhalde werfen.', [], 'game') )->stopPropagation();
			return;
		}

		/** @var InventoryHandler $inventoryHandler */
		$inventoryHandler = $this->container->get(InventoryHandler::class);

		// You don't have enough AP (the first dump is free)
		if ($event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) > 0 && $event->citizen->getAp() < $event->ap_cost) {
			$event->pushErrorCode(ErrorHelper::ErrorNoAP)->stopPropagation();
			return;
		}

		// Check if items are available
		$items = $inventoryHandler->fetchSpecificItems( $event->citizen->getTown()->getBank(), [new ItemRequest($proto->getName(), $event->quantity)] );
		if (!$items) {
			$event->pushErrorCode(ErrorHelper::ErrorItemsMissing)->stopPropagation();
		}

		$event->skipPropagationTo(BaseDumpInsertionCommonListener::class, "onCheckItems");
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

		$dump_def = TownHelper::get_dump_def_for($event->check->consumable, $event);

		$dump = $townHandler->getBuilding($event->citizen->getTown(), "small_trash_#00");

		$items = $inventoryHandler->fetchSpecificItems( $event->citizen->getTown()->getBank(), [new ItemRequest($event->check->consumable->getName(), $event->quantity)] );
		// Remove items
		$n = $event->quantity;
		while (!empty($items) && $n > 0) {
			$item = array_pop($items);
			$c = $item->getCount();
			$qtyMoved = min($c, $n);
			$inventoryHandler->forceMoveItem($dump->getInventory(), $item, $qtyMoved);
			$n -= $c;
		}

		// Reduce AP
		if ($event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) > 0)
			$event->citizen->setAp( $event->citizen->getAp() - $event->check->ap_cost );

		// Increase def
		$dump->setTempDefenseBonus( $dump->getTempDefenseBonus() + $event->quantity * $dump_def );

		$event->addedDefense = $event->quantity * $dump_def;

		// Set ActionCounter
		$counter = $event->citizen->getSpecificActionCounter(ActionCounter::ActionTypeDumpInsertion);
		$counter->setCount($counter->getCount() + 1);

		$event->markModified();

		$event->skipPropagationTo(BaseDumpInsertionCommonListener::class, "onItemHandling");
	}

	public function onProcessPostAttackEffect(BuildingEffectEvent $event): void {
		// we want to empty the dump's inventory
		if ($event->building->getPrototype()->getName() !== "small_trash_#00") return;

		$inventoryHandler = $this->container->get(InventoryHandler::class);
		foreach ($event->building->getInventory()->getItems() as $item) {
			$inventoryHandler->forceRemoveItem($item, $item->getCount());
		}
	}
}