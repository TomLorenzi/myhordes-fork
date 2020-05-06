<?php

namespace App\Controller;

use App\Entity\AdminAction;
use App\Entity\ExternalApp;
use App\Entity\User;
use App\Service\AdminActionHandler;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use App\Translation\T;
use Psr\Cache\InvalidArgumentException;
use Shivas\VersioningBundle\Service\VersionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebController extends AbstractController
{
    private $version_manager;
    private $kernel;
    private $entityManager;
    private $adminAction_handler;
    protected $translator;

    public function __construct(VersionManager $v, KernelInterface $k, EntityManagerInterface $e, AdminActionHandler $admh, TranslatorInterface $translator)
    {
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
        ];
        shuffle($devs);

        $apps = $this->entityManager->getRepository(ExternalApp::class)->findAll();
        $adminActions = [['name' => $this->translator->trans('Users' ,[],'global'), 'id' => 1], ['name' => $this->translator->trans('Meldungen' ,[],'global'), 'id' => 2]];

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