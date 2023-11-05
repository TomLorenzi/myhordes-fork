<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\April\CitizenActivateEvent;
use App\Event\Game\EventHooks\April\DoorResponseEvent;
use App\EventListener\ContainerTypeTrait;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenActivateEvent::class, method: 'activateCitizen', priority: 0)]
#[AsEventListener(event: DoorResponseEvent::class, method: 'fakeError', priority: 0)]
final class AprilListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            InventoryHandler::class,
            ItemFactory::class
        ];
    }

    public function activateCitizen(CitizenActivateEvent $event): void
    {
        if (!$event->citizen->getAlive()) {
            $event->value = true;
            return;
        }

        $inv_handler  = $this->getService(InventoryHandler::class);
        $item_factory = $this->getService(ItemFactory::class);

        $inv_handler->forceMoveItem( $event->citizen->getHome()->getChest(), $item_factory->createItem( 'april_drug_#00' ) );

        $event->value = true;
    }

    public function fakeError(DoorResponseEvent $event): void
    {
        if ($event->action === "close")
            $event->value = AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
    }
}