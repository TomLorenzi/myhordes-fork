<?php


namespace App\EventListener\Common\Social;

use App\Entity\BlackboardEdit;
use App\Entity\CitizenRankingProxy;
use App\Entity\GlobalPrivateMessage;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\User;
use App\Entity\UserDescription;
use App\Enum\AdminReportSpecification;
use App\Enum\NotificationSubscriptionType;
use App\Enum\UserSetting;
use App\Event\Common\Social\ContentReportEvents\BlackboardEditContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\CitizenRankingProxyContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\ContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\GlobalPrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PostContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\UserContentReportEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\Discord\DiscordMessage;
use App\Messages\WebPush\WebPushMessage;
use App\Service\ConfMaster;
use App\Service\HTMLService;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: PostContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]
#[AsEventListener(event: GlobalPrivateMessageContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]
#[AsEventListener(event: PrivateMessageContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]
#[AsEventListener(event: BlackboardEditContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]
#[AsEventListener(event: CitizenRankingProxyContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]
#[AsEventListener(event: UserContentReportEvent::class, method: 'handleReportDiscordNotification', priority: 0)]

#[AsEventListener(event: PostContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]
#[AsEventListener(event: GlobalPrivateMessageContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]
#[AsEventListener(event: PrivateMessageContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]
#[AsEventListener(event: BlackboardEditContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]
#[AsEventListener(event: CitizenRankingProxyContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]
#[AsEventListener(event: UserContentReportEvent::class, method: 'handleReportBrowserNotification', priority: 0)]

