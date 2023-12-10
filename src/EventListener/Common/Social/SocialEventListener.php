<?php


namespace App\EventListener\Common\Social;

use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Social\FriendEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\CrowService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: FriendEvent::class, method: 'queueFriendshipNotifications', priority: 0)]
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

}