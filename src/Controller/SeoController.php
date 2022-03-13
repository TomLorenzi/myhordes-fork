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
 * Class SeoController
 * @package App\Controller
 * @GateKeeperProfile(allow_during_attack=true)
 */
class SeoController extends CustomAbstractController
{


    /**
     * @Route("/robots.txt", name="robots.txt")
     * @return Response
     */
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
     * @Route(
     *     "/", priority=100, name="seo_welcome",
     *     condition="request.headers.get('User-Agent') matches '%seobots%'"
     * )
     * @return Response
     */
    public function seo_welcome(): Response
    {
        return $this->render( 'seo/welcome.html.twig', [
            'devs' => WebController::$devs,
            'supporters' => WebController::$supporters
        ] );
    }

    /**
     * @Route(
     *     "jx/help/{name}", priority=100, name="seo_help",
     *     condition="request.headers.get('User-Agent') matches '%seobots%'"
     * )
     * @param string $name
     * @return Response
     */
    public function seo_help(string $name = 'welcome'): Response
    {
        if ($name === 'shell') return $this->redirect($this->generateUrl('help'));

        try {
            $twig = $this->container->get('twig');
            $template = $twig->load( "ajax/help/$name.html.twig" );
            $content = $template->renderBlock( 'helpContent', []);
        } catch (\Throwable $e){
            return $this->redirect($this->generateUrl('help'));
        }

        return $this->render( 'seo/help.html.twig', [
            'devs' => WebController::$devs,
            'supporters' => WebController::$supporters,
            'section' => $name,
            'content' => str_replace('x-ajax-href', 'href', $content)
        ] );
    }
}
