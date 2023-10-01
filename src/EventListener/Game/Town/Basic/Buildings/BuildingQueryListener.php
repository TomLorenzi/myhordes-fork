<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownParameterEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusInitial', priority: 0)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -15)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCalc', priority: -105)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusFinish', priority: -115)]
#[AsEventListener(event: BuildingQueryTownParameterEvent::class, method: 'onQueryTownParameter', priority: 0)]
final class BuildingQueryListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

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
        $th = $this->getService(TownHandler::class);
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

    public function onQueryTownParameter( BuildingQueryTownParameterEvent $event ): void {
        $event->value = match ($event->query) {
            BuildingValueQuery::GuardianDefenseBonus => $this->getService(TownHandler::class)->getBuilding($event->town, 'small_watchmen_#00', true) ? 10 : 5,
            BuildingValueQuery::NightWatcherCap => $event->town->getPopulation(),
            BuildingValueQuery::NightWatcherWeaponsAllowed, BuildingValueQuery::TownDoorOpeningCost, BuildingValueQuery::TownDoorClosingCost => 1,
            BuildingValueQuery::MissingItemDefenseLoss => 0,
            BuildingValueQuery::ConstructionAPRatio => 1.0 - min(0.06 * $this->getService(TownHandler::class)->getBuilding($event->town, "small_refine_#00")?->getLevel() ?? 0, 0.28),
            BuildingValueQuery::RepairAPRatio => 2 + max(0, $this->getService(TownHandler::class)->getBuilding($event->town, "small_refine_#00")?->getLevel() - 3),
            BuildingValueQuery::OverallTownDefenseScale => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_shield_#00', true )?->getLevel() ?? -1) {
                0 => 1.10,
                1 => 1.12,
                2 => 1.14,
                default => 1.0
            },
        };
    }

}