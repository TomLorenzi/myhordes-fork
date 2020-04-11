<?php

namespace App\Controller;

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

    public function __construct(VersionManager $v, KernelInterface $k)
    {
        $this->version_manager = $v;
        $this->kernel = $k;
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
        ];
        shuffle($devs);

        return $this->render( 'web/framework.html.twig', [
            'version' => $version, 'debug' => $is_debug_version, 'env' => $this->kernel->getEnvironment(),
            'devs' => $devs,
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