<?php


namespace MyHordes\Prime\EventListener\Game\Citizen;

use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Citizen\CitizenDeathListener;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenPostDeathEvent::class, method: 'onSpawnSouls',  priority: 10)]
final class PrimeCitizenDeathListener implements ServiceSubscriberInterface
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
            RandomGenerator::class,
			TownHandler::class
        ];
    }

    public function onSpawnSouls( CitizenPostDeathEvent $event ): void {

        $event->skipPropagationTo( CitizenDeathListener::class, 'onSpawnSouls' );

        if ( $event->townConfig->get(TownConf::CONF_FEATURE_SHAMAN_MODE, 'normal') != 'none' ) {
            $minDistance = min(10, 3 + intval($event->town->getDay() * 0.75));
            $maxDistance = min(15, 6 + $event->town->getDay());
            if ($this->getService(TownHandler::class)->getBuilding($event->town, 'small_spa4souls_#00', true)) {
                $minDistance = min($minDistance, 5);
                $maxDistance = min($maxDistance, 11);
            }

            $spawnZone = $this->getService(RandomGenerator::class)->pickLocationBetweenFromList($event->town->getZones()->toArray(), $minDistance, $maxDistance);
            $soulItem = $this->getService(ItemFactory::class)->createItem( "soul_blue_#00");
            $soulItem->setFirstPick(true);
            $this->getService(InventoryHandler::class)->forceMoveItem($spawnZone->getFloor(), $soulItem);
        }
    }

}