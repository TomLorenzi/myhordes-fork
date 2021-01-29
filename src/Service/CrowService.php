<?php

namespace App\Service;

use App\Entity\BankAntiAbuse;
use App\Entity\Citizen;
use App\Entity\Forum;
use App\Entity\GlobalPrivateMessage;
use App\Entity\LogEntryTemplate;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Thread;
use App\Entity\User;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CrowService {
    const ModerationActionDomainForum = 1;
    const ModerationActionDomainTownPM = 2;
    const ModerationActionDomainGlobalPM = 3;
    const ModerationActionDomainAccount = 101;

    const ModerationActionTargetThread = 1;
    const ModerationActionTargetPost = 2;
    const ModerationActionTargetForumBan = 101;
    const ModerationActionTargetGameBan = 102;

    const ModerationActionEdit = 1;
    const ModerationActionDelete = 2;
    const ModerationActionImpose = 3;
    const ModerationActionRevoke = 4;

    private EntityManagerInterface $em;
    private ?User $crow_cache = null;

    private function getCrowAccount(): User {
        return $this->crow_cache ?? ($this->crow_cache = $this->em->getRepository(User::class)->find(66));
    }

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param Forum $forum
     * @param string|array $text
     * @param bool $pinned
     * @param bool $translatable
     * @param string|array|null $title
     * @param Thread|null $thread
     */
    public function postToForum( Forum $forum, $text, bool $pinned, bool $translatable, $title = null, $semantic = 0, ?Thread $thread = null ) {

        if (is_array( $text )) {

            foreach ($text as $k => $single_text)
                $this->postToForum(
                    $forum, $single_text, $pinned, $translatable,
                    is_array($title) ? $title[$k] : $title,
                    is_array($semantic) ? $semantic[$k] : $semantic,
                    $thread
                );
                return;
        }

        if ($thread === null) {
            $thread = (new Thread())
                ->setTitle( $title )
                ->setTranslatable( $translatable )
                ->setOwner( $this->getCrowAccount() );
            $forum->addThread($thread);
        }

        if ($pinned) $thread->setPinned( true );
        if ($semantic !== 0) $thread->setSemantic( $semantic );
        $thread->setLastPost( new DateTime() );

        $post = (new Post())
            ->setDate(new DateTime())
            ->setOwner( $this->getCrowAccount() )
            ->setText( $text )
            ->setTranslate( $translatable );
        $thread->addPost($post);

        $this->em->persist( $thread );
        $this->em->persist( $post );
    }


    public function postAsPM( Citizen $receiver, string $title, string $text, int $template = 0, ?int $foreign = null ) {

        $thread = new PrivateMessageThread();

        $thread
            ->setTitle($title)
            ->setLocked(false)
            ->setLastMessage(new DateTime('now'))
            ->setRecipient($receiver);

        $post = new PrivateMessage();
        $post->setDate(new DateTime('now'))
            ->setText($text)
            ->setPrivateMessageThread($thread)
            ->setNew(true)
            ->setRecipient($receiver)
            ->setTemplate( $template )
            ->setForeignID( $foreign );

        $thread
            ->setLastMessage($post->getDate())
            ->addMessage($post);

        $this->em->persist($thread);
        $this->em->persist($post);

    }

    public function createPM_townNegated( User $receiver, string $townName, bool $auto ): GlobalPrivateMessage {
        $template = $auto
            ? $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_townNegatedAuto'])
            : $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_townNegatedAdmin']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( [ 'town' => $townName ])
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    public function createPM_townQuarantine( User $receiver, string $townName, bool $on ): GlobalPrivateMessage {
        $template = $on
            ? $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_townQuarantineOn'])
            : $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_townQuarantineOff']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( [ 'town' => $townName ])
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    public function createPM_moderation( User $receiver, int $domain, int $target, int $action, $object = null, string $reason = ''): ?GlobalPrivateMessage {

        $name = null;
        $data = [];
        switch ($domain) {

            case self::ModerationActionDomainForum: {
                if (!is_a($object, Post::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionDelete: $name = 'gpm_mod_threadDeleted'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionEdit:     $name = 'gpm_mod_postEdited'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionDelete:   $name = 'gpm_mod_postDeleted'; break;
                    default: return null;
                }
                $data = [ 'link_post' => $object->getId(), 'threadname' => $object->getThread()->getTitle(), 'forumname' => $object->getThread()->getForum()->getTitle() ];
                break;
            }

            case self::ModerationActionDomainTownPM: {
                if (!is_a($object, PrivateMessage::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionEdit:   $name = 'gpm_mod_townPMEdited'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionDelete: $name = 'gpm_mod_townPMDeleted'; break;
                    default: return null;
                }
                $data = [ 'threadname' => $object->getPrivateMessageThread()->getTitle() ];
                break;
            }

            case self::ModerationActionDomainGlobalPM: {
                if (!is_a($object, GlobalPrivateMessage::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionEdit:   $name = 'gpm_mod_globalPMEdited'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionDelete: $name = 'gpm_mod_globalPMDeleted'; break;
                    default: return null;
                }
                $data = [ 'threadname' => $object->getReceiverGroup()->getName() ];
                break;
            }

            case self::ModerationActionDomainAccount: {
                if (!is_int($object) && $object !== null) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetForumBan .'.'. self::ModerationActionImpose: $name = 'gpm_mod_forumBanOn'; break;
                    case self::ModerationActionTargetGameBan .'.'.  self::ModerationActionImpose: $name = 'gpm_mod_gameBanOn'; break;
                    case self::ModerationActionTargetForumBan .'.'. self::ModerationActionRevoke: $name = 'gpm_mod_forumBanOff'; break;
                    case self::ModerationActionTargetGameBan .'.'.  self::ModerationActionRevoke: $name = 'gpm_mod_gameBanOff'; break;
                    default: return null;
                }
                $data = [ 'reason' => $reason, 'duration' => $object ?? -1 ];
                break;
            }

            default: break;
        }

        if ($name === null) return null;

        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $name]);
        $data['reason'] = $reason;

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( $data )
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }
}