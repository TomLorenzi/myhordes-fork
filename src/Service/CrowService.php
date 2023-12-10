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


    /**
     * @param string $text
     * @param Post|GlobalPrivateMessage|PrivateMessage|BlackboardEdit|CitizenRankingProxy|User $object
     * @param AdminReport $report
     * @param string|null $note
     * @return void
     */
    public function triggerExternalModNotification(string $text, Post|GlobalPrivateMessage|PrivateMessage|BlackboardEdit|CitizenRankingProxy|User $object, AdminReport $report, ?string $note = null ): void {

        $endpoint = $this->conf->getGlobalConf()->get( MyHordesConf::CONF_MOD_MAIL_DCHOOK );
        $class = ClassUtils::getRealClass(get_class($object));

        if ($endpoint) {

            $id = md5($class . '##' . $object->getId() . "##" . $report->getId());
            $report_dir = "{$this->report_path}/{$id}";
            $report_path = "{$report_dir}/discord";

            $user = match ( $class ) {
                Post::class => $object->getOwner(),
                PrivateMessage::class => $object->getOwner()?->getUser(),
                GlobalPrivateMessage::class => $object->getSender(),
                BlackboardEdit::class, CitizenRankingProxy::class => $object->getUser(),
                User::class => $object,
                default => null
            };

            $complaint_list = [
                'Keinen Grund angeben','Cheating','Flooding oder Spam','Verwendung einer anderen als der Stadtsprache',
                'Beleidigungen / Unangemessener Ausdruck','Pornographie','Hassrede','Verbreitung persönlicher Informationen',
                'Verletzung von Copyright','Aufruf zu Gesetzesverstößen','Ermutigung von Selbstmord oder Selbstverletzung',
                'Unangemessene Profilbeschreibung', 'Unangemessener Avatar', 'Unangemessener Name'
            ];

            if (!file_exists($report_path)) {

                global $kernel;
                $html = $kernel->getContainer()->get(HTMLService::class);

                $discord = new Client($endpoint);

                $message_embed = (new Embed())
                    ->color('FF5500')
                    ->title( match ( $class ) {
                        Post::class => $object->getThread()->getTitle(),
                        PrivateMessage::class => $object->getPrivateMessageThread()->getTitle(),
                        GlobalPrivateMessage::class => $object->getReceiverGroup()->getName(),
                        BlackboardEdit::class => 'The words of Heroes',
                        CitizenRankingProxy::class => 'Citizens',
                        User::class => $object->getName(),
                        default => 'untitled'
                    } )
                    ->description(match ( $class ) {
                        Post::class, PrivateMessage::class, GlobalPrivateMessage::class =>
                        strip_tags(
                            preg_replace(
                                ['/(?:<br ?\/?>)+/', '/<span class="quoteauthor">([\w\d ._-]+)<\/span>/',  '/<blockquote>/', '/<\/blockquote>/', '/<a href="(.*?)">(.*?)<\/a>/'],
                                ["\n", '${1}:', '[**', '**]', '[${2}](${1})'],
                                $html->prepareEmotes( $object->getText())
                            )
                        ),
                        BlackboardEdit::class => $object->getText(),
                        CitizenRankingProxy::class => match ($report->getSpecification()) {
                            AdminReportSpecification::None => 'no content',
                            AdminReportSpecification::CitizenAnnouncement => $object->getCitizen()?->getHome()->getDescription() ?? 'deleted',
                            AdminReportSpecification::CitizenLastWords => $object->getLastWords(),
                            AdminReportSpecification::CitizenTownComment => $object->getComment(),
                        },
                        User::class => strip_tags( preg_replace('/<br ?\/?>/', "\n", $html->prepareEmotes( $this->em->getRepository(UserDescription::class)->findOneBy(['user' => $user])?->getText() ?? '[no description]' ) ) ),
                        default => 'no content'
                    })
                    ->url(match ( $class ) {
                        Post::class => $this->url_generator->generate( 'forum_jump_view', [ 'pid' => $object->getId() ], UrlGeneratorInterface::ABSOLUTE_URL ),
                        PrivateMessage::class, GlobalPrivateMessage::class => $this->url_generator->generate('admin_reports', [ 'tab' => 'reports' ], UrlGeneratorInterface::ABSOLUTE_URL ),
                        BlackboardEdit::class => $this->url_generator->generate( 'admin_town_dashboard', ['id' => $object->getTown()->getId(), 'tab' => 'blackboard'], UrlGeneratorInterface::ABSOLUTE_URL ),
                        CitizenRankingProxy::class => match ($report->getSpecification()) {
                            AdminReportSpecification::None => 'no content',
                            AdminReportSpecification::CitizenAnnouncement => $object->getCitizen() ? $this->url_generator->generate( 'admin_town_dashboard', ['id' => $object->getCitizen()->getTown()->getId(), 'tab' => 'citizens'], UrlGeneratorInterface::ABSOLUTE_URL ) : 'deleted',
                            AdminReportSpecification::CitizenLastWords, AdminReportSpecification::CitizenTownComment => $this->url_generator->generate( 'soul_view_town', ['sid' => $object->getUser()->getId(), 'idtown' => $object->getTown()->getId()], UrlGeneratorInterface::ABSOLUTE_URL )
                        },
                        User::class => $this->url_generator->generate( 'soul_visit', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL  ),
                        default => 'no content'
                    });


                if ($user) $message_embed->author(
                    $user->getName(),
                    $this->url_generator->generate( 'admin_users_account_view', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                    $user->getAvatar() ? $this->url_generator->generate( 'app_web_avatar', ['uid' => $user->getId(), 'name' => $user->getAvatar()->getFilename(), 'ext' => $user->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
                );

                if ($report->getReason() >= 0 && $report->getReason() < count($complaint_list))
                    $reason = $this->trans->trans( $complaint_list[$report->getReason()], [], 'global', 'en' );
                else $reason = $this->trans->trans( 'Keinen Grund angeben', [], 'global', 'en' );

                $report_embed = (new Embed())
                    ->color('	6A00FF')
                    ->title($reason)
                    ->description($report->getDetails() ?? 'No description');

                if ($report->getSourceUser()) $report_embed->author(
                    $report->getSourceUser()->getName(),
                    $this->url_generator->generate( 'admin_users_account_view', ['id' => $report->getSourceUser()->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                    $report->getSourceUser()->getAvatar() ? $this->url_generator->generate( 'app_web_avatar', ['uid' => $report->getSourceUser()->getId(), 'name' => $report->getSourceUser()->getAvatar()->getFilename(), 'ext' => $report->getSourceUser()->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
                );

                $discord
                    ->message(":loudspeaker: **{$text}**" . ($note ? "\n{$note}" : '') . "\n\n")
                    ->embed( $message_embed )
                    ->embed( $report_embed );

                $this->bus->dispatch( new DiscordMessage( $discord ) );

                mkdir( $report_dir, recursive: true );
                file_put_contents( $report_path, "".time() );
            }

        }

    }

}