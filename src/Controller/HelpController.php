<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\OfficialGroup;
use App\Response\AjaxResponse;
use App\Service\JSONRequestParser;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
 */
class HelpController extends CustomAbstractController
{
    /**
     * @Route("jx/help/{name}", name="help")
     * @param string $name
     * @return Response
     */
    public function help(string $name = 'welcome'): Response
    {
        if ($name === 'shell') return $this->redirect($this->generateUrl('help'));
        try {
            $support_groups = $this->entity_manager->getRepository(OfficialGroup::class)->findBy(['lang' => $this->getUserLanguage(), 'semantic' => OfficialGroup::SEMANTIC_SUPPORT]);
            return $this->render( "ajax/help/$name.html.twig", $this->addDefaultTwigArgs(null, [
                'section' => $name,
                'timezone' => date_default_timezone_get(),
                'support' => count($support_groups) === 1 ? $support_groups[0] : null
            ]));
        } catch (Exception $e){
            return $this->redirect($this->generateUrl('help'));
        }
    }

    /**
     * @Route("jx/help/partial/{name}", name="help_partial", priority=1)
     * @param string $name
     * @return Response
     */
    public function help_partial(string $name = 'welcome'): Response
    {
        if ($name === 'shell') return $this->redirect($this->generateUrl('help'));
        try {
            return $this->renderBlocks( "ajax/help/$name.html.twig", ["helpContent"], [], [], false, null, true);
        } catch (Exception $e){
            return new Response('');
        }
    }

    /**
     * @Route("api/help/search", name="help_search")
     * @param JSONRequestParser $parser
     * @param KernelInterface $kernel
     * @return Response
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function help_search(JSONRequestParser $parser, KernelInterface $kernel): Response
    {
        $sections = [];
        foreach (scandir("{$kernel->getProjectDir()}/templates/ajax/help") as $f)
            if ($f !== '.' && $f !== '..' && $f !== 'shell.html.twig' && str_ends_with($f, '.html.twig')) $sections[] = substr($f, 0, -10);

        $twig = $this->container->get('twig');
        $query = $parser->trimmed('query', '');

        if (mb_strlen($query) > 3)
            $sections_filtered = array_filter( $sections, fn(string $section) => mb_strpos( strtolower(strip_tags( $twig->load( "ajax/help/$section.html.twig" )->renderBlock( 'helpContent', []) )), strtolower($query) ) !== false );
        else $sections_filtered = $sections;

        return AjaxResponse::success( true,  ['result' => array_values($sections_filtered), 'filtered' => count($sections_filtered) !== count($sections)] );
    }
}