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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SeoController
 * @package App\Controller
 */
#[GateKeeperProfile(allow_during_attack: true)]
class SeoController extends CustomAbstractController
{

    private function renderWithLanguageLinks( Request $request, string $view, array $data ): Response {
        $routes = [];
        if ($request->get('lang', null) && $request->get('_route', null))
            foreach ($this->generatedLangsCodes as $lang) if ($lang !== $request->get('lang'))
                $routes[$lang] = $this->generateUrl( $request->get('_route'), array_merge( $request->get('_route_params'), ['lang' => $lang] ), UrlGeneratorInterface::ABSOLUTE_URL );

        $data = array_merge( $data, ['in_other_languages' => $routes] );
        return $this->render( $view, $data );
    }

    /**
     * @return Response
     */
    #[Route(path: '/robots.txt', name: 'robots.txt')]
    public function robots_txt(): Response
    {
        $base_url = Request::createFromGlobals()->getHost();
        return new Response(
            "# robots.txt for {$base_url}\n" .
            "User-agent: *\n" .
            "Disallow: /api/\n" .
            "Disallow: /admin/\n"
            , 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * @return Response
     */
    #[Route(path: '/', priority: 100, name: 'seo_welcome', condition: "request.headers.has('User-Agent') and request.headers.get('User-Agent') matches '%seobots%'")]
    public function seo_welcome(): Response
    {
        return $this->redirectToRoute( 'seo_welcome_lang', ['lang' => 'en'] );
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/{lang}/', priority: 100, name: 'seo_welcome_lang', requirements: ['lang' => 'de|en|fr|es'], condition: "request.headers.has('User-Agent') and request.headers.get('User-Agent') matches '%seobots%'")]
    public function seo_welcome_with_lang(Request $request): Response
    {
        return $this->renderWithLanguageLinks( $request, 'seo/welcome.html.twig', [
            'devs' => array_map( fn($dev) => $dev[2], WebController::$devs),
            'supporters' => WebController::$supporters
        ] );
    }

    /**
     * @param string $name
     * @return Response
     */
    #[Route(path: 'jx/help/{name}', priority: 100, name: 'seo_help', condition: "request.headers.has('User-Agent') and request.headers.get('User-Agent') matches '%seobots%'")]
    public function seo_help(string $name = 'welcome'): Response
    {
        return $this->redirectToRoute( 'seo_help_lang', [ 'lang' => 'en', 'name' => $name ] );
    }

    /**
     * @param Request $request
     * @param string $lang
     * @param string $name
     * @return Response
     */
    #[Route(path: '{lang}/help/{name}', priority: 100, name: 'seo_help_lang', requirements: ['lang' => 'de|en|fr|es'], condition: "request.headers.has('User-Agent') and request.headers.get('User-Agent') matches '%seobots%'")]
    public function seo_help_with_lang(Request $request, string $lang, string $name = 'welcome'): Response
    {
        if ($name === 'shell') return $this->redirect($this->generateUrl('help'));

        try {
            $twig = $this->container->get('twig');
            $template = $twig->load( "ajax/help/$name.html.twig" );
            $content = $template->renderBlock( 'helpContent', []);
            try {
                $title = $template->renderBlock( 'title', []);
            } catch (\Throwable $t) { $title = null; }

        } catch (\Throwable $e){
            return $this->redirect($this->generateUrl('seo_help_lang', ['lang' => $lang]));
        }

        return $this->renderWithLanguageLinks( $request, 'seo/help.html.twig', [
            'title' => $title,
            'devs' => WebController::$devs,
            'supporters' => WebController::$supporters,
            'section' => $name,
            'content' => str_replace('x-ajax-href', 'href', $content)
        ] );
    }

    /**
     * @param Request $request
     * @param string $lang
     * @param string $any
     * @return Response
     */
    #[Route(path: '{lang}/{any}', name: 'seo_redirect', requirements: ['lang' => 'de|en|fr|es', 'any' => '.*'])]
    public function seo_redirect(Request $request, string $lang, string $any): Response
    {
        return trim($any)
            ? $this->redirect( "{$request->getScheme()}://{$request->getHost()}/jx/{$any}" )
            : $this->redirect( "{$request->getScheme()}://{$request->getHost()}/" );
    }
}
