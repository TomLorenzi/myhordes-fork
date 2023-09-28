<?php


namespace App\EventListener\Game\Town\Addon\Dump;


use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Service\TownHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DumpInsertionCheckEvent::class, method: 'onCheckDumpExtensions', priority: 0)]
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
    public function onCheckDumpExtensions(DumpInsertionCheckEvent $event ): void {
        $event->dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#00');
		$event->wood_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#01');
		$event->metal_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#02');
		$event->animal_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_howlingbait_#00');
		$event->free_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trashclean_#00');
		$event->weapon_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#03');
		$event->food_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#04');
		$event->defense_dump_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#05');
		$event->dump_upgrade_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_trash_#06');
		$event->ap_cost = $event->free_dump_built ? 0 : 1;
    }
}