<?php


namespace App\EventListener\Common\Messages;

use App\Entity\OfficialGroupMessageLink;
use App\Entity\SocialRelation;
use App\Entity\User;
use App\Entity\UserGroupAssociation;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Messages\GlobalPrivateMessage\GPDirectMessageEvent;
use App\Event\Common\Messages\GlobalPrivateMessage\GPDirectMessageNewPostEvent;
use App\Event\Common\Messages\GlobalPrivateMessage\GPMessageNewPostEvent;
use App\Event\Common\Messages\GlobalPrivateMessage\GPMessageNewThreadEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\Actions\Mercure\BroadcastPMUpdateViaMercureAction;
use App\Service\HTMLService;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: GPMessageNewThreadEvent::class, method: 'queueNotifications', priority: 0)]
#[AsEventListener(event: GPMessageNewPostEvent::class, method: 'queueNotifications', priority: 0)]
#[AsEventListener(event: GPDirectMessageNewPostEvent::class, method: 'broadcastSingleUpdate', priority: 0)]
#[AsEventListener(event: GPMessageNewThreadEvent::class, method: 'broadcastUpdate', priority: 0)]
#[AsEventListener(event: GPMessageNewPostEvent::class, method: 'broadcastUpdate', priority: 0)]
final class GlobalPrivateMessageEventListener implements ServiceSubscriberInterface
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
            BroadcastPMUpdateViaMercureAction::class,
        ];
    }

    private function should_notify( ?User $from, ?User $to, bool $og = false, bool $is_og_member = false ): bool {
        if (!$from || !$to) return false;
        $setting = ($og && $is_og_member) ? UserSetting::PushNotifyOnOfficialGroupChat : UserSetting::PushNotifyMeOnPM;

        return $from !== $to &&
            $to->getSetting( $setting ) &&
            ($og || !$this->getService(UserHandler::class)->checkRelation($to,$from,SocialRelation::SocialRelationTypeBlock));
    }

    public function broadcastSingleUpdate(GPDirectMessageEvent $event): void {
        if (!$event->post->getReceiverUser()) return;

        $mercure = $this->getService(BroadcastPMUpdateViaMercureAction::class);
        $mercure($event->post->getReceiverUser());
    }

    public function broadcastUpdate(GPMessageNewPostEvent|GPMessageNewThreadEvent $event): void {
        $is_thread = is_a($event, GPMessageNewThreadEvent::class);
        if (!$event->post->getSender()) return;

        $group = $event->post->getReceiverGroup();

        /** @var UserGroupAssociation[] $all_associations */
        $all_associations = $this->getService(EntityManagerInterface::class)->getRepository(UserGroupAssociation::class)->findBy([
            'associationType' => [ UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember ],
            'association' => $group,
            'bref' => false
        ]);

        $targets = array_filter( $all_associations, fn(UserGroupAssociation $a) => $a->getUser() !== $event->post->getSender() );

        $updating_users = [];
        $passive_users = [];

        if ($is_thread) $updating_users = array_map( fn(UserGroupAssociation $a) => $a->getUser(), $targets );
        else {
            $passive_users = array_map( fn(UserGroupAssociation $a) => $a->getUser(), array_filter( $targets, fn(UserGroupAssociation $a) => $a->getRef1() === null || $a->getRef1() !== ($group->getRef1()-1) ) );
            $updating_users = array_map( fn(UserGroupAssociation $a) => $a->getUser(), array_filter( $targets, fn(UserGroupAssociation $a) => $a->getRef1() === ($group->getRef1()-1) ) );
        }

        $mercure = $this->getService(BroadcastPMUpdateViaMercureAction::class);
        $mercure($updating_users);
        $mercure($passive_users, 0);
    }

	public function queueNotifications(GPMessageNewPostEvent|GPMessageNewThreadEvent $event): void {

        if (!$event->post->getSender()) return;

        $group = $event->post->getReceiverGroup();
        /** @var UserGroupAssociation[] $all_associations */
        $all_associations = $this->getService(EntityManagerInterface::class)->getRepository(UserGroupAssociation::class)->findBy(['associationType' => [
            UserGroupAssociation::GroupAssociationTypePrivateMessageMember, UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember
        ], 'association' => $group]);

        if ($og_link = $this->getService(EntityManagerInterface::class)->getRepository(OfficialGroupMessageLink::class)->findOneBy(['messageGroup' => $group]))
            $og_link = $og_link->getOfficialGroup();

        $prepared_post = $this->getService(HTMLService::class)->prepareEmotes( $event->post->getText(), $event->post->getSender() );

        foreach ($all_associations as $association) {
            $as_og_member = !!$og_link && $association->getAssociationType() === UserGroupAssociation::GroupAssociationTypeOfficialGroupMessageMember;
            if ($association->getBref()) continue;
            if ($this->should_notify($event->post->getSender(), $association->getUser(), !!$og_link, $as_og_member)) {
                $prefix = $as_og_member ? $og_link->getUsergroup()->getName() : $this->getService(TranslatorInterface::class)->trans('PN', [], 'global', $association->getUser()->getLanguage() ?? 'en');
                foreach ($association->getUser()->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription)
                    $this->getService(MessageBusInterface::class)->dispatch(
                        new WebPushMessage($subscription,
                            title:         "[{$prefix}] {$group->getName()}",
                            body:          $prepared_post,
                            avatar:        ($og_link?->getAnon()) ? null : $event->post->getSender()->getAvatar()?->getId()
                        )
                    );
            }
        }
	}
}