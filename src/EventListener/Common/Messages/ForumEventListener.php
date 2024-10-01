<?php


namespace App\EventListener\Common\Messages;

use App\Entity\Citizen;
use App\Entity\ForumThreadSubscription;
use App\Entity\ForumUsagePermissions;
use App\Entity\SocialRelation;
use App\Entity\Town;
use App\Entity\User;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Messages\Forum\ForumMessageNewPostEvent;
use App\Event\Common\Messages\Forum\ForumMessageNewThreadEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\WebPush\WebPushMessage;
use App\Service\Actions\Mercure\BroadcastPMUpdateViaMercureAction;
use App\Service\CitizenHandler;
use App\Service\CrowService;
use App\Service\PermissionHandler;
use App\Service\PictoHandler;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: ForumMessageNewThreadEvent::class, method: 'queueMentions', priority: 0)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueMentions', priority: 0)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueSubscriptions', priority: 10)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueDistinctions', priority: 20)]
#[AsEventListener(event: ForumMessageNewThreadEvent::class, method: 'removeForumCheckmarkForTownForums', priority: -10)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'removeForumCheckmarkForTownForums', priority: -10)]
final class ForumEventListener implements ServiceSubscriberInterface
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
            UserHandler::class,
            PermissionHandler::class,
            CrowService::class,
            PictoHandler::class,
            CitizenHandler::class,
            BroadcastPMUpdateViaMercureAction::class
        ];
    }

    private function should_notify( User $from, User $to, ?Town $town ): bool {
        return $from !== $to && match ( $to->getSetting( UserSetting::NotifyMeWhenMentioned ) ) {
                1 => !!$town,   // Only town
                2 => true,      // Always
                3 => !$town,    // Only global
                default => false,
            } && !$this->getService(UserHandler::class)->checkRelation($to,$from,SocialRelation::SocialRelationTypeBlock);
    }

	public function queueSubscriptions(ForumMessageNewPostEvent $event): void {
        /** @var ForumThreadSubscription[] $subscriptions */
        $subscriptions = $this->getService(EntityManagerInterface::class)->getRepository(ForumThreadSubscription::class)->matching(
            (new Criteria())
                ->andWhere( Criteria::expr()->neq('user', $event->post->getOwner()) )
                ->andWhere( Criteria::expr()->eq('thread', $event->post->getThread()) )
                ->andWhere( Criteria::expr()->lt('num', 10) )
        );

        $inform_users = [];
        foreach ($subscriptions as $s) {
            $was_read = $s->getNum() === 0;
            $this->getService(EntityManagerInterface::class)->persist($s->setNum($s->getNum() + 1));

            // Dispatch WebPush notifications
            $subscribed_user = $s->getUser();
            $lang = $subscribed_user->getLanguage() ?? 'en';

            if (!$this->getService(PermissionHandler::class)->checkEffectivePermissions( $subscribed_user, $event->post->getThread()->getForum(), ForumUsagePermissions::PermissionReadThreads ))
                continue;

            if ($was_read)
                $inform_users[] = $subscribed_user;

            foreach ( $subscribed_user->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription )
                $this->getService(MessageBusInterface::class)->dispatch(
                    new WebPushMessage($subscription,
                        title: $this->getService(TranslatorInterface::class)->trans('Neue Antwort in abonnierter Diskussion', [], 'global', $lang ),
                        body: $this->getService(TranslatorInterface::class)->trans('{player} hat auf die Diskussion "{threadname}" im Forum "{forumname}" geantwortet.', [
                            'player' => $event->post->isAnonymous() ? '???' : $event->post->getOwner(),
                            'threadname' => $event->post->getThread()->getTranslatable() ? $this->getService(TranslatorInterface::class)->trans($event->post->getThread()->getTitle(), [], 'game', $lang) : $event->post->getThread()->getTitle(),
                            'forumname' => $event->post->getThread()->getForum()->getLocalizedTitle( $lang )
                        ], 'global', $lang ),
                        avatar: $event->post->isAnonymous() ? null : $event->post->getOwner()->getAvatar()?->getId()
                    )
                );
        }

        if (!empty($subscriptions)) try { $this->getService(EntityManagerInterface::class)->flush(); } catch (\Throwable) {}
        if (!empty($inform_users)) {
            $mercure = $this->getService(BroadcastPMUpdateViaMercureAction::class);
            $mercure($inform_users);
        }
	}

    public function queueDistinctions(ForumMessageNewPostEvent $event): void {
        $forum = $event->post->getThread()->getForum();
        $user = $event->post->getOwner();

        if ($forum->getTown()) {
            /** @var Citizen $current_citizen */
            $current_citizen = $this->getService(EntityManagerInterface::class)->getRepository(Citizen::class)->findOneBy(['user' => $user, 'town' => $forum->getTown(), 'alive' => true]);
            if ($current_citizen)
                // Give picto if the post is in the town forum
                $this->getService(PictoHandler::class)->give_picto($current_citizen, 'r_forum_#00');
        }
    }


    public function queueMentions(ForumMessageNewPostEvent|ForumMessageNewThreadEvent $event): void {
        $has_notif = false;

        $forum = $event->post->getThread()->getForum();
        $user = $event->post->getOwner();

        if (count($event->insight->taggedUsers) <= ($forum->getTown() ? 10 : 5) )
            foreach ( $event->insight->taggedUsers as $tagged_user )
                if ( $this->should_notify( $user, $tagged_user, $forum->getTown() ) && $this->getService(PermissionHandler::class)->checkEffectivePermissions( $tagged_user, $forum, ForumUsagePermissions::PermissionReadThreads ) ) {
                    if (!$event->post->isAnonymous())
                        $this->getService(EntityManagerInterface::class)->persist( $this->getService(CrowService::class)->createPM_mentionNotification( $tagged_user, $event->post ) );

                    // Dispatch WebPush notifications
                    $lang = $tagged_user->getLanguage() ?? 'en';
                    foreach ( $tagged_user->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription )
                        $this->getService(MessageBusInterface::class)->dispatch(
                            new WebPushMessage($subscription,
                                title: $this->getService(TranslatorInterface::class)->trans('Du wurdest erwähnt', [], 'global', $lang ),
                                body: $this->getService(TranslatorInterface::class)->trans('{player} hat dich auf MyHordes in einem Post unter "{threadname}" im Forum "{forumname}" erwähnt.', [
                                    'player' => $event->post->isAnonymous() ? '???' : $user,
                                    'threadname' => $event->post->getThread()->getTranslatable() ? $this->getService(TranslatorInterface::class)->trans($event->post->getThread()->getTitle(), [], 'game', $lang) : $event->post->getThread()->getTitle(),
                                    'forumname' => $forum->getLocalizedTitle( $lang )
                                ], 'global', $lang ),
                                avatar: $event->post->isAnonymous() ? null : $user->getAvatar()?->getId()
                            )
                        );

                    $has_notif = true;
                }

        if ($has_notif) try { $this->getService(EntityManagerInterface::class)->flush(); } catch (\Throwable) {}
    }

    public function removeForumCheckmarkForTownForums(ForumMessageNewPostEvent|ForumMessageNewThreadEvent $event): void {
        foreach ($event->post?->getThread()?->getForum()?->getTown()?->getCitizens() ?? [] as $c)
            if ($c->getAlive() && $c->getUser() !== $event->post->getOwner() && $c->hasStatus('tg_chk_forum') && $this->getService(CitizenHandler::class)->removeStatus( $c, 'tg_chk_forum' ))
                $this->getService(EntityManagerInterface::class)->persist($c);
    }

}