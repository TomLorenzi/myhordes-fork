<?php


namespace MyHordes\Prime\EventListener\Game\Town\Basic\Buildings;

use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\RandomGenerator;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;

#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onConfigureWellEffect', priority: -16)]
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onConfigurePictoEffect', priority: -26)]
#[AsEventListener(event: BuildingConstructionEvent::class, method: 'onExecuteSpecialEffect', priority: -104)]
final class PrimeBuildingConstructionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            TownHandler::class,
            InventoryHandler::class,
            ItemFactory::class,
            CitizenHandler::class,
            RandomGenerator::class
        ];
    }

    public function onConfigureWellEffect( BuildingConstructionEvent $event ): void {
        $event->spawn_well_water = match ($event->building->getPrototype()->getName()) {
            // You only need to add the buildings where the water bonus has changed, see the following example:
            'small_derrick_#00'        =>  75,
            'small_water_#01'          =>  50,
            'small_eden_#00'           =>  50,
            'small_water_#00'          =>  15,
            'small_derrick_#01'        => 100,
            'item_firework_tube_#00'   =>   5,
            default => $event->spawn_well_water
        };
    }

    public function onConfigurePictoEffect( BuildingConstructionEvent $event ): void {
        // Implement me!
		// Buildings which give pictos
		$pictos = match ($event->building->getPrototype()->getName()) {
			'small_thermal_#00' =>  ['r_thermal_#00','r_ebuild_#00', 'r_wondrs_#00'],
            'small_crow_#00'   =>  ['r_wondrs_#00'],
			default => []
		};

		if (!empty($pictos))
			$event->pictos = array_merge($event->pictos, $pictos);
    }

    public function onExecuteSpecialEffect( BuildingConstructionEvent $event ): void {
        // Implement me!
        // To cancel an existing building effect from the public code, run
        // $event->stopPropagation();
        switch ($event->building->getPrototype()->getName()) {
            case "small_lastchance_#00":
                $event->stopPropagation(); //we cancel the effect of the public file
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
                    $destroyedItems+= $count*2; //we give 2 defense / item now
                }
                $townHandler->getBuilding($event->town, "small_lastchance_#00")->setTempDefenseBonus($destroyedItems);
                $em->persist( $this->container->get(LogTemplateHandler::class)->constructionsBuildingCompleteAllOrNothing($event->town, $destroyedItems ) );
                break;

            case 'small_spa4souls_#00':
                // Move souls closer to town
                // Get all soul items on the WB
                $soul_items = $this->getService(InventoryHandler::class)->getAllItems($event->town, ['soul_blue_#00'], false, false, false, true, false, false);

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
}