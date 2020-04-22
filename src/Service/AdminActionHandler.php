<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\Forum;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Service\DeathHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminActionHandler
{
    private $entity_manager;
    /**
     * @var DeathHandler
     */
    private $death_handler;
    private $translator;
    private $log;

    public function __construct( EntityManagerInterface $em, DeathHandler $dh, TranslatorInterface $ti, LogTemplateHandler $lt)
    {
        $this->entity_manager = $em;
        $this->death_handler = $dh;
        $this->translator = $ti;
        $this->log = $lt;
    }

    protected function hasRights(int $sourceUser)
    {
        $userRoles = $this->entity_manager->getRepository(User::class)->find($sourceUser)->getRoles();
        if (in_array("ROLE_ADMIN", $userRoles))
            return true;
        return false;
    }

    public function headshot(int $sourceUser, int $targetUserId): string
    {
        if(!$this->hasRights($sourceUser))
            return $this->translator->trans('Dazu hast Du kein Recht.', [], 'game');        
        $user = $this->entity_manager->getRepository(User::class)->find($targetUserId);
        /**
        * @var Citizen
        */
        $citizen = $user->getAliveCitizen();
        if (isset($citizen)) {
            $rem = [];
            $this->death_handler->kill( $citizen, CauseOfDeath::Headshot, $rem );
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
            $this->entity_manager->flush();
            $message = $this->translator->trans('%username% wurde standrechtlich erschossen.', ['%username%' => '<span>' . $user->getUsername() . '</span>'], 'game');
        }
        else {
            $message = $this->translator->trans('%username% gehört keiner Stadt an.', ['%username%' => '<span>' . $user->getUsername() . '</span>'], 'game');
        }
        return $message;
    }

    public function lockThread(int $sourceUser, int $forumId, int $threadId): bool
    {
        if(!$this->hasRights($sourceUser))
            return false;
        /**
        * @var Thread
        */
        $thread = $this->entity_manager->getRepository( Thread::class )->find( $threadId );
        if ($thread === null || $thread->getForum()->getId() !== $forumId) return false;
        $thread->setLocked(true);
        $this->entity_manager->persist($thread);
        $this->entity_manager->flush();
        return true;
    }

    public function unlockThread(int $sourceUser, int $forumId, int $threadId): bool
    {
        if(!$this->hasRights($sourceUser))
            return false;
        /**
        * @var Thread
        */
        $thread = $this->entity_manager->getRepository( Thread::class )->find( $threadId );
        if ($thread === null || $thread->getForum()->getId() !== $forumId) return false;
        $thread->setLocked(false);
        $this->entity_manager->persist($thread);
        $this->entity_manager->flush();
        return true;
    }

    public function crowPost(int $sourceUser, Forum $forum, ?Thread $thread, string $text, ?string $title): ?Thread
    {
        if(!$this->hasRights($sourceUser))
            return null;

        $theCrow = $this->entity_manager->getRepository(User::class)->find(66);

        if (!isset($thread)){
            $thread = (new Thread())->setTitle( $title )->setOwner($theCrow);
            $forum->addThread($thread);
        }
               
        $post = (new Post())
            ->setOwner( $theCrow )
            ->setText( $text )
            ->setDate( new DateTime('now') );
        $thread->addPost($post)->setLastPost( $post->getDate() );
        try {
            $this->entity_manager->persist($thread);
            $this->entity_manager->persist($forum);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return null;
        }
        return $thread;
    }
}