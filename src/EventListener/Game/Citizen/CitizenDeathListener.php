<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\Town;
use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Structures\Math;
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
			$coef = $event->townConfig->get(TownConf::CONF_MODIFIER_SOUL_GENERATION_COEF, 1.00);
            $maxDistance = Math::Clamp($coef * ($event->town->getDay() + 4), 5, 20);
            $minDistance = Math::Clamp($coef * $event->town->getDay(), 4, 8);

            $spawnZone = $this->getService(RandomGenerator::class)->pickLocationBetweenFromList($event->town->getZones()->toArray(), $minDistance, $maxDistance);
            $soulItem = $this->getService(ItemFactory::class)->createItem( "soul_blue_#00");
            $soulItem->setFirstPick(true);
            $this->getService(InventoryHandler::class)->forceMoveItem($spawnZone->getFloor(), $soulItem);
            $spawnZone->setSoulPositionOffset( mt_rand(0,3) );
        }
    }

}