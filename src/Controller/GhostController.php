<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Entity\AccountRestriction;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\HeroSkillPrototype;
use App\Entity\MayorMark;
use App\Entity\Season;
use App\Entity\TeamTicket;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\User;
use App\Enum\Configuration\MyHordesSetting;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\HookExecutor;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Service\UserHandler;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Order;
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

    public function __construct(EntityManagerInterface $em, UserHandler $uh, TimeKeeperService $tk, TranslatorInterface $translator, ConfMaster $conf, CitizenHandler $ch, InventoryHandler $ih, HookExecutor $hookExecutor)
    {
        parent::__construct($conf, $em, $tk, $ch, $ih, $translator, $hookExecutor);
        $this->user_handler = $uh;
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
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
        $cap = $this->conf->getGlobalConf()->get(MyHordesSetting::AntiGriefForeignCap);
        $tickets = $user->getTeamTicketsFor( $season, '!' )->count();
        $cap_left = ($cap >= 0) ? max(0, $cap - $tickets) : -1;

        $mayor_block = $em->getRepository(MayorMark::class)->matching( (new Criteria())
            ->where( new Comparison( 'user', Comparison::EQ, $user )  )
            ->andWhere( new Comparison( 'expires', Comparison::GT, new \DateTime() ) )
            ->orderBy( ['expires' => Order::Descending] )
            ->setMaxResults(1)
        )->first();

        return $this->render( 'ajax/ghost/intro.html.twig', $this->addDefaultTwigArgs(null, [
            'cap_left'           => $cap_left,
            'team_members'       => $user->getTeam() ? $this->entity_manager->getRepository(User::class)->count(['team' => $user->getTeam()]) : 0,
            'team_souls'         => $user->getTeam() ? $this->entity_manager->getRepository(TeamTicket::class)->count(['team' => $user->getTeam()]) : 0,
            'warnCoaInactive'    => $count > 0 && !$active,
            'warnCoaNotComplete' => $count > 0 && (count($coa_members) + 1) < $count,
            'warnCoaEmpty'       => $count > 1 && empty($coa_members),
            'coa'                => $coa_members,
            'cdm_level'          => $cdm_lock ? 2 : ( $cdm_warn ? 1 : 0 ),
            'canCreateTown' => true,
            'mayorBlock' => $mayor_block,
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
     * @throws Exception
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
                ->orderBy(['end' => Order::Descending])
                ->setMaxResults(1)
        );
        $last_game_sp = $last_game_sp->isEmpty() ? 0 : $last_game_sp->first()->getPoints();
        $all_sp = $this->user_handler->fetchSoulPoints($this->getUser(), true);
        $town_limit = $this->conf->getGlobalConf()->get(MyHordesSetting::SoulPointRequirementRemote);

        $has_skills = $this->entity_manager->getRepository(HeroSkillPrototype::class)->count(['enabled' => true, 'legacy' => false]) > 0;

        return $this->render( 'ajax/ghost/donate.html.twig', [
            'exp' => $town_limit > 0 && ($all_sp >= $town_limit && ($all_sp - $last_game_sp) < $town_limit),
            'h1' => $all_sp >= 100 && ($all_sp - $last_game_sp) < 100,
            'h2' => $all_sp >= 200 && ($all_sp - $last_game_sp) < 200,
            'hxp' => $has_skills ? $request->query->get('t', 0) : 0,
        ] );
    }

    /**
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/ghost/create_town', name: 'ghost_create_town', defaults: ['tab' => 'private'])]
    #[Route(path: 'jx/ghost/create/private', name: 'ghost_create_private_town', defaults: ['tab' => 'private'])]
    #[Route(path: 'jx/ghost/create/public', name: 'ghost_create_public_town', defaults: ['tab' => 'public'])]
    public function create_town(EntityManagerInterface $em, string $tab): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $limit = $this->conf->getGlobalConf()->get(MyHordesSetting::TownLimitMaxPrivate);

        $open_town = $this->entity_manager->getRepository(Town::class)
            ->findBy(['creator' => $user, 'mayor' => true]);

        return $this->render( 'ajax/ghost/create_town_rc.html.twig', $this->addDefaultTwigArgs(null, [
            'tab' => $tab,
            'town_limit' => $limit,
            'limit_reached' => count(array_filter($em->getRepository(Town::class)->findOpenTown(), fn(Town $t) => $t->getType()->getName() === 'custom')) >= $limit,
            'townClasses' => $em->getRepository(TownClass::class)->findBy(['hasPreset' => true]),
            'professions' => array_filter( $em->getRepository(CitizenProfession::class)->findAll(), fn(CitizenProfession $pro) => $pro->getName() !== CitizenProfession::DEFAULT ),
            'constructions' => $em->getRepository(BuildingPrototype::class)->findAll(),
            'langs' => $this->generatedLangs,
            'mayorTowns' => $open_town,
            'canMayorTowns' => $user->getAllSoulPoints() >= 250,
            'mayorBlocked' => $em->getRepository(MayorMark::class)->matching( (new Criteria())
                ->where( new Comparison( 'user', Comparison::EQ, $user )  )
                ->andWhere( new Comparison( 'expires', Comparison::GT, new \DateTime() ) )
                ->orderBy( ['expires' => Order::Descending] )
                ->setMaxResults( 1 )
            )->first() ?: null,
            'tooMany' => $em->getRepository(Town::class)->count(['mayor' => true]) > 14,
        ]));
    }
}
