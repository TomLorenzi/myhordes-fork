<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Controller\Admin\AdminActionController;
use App\Controller\CustomAbstractController;
use App\Entity\AdminAction;
use App\Entity\ExternalApp;
use App\Entity\OfficialGroup;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Service\AdminActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\EternalTwinHandler;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\TimeKeeperService;
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class WebController
 * @package App\Controller
 * @GateKeeperProfile(allow_during_attack=true)
 */
class WebController extends CustomAbstractController
{
    private $version_manager;
    private $kernel;

    public function __construct(VersionManager $v, KernelInterface $k, EntityManagerInterface $e, TranslatorInterface $translator, ConfMaster $conf, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $e, $tk, $ch, $ih, $translator);
        $this->version_manager = $v;
        $this->kernel = $k;
    }

    private function render_web_framework(string $ajax_landing): Response {
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
            'Connor "<i>Dylan57</i>" Ottermann',
        ];
        shuffle($devs);

        $supporters = [
            'MisterD', 'Mondi', 'Schrödinger', 'Kitsune',
            'MOTZI', 'devwwm', 'tchekof', 'alonsopor', 'Termineitron',
            'Nayr', 'Rikrdo', 'Valedres', 'Yaken'
        ];
        shuffle($supporters);

        return $this->render( 'web/framework.html.twig', [
            'version' => $version, 'debug' => $is_debug_version, 'env' => $this->kernel->getEnvironment(),
            'devs' => $devs,
            'supporters' => $supporters,
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
     * @Route("/ref/{name}")
     * @return Response
     */
    public function refer_incoming(string $name, SessionInterface $s): Response
    {
        $s->set('refer', $name);
        return $this->render_web_framework($this->generateUrl('public_register'));
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

    private function check_cache(string $name): ?Response {
        $request = Request::createFromGlobals();
        if (!$request->headers->has('If-None-Match')) return null;

        return $request->headers->get('If-None-Match') === $name ? new Response("",304) : null;
    }

    private function image_output($data, string $name, string $ext): Response {
        $response = new Response(stream_get_contents( $data ));
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            "{$name}.{$ext}"
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', "image/{$ext}");
        $response->headers->set('Cache-Control', ['public','max-age=157680000','immutable']);
        $response->headers->set('ETag', $name);
        return $response;
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
        if ($r = $this->check_cache($name)) return $r;

        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find( $uid );
        if (!$user || !$user->getAvatar()) return $this->cdn_fallback( "avatar/{$uid}/{$name}/{$ext}" );
        if (($user->getAvatar()->getFilename() !== $name && $user->getAvatar()->getSmallName() !== $name) || $user->getAvatar()->getFormat() !== $ext)
            return $this->cdn_fallback( "avatar/{$uid}/{$name}/{$ext}" );

        $target = ($user->getAvatar()->getFilename() === $name || !$user->getAvatar()->getSmallImage()) ? $user->getAvatar()->getImage() : $user->getAvatar()->getSmallImage();
        return $this->image_output($target, $name, $ext);
    }

    /**
     * @Route("/cdn/app/{aid<\d+>}/{name}.{ext<[\w\d]+>}",requirements={"name"="[0123456789abcdef]{32}"},condition="!request.isXmlHttpRequest()")
     * @param int $aid
     * @param string $name
     * @param string $ext
     * @return Response
     */
    public function app_icon(int $aid, string $name, string $ext): Response
    {
        if ($r = $this->check_cache($name)) return $r;

        /** @var ExternalApp $app */
        $app = $this->entity_manager->getRepository(ExternalApp::class)->find( $aid );
        if (!$app || !$app->getImage()) return $this->cdn_fallback( "app/{$aid}/{$name}/{$ext}" );
        if ($app->getImageName() !== $name || $app->getImageFormat() !== $ext)
            return $this->cdn_fallback( "app/{$aid}/{$name}/{$ext}" );

        return $this->image_output($app->getImage(), $name, $ext);
    }

    /**
     * @Route("/cdn/group/{gid<\d+>}/{name}.{ext<[\w\d]+>}",requirements={"name"="[0123456789abcdef]{32}"},condition="!request.isXmlHttpRequest()")
     * @param int $gid
     * @param string $name
     * @param string $ext
     * @return Response
     */
    public function group_icon(int $gid, string $name, string $ext): Response
    {
        if ($r = $this->check_cache($name)) return $r;

        /** @var UserGroup $group */
        $group = $this->entity_manager->getRepository(UserGroup::class)->find( $gid );
        if (!$group) return $this->cdn_fallback( "group/{$gid}/{$name}/{$ext}" );

        $meta = $this->entity_manager->getRepository(OfficialGroup::class)->findOneBy(['usergroup' => $group]);
        if (!$meta) return $this->cdn_fallback( "group/{$gid}/{$name}/{$ext}" );

        if ($meta->getAvatarName() !== $name || $meta->getAvatarExt() !== $ext)
            return $this->cdn_fallback( "group/{$gid}/{$name}/{$ext}" );

        return $this->image_output($meta->getIcon(), $name, $ext);
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