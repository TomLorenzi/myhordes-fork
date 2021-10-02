<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
use App\Entity\AccountRestriction;
use App\Entity\Award;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ConnectionWhitelist;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Entity\UserDescription;
use App\Entity\UserGroup;
use App\Entity\UserPendingValidation;
use App\Entity\UserReferLink;
use App\Entity\UserSponsorship;
use App\Exception\DynamicAjaxResetException;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\AntiCheatService;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\MediaService;
use App\Service\PermissionHandler;
use App\Service\TwinoidHandler;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
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
    public function users_account_view(int $id, HTMLService $html): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return $this->redirect( $this->generateUrl('admin_users') );

        $validations = $this->isGranted('ROLE_ADMIN') ? $this->entity_manager->getRepository(UserPendingValidation::class)->findByUser($user) : [];
        $desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);

        $all_sponsored = $this->entity_manager->getRepository(UserSponsorship::class)->findBy(['sponsor' => $user]);

        return $this->render( 'ajax/admin/users/account.html.twig', $this->addDefaultTwigArgs("admin_users_account", [
            'user' => $user,
            'user_desc' => $desc ? $html->prepareEmotes($desc->getText()) : null,
            'validations' => $validations,

            'ref' => $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['user' => $user]),
            'spon'          => $this->entity_manager->getRepository(UserSponsorship::class)->findOneBy(['user' => $user]),
            'spon_active'   => array_filter( $all_sponsored, fn(UserSponsorship $s) => !$this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') &&  $s->getUser()->getValidated() ),
            'spon_inactive' => array_filter( $all_sponsored, fn(UserSponsorship $s) =>  $this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') || !$s->getUser()->getValidated() ),
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
     * @param PermissionHandler $perm
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param CrowService $crow
     * @param KernelInterface $kernel
     * @param string $param
     * @return Response
     */
    public function user_account_manager(int $id, string $action, JSONRequestParser $parser, UserFactory $uf,
                                         TwinoidHandler $twin, UserHandler $userHandler, PermissionHandler $perm,
                                         UserPasswordHasherInterface $passwordEncoder, CrowService $crow, KernelInterface $kernel,
                                         string $param = ''): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (empty($param)) $param = $parser->get('param', '');

        if (in_array($action, [
            'delete_token', 'invalidate', 'validate', 'twin_full_reset', 'twin_main_reset', 'twin_main_full_import', 'delete', 'rename',
            'shadow', 'whitelist', 'unwhitelist', 'etwin_reset', 'overwrite_pw', 'initiate_pw_reset',
            'enforce_pw_reset', 'change_mail', 'ref_rename', 'ref_disable', 'ref_enable', 'set_sponsor'
        ]) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (str_starts_with($action, 'dbg_') && (!$this->isGranted('ROLE_ADMIN') || $kernel->getEnvironment() !== 'dev') )
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($action === 'grant' && $param !== 'NONE' && !$userHandler->admin_canGrant( $this->getUser(), $param ))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if ($action === 'grant' && $param === 'NONE' && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$userHandler->admin_canAdminister( $this->getUser(), $user )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        switch ($action) {
            case 'permit_town_forum_access': case 'unpermit_town_forum_access':
                if (!is_numeric($param) || !$userHandler->hasRole($user, 'ROLE_ANIMAC'))
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $town = $this->entity_manager->getRepository(Town::class)->find( $param );
                if ($town === null || $town->getForum() === null)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                foreach ($town->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen->getUser() === $user)
                        return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $town->getId()] );
                if ($town_group === null)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($action === 'permit_town_forum_access') $perm->associate( $user, $town_group );
                else $perm->disassociate( $user, $town_group );
                break;

            case 'validate':
                if ($user->getValidated())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $pf = $this->entity_manager->getRepository(UserPendingValidation::class)->findOneByUserAndType($user, UserPendingValidation::EMailValidation);
                if ($pf) {
                    $this->entity_manager->remove($pf);
                    $user->setPendingValidation(null);
                }
                $user->setValidated(true);
                $perm->associate($user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup ));
                $this->entity_manager->persist($user);
                break;

            case 'invalidate':
                if (!$user->getValidated())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setValidated(false);
                $perm->disassociate($user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultUserGroup ));
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

            case 'overwrite_pw':
                if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setPassword( $passwordEncoder->hashPassword( $user, $param ) );
                $this->entity_manager->persist($user);
                break;

            case 'change_mail':
                $user->setEmail( $param ?? null );
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
                    $user->setTwinoidImportPreview(null);
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

            case 'twin_main_full_import':

                $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);
                if ($main) {
                    $twin->importData($main->getUser(), $main->getScope(), $main->getData($this->entity_manager), true, false);
                    $this->entity_manager->persist($user);
                    foreach ($user->getPastLifes() as $pastLife)
                        if ($pastLife->getLimitedImport())
                            $this->entity_manager->persist($pastLife->setLimitedImport(false)->setDisabled(false));
                    $this->entity_manager->flush();
                    $user->setImportedSoulPoints($this->user_handler->fetchImportedSoulPoints($user));
                    $this->entity_manager->persist($user);
                }
                break;

            case 'etwin_reset':

                if (empty($param) || $user->getEternalID() === null)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user
                    ->setEternalID( null )
                    ->setEmail( $param )
                    ->setPassword( null );
                $this->entity_manager->persist($user);
                break;

            case 'rename':
                if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setName( $param );
                $this->entity_manager->persist($user);
                break;

            case 'rename_pseudo':
                if (empty($param)) $user->setDisplayName( null );
                else $user->setDisplayName( $param );
                $this->entity_manager->persist($user);
                break;

            case 'delete':
                if ($user->getEternalID())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $userHandler->deleteUser($user);
                $this->entity_manager->persist($user);
                break;

            case 'shadow':
                if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $this->entity_manager->persist(
                    (new AccountRestriction())
                        ->setUser($user)
                        ->setActive(true)
                        ->setConfirmed(true)
                        ->setPublicReason($param)
                        ->setOriginalDuration(-1)
                        ->setExpires(null)
                        ->setModerator( $this->getUser() )
                        ->addConfirmedBy( $this->getUser() )
                        ->setCreated( new \DateTime() )
                        ->setRestriction( AccountRestriction::RestrictionGameplay )
                );

                $n = $crow->createPM_moderation( $user, CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionImpose, -1, $param );
                if ($n) $this->entity_manager->persist($n);
                break;

            case 'whitelist':
                if ($user->getConnectionWhitelists()->isEmpty()) $user->getConnectionWhitelists()->add( $wl = new ConnectionWhitelist() );
                else $wl = $user->getConnectionWhitelists()->getValues()[0];

                $wl->addUser($user);
                if (!is_array($param)) $param = [$param];
                foreach ($param as $other_user_id) {
                    /** @var User $other_user */
                    $other_user = $this->entity_manager->getRepository(User::class)->find($other_user_id);
                    if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    $wl->addUser($other_user);
                }

                $this->entity_manager->persist($wl);
                break;

            case 'unwhitelist':
                if (!is_array($param)) $param = [$param];
                foreach ($param as $other_user_id) {
                    /** @var User $other_user */
                    $other_user = $this->entity_manager->getRepository(User::class)->find($other_user_id);
                    if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                    foreach ($user->getConnectionWhitelists() as $wl)
                        $wl->removeUser($other_user);

                    foreach ($user->getConnectionWhitelists() as $wl)
                        if ($wl->getUsers()->count() < 2) $this->entity_manager->remove($wl);
                        else $this->entity_manager->persist($wl);
                }
                break;

                //'ref_rename', 'ref_disable', 'ref_enable', 'set_sponsor'
            case 'ref_rename':case 'ref_disable':case 'ref_enable':

                $existing_ref = $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['user' => $user]);
                if (!$existing_ref && $action === 'ref_rename')
                    $existing_ref = (new UserReferLink())->setUser($user)->setActive(true);
                elseif (!$existing_ref) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($action === 'ref_rename') $existing_ref->setName( $param );
                else $existing_ref->setActive($action !== 'ref_disable');

                $this->entity_manager->persist($existing_ref);
                break;

            case 'set_sponsor':

                $other_user = $this->entity_manager->getRepository(User::class)->find($param);
                if (!$other_user || $other_user === $user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($this->entity_manager->getRepository(UserSponsorship::class)->findOneBy(['user' => $user]))
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $current = $other_user;
                $i = 0;
                while ($s = $this->entity_manager->getRepository(UserSponsorship::class)->findOneBy(['user' => $current]))
                    if ($s->getSponsor() === $user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    else {
                        //echo "Sponsor " . $s->getSponsor()->getName() . " - User " . $s->getUser()->getName() . " |||||||\n";
                        $current = $s->getSponsor();
                        $i++;
                        if ($i > 1000) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    }

                $this->entity_manager->persist( (new UserSponsorship)->setUser($user)->setSponsor($other_user)->setCountedSoulPoints(0)->setCountedHeroExp(0) );

                break;

            case 'remove_sponsorship':
                $s = $this->entity_manager->getRepository(UserSponsorship::class)->find($param);
                if (!$s) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($s->getUser() !== $user && $s->getSponsor() !== $user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $this->entity_manager->remove($s);

                break;

            case 'clear_title':
                $user->setActiveIcon(null)->setActiveTitle(null);
                break;
            case 'clear_desc':
                $desc = $this->entity_manager->getRepository(UserDescription::class)->findOneBy(['user' => $user]);
                if ($desc) $this->entity_manager->remove($desc);
                break;
            case 'clear_avatar':
                if ($user->getAvatar()) {
                    $this->entity_manager->remove($user->getAvatar());
                    $user->setAvatar(null);
                }
                break;

            case 'grant':
                switch ($param) {
                    case 'NONE':
                        $user->setRightsElevation( User::USER_LEVEL_BASIC );

                        $perm->disassociate( $user, $perm->getDefaultGroup(UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                        break;

                    case 'ROLE_CROW':
                        $user->setRightsElevation( max($user->getRightsElevation(), User::USER_LEVEL_CROW) );
                        $user->removeRoleFlag( User::USER_ROLE_ORACLE | User::USER_ROLE_ANIMAC );

                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                        break;
                    case 'ROLE_ADMIN': case 'ROLE_SUPER':
                        $user->setRightsElevation( max($user->getRightsElevation(), $param === 'ROLE_ADMIN' ? User::USER_LEVEL_ADMIN : User::USER_LEVEL_SUPER) );

                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                        break;

                    case 'FLAG_TEAM':
                        $user->addRoleFlag( User::USER_ROLE_TEAM );
                        break;

                    case '!FLAG_TEAM':
                        $user->removeRoleFlag( User::USER_ROLE_TEAM );
                        break;

                    case 'FLAG_ORACLE':
                        if ( $user->getRightsElevation() === User::USER_LEVEL_CROW )
                            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                        else $user->addRoleFlag( User::USER_ROLE_ORACLE );

                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        break;

                    case '!FLAG_ORACLE':
                        $user->removeRoleFlag( User::USER_ROLE_ORACLE );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        break;

                    case 'FLAG_ANIMAC':
                        if ( $user->getRightsElevation() === User::USER_LEVEL_CROW )
                            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                        else $user->addRoleFlag( User::USER_ROLE_ANIMAC );

                        //$perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        break;

                    case '!FLAG_ANIMAC':
                        $user->removeRoleFlag( User::USER_ROLE_ANIMAC );
                        //$perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        break;

                    default: breaK;
                }
                $this->entity_manager->persist($user);
                break;

            case 'dbg_herodays':
                if (empty($param) || !is_numeric($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setHeroDaysSpent( max(0,$param) );
                $this->entity_manager->persist($user);
                break;


            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
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
        if (!$user) throw new DynamicAjaxResetException(Request::createFromGlobals());

        return $this->render( 'ajax/admin/users/ban.html.twig', $this->addDefaultTwigArgs("admin_users_ban", [
            'user' => $user,
            'existing' => $this->entity_manager->getRepository(AccountRestriction::class)->findBy(['user' => $user], ['active' => 'ASC', 'confirmed' => 'ASC', 'created' => 'ASC'])
        ]));
    }

    private function requires_crow_confirmation(AccountRestriction $a): bool {
        return $a->getOriginalDuration() > 2592000;
    }

    private function requires_admin_confirmation(AccountRestriction $a): bool {
        return $a->getOriginalDuration() >= 31536000 || ($a->getRestriction() & AccountRestriction::RestrictionGameplay) === AccountRestriction::RestrictionGameplay;
    }

    private function check_ban_confirmation(AccountRestriction $a): bool {

        if ($a->getConfirmed()) return true;

        if (!$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' )) {

            if ($this->requires_crow_confirmation($a) && count($a->getConfirmedBy()) < 2) return false;
            if ($this->requires_admin_confirmation($a)) {
                $confirmed_by_admin = false;
                foreach ($a->getConfirmedBy() as $u) if ($this->user_handler->hasRole( $u, 'ROLE_ADMIN' )) $confirmed_by_admin = true;
                if (!$confirmed_by_admin) return false;
            }
        }

        $a->setConfirmed(true)->setExpires( $a->getOriginalDuration() < 0 ? null : (new \DateTime())->setTimestamp( time() + $a->getOriginalDuration() ) );
        if ($a->getRestriction() & AccountRestriction::RestrictionSocial)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetForumBan, CrowService::ModerationActionImpose, $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(), $a->getPublicReason() ));
        if ($a->getRestriction() & AccountRestriction::RestrictionGameplay)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionRevoke, -1, $a->getPublicReason() ));
        return true;
    }

    /**
     * @Route("api/admin/users/{uid}/ban/{bid}/confirm", name="admin_users_ban_confirm", requirements={"uid"="\d+","bid"="\d+"})
     * @param int $uid
     * @param int $bid
     * @return Response
     */
    public function users_confirm_ban(int $uid, int $bid): Response
    {
        $a = $this->entity_manager->getRepository(AccountRestriction::class)->find($bid);
        if ($a === null || $a->getUser()->getId() !== $uid) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$a->getActive() || $a->getExpires() && $a->getExpires() < new \DateTime()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($a->getConfirmedBy()->contains($this->getUser())) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $a->addConfirmedBy($this->getUser());
        $this->check_ban_confirmation($a);

        $this->entity_manager->persist($a);
        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/users/{uid}/ban/{bid}/disable", name="admin_users_ban_disable", requirements={"uid"="\d+","bid"="\d+"})
     * @param int $uid
     * @param int $bid
     * @return Response
     */
    public function users_disable_ban(int $uid, int $bid): Response
    {
        $a = $this->entity_manager->getRepository(AccountRestriction::class)->find($bid);
        if ($a === null || $a->getUser()->getId() !== $uid) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$a->getActive() || $a->getExpires() && $a->getExpires() < new \DateTime()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $is_admin_confirmed = $is_super_confirmed = false;
        foreach ($a->getConfirmedBy() as $confirmer) {
            if ($this->user_handler->hasRole($confirmer, 'ROLE_ADMIN')) $is_admin_confirmed = true;
            if ($this->user_handler->hasRole($confirmer, 'ROLE_SUPER')) $is_super_confirmed = true;
        }

        if ($is_super_confirmed && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_SUPER' )) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
        if ($is_admin_confirmed && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' )) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $a->setActive(false)->setInternalReason($a->getInternalReason() . " ~ [DISABLED BY {$this->getUser()->getName()}]");
        $this->entity_manager->persist($a);

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/admin/users/{id}/ban", name="admin_users_ban", requirements={"id"="\d+"})
     * @param int $id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function users_ban(int $id, JSONRequestParser $parser): Response
    {
        if (!$parser->has_all(['reason','duration','restriction'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($this->user_handler->hasRole( $user, 'ROLE_CROW' ) && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
        if ($this->user_handler->hasRole( $user, 'ROLE_ADMIN' ) && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_SUPER' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
        if ($this->user_handler->hasRole( $user, 'ROLE_SUPER' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $reason    = trim($parser->get('reason'));
        $note      = trim($parser->get('note'));
        $duration  = $parser->get_int('duration');
        $mask      = $parser->get_int('restriction', 0);

        if ($duration === 0 || $mask <= 0 || ($mask & ~(AccountRestriction::RestrictionSocial | AccountRestriction::RestrictionGameplay | AccountRestriction::RestrictionProfile)))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $a = (new AccountRestriction())
            ->setUser($user)
            ->setRestriction( $mask )
            ->setOriginalDuration( $duration )
            ->setCreated( new \DateTime() )
            ->setActive( true )
            ->setConfirmed( false )
            ->setModerator( $this->getUser() )
            ->setPublicReason( $reason )
            ->setInternalReason( $note )
            ->addConfirmedBy( $this->getUser() );
        $this->check_ban_confirmation($a);
        $this->entity_manager->persist($a);

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
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

        $parts = explode(':', $parser->get('name'), 2);
        list($query,$searchName) = count($parts) === 2 ? $parts : ['u', $parts[0]];

        switch ($query) {
            case 'i': $users = $em->getRepository(User::class)->findBy(['id' => (int)$searchName]); break; // ID
            case 'u': $users = $em->getRepository(User::class)->findByNameContains($searchName); break; // Username & Display name
            case 'd': $users = $em->getRepository(User::class)->findByDisplayNameContains($searchName); break; // Only Display name
            case 'e': $users = $this->isGranted('ROLE_ADMIN') ? $em->getRepository(User::class)->findByMailContains($searchName) : []; break; // Mail
            case 'ue':case 'eu': $users = $this->isGranted('ROLE_ADMIN') ? $em->getRepository(User::class)->findByNameOrMailContains($searchName) : []; break; // Username & Mail
            case 't':  // Twinoid ID
                $users = $em->getRepository(User::class)->findBy(['twinoidID' => (int)$searchName]);
                foreach ($em->getRepository(TwinoidImportPreview::class)->findBy(['twinoidID' => (int)$searchName]) as $ip)
                    if (!in_array($ip->getUser(), $users)) $users[] = $ip->getUser();
                break;
            case 'et': $users = $em->getRepository(User::class)->findBy(['eternalID' => $searchName]); break; // EternalTwin ID
            case 'v0': $users = $em->getRepository(User::class)->findBy(['validated' => false]); break; // Non-validated
            case 'x':  $users = $em->getRepository(User::class)->findAboutToBeDeleted(); break; // Non-validated
            case 'rc': $users = $em->getRepository(User::class)->findBy(['rightsElevation' => [User::USER_LEVEL_CROW]]);   break; // Is Crow
            case 'ra': $users = $em->getRepository(User::class)->findBy(['rightsElevation' => [User::USER_LEVEL_ADMIN, User::USER_LEVEL_SUPER]]);  break; // Is Admin
            case 'rb': $users = $em->getRepository(User::class)->findBy(['rightsElevation' => [User::USER_LEVEL_SUPER]]);  break; // Is Brainbox
            default: $users = [];
        }

        return $this->render( 'ajax/admin/users/list.html.twig', $this->addDefaultTwigArgs("admin_users_citizen", [
            'users' => $users,
            'nohref' => $parser->get('no-href',false)
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
            $alive = $citizen->getAlive();
        }                    
        else {
            $active = false;
            $alive = false;
            $town = null;
        }

        $pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($pictoProtos, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $itemPrototypes = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        usort($itemPrototypes, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'items'), $this->translator->trans($b->getLabel(), [], 'items'));
        });

        $citizenStati = $this->entity_manager->getRepository(CitizenStatus::class)->findAll();
        usort($citizenStati, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $disabled_profs = $town ? $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, []) : [];
        $professions = array_filter($this->entity_manager->getRepository( CitizenProfession::class )->findSelectable(),
            fn(CitizenProfession $p) => !in_array($p->getName(),$disabled_profs)
        );

        $citizenRoles = $this->entity_manager->getRepository(CitizenRole::class)->findAll();

        return $this->render( 'ajax/admin/users/citizen.html.twig', $this->addDefaultTwigArgs("admin_users_citizen", [
            'town' => $town,
            'active' => $active,
            'alive' => $alive,
            'user' => $user,
            'user_citizen' => $citizen,
            'home_upgrades' => $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findAll(),
            'itemPrototypes' => $itemPrototypes,
            'pictoPrototypes' => $pictoProtos,
            'citizenStati' => $citizenStati,
            'citizenRoles' => $citizenRoles,
            'citizenProfessions' => $professions,
            'citizen_id' => $citizen ? $citizen->getId() : -1,
        ]));        
    }

    /**
     * @Route("api/admin/users/{id}/citizen/headshot", name="admin_users_citizen_headshot", requirements={"id"="\d+"})
     * @param int $id
     * @param AdminActionHandler $admh
     * @return Response
     */
    public function users_citizen_headshot(int $id, AdminActionHandler $admh): Response
    {                
        if ($admh->headshot($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/admin/users/{id}/citizen/engagement/{cid}", name="admin_users_citizen_engage", requirements={"id"="\d+","cid"="\d+"})
     * @param int $id
     * @param int $cid
     * @return Response
     */
    public function users_update_engagement(int $id, int $cid): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($user->getActiveCitizen()) $this->entity_manager->persist($user->getActiveCitizen()->setActive(false));

        if ($cid !== 0) {
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($cid);
            if (!$citizen || $citizen->getUser() !== $user || (!$citizen->getAlive() && $citizen->getProfession()->getName() !== CitizenProfession::DEFAULT))
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            $this->entity_manager->persist($citizen->setActive(true));
        }

        $this->entity_manager->flush();
        return AjaxResponse::success();
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

        $protos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($protos, function($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $f_protos = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findAll();
        $features = [];
        $season = $this->entity_manager->getRepository(Season::class)->findLatest();
        foreach ($f_protos as $p)
            if ($ff = $this->entity_manager->getRepository(FeatureUnlock::class)->findOneActiveForUser($user,$season,$p))
                $features[] = $ff;

        return $this->render( 'ajax/admin/users/pictos.html.twig', $this->addDefaultTwigArgs("admin_users_pictos", [
            'user' => $user,
            'pictos' => $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user),
            'pictos_all' => $this->entity_manager->getRepository(Picto::class)->findByUser($user),
            'pictos_mh' => $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user, false),
            'pictos_im' => $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user, true),
            'pictos_old' => $this->entity_manager->getRepository(Picto::class)->findOldByUser($user),
            'pictoPrototypes' => $protos,
            'features' => $features,
            'featurePrototypes' => $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findAll(),
            'icon_max_size' => $this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD, 3145728)
        ]));
    }

    /**
     * @Route("/api/admin/users/{id}/picto/give", name="admin_user_give_picto", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @param KernelInterface $kernel
     * @return Response
     */
    public function user_give_picto(int $id, JSONRequestParser $parser, KernelInterface $kernel): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if(!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number', 1);

        if ($prototype_id === -42 && $kernel->getEnvironment() === 'dev')
            $prototypes = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        else {
            /** @var PictoPrototype $prototype */
            $prototype = $this->entity_manager->getRepository(PictoPrototype::class)->find($prototype_id);
            if ($prototype === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $prototypes = [$prototype];
        }

        foreach ( $prototypes as $pictoPrototype ) {
            $picto = $this->entity_manager->getRepository(Picto::class)->findByUserAndTownAndPrototype($user, null, $pictoPrototype);
            if (null === $picto) {
                $picto = new Picto();
                $picto->setPrototype($pictoPrototype)
                    ->setPersisted(2)
                    ->setUser($user);
                $user->addPicto($picto);
                $this->entity_manager->persist($user);
            }

            $picto->setCount($picto->getCount() + $number);
            if ($picto->getCount() > 0) $this->entity_manager->persist($picto);
            else $this->entity_manager->remove($picto);
        }

        $this->entity_manager->flush();

        $this->user_handler->computePictoUnlocks($user);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/users/{id}/unique_award/manage", name="admin_user_manage_unique_award", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @param MediaService $media
     * @return Response
     */
    public function user_manage_unique_award(int $id, JSONRequestParser $parser, MediaService $media): Response {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$parser->has('id') && $parser->get_int('delete', 0))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($parser->has('id', true)) {
            $award = $this->entity_manager->getRepository(Award::class)->find( $parser->get_int('id') );
            if (!$award || $award->getUser() !== $user || $award->getPrototype())
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        } else $award = new Award();

        if ($parser->get_int('delete', 0)) {

            if ($user->getActiveTitle() === $award) $user->setActiveTitle(null);
            if ($user->getActiveIcon() === $award) $user->setActiveIcon(null);
            $user->getAwards()->removeElement($award);
            $this->entity_manager->remove($award);
            $this->entity_manager->persist($user);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
            return AjaxResponse::success();
        }

        if ($parser->has('title', true) === $parser->has('icon', true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($parser->has('title', true)) {
            if ($award->getCustomIcon() !== null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            $this->entity_manager->persist($award->setUser($user)->setCustomTitle($parser->get('title')));
        } else {
            if ($award->getCustomTitle() !== null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            $payload = $parser->get_base64('icon');

            if (strlen( $payload ) > $this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD))
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            if ($media->resizeImageSimple( $payload, 16, 16, $processed_format, false ) !== MediaService::ErrorNone)
                return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

            $this->entity_manager->persist( $award
                                                ->setUser($user)
                                                ->setCustomIcon($payload)
                                                ->setCustomIconName(md5($payload))
                                                ->setCustomIconFormat(strtolower( $processed_format ))
            );
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/users/{id}/comments/{cid}", name="admin_user_edit_comment", requirements={"id"="\d+","cid"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * @param int $id User ID
     * @param int $cid
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    public function user_edit_comments(int $id, int $cid, JSONRequestParser $parser): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if(!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen_proxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($cid);
        if(!$citizen_proxy || $citizen_proxy->getUser() !== $user)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $mode = $parser->get('mod', null, ['last','com']);
        if ($mode === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $text = $parser->get('txt', null);
        if (empty($text)) $text = null;

        if ($mode === 'last') $citizen_proxy->setLastWords($text);
        else $citizen_proxy->setComment($text)->setCommentLocked(true);

        $this->entity_manager->persist($citizen_proxy);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/users/{id}/feature/give", name="admin_user_give_feature", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     * @throws Exception
     */
    public function user_give_feature(int $id, JSONRequestParser $parser): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if(!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $prototype_id = $parser->get('prototype');
        $number = $parser->get_int('number', 1);
        $date = new \DateTime($parser->get('date'));

        /** @var FeatureUnlockPrototype $prototype */
        $prototype = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->find($prototype_id);
        if ($prototype === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $feature = (new FeatureUnlock())
            ->setUser($user)
            ->setPrototype($prototype);

        switch ($parser->get_int('type', -1)) {
            case 0:
                $feature->setExpirationMode(FeatureUnlock::FeatureExpirationNone);
                break;
            case 1:
                $feature
                    ->setExpirationMode(FeatureUnlock::FeatureExpirationSeason)
                    ->setSeason( $this->entity_manager->getRepository(Season::class)->findLatest() );
                break;
            case 2:
                $feature
                    ->setExpirationMode(FeatureUnlock::FeatureExpirationTimestamp)
                    ->setTimestamp( $date );
                break;
            case 3:
                if ($number <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $feature
                    ->setExpirationMode(FeatureUnlock::FeatureExpirationTownCount)
                    ->setTownCount( $number );
                break;
            default: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $this->entity_manager->persist($feature);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

}
