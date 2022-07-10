<?php


namespace App\EventSubscriber;

use App\Service\ConfMaster;
use App\Structures\MyHordesConf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private string $report_path;
    private string $version;

    private ?string $discordEndpoint;
    private ?array $gitlabIssueMail;

    private MailerInterface $mail;

    public function __construct( ConfMaster $conf, ParameterBagInterface $params, MailerInterface $mailer ) {
        $this->mail = $mailer;

        $this->report_path = "{$params->get('kernel.project_dir')}/var/reports";

        $version_file = "{$params->get('kernel.project_dir')}/VERSION";
        $this->version = file_exists( $version_file ) ? file_get_contents( $version_file ) : 'NOVER';

        $this->gitlabIssueMail['to']   = $conf->getGlobalConf()->get( MyHordesConf::CONF_FATAL_MAIL_TARGET, null );
        $this->gitlabIssueMail['from'] = $conf->getGlobalConf()->get( MyHordesConf::CONF_FATAL_MAIL_SOURCE, null );
        $this->discordEndpoint = $conf->getGlobalConf()->get( MyHordesConf::CONF_FATAL_MAIL_DCHOOK, null );
    }

    public function onKernelException(ExceptionEvent $event) {

        if (is_a( $event->getThrowable(), HttpException::class )) return;

        $error_id = md5( $event->getThrowable()->getFile() . "@" . $event->getThrowable()->getLine() . '@' . $this->version );
        $report_path = "{$this->report_path}/{$error_id}/";

        $discord_file = "{$report_path}/discord";
        $mail_file = "{$report_path}/mail";

        if (!file_exists($report_path)) mkdir( $report_path, 0777, true );

        if ($this->discordEndpoint && !file_exists($discord_file)) {
            $payload = [
                'content' =>
                    ":sos: **Reporting an exception in MyHordes**\n" .
                    "```fix\n[{$event->getThrowable()->getMessage()}]\n```\n" .
                    "*{$event->getThrowable()->getFile()}*\nLine *{$event->getThrowable()->getLine()}*\n\n" .
                    "See attached stack trace for more information."
            ];

            try {
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_URL => $this->discordEndpoint,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_POSTFIELDS => [
                        'payload_json' => new \CURLStringFile( json_encode( $payload, JSON_FORCE_OBJECT ), '', 'application/json' ),
                        'files[0]'  => new \CURLStringFile( $event->getThrowable()->getTraceAsString(), 'stack.txt', 'text/plain' ),
                    ],
                ]);

                $response = curl_exec($curl);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if (!($response === false || $status < 200 || $status > 299))
                    file_put_contents( $discord_file, "".time() );
            } catch (Throwable $e) {}

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