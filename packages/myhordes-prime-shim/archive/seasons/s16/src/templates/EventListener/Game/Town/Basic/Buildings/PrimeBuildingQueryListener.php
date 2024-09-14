<?php


namespace MyHordes\Prime\EventListener\Game\Town\Basic\Buildings;

use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use App\Event\Game\Town\Basic\Buildings\BuildingQueryTownParameterEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Town\Basic\Buildings\BuildingQueryListener;
use App\Service\EventProxyService;
use App\Service\TownHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onCheckItemsAllowed', priority:   1)]
#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -16)]
#[AsEventListener(event: BuildingQueryTownParameterEvent::class, method: 'onQueryTownParameter', priority: 10)]
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
            EventProxyService::class
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
            'small_animfence_#00'       => [ 'nw_trebuchet', 0.2 ],
            'small_sewers_#00'          => [ 'nw_shooting', 0.4 ],
            'small_ikea_#00'            => [ 'nw_ikea', 0.3 ],
            'small_blacksmith_#00'      => [ 'nw_armory', 0.2 ],
        ]);
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
            default => null
        };

        if ($value !== null) {
            $event->skipPropagationTo(BuildingQueryListener::class);
            $event->value = $value;
        }
    }


}