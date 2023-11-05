<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\Easter\TownActivateEvent;
use App\Event\Game\EventHooks\Easter\TownDeactivateEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: TownActivateEvent::class, method: 'activateTown', priority: 0)]
#[AsEventListener(event: TownDeactivateEvent::class, method: 'deactivateTown', priority: 0)]
final class EasterListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [TownHandler::class];
    }

    public function activateTown(TownActivateEvent $event): void
    {
        $town_handler = $this->getService(TownHandler::class);

        $cross = $town_handler->getBuildingPrototype('small_eastercross_#00');
        if (!$cross) {
            $event->value = false;
            return;
        }

        $gallows = $town_handler->getBuilding($event->town,'r_dhang_#00', false);
        if ($gallows) $gallows->setPrototype( $cross );

        $event->value = true;
    }

    public function deactivateTown(TownDeactivateEvent $event): void
    {
        $town_handler = $this->getService(TownHandler::class);

        $gallows = $town_handler->getBuildingPrototype('r_dhang_#00');
        if (!$gallows) {
            $event->value = false;
            return;
        }

        $cross = $town_handler->getBuilding($event->town,'small_eastercross_#00', false);
        if ($cross) $cross->setPrototype( $gallows );

        $event->value = true;
    }

}