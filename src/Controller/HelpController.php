<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\OfficialGroup;
use App\Response\AjaxResponse;
use App\Service\JSONRequestParser;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class HelpController extends CustomAbstractController
{
    private function tryToRenderHelpPage(string $base, string $page, ?string $section = null, bool $partial = false): ?Response {
        try {
            if ($partial)
                return $this->renderBlocks( $section ? "$base/$section/$page.html.twig" : "$base/$page.html.twig", ["helpContent"], [], [], false, null, true);
            else {

                $support_group = $this->getUser()
                    ? $this->entity_manager->getRepository(OfficialGroup::class)->findOneBy(['lang' => $this->getUserLanguage(), 'semantic' => OfficialGroup::SEMANTIC_SUPPORT])
                    : null;

                $oracle_group = $this->getUser()
                    ? $this->entity_manager->getRepository(OfficialGroup::class)->findOneBy(['lang' => $this->getUserLanguage(), 'semantic' => OfficialGroup::SEMANTIC_ORACLE])
                    : null;

                $animaction_group = $this->getUser()
                    ? $this->entity_manager->getRepository(OfficialGroup::class)->findOneBy(['lang' => $this->getUserLanguage(), 'semantic' => OfficialGroup::SEMANTIC_ANIMACTION])
                    : null;

                return $this->render( $section ? "$base/$section/$page.html.twig" : "$base/$page.html.twig", $this->addDefaultTwigArgs(null, [
                    'directory' => $section,
                    'section' => $page,
                    'timezone' => date_default_timezone_get(),
                    'support' => $support_group,
                    'oracle' => $oracle_group,
                    'animaction' => $animaction_group,
                ]));
            }
        } catch (LoaderError $e){
            return null;
        }
    }

    private function renderHelpPage(string $page, ?string $section = null, bool $partial = false): Response {
        if ($page === 'shell') return $this->redirectToRoute('help');
        else return
            $this->tryToRenderHelpPage('ajax/help', $page, $section, $partial) ??
            $this->redirectToRoute('help');
    }

    private function validateSection(?string $section): bool {
        return match ($section) {
            null => true,
            'crows' => $this->isGranted('ROLE_CROW'),
            'animaction' => $this->isGranted('ROLE_ANIMAC') || $this->isGranted('ROLE_ORACLE'),
            default => false
        };
    }

    /**
     * @param string|null $sect
     * @param string $name
     * @param bool $partial
     * @return Response
     */
    #[Route(path: 'jx/help/{name}', name: 'help', defaults: ['sect' => null, 'partial' => false])]
    #[Route(path: 'jx/help/{sect}/{name}', name: 'help_classified', defaults: ['partial' => false])]
    #[Route(path: 'jx/help/partial/{name}', name: 'help_partial', defaults: ['sect' => null, 'partial' => true], priority: 1)]
    #[Route(path: 'jx/help/partial/{sect}/{name}', name: 'help_partial_sect', defaults: ['partial' => true], priority: 1)]
    public function help(?string $sect = null, string $name = 'welcome', bool $partial = false): Response
    {
        return $this->validateSection($sect) ? $this->renderHelpPage($name, $sect, $partial) : $this->redirectToRoute('help');
    }

    /**
     * @param JSONRequestParser $parser
     * @param KernelInterface $kernel
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/help/search', name: 'help_search')]
    public function help_search(JSONRequestParser $parser, KernelInterface $kernel): Response
    {
        $dirs = [null];
        if ($this->isGranted('ROLE_CROW')) $dirs[] = 'crows';
        if ($this->isGranted('ROLE_ANIMAC') || $this->isGranted('ROLE_ORACLE')) $dirs[] = 'animaction';

        $sections = [];
        foreach ($dirs as $dir)
            foreach (scandir($dir !== null ? "{$kernel->getProjectDir()}/templates/ajax/help/$dir" : "{$kernel->getProjectDir()}/templates/ajax/help") as $f)
                if ($f !== '.' && $f !== '..' && $f !== 'shell.html.twig' && str_ends_with($f, '.html.twig')) $sections[] = [$dir, substr($f, 0, -10)];

        $twig = $this->container->get('twig');
        $query = $parser->trimmed('query', '');

        if (mb_strlen($query) > 3)
            $sections_filtered = array_filter( $sections, fn(array $section) => mb_strpos( strtolower(strip_tags( $twig->load( $section[0] ? "ajax/help/{$section[0]}/{$section[1]}.html.twig" : "ajax/help/{$section[1]}.html.twig" )->renderBlock( 'helpContent', []) )), strtolower($query) ) !== false );
        else $sections_filtered = $sections;

        return AjaxResponse::success( true,  ['result' => array_values($sections_filtered), 'filtered' => count($sections_filtered) !== count($sections)] );
    }
}