final class ContentReportEventListener implements ServiceSubscriberInterface
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
            ConfMaster::class,
            UrlGeneratorInterface::class,
            HTMLService::class,
            UserHandler::class
        ];
    }

    private function getTitle( string $class, ContentReportEvent $event, ?string $lang = null ): string {
        return match( $class ) {
            Post::class => $this->getService(TranslatorInterface::class)->trans( 'Ein Forenpost wurde gemeldet.', [], 'global', $lang ),
            PrivateMessage::class, $this->getService(TranslatorInterface::class)->trans( 'Eine Stadt-PN wurde gemeldet.', [], 'global', $lang ),
            BlackboardEdit::class => $this->getService(TranslatorInterface::class)->trans( 'Ein Eintrag auf dem Schwarzen Brett wurde gemeldet.', [], 'global', $lang ),
            CitizenRankingProxy::class => match ($event->report->getSpecification()) {
                AdminReportSpecification::None => '',
                AdminReportSpecification::CitizenAnnouncement => $this->getService(TranslatorInterface::class)->trans('Die Nachricht eines Bürgers wurde gemeldet.', [], 'global', $lang),
                AdminReportSpecification::CitizenLastWords => $this->getService(TranslatorInterface::class)->trans('Die Letzten Worte eines Bürgers wurde gemeldet.', [], 'global', $lang),
                AdminReportSpecification::CitizenTownComment => $this->getService(TranslatorInterface::class)->trans('Der Stadtkommentar eines Bürgers wurde gemeldet.', [], 'global', $lang),
            },
            User::class => $this->getService(TranslatorInterface::class)->trans('Ein Spieleraccount wurde gemeldet.', [], 'global', $lang),
            default => ''
        };
    }

    private function getSubtitle( ContentReportEvent $event, ?string $lang = null ): string {
        return $this->getService(TranslatorInterface::class)->trans('Dies ist Meldung #{num}.', ['num' => $event->count], 'global', $lang);
    }

    private function getComplaintCategory( ContentReportEvent $event, ?string $lang = null ): string {
        $complaint_list = [
            'Keinen Grund angeben','Cheating','Flooding oder Spam','Verwendung einer anderen als der Stadtsprache',
            'Beleidigungen / Unangemessener Ausdruck','Pornographie','Hassrede','Verbreitung persönlicher Informationen',
            'Verletzung von Copyright','Aufruf zu Gesetzesverstößen','Ermutigung von Selbstmord oder Selbstverletzung',
            'Unangemessene Profilbeschreibung', 'Unangemessener Avatar', 'Unangemessener Name'
        ];

        if ($event->report->getReason() >= 0 && $event->report->getReason() < count($complaint_list))
            return $this->getService(TranslatorInterface::class)->trans( $complaint_list[$event->report->getReason()], [], 'global', $lang );
        else return $this->getService(TranslatorInterface::class)->trans( 'Keinen Grund angeben', [], 'global', $lang );
    }

    private function getReportedTitle( string $class, ContentReportEvent $event, ?string $lang = null ): string {
        return match ( $class ) {
            Post::class => $event->subject->getThread()->getTitle(),
            PrivateMessage::class => $event->subject->getPrivateMessageThread()->getTitle(),
            GlobalPrivateMessage::class => $event->subject->getReceiverGroup()->getName(),
            BlackboardEdit::class => $this->getService(TranslatorInterface::class)->trans('Schwarzes Brett', [], 'admin', $lang),
            CitizenRankingProxy::class => $this->getService(TranslatorInterface::class)->trans('Bürger', [], 'admin', $lang),
            User::class => $event->subject->getName(),
            default => ''
        };
    }

    private function getReportedContent( string $class, ContentReportEvent $event, ?string $lang = null ): string {
        $html = $this->getService(HTMLService::class);
        return match ( $class ) {
            Post::class, PrivateMessage::class, GlobalPrivateMessage::class =>
            strip_tags(
                preg_replace(
                    ['/(?:<br ?\/?>)+/', '/<span class="quoteauthor">([\w\d ._-]+)<\/span>/',  '/<blockquote>/', '/<\/blockquote>/', '/<a href="(.*?)">(.*?)<\/a>/'],
                    ["\n", '${1}:', '[**', '**]', '[${2}](${1})'],
                    $html->prepareEmotes( $event->subject->getText())
                )
            ),
            BlackboardEdit::class => $event->subject->getText(),
            CitizenRankingProxy::class => match ($event->report->getSpecification()) {
                AdminReportSpecification::None => 'no content',
                AdminReportSpecification::CitizenAnnouncement => $event->subject->getCitizen()?->getHome()->getDescription() ?? 'deleted',
                AdminReportSpecification::CitizenLastWords => $event->subject->getLastWords(),
                AdminReportSpecification::CitizenTownComment => $event->subject->getComment(),
            },
            User::class => strip_tags( preg_replace('/<br ?\/?>/', "\n", $html->prepareEmotes( $this->getService(EntityManagerInterface::class)->getRepository(UserDescription::class)->findOneBy(['user' => $event->subject])?->getText() ?? '' ) ) ),
            default => ''
        };
    }

    private function getReportedContentURL( string $class, ContentReportEvent $event, ?string $lang = null ): string {
        $url = $this->getService(UrlGeneratorInterface::class);
        return match ( $class ) {
            Post::class => $url->generate( 'forum_jump_view', [ 'pid' => $event->subject->getId() ], UrlGeneratorInterface::ABSOLUTE_URL ),
            PrivateMessage::class, GlobalPrivateMessage::class => $url->generate('admin_reports', [ 'tab' => 'reports' ], UrlGeneratorInterface::ABSOLUTE_URL ),
            BlackboardEdit::class => $url->generate( 'admin_town_dashboard', ['id' => $event->subject->getTown()->getId(), 'tab' => 'blackboard'], UrlGeneratorInterface::ABSOLUTE_URL ),
            CitizenRankingProxy::class => match ($event->report->getSpecification()) {
                AdminReportSpecification::None => 'no content',
                AdminReportSpecification::CitizenAnnouncement => $event->subject->getCitizen() ? $url->generate( 'admin_town_dashboard', ['id' => $event->subject->getCitizen()->getTown()->getId(), 'tab' => 'citizens'], UrlGeneratorInterface::ABSOLUTE_URL ) : 'deleted',
                AdminReportSpecification::CitizenLastWords, AdminReportSpecification::CitizenTownComment => $url->generate( 'soul_view_town', ['sid' => $event->subject->getUser()->getId(), 'idtown' => $event->subject->getTown()->getId()], UrlGeneratorInterface::ABSOLUTE_URL )
            },
            User::class => $url->generate( 'soul_visit', ['id' => $event->subject->getId()], UrlGeneratorInterface::ABSOLUTE_URL  ),
            default => ''
        };
    }

    private function getReportedContentUser( string $class, ContentReportEvent $event ): ?User {
        return match ( $class ) {
            Post::class => $event->subject->getOwner(),
            PrivateMessage::class => $event->subject->getOwner()?->getUser(),
            GlobalPrivateMessage::class => $event->subject->getSender(),
            BlackboardEdit::class, CitizenRankingProxy::class => $event->subject->getUser(),
            User::class => $event->subject,
            default => null
        };
    }

    public function handleReportDiscordNotification(ContentReportEvent $event): void {
        $endpoint = $this->getService(ConfMaster::class)->getGlobalConf()->get( MyHordesConf::CONF_MOD_MAIL_DCHOOK );
        $class = ClassUtils::getRealClass(get_class($event->subject));

        if ($endpoint) {
            $user = $this->getReportedContentUser( $class, $event );

            $discord = new Client($endpoint);
            $message_embed = (new Embed())
                ->color('FF5500')
                ->title( $this->getReportedTitle( $class, $event, 'en' ) )
                ->description( $this->getReportedContent( $class, $event, 'en' ) )
                ->url( $this->getReportedContentURL( $class, $event, 'en' ) );


            if ($user) $message_embed->author(
                $user->getName(),
                $this->getService(UrlGeneratorInterface::class)->generate( 'admin_users_account_view', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                $user->getAvatar() ? $this->getService(UrlGeneratorInterface::class)->generate( 'app_web_avatar', ['uid' => $user->getId(), 'name' => $user->getAvatar()->getFilename(), 'ext' => $user->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
            );

            $report_embed = (new Embed())
                ->color('	6A00FF')
                ->title($this->getComplaintCategory( $event, 'en' ))
                ->description($event->report->getDetails() ?? '---');

            $report_embed->author(
                $event->reporter->getName(),
                $this->getService(UrlGeneratorInterface::class)->generate( 'admin_users_account_view', ['id' => $event->reporter->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                $event->reporter->getAvatar() ? $this->getService(UrlGeneratorInterface::class)->generate( 'app_web_avatar', ['uid' => $event->reporter->getId(), 'name' => $event->reporter->getAvatar()->getFilename(), 'ext' => $event->reporter->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
            );

            $discord
                ->message(":loudspeaker: **{$this->getTitle( $class, $event, 'en' )}**\n{$this->getSubtitle( $event, 'en' )}\n\n")
                ->embed( $message_embed )
                ->embed( $report_embed );

            $this->getService(MessageBusInterface::class)->dispatch( new DiscordMessage( $discord ) );

        }
    }

    public function handleReportBrowserNotification(ContentReportEvent $event): void {
        $class = ClassUtils::getRealClass(get_class($event->subject));

        $users = [];
        $user_ids = [];
        foreach ($this->getService(EntityManagerInterface::class)->getRepository(User::class)->findByLeastElevationLevel( User::USER_LEVEL_CROW ) as $crow) {
            if (!in_array( $crow->getId(), $user_ids )) {
                $users[] = $crow;
                $user_ids[] = $crow->getId();
            }

            foreach ( $this->getService(UserHandler::class)->getAllPivotUserRelationsFor( $crow, false, true ) as $pivot )
                if (!in_array( $pivot->getId(), $user_ids )) {
                    $users[] = $pivot;
                    $user_ids[] = $pivot->getId();
                }
        }

        foreach ($users as $target_user) {
            if (!$target_user->getSetting( UserSetting::PushNotifyOnModReport )) continue;

            $title = $this->getTitle( $class, $event, $target_user->getLanguage() ?? 'en' );
            $body = "<i>{$this->getComplaintCategory($event, $target_user->getLanguage() ?? 'en')}</i><br/><b>{$this->getReportedTitle( $class, $event, $target_user->getLanguage() ?? 'en' )}</b><br/>{$this->getReportedContent( $class, $event, $target_user->getLanguage() ?? 'en' )}";
            $owner = $this->getReportedContentUser( $class, $event );

            foreach ($target_user->getNotificationSubscriptionsFor(NotificationSubscriptionType::WebPush) as $subscription )
                $this->getService(MessageBusInterface::class)->dispatch(
                    new WebPushMessage($subscription,
                        title: $title,
                        body: $body,
                        avatar: $owner?->getAvatar()?->getId()
                    )
                );
        }
    }
}