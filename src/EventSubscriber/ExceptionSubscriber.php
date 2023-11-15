<?php


namespace App\EventSubscriber;

use App\Entity\User;
use App\Messages\Discord\DiscordMessage;
use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use DiscordWebhooks\Client;
use DiscordWebhooks\Embed;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private string $report_path;
    private string $version;
    private ?string $discordEndpoint;
    private ?array $gitlabIssueMail;

    public function __construct(
        ConfMaster                           $conf,
        ParameterBagInterface                $params,
        private readonly MailerInterface     $mail,
        private readonly MessageBusInterface $bus,
        private readonly ManagerRegistry     $mr,
		private readonly TokenStorageInterface	$ts
    ) {
        $this->report_path = "{$params->get('kernel.project_dir')}/var/reports";

        $version_file = "{$params->get('kernel.project_dir')}/VERSION";
        $this->version = file_exists( $version_file ) ? file_get_contents( $version_file ) : 'NOVER';

        $this->gitlabIssueMail['to']   = $conf->getGlobalConf()->get( MyHordesConf::CONF_FATAL_MAIL_TARGET, null );
        $this->gitlabIssueMail['from'] = $conf->getGlobalConf()->get( MyHordesConf::CONF_FATAL_MAIL_SOURCE, null );
        $this->discordEndpoint = $conf->getGlobalConf()->get(MyHordesConf::CONF_FATAL_MAIL_DCHOOK, null );
    }

    public function onKernelException(ExceptionEvent $event) {

        if (is_a( $event->getThrowable(), HttpException::class )) return;

        $error_id = md5( $event->getThrowable()->getFile() . "@" . $event->getThrowable()->getLine() . '@' . $this->version );
        $report_path = "{$this->report_path}/{$error_id}/";

        $discord_file = "{$report_path}/discord";
        $mail_file = "{$report_path}/mail";

		/** @var User $user */
		$user = $this->ts->getToken()?->getUser();

        if (!file_exists($report_path)) mkdir( $report_path, 0777, true );

        if ($this->discordEndpoint && !file_exists($discord_file)) {

            $this->mr->resetManager();
            $this->bus->dispatch( new DiscordMessage(
                (new Client( $this->discordEndpoint ))
                    ->message(":sos: **Reporting an exception in MyHordes**\n" .
                              "```fix\n[{$event->getThrowable()->getMessage()}]\n```\n" .
							  ($user !== null ? "User that thrown the exception: {$user->getUsername()}\n" : "") .
							  "URL of the error: {$event->getRequest()->getPathInfo()}\n" .
                              "*{$event->getThrowable()->getFile()}*\nLine *{$event->getThrowable()->getLine()}*\n\n"
                    )
            ) );

        }

        if ($this->gitlabIssueMail['from'] && $this->gitlabIssueMail['to'] && !file_exists($mail_file)) {
            try {
                $this->mail->send( (new Email())
                                       ->from( $this->gitlabIssueMail['from'] )
                                       ->to( $this->gitlabIssueMail['to'] )
                                       ->subject( "Automatic Error Report {$error_id}" )
                                       ->text(
                                           "**Reporting an exception in MyHordes**\n" .
                                           "```\n[{$event->getThrowable()->getMessage()}]\n```\n" .
                                           "*{$event->getThrowable()->getFile()}*\nLine *{$event->getThrowable()->getLine()}*\n\n" .
										   ($user !== null ? "User that thrown the exception: {$user->getUsername()}\n\n" : "") .
										   "URL of the error: {$event->getRequest()->getPathInfo()}\n\n" .
                                           "See attached stack trace for more information.\n" .
                                           "/confidential\n/label ~Bug ~High ~Automatic"
                                       )
                                       ->attach( $event->getThrowable()->getTraceAsString(), 'stack.txt', 'text/plain' ) );
                file_put_contents( $mail_file, "".time() );
            } catch (Throwable $e) {}
        }

    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}