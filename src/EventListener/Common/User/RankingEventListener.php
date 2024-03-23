<?php


namespace App\EventListener\Common\User;

use App\Entity\Season;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\TownJoinNotificationAccumulation;
use App\Entity\User;
use App\Entity\UserGroupAssociation;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Social\FriendEvent;
use App\Event\Common\User\PictoPersistedEvent;
use App\Event\Game\Town\Basic\Core\AfterJoinTownEvent;
use App\Event\Game\Town\Basic\Core\BeforeJoinTownEvent;
use App\Event\Game\Town\Basic\Core\JoinTownEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\Actions\User\UserPictoRollupAction;
use App\Service\CrowService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
            UserHandler::class,
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

        $this->getService(UserHandler::class)->computePictoUnlocks($event->user);
        $this->getService(EntityManagerInterface::class)->flush();
    }

}