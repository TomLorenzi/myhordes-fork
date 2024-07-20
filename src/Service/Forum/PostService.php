<?php

namespace App\Service\Forum;

use App\Entity\Activity;
use App\Entity\AdminDeletion;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\LogEntryTemplate;
use App\Entity\OfficialGroup;
use App\Entity\Post;
use App\Entity\User;
use App\EventListener\ContainerTypeTrait;
use App\Service\CrowService;
use App\Service\PermissionHandler;
use App\Service\UserHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class PostService implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            PermissionHandler::class,
            CrowService::class
        ];
    }

    /**
     * @param Post $post
     * @param User $mod
     * @param string|null $reason
     * @param bool $checkModPermissions
     * @param bool $addCrowAnnounce
     * @return bool
     */
    public function hidePost(Post $post, User $mod, ?string $reason = null, bool $checkModPermissions = true, bool $addCrowAnnounce = true, bool $purgeNotifications = true): bool {

        $reason ??= "---";

        $em = $this->getService(EntityManagerInterface::class);
        $permissionHandler = $checkModPermissions ? $this->getService(PermissionHandler::class) : null;
        if ($checkModPermissions && !$permissionHandler->checkEffectivePermissions($mod, $post->getThread()->getForum(), ForumUsagePermissions::PermissionModerate))
            return false;

        if ($post->getHidden()) return false;

        try {
            $post->setHidden(true);
            $em->persist( $post );
            $em->persist( (new AdminDeletion())
                ->setSourceUser( $mod )
                ->setTimestamp( new DateTime('now') )
                ->setReason( $reason )
                ->setPost( $post ) );

            $reports = $post->getAdminReports(true);
            foreach ($reports as $report)
                $em->persist($report->setSeen(true));

            $notification = null;
            if ($post === $post->getThread()->firstPost(true)) {
                $post->getThread()->setHidden(true)->setLocked(true);
                $em->persist($post->getThread());

                if ($addCrowAnnounce)
                    $notification = $this->getService(CrowService::class)->createPM_moderation( $post->getOwner(),
                                                                CrowService::ModerationActionDomainForum, CrowService::ModerationActionTargetThread, CrowService::ModerationActionDelete,
                                                                $post, $reason
                    );

            } else {
                $notification = $this->getService(CrowService::class)->createPM_moderation( $post->getOwner(),
                                                            CrowService::ModerationActionDomainForum, CrowService::ModerationActionTargetPost, CrowService::ModerationActionDelete,
                                                            $post, $reason
                );
            }

            if ($notification) $em->persist($notification);

            if ($purgeNotifications) {
                $template = $em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_post_notification']);
                $relatedNotifications = $em->getRepository(GlobalPrivateMessage::class)
                    ->createQueryBuilder('g')
                    ->where( 'g.template = :value' )->setParameter('value', $template)
                    ->andWhere("JSON_EXTRACT(g.data, '$.link_post') = :pid")->setParameter('pid', $post->getId())
                    ->getQuery()->getResult();

                foreach ($relatedNotifications as $n)
                    $em->remove($n);
            }

            $em->flush();

            $em->persist( $post->getThread()->setLastPost( $post->getThread()->lastPost(false)?->getDate() ?? $post->getThread()->lastPost(true)?->getDate() ?? new DateTime() ) );
            $em->flush();

            return true;
        }
        catch (\Throwable $e) {
            return false;
        }
    }

}