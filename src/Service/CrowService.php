<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\CommunityEvent;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Forum;
use App\Entity\GlobalPrivateMessage;
use App\Entity\LogEntryTemplate;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageThread;
use App\Entity\Thread;
use App\Entity\ThreadTag;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

class CrowService {
    const int ModerationActionDomainForum = 1;
    const int ModerationActionDomainTownPM = 2;
    const int ModerationActionDomainGlobalPM = 3;
    const int ModerationActionDomainAccount = 101;
    const int ModerationActionDomainRanking = 201;
    const int ModerationActionDomainEvents = 301;

    const int ModerationActionTargetThread = 1;
    const int ModerationActionTargetPost = 2;
    const int ModerationActionTargetForumBan = 101;
    const int ModerationActionTargetGameBan = 102;
    const int ModerationActionTargetAnyBan = 103;
    const int ModerationActionTargetGameName = 201;
    const int ModerationActionTargetEvent = 301;
    const int ModerationActionTargetEventValidation = 302;

    const int ModerationActionEdit = 1;
    const int ModerationActionDelete = 2;
    const int ModerationActionImpose = 3;
    const int ModerationActionRevoke = 4;
    const int ModerationActionMove = 5;
    const int ModerationActionClose = 6;
    const int ModerationActionSolve = 7;
    const int ModerationActionOpen = 8;

