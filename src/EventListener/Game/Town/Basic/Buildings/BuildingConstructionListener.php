<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\CitizenStatus;
use App\Entity\Complaint;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Zone;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Service\CitizenHandler;
use App\Service\DoctrineCacheService;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onSetUpBuildingInstance', priority: 0)]
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onStoreInGPS', priority: -1)]

#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onConfigureWellEffect', priority: -15)]
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onExecuteWellEffect', priority: -19)]

#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onConfigurePictoEffect', priority: -25)]
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onExecutePictoEffect', priority: -29)]

#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onExecuteSpecialEffect', priority: -105)]
final class BuildingConstructionListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            PictoHandler::class,
            DoctrineCacheService::class,
            TownHandler::class,
            GameProfilerService::class,
            InventoryHandler::class,
            ItemFactory::class,
            CitizenHandler::class
        ];
    }

    public function onSetUpBuildingInstance( BuildingConstructionEvent $event ): void {
        $event->building->setComplete(true);
        $event->building->setConstructionDate(new \DateTime());
        $event->building->setAp($event->building->getPrototype()->getAp());
        $event->building->setHp($event->building->getPrototype()->getHp());
        $event->building->setDefense($event->building->getPrototype()->getDefense());
        $event->markModified();
    }

    public function onStoreInGPS( BuildingConstructionEvent $event ): void {
        if ($event->method !== null) {
            $this->container->get(GameProfilerService::class)->recordBuildingConstructed( $event->building->getPrototype(), $event->town, $event->citizen, $event->method );
            $event->markModified();
        }
    }

    public function onConfigureWellEffect( BuildingConstructionEvent $event ): void {
        $event->spawn_well_water = match ($event->building->getPrototype()->getName()) {
            'small_derrick_#00'      =>  50,
            'small_water_#01'        =>  40,
            'small_eden_#00'         =>  70,
            'small_water_#00'        =>   5,
            'small_derrick_#01'      => 150,
            'item_tube_#01'          =>   2,
            'item_firework_tube_#00' =>  15,
            'small_rocketperf_#00'   =>  60,
            'small_waterdetect_#00'  => 100,
            default => $event->spawn_well_water
        };
    }

    public function onExecuteWellEffect( BuildingConstructionEvent $event ): void {
        if ($event->spawn_well_water > 0) {
            $event->town->setWell( $event->town->getWell() + $event->spawn_well_water );
            $this->container->get(EntityManagerInterface::class)->persist(
                $this->container->get(LogTemplateHandler::class)->constructionsBuildingCompleteWell( $event->building, $event->spawn_well_water )
            );
            $event->markModified();
        }
    }

    public function onConfigurePictoEffect( BuildingConstructionEvent $event ): void {
        // Buildings which give pictos
        $pictos = match ($event->building->getPrototype()->getName()) {
            'small_castle_#00' =>  ['r_ebcstl_#00','r_ebuild_#00'],
            'small_pmvbig_#00' =>  ['r_ebpmv_#00','r_ebuild_#00'],
            'small_wheel_#00'  =>  ['r_ebgros_#00','r_ebuild_#00'],
            'small_crow_#00'   =>  ['r_ebcrow_#00','r_ebuild_#00'],
            default => []
        };

        // If this is a child of fundament, give a picto
        $parent = $event->building->getPrototype()->getParent();
        while($parent != null) {
            if ($parent->getName() === "small_building_#00") {
                $pictos[] = 'r_wondrs_#00';
                break;
            }
            $parent = $parent->getParent();
        }

        if (!empty($pictos))
            $event->pictos = array_merge($event->pictos, $pictos);
    }

    public function onExecutePictoEffect( BuildingConstructionEvent $event ): void {
        if (!empty($event->pictos)) {
            /** @var PictoHandler $handler */
            $handler = $this->container->get(PictoHandler::class);
            /** @var DoctrineCacheService $cache */
            $cache = $this->container->get(DoctrineCacheService::class);
            foreach ($event->town->getCitizens() as $target_citizen) {
                if (!$target_citizen->getAlive()) continue;

                foreach ($event->pictos as $picto)
                    $handler->give_picto($target_citizen, $cache->getEntityByIdentifier( PictoPrototype::class, $picto ));
            }
            $event->markModified();
        }
    }

    public function onExecuteSpecialEffect( BuildingConstructionEvent $event ): void {
        switch ($event->building->getPrototype()->getName()) {
            /*case 'small_fireworks_#00':*/case 'small_balloon_#00':
            $all = $event->building->getPrototype()->getName() === 'small_balloon_#00';
            /** @var TownHandler $townHandler */
            $townHandler = $this->container->get(TownHandler::class);
            
            $state = $townHandler->getBuilding($event->town, 'item_electro_#00', true) ? Zone::ZombieStateExact : Zone::ZombieStateEstimate;
            foreach ($event->town->getZones() as $zone)
                if ($all || $zone->getPrototype()) {
                    $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                    $zone->setZombieStatus( max( $zone->getZombieStatus(), $state ) );
                }
            break;
            case 'small_rocket_#00':
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);
                
                foreach ($event->town->getZones() as $zone)
                    if ($zone->getX() === 0 || $zone->getY() === 0) {
                        $zone->setZombies(0)->setInitialZombies(0);
                        $zone->getEscapeTimers()->clear();
                    }
                $em->persist( $this->container->get(LogTemplateHandler::class)->constructionsBuildingCompleteZombieKill( $event->building ) );
                break;
            case 'small_cafet_#00':
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);
                /** @var InventoryHandler $inventoryHandler */
                $inventoryHandler = $this->container->get(InventoryHandler::class);
                /** @var ItemFactory $itemFactory */
                $itemFactory = $this->container->get(ItemFactory::class);

                $proto = $em->getRepository(ItemPrototype::class)->findOneBy( ['name' => 'woodsteak_#00'] );
                $inventoryHandler->forceMoveItem( $event->town->getBank(), $itemFactory->createItem( $proto ) );
                $inventoryHandler->forceMoveItem( $event->town->getBank(), $itemFactory->createItem( $proto ) );
                $em->persist( $event->town->getBank() );
                $em->persist( $this->container->get(LogTemplateHandler::class)->constructionsBuildingCompleteSpawnItems( $event->building, [ ['item'=>$proto,'count'=>2] ] ) );
                break;
            case 'r_dhang_#00':case 'small_fleshcage_#00':case 'small_eastercross_#00':
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->container->get(CitizenHandler::class);
                /** @var TownHandler $townHandler */
                $townHandler = $this->container->get(TownHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);

                // Only insta-kill on building completion when shunning is enabled
                if ($event->townConfig->get(TownConf::CONF_FEATURE_SHUN, true))
                    foreach ($event->town->getCitizens() as $citizen)
                        if ($citizenHandler->updateBanishment( $citizen, ($event->building->getPrototype()->getName() === 'r_dhang_#00' || $event->building->getPrototype()->getName() === 'small_eastercross_#00') ? $event->building : ($townHandler->getBuilding( $event->town, 'r_dhang_#00', true ) ?? $townHandler->getBuilding( $event->town, 'small_eastercross_#00', true )), $event->building->getPrototype()->getName() === 'small_fleshcage_#00' ? $event->building : $townHandler->getBuilding( $event->town, 'small_fleshcage_#00', true ) ))
                            $em->persist($event->town);
                break;
            case 'small_redemption_#00':
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->container->get(CitizenHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);

                foreach ($event->town->getCitizens() as $citizen)
                    if ($citizen->getBanished()) {
                        foreach ($em->getRepository(Complaint::class)->findByCulprit($citizen) as $complaint) {
                            /** @var $complaint Complaint */
                            $complaint->setSeverity(0);
                            $em->persist($complaint);
                        }
                        $citizen->setBanished(false);
                        $citizenHandler->inflictStatus( $citizen, 'tg_unban_altar' );
                        $em->persist($citizen);
                    }
                break;
            case "small_lastchance_#00":
                /** @var TownHandler $townHandler */
                $townHandler = $this->container->get(TownHandler::class);
                /** @var InventoryHandler $inventoryHandler */
                $inventoryHandler = $this->container->get(InventoryHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);
                
                $destroyedItems = 0;
                $bank = $event->town->getBank();
                foreach ($bank->getItems() as $bankItem) {
                    $count = $bankItem->getcount();
                    $inventoryHandler->forceRemoveItem($bankItem, $count);
                    $destroyedItems+= $count;
                }
                $townHandler->getBuilding($event->town, "small_lastchance_#00")->setTempDefenseBonus($destroyedItems);
                $em->persist( $this->container->get(LogTemplateHandler::class)->constructionsBuildingCompleteAllOrNothing($event->town, $destroyedItems ) );
                break;
            case "item_electro_#00":
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);

                $zones = $event->town->getZones();
                foreach ($zones as $zone) {
                    $zone->setZombieStatus(Zone::ZombieStateExact);
                    $em->persist($zone);
                }
                break;
            case "item_courroie_#00":
                /** @var TownHandler $townHandler */
                $townHandler = $this->container->get(TownHandler::class);
                $townHandler->assignCatapultMaster($event->town);
                break;
            case "small_novlamps_#00":
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->container->get(CitizenHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->container->get(EntityManagerInterface::class);

                // If the novelty lamps are built, it's effect must be applied immediately
                $novlamp_status = $em->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_novlamps']);
                foreach ($event->town->getCitizens() as $citizen) {
                    if ($citizen->getAlive()) $citizenHandler->inflictStatus($citizen, $novlamp_status);
                    $em->persist($citizen);
                }

                break;
            default: break;
        }
    }

}