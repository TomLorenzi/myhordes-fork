<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Controller\Admin\AdminActionController;
use App\Controller\CustomAbstractController;
use App\Entity\AdminAction;
use App\Entity\Award;
use App\Entity\ExternalApp;
use App\Entity\OfficialGroup;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Service\AdminHandler;
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
use Shivas\VersioningBundle\Service\VersionManagerInterface as VersionManager;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
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
    public static array $devs = [
        'Benjamin "<i>Brainbox</i>" Behrendt',
        'Ludovic "<i>Cheh\'Tan</i>" Le Brech',
        'Paul "<i>CountCount</i>" Bruhn',
        'Adrien "<i>Adri</i>" Boitelle',
        'Niklas "<i>Choreas</i>" Kosanke',
        'Christopher "<i>Vander</i>" Chalfant',
        'Connor "<i>Dylan57</i>" Ottermann',
        'Ryan "<i>Nayr</i>" Nayrovic',
    ];

    public static array $supporters = [
        'MisterD', 'Mondi', 'Schrödinger', 'Kitsune',
        'MOTZI', 'devwwm', 'tchekof', 'alonsopor', 'Termineitron',
        'Rikrdo', 'Valedres', 'Yaken', 'Finne', 'Ross',
        'Elara', 'MisterSimple', 'Eragony', 'Tristana', 'Bigonoud'
    ];

    private VersionManager $version_manager;
    private KernelInterface $kernel;

    public function __construct(VersionManager $v, KernelInterface $k, EntityManagerInterface $e, TranslatorInterface $translator, ConfMaster $conf, TimeKeeperService $tk, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $e, $tk, $ch, $ih, $translator);
        $this->version_manager = $v;
        $this->kernel = $k;
    }

    private function handleDomainRedirection(): ?Response {
        $redirect = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_DOMAIN_REDIRECTION, []);
        $request = Request::createFromGlobals();

        $current_host = "{$request->getHttpHost()}{$request->getBasePath()}";
        foreach ($redirect as $entry)
            if ($entry['principal'] === $current_host && ($url = $entry[$this->getUserLanguage()] ?? $entry['*'] ?? null))
                return new RedirectResponse( "{$url}{$request->getPathInfo()}", 308 );

        return null;
    }

    private function render_web_framework(string $ajax_landing, $allow_attract_page = false): Response {
        try {
            $version = $this->version_manager->getVersion();
            $is_debug_version =
                ($version->getMajor() < 1) ||
                ($version->getPreRelease() && !(
                    $version->getPreRelease()->toString() === 'rc' || str_starts_with($version->getPreRelease()->toString(), 'rc.')
                ));
        } catch (\Exception $e) {
            $is_debug_version = false;
            $version = null;
        }

        $devs = self::$devs;
        shuffle($devs);

        $supporters = self::$supporters;
        shuffle($supporters);

        return $this->render( ($this->getUser() || !$allow_attract_page) ? 'web/framework.html.twig' : 'web/attract.html.twig', [
            'version' => $version, 'debug' => $is_debug_version, 'env' => $this->kernel->getEnvironment(),
            'devs' => $devs,
            'supporters' => $supporters,
            'ajax_landing' => $ajax_landing,
            'langs' => $this->allLangs
        ] );
    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function framework(): Response
    {
        return $this->handleDomainRedirection() ?? $this->render_web_framework($this->generateUrl('initial_landing'), true);
    }

    /**
     * @Route("/ref/{name}")
     * @param string $name
     * @param SessionInterface $s
     * @return Response
     */
    public function refer_incoming(string $name, SessionInterface $s): Response
    {
        if ($r = $this->handleDomainRedirection()) return $r;
        $s->set('refer', $name);
        return $this->render_web_framework($this->generateUrl('public_register'));
    }

    /**
     * @Route("/pm/{com}", name="home_pm")
     * @param string|null $com
     * @return Response
     */
    public function standalone_pm(string $com = null): Response
    {
        if ($r = $this->handleDomainRedirection()) return $r;
        if (!$this->isGranted('ROLE_USER'))
            return $this->redirect($this->generateUrl('home'));

        return $this->render( 'web/pm-host.html.twig', $com ? ['command' => $com, 'langs' => $this->allLangs] : ['langs' => $this->allLangs] );
    }

    /**
     * @Route("/r/ach", name="revert_ach_language")
     * @GateKeeperProfile("skip")
     * @return Response
     */
    public function rescue_mode_lang_ach( ): Response
    {
        if (!$this->isGranted('ROLE_USER'))
            return $this->redirect($this->generateUrl('home'));

        if (!($user = $this->getUser())) return $this->redirect($this->generateUrl('home'));
        
        if ($user->getLanguage() === 'ach')
            $user->setLanguage( $this->getUserLanguage( true ) );

        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @Route("gateway/eternal-twin", name="gateway-etwin")
     * @param EternalTwinHandler $etwin
     * @param SessionInterface $session
     * @return Response
     */
    public function gateway_etwin(EternalTwinHandler $etwin, SessionInterface $session): Response {
        if (!$etwin->isReady()) return new Response('Error: No gateway to EternalTwin is configured.');
        $session->set('_etwin_rm', false);
        $request = Request::createFromGlobals();
        return new RedirectResponse($etwin->createAuthorizationRequest('etwin-login#' . $request->getHost() . $request->getBaseUrl()));
    }

    /**
     * @Route("gateway/rm/eternal-twin", name="gateway-remember-etwin")
     * @param EternalTwinHandler $etwin
     * @param SessionInterface $session
     * @return Response
     */
    public function gateway_rm_etwin(EternalTwinHandler $etwin, SessionInterface $session): Response {
        if (!$etwin->isReady()) return new Response('Error: No gateway to EternalTwin is configured.');
        $session->set('_etwin_rm', true);
        $request = Request::createFromGlobals();
        return new RedirectResponse($etwin->createAuthorizationRequest('etwin-login#' . $request->getHost() . $request->getBaseUrl()));
    }

    /**
     * @Route("gateway/eternal-twin-registration", name="gateway-etwin-reg")
     * @param EternalTwinHandler $etwin
     * @param ConfMaster $conf
     * @return Response
     */
    public function gateway_etwin_reg(EternalTwinHandler $etwin, ConfMaster $conf): Response {
        if (!$etwin->isReady()) return new Response('Error: No gateway to EternalTwin is configured.');
        return new RedirectResponse( $conf->getGlobalConf()->get( MyHordesConf::CONF_ETWIN_REG ) );
    }

    /**
     * @Route("/twinoid", name="twinoid_auth_endpoint")
     * @param ConfMaster $conf
     * @return Response
     */
    public function framework_import(ConfMaster $conf): Response
    {
        $request = Request::createFromGlobals();
        $state = explode('#',$request->query->get('state'));
        $target_domain = count($state) === 1 ? null : $state[1];
        $state = $state[0];
        $code  = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) return new Response('Error: No code obtained! Reported error is "' . htmlentities($error) . '".');
        if (empty( $code )) return new Response('Error: No code obtained!');

        if ($target_domain !== null && Request::createFromGlobals()->getHost() !== null && !str_starts_with($target_domain, $request->getHost())) {
            foreach ($conf->getGlobalConf()->get(MyHordesConf::CONF_DOMAINS) as $domain)
                if (str_starts_with( $target_domain, $domain )) return new RedirectResponse( "https://{$target_domain}/twinoid?code={$code}&state={$state}" );
            return new Response('Error: Untrusted domain!');
        }

        switch ($state) {
            case 'import': return $this->render_web_framework($this->generateUrl('soul_import', ['code' => $code]));
            case 'etwin-login': return $this->render_web_framework($this->generateUrl('etwin_login', ['code' => $code]));
            default: return new Response('Error: Invalid state, can\'t redirect!');
        }


    }

    /**
     * @Route("/jx/{ajax}",requirements={"ajax"=".+"},condition="!request.isXmlHttpRequest()")
     * @param string $ajax
     * @param Request $q
     * @return Response
     */
    public function loader(string $ajax, Request $q): Response
    {
        if ($q->query->count() > 0) {
            $bag = [];
            foreach ($q->query as $p => $v)
                $bag[] = urlencode($p) . '=' . urlencode($v);
            $bag = '?' . implode('&',$bag);
        } else $bag = '';

        $whitelisted = function($s): bool {
            if (in_array( $s, [
                'public/welcome',
                'public/about',
                'public/news'
            ] )) return true;

            foreach ([
                'help',
                'public/changelog'
            ] as $item)
                if ($s === $item || str_starts_with( $s, "$item/" )) return true;

            return false;
        };

        return $this->handleDomainRedirection() ?? $this->render_web_framework(Request::createFromGlobals()->getBasePath() . "/jx/{$ajax}{$bag}", $whitelisted( $ajax ));
    }

    private function check_cache(string $name): ?Response {
        $request = Request::createFromGlobals();
        if (!$request->headers->has('If-None-Match')) return null;

        return $request->headers->get('If-None-Match') === $name ? new Response("",304) : null;
    }

    private function image_output($data, string $name, string $ext): Response {
        // HEIC images should be referred to as AVIF towards the browser
        if ($ext === 'heic') $ext = 'avif';

        $response = new Response(stream_get_contents( $data ));
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            "{$name}.{$ext}"
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', "image/{$ext}");
        $response
            ->setPublic()->setMaxAge(157680000)->setImmutable()
            ->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
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
        if (!$user || !$user->getAvatar()) return $this->cdn_fallback( "avatar/{$uid}/{$name}.{$ext}" );
        if (($user->getAvatar()->getFilename() !== $name && $user->getAvatar()->getSmallName() !== $name) || $user->getAvatar()->getFormat() !== $ext)
            return $this->cdn_fallback( "avatar/{$uid}/{$name}.{$ext}" );

        $target = ($user->getAvatar()->getFilename() === $name || !$user->getAvatar()->getSmallImage()) ? $user->getAvatar()->getImage() : $user->getAvatar()->getSmallImage();
        return $this->image_output($target, $name, $ext);
    }

    /**
     * @Route("/cdn/icon/{uid<\d+>}/{aid<\d+>}/{name}.{ext<[\w\d]+>}",requirements={"name"="[0123456789abcdef]{32}"},condition="!request.isXmlHttpRequest()")
     * @param int $uid
     * @param int $aid
     * @param string $name
     * @param string $ext
     * @return Response
     */
    public function customIcon(int $uid, int $aid, string $name, string $ext): Response
    {
        if ($r = $this->check_cache($name)) return $r;

        /** @var Award $award */
        $user  = $this->entity_manager->getRepository(User::class)->find( $uid );
        $award = $this->entity_manager->getRepository(Award::class)->find( $aid );
        if (!$user || !$award || $award->getUser() !== $user || !$award->getCustomIcon())
            return $this->cdn_fallback( "icon/{$uid}/{$aid}/{$name}.{$ext}" );
        if ($award->getCustomIconName() !== $name || $award->getCustomIconFormat() !== $ext)
            return $this->cdn_fallback( "icon/{$uid}/{$aid}/{$name}.{$ext}" );

        return $this->image_output($award->getCustomIcon(), $name, $ext);
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
        if (!$app || !$app->getImage()) return $this->cdn_fallback( "app/{$aid}/{$name}.{$ext}" );
        if ($app->getImageName() !== $name || $app->getImageFormat() !== $ext)
            return $this->cdn_fallback( "app/{$aid}/{$name}.{$ext}" );

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
        if (!$group) return $this->cdn_fallback( "group/{$gid}/{$name}.{$ext}" );

        $meta = $this->entity_manager->getRepository(OfficialGroup::class)->findOneBy(['usergroup' => $group]);
        if (!$meta) return $this->cdn_fallback( "group/{$gid}/{$name}.{$ext}" );

        if ($meta->getAvatarName() !== $name || $meta->getAvatarExt() !== $ext)
            return $this->cdn_fallback( "group/{$gid}/{$name}.{$ext}" );

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
