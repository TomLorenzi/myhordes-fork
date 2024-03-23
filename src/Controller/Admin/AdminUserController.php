<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Entity\AccountRestriction;
use App\Entity\Award;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ConnectionIdentifier;
use App\Entity\ConnectionWhitelist;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RegistrationToken;
use App\Entity\Season;
use App\Entity\SocialRelation;
use App\Entity\SoulResetMarker;
use App\Entity\Town;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Entity\UserDescription;
use App\Entity\UserGroup;
use App\Entity\UserPendingValidation;
use App\Entity\UserReferLink;
use App\Entity\UserSponsorship;
use App\Entity\UserSwapPivot;
use App\Enum\ServerSetting;
use App\Exception\DynamicAjaxResetException;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\AdminHandler;
use App\Service\AntiCheatService;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\Media\ImageService;
use App\Service\PermissionHandler;
use App\Service\TwinoidHandler;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminUserController extends AdminActionController
{
	/**
	 * @param JSONRequestParser      $parser
	 * @param EntityManagerInterface $em
	 * @return Response
	 * @throws Exception
	 */
    #[Route(path: 'jx/admin/users', name: 'admin_users')]
    public function users(JSONRequestParser $parser, EntityManagerInterface $em): Response {
        $filter = $parser->get_array('filters');

        $fun_realize = function() use (&$em,$filter) {
            $qb = $em->getRepository(User::class)->createQueryBuilder('u');

            if (isset($filter['elevation']))
                $qb->andWhere('u.rightsElevation = :elevation')->setParameter('elevation', (int)$filter['elevation']);

            if (isset($filter['lang'])) {
                if ($filter['lang'] === 'null') $qb->andWhere('u.language IS NULL');
                else $qb->andWhere('u.language = :lang')->setParameter('lang', $filter['lang']);
            }

            if (isset($filter['provider'])) {
                if ($filter['provider'] === 'mh') $qb->andWhere('u.eternalID IS NULL');
                else $qb->andWhere('u.eternalID IS NOT NULL');
            }

            if (isset($filter['active']))
                $qb->andWhere('u.lastActionTimestamp >= :time')->setParameter('time', (new \DateTime())->modify('-' . (int)$filter['active'] . 'days'));

            if (isset($filter['game'])) {
                $qb->leftJoin(Citizen::class, 'c', Join::WITH, 'c.user = u.id AND c.active = 1');
                switch ($filter['game']) {
                    case '1': $qb->andWhere('c.id IS NOT NULL'); break;
                    case '0': $qb->andWhere('c.id IS NULL'); break;
                    case 'd': $qb->andWhere('c.id IS NOT NULL')->andWhere('c.alive = 0'); break;
                }
            }

            if (isset($filter['main_soul'])) {
                $qb->leftJoin(TwinoidImport::class, 't', Join::WITH, 't.user = u.id AND t.main = 1');
                if ($filter['main_soul'] === 'nomain') $qb->andWhere('t.id IS NULL');
                else $qb->andWhere('t.scope = :tscope')->setParameter('tscope', $filter['main_soul']);
            }

            if (isset($filter['any_soul'])) {
                switch ( $filter['any_soul'] ) {
                    case 'any':
                        $qb
                            ->leftJoin(TwinoidImport::class, 't2', Join::WITH, 't2.user = u.id')
                            ->andWhere('t2.id IS NOT NULL');
                        break;
                    case 'noany':
                        $qb
                            ->leftJoin(TwinoidImport::class, 't2', Join::WITH, 't2.user = u.id')
                            ->andWhere('t2.id IS NULL');
                        break;
                    default:
                        $qb
                            ->leftJoin(TwinoidImport::class, 't2', Join::WITH, 't2.user = u.id AND t2.scope = :t2scope')->setParameter('t2scope', $filter['any_soul'])
                            ->andWhere('t2.id IS NOT NULL');
                        break;
                }
            }

            if (isset($filter['restriction']))
                switch ( $filter['restriction'] ) {
                    case 'none':
                        $qb
                            ->leftJoin(AccountRestriction::class, 'r', Join::WITH, 'r.user = u.id')
                            ->andWhere('r.id IS NULL');
                        break;
                    case 'unconf':
                        $qb
                            ->leftJoin(AccountRestriction::class, 'r', Join::WITH, 'r.user = u.id AND r.confirmed = 0')
                            ->andWhere('r.id IS NOT NULL');
                        break;
                    case 'inactive':
                        $qb
                            ->leftJoin(AccountRestriction::class, 'r', Join::WITH, 'r.user = u.id')
                            ->leftJoin(AccountRestriction::class, 'r2', Join::WITH, 'r2.user = u.id AND r2.active = 1 AND r2.confirmed = 1 AND (r2.expires > :rdate OR r2.expires IS NULL)')->setParameter('rdate', new \DateTime())
                            ->andWhere('r.id IS NOT NULL')->andWhere('r2.id IS NULL');
                        break;
                    case 'active':
                        $qb
                            ->leftJoin(AccountRestriction::class, 'r', Join::WITH, 'r.user = u.id AND r.active = 1 AND r.confirmed = 1 AND (r.expires > :rdate OR r.expires IS NULL)')->setParameter('rdate', new \DateTime())
                            ->andWhere('r.id IS NOT NULL');
                        break;
                }

            if (isset($filter['accountstate']))
                switch ($filter['accountstate']) {
                    case 'active': $qb
                        ->andWhere( 'u.name NOT LIKE :name_as' )->setParameter('name_as', '$ deleted %')
                        ->andWhere( 'u.deleteAfter IS NULL' )->andWhere( 'u.email NOT LIKE :email_as' )->setParameter('email_as', '%@localhost')
                        ->andWhere( 'u.email LIKE :email_ass' )->setParameter('email_ass', '%@%');
                        break;
                    case 'dummy': $qb
                        ->andWhere( 'u.email LIKE :email_as' )->setParameter('email_as', '%@localhost');
                        break;
                    case 'special': $qb
                        ->andWhere( 'u.email NOT LIKE :email_as' )->setParameter('email_as', '%@%')->andWhere('u.name != u.email');
                        break;
                    case 'pre-del': $qb
                        ->andWhere( 'u.deleteAfter IS NOT NULL' )->andWhere( 'u.name NOT LIKE :name_as' )->setParameter('name_as', '$ deleted %');
                        break;
                    case 'del': $qb
                        ->andWhere( 'u.name LIKE :name_as' )->setParameter('name_as', '$ deleted %');
                        break;
                }

            return $qb;
        };



        try {
            $total_count = $fun_realize()->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            $total_count = 0;
        }

        $users = $total_count === 0 ? [] : $fun_realize()
            ->setMaxResults( $parser->get_int('limit', 50) )
            ->setFirstResult( $parser->get_int('page', 0) * $parser->get_int('limit', 50) )
            ->orderBy('u.id', 'ASC')->getQuery()->getResult();

        return $this->render( 'ajax/admin/users/all_index.html.twig', $this->addDefaultTwigArgs("full_list", [
            'users' => $users,
            'limit' => $parser->get_int('limit', 50),
            'current_page' => $parser->get_int('page', 0),
            'pages' => ceil($total_count / $parser->get_int('limit', 50)),
            'total' => $total_count,
            'filters' => $filter,
            'langFilter' => array_merge(array_map(function($lang) {
                return [$lang['code'], $this->translator->trans($lang['label'], [], 'global')];
            }, $this->allLangs), [['null', 'null']]),
        ]));
    }

    /**
     * @param AntiCheatService $as
     * @return Response
     */
    #[Route(path: 'jx/admin/users/multis', name: 'admin_users_multi')]
    public function users_multi(AntiCheatService $as): Response
    {
        $report = $as->createMultiAccountReport();
        return $this->render( 'ajax/admin/users/multi_index.html.twig', $this->addDefaultTwigArgs("multi_list", [
            'ma_report' => $report
        ]));
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/users/{id}/account/view', name: 'admin_users_account_view', requirements: ['id' => '\d+'])]
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
            'user_desc' => $desc ? $html->prepareEmotes($desc->getText(), $this->getUser()) : null,
            'validations' => $validations,
            'count_reset' => $this->entity_manager->getRepository(SoulResetMarker::class)->count(['user' => $user]),
            'ref' => $this->entity_manager->getRepository(UserReferLink::class)->findOneBy(['user' => $user]),
            'spon'          => $this->entity_manager->getRepository(UserSponsorship::class)->findOneBy(['user' => $user]),
            'spon_active'   => array_filter( $all_sponsored, fn(UserSponsorship $s) => !$this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') &&  $s->getUser()->getValidated() ),
            'spon_inactive' => array_filter( $all_sponsored, fn(UserSponsorship $s) =>  $this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') || !$s->getUser()->getValidated() ),
            'swap_pivots' => $this->entity_manager->getRepository(UserSwapPivot::class)->findBy(['principal' => $user])
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/users/tokens', name: 'admin_users_tokens')]
    public function users_tokens(): Response
    {
        $tokens = $this->entity_manager->getRepository(RegistrationToken::class)->findAll();
        return $this->render( 'ajax/admin/users/token_index.html.twig', $this->addDefaultTwigArgs("token_list", [
            'tokens' => $tokens
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/users/settings', name: 'admin_users_settings')]
    #[IsGranted('ROLE_ADMIN')]
    public function settings(): Response
    {
        return $this->render( 'ajax/admin/users/settings_index.html.twig', $this->addDefaultTwigArgs("settings", [
            's' => [
                'DisableAutomaticUserValidationMails' => $this->conf->serverSetting( ServerSetting::DisableAutomaticUserValidationMails )
            ]
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/users/perch', name: 'admin_users_crow_management')]
    #[IsGranted('ROLE_ADMIN')]
    public function crowManagementUI(): Response
    {
        return $this->render( 'ajax/admin/users/crow_management.html.twig', $this->addDefaultTwigArgs("perch", [
            'langs' => $this->generatedLangs
        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/admin/users/tokens/distribute', name: 'admin_users_tokens_distribute')]
    public function users_tokens_dist(): Response
    {
        $tokens = $this->entity_manager->getRepository(RegistrationToken::class)->findAll();
        return $this->render( 'ajax/admin/users/token_distribute.html.twig', $this->addDefaultTwigArgs("token_dist", [

        ]));
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/admin/users/generateTokens', name: 'admin_users_generatetokens')]
    public function users_generate_tokens(JSONRequestParser $parser):Response {
        if (!$parser->has("count")) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $nb = intval($parser->get("count"));
        if ($nb <= 0)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        for ($i = 0 ; $i < $nb; $i++) {
            $token = new RegistrationToken();
            $token->setToken(bin2hex(random_bytes(20)));
            $this->entity_manager->persist($token);
        }

        $this->entity_manager->flush();
        $this->addFlash('notice', $this->translator->trans("Tokens erfolgreich erzeugt!", [], 'admin'));
        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/admin/users/distributeTokens', name: 'admin_users_distributetokens')]
    public function users_distribute_tokens(JSONRequestParser $parser):Response {
        if (!$parser->has_all(['message','csv']))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $message = $parser->trimmed('message');

        $lines = array_filter( array_map( fn(string $s) => explode(';',trim($s)), explode( "\n", $parser->get('csv') ) ), fn(array $a) => !empty($a));

        $required_length = 2;

        while ( str_contains( $message, '%%' . ($required_length-1) . '%%' ) )
            $required_length++;

        $missmatch = [];
        $debug = [];

        $success = 0;

        foreach ($lines as $line) {

            if (count($line) < $required_length) {
                $missmatch[] = implode(';', $line);
                continue;
            }
            $username = explode( ':', $line[0] );

            if (count($username) < 2)
                $potential_user = $this->entity_manager->getRepository(User::class)->findOneByNameOrDisplayName( $username[0] );
            else $potential_user = $this->entity_manager->getRepository(User::class)->find( (int)$username[1] );

            if (!$potential_user) {
                $missmatch[] = implode(';', $line);
                continue;
            }

            $this_message = $message;
            foreach ($line as $k => $entry) {
                if ($k === 0) $this_message = str_replace( '%%username%%', $potential_user->getName(), $this_message );
                elseif ($k === 1) $this_message = str_replace( '%%token%%', $entry, $this_message );
                else $this_message = str_replace( '%%' . ($k-1) . '%%', $entry, $this_message );
            }

            $debug[] = $this_message;
            $this->entity_manager->persist($this->crow_service->createPM($potential_user, nl2br($this_message)));
            $success++;
        }

        $this->entity_manager->flush();

        return AjaxResponse::success(true, [
            'info' => [
                'success' => $success,
                'error' => count($missmatch),
                'missmatch' => $missmatch,
                'messages' => $debug
            ]

        ]);
    }

    /**
     * @param int $id
     * @param JSONRequestParser $parser
     * @param UserHandler $userHandler
     * @param UserPasswordHasherInterface $passwordEncoder
     * @param string $param
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/account/do/overwrite_pw/{param}', name: 'admin_users_account_manage_pw', requirements: ['id' => '\d+'], priority: 1)]
    #[AdminLogProfile(enabled: true, mask: ['param', '$.param'])]
    public function user_account_manager_pw(int $id, JSONRequestParser $parser, UserHandler $userHandler,
                                         UserPasswordHasherInterface $passwordEncoder, string $param = ''): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (empty($param)) $param = $parser->get('param', '');

        if (!$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (!$userHandler->admin_canAdminister( $this->getUser(), $user )) return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $user->setPassword( $passwordEncoder->hashPassword( $user, $param ) );
        $this->entity_manager->persist($user);

        try {
            $this->entity_manager->flush();
        } catch (Exception) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param string $action
     * @param JSONRequestParser $parser
     * @param UserFactory $uf
     * @param TwinoidHandler $twin
     * @param UserHandler $userHandler
     * @param PermissionHandler $perm
     * @param CrowService $crow
     * @param KernelInterface $kernel
     * @param InvalidateTagsInAllPoolsAction $clearCache
     * @param string $param
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/account/do/{action}/{param}', name: 'admin_users_account_manage', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function user_account_manager(int $id, string $action, JSONRequestParser $parser, UserFactory $uf,
                                         TwinoidHandler $twin, UserHandler $userHandler, PermissionHandler $perm,
                                         CrowService $crow, KernelInterface $kernel, InvalidateTagsInAllPoolsAction $clearCache,
                                         EventProxyService $proxy,
                                         string $param = ''): Response
    {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (empty($param)) $param = $parser->get('param', '');

        if (in_array($action, [
                'delete_token', 'invalidate', 'validate', 'twin_full_reset', 'twin_main_reset', 'twin_main_full_import', 'delete', 'rename',
                'shadow', 'whitelist', 'unwhitelist', 'link', 'unlink', 'etwin_reset', 'initiate_pw_reset', 'name_manual', 'name_auto', 'herodays',
                'team', 'enforce_pw_reset', 'change_mail', 'ref_rename', 'ref_disable', 'ref_enable', 'set_sponsor', 'mh_unreset', 'forget_name_history',
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
                $uf->announceValidationToken( $pf, true );
                $this->entity_manager->persist( $pf );
            }
            break;

            case 'initiate_pw_reset':case 'enforce_pw_reset':
            if ($action === 'enforce_pw_reset')
                $user->setPassword(null);
            $uf->announceValidationToken( $uf->ensureValidation( $user, UserPendingValidation::ResetValidation ) );
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
                    $this->entity_manager->flush();

                    $proxy->pictosPersisted( $user, imported: true );
                    $this->entity_manager->persist($user);
                    $this->entity_manager->flush();
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
                    $this->entity_manager->flush();

                    $proxy->pictosPersisted( $user, imported: true );
                    $this->entity_manager->persist($user);
                    $this->entity_manager->flush();
                }
                break;

            case 'etwin_reset':

                if (empty($param) || $user->getEternalID() === null)
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user
                    ->setNoAutomaticNameManagement(false)
                    ->setEternalID( null )
                    ->setEmail( $param );
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

            case 'name_manual':
                if (!$user->getEternalID())
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setNoAutomaticNameManagement( true );
                $this->entity_manager->persist($user);
                break;

            case 'name_auto':
                $user->setNoAutomaticNameManagement( false );
                $this->entity_manager->persist($user);
                break;

            case 'forget_name_history':
                $user->setNameHistory([]);
                $this->entity_manager->persist($user);
                break;

            case 'delete':
                $userHandler->deleteUser($user);
                $this->entity_manager->persist($user);
                break;

            case 'team':
                if (!in_array( $param, $this->generatedLangsCodes )) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setTeam( $param );
                $this->entity_manager->persist($user);
                break;

            case 'shadow':
                if (empty($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if (!is_array($param)) $param = ['reason' => $param, 'ids' => [$user->getId()]];
                elseif (!isset($param['reason'])) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                elseif (!isset($param['ids'])) $param['ids'] = [$user->getId()];

                if (!in_array( $user->getId(), $param['ids'] )) $param['ids'][] = $user->getId();
                ['reason' => $reason, 'ids' => $ids] = $param;

                foreach ($ids as $other_user_id) {
                    /** @var User $other_user */
                    $other_user = $this->entity_manager->getRepository(User::class)->find($other_user_id);
                    if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                    $this->entity_manager->persist(
                        (new AccountRestriction())
                            ->setUser( $other_user )
                            ->setActive(true)
                            ->setConfirmed(true)
                            ->setPublicReason($reason)
                            ->setOriginalDuration(-1)
                            ->setExpires(null)
                            ->setModerator($this->getUser())
                            ->addConfirmedBy($this->getUser())
                            ->setCreated(new \DateTime())
                            ->setRestriction(AccountRestriction::RestrictionGameplay)
                    );

                    $n = $crow->createPM_moderation( $other_user, CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionImpose, -1, $reason );
                    if ($n) $this->entity_manager->persist($n);
                }

                break;

            case 'mh_unreset':

                foreach ($this->entity_manager->getRepository(SoulResetMarker::class)->findBy(['user' => $user]) as $marker) {
                    $marker->getRanking()->removeDisableFlag(CitizenRankingProxy::DISABLE_ALL);
                    foreach ($this->entity_manager->getRepository(Picto::class)->findBy(['townEntry' => $marker->getRanking()->getTown(), 'user' => $user]) as $picto)
                        $this->entity_manager->persist( $picto->setDisabled(false) );
                    $this->entity_manager->persist($marker->getRanking());
                    $marker->getRanking()->setResetMarker(null);
                    $this->entity_manager->remove($marker);
                }

                $this->entity_manager->flush();
                $this->entity_manager->persist($user->setSoulPoints( $this->user_handler->fetchSoulPoints( $user, false ) ));

                break;

            case 'whitelist':

                if (!is_array($param)) $param = ['reason' => null, 'ids' => [$param]];
                elseif (!isset($param['ids'])) $param = ['reason' => null, 'ids' => $param];
                ['reason' => $reason, 'ids' => $ids] = $param;

                $user->getConnectionWhitelists()->add( ($wl = new ConnectionWhitelist())->addUser($user)->setReason( $reason )->setCreator( $this->getUser() ) );

                foreach ($ids as $other_user_id) {
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

            case 'link':
                $id = (int)$param;
                if ($id === $user->getId()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $other_user = $this->entity_manager->getRepository(User::class)->find($id);
                if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $existing_pivot = $this->entity_manager->getRepository(UserSwapPivot::class)->findOneBy(['principal' => $user, 'secondary' => $other_user]);
                if ($existing_pivot) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $this->entity_manager->persist( (new UserSwapPivot())
                    ->setPrincipal( $user )->setSecondary( $other_user )
                );
                break;

            case 'unlink':
                $id = (int)$param;
                $other_user = $this->entity_manager->getRepository(User::class)->find($id);
                if (!$other_user) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $existing_pivot = $this->entity_manager->getRepository(UserSwapPivot::class)->findOneBy(['principal' => $user, 'secondary' => $other_user]);
                if (!$existing_pivot) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                $this->entity_manager->remove( $existing_pivot );
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

                $this->entity_manager->persist( (new UserSponsorship)->setUser($user)->setSponsor($other_user)->setCountedSoulPoints(0)->setCountedHeroExp(0)->setTimestamp(new \DateTime()) );

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
                    $clearCache("user_avatar_{$user->getId()}");
                }
                break;

            case 'grant':
                switch ($param) {
                    case 'NONE':
                        $user->setRightsElevation( User::USER_LEVEL_BASIC );

                        $perm->disassociate( $user, $perm->getDefaultGroup(UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAnimactorGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                        break;

                    case 'ROLE_CROW':
                        $user->setRightsElevation( max($user->getRightsElevation(), User::USER_LEVEL_CROW) );
                        $user->removeRoleFlag( User::USER_ROLE_ORACLE | User::USER_ROLE_ANIMAC );

                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAnimactorGroup ) );
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                        break;
                    case 'ROLE_ADMIN': case 'ROLE_SUPER':
                    $user->setRightsElevation( max($user->getRightsElevation(), $param === 'ROLE_ADMIN' ? User::USER_LEVEL_ADMIN : User::USER_LEVEL_SUPER) );

                    $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                    $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                    $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAnimactorGroup ) );
                    $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultModeratorGroup ) );
                    $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAdminGroup ) );
                    break;

                    case 'FLAG_RUFFIAN':
                        $user->addRoleFlag( User::USER_ROLE_LIMIT_MODERATION );
                        break;

                    case '!FLAG_RUFFIAN':
                        $user->removeRoleFlag( User::USER_ROLE_LIMIT_MODERATION );
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
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        break;

                    case '!FLAG_ORACLE':
                        $user->removeRoleFlag( User::USER_ROLE_ORACLE );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultOracleGroup));
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultElevatedGroup ) );
                        break;

                    case 'FLAG_ANIMAC':
                        if ( $user->getRightsElevation() === User::USER_LEVEL_CROW )
                            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                        else $user->addRoleFlag( User::USER_ROLE_ANIMAC );

                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAnimactorGroup));
                        break;

                    case '!FLAG_ANIMAC':
                        $user->removeRoleFlag( User::USER_ROLE_ANIMAC );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultAnimactorGroup));
                        break;

                    case 'FLAG_DEV':
                        $user->addRoleFlag( User::USER_ROLE_DEV );
                        $perm->associate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultDevGroup));
                        break;

                    case '!FLAG_DEV':
                        $user->removeRoleFlag( User::USER_ROLE_DEV );
                        $perm->disassociate( $user, $perm->getDefaultGroup( UserGroup::GroupTypeDefaultDevGroup));
                        break;

                    default: breaK;
                }
                $this->entity_manager->persist($user);
                break;

            case 'herodays':
                if (!is_numeric($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setBonusHeroDaysSpent( (int)$param );
                $this->entity_manager->persist($user);
                break;
            case "dbg_soulpoints":
                if (empty($param) || !is_numeric($param)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                $user->setSoulPoints( max(0,$param) );
                $this->entity_manager->persist($user);
                break;
            case "dbg_confirm_deaths":
                while ( $this->user_handler->confirmNextDeath( $user, '' ) ) {}
                $this->entity_manager->persist($user);
                break;
            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/users/{id}/ban/view', name: 'admin_users_ban_view', requirements: ['id' => '\d+'])]
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

    private function check_ban_confirmation(AccountRestriction $a, bool $notify = true): bool {

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

        if ($notify) {
            if ($a->getRestriction() === AccountRestriction::RestrictionGameplay)
                $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionImpose, $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(), $a->getPublicReason() ));
            elseif ($a->getRestriction() === AccountRestriction::RestrictionForum)
                $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetForumBan, CrowService::ModerationActionImpose, $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(), $a->getPublicReason() ));
            else
                $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetAnyBan, CrowService::ModerationActionImpose, [
                    'mask' => $a->getRestriction(),
                    'duration' => $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(),
                    'old_duration' => 0,
                ], $a->getPublicReason() ));
        }


        return true;
    }

    /**
     * @param int $uid
     * @param int $bid
     * @return Response
     */
    #[Route(path: 'api/admin/users/{uid}/ban/{bid}/confirm', name: 'admin_users_ban_confirm', requirements: ['uid' => '\d+', 'bid' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
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
     * @param int $uid
     * @param int $bid
     * @return Response
     */
    #[Route(path: 'api/admin/users/{uid}/ban/{bid}/disable', name: 'admin_users_ban_disable', requirements: ['uid' => '\d+', 'bid' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
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

        if ($a->getRestriction() === AccountRestriction::RestrictionGameplay)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionRevoke, 0, '' ));
        elseif ($a->getRestriction() === AccountRestriction::RestrictionForum)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetForumBan, CrowService::ModerationActionRevoke, 0, '' ));
        else
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetAnyBan, CrowService::ModerationActionRevoke, [
                'mask' => $a->getRestriction(),
                'duration' => 0,
                'old_duration' => $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(),
            ], $a->getPublicReason() ));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $uid
     * @param int $bid
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/users/{uid}/ban/{bid}/modify', name: 'admin_users_ban_modify', requirements: ['uid' => '\d+', 'bid' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function users_modify_ban(int $uid, int $bid, JSONRequestParser $parser): Response
    {
        $a = $this->entity_manager->getRepository(AccountRestriction::class)->find($bid);
        if ($a === null || $a->getUser()->getId() !== $uid) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$parser->has('duration', true)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (
            !$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' ) && (
                $a->getModerator() !== $this->getUser() ||
                $a->getConfirmedBy()->filter( fn(User $u) => $u !== $this->getUser() )->count() > 0
            )
        ) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$a->getActive() || $a->getExpires() && $a->getExpires() < new \DateTime()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $is_super_confirmed = false;
        foreach ($a->getConfirmedBy() as $confirmer)
            if ($this->user_handler->hasRole($confirmer, 'ROLE_SUPER')) $is_super_confirmed = true;

        if ($is_super_confirmed && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_SUPER' )) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $old_duration = $a->getOriginalDuration();
        $a->setOriginalDuration( $parser->get_int('duration') );

        if ($old_duration >= 0) $a->setExpires( $a->getOriginalDuration() < 0 ? null : (new \DateTime())->setTimestamp( $a->getExpires()->getTimestamp() - $old_duration + $a->getOriginalDuration() ) );
        else $a->setExpires( (new \DateTime())->setTimestamp( $a->getCreated()->getTimestamp() + $a->getOriginalDuration() ) );
        $this->entity_manager->persist($a);

        if ($a->getRestriction() === AccountRestriction::RestrictionGameplay)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetGameBan, CrowService::ModerationActionEdit, 0, '' ));
        elseif ($a->getRestriction() === AccountRestriction::RestrictionForum)
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetForumBan, CrowService::ModerationActionEdit, 0, '' ));
        else
            $this->entity_manager->persist($this->crow_service->createPM_moderation( $a->getUser(), CrowService::ModerationActionDomainAccount, CrowService::ModerationActionTargetAnyBan, CrowService::ModerationActionEdit, [
                'mask' => $a->getRestriction(),
                'duration' => $a->getOriginalDuration() < 0 ? null : $a->getOriginalDuration(),
                'old_duration' => $old_duration,
            ], $a->getPublicReason() ));

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/ban', name: 'admin_users_ban', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function users_ban(int $id, JSONRequestParser $parser): Response
    {
        if (!$parser->has_all(['reason','duration','restriction'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $edit_id = $parser->get_int('existing', 0);
        if ($edit_id && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $edit = $edit_id ? $this->entity_manager->getRepository( AccountRestriction::class )->find( $edit_id ) : null;
        if ($edit) {
            if ($this->user_handler->hasRole( $edit->getModerator(), 'ROLE_SUPER' ) && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_SUPER' ))
                return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
            $edit->getConfirmedBy()->clear();
        }

        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if (!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($this->user_handler->hasRole( $user, 'ROLE_CROW' ) && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_ADMIN' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
        if ($this->user_handler->hasRole( $user, 'ROLE_ADMIN' ) && !$this->user_handler->hasRole( $this->getUser(), 'ROLE_SUPER' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $reason    = trim($parser->get('reason'));
        $note      = trim($parser->get('note'));
        $duration  = $parser->get_int('duration');
        $mask      = $parser->get_int('restriction', 0);

        if ($duration === 0 || $mask <= 0 || ($mask & ~(AccountRestriction::RestrictionSocial | AccountRestriction::RestrictionGameplay | AccountRestriction::RestrictionProfile | AccountRestriction::RestrictionGameplayLang | AccountRestriction::RestrictionReportToGitlab )))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $a = ($edit ?? ((new AccountRestriction())->setCreated( new \DateTime() )))
            ->setUser($user)
            ->setRestriction( $mask )
            ->setOriginalDuration( $duration )
            ->setActive( true )
            ->setConfirmed( false )
            ->setModerator( $this->getUser() )
            ->setPublicReason( $reason )
            ->setInternalReason( $note )
            ->addConfirmedBy( $this->getUser() );
        $this->check_ban_confirmation($a, !$edit);
        $this->entity_manager->persist($a);

        try {
            $this->entity_manager->flush();
        } catch (\Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/ban/lift', name: 'admin_users_ban_lift', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function users_ban_lift(int $id): Response
    {                
        if ($this->adminHandler->liftAllBans($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/admin/users/fuzzyfind', name: 'admin_users_fuzzyfind')]
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
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/users/{id}/citizen/view', name: 'admin_users_citizen_view', requirements: ['id' => '\d+'])]
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
     * @param User $user
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/citizen/delete', name: 'admin_users_citizen_remove_aspect', requirements: ['id' => '\d+'])]
    public function users_citizen_remove_aspect(User $user, JSONRequestParser $parser): Response {
        $citizen = $user->getActiveCitizen();

        if (!$citizen) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        switch ($parser->get('subject')) {
            case 'comment':
                $citizen->setComment('')->getRankingEntry()->setComment('');
                $this->entity_manager->persist($citizen);
                $this->entity_manager->persist($citizen->getRankingEntry());
                break;
            case 'lastWords':
                $citizen->setLastWords('')->getRankingEntry()->setLastWords('');
                $this->entity_manager->persist($citizen);
                $this->entity_manager->persist($citizen->getRankingEntry());
                break;
            case 'status':
                $citizen->getHome()->setDescription(null);
                $this->entity_manager->persist($citizen->getHome());
                break;
            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        try {
            $this->entity_manager->flush();
        } catch (\Throwable) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/users/{id}/social/view', name: 'admin_users_social_view', requirements: ['id' => '\d+'])]
    public function user_social_view(int $id): Response {
        $user = $this->entity_manager->getRepository(User::class)->find($id);

        return $this->render("ajax/admin/users/social.html.twig", $this->addDefaultTwigArgs("admin_users_social", [
            "user" => $user,
            "blocked" => $this->entity_manager->getRepository( SocialRelation::class )->findBy( ['owner' => $user, 'type' => SocialRelation::SocialRelationTypeBlock ] ),
            "blockers" => $this->entity_manager->getRepository( SocialRelation::class )->findBy( ['related' => $user, 'type' => SocialRelation::SocialRelationTypeBlock ] ),
            "sponsored" => array_filter(
                $this->entity_manager->getRepository(UserSponsorship::class)->findBy(['sponsor' => $user]),
                fn(UserSponsorship $s) => !$this->user_handler->hasRole($s->getUser(), 'ROLE_DUMMY') && $s->getUser()->getValidated()
            ),
            "friends" => $user->getFriends(),
            'reverse_friends' => $this->entity_manager->getRepository(User::class)->findInverseFriends($user, true),
        ]));
    }
    /**
     * @param int $id
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/citizen/headshot', name: 'admin_users_citizen_headshot', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function users_citizen_headshot(int $id, AdminHandler $admh): Response
    {
        if ($admh->headshot($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @param int $id
     * @param AdminHandler $admh
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/citizen/eat_liver', name: 'admin_users_citizen_eat_liver', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function users_citizen_eat_liver(int $id, AdminHandler $admh): Response
    {
        if ($admh->eatLiver($this->getUser()->getId(), $id))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @param int $id
     * @param int $cid
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/citizen/engagement/{cid}', name: 'admin_users_citizen_engage', requirements: ['id' => '\d+', 'cid' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
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
     * @param int $id
     * @return Response
     */
    #[Route(path: 'jx/admin/users/{id}/pictos/view', name: 'admin_users_pictos_view', requirements: ['id' => '\d+'])]
    public function users_pictos_view(int $id): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);

        $protos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($protos, function($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $f_protos = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findAll();
        $features = [];
        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);
        foreach ($f_protos as $p)
            if ($ff = $this->entity_manager->getRepository(FeatureUnlock::class)->findOneActiveForUser($user,$season,$p))
                $features[] = $ff;

        return $this->render( 'ajax/admin/users/pictos.html.twig', $this->addDefaultTwigArgs("admin_users_pictos", [
            'user' => $user,
            'pictoPrototypes' => $this->isGranted("ROLE_ADMIN", $user) ? $protos : array_filter($protos, fn(PictoPrototype $p) => $p->getCommunity()),
            'features' => $features,
            'featurePrototypes' => $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findAll(),
            'icon_max_size' => $this->conf->getGlobalConf()->get(MyHordesConf::CONF_AVATAR_SIZE_UPLOAD, 3145728)
        ]));
    }

    /**
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @param KernelInterface $kernel
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/picto/give', name: 'admin_user_give_picto', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function user_give_picto(int $id, JSONRequestParser $parser, KernelInterface $kernel, EventProxyService $proxy): Response
    {
        $user = $this->entity_manager->getRepository(User::class)->find($id);
        if(!$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number', 1);

        if ($prototype_id === -42 && $kernel->getEnvironment() === 'dev' && $this->isGranted('ROLE_ADMIN', $user))
            $prototypes = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        else {
            /** @var PictoPrototype $prototype */
            $prototype = $this->entity_manager->getRepository(PictoPrototype::class)->find($prototype_id);
            if ($prototype === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            if (!$prototype->getCommunity() && !$this->isGranted('ROLE_ADMIN', $user))
                return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

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

        $proxy->pictosPersisted( $user, $this->entity_manager->getRepository(Season::class)->findOneBy(['number' => 0, 'subNumber' => 15]) );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CrowService $crow
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/unique_award/manage', name: 'admin_user_manage_unique_award', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function user_manage_unique_award(int $id, JSONRequestParser $parser, CrowService $crow): Response {
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

            $image = ImageService::createImageFromData( $payload );
            ImageService::resize( $image, 16, 16, bestFit: true );
            $payload = ImageService::save( $image );

            $this->entity_manager->persist( $award
                ->setUser($user)
                ->setCustomIcon($payload)
                ->setCustomIconName(md5($payload))
                ->setCustomIconFormat(strtolower( $image->format ))
            );
        }

        try {
            $this->entity_manager->flush();
            if (!$parser->has('id', true)) {
                $this->entity_manager->persist($crow->createPM_titleUnlock($user, [$award]));
                $this->entity_manager->flush();
            }
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id User ID
     * @param int $cid
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    #[Route(path: 'api/admin/users/{id}/comments/{cid}', name: 'admin_user_edit_comment', requirements: ['id' => '\d+', 'cid' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
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
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     * @throws Exception
     */
    #[Route(path: 'api/admin/users/{id}/feature/give', name: 'admin_user_give_feature', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
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
                    ->setSeason( $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]) );
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
