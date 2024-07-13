<?php


namespace MyHordes\Prime\EventListener\Game\Town\Basic\Buildings;

use App\Event\Game\Town\Basic\Buildings\BuildingQueryNightwatchDefenseBonusEvent;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: BuildingQueryNightwatchDefenseBonusEvent::class, method: 'onQueryNightwatchDefenseBonusCollect', priority: -16)]
final class PrimeBuildingQueryListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [];
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

}