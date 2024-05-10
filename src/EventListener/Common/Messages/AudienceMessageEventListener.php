<?php


namespace App\EventListener\Common\Messages;

use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Messages\Announcement\NewAnnouncementEvent;
use App\Event\Common\Messages\Announcement\NewEventAnnouncementEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\Actions\Mercure\BroadcastAnnouncementUpdateViaMercureAction;
use App\Service\Actions\User\GetAudienceAction;
use App\Service\HTMLService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: NewAnnouncementEvent::class, method: 'queueAnnouncementNotifications', priority: 0)]
#[AsEventListener(event: NewAnnouncementEvent::class, method: 'broadcastUpdate', priority: 0)]
#[AsEventListener(event: NewEventAnnouncementEvent::class, method: 'queueCommunityEventNotifications', priority: 0)]
final class AudienceMessageEventListener implements ServiceSubscriberInterface
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
            UserHandler::class,
            HTMLService::class,
            TranslatorInterface::class,
            GetAudienceAction::class,
            BroadcastAnnouncementUpdateViaMercureAction::class
        ];
    }


    public function broadcastUpdate(NewAnnouncementEvent $event): void {
        if (!$event->announcement->getLang()) return;
        $mercure = $this->getService(BroadcastAnnouncementUpdateViaMercureAction::class);
        $mercure($event->announcement->getLang());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function queueAnnouncementNotifications(NewAnnouncementEvent $event): void {
        if (!$event->announcement->getLang()) return;

        $em = $this->getService(EntityManagerInterface::class);
        $user_list = ($this->getService(GetAudienceAction::class))( UserSetting::PushNotifyOnAnnounce, language: $event->announcement->getLang() );

        $prefix = $this->getService(TranslatorInterface::class)->trans('Ankündigung', [], 'global', $event->announcement->getLang());
        $prepared_post = $this->getService(HTMLService::class)->prepareEmotes( $event->announcement->getText(), $event->announcement->getSender() );

        foreach ($user_list as $user_id)
            foreach ($em->getRepository(User::class)->find($user_id)?->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) ?? [] as $subscription)
                $this->getService(MessageBusInterface::class)->dispatch(
                    new WebPushMessage($subscription,
                        title:         "$prefix: {$event->announcement->getTitle()}",
                        body:          $prepared_post,
                        avatar:        $event->announcement->getSender()->getAvatar()?->getId()
                    )
                );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function queueCommunityEventNotifications(NewEventAnnouncementEvent $event): void {
        $em = $this->getService(EntityManagerInterface::class);

        foreach ($event->communityEvent->getMetas() as $meta) {
            if (!$meta->getLang()) continue;

            $user_list = ($this->getService(GetAudienceAction::class))( UserSetting::PushNotifyOnEvent, language: $meta->getLang() );
            $prefix = $this->getService(TranslatorInterface::class)->trans('Community-Event', [], 'global', $meta->getLang());

            foreach ($user_list as $user_id)
                foreach ($em->getRepository(User::class)->find($user_id)?->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) ?? [] as $subscription)
                    $this->getService(MessageBusInterface::class)->dispatch(
                        new WebPushMessage($subscription,
                            title:         "$prefix: {$meta->getName()}",
                            body:          $meta->getShort() ?? $meta->getDescription(),
                            avatar:        $event->communityEvent->getOwner()?->getAvatar()?->getId()
                        )
                    );
        }
    }
}