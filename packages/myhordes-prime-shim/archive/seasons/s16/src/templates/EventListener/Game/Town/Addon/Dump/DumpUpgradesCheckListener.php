<?php


namespace MyHordes\Prime\EventListener\Game\Town\Addon\Dump;


use App\Entity\ActionCounter;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\EventListener\Game\Town\Addon\Dump\DumpUpgradesCheckListener as BaseDumpUpgradesCheckListener;
use App\Service\TownHandler;
use MyHordes\Prime\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpExtensions', priority: -1)]
#[AsEventListener(event: DumpRetrieveCheckEvent::class, method: 'onCheckDumpExtensions', priority: -1)]
final class DumpUpgradesCheckListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
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
		$event->wood_dump_built = $dump->getLevel() >= 3;
		$event->metal_dump_built = $dump->getLevel() >= 3;
		$event->animal_dump_built = $dump->getLevel() >= 2;
		$event->free_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trashclean_#00', true);
		$event->weapon_dump_built = $dump->getLevel() >= 1;
		$event->food_dump_built = $dump->getLevel() >= 1;
		$event->defense_dump_built = $dump->getLevel() >= 2;
		$event->dump_upgrade_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#06', true);
		$event->ap_cost = $event->citizen->getSpecificActionCounterValue(ActionCounter::ActionTypeDumpInsertion) == 0 ? 0 : ($event->free_dump_built ? 1 : 2);
		$event->skipPropagationTo(BaseDumpUpgradesCheckListener::class, "onCheckDumpExtensions");
    }
}