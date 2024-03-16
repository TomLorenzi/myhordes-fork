<?php

namespace App\Service;

use App\Entity\AdminReport;
use App\Entity\Award;
use App\Entity\BlackboardEdit;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
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
use App\Entity\UserDescription;
use App\Enum\AdminReportSpecification;
use App\Messages\Discord\DiscordMessage;
use App\Structures\MyHordesConf;
use DateTime;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CrowService {
    const ModerationActionDomainForum = 1;
    const ModerationActionDomainTownPM = 2;
    const ModerationActionDomainGlobalPM = 3;
    const ModerationActionDomainAccount = 101;
    const ModerationActionDomainRanking = 201;
    const ModerationActionDomainEvents = 301;

    const ModerationActionTargetThread = 1;
    const ModerationActionTargetPost = 2;
    const ModerationActionTargetForumBan = 101;
    const ModerationActionTargetGameBan = 102;
    const ModerationActionTargetAnyBan = 103;
    const ModerationActionTargetGameName = 201;
    const ModerationActionTargetEvent = 301;
    const ModerationActionTargetEventValidation = 302;

    const ModerationActionEdit = 1;
    const ModerationActionDelete = 2;
    const ModerationActionImpose = 3;
    const ModerationActionRevoke = 4;
    const ModerationActionMove = 5;
    const ModerationActionClose = 6;
    const ModerationActionSolve = 7;
    const ModerationActionOpen = 8;

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $url_generator;
    private ConfMaster $conf;
    private ?string $report_path;
    private TranslatorInterface $trans;
    private MessageBusInterface $bus;


    private function getCrowAccount(): User {
        return $this->em->getRepository(User::class)->find(66);
    }

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, ConfMaster $conf, UrlGeneratorInterface $url_generator, TranslatorInterface $trans, MessageBusInterface $bus )
    {
        $this->em = $em;
        $this->conf = $conf;
        $this->trans = $trans;
        $this->url_generator = $url_generator;
        $this->report_path = "{$params->get('kernel.project_dir')}/var/reports";
        $this->bus = $bus;
    }

    /**
     * Post a message in a forum/thread as the crow
     * @param Forum $forum
     * @param string|array $text
     * @param bool $pinned
     * @param bool $translatable
     * @param string|array|null $title
     * @param int $semantic
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


    public function postAsPM( Citizen $receiver, string $title, string $text, int $template = 0, ?int $foreign = null, $data = null ) {

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

    /**
     * @param User $receiver
     * @param Award[] $awards
     * @return GlobalPrivateMessage
     */
    public function createPM_titleUnlock(User $receiver, array $awards): GlobalPrivateMessage
    {
        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_unlock_titles2']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( ['list' => array_map(fn(Award $a) => $a->getPrototype() ? $a->getPrototype()->getId() : -$a->getId(), $awards) ] )
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    /**
     * @param User $receiver
     * @param array $pictos
     * @param FeatureUnlockPrototype[] $features
     * @return GlobalPrivateMessage
     */
    public function createPM_seasonalRewards(User $receiver, array $pictos, array $features, ?string $importLang, int $season): GlobalPrivateMessage
    {
        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_season_reward']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( [
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
            ] )
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    /**
     * @param User $receiver
     * @param Post $post
     * @return GlobalPrivateMessage
     */
    public function createPM_mentionNotification(User $receiver, Post $post): GlobalPrivateMessage
    {
        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => 'gpm_post_notification']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( [
               'link_post' => $post->getId(),
               'threadname' => $post->getThread()->getTitle(),
               'forumname' => $post->getThread()->getForum()->getTitle(),
               'player' => $post->getOwner()->getId(),
               'threadname__translate' => $post->getThread()->getTranslatable() ? 'game' : null,
            ] )
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    /**
     * @param User $receiver
     * @return GlobalPrivateMessage
     */
    public function createPM_friendNotification(User $receiver, User $sender, bool $revert = false): GlobalPrivateMessage
    {
        $template = $this->em->getRepository(LogEntryTemplate::class)->findOneBy(['name' => $revert ? 'gpm_friend_reverse_notification' : 'gpm_friend_notification']);

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( ['player' => $sender->getId() ])
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }

    public function createPM( User $receiver, string $message): ?GlobalPrivateMessage {
        return (new GlobalPrivateMessage())
            ->setText($message)
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

        return (new GlobalPrivateMessage())
            ->setTemplate( $template )
            ->setData( $data )
            ->setTimestamp( new DateTime('now') )
            ->setReceiverUser( $receiver )
            ->setSender( $this->getCrowAccount() )
            ->setSeen( false );
    }
}