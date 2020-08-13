<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Response\AjaxResponse;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
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
     * @return Response
     */
    public function soul_news(Request $request, string $name = 'welcome'): Response
    {
        //try {
            return $this->render( "ajax/help/$name.html.twig", ['section' => $name]);
        /*} catch (Exception $e){
            return $this->redirect($this->generateUrl('help'));
        }*/
    }
}