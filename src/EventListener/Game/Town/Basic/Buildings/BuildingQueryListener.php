<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\CitizenStatus;
use App\Entity\Complaint;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Zone;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
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

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusInitial', priority: 0)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -15)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCalc', priority: -105)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusFinish', priority: -115)]
final class BuildingQueryListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TownHandler::class,
        ];
    }

    public function onQueryNightwatchDefenseBonusInitial( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        $event->defense = $event->item->getBroken() ? 0 : $event->item->getPrototype()->getWatchpoint();
    }

    public function onQueryNightwatchDefenseBonusCollect( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        if ($event->defense === 0) {
            $event->stopPropagation();
            return;
        }

        /** @var TownHandler $th */
        $th = $this->container->get(TownHandler::class);
        $event->buildings = $th->getCachedBuildingList( $event->town, true );

        $event->building_bonus_map = [
            // <building name> => [ <item property>, <bonus defense> ]
            // Use null as item property to apply to apply to all items
            'small_tourello_#00'        => [ 'nw_shooting', 0.2 ],
            'small_catapult3_#00'       => [ 'nw_trebuchet', 0.2 ],
            'small_ikea_#00'            => [ 'nw_ikea', 0.2 ],
            'small_armor_#00'           => [ 'nw_armory', 0.2 ],
        ];
    }

    public function onQueryNightwatchDefenseBonusCalc( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        $bonus_temp = $event->bonus;
        foreach ( $event->building_bonus_map as $building => [$property, $bonus] )
            if ($bonus <> 0 && ($property === null || $event->item->getPrototype()->hasProperty( $property )) && in_array( $building, $event->buildings ))
                $bonus_temp[$building] = $bonus;
        $event->bonus = $bonus_temp;
    }

    public function onQueryNightwatchDefenseBonusFinish( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        foreach ($event->bonus as $single)
            $event->defense = (int)floor( $event->defense * (1.0+$single) );
    }

}