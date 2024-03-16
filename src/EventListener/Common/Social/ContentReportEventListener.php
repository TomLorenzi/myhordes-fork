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
use App\Event\Common\Social\ContentReportEvents\BlackboardEditContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\CitizenRankingProxyContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\ContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\GlobalPrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PostContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\PrivateMessageContentReportEvent;
use App\Event\Common\Social\ContentReportEvents\UserContentReportEvent;
use App\EventListener\ContainerTypeTrait;
use App\Messages\Discord\DiscordMessage;
use App\Service\ConfMaster;
use App\Service\HTMLService;
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
            HTMLService::class
        ];
    }

    public function handleReportDiscordNotification(ContentReportEvent $event): void {
        $endpoint = $this->getService(ConfMaster::class)->getGlobalConf()->get( MyHordesConf::CONF_MOD_MAIL_DCHOOK );
        $class = ClassUtils::getRealClass(get_class($event->subject));

        if ($endpoint) {
            $user = match ( $class ) {
                Post::class => $event->subject->getOwner(),
                PrivateMessage::class => $event->subject->getOwner()?->getUser(),
                GlobalPrivateMessage::class => $event->subject->getSender(),
                BlackboardEdit::class, CitizenRankingProxy::class => $event->subject->getUser(),
                User::class => $event->subject,
                default => null
            };

            $complaint_list = [
                'Keinen Grund angeben','Cheating','Flooding oder Spam','Verwendung einer anderen als der Stadtsprache',
                'Beleidigungen / Unangemessener Ausdruck','Pornographie','Hassrede','Verbreitung persönlicher Informationen',
                'Verletzung von Copyright','Aufruf zu Gesetzesverstößen','Ermutigung von Selbstmord oder Selbstverletzung',
                'Unangemessene Profilbeschreibung', 'Unangemessener Avatar', 'Unangemessener Name'
            ];
            
            $html = $this->getService(HTMLService::class);

            $discord = new Client($endpoint);

            $message_embed = (new Embed())
                ->color('FF5500')
                ->title( match ( $class ) {
                    Post::class => $event->subject->getThread()->getTitle(),
                    PrivateMessage::class => $event->subject->getPrivateMessageThread()->getTitle(),
                    GlobalPrivateMessage::class => $event->subject->getReceiverGroup()->getName(),
                    BlackboardEdit::class => 'The words of Heroes',
                    CitizenRankingProxy::class => 'Citizens',
                    User::class => $event->subject->getName(),
                    default => 'untitled'
                } )
                ->description(match ( $class ) {
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
                    User::class => strip_tags( preg_replace('/<br ?\/?>/', "\n", $html->prepareEmotes( $this->getService(EntityManagerInterface::class)->getRepository(UserDescription::class)->findOneBy(['user' => $user])?->getText() ?? '[no description]' ) ) ),
                    default => 'no content'
                })
                ->url(match ( $class ) {
                    Post::class => $this->getService(UrlGeneratorInterface::class)->generate( 'forum_jump_view', [ 'pid' => $event->subject->getId() ], UrlGeneratorInterface::ABSOLUTE_URL ),
                    PrivateMessage::class, GlobalPrivateMessage::class => $this->getService(UrlGeneratorInterface::class)->generate('admin_reports', [ 'tab' => 'reports' ], UrlGeneratorInterface::ABSOLUTE_URL ),
                    BlackboardEdit::class => $this->getService(UrlGeneratorInterface::class)->generate( 'admin_town_dashboard', ['id' => $event->subject->getTown()->getId(), 'tab' => 'blackboard'], UrlGeneratorInterface::ABSOLUTE_URL ),
                    CitizenRankingProxy::class => match ($event->report->getSpecification()) {
                        AdminReportSpecification::None => 'no content',
                        AdminReportSpecification::CitizenAnnouncement => $event->subject->getCitizen() ? $this->getService(UrlGeneratorInterface::class)->generate( 'admin_town_dashboard', ['id' => $event->subject->getCitizen()->getTown()->getId(), 'tab' => 'citizens'], UrlGeneratorInterface::ABSOLUTE_URL ) : 'deleted',
                        AdminReportSpecification::CitizenLastWords, AdminReportSpecification::CitizenTownComment => $this->getService(UrlGeneratorInterface::class)->generate( 'soul_view_town', ['sid' => $event->subject->getUser()->getId(), 'idtown' => $event->subject->getTown()->getId()], UrlGeneratorInterface::ABSOLUTE_URL )
                    },
                    User::class => $this->getService(UrlGeneratorInterface::class)->generate( 'soul_visit', ['id' => $event->subject->getId()], UrlGeneratorInterface::ABSOLUTE_URL  ),
                    default => 'no content'
                });


            if ($user) $message_embed->author(
                $user->getName(),
                $this->getService(UrlGeneratorInterface::class)->generate( 'admin_users_account_view', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                $user->getAvatar() ? $this->getService(UrlGeneratorInterface::class)->generate( 'app_web_avatar', ['uid' => $user->getId(), 'name' => $user->getAvatar()->getFilename(), 'ext' => $user->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
            );

            if ($event->report->getReason() >= 0 && $event->report->getReason() < count($complaint_list))
                $reason = $this->getService(TranslatorInterface::class)->trans( $complaint_list[$event->report->getReason()], [], 'global', 'en' );
            else $reason = $this->getService(TranslatorInterface::class)->trans( 'Keinen Grund angeben', [], 'global', 'en' );

            $report_embed = (new Embed())
                ->color('	6A00FF')
                ->title($reason)
                ->description($event->report->getDetails() ?? 'No description');

            $report_embed->author(
                $event->reporter->getName(),
                $this->getService(UrlGeneratorInterface::class)->generate( 'admin_users_account_view', ['id' => $event->reporter->getId()], UrlGeneratorInterface::ABSOLUTE_URL ),
                $event->reporter->getAvatar() ? $this->getService(UrlGeneratorInterface::class)->generate( 'app_web_avatar', ['uid' => $event->reporter->getId(), 'name' => $event->reporter->getAvatar()->getFilename(), 'ext' => $event->reporter->getAvatar()->getFormat()],UrlGeneratorInterface::ABSOLUTE_URL ) : ''
            );

            $text = match( $class ) {
                Post::class => $this->getService(TranslatorInterface::class)->trans( 'Ein Forenpost wurde gemeldet.', [], 'global', 'en' ),
                PrivateMessage::class, $this->getService(TranslatorInterface::class)->trans( 'Eine Stadt-PN wurde gemeldet.', [], 'global', 'en' ),
                BlackboardEdit::class => $this->getService(TranslatorInterface::class)->trans( 'Ein Eintrag auf dem Schwarzen Brett wurde gemeldet.', [], 'global', 'en' ),
                CitizenRankingProxy::class => match ($event->report->getSpecification()) {
                    AdminReportSpecification::None => '',
                    AdminReportSpecification::CitizenAnnouncement => $this->getService(TranslatorInterface::class)->trans('Die Nachricht eines Bürgers wurde gemeldet.', [], 'global', 'en'),
                    AdminReportSpecification::CitizenLastWords => $this->getService(TranslatorInterface::class)->trans('Die Letzten Worte eines Bürgers wurde gemeldet.', [], 'global', 'en'),
                    AdminReportSpecification::CitizenTownComment => $this->getService(TranslatorInterface::class)->trans('Der Stadtkommentar eines Bürgers wurde gemeldet.', [], 'global', 'en'),
                },
                User::class => $this->getService(TranslatorInterface::class)->trans('Ein Spieleraccount wurde gemeldet.', [], 'global', 'en'),
                default => ''
            };

            $note = $this->getService(TranslatorInterface::class)->trans('Dies ist Meldung #{num}.', ['num' => $event->count], 'global', 'en');

            $discord
                ->message(":loudspeaker: **{$text}**\n{$note}\n\n")
                ->embed( $message_embed )
                ->embed( $report_embed );

            $this->getService(MessageBusInterface::class)->dispatch( new DiscordMessage( $discord ) );

        }
    }
}