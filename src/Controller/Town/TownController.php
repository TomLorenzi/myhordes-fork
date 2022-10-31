<?php

namespace App\Controller\Town;

use App\Annotations\GateKeeperProfile;
use App\Controller\InventoryAwareController;
use App\Entity\AccountRestriction;
use App\Entity\ActionCounter;
use App\Entity\ActionEventLog;
use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\BuildingVote;
use App\Entity\Citizen;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use App\Entity\CitizenWatch;
use App\Entity\Complaint;
use App\Entity\ComplaintReason;
use App\Entity\ExpeditionRoute;
use App\Entity\ForumThreadSubscription;
use App\Entity\HomeIntrusion;
use App\Entity\Item;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\LogEntryTemplate;
use App\Entity\PictoPrototype;
use App\Entity\PrivateMessage;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Entity\UserGroupAssociation;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Enum\AdminReportSpecification;
use App\Enum\ZoneActivityMarkerType;
use App\Service\BankAntiAbuseService;
use App\Service\ConfMaster;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\RateLimitingFactoryProvider;
use App\Structures\CitizenInfo;
use App\Structures\ItemRequest;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use App\Translation\T;
use App\Response\AjaxResponse;
use App\Service\AdminHandler;
use App\Service\ErrorHelper;
use App\Service\TownHandler;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
 * @method User getUser()
 */
class TownController extends InventoryAwareController
{
    const ErrorWellEmpty         = ErrorHelper::BaseTownErrors + 1;
    const ErrorWellLimitHit      = ErrorHelper::BaseTownErrors + 2;
    const ErrorWellNoWater       = ErrorHelper::BaseTownErrors + 3;
    const ErrorDoorAlreadyClosed = ErrorHelper::BaseTownErrors + 4;
    const ErrorDoorAlreadyOpen   = ErrorHelper::BaseTownErrors + 5;
    const ErrorNotEnoughRes      = ErrorHelper::BaseTownErrors + 6;
    const ErrorAlreadyUpgraded   = ErrorHelper::BaseTownErrors + 7;
    const ErrorComplaintLimitHit = ErrorHelper::BaseTownErrors + 8;
    const ErrorAlreadyFinished   = ErrorHelper::BaseTownErrors + 9;
    const ErrorTownChaos         = ErrorHelper::BaseTownErrors + 10;
    const ErrorAlreadyThrown     = ErrorHelper::BaseTownErrors + 11;
    const ErrorAlreadyWatered    = ErrorHelper::BaseTownErrors + 12;
    const ErrorAlreadyCooked     = ErrorHelper::BaseTownErrors + 13;
    const ErrorAlreadyGhoul      = ErrorHelper::BaseTownErrors + 14;

    protected function get_needed_votes(): array {
        $town = $this->getActiveCitizen()->getTown();
        /** @var CitizenRole[] $roles */
        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();

        $votesNeeded = array();
        foreach ($roles as $role) {
            $votesNeeded[$role->getName()] = $this->town_handler->is_vote_needed($town, $role) ? $role : false;
        }

        return $votesNeeded;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];

        $addons = [];
        $town = $this->getActiveCitizen()->getTown();

        $data["builtbuildings"] = array();

        if ($this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false) && $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
            $addons['battlement'] = [T::__('Wächt', 'game'), 'town_nightwatch', 3];

        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {

            if ($b->getPrototype()->getMaxLevel() > 0)
                $addons['upgrade']  = [T::__('Verbesserung des Tages (building)', 'game'), 'town_upgrades', 0];

            if ($b->getPrototype()->getName() === 'item_tagger_#00')
                $addons['watchtower'] = [T::__('Wachturm', 'game'), 'town_watchtower', 1];

            if ($b->getPrototype()->getName() === 'small_refine_#00')
                $addons['workshop'] = [T::__('Werkstatt (building)', 'game'), 'town_workshop', 2];

            if (($b->getPrototype()->getName() === 'small_round_path_#00' && !$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false)) && $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
                $addons['battlement'] = [T::__('Wächt', 'game'), 'town_nightwatch', 3];

            if ($b->getPrototype()->getName() === 'small_trash_#00')
                $addons['dump'] = [T::__('Müllhalde', 'game'), 'town_dump', 4];

            if ($b->getPrototype()->getName() === 'item_courroie_#00')
                $addons['catapult'] = [T::__('Katapult', 'game'), 'town_catapult', 5];
            

            $data["builtbuildings"][] = $b;

        }

        $data['addons'] = $addons;
        $data['home'] = $this->getActiveCitizen()->getHome();
        $data['chaos'] = $town->getChaos();
        $data['town'] = $town;

        if ($section == "citizens")
            $data['votesNeeded'] = $this->get_needed_votes();

