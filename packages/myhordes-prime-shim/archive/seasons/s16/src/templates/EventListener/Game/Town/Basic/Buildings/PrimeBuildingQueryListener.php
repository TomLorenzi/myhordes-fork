<?php


namespace MyHordes\Prime\EventListener\Game\Town\Basic\Buildings;

use App\Entity\ItemPrototype;
use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Town\Basic\Buildings\BuildingCatapultItemTransformEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownParameterEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownRoleEnabledEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Town\Basic\Buildings\BuildingQueryListener;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onCheckItemsAllowed', priority:   1)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -16)]
#[AsEventListener(event: BuildingQueryTownParameterEvent::class, method: 'onQueryTownParameter', priority: 10)]
#[AsEventListener(event: BuildingQueryTownRoleEnabledEvent::class, method: 'onQueryTownRoleEnabled', priority: 10)]
#[AsEventListener(event: BuildingCatapultItemTransformEvent::class, method: 'onQueryCatapultItemTransformation', priority: 10)]
final class PrimeBuildingQueryListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TownHandler::class,
            InventoryHandler::class,
            EventProxyService::class,
            EntityManagerInterface::class
        ];
    }

    // #[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusInitial', priority: 0)]
    public function onCheckItemsAllowed( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        if ($this->getService(EventProxyService::class)->queryTownParameter( $event->town, BuildingValueQuery::NightWatcherWeaponsAllowed ) == 0)
            $event->skipPropagationTo( BuildingQueryListener::class, 'onQueryNightwatchDefenseBonusInitial' );
    }

    public function onQueryNightwatchDefenseBonusCollect( BuildingQueryNightwatchDefenseBonusEvent $event ): void {
        $event->building_bonus_map = array_replace_recursive($event->building_bonus_map, [
            // <building name> => [ <item property>, <bonus defense> ]
            // Use null as item property to apply to apply to all items
            // Only changed settings need to be defined; to remove a previous setting, set the bonus to 0, see example:
            //'small_ikea_#00'            => [ 'nw_ikea', 0.5 ],
            'small_tourello_#00'        => [ 'nw_shooting', 0 ],
            'small_catapult3_#00'       => [ 'nw_trebuchet', 0 ],
            'small_armor_#00'           => [ 'nw_armory', 0 ],
            'small_animfence_#00'       => [ 'nw_trebuchet', 0.3 ],
            'small_sewers_#00'          => [ 'nw_shooting', 0.3 ],
            'small_ikea_#00'            => [ 'nw_ikea', 0.3 ],
            'small_grinder2_#00'        => [ 'nw_armory', 0.2 ],
        ]);
    }

    private function level3PortalEffectActive( BuildingQueryTownParameterEvent $event ): bool {
        // Check if the portal is built, it has been votes to lv3 or beyond, and it is before 12:00
        return (
            $this->getService( TownHandler::class )->getBuilding( $event->town, 'small_door_closed_#00' )?->getLevel() >= 3 &&
            (int)(new \DateTime())->format('G') < 12
        );
    }

    private function countMissingItemsDefenseLoss( BuildingQueryTownParameterEvent $event ): int {
        $base = 0;

        $grenade_launcher = $this->getService(TownHandler::class)->getBuilding( $event->town, 'item_boomfruit_#00' );
        if ($grenade_launcher?->getLevel() > 0 && !$this->getService(InventoryHandler::class)->fetchSpecificItems( $event->town->getBank(), [new ItemRequest('boomfruit_#00', $grenade_launcher?->getLevel())] ))
            $base += $grenade_launcher->getDefenseBonus();

        return $base;
    }

    public function onQueryTownParameter( BuildingQueryTownParameterEvent $event ): void {
        $value = match ($event->query) {
            BuildingValueQuery::GuardianDefenseBonus => 5,
            BuildingValueQuery::NightWatcherCap => match ($this->getService( TownHandler::class )->getBuilding( $event->town, 'small_round_path_#00' )?->getLevel() ?? 0) {
                0 => 10,
                1 => 20,
                default => 40
            },
            BuildingValueQuery::NightWatcherWeaponsAllowed => $this->getService( TownHandler::class )->getBuilding( $event->town, 'small_armor_#00' ) ? 1 : 0,
            BuildingValueQuery::TownDoorOpeningCost => $this->level3PortalEffectActive( $event ) ? 0 : 1,
            BuildingValueQuery::MissingItemDefenseLoss => $this->countMissingItemsDefenseLoss( $event ),
            BuildingValueQuery::MaxItemDefense => PHP_INT_MAX,
            BuildingValueQuery::ConstructionAPRatio => match($this->getService(TownHandler::class)->getBuilding($event->town, "small_refine_#00")?->getLevel()) {
                4 => 0.75,
                5 => 0.65,
                default => null
            },
            BuildingValueQuery::OverallTownDefenseScale => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_shield_#00', true )?->getLevel() ?? -1) {
                0 => 1.10,
                1 => 1.11,
                2 => 1.13,
                3 => 1.15,
                default => 1.0
            },
            BuildingValueQuery::NightlyZoneDiscoveryRadius => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_scope_#00', true )?->getLevel() ?? 0) {
                1 => 3,
                2 => 6,
                3, 4, 5 => 10,
                default => 0
            },
            BuildingValueQuery::BeyondTeleportRadius => match ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_scope_#00', true )?->getLevel() ?? 0) {
                4 => 1,
                5 => 2,
                default => 0
            },
            BuildingValueQuery::NightlyRedSoulPenalty => ($this->getService(TownHandler::class)->getBuilding($event->town, 'item_soul_blue_static_#00', true )?->getLevel() ?? 0) >= 2
                ? 0.02
                : null
            ,
            BuildingValueQuery::ScoutMarkingsEnabled => $this->getService( TownHandler::class )->getBuilding( $event->town, 'small_watchmen_#01' ) ? 1 : 0,
            default => null
        };

        if ($value !== null) {
            $event->skipPropagationTo(BuildingQueryListener::class);
            $event->value = $value;
        }
    }

    public function onQueryTownRoleEnabled( BuildingQueryTownRoleEnabledEvent $event ): void {
        $enabled = match ($event->role->getName()) {
            'guide' => $this->getService(TownHandler::class)->getBuilding($event->town, 'item_scope_#00' ) !== null,
            'shaman' => $this->getService(TownHandler::class)->getBuilding($event->town, 'small_spa4souls_#01' ) !== null,
            default => null
        };

        if ($enabled !== null) {
            $event->skipPropagationTo(BuildingQueryListener::class);
            $event->enabled = $enabled;
        }
    }

    public function onQueryCatapultItemTransformation( BuildingCatapultItemTransformEvent $event ): void {
        $event->out = ($event->in->getFragile() && (!$event->in->hasProperty('pet') || !$this->getService(TownHandler::class)->getBuilding($event->town, 'small_catapult3_#00' ))) ? (
            $this->getService(EntityManagerInterface::class)->getRepository( ItemPrototype::class )->findOneByName( $event->in->hasProperty('pet') ? 'undef_#00' : 'broken_#00' )
        ) : null;
        $event->skipPropagationTo(BuildingQueryListener::class);
    }


}