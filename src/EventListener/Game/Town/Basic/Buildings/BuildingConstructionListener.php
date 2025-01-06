<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\CitizenStatus;
use App\Entity\Complaint;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Zone;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\DoctrineCacheService;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\PictoHandler;
use App\Service\RandomGenerator;
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
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onRecordBuildingCount', priority: -106)]
final readonly class BuildingConstructionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private ContainerInterface $container,
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
            CitizenHandler::class,
            RandomGenerator::class,
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
            $this->getService(GameProfilerService::class)->recordBuildingConstructed( $event->building->getPrototype(), $event->town, $event->citizen, $event->method );
            $event->markModified();
        }
    }

    public function onConfigureWellEffect( BuildingConstructionEvent $event ): void {
        $event->spawn_well_water = match ($event->building->getPrototype()->getName()) {
            'small_derrick_#00'      =>  75,
            'small_water_#01'        =>  50,
            'small_eden_#00'         =>  50,
            'small_water_#00'        =>  15,
            'small_derrick_#01'      => 100,
            'item_tube_#01'          =>   2,
            'item_firework_tube_#00' =>   5,
            'small_rocketperf_#00'   =>  60,
            'small_waterdetect_#00'  => 100,
            default => $event->spawn_well_water
        };
    }

    public function onExecuteWellEffect( BuildingConstructionEvent $event ): void {
        if ($event->spawn_well_water > 0) {
            $event->town->setWell( $event->town->getWell() + $event->spawn_well_water );
            $this->getService(EntityManagerInterface::class)->persist(
                $this->getService(LogTemplateHandler::class)->constructionsBuildingCompleteWell( $event->building, $event->spawn_well_water )
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
            'small_crow_#00'   =>  ['r_ebcrow_#00','r_ebuild_#00', 'r_wondrs_#00'],
            'small_thermal_#00' =>  ['r_thermal_#00','r_ebuild_#00', 'r_wondrs_#00'],
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
            $handler = $this->getService(PictoHandler::class);
            /** @var DoctrineCacheService $cache */
            $cache = $this->getService(DoctrineCacheService::class);
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
            case 'small_balloon_#00':
                $all = $event->building->getPrototype()->getName() === 'small_balloon_#00';
                /** @var TownHandler $townHandler */
                $townHandler = $this->getService(TownHandler::class);

                $state = $townHandler->getBuilding($event->town, 'item_electro_#00', true) ? Zone::ZombieStateExact : Zone::ZombieStateEstimate;
                foreach ($event->town->getZones() as $zone)
                    if ($all || $zone->getPrototype()) {
                        $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                        $zone->setZombieStatus( max( $zone->getZombieStatus(), $state ) );
                    }
                break;
            case 'small_rocket_#00':
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);
                
                foreach ($event->town->getZones() as $zone)
                    if ($zone->getX() === 0 || $zone->getY() === 0) {
                        $zone->setZombies(0)->setInitialZombies(0);
                        $zone->getEscapeTimers()->clear();
                    }
                $em->persist( $this->getService(LogTemplateHandler::class)->constructionsBuildingCompleteZombieKill( $event->building ) );
                break;
            case 'small_cafet_#00':
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);
                /** @var InventoryHandler $inventoryHandler */
                $inventoryHandler = $this->getService(InventoryHandler::class);
                /** @var ItemFactory $itemFactory */
                $itemFactory = $this->getService(ItemFactory::class);

                $proto = $em->getRepository(ItemPrototype::class)->findOneBy( ['name' => 'woodsteak_#00'] );
                $inventoryHandler->forceMoveItem( $event->town->getBank(), $itemFactory->createItem( $proto ) );
                $inventoryHandler->forceMoveItem( $event->town->getBank(), $itemFactory->createItem( $proto ) );
                $em->persist( $event->town->getBank() );
                $em->persist( $this->getService(LogTemplateHandler::class)->constructionsBuildingCompleteSpawnItems( $event->building, [ ['item'=>$proto,'count'=>2] ] ) );
                break;
            case 'r_dhang_#00': case 'small_fleshcage_#00': case 'small_eastercross_#00':
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->getService(CitizenHandler::class);
                /** @var TownHandler $townHandler */
                $townHandler = $this->getService(TownHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);

                // Only insta-kill on building completion when shunning is enabled
                if ($event->townConfig->get(TownConf::CONF_FEATURE_SHUN, true))
                    foreach ($event->town->getCitizens() as $citizen)
                        if ($citizenHandler->updateBanishment( $citizen, ($event->building->getPrototype()->getName() === 'r_dhang_#00' || $event->building->getPrototype()->getName() === 'small_eastercross_#00') ? $event->building : ($townHandler->getBuilding( $event->town, 'r_dhang_#00', true ) ?? $townHandler->getBuilding( $event->town, 'small_eastercross_#00', true )), $event->building->getPrototype()->getName() === 'small_fleshcage_#00' ? $event->building : $townHandler->getBuilding( $event->town, 'small_fleshcage_#00', true ) ))
                            $em->persist($event->town);
                break;
            case 'small_redemption_#00':
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->getService(CitizenHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);

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
                $townHandler = $this->getService(TownHandler::class);
                /** @var InventoryHandler $inventoryHandler */
                $inventoryHandler = $this->getService(InventoryHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);

                $destroyedItems = 0;
                $bank = $event->town->getBank();
                foreach ($bank->getItems() as $bankItem) {
                    $count = $bankItem->getcount();
                    $inventoryHandler->forceRemoveItem($bankItem, $count);
                    $destroyedItems+= $count*2; //we give 2 defense / item now
                }
                $townHandler->getBuilding($event->town, "small_lastchance_#00")->setTempDefenseBonus($destroyedItems);
                $em->persist( $this->getService(LogTemplateHandler::class)->constructionsBuildingCompleteAllOrNothing($event->town, $destroyedItems ) );
                break;
            case "item_electro_#00":
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);

                $zones = $event->town->getZones();
                foreach ($zones as $zone) {
                    $zone->setZombieStatus(Zone::ZombieStateExact);
                    $em->persist($zone);
                }
                break;
            case "item_courroie_#00":
                /** @var TownHandler $townHandler */
                $townHandler = $this->getService(TownHandler::class);
                $townHandler->assignCatapultMaster($event->town);
                break;
            case "small_novlamps_#00":
                /** @var CitizenHandler $citizenHandler */
                $citizenHandler = $this->getService(CitizenHandler::class);
                /** @var EntityManagerInterface $em */
                $em = $this->getService(EntityManagerInterface::class);

                // If the novelty lamps are built, it's effect must be applied immediately
                $novlamp_status = $em->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_novlamps']);
                foreach ($event->town->getCitizens() as $citizen) {
                    if ($citizen->getAlive()) $citizenHandler->inflictStatus($citizen, $novlamp_status);
                    $em->persist($citizen);
                }

                break;

            case 'small_spa4souls_#00':
                // Move souls closer to town
                // Get all soul items on the WB
                $soul_items = $this->getService(InventoryHandler::class)->getAllItems($event->town, ['soul_blue_#00', 'soul_blue_#01', 'soul_red_#00', 'soul_yellow_#00'], false, false, false, true, false, false);

                foreach ($soul_items as $soul)
                    // Only move souls which have not been picked up yet
                    if ($soul->getFirstPick()) {
                        $distance = $soul->getInventory()?->getZone()?->getDistance() ?? 0;
                        if ($distance > 11) {
                            $newZone = $this->getService(RandomGenerator::class)->pickLocationBetweenFromList($event->town->getZones()->toArray(), 5, 11);
                            $this->getService(InventoryHandler::class)->forceMoveItem($newZone->getFloor(), $soul);
                        }
                    }
                break;
            default: break;
        }
    }

    public function onRecordBuildingCount( BuildingConstructionEvent $event ): void {
        foreach ($event->town->getCitizens() as $target_citizen) {
            if (!$target_citizen->getAlive()) continue;
            $target_citizen->registerPropInPersistentCache("b_{$event->building->getPrototype()->getName()}_count");
        }

        $event->markModified();
    }

}