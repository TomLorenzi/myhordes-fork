<?php


namespace App\EventListener\Common\Social;

use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\TownJoinNotificationAccumulation;
use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Social\FriendEvent;
use App\Event\Game\Town\Basic\Core\AfterJoinTownEvent;
use App\Event\Game\Town\Basic\Core\BeforeJoinTownEvent;
use App\Event\Game\Town\Basic\Core\JoinTownEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\CrowService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: FriendEvent::class, method: 'queueFriendshipNotifications', priority: 0)]
#[AsEventListener(event: BeforeJoinTownEvent::class, method: 'checkShoutboxNotification', priority: 0)]
#[AsEventListener(event: JoinTownEvent::class, method: 'createShoutboxNotification', priority: 0)]
#[AsEventListener(event: AfterJoinTownEvent::class, method: 'handleShoutboxNotificationCleanup', priority: 0)]
#[AsEventListener(event: AfterJoinTownEvent::class, method: 'accumulateNotifications', priority: -100)]
final class SocialEventListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            MessageBusInterface::class,
            EntityManagerInterface::class,
            TranslatorInterface::class,
            CrowService::class,
            UserHandler::class,
        ];
    }

    public function queueFriendshipNotifications(FriendEvent $event): void {
        if ($event->added && $event->subject->getSetting( UserSetting::NotifyMeOnFriendRequest )) {
            $reverse = $event->subject->getFriends()->contains( $event->actor );

            $this->getService(EntityManagerInterface::class)->persist( $this->getService(CrowService::class)->createPM_friendNotification( $event->subject, $event->actor, $reverse ) );
            try { $this->getService(EntityManagerInterface::class)->flush(); } catch (\Throwable) {}

            $lang = $event->subject->getLanguage() ?? 'en';
            foreach ( $event->subject->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription )
                $this->getService(MessageBusInterface::class)->dispatch(
                    new WebPushMessage($subscription,
                        title: $this->getService(TranslatorInterface::class)->trans('Du hast einen neuen Freund!', [], 'global', $lang ),
                        body: $this->getService(TranslatorInterface::class)->trans( $reverse ? '{player} hat deine Freundschaftserklärung erwidert.' : '{player} hat dich als "Freund" hinzugefügt.', [
                            'player' => $event->actor,
                        ], 'game', $lang ),
                        avatar: $event->actor->getAvatar()?->getId()
                    )
                );
        }
    }

    public function checkShoutboxNotification(BeforeJoinTownEvent $event): void {
        if ($sb = $this->getService(UserHandler::class)->getShoutbox($event->subject)) {
            $last_entry = $this->getService(EntityManagerInterface::class)->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC', 'id' => 'DESC']);
            if ($last_entry) {
                $marker = $this->getService(EntityManagerInterface::class)->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $event->subject]);
                if ($marker && $last_entry === $marker->getEntry()) $event->shoutbox_clean_needed = true;
            }
        }
    }

    public function createShoutboxNotification(JoinTownEvent $event): void {
        if ($shoutbox = $this->getService(UserHandler::class)->getShoutbox($event->subject)) {
            $shoutbox->addEntry(
                $entry_cache[$shoutbox->getId()] = (new ShoutboxEntry())
                    ->setType(ShoutboxEntry::SBEntryTypeTown)
                    ->setTimestamp(new \DateTime())
                    ->setUser1($event->subject)
                    ->setText($event->town->getName())
            );
            $this->getService(EntityManagerInterface::class)->persist($shoutbox);
        }
    }

    public function handleShoutboxNotificationCleanup(AfterJoinTownEvent $event): void {
        if ($event->before->shoutbox_clean_needed && $sb = $this->getService(UserHandler::class)->getShoutbox($event->before->subject)) {
            $marker = $this->getService(EntityManagerInterface::class)->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $event->before->subject]);
            $last_entry = $this->getService(EntityManagerInterface::class)->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC', 'id' => 'DESC']);
            if ($marker && $last_entry)
                $this->getService(EntityManagerInterface::class)->persist( $marker->setEntry( $last_entry ));

        }
    }

    public function accumulateNotifications(AfterJoinTownEvent $event): void {
        $friends = $event->before->subject->getFriends()->filter( fn(User $friend) => $friend->getFriends()->contains( $event->before->subject ) );

        foreach ($friends as $friend) {
            $accumulator = $this->getService(EntityManagerInterface::class)
                ->getRepository(TownJoinNotificationAccumulation::class)
                ->findOneBy( [ 'town' => $event->town, 'subject' => $friend ] ) ?? (new TownJoinNotificationAccumulation())
                ->setTown( $event->town )
                ->setSubject( $friend )
                ->setDue( (new \DateTime())->modify('+1 min') )
            ;

            $this->getService(EntityManagerInterface::class)->persist(
                $accumulator->addFriend( $event->before->subject )
            );
        }


    }
}