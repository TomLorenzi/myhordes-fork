<?php

namespace App\Controller\Admin;

use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\ShadowBan;
use App\Entity\Town;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\AntiCheatService;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\TwinoidHandler;
use App\Service\UserFactory;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class AdminUserController extends AdminActionController
{
    /**
     * @Route("jx/admin/users", name="admin_users")
     * @param AntiCheatService $as
     * @return Response
     */
    public function users(AntiCheatService $as): Response
    {
        $report = $as->createMultiAccountReport();
        return $this->render( 'ajax/admin/users/index.html.twig', $this->addDefaultTwigArgs("admin_users_ban", [
            'ma_report' => $report
        ]));
    }

    /**
     * @Route("jx/admin/users/{id}/account/view", name="admin_users_account_view", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function users_account_view(int $id): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return $this->redirect( $this->generateUrl('admin_users') );

        $validations = $this->isGranted('ROLE_ADMIN') ? $this->entity_manager->getRepository(UserPendingValidation::class)->findByUser($user) : [];

        return $this->render( 'ajax/admin/users/account.html.twig', $this->addDefaultTwigArgs("admin_users_account", [
            'user' => $user,
            'validations' => $validations,
        ]));
    }

    /**
     * @Route("api/admin/users/{id}/account/do/{action}/{param}", name="admin_users_account_manage", requirements={"id"="\d+"})
     * @param int $id
     * @param string $action
     * @param JSONRequestParser $parser
     * @param UserFactory $uf
     * @param TwinoidHandler $twin
     * @param UserHandler $userHandler
     * @return Response
     */
    public function user_account_manager(int $id, string $action, JSONRequestParser $parser, UserFactory $uf, TwinoidHandler $twin, UserHandler $userHandler, string $param = ''): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (empty($param)) $param = $parser->get('param', '');

        if (in_array($action, [ 'delete_token', 'invalidate', 'validate', 'twin_full_reset', 'twin_main_reset', 'delete', 'rename', 'shadow', 'unshadow' ]) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($action === 'grant' && $param !== 'NONE' && !$userHandler->admin_canGrant( $this->getUser(), $param ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($action === 'grant' && $param === 'NONE' && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$userHandler->admin_canAdminister( $this->getUser(), $user )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        switch ($action) {
            case 'validate':
                if ($user->getValidated())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $pf = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user, UserPendingValidation::EMailValidation);
                if ($pf) {
                    $this->entity_manager->remove($pf);
                    $user->setPendingValidation(null);
                }
                $user->setValidated(true);
                $this->entity_manager->persist($user);
                break;
            case 'invalidate':
                if (!$user->getValidated())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setValidated(false);
                $uf->announceValidationToken( $uf->ensureValidation( $user, UserPendingValidation::EMailValidation ) );
                $this->entity_manager->persist($user);
                break;
            case 'refresh_tokens': case 'regen_tokens':
                foreach ($this->entity_manager->getRepository(UserPendingValidation::class)->findByUser($user) as $pf) {
                    /** @var $pf UserPendingValidation */
                    if ($action === 'regen_tokens') $pf->generatePKey();
                    $uf->announceValidationToken( $pf );
                    $this->entity_manager->persist( $pf );
                }
                break;
            case 'initiate_pw_reset':case 'enforce_pw_reset':
                if ($action === 'enforce_pw_reset')
                    $user->setPassword(null);
                $uf->announceValidationToken( $uf->ensureValidation( $user, UserPendingValidation::ResetValidation ) );
                $this->entity_manager->persist($user);
                break;
            case 'delete_token':
                /** @var $pv UserPendingValidation */
                if (!$parser->has('tid') || ($pv = $this->entity_manager->getRepository(UserPendingValidation::class)->find((int)$parser->get('tid'))) === null)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                if ($pv->getUser()->getId() !== $id)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $this->entity_manager->remove($pv);
                break;

            case 'twin_full_reset'://, 'twin_main_reset'
                foreach ($user->getTwinoidImports() as $import) {
                    $user->removeTwinoidImport($import);
                    $this->entity_manager->remove($import);
                }

                $pending = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]);
                if ($pending) {
                    $pending->setUser(null);
                    $this->entity_manager->remove($pending);
                }

                $twin->clearImportedData( $user, null, true );
                $user->setTwinoidID(null);
                $this->entity_manager->persist($user);
                break;

            case 'twin_main_reset':

                $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);
                if ($main) {
                    $twin->clearPrimaryImportedData( $user );
                    $main->setMain( false );
                    $this->entity_manager->persist($main);
                }

                break;

            case 'rename':
                if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setName( $param );
                $this->entity_manager->persist($user);
                break;

            case 'delete':
                $userHandler->deleteUser($user);
                $this->entity_manager->persist($user);
                break;

            case 'shadow':
                if (empty($param) || $user->getShadowBan()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $user->setShadowBan( (new ShadowBan())->setAdmin( $this->getUser() )->setCreated( new \DateTime() )->setReason($param) );
                $this->entity_manager->persist($user);
                break;

            case 'unshadow':
                if (!$user->getShadowBan()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $this->entity_manager->remove($user->getShadowBan());
                $user->setShadowBan( null );

                $this->entity_manager->persist($user);
                break;

            case 'grant':
                switch ($param) {
                    case 'NONE':
                        $user->setRightsElevation( User::ROLE_USER );
                        break;
                    case 'ROLE_ORACLE':
                        if ( $user->getRightsElevation() === User::ROLE_CROW )
                            $user->setRightsElevation( User::ROLE_ORACLE );
                        else $user->setRightsElevation( max($user->getRightsElevation(), User::ROLE_ORACLE) );
                        break;
                    case 'ROLE_CROW':
                        $user->setRightsElevation( max($user->getRightsElevation(), User::ROLE_CROW) );
                        break;
                    case 'ROLE_ADMIN':
                        $user->setRightsElevation( max($user->getRightsElevation(), User::ROLE_ADMIN) );
                        break;
                    case 'ROLE_SUPER':
                        $user->setRightsElevation( max($user->getRightsElevation(), User::ROLE_SUPER) );
                        break;
                    default: breaK;
                }
                $this->entity_manager->persist($user);
                break;

            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, [$e->getMessage()] );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/admin/users/{id}/ban/view", name="admin_users_ban_view", requirements={"id"="\d+"})
     * @param int $id
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

        return $this->render( 'ajax/admin/users/ban.html.twig', $this->addDefaultTwigArgs("admin_users_ban", [
            'user' => $user,
            'banned' => $banned,
            'activeBan' => $longestActiveBan,
            'bannings' => $bannings,
            'banCount' => $banCount,
            'lastBan' => $lastBan,
        ]));        
    }

    /**
     * @Route("api/admin/users/{id}/ban", name="admin_users_ban", requirements={"id"="\d+"})
     * @param int $id
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param AdminActionHandler $admh
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
     * @Route("api/admin/users/{id}/ban/lift", name="admin_users_ban_lift", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_ban_lift(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->liftAllBans($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/admin/users/find", name="admin_users_find")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function users_find(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $searchName = $parser->get('name');
        $user = $em->getRepository(User::class)->findOneBy(array('name' => $searchName));
        
        if (isset($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('admin_users_ban_view', ['id' => $user->getId()])] );

        return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

    /**
     * @Route("jx/admin/users/fuzzyfind", name="admin_users_fuzzyfind")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function users_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $searchName = $parser->get('name');
        $users = $em->getRepository(User::class)->findByNameContains($searchName);

        return $this->render( 'ajax/admin/users/list.html.twig', $this->addDefaultTwigArgs("admin_users_citizen", [
            'users' => $users,
        ]));
    }

    /**
     * @Route("jx/admin/users/{id}/citizen/view", name="admin_users_citizen_view", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function users_citizen_view(int $id): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);

        /** @var Citizen $citizen */
        $citizen = $user->getActiveCitizen();
        if ($citizen) {
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
        
        return $this->render( 'ajax/admin/users/citizen.html.twig', $this->addDefaultTwigArgs("admin_users_citizen", [
            'town' => $town,
            'active' => $active,
            'alive' => $alive,
            'user' => $user,
            'citizen_id' => $citizen ? $citizen->getId() : -1,
        ]));        
    }

    /**
     * @Route("api/admin/users/{id}/citizen/headshot", name="admin_users_citizen_headshot", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_citizen_headshot(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->headshot($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/admin/users/{id}/citizen/confirm_death", name="admin_users_citizen_confirm_death", requirements={"id"="\d+"})
     * @return Response
     */
    public function users_citizen_confirm_death(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->confirmDeath($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("jx/admin/users/{id}/pictos/view", name="admin_users_pictos_view", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function users_pictos_view(int $id): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);

        $pictos = $this->entity_manager->getRepository(Picto::class)->findByUser($user);
        return $this->render( 'ajax/admin/users/pictos.html.twig', $this->addDefaultTwigArgs("admin_users_pictos", [
            'user' => $user,
            'pictos' => $pictos
        ]));        
    }
}
