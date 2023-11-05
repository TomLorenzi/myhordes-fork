<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\Arma\DoorResponseEvent;
use App\Event\Game\EventHooks\Arma\WatchtowerModifierEvent;
use App\EventListener\ContainerTypeTrait;
use App\Response\AjaxResponse;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: DoorResponseEvent::class, method: 'fakeError', priority: 0)]
#[AsEventListener(event: WatchtowerModifierEvent::class, method: 'fakeWatchtower', priority: 0)]
final class ArmaListener implements ServiceSubscriberInterface
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
            $event->value = AjaxResponse::error( 666666 );
    }

    public function fakeWatchtower(WatchtowerModifierEvent $event): void
    {
        $d = $event->town->getDay() + $event->dayOffset;
        $event->min = $event->min * ($d + mt_rand(0, 4));
        $event->max = $event->max * ($d + mt_rand(3, 8));
    }
}