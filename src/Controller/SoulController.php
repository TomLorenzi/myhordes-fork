<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace App\Controller;

use App\Entity\Avatar;
use App\Entity\Award;
use App\Entity\AwardPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\CitizenRankingProxy;
use App\Entity\FoundRolePlayText;
use App\Entity\HeroSkillPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\TwinoidImportPreview;
use App\Entity\User;
use App\Entity\RolePlayTextPage;
use App\Entity\Season;
use App\Entity\UserGroup;
use App\Entity\UserGroupAssociation;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\PermissionHandler;
use App\Service\TwinoidHandler;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Service\AdminActionHandler;
use App\Service\TimeKeeperService;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class SoulController extends AbstractController
{
    const ErrorUserEditPasswordIncorrect     = ErrorHelper::BaseSoulErrors + 1;
    const ErrorTwinImportInvalidResponse     = ErrorHelper::BaseSoulErrors + 2;
    const ErrorTwinImportNoToken             = ErrorHelper::BaseSoulErrors + 3;
    const ErrorTwinImportProfileMismatch     = ErrorHelper::BaseSoulErrors + 4;
    const ErrorTwinImportProfileInUse        = ErrorHelper::BaseSoulErrors + 4;

    const ErrorCoalitionAlreadyMember        = ErrorHelper::BaseSoulErrors + 10;
    const ErrorCoalitionNotSet               = ErrorHelper::BaseSoulErrors + 11;

    protected $entity_manager;
    protected $translator;
    protected $user_factory;
    protected $time_keeper;
    private $user_handler;
    private $asset;

    public function __construct(EntityManagerInterface $em, UserFactory $uf, Packages $a, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator)
    {
        $this->entity_manager = $em;
        $this->user_factory = $uf;
        $this->asset = $a;
        $this->translator = $translator;
        $this->user_handler = $uh;
        $this->time_keeper = $tk;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
        /** @var User $user */
        $user = $this->getUser();

        $data = $data ?? [];

        $data["soul_tab"] = $section;

        $data['clock'] = [
            'desc'      => $user->getActiveCitizen() !== null ? $user->getActiveCitizen()->getTown()->getName() : $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => $user->getActiveCitizen() !== null ? $user->getActiveCitizen()->getTown()->getDay() : "",
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => $user->getActiveCitizen() !== null ? $user->getActiveCitizen()->getTown()->getType()->getName() : "",
        ];

        return $data;
    }

    /**
     * @Route("jx/soul/disabled_message", name="soul_disabled")
     * @return Response
     */
    public function soul_disabled(): Response
    {
        $user = $this->getUser();
        if (!$user->getShadowBan())
            return $this->redirect($this->generateUrl( 'soul_me' ));
        return $this->render( 'ajax/soul/acc_disabled.html.twig', ['ban' => $user->getShadowBan()]);
    }

    /**
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function soul_me(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        // Get all the picto & count points
        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    	$points = $this->user_handler->getPoints($user);
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;

        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/me.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'pictos' => $pictos,
            'points' => round($points, 0),
            'latestSkill' => $latestSkill,
            'progress' => floor($progress),
            'seasons' => $this->entity_manager->getRepository(Season::class)->findAll()
        ]));
    }

    /**
     * @Route("jx/soul/fuzzyfind", name="users_fuzzyfind")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function users_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if ($this->getUser()->getShadowBan()) return $this->render( 'ajax/soul/users_list.html.twig', [ 'users' => [] ]);

        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $searchName = $parser->get('name');
        $users = mb_strlen($searchName) >= 3 ? $em->getRepository(User::class)->findByNameContains($searchName) : [];

        return $this->render( 'ajax/soul/users_list.html.twig', [ 'users' => $users ]);
    }


    /**
     * @Route("jx/soul/heroskill", name="soul_heroskill")
     * @return Response
     */
    public function soul_heroskill(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        // Get all the picto & count points
        $latestSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getLatestUnlocked($user->getAllHeroDaysSpent());
        $nextSkill = $this->entity_manager->getRepository(HeroSkillPrototype::class)->getNextUnlockable($user->getAllHeroDaysSpent());

        $allSkills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->findAll();

        $factor1 = $latestSkill !== null ? $latestSkill->getDaysNeeded() : 0;
        $progress = $nextSkill !== null ? ($user->getAllHeroDaysSpent() - $factor1) / ($nextSkill->getDaysNeeded() - $factor1) * 100.0 : 0;

        return $this->render( 'ajax/soul/heroskills.html.twig', $this->addDefaultTwigArgs("soul_me", [
            'latestSkill' => $latestSkill,
            'nextSkill' => $nextSkill,
            'progress' => floor($progress),
            'skills' => $allSkills
        ]));
    }

    /**
     * @Route("jx/soul/news/{id}", name="soul_news")
     * @param Request $request
     * @return Response
     */
    public function soul_news(Request $request, UserHandler $userHandler, int $id = 0): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $news = $this->entity_manager->getRepository(Changelog::class)->findByLang($lang);

        $selected = $id > 0 ? $this->entity_manager->getRepository(Changelog::class)->find($id) : null;
        if ($selected === null)
            $selected = $news[0] ?? null;

        try {
            $userHandler->setSeenLatestChangelog( $user, $lang );
            $this->entity_manager->flush();
        } catch (Exception $e) {}

        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", [
            'news' => $news, 'selected' => $selected
        ]) );
    }

    /**
     * @Route("jx/soul/settings", name="soul_settings")
     * @return Response
     */
    public function soul_settings(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/soul/settings.html.twig', $this->addDefaultTwigArgs("soul_settings", null) );
    }

    /**
     * @Route("jx/soul/import/{code}", name="soul_import")
     * @param TwinoidHandler $twin
     * @param string $code
     * @return Response
     */
    public function soul_import(TwinoidHandler $twin, string $code = ''): Response
    {
        if ($this->getUser()->getShadowBan()) return $this->redirect($this->generateUrl( 'soul_disabled' ));

        /** @var User $user */
        $user = $this->getUser();
        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        if ($cache = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user])) {

            return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'payload' => $cache->getData($this->entity_manager), 'preview' => true,
                'main_soul' => $main !== null && $main->getScope() === $cache->getScope(), 'select_main_soul' => $main === null,
            ]) );

        } else return $this->render( 'ajax/soul/import.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'services' => ['www.hordes.fr' => 'Hordes','www.die2nite.com' => 'Die2Nite','www.dieverdammten.de' => 'Die Verdammten','www.zombinoia.com' => 'Zombinoia'],
            'code' => $code, 'need_sk' => !$twin->hasBuiltInTwinoidAccess(),
            'souls' => $this->entity_manager->getRepository(TwinoidImport::class)->findBy(['user' => $user], ['created' => 'DESC']),
            'select_main_soul' => $main === null
        ]) );
    }

    /**
     * @Route("jx/soul/import/view/{id}", name="soul_import_viewer")
     * @param int $id
     * @return Response
     */
    public function soul_import_viewer(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return $this->redirect($this->generateUrl( 'soul_disabled' ));

        $import = $this->entity_manager->getRepository(TwinoidImport::class)->find( $id );
        if (!$import || $import->getUser() !== $user) return $this->redirect($this->generateUrl('soul_import'));

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);

        return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'payload' => $import->getData($this->entity_manager), 'preview' => false,
            'main_soul' => $main !== null && $main->getScope() === $import->getScope(), 'select_main_soul' => $main === null,
        ]) );
    }

    private function validate_twin_json_request(JSONRequestParser $json, TwinoidHandler $twin, ?string &$sc = null, ?string &$sk = null, ?int &$app = null): bool {
        $sc = $json->get('scope', null);
        if (!in_array($sc, ['www.hordes.fr','www.die2nite.com','www.dieverdammten.de','www.zombinoia.com']))
            return false;

        $sk    = $json->get('sk');
        $app   = (int)$json->get('app');

        if (!$twin->hasBuiltInTwinoidAccess()) {
            if ($app <= 0 || empty($sk))
                return false;
            $twin->setFallbackAccess($app,$sk);
        }

        return true;
    }

    /**
     * @Route("api/soul/import_turl", name="soul_import_turl_api")
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @return Response
     */
    public function soul_import_twinoid_endpoint(JSONRequestParser $json, TwinoidHandler $twin): Response
    {
        if (!$this->validate_twin_json_request( $json, $twin, $scope ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        return AjaxResponse::success(true, ['goto' => $twin->getTwinoidAuthURL('import',$scope)]);
    }

    /**
     * @Route("api/soul/import/{code}", name="soul_import_api")
     * @param string $code
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @return Response
     */
    public function soul_import_loader(string $code, JSONRequestParser $json, TwinoidHandler $twin): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->validate_twin_json_request( $json, $twin, $scope ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $twin->setCode( $code );

        $data1 = $twin->getData("$scope/tid",'me', [
            'name','twinId',
            'playedMaps' => [ 'mapId','survival','mapName','season','v1','score','dtype','msg','comment','cleanup' ]
        ], $error);

        if ($error || isset($data1['error'])) return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data1]);

        $twin_id = (int)($data1['twinId'] ?? 0);
        if (!$twin_id) return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data1]);

        $data2 = $twin->getData('twinoid.com',"site?host={$scope}", [
            'me' => [ 'points','npoints',
                'stats' => [ 'id','score','name','rare','social' ],
                'achievements' => [ 'id','name','stat','score','points','npoints','date','index',
                    'data' => ['type','title','url','prefix','suffix']
                ]
            ]
        ], $error);

        if ($error || isset($data2['error'])) return AjaxResponse::error(self::ErrorTwinImportInvalidResponse, ['response' => $data2]);

        if ($user->getTwinoidID() === null) {

            if (
                $this->entity_manager->getRepository(User::class)->findOneBy(['twinoidID' => $twin_id]) ||
                $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['twinoidID' => $twin_id])
            ) return AjaxResponse::error(self::ErrorTwinImportProfileInUse);

        } elseif ($user->getTwinoidID() !== $twin_id)
            return AjaxResponse::error(self::ErrorTwinImportProfileMismatch);

        $user->setTwinoidImportPreview( (new TwinoidImportPreview())
            ->setTwinoidID($twin_id)
            ->setCreated(new DateTime())
            ->setScope($scope)
            ->setPayload(array_merge($data1,$data2['me'])) );

        try {
            $this->entity_manager->persist($user);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/import-cancel", name="soul_import_cancel_api")
     * @param JSONRequestParser $json
     * @return Response
     */
    public function soul_import_cancel(JSONRequestParser $json): Response
    {
        $user = $this->getUser();

        $pending = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]);
        if (!$pending) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $user->setTwinoidImportPreview(null);
        $pending->setUser(null);

        try {
            $this->entity_manager->remove($pending);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/import-confirm/{id}", name="soul_import_confirm_api")
     * @param JSONRequestParser $json
     * @param TwinoidHandler $twin
     * @param int $id
     * @return Response
     */
    public function soul_import_confirm(JSONRequestParser $json, TwinoidHandler $twin, int $id = -1): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $to_main = (bool)$json->get('main', false);
        $pending = null; $selected = null;

        if ($id < 0) {
            $pending = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user]);
            if (!$pending) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $scope = $pending->getScope();
            $data = $pending->getData($this->entity_manager);
        } else {
            $selected = $this->entity_manager->getRepository(TwinoidImport::class)->find($id);
            if (!$selected || $selected->getUser() !== $user) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $scope = $selected->getScope();
            $data = $selected->getData($this->entity_manager);
        }

        $main = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'main' => true]);
        if ($main !== null) {
            if ($main->getScope() !== $scope && $to_main)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            elseif ($main->getScope() === $scope) $to_main = true;
        }

        if ($twin->importData( $user, $scope, $data, $to_main )) {

            if ($id < 0) {
                $import_ds = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'scope' => $scope]);
                if ($import_ds === null) $user->addTwinoidImport( $import_ds = new TwinoidImport() );

                $import_ds->fromPreview( $pending );
                $import_ds->setMain( $to_main );

                $user->setTwinoidID( $pending->getTwinoidID() );
                $user->setTwinoidImportPreview(null);
                $pending->setUser(null);

                $this->entity_manager->remove($pending);
            } else $selected->setMain($to_main);

            $this->entity_manager->persist( $user );

            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()]);
            }

            return AjaxResponse::success();
        } else return AjaxResponse::error(ErrorHelper::ErrorInternalError);
    }

    /**
     * @Route("jx/soul/coalitions", name="soul_coalitions")
     * @return Response
     */
    public function soul_coalitions(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $user,
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
            ]);

        $all_users = $user_coalition ? $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation()
            ]) : [];

        $user_invitations = $user_coalition ? null : $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        );

        return $this->render( 'ajax/soul/coalitions.html.twig', $this->addDefaultTwigArgs("soul_coalitions", [
            'membership' => $user_coalition,
            'all_users' => $all_users,
            'invitations' => $user_invitations,
        ]) );
    }

    /**
     * @Route("api/soul/coalition/create", name="soul_create_coalition")
     * @param TranslatorInterface $trans
     * @param PermissionHandler $perm
     * @return Response
     */
    public function api_soul_create_coalitions(TranslatorInterface $trans, PermissionHandler $perm): Response
    {
        $user = $this->getUser();


        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalitions = $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive] ]
        );

        if (!empty($user_coalitions)) return AjaxResponse::error( self::ErrorCoalitionAlreadyMember );

        // Creating a coalition refuses all invitations
        foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'user' => $user,
                'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation ]
        ) as $invitation) $this->entity_manager->remove($invitation);

        $this->entity_manager->persist( $g = (new UserGroup())->setName($trans->trans("%name%'s Koalition", ['%name%' => $user->getUsername()]))->setType(UserGroup::GroupSmallCoalition)->setRef1($user->getId()) );
        $perm->associate( $user, $g, UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationLevelFounder );

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/coalition/toggle", name="soul_toggle_coalition")
     * @return Response
     */
    public function api_soul_toggle_coalition_membership(): Response
    {
        $user = $this->getUser();


        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
                'user' => $user,
                'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive] ]
        );

        if ($user_coalition === null) return AjaxResponse::error( self::ErrorCoalitionNotSet );

        $user_coalition->setAssociationType(
            $user_coalition->getAssociationType() === UserGroupAssociation::GroupAssociationTypeCoalitionMember
                ? UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive
                : UserGroupAssociation::GroupAssociationTypeCoalitionMember
        );
        $this->entity_manager->persist( $user_coalition );

        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/coalition/leave/{coalition<\d+>}", name="soul_leave_coalition")
     * @param int $coalition
     * @return Response
     */
    public function api_soul_leave_coalition(int $coalition): Response
    {
        $user = $this->getUser();

        /** @var UserGroupAssociation|null $user_coalition */
        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->find($coalition);

        if ($user_coalition === null) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        if (
            $user_coalition->getUser() !== $user ||
            $user_coalition->getAssociation()->getType() !== UserGroup::GroupSmallCoalition ||
            !in_array($user_coalition->getAssociationType(), [
                UserGroupAssociation::GroupAssociationTypeCoalitionInvitation,
                UserGroupAssociation::GroupAssociationTypeCoalitionMember,
                UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive
            ])) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $destroy = $user_coalition->getAssociationLevel() === UserGroupAssociation::GroupAssociationLevelFounder;


        if ($destroy) {

            foreach ($this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
                'association' => $user_coalition->getAssociation()
            ]) as $assoc ) $this->entity_manager->remove($assoc);

            $this->entity_manager->remove( $user_coalition->getAssociation() );

        } else $this->entity_manager->remove( $user_coalition );


        try {
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/soul/season", name="soul_season")
     * @return Response
     */
    public function soul_season(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/soul/season.html.twig', $this->addDefaultTwigArgs("soul_season", null) );
    }

    /**
     * @Route("jx/soul/rps", name="soul_rps")
     * @return Response
     */
    public function soul_rps(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $rps = $this->entity_manager->getRepository(FoundRolePlayText::class)->findByUser($user);
        return $this->render( 'ajax/soul/rps.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'rps' => $rps
        )));
    }

    /**
     * @Route("jx/soul/rps/read/{id}-{page}", name="soul_rp", requirements={"id"="\d+", "page"="\d+"})
     * @return Response
     */
    public function soul_view_rp(int $id, int $page): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $rp = $this->entity_manager->getRepository(FoundRolePlayText::class)->find($id);
        if($rp === null || !$user->getFoundTexts()->contains($rp)){
            return $this->redirect($this->generateUrl('soul_rps'));
        }

        if($page > count($rp->getText()->getPages()))
            return $this->redirect($this->generateUrl('soul_rps'));

        $pageContent = $this->entity_manager->getRepository(RolePlayTextPage::class)->findOneByRpAndPageNumber($rp->getText(), $page);

        preg_match('/%asset%([a-zA-Z0-9.\/]+)%endasset%/', $pageContent->getContent(), $matches);

        if(count($matches) > 0) {
            $pageContent->setContent(preg_replace("={$matches[0]}=", "<img src='" . $this->asset->getUrl($matches[1]) . "' alt='' />", $pageContent->getContent()));
        }

        return $this->render( 'ajax/soul/view_rp.html.twig', $this->addDefaultTwigArgs("soul_rps", array(
            'page' => $pageContent,
            'rp' => $rp,
            'current' => $page
        )));
    }


    /**
     * @Route("jx/soul/{sid}/town/{idtown}", name="soul_view_town")
     * @param string $sid
     * @param int $idtown
     * @return Response
     */
    public function soul_view_town(int $idtown, $sid = 'me'): Response
    {
        $user = $this->getUser();

        if ($sid !== 'me' && !is_numeric($sid))
            return $this->redirect($this->generateUrl('soul_me'));

        $id = $sid === 'me' ? -1 : (int)$sid;
        if ($id === $user->getId())
            return $this->redirect($this->generateUrl( 'soul_view_town', ['idtown' => $idtown] ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $target_user = $this->entity_manager->getRepository(User::class)->find($id);
        if($target_user === null && $id !== -1)  return $this->redirect($this->generateUrl('soul_me'));

        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($idtown);
        if($town === null)
            return $target_user === null ? $this->redirect($this->generateUrl('soul_me')) : $this->redirect($this->generateUrl('soul_visit', ['id' => $id]));

        if ($target_user === null) $target_user = $user;

        $pictoname = $town->getType()->getName() == 'panda' ? 'r_suhard_#00' : 'r_surlst_#00';
        $proto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoname]);

        $picto = $this->entity_manager->getRepository(Picto::class)->findOneBy(['townEntry' => $town, 'prototype' => $proto]);

        return $this->render(  $user === $target_user ? 'ajax/soul/view_town.html.twig' : 'ajax/soul/view_town_foreign.html.twig', $this->addDefaultTwigArgs("soul_visit", array(
            'user' => $target_user,
            'town' => $town,
            'last_user_standing' => $picto !== null ? $picto->getUser() : null
        )));
    }

    /**
     * @Route("api/soul/town/add_comment", name="soul_add_comment")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_add_comment(JSONRequestParser $parser): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $id = $parser->get("id");
        /** @var CitizenRankingProxy $citizenProxy */
        $citizenProxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($id);
        if ($citizenProxy === null || $citizenProxy->getUser() !== $user )
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $comment = $parser->get("comment");
        $citizenProxy->setComment($comment);
        if ($citizenProxy->getCitizen()) $citizenProxy->getCitizen()->setComment($comment);

        $this->entity_manager->persist($citizenProxy);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/generateid", name="api_soul_settings_generateid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_generateid(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $user->setExternalId(md5($user->getEmail() . mt_rand()));
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/deleteid", name="api_soul_settings_deleteid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_deleteid(EntityManagerInterface $entityManager): Response {
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');
        $entityManager->persist( $user );
        $entityManager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/common", name="api_soul_common")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_common(JSONRequestParser $parser): Response {
        $user = $this->getUser();

        $user->setPreferSmallAvatars( (bool)$parser->get('sma', false) );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/setlanguage", name="api_soul_set_language")
     * @param JSONRequestParser $parser
     * @param Request $request
     * @param UserHandler $userHandler
     * @param SessionInterface $session
     * @return Response
     */
    public function soul_settings_set_language(JSONRequestParser $parser, Request $request, UserHandler $userHandler, SessionInterface $session): Response {
        $user = $this->getUser();

        $validLanguages = ['de','fr','en','es'];
        if (!$parser->has('lang', true))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        
        $lang = $parser->get('lang', 'de');
        if (!in_array($lang, $validLanguages))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);

        // Check if the user has seen all news in the previous language
        $previous_lang = $user->getLanguage() ?? $request->getLocale() ?? 'de';
        $seen_news = $userHandler->hasSeenLatestChangelog($user, $previous_lang);

        $user->setLanguage( $lang );
        $session->set('_user_lang',$lang);

        if ($seen_news) $userHandler->setSeenLatestChangelog($user, $lang);
        else $user->setLatestChangelog(null);

        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/defaultrole", name="api_soul_defaultrole")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_defaultrole(JSONRequestParser $parser, AdminActionHandler $admh): Response {
        $user = $this->getUser();

        $asDev = $parser->get('dev', false);
        if ($admh->setDefaultRoleDev($user->getId(), $asDev))
            return AjaxResponse::success();

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/soul/settings/avatar", name="api_soul_avatar")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_avatar(JSONRequestParser $parser, ConfMaster $conf): Response {

        $payload = $parser->get_base64('image', null);
        $upload = (int)$parser->get('up', 1);
        $mime = $parser->get('mime', null);

        $user = $this->getUser();

        if ($upload) {
            if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);
            if (!$payload) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            
            $raw_processing = $conf->getGlobalConf()->get(MyHordesConf::CONF_RAW_AVATARS, false);
            $error = $this->user_handler->setUserBaseAvatar($user, $payload, $raw_processing ? UserHandler::ImageProcessingPreferImagick : UserHandler::ImageProcessingForceImagick, $raw_processing ? $mime : null);
            if ($error !== UserHandler::NoError)
                return AjaxResponse::error($error);

            $this->entity_manager->persist( $user );
        } elseif ($user->getAvatar()) {

            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/avatar/crop", name="api_soul_small_avatar")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_small_avatar(JSONRequestParser $parser): Response
    {
        if (!$parser->has_all(['x', 'y', 'dx', 'dy'], false))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $x  = (int)floor((float)$parser->get('x', 0));
        $y  = (int)floor((float)$parser->get('y', 0));
        $dx = (int)floor((float)$parser->get('dx', 0));
        $dy = (int)floor((float)$parser->get('dy', 0));

        $user = $this->getUser();
        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $error = $this->user_handler->setUserSmallAvatar($user, null, $x, $y, $dx, $dy);
        if ($error !== UserHandler::NoError) return AjaxResponse::error( $error );

        $this->entity_manager->persist($user);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/change_password", name="api_soul_change_password")
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_change_pass(UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, TokenStorageInterface $token): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY') && !$this->isGranted( 'ROLE_CROW' ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $new_pw = $parser->trimmed('pw_new', '');
        if (mb_strlen($new_pw) < 6) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $user->setPassword( $passwordEncoder->encodePassword($user, $parser->trimmed('pw_new')) );

        $this->entity_manager->persist($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Dein Passwort wurde erfolgreich geändert. Bitte logge dich mit deinem neuen Passwort ein.', [], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("api/soul/settings/delete_account", name="api_soul_delete_account")
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param JSONRequestParser $parser
     * @param UserHandler $userhandler
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_delete_account(UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, UserHandler $userhandler, TokenStorageInterface $token): Response
    {
        $user = $this->getUser();

        if ($this->getUser()->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $name = $user->getUsername();
        $userhandler->deleteUser($user);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Auf wiedersehen, %name%. Wir werden dich vermissen und hoffen, dass du vielleicht doch noch einmal zurück kommst.', ['%name%' => $name], 'login') );
        $token->setToken(null);
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/soul/{id}", name="soul_visit", requirements={"id"="\d+"})
     * @return Response
     */
    public function soul_visit(int $id): Response
    {
        $current_user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($current_user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var User $user */
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if($user === null || $user === $current_user) 
            return $this->redirect($this->generateUrl('soul_me'));

        $pictos = $this->entity_manager->getRepository(Picto::class)->findNotPendingByUser($user);
    	$points = $this->user_handler->getPoints($user);

        $referer = null; // get the referer, it can be empty!
        $returnUrl = $this->generateUrl('soul_me');
        //TODO: get referer, generate URL to return to it

        $cac = $current_user->getActiveCitizen();
        $uac = $user->getActiveCitizen();
        $citizen_id = ($cac && $uac && $cac->getAlive() && !$cac->getZone() && $cac->getTown() === $uac->getTown()) ? $uac->getId() : null;

        return $this->render( 'ajax/soul/visit.html.twig', $this->addDefaultTwigArgs("soul_visit", [
        	'user' => $user,
            'pictos' => $pictos,
            'points' => round($points, 0),
            'seasons' => $this->entity_manager->getRepository(Season::class)->findAll(),
            'returnUrl' => $returnUrl,
            'citizen_id' => $citizen_id,
        ]));
    }

    /**
     * @Route("api/soul/unsubscribe", name="api_unsubscribe")
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @return Response
     */
    public function unsubscribe_api(JSONRequestParser $parser, SessionInterface $session): Response {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);



        if ($nextDeath->getCod()->getRef() != CauseOfDeath::Poison && $nextDeath->getCod()->getRef() != CauseOfDeath::GhulEaten)
            $last_words = $parser->get('lastwords');
        else $last_words = $this->translator->trans("...der Mörder .. ist.. IST.. AAARGHhh..", [], "game");

        // Here, we delete picto with persisted = 0,
        // and definitively validate picto with persisted = 1
        /** @var Picto[] $pendingPictosOfUser */
        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUser($user);
        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($pendingPicto->getPersisted() == 0)
                $this->entity_manager->remove($pendingPicto);
            else {
                $pendingPicto->setPersisted(2);
                $this->entity_manager->persist($pendingPicto);
            }
        }

        //$awardRepo = $this->entity_manager->getRepository(AwardPrototype::class);
        //foreach ($pendingPictosOfUser as $pendingPicto) {
        //    if($awardRepo->getAwardsByPicto($pendingPicto->getPrototype()->getLabel()) != null) {
        //        $this->checkAwards($user, $pendingPicto->getPrototype()->getLabel());
        //    }
        //}


        if ($active = $nextDeath->getCitizen()) {
            $active->setActive(false);
            $active->setLastWords( $user->getShadowBan() ? '' : $last_words);
            $nextDeath = CitizenRankingProxy::fromCitizen( $active, true );
            $this->entity_manager->persist( $active );
        }
        
        $nextDeath->setConfirmed(true)->setLastWords( $last_words );

        $this->entity_manager->persist( $nextDeath );
        $this->entity_manager->flush();

        if ($session->has('_town_lang')) {
            $session->remove('_town_lang');
            return AjaxResponse::success()->setAjaxControl(AjaxResponse::AJAX_CONTROL_RESET);
        } else return AjaxResponse::success();
    }

    private function checkAwards(User $user, string $award) {
        //$repo = $this->entity_manager->getRepository(Award::class);
        //$awardList = $this->entity_manager->getRepository(AwardPrototype::class)->getAwardsByPicto($award);
        //$pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByLabel($award);
        //$numPicto = 0;

        //foreach($this->entity_manager->getRepository(Picto::class)->getAllByUserAndPicto($user, $pictoPrototype) as $item) {
        //    /** @var Picto $item */
        //    $numPicto += $item->getCount();
        //}

        //foreach($awardList as $item) {
        //    /** @var AwardPrototype $item */
        //    if($numPicto >= $item->getUnlockQuantity() && !$repo->hasAward($user, $item)) {
        //        $newAward = new Award();
        //        $newAward->setUser($user);
        //        $newAward->setPrototype($item);
        //        $this->entity_manager->persist($newAward);
        //    }
        //}
    }


    /**
     * @Route("jx/soul/death", name="soul_death")
     * @return Response
     */
    public function soul_deathpage(): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        $nextDeath = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user);
        if ($nextDeath === null || ($nextDeath->getCitizen() && $nextDeath->getCitizen()->getAlive()))
            return $this->redirect($this->generateUrl('initial_landing'));

        $pictosDuringTown = $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($user, $nextDeath->getTown());
        $pictosWonDuringTown = [];
        $pictosNotWonDuringTown = [];

        foreach ($pictosDuringTown as $picto) {
            if ($picto->getPrototype()->getName() == "r_ptame_#00") continue;
            if ($picto->getPersisted() > 0)
                $pictosWonDuringTown[] = $picto;
            else
                $pictosNotWonDuringTown[] = $picto;
        }

        $canSeeGazette = $nextDeath->getTown() !== null;
        if($canSeeGazette){
            $citizensAlive = false;
            foreach ($nextDeath->getTown()->getCitizens() as $citizen) {
                if($citizen->getCod() === null){
                    $citizensAlive = true;
                    break;
                }
            }
            if(!$citizensAlive && $nextDeath->getCod()->getRef() != CauseOfDeath::Radiations) {
                $canSeeGazette = false;
            }
        }


        return $this->render( 'ajax/soul/death.html.twig', [
            'citizen' => $nextDeath,
            'sp' => $nextDeath->getPoints(),
            'pictos' => $pictosWonDuringTown,
            'gazette' => $canSeeGazette,
            'denied_pictos' => $pictosNotWonDuringTown
        ] );
    }

    /**
     * @Route("api/soul/{user_id}/towns_all", name="soul_get_towns")
     * @param int $user_id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_town_list(int $user_id, JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->entity_manager->getRepository(User::class)->find($user_id);
        if ($user === null) return new Response("");

        $season_id = $parser->get('season', '');
        if(empty($season_id)) return new Response("");

        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['id' => $season_id]);

        $limit = (bool)$parser->get('limit10', true);

        return $this->render( 'ajax/soul/town_list.html.twig', [
            'towns' => $this->entity_manager->getRepository(CitizenRankingProxy::class)->findPastByUserAndSeason($user, $season, $limit),
            'editable' => $user->getId() === $this->getUser()->getId()
        ]);
    }

    /**
     * @Route("jx/help", name="help_me")
     * @return Response
     */
    public function help_me(): Response
    {
        return $this->render( 'ajax/help/shell.html.twig');
    }
}
