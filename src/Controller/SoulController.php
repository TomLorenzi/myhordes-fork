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
use App\Response\AjaxResponse;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\JSONRequestParser;
use App\Service\TwinoidHandler;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Service\AdminActionHandler;
use App\Service\TimeKeeperService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Imagick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class SoulController extends AbstractController
{
    protected $entity_manager;
    protected $translator;
    protected $user_factory;
    protected $time_keeper;
    private $user_handler;
    private $asset;

    const ErrorAvatarBackendUnavailable      = ErrorHelper::BaseAvatarErrors +  1;
    const ErrorAvatarTooLarge                = ErrorHelper::BaseAvatarErrors +  2;
    const ErrorAvatarFormatUnsupported       = ErrorHelper::BaseAvatarErrors +  3;
    const ErrorAvatarImageBroken             = ErrorHelper::BaseAvatarErrors +  4;
    const ErrorAvatarResolutionUnacceptable  = ErrorHelper::BaseAvatarErrors +  5;
    const ErrorAvatarProcessingFailed        = ErrorHelper::BaseAvatarErrors +  6;
    const ErrorAvatarInsufficientCompression = ErrorHelper::BaseAvatarErrors +  7;
    const ErrorUserEditPasswordIncorrect     = ErrorHelper::BaseAvatarErrors +  8;
    const ErrorTwinImportInvalidResponse     = ErrorHelper::BaseAvatarErrors +  9;
    const ErrorTwinImportNoToken             = ErrorHelper::BaseAvatarErrors + 10;
    const ErrorTwinImportProfileMismatch     = ErrorHelper::BaseAvatarErrors + 11;
    const ErrorTwinImportProfileInUse        = ErrorHelper::BaseAvatarErrors + 12;

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
     * @Route("jx/soul/me", name="soul_me")
     * @return Response
     */
    public function soul_me(): Response
    {
        /** @var User $user */
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
     * @Route("jx/soul/news", name="soul_news")
     * @return Response
     */
    public function soul_news(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $news = $this->entity_manager->getRepository(Changelog::class)->findByLang($user->getLanguage());
        return $this->render( 'ajax/soul/news.html.twig', $this->addDefaultTwigArgs("soul_news", [
            'news' => $news
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
        /** @var User $user */
        $user = $this->getUser();

        if ($cache = $this->entity_manager->getRepository(TwinoidImportPreview::class)->findOneBy(['user' => $user])) {

            return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
                'payload' => $cache->getData($this->entity_manager), 'preview' => true
            ]) );

        } else return $this->render( 'ajax/soul/import.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'services' => ['www.hordes.fr' => 'Hordes','www.die2nite.com' => 'Die2Nite','www.dieverdammten.de' => 'Die Verdammten','www.zombinoia.com' => 'Zombinoia'],
            'code' => $code, 'need_sk' => !$twin->hasBuiltInTwinoidAccess(),
            'souls' => $this->entity_manager->getRepository(TwinoidImport::class)->findBy(['user' => $user], ['created' => 'DESC'])
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

        $import = $this->entity_manager->getRepository(TwinoidImport::class)->find( $id );
        if (!$import || $import->getUser() !== $user) return $this->redirect($this->generateUrl('soul_import'));

        return $this->render( 'ajax/soul/import_preview.html.twig', $this->addDefaultTwigArgs("soul_settings", [
            'payload' => $import->getData($this->entity_manager), 'preview' => false
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
        /** @var User $user */
        $user = $this->getUser();

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
        /** @var User $user */
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
        /** @var User $user */
        $user = $this->getUser();

        $pending = null;

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

        if ($twin->importData( $user, $scope, $data, false )) {

            if ($id < 0) {
                $import_ds = $this->entity_manager->getRepository(TwinoidImport::class)->findOneBy(['user' => $user, 'scope' => $scope]);
                if ($import_ds === null) $user->addTwinoidImport( $import_ds = new TwinoidImport() );

                $import_ds->fromPreview( $pending );

                $user->setTwinoidImportPreview(null);
                $pending->setUser(null);

                $this->entity_manager->persist( $user );
                $this->entity_manager->remove($pending);
            }

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
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        return $this->render( 'ajax/soul/coalitions.html.twig', $this->addDefaultTwigArgs("soul_coalitions", null) );
    }

    /**
     * @Route("jx/soul/season", name="soul_season")
     * @return Response
     */
    public function soul_season(): Response
    {
        /** @var User $user */
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
        /** @var User $user */
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
        /** @var User $user */
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
     * @Route("jx/soul/town/{id}", name="soul_view_town", requirements={"id"="\d+"})
     * @return Response
     */
    public function soul_view_town(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
        if($town === null){
            return $this->redirect($this->generateUrl('soul_me'));
        }

        $pictoname = $town->getType()->getName() == 'panda' ? 'r_suhard_#00' : 'r_surlst_#00';
        $proto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoname]);

        $picto = $this->entity_manager->getRepository(Picto::class)->findOneBy(['townEntry' => $town, 'prototype' => $proto]);

        return $this->render( 'ajax/soul/view_town.html.twig', $this->addDefaultTwigArgs("soul_me", array(
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
        /** @var User $user */
        $user = $this->getUser();

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
     * @return Response
     */
    public function soul_settings_generateid(EntityManagerInterface $entityManager): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId(md5($user->getEmail() . mt_rand()));
        $entityManager->persist( $user );
        $entityManager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/deleteid", name="api_soul_settings_deleteid")
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function soul_settings_deleteid(EntityManagerInterface $entityManager): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $user->setExternalId('');
        $entityManager->persist( $user );
        $entityManager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/common", name="api_soul_common")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_common(JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->getUser();

        $user->setPreferSmallAvatars( (bool)$parser->get('sma', false) );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/setlanguage", name="api_soul_set_language")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_set_language(JSONRequestParser $parser): Response {
        /** @var User $user */
        $user = $this->getUser();

        $validLanguages = ['de','fr','en','es'];
        if (!$parser->has('lang', true))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        
        $lang = $parser->get('lang', 'de');
        if (!in_array($lang, $validLanguages))
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);

        $user->setLanguage( $lang );
        $this->entity_manager->persist( $user );
        $this->entity_manager->flush();

        return new AjaxResponse( ['success' => true] );
    }

    /**
     * @Route("api/soul/settings/defaultrole", name="api_soul_defaultrole")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_defaultrole(JSONRequestParser $parser, AdminActionHandler $admh): Response {
        /** @var User $user */
        $user = $this->getUser();

        $asDev = $parser->get('dev', false);
        if ($admh->setDefaultRoleDev($user->getId(), $asDev))
            return new AjaxResponse( ['success' => true] );

        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
    }

    /**
     * @Route("api/soul/settings/avatar", name="api_soul_avatar")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function soul_settings_avatar(JSONRequestParser $parser): Response {

        if (!extension_loaded('imagick')) return AjaxResponse::error(self::ErrorAvatarBackendUnavailable );

        $payload = $parser->get_base64('image', null);
        $upload = (int)$parser->get('up', 1);

        /** @var User $user */
        $user = $this->getUser();

        if ($upload) {

            if (!$payload) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            // Processing limit: 3MB
            if (strlen( $payload ) > 3145728) return AjaxResponse::error( self::ErrorAvatarTooLarge );

            $im_image = new Imagick();
            $processed_image_data = null;

            try {
                if (!$im_image->readImageBlob($payload))
                    return AjaxResponse::error( self::ErrorAvatarImageBroken );

                if (!in_array($im_image->getImageFormat(), ['GIF','JPEG','BMP','PNG','WEBP']))
                    return AjaxResponse::error( self::ErrorAvatarFormatUnsupported );

                if ($im_image->getImageFormat() === 'GIF') {
                    $im_image->coalesceImages();
                    $im_image->resetImagePage('0x0');
                    $im_image->setFirstIterator();
                }

                $w = $im_image->getImageWidth();
                $h = $im_image->getImageHeight();

                if ($w / $h < 0.1 || $h / $w < 0.1 || $h < 16 || $w < 16)
                    return AjaxResponse::error( self::ErrorAvatarResolutionUnacceptable, [$w,$h,$im_image->getImageFormat() ] );

                if ( (max($w,$h) > 200 || min($w,$h < 90)) &&
                    !$im_image->resizeImage(
                        min(200,max(90,$w,$h)),
                        min(200,max(90,$w,$h)),
                        imagick::FILTER_SINC, 1, true )
                ) return AjaxResponse::error( self:: ErrorAvatarProcessingFailed );

                if ($im_image->getImageFormat() === 'GIF')
                    $im_image->setFirstIterator();

                $w_final = $im_image->getImageWidth();
                $h_final = $im_image->getImageHeight();

                switch ($im_image->getImageFormat()) {
                    case 'JPEG':
                        $im_image->setImageCompressionQuality ( 90 );
                        break;
                    case 'PNG':
                        $im_image->setOption('png:compression-level', 9);
                        break;
                    case 'GIF':
                        $im_image->setOption('optimize', true);
                        break;
                    default: break;
                }

                $processed_image_data = $im_image->getImagesBlob();
                if (strlen($processed_image_data) > 1048576) return AjaxResponse::error( self::ErrorAvatarInsufficientCompression );
            } catch (Exception $e) {
                return AjaxResponse::error( self::ErrorAvatarProcessingFailed );
            }

            if (!($avatar = $user->getAvatar())) {
                $avatar = new Avatar();
                $user->setAvatar($avatar);
            }

            $name = md5( $processed_image_data );

            $avatar
                ->setChanged(new DateTime())
                ->setFilename( $name )
                ->setSmallName( $name )
                ->setFormat( strtolower( $im_image->getImageFormat() ) )
                ->setImage( $processed_image_data )
                ->setX( $w_final )
                ->setY( $h_final )
                ->setSmallImage( null );

            $this->entity_manager->persist( $user );
            $this->entity_manager->persist( $avatar );
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

        /** @var User $user */
        $user = $this->getUser();
        $avatar = $user->getAvatar();

        if (!$avatar || $avatar->isClassic())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        if (
            $x < 0 || $dx < 0 || $x + $dx > $avatar->getX() ||
            $y < 0 || $dy < 0 || $y + $dy > $avatar->getY()
        ) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest, [$x,$y,$dx,$dy,$avatar->getX(),$avatar->getY()]);

        $im_image = new Imagick();
        $processed_image_data = null;

        try {
            if (!$im_image->readImageBlob(stream_get_contents( $avatar->getImage() )))
                return AjaxResponse::error(self::ErrorAvatarImageBroken);

            $im_image->setFirstIterator();

            if (!$im_image->cropImage( $dx, $dy, $x, $y ))
                return AjaxResponse::error(self::ErrorAvatarProcessingFailed);

            $im_image->setFirstIterator();

            $iw = $im_image->getImageWidth(); $ih = $im_image->getImageHeight();
            if ($iw < 90 || $ih < 30 || ($ih/$iw != 3)) {
                $new_height = max(30,$ih);
                $new_width = $new_height * 3;
                if (!$im_image->resizeImage(
                    $new_width, $new_height,
                    imagick::FILTER_SINC, 1, true ))
                    return AjaxResponse::error(self::ErrorAvatarProcessingFailed);
            }

            if ($im_image->getImageFormat() === 'GIF')
                $im_image->setOption('optimize', true);

            $processed_image_data = $im_image->getImagesBlob();
            if (strlen($processed_image_data) > 1048576) return AjaxResponse::error( self::ErrorAvatarInsufficientCompression );

        } catch (Exception $e) {
            return AjaxResponse::error( self::ErrorAvatarProcessingFailed );
        }

        $name = md5( (new DateTime())->getTimestamp() );

        $avatar
            ->setSmallName( $name )
            ->setSmallImage( $processed_image_data );

        $this->entity_manager->persist($avatar);

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
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY'))
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
     * @param DeathHandler $death
     * @param TokenStorageInterface $token
     * @return Response
     */
    public function soul_settings_delete_account(UserPasswordEncoderInterface $passwordEncoder, JSONRequestParser $parser, DeathHandler $death, TokenStorageInterface $token): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_DUMMY'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (!$passwordEncoder->isPasswordValid( $user, $parser->trimmed('pw') ))
            return AjaxResponse::error(self::ErrorUserEditPasswordIncorrect );

        $name = $user->getUsername();
        $user->setEmail("$ deleted <{$user->getId()}>")->setName("$ deleted <{$user->getId()}>")->setPassword(null)->setRightsElevation(0);
        if ($user->getAvatar()) {
            $this->entity_manager->remove($user->getAvatar());
            $user->setAvatar(null);
        }
        $citizen = $user->getActiveCitizen();
        if ($citizen) {
            $death->kill( $citizen, CauseOfDeath::Headshot, $r );
            foreach ($r as $re) $this->entity_manager->remove($re);
        }

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
        /** @var User $current_user */
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
     * @Route("jx/soul/{id}/town/{idtown}", name="soul_view_town_foreign", requirements={"id"="\d+", "idtown"="\d+"})
     * @param int $id
     * @param int $idtown
     * @return Response
     */
    public function soul_view_town_foreign(int $id, int $idtown): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));
        
    	$user = $this->entity_manager->getRepository(User::class)->find($id);
    	if ($user === null) return $this->redirect($this->generateUrl('soul_me'));
        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($idtown);
        if($town === null)
            return $this->redirect($this->generateUrl('soul_visit', ['id' => $id]));

        $pictoname = $town->getType()->getName() == 'panda' ? 'r_suhard_#00' : 'r_surlst_#00';
        $proto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoname]);

        $picto = $this->entity_manager->getRepository(Picto::class)->findOneBy(['townEntry' => $town, 'prototype' => $proto]);

        return $this->render( 'ajax/soul/view_town_foreign.html.twig', $this->addDefaultTwigArgs("soul_visit", array(
        	'user' => $user,
            'town' => $town,
            'last_user_standing' => $picto !== null ? $picto->getUser() : null
        )));
    }

    /**
     * @Route("api/soul/unsubscribe", name="api_unsubscribe")
     * @param JSONRequestParser $parser
     * @param SessionInterface $session
     * @return Response
     */
    public function unsubscribe_api(JSONRequestParser $parser, SessionInterface $session): Response {
        /** @var User $user */
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

        $awardRepo = $this->entity_manager->getRepository(AwardPrototype::class);
        foreach ($pendingPictosOfUser as $pendingPicto) {
            if($awardRepo->getAwardsByPicto($pendingPicto->getPrototype()->getLabel()) != null) {
                $this->checkAwards($user, $pendingPicto->getPrototype()->getLabel());
            }
        }

          /** @var User|null $user */
        if ($active = $nextDeath->getCitizen()) {
            $active->setActive(false);
            $active->setLastWords($last_words);
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
        $repo = $this->entity_manager->getRepository(Award::class);
        $awardList = $this->entity_manager->getRepository(AwardPrototype::class)->getAwardsByPicto($award);
        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByLabel($award);
        $numPicto = 0;

        foreach($this->entity_manager->getRepository(Picto::class)->getAllByUserAndPicto($user, $pictoPrototype) as $item) {
            /** @var Picto $item */
            $numPicto += $item->getCount();
        }

        foreach($awardList as $item) {
            /** @var AwardPrototype $item */
            if($numPicto >= $item->getUnlockQuantity() && !$repo->hasAward($user, $item)) {
                $newAward = new Award();
                $newAward->setUser($user);
                $newAward->setPrototype($item);
                $this->entity_manager->persist($newAward);
            }
        }
    }


    /**
     * @Route("jx/soul/death", name="soul_death")
     * @return Response
     */
    public function soul_deathpage(): Response
    {
        /** @var User $user */
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

        return $this->render( 'ajax/soul/town_list.html.twig', ['towns' => $this->entity_manager->getRepository(CitizenRankingProxy::class)->findPastByUserAndSeason($user, $season, $limit)]);
    }
}
