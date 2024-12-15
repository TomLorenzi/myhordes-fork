<?php


namespace MyHordes\Prime\EventListener\Game\Citizen;

use App\Entity\ItemPrototype;
use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Citizen\CitizenDeathListener;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenPostDeathEvent::class, method: 'onSpawnSouls',  priority: 10)]
#[AsEventListener(event: CitizenPostDeathEvent::class, method: 'onUninstallGarlands',  priority: 5)]
final class PrimeCitizenDeathListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            InventoryHandler::class,
            ItemFactory::class,
            RandomGenerator::class,
			TownHandler::class,
            PictoHandler::class
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

    public function onUninstallGarlands( CitizenPostDeathEvent $event ): void {
        $base_garland = $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('xmas_gift_#00');

        $installed_garlands = 0;
        if ($base_garland) foreach ( $event->citizen->getHome()->getChest()->getItems() as $item )
            if ($item->getPrototype()->getName() === 'xmas_gift_#01') {
                $this->getService(EntityManagerInterface::class)->persist($item->setPrototype($base_garland));
                $installed_garlands++;
            }

        if ($installed_garlands > 0)
            $this->getService(PictoHandler::class)->give_validated_picto($event->citizen, "r_decofeist_#00", $installed_garlands);
    }

}