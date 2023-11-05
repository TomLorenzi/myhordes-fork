<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\Common\AutoDoorEvent;
use App\EventListener\ContainerTypeTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: AutoDoorEvent::class, method: 'autoCloseDoor', priority: 0)]
final class CommonListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}
    public static function getSubscribedServices(): array
    {
        return [];
    }

    public function autoCloseDoor(AutoDoorEvent $event): void
    {
        if(!$event->town->getDevastated()) $event->town->setDoor(false);
    }
}