    private function getCrowAccount(): User {
        return $this->em->getRepository(User::class)->find(66);
    }

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventProxyService $proxy,
    ) { }

    /**
     * Post a message in a forum/thread as the crow
     * @param Forum $forum
     * @param string|array $text
     * @param bool $pinned
     * @param bool $translatable
     * @param string|array|null $title
     * @param int|array $semantic
     * @param Thread|null $thread
     */
    public function postToForum( Forum $forum, string|array $text, bool $pinned, bool $translatable, string|array $title = null, int|array $semantic = 0, ?Thread $thread = null ): void
    {

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
                ->setOwner( $this->getCrowAccount() )
                ->setTag($this->em->getRepository(ThreadTag::class)->findOneBy(['name' => 'official']));
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


    public function postAsPM( Citizen $receiver, string $title, string $text, int $template = 0, ?int $foreign = null, $data = null ): void
    {

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
            ->setForeignID( $foreign )
            ->setAdditionalData( $data );

        $thread
            ->setLastMessage($post->getDate())
            ->addMessage($post);

        $this->em->persist($thread);
        $this->em->persist($post);

    }

    protected function createMessage(
        LogEntryTemplate|string|null $template = null,
        ?array                       $data = null,
        User                         $receiver = null,
        ?DateTimeInterface           $timestamp = null,
        ?string                      $message = null,
    ): ?GlobalPrivateMessage {
        if (is_string($template)) $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $template]);
        if (!$receiver) return null;
        $entity = (new GlobalPrivateMessage())
            ->setTemplate($template)
            ->setText($message)
            ->setData($data)
            ->setTimestamp( $timestamp ?? new DateTime('now') )
            ->setReceiverUser($receiver)
            ->setSender($this->getCrowAccount())
            ->setSeen(false);

        $this->proxy->globalPrivateDirectMessageNewPostEvent( $entity );
        return $entity;
    }

    public function createPM_townNegated( User $receiver, string $townName, bool $auto ): GlobalPrivateMessage {
        return $this->createMessage(
            $auto
                ? 'gpm_townNegatedAuto'
                : 'gpm_townNegatedAdmin',
            [ 'town' => $townName ],
            $receiver
        );
    }


    public function createPM_townQuarantine( User $receiver, string $townName, bool $on ): GlobalPrivateMessage {
        return $this->createMessage(
            $on
                ? 'gpm_townQuarantineOn'
                : 'gpm_townQuarantineOff',
            [ 'town' => $townName ],
            $receiver
        );
    }

    /**
     * @param User $receiver
     * @param Award[] $awards
     * @return GlobalPrivateMessage
     */
    public function createPM_titleUnlock(User $receiver, array $awards): GlobalPrivateMessage
    {
        return $this->createMessage(
            'gpm_unlock_titles2',
            ['list' => array_map(fn(Award $a) => $a->getPrototype() ? $a->getPrototype()->getId() : -$a->getId(), $awards) ],
            $receiver
        );
    }

    /**
     * @param User $receiver
     * @param array $pictos
     * @param FeatureUnlockPrototype[] $features
     * @param string|null $importLang
     * @param int $season
     * @return GlobalPrivateMessage
     */
    public function createPM_seasonalRewards(User $receiver, array $pictos, array $features, ?string $importLang, int $season): GlobalPrivateMessage
    {
        return $this->createMessage(
            'gpm_season_reward',
            [
                'pictos' => array_map(fn(array $p) => [$p[0]->getId(), $p[1]], $pictos),
                'features' => array_map(fn(FeatureUnlockPrototype $f) => $f->getId(), $features),
                'server' => match ($importLang) {
                    'de' => 'Die Verdammten',
                    'en' => 'Die2Nite',
                    'es' => 'Zombinoia',
                    'fr' => 'Hordes',
                    default => 'MyHordes'
                },
                'season' => $season
            ],
            $receiver
        );
    }

    /**
     * @param User $receiver
     * @param Post $post
     * @return GlobalPrivateMessage
     */
    public function createPM_mentionNotification(User $receiver, Post $post): GlobalPrivateMessage
    {
        return $this->createMessage(
            'gpm_post_notification',
            [
                'link_post' => $post->getId(),
                'threadname' => $post->getThread()->getTitle(),
                'forumname' => $post->getThread()->getForum()->getTitle(),
                'player' => $post->getOwner()->getId(),
                'threadname__translate' => $post->getThread()->getTranslatable() ? 'game' : null,
            ],
            $receiver
        );
    }

    /**
     * @param User $receiver
     * @param User $sender
     * @param bool $revert
     * @return GlobalPrivateMessage
     */
    public function createPM_friendNotification(User $receiver, User $sender, bool $revert = false): GlobalPrivateMessage
    {
        return $this->createMessage(
            $revert ? 'gpm_friend_reverse_notification' : 'gpm_friend_notification',
            ['player' => $sender->getId() ],
            $receiver
        );
    }

    public function createPM( User $receiver, string $message): ?GlobalPrivateMessage {
        return $this->createMessage(
            receiver: $receiver,
            message: $message,
        );
    }

    public function createPM_moderation( User $receiver, int $domain, int $target, int $action, $object = null, string $reason = ''): ?GlobalPrivateMessage {

        $name = null;
        $data = [];
        switch ($domain) {

            case self::ModerationActionDomainForum: {
                if (!is_a($object, Post::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionDelete: $name = 'gpm_mod_threadDeleted'; break;
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionMove:   $name = 'gpm_mod_threadMoved'; break;
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionClose:  $name = 'gpm_mod_threadClosed'; break;
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionSolve:  $name = 'gpm_mod_threadSolved'; break;
                    case self::ModerationActionTargetThread .'.'. self::ModerationActionOpen:   $name = 'gpm_mod_threadReopened'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionEdit:     $name = 'gpm_mod_postEdited'; break;
                    case self::ModerationActionTargetPost .'.'. self::ModerationActionDelete:   $name = 'gpm_mod_postDeleted'; break;
                    default: return null;
                }
                $data = [ 'link_post' => $object->getId(), 'threadname' => $object->getThread()->getTitle(), 'forumname' => $object->getThread()->getForum()->getTitle(), 'reason' => $reason ];
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
                switch ($target) {
                    case self::ModerationActionTargetForumBan:
                    case self::ModerationActionTargetGameBan:
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

                    case self::ModerationActionTargetAnyBan:
                        if (!is_array($object)) return null;
                        ['mask' => $mask, 'duration' => $duration, 'old_duration' => $old_duration] = $object;
                        switch ($action) {
                            case self::ModerationActionImpose: $name = 'gpm_mod_AnyBanOn'; break;
                            case self::ModerationActionEdit:   $name = 'gpm_mod_AnyBanMod'; break;
                            case self::ModerationActionRevoke: $name = 'gpm_mod_AnyBanOff'; break;
                            default: return null;
                        }
                        $data = [ 'reason' => $reason, 'duration' => $duration ?? -1, 'old_duration' => $old_duration, 'type' => $mask ];
                        break;

                    default: return null;
                }

                break;
            }

            case self::ModerationActionDomainRanking: {
                if (!is_a($object, Town::class) && !is_a($object, TownRankingProxy::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetGameName .'.'. self::ModerationActionEdit: $name = 'gpm_mod_townNameChange'; break;
                    default: return null;
                }
                $data = [ 'townOld' => $reason, 'townNew' => $object->getName() ];
                break;
            }

            case self::ModerationActionDomainEvents:
                if (!is_a($object, CommunityEvent::class)) return null;
                switch ("{$target}.{$action}") {
                    case self::ModerationActionTargetEvent .'.'. self::ModerationActionDelete: $name = 'gpm_mod_eventDeleted'; break;
                    case self::ModerationActionTargetEventValidation .'.'. self::ModerationActionSolve:   $name = 'gpm_mod_eventValidated'; break;
                    default: return null;
                }
                $data = [ 'eventName' => $object->getMeta('en')?->getName() ?? $object->getMeta('fr')?->getName() ?? $object->getMeta('de')?->getName() ?? $object->getMeta('es')?->getName() ?? '---' ];
                break;

            default: break;
        }

        if ($name === null) return null;

        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $name]);
        $data['reason'] = $reason;

        return $this->createMessage(
            $template,
            $data,
            $receiver
        );
    }
}