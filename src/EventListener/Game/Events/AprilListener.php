<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\April\DoorResponseEvent;
use App\EventListener\ContainerTypeTrait;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DoorResponseEvent::class, method: 'fakeError', priority: 0)]
final class AprilListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [];
    }

    public function fakeError(DoorResponseEvent $event): void
    {
        if ($event->action === "close")
            $event->value = AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
    }
}