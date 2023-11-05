<?php


namespace App\EventListener\Game\Events;

use App\Event\Game\EventHooks\Christmas\NightlyEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: NightlyEvent::class, method: 'santa', priority: 0)]
final class ChristmasListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}
    public static function getSubscribedServices(): array
    {
        return [
            CitizenHandler::class,
            InventoryHandler::class,
            ItemFactory::class
        ];
    }

    public function santa(NightlyEvent $event): void
    {
        //if ((int)date('m') !== 12 || (int)date('j') !== 25) return;
        $citizen_handler   = $this->getService(CitizenHandler::class);
        $inventory_handler = $this->getService(InventoryHandler::class);
        $item_factory      = $this->getService(ItemFactory::class);

        foreach ($event->town->getCitizens() as $citizen) {
            if (!$citizen->getAlive() || $citizen_handler->hasStatusEffect($citizen, 'tg_got_xmas_gift')) continue;

            $citizen_handler->inflictStatus( $citizen, 'tg_got_xmas_gift' );
            $inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( 'chest_christmas_3_#00' ) );
            $inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( 'rp_letter_#00' ) );
        }
    }


}