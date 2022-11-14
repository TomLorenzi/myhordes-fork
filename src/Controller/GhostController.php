<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\AccountRestriction;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(only_ghost=true)
 * @method User|null getUser
 */
class GhostController extends CustomAbstractController
{
    private UserHandler $user_handler;
    const ErrorWrongTownPassword          = ErrorHelper::BaseGhostErrors + 1;
    private GameProfilerService $gps;

    public function __construct(EntityManagerInterface $em, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih, GameProfilerService $gps)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator);
        $this->gps = $gps;
        $this->user_handler = $uh;
    }

    /**
     * @Route("jx/ghost/welcome", name="ghost_welcome")
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function welcome(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
            return $this->redirect($this->generateUrl( 'soul_disabled' ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $coa_members = $this->user_handler->getAvailableCoalitionMembers($user, $count, $active);
        $cdm_lock = $this->user_handler->getConsecutiveDeathLock( $user, $cdm_warn );

        return $this->render( 'ajax/ghost/intro.html.twig', $this->addDefaultTwigArgs(null, [
            'warnCoaInactive'    => $count > 0 && !$active,
            'warnCoaNotComplete' => $count > 0 && (count($coa_members) + 1) < $count,
            'warnCoaEmpty'       => $count > 1 && empty($coa_members),
            'coa'                => $coa_members,
            'cdm_level'          => $cdm_lock ? 2 : ( $cdm_warn ? 1 : 0 ),
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $this->getUserTownClassAccess($this->conf->getGlobalConf()),
            'userCantJoinReason' => $this->getUserTownClassAccessLimitReason($this->conf->getGlobalConf()),
            'sp_limits' => $this->getTownClassAccessLimits($this->conf->getGlobalConf()),
            'canCreateTown' => $this->user_handler->hasSkill($user, 'mayor') || $user->getRightsElevation() >= User::USER_LEVEL_CROW,
        ] ));
    }

    /**
     * @Route("jx/ghost/welcome_soul", name="welcome_soul")
     * @return Response
     */
    public function welcome_soul(): Response
    {
        return $this->render( 'ajax/ghost/welcome.html.twig' );
    }

    /**
     * @Route("jx/ghost/postgame", name="postgame")
     * @return Response
     */
    public function postgame_screen(): Response
    {
        $last_game_sp = $this->entity_manager->getRepository( CitizenRankingProxy::class )->matching(
            (new Criteria())
                ->andWhere( Criteria::expr()->gt('points', 0) )
                ->andWhere( Criteria::expr()->neq('points', null) )
                ->andWhere( Criteria::expr()->eq('disabled', false) )
                ->andWhere( Criteria::expr()->eq('confirmed', true) )
                ->andWhere( Criteria::expr()->eq('user', $this->getUser()) )
                ->orderBy(['end' => Criteria::DESC])
                ->setMaxResults(1)
        );
        $last_game_sp = $last_game_sp->isEmpty() ? 0 : $last_game_sp->first()->getPoints();
        $all_sp = $this->user_handler->fetchSoulPoints($this->getUser(), true);
        $town_limit = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE);

        return $this->render( 'ajax/ghost/donate.html.twig', ['exp' => $all_sp >= $town_limit && ($all_sp - $last_game_sp) < $town_limit] );
    }

    /**
     * @Route("jx/ghost/create_town", name="ghost_create_town", defaults={"react"=0})
     * @Route("jx/ghost/create_town_rc", name="ghost_create_town_rc", defaults={"react"=1})
     * @param int $react
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function create_town(int $react, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        if(!$this->user_handler->hasSkill($user, 'mayor') && $user->getRightsElevation() < User::USER_LEVEL_CROW){
            return $this->redirect($this->generateUrl( 'initial_landing' ));
        }

        return $this->render( $react ? 'ajax/ghost/create_town_rc.html.twig' : 'ajax/ghost/create_town.html.twig', $this->addDefaultTwigArgs(null, [
            'townClasses' => $em->getRepository(TownClass::class)->findBy(['hasPreset' => true]),
            'professions' => array_filter( $em->getRepository(CitizenProfession::class)->findAll(), fn(CitizenProfession $pro) => $pro->getName() !== CitizenProfession::DEFAULT ),
            'constructions' => $em->getRepository(BuildingPrototype::class)->findAll(),
            'langs' => $this->generatedLangs
        ]));
    }

    /**
     * @Route("api/ghost/create_town", name="ghost_process_create_town")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param GameFactory $gf
     * @param LogTemplateHandler $log
     * @param TownHandler $townHandler
     * @return Response
     */
    public function process_create_town(JSONRequestParser $parser, EntityManagerInterface $em,
                                        GameFactory $gf, LogTemplateHandler $log,
                                        TownHandler $townHandler): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_death')] );

        if(!$this->user_handler->hasSkill($user, 'mayor') && $user->getRightsElevation() < User::USER_LEVEL_CROW){
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable, ['url' => $this->generateUrl('initial_landing')] );
        }

        $crow_permissions = $this->isGranted('ROLE_CROW');

        $nightwatch = $parser->get('nightWatchMode', 'normal', ['normal','instant','none']);
        $nightmode = $parser->get('nightmode', 'myhordes', ['myhordes','hordes','none']);
        $ghoulmode = $parser->get('ghoulType', 'normal', ['normal', 'childtown', 'bloodthirst', 'airborne', 'airbnb']);

        $customConf = [
            'open_town_limit'      => ($crow_permissions && !(bool)$parser->get('negate', true)) ? -1 : 2,
            'lock_door_until_full' => !$crow_permissions || $parser->get('lock_door', true),

            'features' => [
                'xml_feed' => !(bool)$parser->get('disablexml', false),
                'citizen_alias' => $crow_permissions && $parser->get('citizenalias', false),

                'ghoul_mode'    => $ghoulmode,
                'shaman'        => $parser->get('shamanMode', 'normal', ['normal','job','none','both']),
                'shun'          => (bool)$parser->get('shun', true),
                'nightmode'     => $nightmode !== 'none',
                'camping'       => (bool)$parser->get('camp', true),
                'ghoul'         => (bool)$parser->get('ghouls', true),
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

                'free_for_all' => (bool)$parser->get('free_for_all', true),
            ],

            'overrides' => [
                'named_drops' => []
            ],

            'modifiers' => [
                'strict_picto_distribution' => $crow_permissions ? (bool)$parser->get('strict_pictos', false) : false,
                'daytime' => [
                    'range' => [$parser->get_int('nighttime0', 7, 0, 23),$parser->get_int('nighttime1', 18, 1, 24)],
                    'invert' => $parser->get('nighttime', 'day', ['day','night']) === 'night',
                ]
            ]
        ];

        $townname = $crow_permissions ? $parser->get('townName', '') : '';
        $password = $parser->get('password', null);
        $lang = $parser->get('lang', '');
        $well = $parser->get('well', '');
        $population = $parser->get('pop', '');

        $seed       = $crow_permissions ? $parser->get_int('seed', -1) : -1;
        $incarnated = $crow_permissions ? (bool)$parser->get('incarnated', true) : true;

        $map_size = []; $ruin_count = [];
        switch ($parser->get('mapsize', 'auto')) {
            case 'small': $map_size = [12,14]; $ruin_count = [10,0]; break;
            case 'normal': $map_size = [25,27]; break;
            case 'large':
                $map_size = $crow_permissions ? [32,35] : [];
                $ruin_count = $crow_permissions ? [30,2] : [];
                break;
            case 'custom':
                $s = max(10,min($parser->get_int('mapsize_e', 25),35));
                $r = max(0,min($parser->get_int('ruins_num', 20),30));
                $re = max(0,min($parser->get_int('ruins_e_num', 1),5));
                $map_size = $crow_permissions ? [$s,$s] : [];
                $ruin_count = [$r,$re]; break;
                break;
        }

        $map_margin = null;
        switch ($parser->get('mapmargin', 'normal')) {
            case 'normal': $map_margin = 0.25; break;
            case 'small' : $map_margin = 0.3; break;
            case 'center': $map_margin = 0.5; break;
        }

        $map = [];

        if (!empty($map_size)) {
            $map['min'] = $map_size[0];
            $map['max'] = $map_size[1];
        }

        if (!empty($map_margin)) $map['margin'] = $map_margin;
        if (!empty($map)) $customConf['map'] = $map;

        if (!empty($ruin_count)) {
            $customConf['ruins'] = $ruin_count[0];
            $customConf['explorable_ruins'] = $ruin_count[1];
        }

        $type = $parser->get('townType', 'remote', array_map(fn(TownClass $t) => $t->getName(), $em->getRepository(TownClass::class)->findBy(['hasPreset' => true])));

        if(!empty($well) && is_numeric($well) && $well <= ($crow_permissions ? 9999 : 300)){
            $customConf['well'] = [
                'min' => round(intval($well) * (($type === 'panda' && !$crow_permissions) ? (2.0/3.0) : 1)),
                'max' => round(intval($well) * (($type === 'panda' && !$crow_permissions) ? (2.0/3.0) : 1))
            ];
        }

        if(!empty($population) && is_numeric($population) && $population <= ($crow_permissions ? 80 : 40) && $population >= 10){
            $customConf['population'] = [
                'min' => $population,
                'max' => $population
            ];
            $customConf['open_town_grace'] = $population;
        }

        $rules = $parser->get('rules', []);
        if (is_array($rules)) foreach ($rules as $rule) switch($rule) {
            case 'nobuilding':
                $customConf['features']['unlocked_buildings']['replace'] = [];
                break;
            case 'poison':
                $customConf['features']['all_poison'] = true;
                break;
            case 'beta':
                $customConf['initial_chest'][] = 'beta_drug_#00';
                break;
            case 'with-toxin':
                $customConf['overrides']['named_drops'][] = 'with-toxin';
                break;
            case 'hungry-ghouls':
                $customConf['features']['hungry_ghouls'] = true;
                break;
        }

        if (!(bool)$parser->get('ruins', '')) $customConf['explorable_ruins'] = 0;


        $disabled_jobs   = [];
        $disabled_builds = [];
        $disabled_roles  = [];

        if ($nightmode !== 'myhordes') $disabled_builds[] = 'small_novlamps_#00';

        if($customConf['features']['shaman'] === "normal" || $customConf['features']['shaman'] === "none")
            $disabled_jobs[] = 'shaman';
        else if ($customConf['features']['shaman'] === "job" || $customConf['features']['shaman'] === "none")
            $disabled_roles[] = 'shaman';

        if (!(bool)$parser->get('improveddump', true)) {
            $disabled_builds[] = 'small_trash_#01';
            $disabled_builds[] = 'small_trash_#02';
            $disabled_builds[] = 'small_trash_#03';
            $disabled_builds[] = 'small_trash_#04';
            $disabled_builds[] = 'small_trash_#05';
            $disabled_builds[] = 'small_trash_#06';
            $disabled_builds[] = 'small_howlingbait_#00';
            $disabled_builds[] = 'small_trashclean_#00';
        }

        if ($customConf['features']['shaman'] !== 'job' && $customConf['features']['shaman'] !== 'both') {
            $disabled_builds[] = 'small_vaudoudoll_#00';
            $disabled_builds[] = 'small_bokorsword_#00';
            $disabled_builds[] = 'small_spiritmirage_#00';
            $disabled_builds[] = 'small_holyrain_#00';
        }

        if ($customConf['features']['shaman'] !== 'normal' && $customConf['features']['shaman'] !== 'both')
            $disabled_builds[] = 'small_spa4souls_#00';

        if ($nightwatch !== 'normal')
            $disabled_builds[] = 'small_round_path_#00';

        if ($crow_permissions) {
            foreach ($parser->get_array( 'professions', [] ) as $profession => $setting)
                switch ($setting) {
                    case 'include':
                        $disabled_jobs = array_filter( $disabled_jobs, fn(string $s) => $s !== $profession );
                        break;
                    case 'lock':
                        $disabled_jobs[] = $profession;
                        break;
                }

            $constructions = $parser->get_array( 'constructions', [] );
            if (!empty($constructions)) {

                $core_config = $this->conf->getTownConfigurationByType( $type );
                $initial_buildings  = $core_config->get( TownConf::CONF_BUILDINGS_CONSTRUCTED );
                $unlocked_buildings = array_unique( array_merge( $core_config->get( TownConf::CONF_BUILDINGS_UNLOCKED ), $disabled_builds) );
                $disabled_buildings = $core_config->get( TownConf::CONF_DISABLED_BUILDINGS );

                foreach ($constructions as $construction => $setting)
                    switch ($setting) {
                        case 'prebuild':
                            if (!in_array( $construction, $initial_buildings )) $initial_buildings[] = $construction;
                            $unlocked_buildings = array_filter( $unlocked_buildings, fn(string $s) => $s !== $construction );
                            $disabled_buildings = array_filter( $disabled_buildings, fn(string $s) => $s !== $construction );
                            break;
                        case 'prefound':
                            $initial_buildings = array_filter( $initial_buildings, fn(string $s) => $s !== $construction );
                            if (!in_array( $construction, $unlocked_buildings )) $unlocked_buildings[] = $construction;
                            $disabled_buildings = array_filter( $disabled_buildings, fn(string $s) => $s !== $construction );
                            break;
                        case 'findable':
                            $initial_buildings = array_filter( $initial_buildings, fn(string $s) => $s !== $construction );
                            $unlocked_buildings = array_filter( $unlocked_buildings, fn(string $s) => $s !== $construction );
                            $disabled_buildings = array_filter( $disabled_buildings, fn(string $s) => $s !== $construction );
                            break;
                        case 'lock':
                            $initial_buildings = array_filter( $initial_buildings, fn(string $s) => $s !== $construction );
                            $unlocked_buildings = array_filter( $unlocked_buildings, fn(string $s) => $s !== $construction );
                            if (!in_array( $construction, $disabled_buildings )) $disabled_buildings[] = $construction;
                            break;
                    }

                $customConf['initial_buildings']['replace']  = $initial_buildings;
                $customConf['unlocked_buildings']['replace'] = $unlocked_buildings;
                $disabled_builds = $disabled_buildings;
            }
        }

        $customConf['disabled_jobs']['replace']      = $disabled_jobs;
        $customConf['disabled_buildings']['replace'] = $disabled_builds;
        $customConf['disabled_roles']['replace']     = $disabled_roles;



        $managed_events = ($crow_permissions && $parser->get('event-management', true));

        $name_changers = [];

        if ($managed_events) {
            $event_conf = $this->conf->getEvent($parser->get('event-name', 'none'));
            $name_changers = $event_conf->get(EventConf::EVENT_MUTATE_NAME) ? [$event_conf->get(EventConf::EVENT_MUTATE_NAME)] : [];
        } else {
            $current_events = $this->conf->getCurrentEvents();
            $name_changers = array_values(
                array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
            );
        }

        if ($crow_permissions && $parser->get('unprivate', false))
            $town = $gf->createTown($townname, $lang, null, $type, $customConf, $seed, $name_changers[0] ?? null);
        else $town = ($gf->createTown($townname, $lang, null, 'custom', $customConf, $seed, $name_changers[0] ?? null))->setDeriveConfigFrom( $type );

        $town->setCreator($user);
        if(!empty($password)) $town->setPassword($password);
        $em->persist($town);

        $user_slots = array_filter($this->entity_manager->getRepository(User::class)->findBy(['id' => array_map(fn($a) => (int)$a, $parser->get_array('reserved_slots'))]), function(User $u) {
            return $u->getEmail() !== 'crow' && $u->getEmail() !== $u->getUsername() && !str_ends_with($u->getName(), '@localhost');
        });

        if (count($user_slots) !== count($parser->get_array('reserved_slots')))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        foreach ($user_slots as $user_slot)
            $this->entity_manager->persist((new TownSlotReservation())->setTown($town)->setUser($user_slot));

        try {
            $em->flush();
            $this->gps->recordTownCreated( $town, $user, 'custom' );
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if ($managed_events)
            $town->setManagedEvents(true);

        if (!$town->getManagedEvents()) {
            $current_events = $this->conf->getCurrentEvents();
            if (!empty(array_filter($current_events, fn(EventConf $e) => $e->active()))) {
                if (!$townHandler->updateCurrentEvents($town, $current_events)) {
                    $em->clear();
                } else try {
                    $em->persist($town);
                    $em->flush();
                } catch (Exception $e) {}
            }
        } else {

            $event = $parser->get('event-name', 'none');
            if (!in_array($event, ['afools','stpatrick','easter','halloween','arma','christmas']))
                $event = 'none';

            if ($event !== 'none') {
                if (!$townHandler->updateCurrentEvents($town, [$this->conf->getEvent($event)])) {
                    $em->clear();
                } else try {
                    $em->persist($town);
                    $em->flush();
                } catch (Exception $e) {}
            }

        }

        if ($parser->get('rk-event-tag', false) && $crow_permissions) {
            $em->persist($town->getRankingEntry()->setEvent(true));
            $em->flush();
        }

        if ($incarnated) {
            $citizen = $gf->createCitizen($town, $user, $error, $all);
            if (!$citizen) return AjaxResponse::error($error);
            try {
                $em->persist($citizen);
                $em->flush();
                foreach ($all as $new_citizen)
                    $this->gps->recordCitizenJoined( $new_citizen, $new_citizen === $citizen ? 'create' : 'follow' );
            } catch (Exception $e) {
              return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

            try {
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        return AjaxResponse::success( true, ['url' => $incarnated ? $this->generateUrl('game_jobs') : $this->generateUrl('ghost_welcome')] );
    }

    /**
     * @Route("api/ghost/join", name="api_join")
     * @Semaphore(scope="global")
     * Process the user joining a town
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param ConfMaster $conf
     * @param LogTemplateHandler $log
     * @param TownHandler $townHandler
     * @return Response
     */
    public function join_api(JSONRequestParser $parser, GameFactory $factory,
                             ConfMaster $conf, LogTemplateHandler $log, TownHandler $townHandler) {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('town')) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        $town_id = (int)$parser->get('town', -1);
        if ($town_id <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find( $town_id );
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
            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        try {
            foreach ($all as $new_citizen)
                $this->gps->recordCitizenJoined( $new_citizen, $new_citizen === $citizen ? 'join' : 'follow' );
            $this->entity_manager->flush();
        }
        catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        $current_town_events = $this->conf->getCurrentEvents($town);
        if (!empty(array_filter($current_town_events,fn(EventConf $e) => $e->active()))) {
            if (!$townHandler->updateCurrentEvents($town, $current_town_events))
                $this->entity_manager->clear();
            else {
                $this->entity_manager->persist($town);
                $this->entity_manager->flush();
            }
        }

        if (!$town->isOpen()){
            // Target town is closed, let's add special voting actions !
            $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();
            /** @var CitizenRole $role */
            foreach ($roles as $role){
                /** @var SpecialActionPrototype $special_action */
                $special_action = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => 'special_vote_' . $role->getName()]);
                /** @var Citizen $citizen */
                foreach ($town->getCitizens() as $citizen){
                    if(!$citizen->getProfession()->getHeroic()) continue;

                    if(!$citizen->getSpecialActions()->contains($special_action)) {
                        $citizen->addSpecialAction($special_action);
                        $this->entity_manager->persist($citizen);
                    }
                }
            }
        }

        return AjaxResponse::success();
    }

    public function getUserTownClassAccess(MyHordesConf $conf, ?User $user = null): array {
        $user = $user ?? $this->getUser();

        if ($this->user_handler->checkFeatureUnlock( $user, 'f_sptkt', false ) || $user->getRightsElevation() >= User::USER_LEVEL_CROW)
            return [
                'small' => true,
                'remote' => true,
                'panda' => true,
                'custom' => true,
            ];

        $sp = $this->user_handler->fetchSoulPoints($user);

        return [
            'small' =>
                ($sp < $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )
                || $sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )),
            'remote' => ($sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )),
            'panda' => ($sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 )),
            'custom' => 'maybe',
        ];
    }

    public function getUserTownClassAccessLimitReason(MyHordesConf $conf): array {
        return [
            'small' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )], 'ghost' ),
            'remote' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 )], 'ghost' ),
            'panda' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 )], 'ghost' ),
            'custom' => null,
        ];
    }

    public function getTownClassAccessLimits(MyHordesConf $conf): array {
        return [
            'remote' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 100 ),
            'panda'  => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 500 ),
            'custom' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 ),
        ];
    }
}
