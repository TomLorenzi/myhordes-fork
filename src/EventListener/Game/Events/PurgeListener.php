<?php


namespace App\EventListener\Game\Events;

use App\Entity\CauseOfDeath;
use App\Event\Game\EventHooks\Purge\DashboardModifierEvent;
use App\Event\Game\EventHooks\Purge\TownDeactivateEvent;
use App\Event\Game\EventHooks\Purge\WatchtowerModifierEvent;
use App\EventListener\ContainerTypeTrait;
use App\Response\AjaxResponse;
use App\Service\DeathHandler;
use App\Translation\T;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: WatchtowerModifierEvent::class, method: 'fakeWatchtower', priority: 0)]
#[AsEventListener(event: DashboardModifierEvent::class, method: 'fakeDashboard', priority: 0)]
#[AsEventListener(event: TownDeactivateEvent::class, method: 'purge', priority: 0)]
final class PurgeListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [DeathHandler::class];
    }

    public static function purge_daysUntil(?\DateTimeInterface $dateTime = null): int {
        if ($dateTime === null) $dateTime = new \DateTime();
        return $dateTime->diff( (new \DateTime('today'))->setDate(2021,9,1) )->d;
    }

    public function fakeWatchtower(WatchtowerModifierEvent $event): void
    {
        if ($event->dayOffset !== 0) return;
        $dayDiff = self::purge_daysUntil();

        if ($dayDiff > 7 || $dayDiff < 0) return;
        elseif ( $event->quality >= (1.0 - ((7-$dayDiff) / 7) * 0.7) )
            $event->message = match($dayDiff) {
                7,6 => T::__('Vereinzelte Bürger berichten von einem merkwürdigen Phänomen am Himmel... Ihr solltet die Alkoholvorräte in der Bank pürfen.', 'game'),
                5,4 => T::__('Einige Bürger haben berichtet, während ihrer Abschätzung ein rotes Blitzen am Horizont gesehen zu haben.', 'game'),
                3,2 => T::__('Ein Großteil der Bürger, die heute auf dem Wachturm waren, haben ein lautes Grollen in der Ferne vernommen.', 'game'),
                1   => T::__('Zusatzbemerkung zur heutigen Abschätzung: Der Himmel hat sich blutrot gefärbt. Das sieht nicht gut aus, Leute...', 'game'),
                0   => T::__('Das sieht nicht gut aus... Die Zombies werden heute Nacht nicht unser größtes Problem sein.', 'game'),
                default => null,
            };
    }

    public function fakeDashboard(DashboardModifierEvent $event): void
    {
        if (self::purge_daysUntil() === 0) {
            $event->addBullet( T::__('Beten', 'game'), false );
            $event->addSituation( T::__('Es gibt keine Hoffnung!', 'game'), true );
        }
    }

    public function purge(TownDeactivateEvent $event): void
    {
        $death_handler = $this->getService(DeathHandler::class);

        foreach ($event->town->getCitizens() as $citizen) {
            if (!$citizen->getAlive()) continue;
            $death_handler->kill($citizen, CauseOfDeath::Apocalypse);
        }

        $event->value = true;
    }
}