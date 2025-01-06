<?php


namespace App\EventListener\Game\Action;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\EventActivationMarker;
use App\Entity\ItemPrototype;
use App\Entity\Zone;
use App\Enum\Game\TransferItemModality;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\ItemRequest;
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
            EventProxyService::class,
            ZoneHandler::class,
            ItemFactory::class,
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

            // Tamer Dog Fetch Action
            case 10501: case 10502:

                // The tamer does not work if the door is closed
                if (!$event->citizen->getTown()->getDoor()) {
                    $event->cache->addTag('fail');
                    $event->cache->addTag('door-closed');
                    break;
                }

                $source = $event->type === 10501 ? $event->citizen->getHome()->getChest() : $event->town->getBank();
                $target = $event->citizen->getInventory();

                $item = $event->type === 10501
                    ? $event->target
                    : ($this->getService(InventoryHandler::class)->fetchSpecificItems($event->town->getBank(), [new ItemRequest($event->target->getName())]))[0] ?? null;

                $em = $this->getService(EntityManagerInterface::class);
                if (!$item) {

                    if ($event->type === 10502) {
                        if ($event->item->getPrototype()->getName() === 'tamed_pet_#00' || $event->item->getPrototype()->getName() === 'tamed_pet_drug_#00' )
                            $event->item->setPrototype( $em->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_off_#00']) );
                    }

                    $event->cache->addTag('fail');
                    $event->cache->addTag('impossible');
                    break;
                }

                if (($s = $this->getService(EventProxyService::class)->transferItem($event->citizen, $item, $source, $target, TransferItemModality::Tamer)) === InventoryHandler::ErrorNone) {
                    if ($event->item->getPrototype()->getName() === 'tamed_pet_#00' || $event->item->getPrototype()->getName() === 'tamed_pet_drug_#00' )
                        $event->item->setPrototype( $em->getRepository(ItemPrototype::class)->findOneBy(['name' => 'tamed_pet_off_#00']) );

                    if ($event->type === 10502)
                        $this->getService(EntityManagerInterface::class)->persist(
                            $this->getService(LogTemplateHandler::class)->bankItemTamerTakeLog( $event->citizen, $item->getPrototype(), $item->getBroken() )
                        );

                } else {
                    $event->cache->addTag('fail');
                    $event->cache->addTag('impossible');
                }

                break;

            // Photo_4 action on ruin
            case 12001:
                // Grant blueprint if available on a ruin.
                $zone_handler = $this->getService(ZoneHandler::class);
                $item_factory = $this->getService(ItemFactory::class);
                $em = $this->getService(EntityManagerInterface::class);

                $citizen = $event->citizen;

                if ($citizen->getZone()->getBlueprint() === Zone::BlueprintAvailable && $citizen->getZone()->getBuryCount() <= 0) {
                    // Spawn BP.
                    $bp_name = ($zone_handler->getZoneKm($citizen->getZone()) < 10)
                        ? 'bplan_u_#00'
                        : 'bplan_r_#00';
                    $bp_item_prototype = $em->getRepository(ItemPrototype::class)->findOneBy(['name' => $bp_name]);
                    $bp_item = $item_factory->createItem( $bp_item_prototype );

                    $this->getService(EventProxyService::class)->placeItem($event->citizen, $bp_item, inventories: [$citizen->getInventory(), $citizen->getZone()->getFloor()]);

                    // Set zone blueprint.
                    $citizen->getZone()->setBlueprint(Zone::BlueprintFound);

                    $event->cache->addTag("bp-found");
                    $event->cache->addSpawnedItem($bp_item);

                    $this->getService(EntityManagerInterface::class)->persist(
                        $this->getService(LogTemplateHandler::class)->beyondItemLog(citizen: $event->citizen, item: $bp_item_prototype, toFloor: true)
                    );
                }
                break;
        }
    }

}