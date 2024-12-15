<?php


namespace MyHordes\Prime\EventListener\Game\Events;

use App\Event\Game\GameEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift1Event;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift2Event;
use MyHordes\Prime\Event\Game\EventHooks\Christmas\NightlyGift3Event;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: NightlyGift1Event::class, method: 'santa1', priority: 0)]
#[AsEventListener(event: NightlyGift2Event::class, method: 'santa2', priority: 0)]
#[AsEventListener(event: NightlyGift3Event::class, method: 'santa3', priority: 0)]
final class PrimeChristmasListener implements ServiceSubscriberInterface
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

    private function present(GameEvent $event, array $items, string $status): void {
        $citizen_handler   = $this->getService(CitizenHandler::class);
        $inventory_handler = $this->getService(InventoryHandler::class);
        $item_factory      = $this->getService(ItemFactory::class);

        foreach ($event->town->getCitizens() as $citizen) {
            if (!$citizen->getAlive() || $citizen_handler->hasStatusEffect($citizen, $status)) continue;

            $citizen_handler->inflictStatus( $citizen, $status );
            foreach ( $items as $item )
                $inventory_handler->forceMoveItem( $citizen->getHome()->getChest(), $item_factory->createItem( $item ) );
        }
    }

    public function santa1(NightlyGift1Event $event): void
    {
        $this->present( $event, ['rp_letter_#00'], 'tg_got_xmas1');
    }

    public function santa2(NightlyGift2Event $event): void
    {
        $this->present( $event, ['rp_letter_#00','christmas_candy_#00','xmas_gift_#00'], 'tg_got_xmas2');
    }

    public function santa3(NightlyGift3Event $event): void
    {
        $this->present( $event, ['chest_christmas_3_#00'], 'tg_got_xmas3');
    }


}