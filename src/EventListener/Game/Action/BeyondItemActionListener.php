<?php


namespace App\EventListener\Game\Action;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\EventActivationMarker;
use App\Entity\Zone;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class BeyondItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            RandomGenerator::class,
            TownHandler::class,
            PictoHandler::class,
            InventoryHandler::class,
            CitizenHandler::class,
            LogTemplateHandler::class,
        ];
    }

    
    
    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {
            // Discover a random ruin
            case 12:
            {
                $list = [];
                foreach ($event->citizen->getTown()->getZones() as $zone)
                    if ($zone->getDiscoveryStatus() === Zone::DiscoveryStateNone && $zone->getPrototype())
                        $list[] = $zone;

                $selected = $this->getService(RandomGenerator::class)->pick($list);
                if ($selected) {
                    $upgraded_map = $this->getService(TownHandler::class)->getBuilding($event->citizen->getTown(),'item_electro_#00', true);
                    $event->cache->setTargetZone($selected);
                    $event->cache->addTag('zone');
                    $selected->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                    if ($upgraded_map) $selected->setZombieStatus( Zone::ZombieStateExact );
                    else $selected->setZombieStatus( max( $selected->getZombieStatus(), Zone::ZombieStateEstimate ) );
                }
                break;

            }

            // Sandballs, bitches!
            case 20: {

                if ($event->target === null) {
                    // Hordes-like - there is no target, there is only ZUUL
                    $list = $event->citizen->getZone()->getCitizens()->filter( function(Citizen $c) use ($event): bool {
                        return $c->getAlive() && $c !== $event->citizen && ($c->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $event->citizen->getId())->getLast() === null || $c->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $event->citizen->getId())->getLast()->getTimestamp() < (time() - 1800));
                    } )->getValues();
                    $sandball_target = $this->getService(RandomGenerator::class)->pick( $list );

                } else $sandball_target = $event->target;

                if (!$this->getService(EntityManagerInterface::class)->getRepository(EventActivationMarker::class)->findOneBy(['town' => $event->citizen->getTown(), 'active' => true, 'event' => 'christmas']))
                    $sandball_target = null;

                if ($sandball_target !== null) {
                    $this->getService(PictoHandler::class)->give_picto($event->citizen, 'r_sandb_#00');

                    $this->getService(InventoryHandler::class)->forceRemoveItem( $event->item );
                    $event->cache->addConsumedItem($event->item);

                    $event->cache->setTargetCitizen($sandball_target);
                    $sandball_target->getSpecificActionCounter(ActionCounter::ActionTypeSandballHit, $event->citizen->getId())->increment();

                    $hurt = !$this->getService(CitizenHandler::class)->isWounded($sandball_target) && $this->getService(RandomGenerator::class)->chance( $event->townConfig->get(TownConf::CONF_MODIFIER_SANDBALL_NASTYNESS, 0.0) );
                    if ($hurt) $this->getService(CitizenHandler::class)->inflictWound($sandball_target);

                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->sandballAttack( $event->citizen, $sandball_target, $hurt ) );
                    $this->getService(EntityManagerInterface::class)->persist($sandball_target);


                } else $event->cache->addTag('fail');

                break;
            }

            // Flare
            case 21 :
                $criteria = new Criteria();
                $criteria->andWhere($criteria->expr()->eq('town', $event->citizen->getTown()));
                $criteria->andWhere($criteria->expr()->neq('discoveryStatus', Zone::DiscoveryStateCurrent));
                $zones = $this->getService(EntityManagerInterface::class)->getRepository(Zone::class)->matching($criteria)->getValues();
                if(count($zones) > 0) {
                    /** @var Zone $zone */
                    $zone = $this->getService(RandomGenerator::class)->pick($zones);
                    $zone->setDiscoveryStatus(Zone::DiscoveryStateCurrent);
                    $zone->setZombieStatus( max( $zone->getZombieStatus(), $this->getService(TownHandler::class)->getBuilding($event->citizen->getTown(), 'item_electro_#00', true) ? Zone::ZombieStateExact : Zone::ZombieStateEstimate ) );
                    $this->getService(EntityManagerInterface::class)->persist($zone);
                    $this->getService(InventoryHandler::class)->forceRemoveItem( $event->item );
                    $event->cache->addConsumedItem($event->item);
                    $event->cache->addTag($zone->getPrototype() ? 'flare_ok_ruin' : 'flare_ok');
                    $event->cache->setTargetZone($zone);
                } else {
                    $event->cache->addTag('flare_fail');
                }
                break;
        }
    }

}