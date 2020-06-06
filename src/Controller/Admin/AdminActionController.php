<?php

namespace App\Controller\Admin;

use App\Entity\AdminReport;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\UserFactory;
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
    protected $conf;

    public function __construct(EntityManagerInterface $em, ConfMaster $conf)
    {
        $this->entity_manager = $em;
        $this->conf = $conf;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null): array
    {
        $data = $data ?? [];

        $data["admin_tab"] = $section;

        return $data;
    }

    /**
     * @Route("jx/admin/action/{id}", name="admin_action", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function index(int $id): Response
    {
        switch ($id) {
            case 1:
                return $this->redirect($this->generateUrl('admin_users'));
                break;
            case 2:
                return $this->redirect($this->generateUrl('admin_reports'));
                break;
            case 3:
                return $this->redirect($this->generateUrl('admin_town_list'));
                break;
            case 4:
                return $this->redirect($this->generateUrl('admin_changelogs'));
                break;
            default:
                break;
        }
        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }
}
