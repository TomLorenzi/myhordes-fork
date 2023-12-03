<?php


namespace App\EventListener\Common\Messages;

use App\Entity\Citizen;
use App\Entity\ForumThreadSubscription;
use App\Entity\ForumUsagePermissions;
use App\Entity\SocialRelation;
use App\Entity\Town;
use App\Entity\User;
use App\Enum\UserSetting;
use App\Event\Common\Messages\Forum\ForumMessageNewPostEvent;
use App\Event\Common\Messages\Forum\ForumMessageNewThreadEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CrowService;
use App\Service\PermissionHandler;
use App\Service\PictoHandler;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: ForumMessageNewThreadEvent::class, method: 'queueMentions', priority: 0)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueMentions', priority: 0)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueSubscriptions', priority: 10)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueSubscriptions', priority: 10)]
#[AsEventListener(event: ForumMessageNewPostEvent::class, method: 'queueDistinctions', priority: 20)]
final class ForumEventListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            UserHandler::class,
            PermissionHandler::class,
            CrowService::class,
            PictoHandler::class
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

        foreach ($subscriptions as $s) $this->getService(EntityManagerInterface::class)->persist($s->setNum($s->getNum() + 1));
        if (!empty($subscriptions)) try { $this->getService(EntityManagerInterface::class)->flush(); } catch (\Throwable) {}
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
                    $this->getService(EntityManagerInterface::class)->persist( $this->getService(CrowService::class)->createPM_mentionNotification( $tagged_user, $event->post ) );
                    $has_notif = true;
                }

        if ($has_notif) try { $this->getService(EntityManagerInterface::class)->flush(); } catch (\Throwable) {}
    }

}