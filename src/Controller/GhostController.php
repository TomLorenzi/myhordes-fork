<?php

namespace App\Controller;

use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\ConsecutiveDeathMarker;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class GhostController extends CustomAbstractController implements GhostInterfaceController
{
    private $user_handler;
    const ErrorWrongTownPassword          = ErrorHelper::BaseGhostErrors + 1;

    public function __construct(EntityManagerInterface $em, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->user_handler = $uh;
    }

    protected function addDefaultTwigArgs(?string $section = null, ?array $data = null ): array {
        parent::addDefaultTwigArgs($section, $data);
        $data = $data ?? [];

        $data['clock'] = [
            'desc'      => $this->translator->trans('Worauf warten Sie noch?', [], 'ghost'),
            'day'       => "",
            'timestamp' => new \DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true),
            'towntype'  => "",
        ];

        return $data;
    }

    /**
     * @Route("jx/ghost/welcome", name="ghost_welcome")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function welcome(EntityManagerInterface $em, ConfMaster $conf, UserHandler $uh): Response
    {
        $user = $this->getUser();

        if ($user->getShadowBan())
            return $this->redirect($this->generateUrl( 'soul_disabled' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $coa_members = $this->user_handler->getAvailableCoalitionMembers($user, $count, $active);
        $cdm_lock = $this->user_handler->getConsecutiveDeathLock( $user, $cdm_warn );

        return $this->render( 'ajax/ghost/intro.html.twig', $this->addDefaultTwigArgs([
            'warnCoaInactive'    => $count > 0 && !$active,
            'warnCoaNotComplete' => $count > 0 && (count($coa_members) + 1) < $count,
            'warnCoaEmpty'       => $count > 1 && empty($coa_members),
            'coa'                => $coa_members,
            'cdm_level'          => $cdm_lock ? 2 : ( $cdm_warn ? 1 : 0 ),
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $this->getUserTownClassAccess($conf->getGlobalConf()),
            'canCreateTown' => $uh->hasSkill($user, 'mayor') || $user->getRightsElevation() >= User::ROLE_CROW,
        ] ));
    }

    /**
     * @Route("jx/ghost/create_town", name="ghost_create_town")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function create_town(EntityManagerInterface $em, ConfMaster $conf, UserHandler $uh): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        if(!$uh->hasSkill($user, 'mayor') && $user->getRightsElevation() < User::ROLE_CROW){
            return $this->redirect($this->generateUrl( 'initial_landing' ));
        }

        return $this->render( 'ajax/ghost/create_town.html.twig', $this->addDefaultTwigArgs([
            'townClasses' => $em->getRepository(TownClass::class)->findBy(['hasPreset' => true]),
        ]));
    }

    /**
     * @Route("api/ghost/create_town", name="ghost_process_create_town")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param ConfMaster $conf
     * @param UserHandler $uh
     * @param GameFactory $gf
     * @param LogTemplateHandler $log
     * @return Response
     */
    public function process_create_town(JSONRequestParser $parser, EntityManagerInterface $em, ConfMaster $conf,
                                        UserHandler $uh, GameFactory $gf, LogTemplateHandler $log,
                                        TownHandler $townHandler): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_death')] );

        if(!$uh->hasSkill($user, 'mayor') && $user->getRightsElevation() < User::ROLE_CROW){
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable, ['url' => $this->generateUrl('initial_landing')] );
        }

        $crow_permissions = $this->isGranted('ROLE_CROW');

        $nightwatch = $parser->get('nightWatchMode', 'normal', ['normal','instant','none']);

        $customConf = [
            'open_town_limit'      => ($crow_permissions && !(bool)$parser->get('negate', true)) ? -1 : 2,
            'lock_door_until_full' => $crow_permissions ? (bool)$parser->get('lock_door', true) : true,

            'features' => [
                'xml_feed' => !(bool)$parser->get('disablexml', false),

                'ghoul_mode'    => $parser->get('ghoulType', 'normal'),
                'shaman'    => $parser->get('shamanMode', 'normal', ['normal','job','none']),
                'shun'          => (bool)$parser->get('shun', true),
                'nightmode'     => (bool)$parser->get('nightmode', true),
                'camping'       => (bool)$parser->get('camp', true),
                'ghoul'         => (bool)$parser->get('ghouls', true),
                'improveddump'  => (bool)$parser->get('improveddump', true),
                'attacks'       => $parser->get('attacks', 'normal', ['easy','normal','hard']),

                'nightwatch' => [
                    'enabled' => $nightwatch !== 'none',
                    'instant' => $nightwatch === 'instant',
                ],

                'escort' => [
                    'enabled' => (bool)$parser->get('escorts', false)
                ],

                'give_all_pictos' => $crow_permissions ? (bool)$parser->get('allpictos', false) : false,
                'give_soulpoints' => $crow_permissions ? (bool)$parser->get('soulpoints', false) : false,
            ],

            'modifiers' => [
                'strict_picto_distribution' => $crow_permissions ? (bool)$parser->get('strict_pictos', false) : false,
            ]
        ];

        $townname = $crow_permissions ? $parser->get('townName', '') : '';
        $password = $parser->get('password', null);
        $lang = $parser->get('lang', '');
        $well = $parser->get('well', '');

        $seed       = $crow_permissions ? (int)$parser->get('seed', -1) : -1;
        $incarnated = $crow_permissions ? (bool)$parser->get('incarnated', true) : true;

        if(!empty($well) && is_numeric($well) && $well <= 300){
            $customConf['well'] = [
                'min' => $well,
                'max' => $well
            ];
        }

        $remove_chest_items = [];

        $rules = $parser->get('rules', []);
        if (is_array($rules)) foreach ($rules as $rule) switch($rule) {
            case 'nobuilding':
                $customConf['features']['unlocked_buildings']['replace'] = [];
                break;
            case 'poison':
                $customConf['features']['all_poison'] = true;
                break;
            case 'nobeta':
                $remove_chest_items[] = 'beta_drug_#00';
                break;
        }

        $customConf['initial_chest']['remove'] = $remove_chest_items;

        if (!(bool)$parser->get('ruins', '')) $customConf['explorable_ruins'] = 0;


        $disabled_jobs   = [];
        $disabled_builds = [];
        $disabled_roles  = [];

        if($customConf['features']['shaman'] == "normal" || $customConf['features']['shaman'] == "none")
            $disabled_jobs[] = 'shaman';
        else if ($customConf['features']['shaman'] == "job" || $customConf['features']['shaman'] == "none")
            $disabled_roles[] = 'shaman';

        if(!(bool)$parser->get('basic', true)) $disabled_jobs[] = 'basic';
        if(!(bool)$parser->get('collec', true)) $disabled_jobs[] = 'collec';
        if(!(bool)$parser->get('guardian', true)) $disabled_jobs[] = 'guardian';
        if(!(bool)$parser->get('hunter', true)) $disabled_jobs[] = 'hunter';
        if(!(bool)$parser->get('tamer', true)) $disabled_jobs[] = 'tamer';
        if(!(bool)$parser->get('tech', true)) $disabled_jobs[] = 'tech';
        if(!(bool)$parser->get('survivalist', true)) $disabled_jobs[] = 'survivalist';

        if(!(bool)$parser->get('shaman', false))
            $disabled_jobs[] = 'shaman';
        else if (in_array('shaman', $disabled_jobs)) {
            // If the shaman is disabled, but we enforced its activation, remove it from the disabled array
            $disabled_jobs = array_diff($disabled_jobs, ['shaman']);
        }

        if ($customConf['features']['shaman'] !== 'job') {
            $disabled_builds[] = 'small_vaudoudoll_#00';
            $disabled_builds[] = 'small_bokorsword_#00';
            $disabled_builds[] = 'small_spiritmirage_#00';
            $disabled_builds[] = 'small_holyrain_#00';
        }

        if ($customConf['features']['shaman'] !== 'normal')
            $disabled_builds[] = 'small_spa4souls_#00';

        if ($nightwatch !== 'normal')
            $disabled_builds[] = 'small_round_path_#00';

        $customConf['disabled_jobs']['replace']      = $disabled_jobs;
        $customConf['disabled_buildings']['replace'] = $disabled_builds;
        $customConf['disabled_roles']['replace']     = $disabled_roles;

        $type = $parser->get('townType', 'remote', array_map(fn(TownClass $t) => $t->getName(), $em->getRepository(TownClass::class)->findBy(['hasPreset' => true])));
        if ($crow_permissions && (bool)$parser->get('unprivate', false))
            $town = $gf->createTown($townname, $lang, null, $type, $customConf, $seed);
        else $town = ($gf->createTown($townname, $lang, null, 'custom', $customConf, $seed))->setDeriveConfigFrom( $type );

        $town->setCreator($user);
        if(!empty($password)) $town->setPassword($password);
        $em->persist($town);

        if($incarnated) {
            $citizen = $gf->createCitizen($town, $user, $error);
            if (!$citizen) return AjaxResponse::error($error);
            try {
                $em->persist($citizen);
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

            $em->persist( $log->citizenJoin( $citizen ) );
        }

        try {
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $current_event = $this->conf->getCurrentEvent();
        if ($current_event->active()) {
            if (!$townHandler->updateCurrentEvent($town, $current_event)) {
                $em->clear();
            } else try {
                $em->persist($town);
                $em->flush();
            } catch (Exception $e) {}
        }

        return AjaxResponse::success( true, ['url' => $this->generateUrl('game_jobs')] );
    }

    /**
     * @Route("api/ghost/join", name="api_join")
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param EntityManagerInterface $em
     * @param ConfMaster $conf
     * @param LogTemplateHandler $log
     * @param TownHandler $townHandler
     * @return Response
     */
    public function join_api(JSONRequestParser $parser, GameFactory $factory, EntityManagerInterface $em,
                             ConfMaster $conf, LogTemplateHandler $log, TownHandler $townHandler) {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getShadowBan()) return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('town')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $town_id = (int)$parser->get('town', -1);
        if ($town_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Town $town */
        $town = $em->getRepository(Town::class)->find( $town_id );
        $user = $this->getUser();

        if (!$town || !$user) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if(!empty($town->getPassword()) && $town->getPassword() !== $parser->get('pass', ''))
            return AjaxResponse::error(self::ErrorWrongTownPassword);

        $allowedTownClasses = $this->getUserTownClassAccess($conf->getGlobalConf());
        if (!$allowedTownClasses[$town->getType()->getName()]) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $citizen = $factory->createCitizen($town, $user, $error, $all);
        if (!$citizen) return AjaxResponse::error($error);

        try {
            $em->persist($town);
            $em->persist($citizen);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['e' => $e->getMessage()]);
        }

        try {
            foreach ($all as $new_citizen)
                $em->persist( $log->citizenJoin( $new_citizen ) );
            $em->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $current_town_event = $this->conf->getCurrentEvent($town);
        if ($current_town_event->active()) {
            if (!$townHandler->updateCurrentEvent($town, $current_town_event))
                $em->clear();
            else {
                $em->persist($town);
                $em->flush();
            }
        }

        // Let's check if there is enough opened town
        $openTowns = $em->getRepository(Town::class)->findOpenTown();
        $count = array(
            "fr" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "de" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "en" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "es" => array(
                "remote" => 0,
                "panda" => 0,
                "small" => 0,
                'custom' => 0
            ),
            "multi" => array(
                "remote" => 100,
                "panda" => 100,
                "small" => 100,
                'custom' => 100
            ),
        );
        foreach ($openTowns as $openTown) {
            $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
        }

        $minOpenTown = $this->getMinOpenTownClass($conf->getGlobalConf());

        foreach ($count as $townLang => $array) {
            foreach ($array as $townClass => $openCount) {
                if($openCount < $minOpenTown[$townClass]){

                    // Create the count we need
                    for($i = 0 ; $i < $minOpenTown[$townClass] - $openCount ; $i++){
                        $newTown = $factory->createTown(null, $townLang, null, $townClass);
                        $em->persist($newTown);
                        $em->flush();

                        $current_event = $this->conf->getCurrentEvent();
                        if ($current_event->active()) {
                            if (!$townHandler->updateCurrentEvent($newTown, $current_event))
                                $em->clear();
                            else {
                                $em->persist($newTown);
                                $em->flush();
                            }
                        }
                    }
                }
            }
        }

        return AjaxResponse::success();
    }

    public function getUserTownClassAccess(MyHordesConf $conf): array {
        $user = $this->getUser();
        return [
            'small' =>
                ($user->getAllSoulPoints() < $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )
                || $user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )),
            'remote' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )),
            'panda' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 )),
            'custom' => ($user->getAllSoulPoints() >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 )),
        ];
    }

    public function getMinOpenTownClass(MyHordesConf $conf): array {
        return [
            'small' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 1 ),
            'remote' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_REMOTE, 1 ),
            'panda' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_PANDA, 1 ),
            'custom' => $conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_CUSTOM, 0 ),
        ];
    }

}
