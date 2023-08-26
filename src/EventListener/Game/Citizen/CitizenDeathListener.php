<?php


namespace App\EventListener\Game\Citizen;

use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Structures\TownConf;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenPostDeathEvent::class, method: 'onSpawnSouls',  priority: 0)]
final class CitizenDeathListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            InventoryHandler::class,
            ItemFactory::class,
            RandomGenerator::class
        ];
    }

    public function onSpawnSouls( CitizenPostDeathEvent $event ): void {
        if ( $event->townConfig->get(TownConf::CONF_FEATURE_SHAMAN_MODE, 'normal') != 'none' ) {
            $minDistance = min(10, 3 + intval($event->town->getDay() * 0.75));
            $maxDistance = min(15, 6 + $event->town->getDay());

            $spawnZone = $this->getService(RandomGenerator::class)->pickLocationBetweenFromList($event->town->getZones()->toArray(), $minDistance, $maxDistance);
            $soulItem = $this->getService(ItemFactory::class)->createItem( "soul_blue_#00");
            $soulItem->setFirstPick(true);
            $this->getService(InventoryHandler::class)->forceMoveItem($spawnZone->getFloor(), $soulItem);
        }
    }

}