        $data["new_message"] = $this->citizen_handler->hasNewMessage($this->getActiveCitizen());
        $data['can_do_insurrection'] = $this->getActiveCitizen()->getBanished() && !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), "tg_insurrection") && $town->getInsurrectionProgress() < 100;
        $data['has_insurrection_part'] = $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), "tg_insurrection");
        $data['has_battlement']    = $this->town_handler->getBuilding($town, 'small_round_path_#00') && !$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false) && $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true);
        $data['act_as_battlement'] = $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false) && $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true);
        return parent::addDefaultTwigArgs( $section, $data );
    }

    /**
     * @Route("jx/town/dashboard", name="town_dashboard")
     * @param TownHandler $th
     * @return Response
     */
    public function dashboard(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

        $citizens = $town->getCitizens();
        $alive = 0;
        foreach ($citizens as $citizen) {
            if($citizen->getAlive())
                $alive++;
        }

        $item_def_factor = 1;
        $has_battlement = false;
        $has_watchtower = false;
        $has_levelable_building = false;
        foreach ($town->getBuildings() as $building) {
            if (!$building->getComplete())
                continue;

            if ($building->getPrototype()->getName() === 'item_meca_parts_#00')
                $item_def_factor += (1+$building->getLevel()) * 0.5;

            if($building->getPrototype()->getName() === 'small_round_path_#00')
                $has_battlement = true;

            if($building->getPrototype()->getName() === 'item_tagger_#00')
                $has_watchtower = true;

            if ($building->getPrototype()->getMaxLevel() > 0)
                $has_levelable_building = true;
        }

        $item_def_count = $this->inventory_handler->countSpecificItems($town->getBank(),$this->inventory_handler->resolveItemProperties( 'defence' ), false, false);

        $display_home_upgrade = false;
        foreach ($citizens as $citizen) {
            if($citizen->getHome()->getPrototype()->getLevel() > $this->getActiveCitizen()->getHome()->getPrototype()->getLevel()){
                $display_home_upgrade = true;
                break;
            }
        }

        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();
        $has_voted = array();

        if((!$town->isOpen() || $town->getForceStartAhead()) && !$town->getChaos())
            foreach ($roles as $role)
                $has_voted[$role->getName()] = ($this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($this->getActiveCitizen(), $role) !== null);

        $can_edit_blackboard = $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'dictator') && !$this->getActiveCitizen()->getBanished();
        $has_dictator = $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'dictator');
        
        $sb = $this->user_handler->getShoutbox($this->getUser());
        $messages = false;
        if ($sb) {
            $last_entry = $this->entity_manager->getRepository(ShoutboxEntry::class)->findOneBy(['shoutbox' => $sb], ['timestamp' => 'DESC', 'id' => 'DESC']);
            if ($last_entry) {
                $marker = $this->entity_manager->getRepository(ShoutboxReadMarker::class)->findOneBy(['user' => $this->getUser()]);
                if (!$marker || $last_entry !== $marker->getEntry()) $messages = true;
            }
        }

        $has_zombie_est_today    = !empty($this->town_handler->getBuilding($town, 'item_tagger_#00'));
        $has_zombie_est_tomorrow = !empty($this->town_handler->getBuilding($town, 'item_tagger_#02'));

        $estims = $this->town_handler->get_zombie_estimation($town);
        $zeds_today = [
            $has_zombie_est_today, // Can see
            $estims[0]->getMin(), // Min
            $estims[0]->getMax(),  // Max
            round($estims[0]->getEstimation()*100) // Progress
        ];
        $zeds_tomorrow = [
            $has_zombie_est_tomorrow,
            isset($estims[1]) ? $estims[1]->getMin() : 0,
            isset($estims[1]) ? $estims[1]->getMax() : 0,
            isset($estims[1]) ? round($estims[1]->getEstimation()*100) : 0
        ];

        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay());
        $has_estimated = ($est && ($est->getCitizens()->contains($this->getActiveCitizen()))) || (!$has_zombie_est_tomorrow && $zeds_today[3] >= 100) || ($has_zombie_est_tomorrow && $zeds_tomorrow[3] >= 100);

        $additional_bullets = [];
        $additional_situation = [];
        foreach ($this->conf->getCurrentEvents($town) as $e)
            $e->hook_dashboard($town, $additional_bullets, $additional_situation);

        $user_coalition = $this->entity_manager->getRepository(UserGroupAssociation::class)->findOneBy( [
            'user' => $this->getActiveCitizen()->getUser(),
            'associationType' => [UserGroupAssociation::GroupAssociationTypeCoalitionMember, UserGroupAssociation::GroupAssociationTypeCoalitionMemberInactive]
        ]);

        $user_invitations = $user_coalition ? [] : $this->entity_manager->getRepository(UserGroupAssociation::class)->findBy( [
            'user' => $this->getActiveCitizen()->getUser(),
            'associationType' => UserGroupAssociation::GroupAssociationTypeCoalitionInvitation
        ]);

        $is_watcher = false;
        foreach ($this->entity_manager->getRepository(CitizenWatch::class)->findCurrentWatchers($town) as $watcher)
            if ($watcher->getCitizen()->getId() === $this->getActiveCitizen()->getId())
                $is_watcher = true;

        return $this->render( 'ajax/game/town/dashboard.html.twig', $this->addDefaultTwigArgs(null, [
            'town' => $town,
            'is_watcher' => $is_watcher,
            'def' => $this->town_handler->calculate_town_def($town, $defSummary),
            'zeds_today'    => $zeds_today,
            'zeds_tomorrow' => $zeds_tomorrow,
            'living_citizens' => $alive,
            'def_summary' => $defSummary,
            'item_def_count' => $item_def_count,
            'item_def_factor' => $item_def_factor,
            'has_battlement' => $has_battlement,
            'has_watchtower' => $has_watchtower,
            'votes_needed' => $this->get_needed_votes(),
            'has_voted' => $has_voted,
            'has_levelable_building' => $has_levelable_building,
            'active_citizen' => $this->getActiveCitizen(),
            'has_estimated' => $has_estimated,
            'has_visited_forum' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_chk_forum'),
            'has_been_active' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), ['tg_chk_workshop', 'tg_chk_movewb', 'tg_chk_build']),
            'has_pending_coa_invite' => !empty($user_invitations),
            'display_home_upgrade' => $display_home_upgrade,
            'has_upgraded_house' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_home_upgrade'),
            'can_edit_blackboard' => $can_edit_blackboard,
            'has_dictator' => $has_dictator,
            'new_coa_message' => $messages,
            'additional_bullet_points' => $additional_bullets,
            'additional_situation_points' => $additional_situation,
            'is_dehydrated' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'thirst2'),
            'bbe_id' => $this->entity_manager->getRepository(BlackboardEdit::class)->findOneBy(['town' => $town], ['id' => 'DESC'])?->getId() ?? -1,
        ]) );
    }

    /**
     * @Route("jx/town/visit/{id}/headshot", name="town_visit_headshot", requirements={"id"="\d+"})
     * @param int $id Citizen's ID
     * @param AdminHandler $admh
     * @return Response
     */
    public function visitHeadshot(int $id, AdminHandler $admh): Response
    {
        $sourceUserId = $this->getUser()->getId();
        $message = $admh->headshot($sourceUserId, $id);

        $this->addFlash('notice', $message);
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/visit/{id}", name="town_visit", requirements={"id"="\d+"})
     * @param int $id
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function visit(int $id, EntityManagerInterface $em): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        if ($id === $this->getActiveCitizen()->getId())
            return $this->redirect($this->generateUrl('town_house'));

        /** @var Citizen $c */
        $c = $em->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
            return $this->redirect($this->generateUrl('town_dashboard'));

        $home = $c->getHome();

        $this->town_handler->calculate_home_def($home, $summary);
        $deco = $this->citizen_handler->getDecoPoints($c);

        $town = $this->getActiveCitizen()->getTown();
        $lastActionTimestamp = $c->getLastActionTimestamp();
        $date = (new DateTime())->setTimestamp($lastActionTimestamp);

        // Getting delta time between now and the last action
        $time = time() - $lastActionTimestamp; 
        $time = abs($time); 

        // Less than 1min ago
        if ($time <= 60)
            $lastActionText = $this->translator->trans('vor wenigen Augenblicken', [], 'game');
        // At least 5 hours ago, same day in the morning
        elseif ($time > 18000 && $date->format('d') === (new DateTime())->format('d') && (int)date('H', $lastActionTimestamp) < 12)
            $lastActionText = $this->translator->trans('heute morgen um {H}:{i}', [
                '{H}' => date('H', $lastActionTimestamp),
                '{i}' => date('i', $lastActionTimestamp),
            ], 'game');
        // At least 5 hours ago, same day in the afternoon
        elseif ($time > 18000 && $date->format('d') === (new DateTime())->format('d') && (int)date('H', $lastActionTimestamp) < 19)
            $lastActionText = $this->translator->trans('heute nachmittag um {H}:{i}', [
                '{H}' => date('H', $lastActionTimestamp),
                '{i}' => date('i', $lastActionTimestamp),
            ], 'game');
        // Same day, use relative format if no other notation applies
        elseif ($date->format('d') === (new DateTime())->format('d')) {
            // Tableau des unités et de leurs valeurs en secondes
            $times = [
                3600 =>  T::__('Stunde(n)', 'game'),
                60   =>  T::__('Minute(n)', 'game'),
                1    =>  T::__('Sekunde(n)', 'game')
            ];

            foreach ($times as $seconds => $unit) {
                $delta = floor($time / $seconds);

                if ($delta >= 1) {
                    $unit = $this->translator->trans($unit, [], 'game');
                    $lastActionText = $this->translator->trans('vor {time}', ['{time}' => "$delta $unit"], 'game');
                    break;
                }
            }
        }
        // Yesterday
        elseif ((int)$date->format('d') === ((int)(new DateTime())->format('d') - 1))
            $lastActionText = $this->translator->trans('gestern um {H}:{i}', [
                '{H}' => date('H', $lastActionTimestamp),
                '{i}' => date('i', $lastActionTimestamp),
            ], 'game');
        // Default, full notation
        else
            // If it was more than 3 hours, or if the day changed, let's get the full date/time format
            $lastActionText = $this->translator->trans('am {d}.{m}.{Y}, um {H}:{i}', [
                '{d}' => date('d', $lastActionTimestamp),
                '{m}' => date('m', $lastActionTimestamp),
                '{Y}' => date('Y', $lastActionTimestamp),
                '{H}' => date('H', $lastActionTimestamp),
                '{i}' => date('i', $lastActionTimestamp),
            ], 'game');

        $cc = 0;
        foreach ($c->getTown()->getCitizens() as $citizen)
            if ($citizen->getAlive() && !$citizen->getZone() && $citizen->getId() !== $c->getId() && $c->getId() !== $c->getId()) $cc++;
        $cc = (float)$cc / (float)$c->getTown()->getPopulation(); // Completely arbitrary

        $hidden = ($c->getAlive() && (bool)($em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($home,
            $em->getRepository(CitizenHomeUpgradePrototype::class)->findOneBy(['name' => 'curtain'])
        )));

        $is_injured    = $this->citizen_handler->isWounded($c);
        $is_infected   = $this->citizen_handler->hasStatusEffect($c, 'infection');
        $is_thirsty    = $this->citizen_handler->hasStatusEffect($c, "thirst2");
        $is_addicted   = $this->citizen_handler->hasStatusEffect($c, 'addict');
        $is_terrorised = $this->citizen_handler->hasStatusEffect($c, 'terror');
        $has_job       = $c->getProfession()->getName() != 'none';
        $is_admin      = $c->getUser()->getRightsElevation() >= User::USER_LEVEL_ADMIN;
        $already_stolen = $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_steal') && !$this->getActiveCitizen()->getTown()->getChaos();

        $hasClairvoyance = false;
        $clairvoyanceLevel = 0;

        if ($this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'clairvoyance') && $this->getActiveCitizen()->getProfession()->getHeroic()) {
            $hasClairvoyance = true;
            $clairvoyanceLevel = $this->citizen_handler->getActivityLevel($c);
        }

        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->gte('severity', Complaint::SeverityBanish));
        $criteria->andWhere($criteria->expr()->eq('culprit', $c));

        $recycleAP = $this->getTownConf()->get(TownConf::CONF_MODIFIER_RECYCLING_AP, 15);
        $can_recycle = !$c->getAlive() && $c->getHome()->getPrototype()->getLevel() > 1 && $c->getHome()->getRecycling() < $recycleAP;
        $protected = $this->citizen_handler->houseIsProtected($c, true);

        $intrusion = $this->entity_manager->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $this->getActiveCitizen(), 'victim' => $c]);

        /** @var Complaint $active_complaint */
        $active_complaint = $this->entity_manager->getRepository(Complaint::class)->findByCitizens( $this->getActiveCitizen(), $c );
        $complaint_possible = !$c->getBanished() || $this->town_handler->getBuilding( $this->getActiveCitizen()->getTown(), 'r_dhang_#00', true ) || $this->town_handler->getBuilding( $this->getActiveCitizen()->getTown(), 'small_fleshcage_#00', true ) || $this->town_handler->getBuilding( $this->getActiveCitizen()->getTown(), 'small_eastercross_#00', true );

        return $this->render( 'ajax/game/town/home_foreign.html.twig', $this->addDefaultTwigArgs('citizens', [
            'owner' => $c,
            'can_attack' => !$this->getActiveCitizen()->getBanished() && !$this->citizen_handler->isTired($this->getActiveCitizen()) && $this->getActiveCitizen()->getAp() >= $this->getTownConf()->get( TownConf::CONF_MODIFIER_ATTACK_AP, 5 ),
            'can_devour' => !$this->getActiveCitizen()->getBanished() && $this->getActiveCitizen()->hasRole('ghoul'),
            'caught_chance' => $cc,
            'allow_devour' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_ghoul_eat'),
            'allow_devour_corpse' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_ghoul_corpse'),
            'home' => $home,
            'actions' => $this->getItemActions(),
            'can_complain' => !$this->getActiveCitizen()->getBanished() && $complaint_possible,
            'can_undo_complain' => $complaint_possible && $active_complaint?->getSeverity() > 0,
            'complaint' => $active_complaint,
            'complaints' => $this->entity_manager->getRepository(Complaint::class)->matching( $criteria ),
            'complaintreasons' => $this->entity_manager->getRepository(ComplaintReason::class)->findAll(),
            'chest' => $home->getChest(),
            'chest_size' => $this->inventory_handler->getSize($home->getChest()),
            'has_cremato' => $this->town_handler->getBuilding($town, 'item_hmeat_#00', true) !== null,
            'lastActionText' => $lastActionText,
            'def' => $summary,
            'deco' => $deco,
            'time' => $time,
            'is_injured' => $is_injured,
            'is_infected' => $is_infected,
            'is_thirsty' => $is_thirsty,
            'is_addicted' => $is_addicted,
            'is_terrorised' => $is_terrorised,
            'is_outside_unprotected' => $c->getZone() !== null && !$protected,
            'has_job' => $has_job,
            'is_admin' => $is_admin,
            'log' =>  $c->getAlive() ? $this->renderLog( -1, $c, false, null, 5 )->getContent() : '',
            'day' => $c->getTown()->getDay(),
            'already_stolen' => $already_stolen,
            'hidden' => $hidden,
            'protect' => $protected,
            'hasClairvoyance' => $hasClairvoyance,
            'clairvoyanceLevel' => $clairvoyanceLevel,
            'attackAP' => $this->getTownConf()->get( TownConf::CONF_MODIFIER_ATTACK_AP, 5 ),
            'can_recycle' => $can_recycle,
            'has_omniscience' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'omniscience'),
            'intruding' => $intrusion === null ? 0 : ( $intrusion->getSteal() ? 1 : -1 ),
            'recycle_ap' => $recycleAP
        ]) );
    }

    /**
     * @Route("api/town/visit/{id}/log", name="town_visit_log_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_visit_api(int $id, JSONRequestParser $parser): Response {
        if ($id === $this->getActiveCitizen()->getId())
            return $this->redirect($this->generateUrl('town_house_log_controller'));

        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);

        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
            $c = null;

        return $this->renderLog((int)$parser->get('day', -1), $c, false, null, $c ===  null ? 0 : null);
    }

    /**
     * @Route("api/town/visit/{id}/dispose", name="town_visit_dispose_controller")
     * @param int $id
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param ItemFactory $if
     * @return Response
     */
    public function dispose_visit_api(int $id, EntityManagerInterface $em, JSONRequestParser $parser, ItemFactory $if): Response {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $ac = $this->getActiveCitizen();

        /** @var Citizen $c */
        $c = $em->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getAlive())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        if (!$c->getHome()->getHoldsBody()) {
            if ($c->getDisposed() === Citizen::Thrown) {
                return AjaxResponse::error(self::ErrorAlreadyThrown);
            } else if ($c->getDisposed() === Citizen::Watered) {
                return AjaxResponse::error(self::ErrorAlreadyWatered);
            } else if ($c->getDisposed() === Citizen::Cooked) {
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
            } else if ($c->getDisposed() === Citizen::Ghoul) {
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
            } else  {
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
            }
        }

        $action = (int)$parser->get('action');

        if ($action < 1 || $action > 3)
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $spawn_items = [];
        $pictoName = "";
        $message = "";
        switch ($action) {
            case Citizen::Thrown:
                // Thrown outside
                if ($ac->getAp() < 2 || $this->citizen_handler->isTired( $ac ))
                    return AjaxResponse::error( ErrorHelper::ErrorNoAP );
                $this->citizen_handler->setAP($ac, true, -2);
                $pictoName = "r_cgarb_#00";
                $message = $this->translator->trans('Du hast die Leiche von {disposed} außerhalb der Stadt entsorgt. Eine gute Sache, die Sie getan haben!', ['{disposed}' => '<span>' . $c->getName() . '</span>'], 'game');
                $c->setDisposed(Citizen::Thrown);
                $c->addDisposedBy($ac);
                break;
            case Citizen::Watered:
                // Watered
                $items = $this->inventory_handler->fetchSpecificItems( [$ac->getInventory(),$ac->getHome()->getChest()], [new ItemRequest('water_#00', 1, null, false)] );
                if (!$items) return AjaxResponse::error(ErrorHelper::ErrorItemsMissing );
                $this->inventory_handler->forceRemoveItem( $items[0] );
                $pictoName = "r_cwater_#00";
                $message = $this->translator->trans('Der Körper verflüssigte sich zu einer ekelerregenden, übel riechenden Pfütze. Deine Schuhe haben ganz schön was abgekriegt, das steht fest...', [], 'game');
                $c->setDisposed(Citizen::Watered);
                $c->addDisposedBy($ac);
                break;
            case Citizen::Cooked:
                // Cooked
                $town = $ac->getTown();
                if (!$this->town_handler->getBuilding($town, 'item_hmeat_#00', true))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                $spawn_items[] = [ 'item' => $em->getRepository( ItemPrototype::class )->findOneBy( ['name' => 'hmeat_#00'] ), 'count' => 4 ];
                $pictoName = "r_cooked_#00";
                $message = $this->translator->trans('Sie brachten die Leiche von {disposed} zum Kremato-Cue. Man bekommt {ration} Rationen davon...  Aber zu welchem Preis?', ['{disposed}' => '<span>' . $c->getName() . '</span>','{ration}' => '<span>4</span>'], 'game');
                $c->setDisposed(Citizen::Cooked);
                $c->addDisposedBy($ac);
                break;
        }

        foreach ($spawn_items as $item_spec)
            for ($i = 0; $i < $item_spec['count']; $i++) {
                $new_item = $if->createItem( $item_spec['item'] );
                $this->inventory_handler->forceMoveItem( $ac->getTown()->getBank(), $new_item  );
            }

        $em->persist( $this->log->citizenDisposal( $ac, $c, $action, $spawn_items ) );
        $c->getHome()->setHoldsBody( false );

        if ($message){
            $this->addFlash('notice', $message);
        }

        // Give picto according to action
        $pictoPrototype = $em->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoName]);
        $this->picto_handler->give_picto($ac, $pictoPrototype);

        try {
            $em->persist($ac);
            $em->persist($c);
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/visit/{id}/complain", name="town_visit_complain_controller")
     * @param int $id
     * @param EntityManagerInterface $em
     * @param TownHandler $th
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function complain_visit_api(int $id, EntityManagerInterface $em, TownHandler $th, JSONRequestParser $parser ): Response {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $severity = (int)$parser->get('severity', -1);
        if ($this->getActiveCitizen()->getBanished() && $severity > Complaint::SeverityNone)
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        if ($this->getActiveCitizen()->getUser()->getAllSoulPoints() < $this->conf->getGlobalConf()->get(MyHordesConf::CONF_ANTI_GRIEF_SP, 20))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailableSP );

        if ($severity < Complaint::SeverityNone || $severity > Complaint::SeverityKill)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest );

        $reason = (int)$parser->get('reason', 0);
        if($severity != Complaint::SeverityNone && $reason <= 0)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $complaintReason = $this->entity_manager->getRepository(ComplaintReason::class)->find($reason);
        if ($severity != Complaint::SeverityNone && !$complaintReason)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $has_gallows = $th->getBuilding( $this->getActiveCitizen()->getTown(), 'r_dhang_#00', true ) ?? $th->getBuilding( $this->getActiveCitizen()->getTown(), 'small_eastercross_#00', true );
        $has_cage = $th->getBuilding( $this->getActiveCitizen()->getTown(), 'small_fleshcage_#00', true );

        $author = $this->getActiveCitizen();
        $town = $author->getTown();

        /** @var Citizen $culprit */
        $culprit = $em->getRepository(Citizen::class)->find( $id );
        if (!$culprit || $culprit->getTown()->getId() !== $town->getId() || !$culprit->getAlive() )
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        if ($culprit->getBanished() && !$has_gallows && !$has_cage && $severity > Complaint::SeverityNone) {
            $this->addFlash('error', $this->translator->trans('<strong>Dieser Bürger wurde bereits verbannt</strong>. Eine neue Beschwerde bringt nur etwas, wenn unsere Stadt beschließt, einen <strong>Galgen</strong> oder einen <strong>Fleischkäfig</strong> zu bauen...', [], 'game'));
            return AjaxResponse::success();
        }

        // Check permission: dummy accounts may not complain against non-dummy accounts (dummy is any account which email ends on @localhost)
        if ($this->isGranted('ROLE_DUMMY', $author) && !$this->isGranted('ROLE_DUMMY', $culprit))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError );

        $existing_complaint = $em->getRepository( Complaint::class )->findByCitizens($author, $culprit);

        if ($severity > Complaint::SeverityNone) {
            $counter = $this->getActiveCitizen()->getSpecificActionCounter(ActionCounter::ActionTypeComplaint);
            if ($counter->getCount() >= 4)
                return AjaxResponse::error(self::ErrorComplaintLimitHit );
            $counter->increment();
            $severity = ($has_gallows || $has_cage) ? Complaint::SeverityKill : Complaint::SeverityBanish;
            $this->entity_manager->persist($counter);
        }

        $complaint_level = 0;
        if (!$existing_complaint) {
            $existing_complaint = (new Complaint())
                ->setAutor( $author )
                ->setCulprit( $culprit )
                ->setSeverity( $severity )
                ->setCount( ($author->getProfession()->getHeroic() && $th->getBuilding( $town, 'small_court_#00', true )) ? 2 : 1 );
            
            if($reason > 0)
                $existing_complaint->setLinkedReason($complaintReason);
            $culprit->addComplaint($existing_complaint);

            if ($severity > Complaint::SeverityNone)
                $this->entity_manager->persist(
                    (new ActionEventLog())
                        ->setType(ActionEventLog::ActionEventComplaintIssued)
                        ->setCitizen($author)
                        ->setTimestamp( new DateTime())
                        ->setOpt1( $culprit->getId() )
                        ->setOpt2( $complaintReason->getId() )
                );

            $complaint_level = ($severity > Complaint::SeverityNone) ? 1 : 0;

        } else {

            if ($existing_complaint->getSeverity() > Complaint::SeverityNone && $severity === Complaint::SeverityNone)
                $complaint_level = -1;
            else if ($existing_complaint->getSeverity() === Complaint::SeverityNone && $severity > Complaint::SeverityNone)
                $complaint_level = 1;
            
            if( $complaint_level > 0 && $reason > 0 )
                $existing_complaint->setLinkedReason($complaintReason);
            else $complaintReason = $existing_complaint->getLinkedReason();

            if ( $complaint_level != 0 )
                $this->entity_manager->persist(
                    (new ActionEventLog())
                        ->setType($complaint_level > 0 ? ActionEventLog::ActionEventComplaintIssued : ActionEventLog::ActionEventComplaintRedacted)
                        ->setCitizen($author)
                        ->setTimestamp( new DateTime())
                        ->setOpt1( $culprit->getId() )
                        ->setOpt2( $complaintReason->getId() )
                );

            $existing_complaint->setSeverity( $severity );
        }

        try {
            $num_of_complaints = $this->entity_manager->getRepository(Complaint::class)->countComplaintsFor($culprit, Complaint::SeverityBanish) + $complaint_level;

            $em->persist( $this->log->citizenComplaint( $existing_complaint ) );
            $em->persist($culprit);
            $em->persist($existing_complaint);
            $em->flush();

        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        /** @var Building $a */

        if ($banished = $this->citizen_handler->updateBanishment( $culprit, $has_gallows, $has_cage, $a ))
            try {
                $em->persist($town);
                $em->persist($culprit);
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

        if (($complaint_level > 0 && !$banished) || $complaint_level < 0) {
            $this->crow->postAsPM( $culprit, '', '', $complaint_level > 0 ? PrivateMessage::TEMPLATE_CROW_COMPLAINT_ON : PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF, $complaintReason ? $complaintReason->getId() : 0, ['num' => $num_of_complaints] );
            $em->flush();
        }

        if ($a !== null) {
            $m = [];
            $m[] = $this->translator->trans('Deine Beschwerde ist der Tropfen, der das Fass zum Überlaufen brachte... Die Bürger haben sich in Scharen gegen <strong>{citizen}</strong> ausgesprochen.', ['citizen' => $culprit], 'game');

            switch ($a->getPrototype()->getName()) {
                case 'small_fleshcage_#00':
                    $m[] = $this->translator->trans('Dieser Aussätzige wurde zum Fleischkäfig geschleppt und dort unter dem Beifall des Publikums gesteinigt. Welch ein Schauspiel!', [], 'game');
                    break;
                case 'r_dhang_#00':
                    $m[] = $this->translator->trans('Dieser Aussätzige wurde kurzerhand <strong>gehängt</strong>.', [], 'game');
                    $m[] = $this->translator->trans('Der Galgen wurde im Zuge dieser gewalttätigen Aktion <strong>zerstört</strong>...', [], 'game');
                    break;
                case 'small_eastercross_#00':
                    $m[] = $this->translator->trans('Dieser Aussätzige wurde kurzerhand <strong>gekreuzigt</strong>.', [], 'game');
                    $m[] = $this->translator->trans('Das Schokoladenkreuz wurde von den Bürgern im Rahmen dieses tragischen Ereignisses <strong>gegessen</strong>!', [], 'game');
                    break;
                default: break;
            }
            $this->addFlash( 'notice', implode('<hr/>', $m) );

        } elseif ($severity > 0) {
            if($town->getChaos()) {
                $this->addFlash('notice', $this->translator->trans('Ihre Reklamation wurde gut aufgenommen, wird aber in der aktuellen Situation <strong>nicht sehr hilfreich</strong> sein.<hr>Die Stadt ist im totalen <strong>Chaos</strong> versunken... Bei so wenigen Überlebenden sind <strong>die Gesetze des Landes gebrochen worden</strong>.', [], 'game'));
            } else {
                if ($banished)
                    $this->addFlash('notice',
                                    $this->translator->trans('Deine Beschwerde ist der Tropfen, der das Fass zum Überlaufen brachte... Die Bürger haben sich in Scharen gegen <strong>{citizen}</strong> ausgesprochen.', ['citizen' => $culprit], 'game') . '<hr/>' .
                                            $this->translator->trans('Dieser Bürger wurde aus der Gemeinschaft verbannt; er hat nicht länger Zugang zu den Gebäuden der Stadt, mit Ausnahme des Brunnens (wobei er auf eine Ration pro Tag eingeschränkt ist).', [], 'game'));
                else
                    $this->addFlash('notice', $this->translator->trans('Sie haben eine Beschwerde gegen <strong>{citizen}</strong> eingereicht. Wenn sich genug Beschwerden ansammeln, <strong>wird {citizen} aus der Gemeinschaft verbannt oder gehängt</strong>, falls ein Galgen vorhanden ist.', ['citizen' => $culprit], 'game'));
            }
        } else {
            $this->addFlash('notice', $this->translator->trans('Ihre Beschwerde wurde zurückgezogen... Denken Sie das nächste Mal besser nach...', ['{citizen}' => $culprit->getName()], 'game'));
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/visit/{id}/report", name="report_personal_desc")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param RateLimitingFactoryProvider $rateLimiter
     * @return Response
     */
    public function report_personal_desc_api(int $id, JSONRequestParser $parser, RateLimitingFactoryProvider $rateLimiter ): Response {

        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        /** @var Citizen $citizen */
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$citizen) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        return $this->reportCitizen( $citizen, AdminReportSpecification::CitizenAnnouncement, $parser, $rateLimiter->reportLimiter( $this->getUser() ) );
    }

    /**
     * @Route("api/town/visit/{id}/intrude", name="town_visit_intrusion_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function intrude_visit_api(int $id, JSONRequestParser $parser): Response {

        $action = $parser->get_int('action');

        $victim = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$victim || $victim->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || ($this->citizen_handler->houseIsProtected($victim, true) && $victim->getAlive()) || (!$victim->getZone() && $victim->getAlive()))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        if ($action !== 0) {
            $intrusion = $this->entity_manager->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $this->getActiveCitizen(), 'victim' => $victim]);
            if ($intrusion) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

            if ($this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_steal') && !$this->getActiveCitizen()->getTown()->getChaos())
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

            if ($action > 0 && $this->getActiveCitizen()->getSpecificActionCounterValue(ActionCounter::ActionTypeSendPMItem, $victim->getId()) > 0)
                return AjaxResponse::error(InventoryHandler::ErrorTransferStealPMBlock);
        }

        foreach ($this->entity_manager->getRepository(HomeIntrusion::class)->findBy(['intruder' => $this->getActiveCitizen()]) as $other_intrusion)
            $this->entity_manager->remove($other_intrusion);

        if ($action !== 0 && $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype( $victim->getHome(), $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( 'alarm' ) ) && $victim->getAlive()) {
            $this->entity_manager->persist( $this->log->citizenHomeIntrusion( $this->getActiveCitizen(), $victim, true) );
            $this->addFlash( 'error', $this->translator->trans( 'Du hast das Alarmsystem bei {victim} ausgelöst! Die ganze Stadt weiß jetzt über deinen Einbruch Bescheid.', ['victim' => $victim], 'game' ) );
            $this->crow->postAsPM( $victim, '', '' . time(), PrivateMessage::TEMPLATE_CROW_INTRUSION, $this->getActiveCitizen()->getId() );
        }

        if ($action !== 0)
            $this->entity_manager->persist( (new HomeIntrusion())->setIntruder($this->getActiveCitizen())->setVictim( $victim )->setSteal( $action > 0 ) );

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/visit/{id}/item", name="town_visit_item_controller")
     * @param int $id
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function item_visit_api(int $id, JSONRequestParser $parser, InventoryHandler $handler, EntityManagerInterface $em): Response {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $ac = $this->getActiveCitizen();

        /** @var Citizen $c */
        $c = $em->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $intrusion = null;
        if ($c->getAlive() && !$intrusion = $em->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $ac, 'victim' => $c]))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $direction = $parser->get('direction', '');
        if ($c->getAlive() && $intrusion && (($intrusion->getSteal() && $direction === 'down') || (!$intrusion->getSteal() && $direction === 'up')))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $up_inv   = ($direction === 'down' || $c->getAlive()) ? $ac->getInventory() : $ac->getHome()->getChest();
        $down_inv = $c->getHome()->getChest();
        return $this->generic_item_api( $up_inv, $down_inv, false, $parser, $handler);
    }

    /**
     * @Route("api/town/remove_password", name="town_remove_password")
     * @param int $id
     * @return Response
     */
    public function town_remove_password(): Response {
        /** @var Town $town */
        $town = $this->getActiveCitizen()->getTown();;

        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if($town->getCreator() !== $this->getUser()) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $town->setPassword(null);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        $this->addFlash("notice", $this->translator->trans("Du hast soeben den Zugang zu deiner privaten Stadt für jedermann geöffnet.", [], 'game'));

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/well", name="town_well")
     * @param TownHandler $th
     * @return Response
     */
    public function well(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();
        $pump = $th->getBuilding( $town, 'small_water_#00', true );

        $allow_take = 1;
        if ($pump && !$this->getActiveCitizen()->getBanished()) {
            if($town->getChaos()) {
                $allow_take = 3;
            } else if  (!$this->getActiveCitizen()->getBanished()) {
                $allow_take = 2;
            }
        }

        return $this->render( 'ajax/game/town/well.html.twig', $this->addDefaultTwigArgs('well', [
            'rations_left' => $this->getActiveCitizen()->getTown()->getWell(),
            'first_take' => $this->getActiveCitizen()->getSpecificActionCounterValue( ActionCounter::ActionTypeWell ) === 0,
            'allow_take' => $this->getActiveCitizen()->getSpecificActionCounterValue( ActionCounter::ActionTypeWell ) < $allow_take,
            'maximum' => $allow_take,
            'pump' => $pump,

            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeWell, 5 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/town/well/log", name="town_well_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_well_api(JSONRequestParser $parser): Response {
        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeWell, null);
    }

    /**
     * @Route("api/town/well/item", name="town_well_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $factory
     * @param BankAntiAbuseService $ba
     * @return Response
     */
    public function well_api(JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $factory, BankAntiAbuseService $ba): Response {
        $direction = $parser->get('direction', '');

        if (in_array($direction, ['up','down'])) {
            $citizen = $this->getActiveCitizen();

            $town = $citizen->getTown();

            $pump = $this->town_handler->getBuilding($town, 'small_water_#00', true);

            $limit = ($pump && !$this->getActiveCitizen()->getBanished()) ? ($town->getChaos() ? 3 : 2) : 1;
            if ($direction == 'up') {
                if ($town->getWell() <= 0) return AjaxResponse::error(self::ErrorWellEmpty);

                $counter = $citizen->getSpecificActionCounter(ActionCounter::ActionTypeWell);

                if ($counter->getCount() >= $limit) return AjaxResponse::error(self::ErrorWellLimitHit);

                $inv_target = $citizen->getInventory();
                $inv_source = null;
                $item = $factory->createItem( 'water_#00' );

                if ($counter->getCount() > 0 && !$ba->allowedToTake( $citizen )) {
                    $ba->increaseBankCount($citizen);
                    $this->entity_manager->flush();
                    return AjaxResponse::error(InventoryHandler::ErrorBankLimitHit);
                }

                if (($error = $handler->transferItem(
                    $citizen,
                    $item,$inv_source, $inv_target
                )) === InventoryHandler::ErrorNone) {
                    if ($counter->getCount() > 0) {
                        $flash = $this->translator->trans("Du hast eine weitere {item} genommen. Die anderen Bürger der Stadt wurden informiert. Sei nicht zu gierig...", ['{item}' => $this->log->wrap($this->log->iconize($item), 'tool')], 'game');
                        $ba->increaseBankCount( $citizen );
                    } else {
                        $flash = $this->translator->trans("Du hast deine tägliche Ration erhalten: {item}", ['{item}' => $this->log->wrap($this->log->iconize($item), 'tool')], 'game');
                    }

                    $this->entity_manager->persist( $this->log->wellLog( $citizen, $counter->getCount() >= 1 ) );
                    $counter->increment();
                    $town->setWell( $town->getWell()-1 );
                    try {
                        $this->entity_manager->persist($item);
                        $this->entity_manager->persist($town);
                        $this->entity_manager->persist($citizen);
                        $this->entity_manager->persist($counter);
                        $this->entity_manager->flush();
                    } catch (Exception $e) {
                        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
                    }

                    $this->addFlash('notice', $flash);
                    return AjaxResponse::success();
                } else return AjaxResponse::error($error);
            } else {

                if(!$pump) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

                $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('water_#00', 1, null, false)] );
                if (empty($items)) $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('water_can_1_#00')] );
                if (empty($items)) $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('water_can_2_#00')] );
                if (empty($items)) $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('water_can_3_#00')] );
                if (empty($items)) $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('potion_#00')] );
                if (empty($items)) return AjaxResponse::error(self::ErrorWellNoWater);

                $morph = null;
                switch ($items[0]->getPrototype()->getName()) {
                    case 'water_can_3_#00': $morph = $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('water_can_2_#00'); break;
                    case 'water_can_2_#00': $morph = $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('water_can_1_#00'); break;
                    case 'water_can_1_#00': $morph = $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('water_can_empty_#00'); break;
                    default: break;
                }

                $inv_target = null;
                $inv_source = $citizen->getInventory();

                if (($error = $morph !== null ? InventoryHandler::ErrorNone : $handler->transferItem(
                        $citizen,
                        $items[0],$inv_source, $inv_target
                    )) === InventoryHandler::ErrorNone) {
                    $town->setWell( $town->getWell()+1 );
                    try {
                        $this->entity_manager->persist( $this->log->wellAdd( $citizen, $items[0]->getPrototype(), 1) );

                        $this->addFlash('info', $this->translator->trans('Du hast das Wasser aus {item} in den Brunnen geschüttet (<strong>+1 Einheit</strong>)', [
                            'item' => "<span><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $items[0]->getPrototype()->getIcon() . '.gif' )}' /> {$this->translator->trans($items[0]->getPrototype()->getLabel(),[],'items')}</span>"
                        ], 'game'));

                        if ($morph === null) $this->entity_manager->remove($items[0]);
                        else {
                            $items[0]->setPrototype($morph);
                            $this->entity_manager->persist($items[0]);
                        }
                        $this->entity_manager->persist($town);
                        $this->entity_manager->flush();
                    } catch (Exception $e) {
                        return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
                    }
                    return AjaxResponse::success();
                } else return AjaxResponse::error($error);
            }
        }

        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }


    /**
     * @Route("jx/town/bank", name="town_bank")
     * @param TownHandler $th
     * @return Response
     */
    public function bank(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
        $town = $this->getActiveCitizen()->getTown();
        $item_def_factor = 1;
        
        $building = $th->getBuilding($town, 'item_meca_parts_#00', true);
        if ($building) {
            $item_def_factor += (1+$building->getLevel()) * 0.5;
        }
        return $this->render( 'ajax/game/town/bank.html.twig', $this->addDefaultTwigArgs('bank', [
            'def' => $th->calculate_town_def($town, $defSummary),
            'item_defense' => $defSummary->item_defense,
            'item_def_factor' => $item_def_factor,
            'item_def_count' => $this->inventory_handler->countSpecificItems($town->getBank(),$this->inventory_handler->resolveItemProperties( 'defence' ), false, false),
            'bank' => $this->renderInventoryAsBank( $town->getBank() ),
            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeBank, 5 )->getContent(),
            'day' => $town->getDay(),
        ]) );
    }

    /**
     * @Route("api/town/bank/log", name="town_bank_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_bank_api(JSONRequestParser $parser): Response {
        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeBank, null);
    }

    /**
     * @Route("api/town/bank/item", name="town_bank_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function item_bank_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $item_id = $parser->get_int('item', -1);
        $direction = $parser->get('direction', '');

        if ($item_id > 0 && $direction === 'up') {
            /** @var Item $i */
            $i = $this->entity_manager->getRepository(Item::class)->find( $item_id );
            if ($i && !$this->getActiveCitizen()->getTown()->getBank()->getItems()->contains( $i ))
                return AjaxResponse::errorMessage( $this->translator->trans( 'Ein anderer Bürger hat sich in der Zwischenzeit diesen Gegenstand geschnappt.', [], 'game' ) );
        }

        $up_inv   = $this->getActiveCitizen()->getInventory();
        $down_inv = $this->getActiveCitizen()->getTown()->getBank();

        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $handler);
    }

    /**
     * @Route("jx/town/citizens", name="town_citizens")
     * @param EntityManagerInterface $em
     * @param TownHandler $th
     * @return Response
     */
    public function citizens(EntityManagerInterface $em, TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $citizenInfos = [];
        $hidden = [];

        $prof_count = [];
        $death_count = 0;

        foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $c) {
            $citizenInfo = new CitizenInfo();
            $citizenInfo->citizen = $c;
            $citizenInfo->defense = 0;

            $hidden[$c->getId()] = (bool)($em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($c->getHome(),
                $em->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName('curtain')
            ));

            if (!$c->getAlive()) $death_count++;
            else {
                $home = $c->getHome();
                $citizenInfo->defense = $th->calculate_home_def($home);

                if (!isset($prof_count[ $c->getProfession()->getId() ])) {
                    $prof_count[ $c->getProfession()->getId() ] = [
                        1,
                        $c->getProfession()
                    ];
                } else $prof_count[ $c->getProfession()->getId() ][0]++;

            }

            $citizenInfos[] = $citizenInfo;
        }

        $cc = 0;
        foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
            if ($citizen->getAlive() && !$citizen->getZone() && $citizen->getId() !== $this->getActiveCitizen()->getId()) $cc++;
        $town = $this->getActiveCitizen()->getTown();
        $cc = (float)$cc / (float)$this->town_handler->get_alive_citizens($town); // Completely arbitrary

        return $this->render( 'ajax/game/town/citizen.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $citizenInfos,
            'me' => $this->getActiveCitizen(),
            'hidden' => $hidden,
            'prof_count' => $prof_count,
            'death_count' => $death_count,
            'has_omniscience' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'omniscience'),
            'is_ghoul' => $this->getActiveCitizen()->hasRole('ghoul'),
            'caught_chance' => $cc
        ]) );
    }

    /**
     * @Route("jx/town/citizens/vote/{roleId}", name="town_citizen_vote", requirements={"id"="\d+"})
     * Show the citizens eligible to vote for a role
     * @param int $roleId The role we want to vote for
     * @return Response
     */
    public function citizens_vote(int $roleId): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($town->getChaos())
            // No vote possible in chaos
            return $this->redirect($this->generateUrl('town_citizens'));

        /** @var CitizenRole $role */
        $role = $this->entity_manager->getRepository(CitizenRole::class)->find($roleId);
        if($role === null || !$this->town_handler->is_vote_needed($town,$role))
            return $this->redirect($this->generateUrl('town_citizens'));

        $vote = $this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($this->getActiveCitizen(), $role);

        return $this->render( 'ajax/game/town/citizen_vote.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $town->getCitizens(),
            'me' => $this->getActiveCitizen(),
            'selectedRole' => $role,
            'vote' => $vote,
            'has_omniscience' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'omniscience'),
        ]) );
    }

    /**
     * @Route("api/town/citizens/send_vote", name="town_citizens_send_vote")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function citizens_send_vote_api(JSONRequestParser $parser): Response {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if the request is complete
        if (!$parser->has_all(['voted_citizen_id','role_id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );


        $voted_citizen_id = (int)$parser->get('voted_citizen_id');
        $role_id = (int)$parser->get('role_id');

        // Check if both citizen & role exists, and if voted citizen is in our town and alive
        // and, of course, if you voted for yourself
        // and if town is not in chaos
        $role = $this->entity_manager->getRepository(CitizenRole::class)->find($role_id);
        /** @var CitizenRole $role */
        $voted_citizen = $this->entity_manager->getRepository(Citizen::class)->find($voted_citizen_id);
        if($role === null || $voted_citizen === null || $voted_citizen->getTown() != $citizen->getTown() || !$voted_citizen->getAlive() || $citizen === $voted_citizen || $town->getChaos())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        // You can only vote if your vote is needed
        $needed = $this->get_needed_votes();
        if (!isset($needed[$role->getName()]) || !$needed[$role->getName()]) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        // Add our vote !
        $citizenVote = new CitizenVote();
        $citizenVote->setAutor($citizen)
            ->setVotedCitizen($voted_citizen)
            ->setRole($role);

        $citizen->addVote($citizenVote);

        // We remove the ability to vote from the WB
        $special_action = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => 'special_vote_' . $role->getName()]);
        if($special_action && $citizen->getSpecialActions()->contains($special_action))
            $citizen->removeSpecialAction($special_action);

        // Persist
        try {
            $this->entity_manager->persist($citizenVote);
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/citizens/omniscience", name="town_citizens_omniscience")
     * @return Response
     */
    public function citizens_omniscience(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
            
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if (!$this->getActiveCitizen()->getProfession()->getHeroic() || !$this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'omniscience'))
            return $this->redirect($this->generateUrl('town_citizens'));

        $citizens = [];
        $hidden = [];

        foreach($town->getCitizens() as $citizen) {
            $hidden[$citizen->getId()] = (bool)($this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($citizen->getHome(),
                $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName('curtain')
            ));
            $citizens[] = [
                'infos' => $citizen,
                'omniscienceLevel' => $this->citizen_handler->getActivityLevel($citizen),
                'soulPoint' => $citizen->getUser()->getAllSoulPoints()
            ];
        }

        return $this->render( 'ajax/game/town/citizen_omniscience.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $citizens,
            'has_omniscience' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'omniscience'),
            'me' => $this->getActiveCitizen(),
            'hidden' => $hidden
        ]) );
    }

    /**
     * @Route("api/town/constructions/build", name="town_constructions_build_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function construction_build_api(JSONRequestParser $parser, GameProfilerService $gps): Response {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($this->citizen_handler->hasStatusEffect($citizen, 'wound3')) {
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableWounded );
        }

        // Check if the request is complete
        if (!$parser->has_all(['id','ap'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');
        $ap = (int)$parser->get('ap');

        // Check if slave labor is allowed (ministry of slavery must be built)
        $slavery_allowed = $this->town_handler->getBuilding($town, 'small_slave_#00', true) !== null;

        /** @var Building|null $building */
        // Get the building the citizen wants to work on; fail if we can't find it
        $building = $this->entity_manager->getRepository(Building::class)->find($id);
        if (!$building || $building->getTown()->getId() !== $town->getId() || $ap < 0)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // If no slavery is allowed, block banished citizens from working on the construction site (except for repairs)
        // If slavery is allowed and the citizen is banished, permit slavery bonus
        if (!$slavery_allowed && $citizen->getBanished() && !$building->getComplete())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $slave_bonus = $citizen->getBanished() && !$building->getComplete();

        // Check if all parent buildings are completed
        $current = $building->getPrototype();
        while ($parent = $current->getParent()) {
            if (!$this->town_handler->getBuilding($town, $parent, true))
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
            $current = $parent;
        }

        $this->town_handler->getWorkshopBonus($town, $workshopBonus, $repairBonus);

        $workshopBonus = 1 - $workshopBonus;
        $hpToAp = 2 + $repairBonus;

        // Remember if the building has already been completed (i.e. this is a repair action)
        $was_completed = $building->getComplete();

        // Check out how much AP is missing to complete the building; restrict invested AP to not exceed this
        if(!$was_completed) {
            $missing_ap = ceil( (round($building->getPrototype()->getAp()*$workshopBonus) - $building->getAp()) * ( $slave_bonus ? (2.0/3.0) : 1 )) ;
            $ap = max(0,min( $ap, $missing_ap ) );
        } else {
            $missing_ap = ceil(($building->getPrototype()->getHp() - $building->getHp()) / $hpToAp);
            $ap = max(0, min( $ap, $missing_ap ) );
        }

        if (intval($ap) <= 0 && $was_completed)
            return AjaxResponse::error(TownController::ErrorAlreadyFinished);

        // If the citizen has not enough AP, fail
        if ($ap > 0 && ($citizen->getAp() + $citizen->getBp()) < $ap || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Get all resources needed for this building
        $res = $items = [];
        if (!$building->getComplete() && $building->getPrototype()->getResources())
            foreach ($building->getPrototype()->getResources()->getEntries() as $entry)
                if (!isset($res[ $entry->getPrototype()->getName() ]))
                    $res[ $entry->getPrototype()->getName() ] = new ItemRequest( $entry->getPrototype()->getName(), $entry->getChance(), false, false, false );
                else $res[ $entry->getPrototype()->getName() ]->addCount( $entry->getChance() );

        // If the building needs resources, check if they are present in the bank; otherwise fail
        if (!empty($res)) {
            $items = $this->inventory_handler->fetchSpecificItems($town->getBank(), $res);
            if (empty($items)) return AjaxResponse::error( self::ErrorNotEnoughRes );
        }

        // Create a log entry
        if ($this->town_handler->getBuilding($town, 'item_rp_book2_#00', true)) {
            // TODO: Create an option to include AP in Log entries as a town parameter?
            if (!$was_completed)
                $this->entity_manager->persist( $this->log->constructionsInvest( $citizen, $building->getPrototype(), $ap, $slave_bonus ) );
            else
                $this->entity_manager->persist( $this->log->constructionsInvestRepair( $citizen, $building->getPrototype(), $ap, $slave_bonus ) );
        }

        // Calculate the amount of AP that will be invested in the construction
        $ap_effect = floor( $ap * ( $slave_bonus ? 1.5 : 1 ) );

        // Deduct AP and increase completion of the building
        $usedap = $usedbp = 0;
        $this->citizen_handler->deductAPBP( $citizen, $ap, $usedap, $usedbp);

        if ($was_completed)
            $gps->recordBuildingRepairInvested( $building->getPrototype(), $town, $citizen, $usedap, $usedbp );
        else $gps->recordBuildingConstructionInvested( $building->getPrototype(), $town, $citizen, $usedap, $usedbp );

        if($missing_ap <= 0 || $missing_ap - $ap <= 0){
            // Missing ap == 0, the building has been completed by the workshop upgrade.
            $building->setAp($building->getPrototype()->getAp());
        } else {
            $building->setAp($building->getAp() + $ap_effect);
        }

        $messages[] = "";

        // Notice
        if(!$was_completed) {
            if($building->getAp() < $building->getPrototype()->getAp()){
                $messages[] = $this->translator->trans("Du hast am Bauprojekt {plan} mitgeholfen.", ["{plan}" => "<strong>" . $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings') . "</strong>"], 'game');
            } else {
                $messages[] = $this->translator->trans("Hurra! Folgendes Gebäude wurde fertiggestellt: {plan}!", ['{plan}' => "<strong>" . $this->translator->trans($building->getPrototype()->getLabel(), [], 'buildings') . "</strong>"], 'game');
                $gps->recordBuildingConstructed( $building->getPrototype(), $town, $citizen, 'manual' );
            }
        }

        // If the building was not previously completed but reached 100%, complete the building and trigger the completion handler
        $building->setComplete( $building->getComplete() || $building->getAp() >= $building->getPrototype()->getAp() );

        if (!$was_completed && $building->getComplete()) {
            // Remove resources, create a log entry, trigger
            foreach ($items as $item) if ($res[$item->getPrototype()->getName()]->getCount() > 0) {
                $cc = $item->getCount();
                $this->inventory_handler->forceRemoveItem($item, $res[$item->getPrototype()->getName()]->getCount());
                $res[$item->getPrototype()->getName()]->addCount(-$cc);
            }

            $this->entity_manager->persist( $this->log->constructionsBuildingComplete( $citizen, $building->getPrototype() ) );
            $this->town_handler->triggerBuildingCompletion( $town, $building );
            $votes = $building->getBuildingVotes();
            foreach ($votes as $vote) {
                $vote->getCitizen()->setBuildingVote(null);
                $vote->getBuilding()->removeBuildingVote($vote);
                $this->entity_manager->remove($vote);
            }
        } else if ($was_completed) {
            $newHp = min($building->getPrototype()->getHp(), $building->getHp() + $ap * $hpToAp);
            $building->setHp($newHp);
            if($building->getPrototype()->getDefense() > 0) {
                $newDef = min($building->getPrototype()->getDefense(), $building->getPrototype()->getDefense() * $building->getHp() / $building->getPrototype()->getHp());
                $building->setDefense((int)floor($newDef));
            }
        }
        if($usedbp > 0)
            $messages[] = $this->translator->trans("Du hast dafür {count} Baupunkt(e) verbraucht.", ['{count}' => "<strong>$usedbp</strong>", 'raw_count' => $usedbp], "game");
        if($usedap > 0)
            $messages[] = $this->translator->trans("Du hast dafür {count} Aktionspunkt(e) verbraucht.", ['{count}' => "<strong>$usedap</strong>", 'raw_count' => $usedap], "game");


        if ($slave_bonus && !$was_completed)
            $messages[] = $this->translator->trans("Die in das Gebäude investierten APs zählten <strong>50% mehr</strong> (Sklaverei).", [], "game");

        // Set the activity status
        $this->citizen_handler->inflictStatus($citizen, 'tg_chk_build');

        // Give picto to the citizen
        if(!$was_completed){
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_buildr_#00");
        } else {
            $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_brep_#00");
        }
        $this->picto_handler->give_picto($citizen, $pictoPrototype, $ap);

        // Persist
        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($building);
            $this->entity_manager->persist($town);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        $messages = array_filter($messages);

        if(!empty($messages))
            $this->addFlash("notice", implode('<hr />', $messages));

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/constructions", name="town_constructions")
     * @param TownHandler $th
     * @return Response
     */
    public function constructions(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
        $town = $this->getActiveCitizen()->getTown();
        $buildings = $town->getBuildings();

        $this->town_handler->getWorkshopBonus($town, $workshopBonus, $repairBonus);

        $workshopBonus = 1 - $workshopBonus;
        $hpToAp = 2 + $repairBonus;

        $root = [];
        $dict = [];
        $items = [];

        foreach ($buildings as $building) {
            $dict[ $building->getPrototype()->getId() ] = [];
            if (!$building->getPrototype()->getParent()) $root[] = $building;
            if (!$building->getComplete() && !empty($building->getPrototype()->getResources()))
                foreach ($building->getPrototype()->getResources()->getEntries() as $resource)
                    if (!isset($items[$resource->getPrototype()->getId()]))
                        $items[$resource->getPrototype()->getId()] = $this->inventory_handler->countSpecificItems( $this->getActiveCitizen()->getTown()->getBank(), $resource->getPrototype(), false, false, false );
        }

        $votedBuilding = null; $max_votes = -1;
        foreach ($buildings as $building) {
            if ($building->getPrototype()->getParent()) {
                $dict[$building->getPrototype()->getParent()->getId()][] = $building;
            }

            $v = $building->getBuildingVotes()->count();
            if ($v > 0 && $v > $max_votes) {
                $votedBuilding = $building;
                $max_votes = $v;
            }
        }

        return $this->render( 'ajax/game/town/construction.html.twig', $this->addDefaultTwigArgs('constructions', [
            'root_cats'  => $root,
            'dictionary' => $dict,
            'bank' => $items,
            'slavery' => $th->getBuilding($town, 'small_slave_#00', true) !== null,
            'workshopBonus' => $workshopBonus,
            'hpToAp' => $hpToAp,
            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeConstruction, 5 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
            'canvote' => $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), "dictator") && !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_build_vote'),
            'voted_building' => $votedBuilding,
        ]) );
    }

    /**
     * @Route("api/town/constructions/vote", name="town_constructions_vote_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function constructions_votes_api(JSONRequestParser $parser): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if (!$this->getActiveCitizen()->getProfession()->getHeroic() || !$this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'dictator'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getBuildingVote() || $citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($id);
        if (!$building || $building->getComplete() || $building->getTown()->getId() !== $town->getId())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        try {
            $citizen->setBuildingVote( (new BuildingVote())->setBuilding( $building ) );
            $this->citizen_handler->inflictStatus($citizen, 'tg_build_vote');
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/constructions/log", name="town_constructions_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_constructions_api(JSONRequestParser $parser): Response {
        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeConstruction, null);
    }

    /**
     * @Route("api/town/door/control", name="town_door_control_controller")
     * @param JSONRequestParser $parser
     * @param TownHandler $th
     * @return Response
     */
    public function door_control_api(JSONRequestParser $parser, TownHandler $th): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!($action = $parser->get('action')) || !in_array($action, ['open','close']))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($action === 'close' && $town->getDevastated())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($action === 'open'  && $town->getDoor())
            return AjaxResponse::error( self::ErrorDoorAlreadyOpen );
        if ($action === 'open'  && $town->getQuarantine()) {
            $this->addFlash('error', $this->translator->trans('Das Stadttor kann während einer Quarantäne nicht geöffnet werden!', [], 'game'));
            return AjaxResponse::success();
        }
        if ($action === 'open'  && ($b = $this->door_is_locked($th, $this->conf))) {
            if ($b === true) {
                $this->addFlash('error', $this->translator->trans('Es ist unmöglich, das Stadttor zu einer Privatstadt zu öffnen, solange es *weniger als {num} eingeschriebene Bürger* gibt.', [ 'num' => $town->getPopulation() ], 'game'));
                return AjaxResponse::success();
            } elseif (is_a( $b, BuildingPrototype::class )) {
                if ($b->getName() === 'small_door_closed_#01') {
                    $this->addFlash('error', $this->translator->trans('Der <strong>Kolbenschließmechanismus</strong> hat das Stadttor für heute Nacht sicher verriegelt...', [], 'game'));
                    return AjaxResponse::success();
                } else {
                    $this->addFlash('error', $this->translator->trans('Der <strong>Stadttorriegel</strong> ist eingerastet und das Tor ist zu. Im Moment geht da gar nichts mehr!', [], 'game'));
                    return AjaxResponse::success();
                }
            } else return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }
        if ($action === 'close' && !$town->getDoor())
            return AjaxResponse::error( self::ErrorDoorAlreadyClosed );

        if ($this->citizen_handler->hasStatusEffect($citizen, 'wound3')) {
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableWounded );
        }

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        foreach ($this->conf->getCurrentEvents($town) as $e)
            if ($result = $e->hook_door($action))
                return $result;

        $this->citizen_handler->setAP($citizen, true, -1);
        $town->setDoor( $action === 'open' );

        $this->entity_manager->persist( $this->log->doorControl( $citizen, $action === 'open' ) );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($town);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/door/exit/{special}", name="town_door_exit_controller")
     * @param string $special
     * @return Response
     */
    public function door_exit_api(string $special = 'normal'): Response {
        $citizen = $this->getActiveCitizen();
        switch ($special) {
            case 'normal':
                if (!$citizen->getTown()->getDoor())
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            case 'sneak':
                if (!$citizen->getTown()->getDoor() || !$citizen->hasRole('ghoul'))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            case 'hero':
                if (!$citizen->getProfession()->getHeroic())
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), 0, 0);

        if (!$zone)
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($special !== 'sneak')
            $this->entity_manager->persist( $this->log->doorPass( $citizen, false ) );
        $zone->addCitizen( $citizen );
        foreach ($this->entity_manager->getRepository(HomeIntrusion::class)->findBy(['intruder' => $citizen]) as $homeIntrusion)
            $this->entity_manager->remove($homeIntrusion);

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    private function door_is_locked(TownHandler $th, ConfMaster $conf): bool|BuildingPrototype {
        $town = $this->getActiveCitizen()->getTown();

        if ( !$town->getDoor() ) {

            if ($town->isOpen() && $conf->getTownConfiguration($town)->get(TownConf::CONF_LOCK_UNTIL_FULL, false) ) return true;

            if((($s = $this->time_keeper->secondsUntilNextAttack(null, true)) <= 1800)) {
                if ($b = $th->getBuilding( $town, 'small_door_closed_#02', true )) {
                    if ($s <= 60) return $b->getPrototype();
                } elseif ($b = $th->getBuilding( $town, 'small_door_closed_#01', true )) {
                    if ($s <= 1800) return $b->getPrototype();
                } elseif ($b = $th->getBuilding( $town, 'small_door_closed_#00', true )) {
                    if ($s <= 1200) return $b->getPrototype();
                }
            }
        }
        return false;
    }

    /**
     * @Route("jx/town/door", name="town_door")
     * @param TownHandler $th
     * @return Response
     */
    public function door(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
        $door_locked = (bool)$this->door_is_locked($th,$this->conf);
        $can_go_out = !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tired') && $this->getActiveCitizen()->getAp() > 0;

        $town = $this->getActiveCitizen()->getTown();
        $time = $this->getTownConf()->isNightTime() ? 'night' : 'day';

        if ($door_locked || true) {
            /** @var Zone $zeroZero */
            $zeroZero = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town, 0, 0);
            if ($zeroZero &&
                !$zeroZero->getActivityMarkersFor( ZoneActivityMarkerType::DoorAutoClosed )->isEmpty() &&
                $zeroZero->getActivityMarkersFor( ZoneActivityMarkerType::DoorAutoCloseReported )->isEmpty()
            ) {
                $zeroZero->addActivityMarker( (new ZoneActivityMarker())
                    ->setCitizen( $this->getActiveCitizen() )
                    ->setTimestamp( new DateTime() )
                    ->setType(ZoneActivityMarkerType::DoorAutoCloseReported )
                );
                $this->entity_manager->persist($zeroZero);
                $this->entity_manager->persist( $this->log->doorCheck( $this->getActiveCitizen() ) );
                $this->entity_manager->flush();
            }
        }

        return $this->render( 'ajax/game/town/door.html.twig', $this->addDefaultTwigArgs('door', [
            'def'               => $th->calculate_town_def($town, $defSummary),
            'town'              => $town,
            'door_locked'       => $door_locked,
            'can_go_out'        => $can_go_out,
            'show_ventilation'  => $th->getBuilding($this->getActiveCitizen()->getTown(), 'small_ventilation_#00',  true) !== null,
            'allow_ventilation' => $this->getActiveCitizen()->getProfession()->getHeroic(),
            'show_sneaky'       => $this->getActiveCitizen()->hasRole('ghoul'),
            'log'               => $this->renderLog( -1, null, false, LogEntryTemplate::TypeDoor, 5 )->getContent(),
            'day'               => $this->getActiveCitizen()->getTown()->getDay(),
            'door_section'      => 'door',
            'map_public_json'   => json_encode( $this->get_public_map_blob( 'door-preview', $time ) )
        ]) );
    }

    /**
     * @Route("api/town/door/log", name="town_door_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_door_api(JSONRequestParser $parser): Response {
        if ($this->getActiveCitizen()->getZone())
            return $this->renderLog((int)$parser->get('day', -1), null, false, -1, 0);
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeDoor, null);
    }

    /**
     * @Route("jx/town/routes", name="town_routes")
     * @return Response
     */
    public function routes(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        return $this->render( 'ajax/game/town/routes.html.twig', $this->addDefaultTwigArgs('door', [
            'door_section'      => 'planner',
            'town'  =>  $this->getActiveCitizen()->getTown(),
            'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown($this->getActiveCitizen()->getTown()),
            'allow_extended' => $this->getActiveCitizen()->getProfession()->getHeroic(),
            'map_public_json'   => json_encode( $this->get_public_map_blob( 'door-preview', $this->getTownConf()->isNightTime() ? 'night' : 'day' ) )
        ]));
    }

    /**
     * @Route("jx/town/planner", name="town_planner")
     * @return Response
     */
    public function planner(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $routes = $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown($this->getActiveCitizen()->getTown());

        if(count($routes) >= 16)
            return $this->redirect($this->generateUrl('town_routes'));

        return $this->render( 'ajax/game/town/planner.html.twig', $this->addDefaultTwigArgs('door', [
            'door_section'      => 'planner',
            'town'  =>  $this->getActiveCitizen()->getTown(),
            'allow_extended' => $this->getActiveCitizen()->getProfession()->getHeroic(),
            'map_public_json'   => json_encode( $this->get_public_map_blob( 'door-planner', $this->getTownConf()->isNightTime() ? 'night' : 'day' ) )
        ]) );
    }

    /**
     * @Route("api/town/planner/submit", name="town_planner_route_submit_controller")
     * @param JSONRequestParser $parser
     * @param TranslatorInterface $trans
     * @return Response
     */
    public function planner_submit_api(JSONRequestParser $parser, TranslatorInterface $trans): Response {
        $citizen = $this->getActiveCitizen();

        $name = $parser->get('name', '');
        if (mb_strlen( $name ) > 32 || mb_strlen( $name ) < 3)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $data = $parser->get('data', []);
        if (!$data || !is_array($data)  || count($data) < 2)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($citizen->getExpeditionRoutes()->count() >= 12)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $routes = $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown($citizen->getTown());

        if(count($routes) >= 16)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $last = null; $ap = 0;
        $buildup = [];
        foreach ($data as $entry)
            if (!is_array($entry) && count($entry) !== 2) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            else {
                list($x,$y) = $entry;
                if (!is_int($x) || !is_int($y)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if (!$this->entity_manager->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), $x, $y))
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($last !== null) {
                    if ($last[0] !== $x && $last[1] !== $y) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    if ($last[0] === $x && $last[1] === $y) continue;
                    $ap += (abs($last[0] - $x) + abs($last[1] - $y));
                }

                if ($last === null || $last[0] !== $x || $last[1] !== $y)
                    $buildup[] = $last = [$x,$y];
            }

        $data = $buildup;

        $is_pro_route = $data[0] !== [0,0] || $data[count($data)-1] !== [0,0];
        if ($is_pro_route && !$citizen->getProfession()->getHeroic())
            return AjaxResponse::error( ErrorHelper::ErrorMustBeHero );

        $citizen->addExpeditionRoute(
            (new ExpeditionRoute())
                ->setLabel($name)
                ->setOwner($citizen)
                ->setLength($ap)
                ->setData( $data )
        );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        $this->addFlash( 'notice', $trans->trans('Deine Route wurde gespeichert.', [], 'game') );
        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/planner/delete", name="town_planner_delete_route")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function planner_delete_api(JSONRequestParser $parser): Response {
        $route_id = $parser->get('id', -1);

        if ($route_id <= 0)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var ExpeditionRoute $route */
        $route = $this->entity_manager->getRepository(ExpeditionRoute::class)->find($route_id);

        if(!$route || $route->getOwner() !== $this->getActiveCitizen())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $this->entity_manager->remove($route);
        $this->entity_manager->flush();

        $this->addFlash( 'notice', $this->translator->trans('Die Route wurde gelöscht.', [], 'game') );
        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/dashboard/wordofheroes", name="town_dashboard_save_woh")
     * @param JSONRequestParser $parser
     * @param RateLimitingFactoryProvider $rateLimiter
     * @return Response
     */
    public function dashboard_save_wordofheroes_api(JSONRequestParser $parser, RateLimitingFactoryProvider $rateLimiter ): Response {
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_WORDS_OF_HEROS, false) || !$this->getActiveCitizen()->getProfession()->getHeroic())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->getActiveCitizen()->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->user_handler->isRestricted($this->getActiveCitizen()->getUser(), AccountRestriction::RestrictionTownCommunication) || $this->user_handler->isRestricted($this->getActiveCitizen()->getUser(), AccountRestriction::RestrictionBlackboard))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        $new_words_of_heroes = mb_substr($parser->get('content', ''), 0, 500);

        // Get town
        $town = $this->getActiveCitizen()->getTown();

        // Rate Limiting
        if (
            !$rateLimiter->blackboardEditFixed->create( $this->getActiveCitizen()->getId() )->consume(1)->isAccepted() ||
            !$rateLimiter->blackboardEditSlide->create( $this->getActiveCitizen()->getId() )->consume(1)->isAccepted() )
            return AjaxResponse::error( ErrorHelper::ErrorRateLimited);

        // No need to update WoH is there is no change
        if ($town->getWordsOfHeroes() === $new_words_of_heroes) return AjaxResponse::success();
        $town->setWordsOfHeroes($new_words_of_heroes);

        $this->entity_manager->persist(
            (new BlackboardEdit())
                ->setUser( $this->getActiveCitizen()->getUser() )
                ->setTime( new DateTime() )
                ->setText( $new_words_of_heroes )
                ->setTown( $town )
        );

        // Persist
        try {
            $this->entity_manager->persist($town);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/dashboard/wordofheroes/report", name="town_dashboard_report_woh")
     * @param JSONRequestParser $parser
     * @param RateLimitingFactoryProvider $rateLimiter
     * @return Response
     */
    public function dashboard_report_wordofheroes_api(JSONRequestParser $parser, RateLimitingFactoryProvider $rateLimiter ): Response {
        $user = $this->getUser();
        $blackBoardEdit = $this->entity_manager->getRepository(BlackboardEdit::class)->find( $parser->get_int('bbe') );

        if (!$blackBoardEdit || empty( $blackBoardEdit->getText() ) || $blackBoardEdit->getTown() !== $this->getActiveCitizen()->getTown())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $targetUser = $blackBoardEdit->getUser();
        if ($targetUser === $user)
            return AjaxResponse::success();

        $reports = $this->entity_manager->getRepository(AdminReport::class)->findBy(['blackBoard' => $blackBoardEdit]);
        foreach ($reports as $report)
            if ($report->getSourceUser()->getId() == $user->getId())
                return AjaxResponse::success();
        $report_count = count($reports) + 1;

        if (!$rateLimiter->reportLimiter( $user )->create( $user->getId() )->consume()->isAccepted())
            return AjaxResponse::error( ErrorHelper::ErrorRateLimited);

        $details = $parser->trimmed('details');
        $newReport = (new AdminReport())
            ->setSourceUser($user)
            ->setReason( $parser->get_int('reason', 0, 0, 13) )
            ->setDetails( $details ?: null )
            ->setTs(new DateTime('now'))
            ->setBlackBoard( $blackBoardEdit );

        try {
            $this->entity_manager->persist($newReport);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        try {
            $this->crow->triggerExternalModNotification( "A blackboard message has been reported.", $blackBoardEdit, $newReport, "This is report #{$report_count}." );
        } catch (\Throwable $e) {}

        $message = $this->translator->trans('Du hast die Nachricht auf dem Schwarzen Brett an den Raben gemeldet. Wer weiß, vielleicht wird deren Verfasser heute Nacht stääärben...', [], 'game');
        $this->addFlash('notice', $message);
        return AjaxResponse::success( );
    }

    /**
     * @Route("jx/town/visit/{id}/heal", name="visit_heal_citizen", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function visit_heal_citizen(int $id): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );
        
        $citizen = $this->getActiveCitizen();
        $message = [];
        if($this->citizen_handler->hasStatusEffect($citizen, ['drugged', 'drunk', 'infected', 'terror'])) {
            $message[] = $this->translator->trans('In deinem aktuellen Zustand kannst du diese Aktion nicht ausführen.', [], 'game');
            $this->addFlash('notice', implode('<hr />', $message));
            return AjaxResponse::success();
        }

        if(($citizen->hasRole('shaman') && $citizen->getPM() < 2)) {
            $message[] = $this->translator->trans('In deinem aktuellen Zustand kannst du diese Aktion nicht ausführen.', [], 'game');
            $this->addFlash('notice', implode('<hr />', $message));
            return AjaxResponse::success();
        } else if ($citizen->getProfession()->getName() == "shaman" && $citizen->getAp() < 2) {
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );
        }

        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getZone())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $healableStatus = [
            'terror' => array(
                'success' => T::__('Es gibt nichts Besseres als die Furcht, um eine Angststarre zu heilen. Man nimmt die Schamanenmaske ab und bläst dem Patienten ein selbst hergestelltes halluzinogenes Pulver auf das Gesicht, um einen schlafwandelnden Zustand herbeizuführen. Das provoziert schnell "pavor nocturnus". Als {citizen} wieder erwacht, scheint er von seiner Angststarre befreit zu sein.', 'game'),
                'transfer' => T::__('Allerdings hat dich der Anblick dieses bis aufs Mark verängstigen Bürgers selbst in eine Angststarre versetzt.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, {citizen} umzubringen ist zu hoch...', 'game'),
            ),
            'infection' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt an der Basis des infizierten Körperteils. Der Entgiftungsprozess ist im Gange, wenn auch langsam.', 'game'),
                'transfer' => T::__('Plötzlich platzt eine infizierte Eiterblase auf. Deine bereits verbrannte Haut bricht schnell in offene Wunden aus, und die infektiösen Keime beschließen, diese zu ihrem Zuhause zu machen.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, {citizen} umzubringen ist zu hoch...', 'game'),
            ),
            'drunk' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt nahe der Leber. {citizen} ist aus den Krallen des Alkohols befreit.', 'game'),
                'transfer' => T::__( 'Doch diese alkoholischen Ausdüstungen bringen dich ganz um den Verstand. Voller Wonne kostest du von diesem frisch befreiten Alkohol.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, {citizen} umzubringen ist zu hoch...', 'game'),
            ),
            'drugged' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt nahe der rechten Lunge. So sehr du auch versuchst, den Kräften zu widerstehen, die dich führen, kannst du nicht verhindern, dass deine Klinge tief in {citizen} eindringt und eine klare Flüssigkeit aus seinem frisch verstümmelten Körper austritt.', 'game'),
                'transfer' => T::__( 'Doch dein von Müdigkeit gezeichneter Zustand lässt nicht zu, dass du den Dämonen widerstehst. Du lässt dich dazu hinreißen, diese Flüssigkeit, die – wie du weißt – tödlich sein kann, aufzusaugen.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, {citizen} umzubringen ist zu hoch...', 'game'),
            ),
        ];

        if(!$this->citizen_handler->hasStatusEffect($c, array_keys($healableStatus)) || $this->citizen_handler->hasStatusEffect($c, 'tg_shaman_heal')){
            $message[] = $this->translator->trans('Du kannst diesen Bürger nicht heilen. Entweder bedarf er keiner Heilung, ist nicht in der Stadt oder hat heute bereits eine mystische Heilung erfahren.', [], 'game');
            $this->addFlash('notice', implode('<hr />', $message));
            return AjaxResponse::success();
        }

        $this->citizen_handler->inflictStatus($c, 'tg_shaman_heal');
        $status = [];
        foreach ($c->getStatus() as $citizenStatus) {
            if(in_array($citizenStatus->getName(), array_keys($healableStatus)))
                $status[] = $citizenStatus->getName();
        }
        $healedStatus = $this->random_generator->pick($status);
        $healChances = $this->random_generator->chance(0.65); //same than Hordes
        if($healChances) {

            $this->citizen_handler->removeStatus($c, $healedStatus);
            if($healedStatus == 'infection') {
                $this->citizen_handler->removeStatus($c, "tg_meta_winfect");
                $this->citizen_handler->removeStatus($c, "tg_meta_ginfect");
            }

            $message[] = $this->translator->trans($healableStatus[$healedStatus]['success'], ['{citizen}' => "<span>" . $c->getName() . "</span>"], 'game');
            $this->entity_manager->persist( $this->log->shamanHealLog( $this->getActiveCitizen(), $c ) );

            $transfer = $this->random_generator->chance(0.05); //same than Hordes
            if($transfer){
                $do_transfer = true;
                $witness = $this->citizen_handler->hasStatusEffect($citizen, 'tg_infect_wtns');
                if($healedStatus == 'infection' && $witness) {
                    if($this->random_generator->chance(0.5))
                        $do_transfer = false;
                    $this->citizen_handler->removeStatus($citizen, 'tg_infect_wtns');
                }
                if($do_transfer) {
                    $this->citizen_handler->inflictStatus($citizen, $healedStatus === 'infection' ? 'tg_meta_ginfect' : $healedStatus);
                    $message[] = $this->translator->trans($healableStatus[$healedStatus]['transfer'], ['{citizen}' => "<span>" . $c->getName() . "</span>"], 'game');
                    if ($healedStatus == 'infection' && $witness)
                        $message[] = $this->translator->trans('Ein Opfer der Großen Seuche zu sein hat dir diesmal nicht viel gebracht... und es sieht nicht gut aus...', [], 'items');
                } else if ($witness) {
                    $message[] = $this->translator->trans('Da hast du wohl Glück gehabt... Als Opfer der Großen Seuche bist du diesmal um eine unangenehme Infektion herumgekommen.', [], 'items');
                }
            }
        } else {
            $message[] = $this->translator->trans($healableStatus[$healedStatus]['fail'], ['{citizen}' => "<span>" . $c->getName() . "</span>"], 'game');
        }
        if ($citizen->hasRole('shaman')) {
            $citizen->setPM($citizen->getPM() - 2);
        } else if ($citizen->getProfession()->getName() == "shaman") {
            $citizen->setAp($citizen->getAp() - 2);
        }

        $this->entity_manager->persist($c);
        $this->entity_manager->persist($citizen);
        $this->entity_manager->flush();

        $this->addFlash('notice', implode('<hr />', $message));
        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/visit/{id}/attack", name="visit_attack_citizen", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function visit_attack_citizen(int $id): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();

        if ($citizen->getBanished()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $this->getActiveCitizen()->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->citizen_handler->isWounded($citizen)) {
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableWounded);
        }

        return $this->generic_attack_api( $citizen, $c );
    }

    /**
     * @Route("jx/town/visit/{id}/devour", name="visit_devour_citizen", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function visit_devour_citizen(int $id): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        return $this->generic_devour_api( $citizen, $c );
    }

    /**
     * @Route("jx/town/visit/{id}/recycle", name="visit_recycle_home", requirements={"id"="\d+"})
     * @param int $id
     * @return Response
     */
    public function visit_recycle_home(int $id, ItemFactory $if, Packages $asset): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $recycleAP = $this->getTownConf()->get(TownConf::CONF_MODIFIER_RECYCLING_AP, 15);
        $recycleReturn = $this->getTownConf()->get(TownConf::CONF_MODIFIER_RECYCLING_RETURN, 5);

        $citizen = $this->getActiveCitizen();
        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getAlive())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        if($c->getHome()->getRecycling() >= $recycleAP){
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $this->citizen_handler->setAP($citizen, true, -1);
        $home = $c->getHome();
        $home->setRecycling($home->getRecycling() + 1);

        if ($home->getRecycling() >= $recycleAP) {
            $resources = [];
            for ($l = $home->getPrototype()->getLevel(); $l >= 0; $l--) {
                $prototype = $this->entity_manager->getRepository(CitizenHomePrototype::class)->findOneByLevel( $l );
                if ($prototype && $prototype->getResources())
                    foreach ($home->getPrototype()->getResources()->getEntries() as $entry)
                        if (!isset($resources[$entry->getPrototype()->getName()])) $resources[$entry->getPrototype()->getName()] = $entry->getChance();
                        else $resources[$entry->getPrototype()->getName()] += $entry->getChance();
            }

            $item_list = [];
            $item_list_p = [];

            $has_recycled = false;
            shuffle($resources);
            foreach ($resources as $item_name => &$count) {
                $count = min($recycleReturn, (int)floor($count * 0.4));
                $recycleReturn -= $count;
                if ($count > 0) {
                    $has_recycled = true;
                    $p = $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($item_name);
                    $item_list_p[] = ['item' => $p, 'count' => $count];
                    $item_list[] = "<span class='tool'><img alt='' src='{$asset->getUrl( "build/images/item/item_{$p->getIcon()}.gif" )}'> {$this->translator->trans($p->getLabel(), [], 'items')}" . ($count > 1 ? " x $count" : '') . "</span>";
                }
                for ($i = 0 ; $i < $count; $i++)
                    $this->inventory_handler->forceMoveItem( $citizen->getTown()->getBank(), $if->createItem($item_name));
            }

            foreach ($home->getChest()->getItems() as $item)
                $this->inventory_handler->forceMoveItem($citizen->getTown()->getBank(), $item);

            $msg = [ $this->translator->trans('Du hast das Haus von <strong>✝ {citizen}</strong> vollständig zerlegt. Alle Gegenstände aus dessen Truhe wurden in der Bank deponiert.', ['{citizen}' => $home->getCitizen()->getUser()->getName()], 'game') ];
            if ($has_recycled)
                $msg[] = $this->translator->trans('Die Stadt hat zudem folgende Resourcen zurückgewinnen können: {item_list}', [
                    '{item_list}' => implode(' ', $item_list)
                ], 'game');
            else
                $msg[] = $this->translator->trans('Die Stadt hat nichts Nützliches aus dem Haus herausbekommen, da war wirklich nichts zu holen...', [], 'game');

            $this->addFlash('notice', implode('<hr/>',$msg));
            $this->entity_manager->persist($this->log->houseRecycled($home->getCitizen(), $item_list_p));
        } else
            $this->addFlash('notice', $this->translator->trans('Du hast <strong>1AP</strong> aufgewendet, um das Haus von <strong>✝ {citizen}</strong> zu recyclen. Die Arbeiten sind noch nicht abgeschlossen...', ['{citizen}' => $home->getCitizen()->getUser()->getName()], 'game'));

        $this->entity_manager->persist($c);
        $this->entity_manager->persist($citizen);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("api/town/insurrect", name="town_insurrect")
     * @return Response
     */
    public function do_insurrection(GameProfilerService $gps): Response
    {
        /** @var Citizen $citizen */
        $citizen = $this->getUser()->getActiveCitizen();

        /** @var Town $town */
        $town = $citizen->getTown();

        if ($this->citizen_handler->hasStatusEffect($citizen, "tg_insurrection") || $town->getInsurrectionProgress() >= 100)
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $non_shunned = 0;

        //TODO: This needs huuuuge statistics
        foreach ($town->getCitizens() as $foreinCitizen)
            if ($foreinCitizen->getAlive() && !$foreinCitizen->getBanished()) $non_shunned++;

        $insurrectionProgress = intval(round(50 / $non_shunned));

        $gps->recordInsurrectionProgress($town, $citizen, $insurrectionProgress, $non_shunned);

        $town->setInsurrectionProgress($town->getInsurrectionProgress() + $insurrectionProgress);

        if ($town->getInsurrectionProgress() >= 100) {
            // Let's do the insurrection !
            $town->setInsurrectionProgress(100);

            /*$bank = $citizen->getTown()->getBank();
            $impound_prop = $this->entity_manager->getRepository(ItemProperty::class)->findOneBy(['name' => 'impoundable']);*/

            foreach ($town->getCitizens() as $foreignCitizen) {
                if(!$foreignCitizen->getAlive()) continue;

                // Remove complaints
                $complaints = $this->entity_manager->getRepository(Complaint::class)->findByCulprit($foreignCitizen);
                foreach ($complaints as $complaint)
                    $this->entity_manager->remove($complaint);

                if ($foreignCitizen->getBanished()) {
                    $foreignCitizen->setBanished(false);
                    $this->citizen_handler->inflictStatus($foreignCitizen, 'tg_revolutionist');
                } else {
                    $null = null;
                    $this->citizen_handler->updateBanishment($foreignCitizen, null, null, $null, true);
                }

                $this->entity_manager->persist($foreignCitizen);
            }
        }

        $this->citizen_handler->inflictStatus($citizen, "tg_insurrection");

        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success( true, ['url' => $this->generateUrl('town_dashboard')]);
    }
}
