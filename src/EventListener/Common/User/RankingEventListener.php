<?php


namespace App\EventListener\Common\User;

use App\Entity\Season;
use App\Event\Common\User\PictoPersistedEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\Actions\User\UserPictoRollupAction;
use App\Service\User\PictoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: PictoPersistedEvent::class, method: 'pictoCountUpdated', priority: 0)]
final class RankingEventListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            UserPictoRollupAction::class,
            EntityManagerInterface::class,
            PictoService::class,
        ];
    }

    public function pictoCountUpdated(PictoPersistedEvent $event): void {

        if (!$event->old && !$event->imported) {
            $season = $event->season ?? $this->getService(EntityManagerInterface::class)->getRepository(Season::class)->findOneBy(['current' => true]);
            ($this->getService(UserPictoRollupAction::class))(
                $event->user, null, $season, false, false
            );
        } else
            ($this->getService(UserPictoRollupAction::class))(
                $event->user, null, null, $event->imported ?? false, $event->old ?? false
            );

        $this->getService(EntityManagerInterface::class)->flush();

        $this->getService(PictoService::class)->computePictoUnlocks($event->user);
        $this->getService(EntityManagerInterface::class)->flush();
    }

}