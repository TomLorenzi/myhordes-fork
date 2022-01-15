<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Entity\OfficialGroup;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
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
                'support' => count($support_groups) === 1 ? $support_groups[0] : null
            ]));
        } catch (Exception $e){
            return $this->redirect($this->generateUrl('help'));
        }
    }
}