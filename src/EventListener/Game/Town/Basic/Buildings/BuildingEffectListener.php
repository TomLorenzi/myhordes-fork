<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;
use App\Enum\ItemPoisonType;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPostAttackEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingEffectPreAttackEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingEffectPreAttackEvent::class, method: 'onProcessPreAttackEffect',  priority: 0)]
#[AsEventListener(event: BuildingEffectPostAttackEvent::class, method: 'onProcessPostAttackEffect', priority: 0)]

#[AsEventListener(event: BuildingEffectPreAttackEvent::class, method: 'onApplyEffect',  priority: -100)]
#[AsEventListener(event: BuildingEffectPostAttackEvent::class, method: 'onApplyEffect', priority: -100)]
final class BuildingEffectListener implements ServiceSubscriberInterface
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
            //RandomGenerator::class,
            //InventoryHandler::class,
            //PictoHandler::class,
            //DoctrineCacheService::class,
            TownHandler::class,
            //GameProfilerService::class,
            InventoryHandler::class,
            ItemFactory::class,
            //CitizenHandler::class
        ];
    }

    public function onProcessPreAttackEffect( BuildingEffectEvent $event ): void {

        switch ($event->building->getPrototype()->getName()) {
            case 'item_tube_#00':
                if ($event->building->getLevel() <= 0) break;

                // Attempt to deduct water from the well, reduce building defense to 0 if it fails
                $water_needed = [0,2,4,6,9,12][ $event->building->getLevel() ] ?? 12;
                if ($event->town->getWell() >= $water_needed)
                    $event->waterDeducted += $water_needed;
                else $event->building->setTempDefenseBonus(0 - $event->building->getDefense() - $event->building->getDefenseBonus());

                break;
            default:
                break;
        }
    }

    public function onProcessPostAttackEffect( BuildingEffectEvent $event ): void {
        if ( $event->building->getPrototype()->getName() === 'small_refine_#01' && ( $event->upgradedBuilding !== $event->building || $event->building->getLevel() === 1 ) ) {
            $event->produceDailyBlueprint = true;
            $event->markModified();
        }

        $items = match ($event->building->getPrototype()->getName()) {
            'small_appletree_#00'      => [ 'apple_#00'     => mt_rand(3,5) ],
            'small_chicken_#00'        => [ 'egg_#00' => 3 ],
            'item_vegetable_tasty_#00' => in_array( 'item_digger_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                ? [ 'vegetable_#00' => mt_rand(6,8), 'vegetable_tasty_#00' => mt_rand(3,5) ]    // with fertilizer
                : [ 'vegetable_#00' => mt_rand(4,7), 'vegetable_tasty_#00' => mt_rand(0,2) ],   // without fertilizer
            'item_bgrenade_#01' => in_array( 'item_digger_#00', $this->getService(TownHandler::class)->getCachedBuildingList($event->town, true) )
                ? [ 'boomfruit_#00' => mt_rand(5,8) ]    // with fertilizer
                : [ 'boomfruit_#00' => mt_rand(3,7) ],   // without fertilizer
            default => []
        };

        if (!empty($items)) {
            foreach ($event->dailyProduceItems as $item => $count)
                $items[$item] = ($items[$item] ?? 0) + $count;
            $event->dailyProduceItems = $items;
            $event->markModified();
        }
    }

    public function onApplyEffect( BuildingEffectEvent $event ): void {

        if ($event->waterDeducted > 0) {
            $event->town->setWell( $event->town->getWell() - $event->waterDeducted );
            $gazette = $event->town->findGazette( $event->town->getDay(), true );
            $gazette->setWaterlost($gazette->getWaterlost() + $event->waterDeducted );
            $this->getService(EntityManagerInterface::class)->persist($gazette);
            $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->nightlyAttackBuildingDefenseWater( $event->building, $event->waterDeducted ) );
            $event->markModified();
        }

        $local = [];
        if ($event->produceDailyBlueprint) {
            $plan = $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('bplan_c_#00');
            $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->nightlyAttackProductionBlueprint( $event->town, $plan, $event->building));
            $local[] = ['item' => $plan, 'count' => 1];
        }

        foreach ( $event->dailyProduceItems as $item_id => $count )
            if ($count > 0)
                $local[] = ['item' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName($item_id), 'count' => $count];

        if (!empty($local)) {
            $this->getService(EntityManagerInterface::class)->persist( $this->getService(LogTemplateHandler::class)->nightlyAttackProduction( $event->building, $local ) );

            $strange = $event->townConfig->get( TownConf::CONF_MODIFIER_STRANGE_SOIL, false );
            foreach ($local as ['item' => $item_proto, 'count' => $count])
                for ($i = 0; $i < $count; $i++) {
                    $item = $this->getService(ItemFactory::class)->createItem( $item_proto );
                    if ($strange) $item->setPoison( ItemPoisonType::Strange );
                    $this->getService(InventoryHandler::class)->forceMoveItem( $event->town->getBank(), $item );
                }

            $event->markModified();
        }

    }

}