<?php

namespace App\Controller;

use App\Controller\Admin\AdminActionController;
use App\Controller\CustomAbstractController;
use App\Entity\AdminAction;
use App\Entity\ExternalApp;
use App\Entity\User;
use App\Service\AdminActionHandler;
use App\Service\ConfMaster;
use App\Service\EternalTwinHandler;
use App\Service\JSONRequestParser;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Translation\T;
use Psr\Cache\InvalidArgumentException;
use Shivas\VersioningBundle\Service\VersionManager;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebController extends CustomAbstractController
{
    private $version_manager;
    private $kernel;
    private $entityManager;
    private $adminAction_handler;
    protected $translator;

    public function __construct(VersionManager $v, KernelInterface $k, EntityManagerInterface $e, AdminActionHandler $admh, TranslatorInterface $translator, ConfMaster $conf)
    {
        parent::__construct($conf);
        $this->version_manager = $v;
        $this->kernel = $k;
        $this->entityManager = $e;
        $this->adminAction_handler = $admh;
        $this->translator = $translator;
    }

    private function render_web_framework(string $ajax_landing) {
        try {
            $version = $this->version_manager->getVersion();
            $is_debug_version =
                ($version->getMajor() < 1) ||
                ($version->getPreRelease() && !(
                    $version->getPreRelease() === 'rc' || substr($version->getPreRelease(), 0, 3) === 'rc.'
                ));
        } catch (InvalidArgumentException $e) {
            $is_debug_version = false;
            $version = null;
        }

        $devs = [
            'Benjamin "<i>Brainbox</i>" Behrendt',
            'Ludovic "<i>Ludofloria</i>" Le Brech',
            'Paul "<i>CountCount</i>" Bruhn',
            'Adrien "<i>Adri</i>" Boitelle',
            'Niklas "<i>Choreas</i>" Kosanke',
            'Christopher "<i>Vander</i>" Chalfant',
        ];
        shuffle($devs);

        $apps = $this->entityManager->getRepository(ExternalApp::class)->findBy(['testing' => false]);

        return $this->render( 'web/framework.html.twig', [
            'version' => $version, 'debug' => $is_debug_version, 'env' => $this->kernel->getEnvironment(),
            'devs' => $devs,
            'apps' => $apps,
            'adminActions' => AdminActionController::getAdminActions(),
            'ajax_landing' => $ajax_landing
        ] );
    }

    /**
     * @Route("/")
     * @return Response
     */
    public function framework(): Response
    {
        return $this->render_web_framework($this->generateUrl('initial_landing'));
    }

    /**
     * @Route("gateway/eternal-twin", name="gateway-etwin")
     * @param EternalTwinHandler $etwin
     * @return Response
     */
    public function gateway_etwin(EternalTwinHandler $etwin): Response {
        if (!$etwin->isReady()) return new Response('Error: No gateway to EternalTwin is configured.');
        return new RedirectResponse($etwin->createAuthorizationRequest('etwin-login'));
    }

    /**
     * @Route("/twinoid", name="twinoid_auth_endpoint")
     * @return Response
     */
    public function framework_import(): Response
    {
        $request = Request::createFromGlobals();
        $state = $request->query->get('state');
        $code  = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) return new Response('Error: No code obtained! Reported error is "' . htmlentities($error) . '".');
        if (empty( $code )) return new Response('Error: No code obtained!');

        switch ($state) {
            case 'import': return $this->render_web_framework($this->generateUrl('soul_import', ['code' => $code]));
            case 'etwin-login': return $this->render_web_framework($this->generateUrl('etwin_login', ['code' => $code]));
            default: return new Response('Error: Invalid state, can\'t redirect!');
        }


    }

    /**
     * @Route("/jx/{ajax}",requirements={"ajax"=".+"},condition="!request.isXmlHttpRequest()")
     * @param string $ajax
     * @return Response
     */
    public function loader(string $ajax): Response
    {
        return $this->render_web_framework(Request::createFromGlobals()->getBasePath() . '/jx/' . $ajax);
    }

    /**
     * @Route("/cdn/avatar/{uid<\d+>}/{name}.{ext<[\w\d]+>}",requirements={"name"="[0123456789abcdef]{32}"},condition="!request.isXmlHttpRequest()")
     * @param int $uid
     * @param string $name
     * @param string $ext
     * @return Response
     */
    public function avatar(int $uid, string $name, string $ext): Response
    {
        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find( $uid );
        if (!$user || !$user->getAvatar()) return $this->cdn_fallback( "avatar/{$uid}/{$name}/{$ext}" );
        if (($user->getAvatar()->getFilename() !== $name && $user->getAvatar()->getSmallName() !== $name) || $user->getAvatar()->getFormat() !== $ext)
            return $this->cdn_fallback( "avatar/{$uid}/{$name}/{$ext}" );

        $target = ($user->getAvatar()->getFilename() === $name || !$user->getAvatar()->getSmallImage()) ? $user->getAvatar()->getImage() : $user->getAvatar()->getSmallImage();
        $response = new Response(stream_get_contents( $target));
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            "{$name}.{$ext}"
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', "image/{$ext}");
        $response->headers->set('Cache-Control', ['public','max-age=157680000','immutable']);
        return $response;
    }

    /**
     * @Route("/cdn/{url}",requirements={"url"=".+"},condition="!request.isXmlHttpRequest()")
     * @param string $url
     * @return Response
     */
    public function cdn_fallback(string $url): Response {
        return new Response(
            "File not found: cdn/{$url}",
            Response::HTTP_NOT_FOUND,
            ['content-type' => 'text/plain']
        );
    }

}