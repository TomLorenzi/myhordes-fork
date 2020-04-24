<?php

namespace App\Controller;

use App\Entity\AdminAction;
use App\Entity\ExternalApp;
use App\Service\AdminActionHandler;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Shivas\VersioningBundle\Service\VersionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class WebController extends AbstractController
{
    private $version_manager;
    private $kernel;
    private $entityManager;
    private $adminAction_handler;

    public function __construct(VersionManager $v, KernelInterface $k, EntityManagerInterface $e, AdminActionHandler $admh)
    {
        $this->version_manager = $v;
        $this->kernel = $k;
        $this->entityManager = $e;
        $this->adminAction_handler = $admh;
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
        ];
        shuffle($devs);

        $apps = $this->entityManager->getRepository(ExternalApp::class)->findAll();
        $adminActions = [['name' => 'Users', 'id' => 1]];

        return $this->render( 'web/framework.html.twig', [
            'version' => $version, 'debug' => $is_debug_version, 'env' => $this->kernel->getEnvironment(),
            'devs' => $devs,
            'apps' => $apps,
            'adminActions' => $adminActions,
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
     * @Route("/jx/{ajax}",requirements={"ajax"=".+"},condition="!request.isXmlHttpRequest()")
     * @param string $ajax
     * @return Response
     */
    public function loader(string $ajax): Response
    {
        return $this->render_web_framework(Request::createFromGlobals()->getBasePath() . '/jx/' . $ajax);
    }

}