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
use App\Entity\HeroSkillPrototype;
use App\Entity\Season;
use App\Entity\SpecialActionPrototype;
use App\Entity\TeamTicket;
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
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use App\Structures\TownSetup;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method User|null getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(only_ghost: true)]
class GhostController extends CustomAbstractController
{
    private UserHandler $user_handler;
    const ErrorWrongTownPassword          = ErrorHelper::BaseGhostErrors + 1;
    private GameProfilerService $gps;

    public function __construct(EntityManagerInterface $em, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih, GameProfilerService $gps, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->gps = $gps;
        $this->user_handler = $uh;
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/ghost/welcome', name: 'ghost_welcome')]
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

        $season = $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]);
        $cap = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_FOREIGN_CAP, 3);
        $tickets = $user->getTeamTicketsFor( $season, '!' )->count();
        $cap_left = ($cap >= 0) ? max(0, $cap - $tickets) : -1;

        return $this->render( 'ajax/ghost/intro.html.twig', $this->addDefaultTwigArgs(null, [
            'cap_left'           => $cap_left,
            'team_members'       => $user->getTeam() ? $this->entity_manager->getRepository(User::class)->count(['team' => $user->getTeam()]) : 0,
            'team_souls'         => $user->getTeam() ? $this->entity_manager->getRepository(TeamTicket::class)->count(['team' => $user->getTeam()]) : 0,
            'warnCoaInactive'    => $count > 0 && !$active,
            'warnCoaNotComplete' => $count > 0 && (count($coa_members) + 1) < $count,
            'warnCoaEmpty'       => $count > 1 && empty($coa_members),
            'coa'                => $coa_members,
            'cdm_level'          => $cdm_lock ? 2 : ( $cdm_warn ? 1 : 0 ),
            'townClasses' => $em->getRepository(TownClass::class)->findAll(),
            'userCanJoin' => $this->getUserTownClassAccess($this->conf->getGlobalConf()),
            'userCantJoinReason' => $this->getUserTownClassAccessLimitReason($this->conf->getGlobalConf()),
            'sp_limits' => $this->getTownClassAccessLimits($this->conf->getGlobalConf()),
            'canCreateTown' => true,
        ] ));
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/ghost/welcome_soul', name: 'welcome_soul')]
    public function welcome_soul(): Response
    {
        return $this->render( 'ajax/ghost/welcome.html.twig' );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/ghost/postgame', name: 'postgame')]
    public function postgame_screen(Request $request): Response
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

        $has_skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->count(['enabled' => true, 'legacy' => false]) > 0;

        return $this->render( 'ajax/ghost/donate.html.twig', [
            'exp' => $town_limit > 0 && ($all_sp >= $town_limit && ($all_sp - $last_game_sp) < $town_limit),
            'hxp' => $has_skills ? $request->query->get('t', 0) : 0,
        ] );
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/ghost/create_town', name: 'ghost_create_town')]
    #[Route(path: 'jx/ghost/create_town_rc', name: 'ghost_create_town_rc')]
    public function create_town(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $limit = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOWNS_MAX_PRIVATE, 10);

        return $this->render( 'ajax/ghost/create_town_rc.html.twig', $this->addDefaultTwigArgs(null, [
            'town_limit' => $limit,
            'limit_reached' => count(array_filter($em->getRepository(Town::class)->findOpenTown(), fn(Town $t) => $t->getType()->getName() === 'custom')) >= $limit,
            'townClasses' => $em->getRepository(TownClass::class)->findBy(['hasPreset' => true]),
            'professions' => array_filter( $em->getRepository(CitizenProfession::class)->findAll(), fn(CitizenProfession $pro) => $pro->getName() !== CitizenProfession::DEFAULT ),
            'constructions' => $em->getRepository(BuildingPrototype::class)->findAll(),
            'langs' => $this->generatedLangs
        ]));
    }

    /**
     * Process the user joining a town
     * @param JSONRequestParser $parser
     * @param GameFactory $factory
     * @param ConfMaster $conf
     * @param LogTemplateHandler $log
     * @param TownHandler $townHandler
     * @return Response
     */
    #[Route(path: 'api/ghost/join', name: 'api_join')]
    #[Semaphore(scope: 'global')]
    public function join_api(JSONRequestParser $parser, GameFactory $factory,
                             ConfMaster $conf, LogTemplateHandler $log, TownHandler $townHandler) {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if ($this->user_handler->isRestricted( $user, AccountRestriction::RestrictionGameplayLang ))
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

        if (!$factory->userCanEnterTown( $town, $user ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen = $factory->createCitizen($town, $user, $error, $all);
        if (!$citizen) return AjaxResponse::error($error);

        try {
            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
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
            'remote' => ($sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 0 )),
            'panda' => ($sp >= $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 200 )),
            'custom' => 'maybe',
        ];
    }

    public function getUserTownClassAccessLimitReason(MyHordesConf $conf): array {
        return [
            'small' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_BACK_TO_SMALL, 500 )], 'ghost' ),
            'remote' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 0 )], 'ghost' ),
            'panda' => $this->translator->trans( 'Du benötigst mindestens {sp} Seelenpunkte, um dieser Stadt beitreten zu können. Sammele Seelenpunkte, indem du andere Städte spielst.', ['sp' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 200 )], 'ghost' ),
            'custom' => null,
        ];
    }

    public function getTownClassAccessLimits(MyHordesConf $conf): array {
        return [
            'remote' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_REMOTE, 0 ),
            'panda'  => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_PANDA, 200 ),
            'custom' => $conf->get( MyHordesConf::CONF_SOULPOINT_LIMIT_CUSTOM, 1000 ),
        ];
    }
}
