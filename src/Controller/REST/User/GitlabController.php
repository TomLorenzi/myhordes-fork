<?php

namespace App\Controller\REST\User;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ForumUsagePermissions;
use App\Entity\GlobalPrivateMessage;
use App\Entity\Post;
use App\Entity\PrivateMessage;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Enum\AdminReportSpecification;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Service\RateLimitingFactoryProvider;
use App\Structures\MyHordesConf;
use ArrayHelpers\Arr;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Gitlab\Client;
use Shivas\VersioningBundle\Service\VersionManager;
use Shivas\VersioningBundle\ShivasVersioningBundle;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\UuidV4;
use Throwable;

#[Route(path: '/rest/v1/user/issues', name: 'rest_user_issues_', condition: "request.headers.get('Accept') === 'application/json'")]
#[GateKeeperProfile('skip')]
class GitlabController extends CustomAbstractCoreController
{
    private function validateConfig(ConfMaster $confMaster): bool {
        $data = $confMaster->getGlobalConf()->get( MyHordesConf::CONF_ISSUE_REPORTING_GITLAB ) ?? [];
        return Arr::get( $data, 'token', null ) && Arr::get( $data, 'project-id', null );
    }
    private function collectProxyInformation(Request $request, ?VersionManager $version = null): array {
        $user_profile = $this->generateUrl( 'soul_visit', ['id' => $this->getUser()->getId()], UrlGeneratorInterface::ABSOLUTE_URL );

        return array_filter( [
            'Active User' => "[{$this->getUser()->getName()} #{$this->getUser()->getId()}]({$user_profile})",
            'Page URL' => $request->headers->get('referer'),
            'User Agent' => $request->headers->get('User-Agent'),
            'Version' => $version?->getVersion()?->toString()
        ] );
    }

    private function makeTable(array $data): string {
        return "| Key | Value |\n|---|---|\n" .
            implode("\n", array_map( fn(string $key, string $value) => '| ' . str_replace( '|', ' ', $key ) . ' | ' . str_replace( '|', ' ', $value ) . ' |', array_keys( $data ), array_values( $data ) )) .
            "\n";
    }


    /**
     * @param ConfMaster $confMaster
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    public function index(ConfMaster $confMaster): JsonResponse {
        return new JsonResponse([
            'strings' => [
                'redirect' => $this->validateConfig( $confMaster ) ? null : $confMaster->getGlobalConf()->get( MyHordesConf::CONF_ISSUE_REPORTING_FALLBACK, '' ),

                'common' => [
                    'prompt' => $this->translator->trans('Über dieses Formular kannst du uns einen technischen Fehler melden. Bitte verwende diese Funktion nicht, um uns unangemessene Inhalte oder inhaltliche Vorschläge für zukünftige Updates zu senden.', [], 'global'),
                    'warn' => $this->translator->trans('Der Missbrauch dieses Formulars führt zu Sanktionen für deinen Account.', [], 'global'),

                    'ok' => $this->translator->trans('Absenden', [], 'global'),
                    'cancel' => $this->translator->trans('Abbrechen', [], 'global'),
                ],

                'fields' => [
                    'title' => [
                        'title' => $this->translator->trans('Kurzzusammenfassung des Fehlers', [], 'global'),
                        'hint' => $this->translator->trans('Beschreibe das Problem in einem Stichpunkt.', [], 'global'),
                        'example' => $this->translator->trans('Beispiel: Kommunikationsfehler beim Benutzen des Katapults', [], 'global'),
                    ],
                    'desc' => [
                        'title' => $this->translator->trans('Beschreibung des Fehlers', [], 'global'),
                        'hint' => $this->translator->trans('Bitte beschreibe detailliert, wie es zu diesem Fehler gekommen ist. Wenn du eine Fehlermeldung erhalten hast, gib bitte wenn möglich deren Wortlaut mit an.', [], 'global'),
                    ],
                ]
            ]
        ]);
    }


    /**
     * @param ParameterBagInterface $params
     * @param JSONRequestParser $parser
     * @param ConfMaster $confMaster
     * @param VersionManager $version
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '', name: 'create_issue', methods: ['PUT'])]
    public function create_issue(ParameterBagInterface $params, JSONRequestParser $parser, ConfMaster $confMaster, VersionManager $version): JsonResponse {
        $data = $confMaster->getGlobalConf()->get( MyHordesConf::CONF_ISSUE_REPORTING_GITLAB ) ?? [];
        $token = Arr::get( $data, 'token', null );
        $project = Arr::get( $data, 'project-id', null );

        if (!$token || !$project) return new JsonResponse([], Response::HTTP_PRECONDITION_FAILED);

        $title = $parser->trimmed('issue_title', null);
        $desc  = $parser->trimmed('issue_details', null);

        if (!$title || !$desc)
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $filesystem = new Filesystem();
        $tempDir = "{$params->get('kernel.project_dir')}/var/tmp/issue_attachments/" . UuidV4::v4()->toRfc4122();
        $filesystem->mkdir( $tempDir );

        $attachments = $parser->get_array( 'issue_attachments' );
        $paths = [];
        $accum = 0;
        foreach ( $attachments as $content ) {
            $filename = Arr::get( $content, 'file', null );
            $extension = Arr::get( $content, 'ext', '' );
            $content = Arr::get( $content, 'content', '' );

            if (!$filename || !$extension || !$content) continue;
            $decoded = base64_decode( $content );
            if (!$decoded || ($accum += strlen( $decoded )) > 3145728) continue;

            $storage_name = "$tempDir/" . UuidV4::v4()->toRfc4122() . $extension;
            $filesystem->dumpFile($storage_name, $decoded);
            $paths[$storage_name] = $filename;
        }

        $client = new Client();
        try {
            $client->authenticate($token, Client::AUTH_HTTP_TOKEN);

            $md = array_map( function( $file, $name ) use ($client, $project) {
                ['url' => $url] = $client->projects()->uploadFile($project, $file);
                return "![$name]($url)";
            }, array_keys( $paths ), array_values( $paths ) );

            ['iid' => $issue_id] = $client->issues()->create( $project, [
                'description' => $desc . (empty($md) ? '' : ("\n\nAttachments:\n" .  implode( "\n", $md ))),
                'issue_type' => 'issue',
                'confidential' => true,
                'title' => $title
            ] );

            $client->issues()->addNote(
                $project,
                $issue_id,
                "This issue was created from inside MyHordes.\n## Information added by proxy:\n\n" . $this->makeTable( $this->collectProxyInformation( Request::createFromGlobals(), $version ) ), [
                'internal' => true,
            ] );

        } catch (\Throwable $t) {
            $filesystem->remove( $tempDir );
            return new JsonResponse(['info' => $t->getMessage()], Response::HTTP_PROXY_AUTHENTICATION_REQUIRED);
        }

        $filesystem->remove( $tempDir );

        return new JsonResponse([
            'success' => true
        ], Response::HTTP_CREATED);
    }

}
