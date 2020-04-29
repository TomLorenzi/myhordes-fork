<?php

namespace App\Controller;

use App\Entity\AdminReport;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
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

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
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
        switch ($id)
        {
            case 1: 
                return $this->redirect($this->generateUrl('admin_users'));             
                break;
            case 2:
                return $this->redirect($this->generateUrl('admin_reports'));   
                break;
            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }
        return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
    }

    /**
     * @Route("jx/admin/action/reports", name="admin_reports")
     * @return Response
     */
    public function reports(): Response
    {
        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['seen' => false]);
        return $this->render( 'admin_action/reports/reports.html.twig', [  
            'reports' => $reports,        
        ]);      
    }

    /**
     * @Route("jx/admin/action/reports/clear", name="admin_reports_clear")
     * @return Response
     */
    public function reports_clear(JSONRequestParser $parser, AdminActionHandler $admh): Response
    {
        if (!$parser->has_all(['postId'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->getUser();
        $postId = $parser->get('postId');
        if ($admh->clearReports($user->getId(), $postId))
            return AjaxResponse::success();
        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("jx/admin/action/users", name="admin_users")
     * @return Response
     */
    public function users(): Response
    {
        return $this->render( 'admin_action/index.html.twig', $this->addDefaultTwigArgs("admin_users_ban", [          
        ]));      
    }

    /**
     * @Route("jx/admin/action/users/{id}/ban/view", name="admin_users_ban_view", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_ban_view(int $id): Response
    {

        $user = $this->entity_manager->getRepository(User::class)->find($id);
        $banned = $user->getIsBanned();
        
        $longestActiveBan = $user->getLongestActiveBan();
        $bannings = $user->getBannings();
        $banCount = $bannings->count();
        $lastBan = null;
        if ($banCount > 1)
            $lastBan = $bannings[$banCount - 1];
        else $lastBan = $bannings[0];

        return $this->render( 'admin_action/users/ban.html.twig', $this->addDefaultTwigArgs("admin_users_ban", [
            'user' => $user,
            'banned' => $banned,
            'activeBan' => $longestActiveBan,
            'bannings' => $bannings,
            'banCount' => $banCount,
            'lastBan' => $lastBan,
        ]));        
    }

    /**
     * @Route("jx/admin/action/users/{id}/ban", name="admin_users_ban", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_ban(int $id, JSONRequestParser $parser, EntityManagerInterface $em, AdminActionHandler $admh): Response
    {
        
        if (!$parser->has_all(['reason'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$parser->has_all(['duration'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        
        $reason  = $parser->get('reason');
        $duration  = intval($parser->get('duration'));
        
        if ($admh->ban($this->getUser()->getId(), $id, $reason, $duration))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("jx/admin/action/users/{id}/ban/lift", name="admin_users_ban_lift", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_ban_lift(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->liftAllBans($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("jx/admin/action/users/find", name="admin_users_find")
     * @return Response
     */
    public function users_find(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        $userRoles = $this->getUser()->getRoles();
        if (!in_array("ROLE_ADMIN", $userRoles))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $searchName = $parser->get('name');
        $user = $em->getRepository(User::class)->findOneBy(array('name' => $searchName));
        
        if (isset($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_users_ban_view', ['id' => $user->getId()])] );

        return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

    /**
     * @Route("jx/admin/action/users/{id}/citizen/view", name="admin_users_citizen_view", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_citizen_view(int $id): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        $banned = $user->getIsBanned();
        
        $longestActiveBan = $user->getLongestActiveBan();
        $bannings = $user->getBannings();
        $banCount = $bannings->count();
        $lastBan = null;
        if ($banCount > 1)
            $lastBan = $bannings[$banCount - 1];
        else $lastBan = $bannings[0];

        $citizen = $user->getActiveCitizen();
        if (isset($citizen)) {
            $active = true;
            $town = $citizen->getTown();
            if ($citizen->getAlive()) {
                $alive = true;
            }           
            else {
                $alive = false;
            }               
        }                    
        else {
            $active = false;
            $alive = false;
            $town = null;
        }
        
        return $this->render( 'admin_action/users/citizen.html.twig', $this->addDefaultTwigArgs("admin_users_citizen", [
            'town' => $town,
            'active' => $active,
            'alive' => $alive,


            'user' => $user,
            'banned' => $banned,
            'activeBan' => $longestActiveBan,
            'bannings' => $bannings,
            'banCount' => $banCount,
            'lastBan' => $lastBan,
        ]));        
    }

    /**
     * @Route("jx/admin/action/users/{id}/citizen/headshot", name="admin_users_citizen_headshot", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_citizen_headshot(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->headshot($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("jx/admin/action/users/{id}/citizen/confirm_death", name="admin_users_citizen_confirm_death", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_citizen_confirm_death(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->confirmDeath($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }
}
