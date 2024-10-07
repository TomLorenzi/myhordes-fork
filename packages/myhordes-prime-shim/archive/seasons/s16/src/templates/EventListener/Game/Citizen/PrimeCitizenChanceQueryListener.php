<?php


namespace MyHordes\Prime\EventListener\Game\Citizen;

use App\Enum\ScavengingActionType;
use App\Event\Game\Citizen\CitizenQueryDigChancesEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CitizenQueryDigChancesEvent::class, method: 'getDigChances', priority: -10)]
final class PrimeCitizenChanceQueryListener implements ServiceSubscriberInterface
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

    public function getDigChances(CitizenQueryDigChancesEvent $event): void {
        switch ($event->type) {
            case ScavengingActionType::Dig:

                if ($event->citizen->hasStatus('tg_novlamps')) {
                    // Night mode is active, but so are the Novelty Lamps; we must check if they apply
                    $novelty_lamps_level = $this->getService(TownHandler::class)->getBuilding( $event->town, 'small_novlamps_#00', true )?->getLevel() ?? 0;
                    if ($novelty_lamps_level >= 3) $event->chance = min(max(0, $event->chance + 0.1), 1.0);
                }

                if ($this->getService(TownHandler::class)->getBuilding( $event->town, 'small_watchmen_#01', true ))
                    $event->chance = $event->chance + $event->zone?->getScoutLevel() * 0.025;

                break;
        }
    }
}