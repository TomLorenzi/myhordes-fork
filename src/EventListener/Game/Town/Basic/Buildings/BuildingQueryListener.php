<?php


namespace App\EventListener\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;
use App\Entity\Town;
use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Town\Basic\Buildings\BuildingAddonProviderEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingCatapultItemTransformEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownParameterEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownRoleEnabledEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use App\Structures\TownConf;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusInitial', priority: 0)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -15)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCalc', priority: -105)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusFinish', priority: -115)]
#[AsEventListener(event: BuildingQueryTownParameterEvent::class, method: 'onQueryTownParameter', priority: 0)]
#[AsEventListener(event: BuildingQueryTownRoleEnabledEvent::class, method: 'onQueryTownRoleEnabled', priority: 0)]
#[AsEventListener(event: BuildingCatapultItemTransformEvent::class, method: 'onQueryCatapultItemTransformation', priority: 0)]
#[AsEventListener(event: BuildingAddonProviderEvent::class, method: 'onCollectAddons', priority: 0)]
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
            EntityManagerInterface::class
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

    private function calculateMaxActiveZombies(Town|int $town, int $day): int {
        $targets = 0;
        $b_level = -1;
        $g_malus = false;
        if (is_int($town))
            $targets = $town;
        else
            foreach ($town->getCitizens() as $citizen)
                if ($citizen->getAlive() && !$citizen->getZone()) {
                    $g_malus = $g_malus || $citizen->hasStatus('tg_guitar');
                    $b_level = max($b_level, $citizen->getHome()->getPrototype()->getLevel() ?? 0);
                    $targets++;
                }

        if ($b_level < 0) {
            $b_level = 2;
            $factor = 1.5;
        } else $factor = 1.0 + (mt_rand(0,50)/100.0);

        //return round( $day * max(2.0, $day / 10) ) * max(15, $targets);
        $targets = max($targets, 10);
        return round(($targets / 3.0) * $day * ($b_level + $factor) * ($g_malus ? 1.1 : 1.0));
    }

    public function onQueryTownParameter( BuildingQueryTownParameterEvent $event ): void {
        $event->value = match ($event->query) {
            BuildingValueQuery::GuardianDefenseBonus => $this->getService(TownHandler::class)->getBuilding($event->town, 'small_watchmen_#00', true) ? 10 : 5,
            BuildingValueQuery::NightWatcherCap => $event->town->getPopulation(),
            BuildingValueQuery::NightWatcherWeaponsAllowed,
            BuildingValueQuery::TownDoorOpeningCost,
            BuildingValueQuery::TownDoorClosingCost => 1,
            BuildingValueQuery::MissingItemDefenseLoss,
            BuildingValueQuery::ScoutMarkingsEnabled => 0,
            BuildingValueQuery::ConstructionAPRatio => 1.0 - min(0.06 * $this->getService(TownHandler::class)->getBuilding($event->town, "small_refine_#00")?->getLevel() ?? 0, 0.28),
            BuildingValueQuery::RepairAPRatio => 2 + max(0, $this->getService(TownHandler::class)->getBuilding($event->town, "small_refine_#00")?->getLevel() - 3),
            BuildingValueQuery::MaxItemDefense => 500,
            BuildingValueQuery::OverallTownDefenseScale => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_shield_#00', true )?->getLevel() ?? -1) {
                0 => 1.10,
                1 => 1.12,
                2 => 1.14,
                default => 1.0
            },
            BuildingValueQuery::NightlyZoneDiscoveryRadius => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_tagger_#00', true )?->getLevel() ?? 0) {
                1 => 3,
                2 => 6,
                3, 4 => 10,
                default => 0
            },
            BuildingValueQuery::BeyondTeleportRadius => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_tagger_#00', true )?->getLevel() ?? 0) {
                4 => 1,
                5 => 2,
                default => 0
            },
            BuildingValueQuery::NightlyRecordWindDirection => $this->getService(TownHandler::class)->getBuilding($event->town, 'small_gather_#02', true ) ? 1 : 0,
            BuildingValueQuery::NightlyZoneRecoveryChance => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'small_gather_#02', true )?->getLevel() ?? 0) {
                1 => 0.37,
                2 => 0.49,
                3 => 0.61,
                4 => 0.73,
                5 => 0.85,
                default => 0.25,
            },
            BuildingValueQuery::NightlyRedSoulPenalty => 0.04,
            BuildingValueQuery::MaxActiveZombies => is_array($event->arg)
                ? $this->calculateMaxActiveZombies(is_int( $event->arg[0] ?? null ) ? $event->arg[0] : $event->town, is_int( $event->arg[1] ?? null ) ? $event->arg[1] : $event->town->getDay() )
                : $this->calculateMaxActiveZombies(is_int( $event->arg ) ? $event->arg : $event->town, $event->town->getDay() )
        };
    }

    public function onQueryTownRoleEnabled( BuildingQueryTownRoleEnabledEvent $event ): void {
        $event->enabled = true;
    }

    public function onQueryCatapultItemTransformation( BuildingCatapultItemTransformEvent $event ): void {
        $event->out = $event->in->getFragile() ? (
            $this->getService(EntityManagerInterface::class)->getRepository( ItemPrototype::class )->findOneByName( $event->in->hasProperty('pet') ? 'undef_#00' : 'broken_#00' )
        ) : null;
    }

    public function onCollectAddons( BuildingAddonProviderEvent $event ): void {
        if ($event->townConfig->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false) && $event->townConfig->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
            $event->addAddon( T::__('Wächt', 'game'), 'battlement', 'town_nightwatch', 3 );

        foreach ($event->town->getBuildings() as $b) if ($b->getComplete()) {

            if ($b->getPrototype()->getMaxLevel() > 0)
                $event->addAddon( T::__('Verbesserung des Tages (building)', 'game'), 'upgrade', 'town_upgrades', 0 );

            if ($b->getPrototype()->getName() === 'item_tagger_#00')
                $event->addAddon( T::__('Wachturm', 'game'), 'watchtower', 'town_watchtower', 1 );

            if ($b->getPrototype()->getName() === 'small_refine_#00')
                $event->addAddon( T::__('Werkstatt (building)', 'game'), 'workshop', 'town_workshop', 2 );

            if (($b->getPrototype()->getName() === 'small_round_path_#00' && !$event->townConfig->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false)) && $event->townConfig->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
                $event->addAddon( T::__('Wächt', 'game'), 'battlement', 'town_nightwatch', 3 );

            if ($b->getPrototype()->getName() === 'small_trash_#00')
                $event->addAddon( T::__('Müllhalde', 'game'), 'dump', 'town_dump', 4 );

            if ($b->getPrototype()->getName() === 'item_courroie_#00')
                $event->addAddon( T::__('Katapult', 'game'), 'catapult', 'town_catapult', 5 );

        }
    }

}