<?php

namespace App\Controller;

use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AdminActionController extends AbstractController
{
    /**
     * @Route("/admin/action/{id}", name="admin_action", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function index(int $id): Response
    {
        switch ($id)
        {
            case 1:
                
                break;
            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }
        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }
}
