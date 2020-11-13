<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Response\AjaxResponse;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class HelpController extends AbstractController
{
    protected $entity_manager;
    protected $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->entity_manager = $em;
        $this->translator = $translator;
    }

    /**
     * @Route("jx/help/{name}", name="help")
     * @param Request $request
     * @param string $name
     * @return Response
     */
    public function help(Request $request, string $name = 'welcome'): Response
    {
        if ($name === 'shell') return $this->redirect($this->generateUrl('help'));
        try {
            return $this->render( "ajax/help/$name.html.twig", ['section' => $name]);
        } catch (Exception $e){
            return $this->redirect($this->generateUrl('help'));
        }
    }
}