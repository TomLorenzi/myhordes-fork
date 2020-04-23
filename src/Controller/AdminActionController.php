<?php

namespace App\Controller;

use App\Entity\ExternalApp;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminActionController extends AbstractController
{

    protected $entity_manager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entity_manager = $em;

    }
    /**
     * @Route("jx/admin/action/{id}", name="admin_action", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function index(int $id): Response
    {
        switch ($id)
        {
            case 1: 
                return $this->redirect($this->generateUrl('admin_users'));             
                // return $this->render( 'admin_action/index.html.twig', [
                    
                // ] );
                break;
            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }
        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }

    /**
     * @Route("jx/admin/action/users", name="admin_users")
     * @return Response
     */
    public function users(): Response
    {
        return $this->render( 'admin_action/index.html.twig', [

        ] );
    }
}
