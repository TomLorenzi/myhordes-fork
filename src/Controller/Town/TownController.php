<?php

namespace App\Controller\Town;

use App\Controller\InventoryAwareController;
use App\Controller\TownInterfaceController;
use App\Entity\ActionCounter;
use App\Entity\Building;
use App\Entity\BuildingVote;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenRole;
use App\Entity\CitizenVote;
use App\Entity\Complaint;
use App\Entity\ExpeditionRoute;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\TownLogEntry;
use App\Entity\PrivateMessage;
use App\Entity\User;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Service\BankAntiAbuseService;
use App\Structures\TownConf;
use App\Translation\T;
use App\Response\AjaxResponse;
use App\Service\AdminActionHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\CitizenInfo;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class TownController extends InventoryAwareController implements TownInterfaceController
{
    const ErrorWellEmpty         = ErrorHelper::BaseTownErrors + 1;
    const ErrorWellLimitHit      = ErrorHelper::BaseTownErrors + 2;
    const ErrorWellNoWater       = ErrorHelper::BaseTownErrors + 3;
    const ErrorDoorAlreadyClosed = ErrorHelper::BaseTownErrors + 4;
    const ErrorDoorAlreadyOpen   = ErrorHelper::BaseTownErrors + 5;
    const ErrorNotEnoughRes      = ErrorHelper::BaseTownErrors + 6;
    const ErrorAlreadyUpgraded   = ErrorHelper::BaseTownErrors + 7;
    const ErrorComplaintLimitHit = ErrorHelper::BaseTownErrors + 8;

    protected function get_needed_votes(): array {
        $town = $this->getActiveCitizen()->getTown();
        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();

        $votesNeeded = array();
        foreach ($roles as $role)
            $votesNeeded[$role->getName()] = ($town->getChaos() || $town->isOpen()) ? null : $role;

        if(!$town->isOpen() && !$town->getChaos())
            foreach ($roles as $role)
                foreach ($town->getCitizens() as $citizen)
                    if($citizen->getRoles()->contains($role)) {
                        if ($citizen->getAlive()) $votesNeeded[$role->getName()] = false;
                        else if ($citizen->getSurvivedDays() >= ($citizen->getTown()->getDay() - 1) && $citizen->getCauseOfDeath()->getRef() !== CauseOfDeath::NightlyAttack)
                            $votesNeeded[$role->getName()] = false;
                    }

        return $votesNeeded;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];

        $addons = [];
        $town = $this->getActiveCitizen()->getTown();

        $data["builtbuildings"] = array();

        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {

            if ($b->getPrototype()->getMaxLevel() > 0)
                $addons['upgrade']  = [T::__('Verbesserung des Tages (building)', 'game'), 'town_upgrades', 0];

            if ($b->getPrototype()->getName() === 'item_tagger_#00')
                $addons['watchtower'] = [T::__('Wachturm', 'game'), 'town_watchtower', 1];

            if ($b->getPrototype()->getName() === 'small_refine_#00')
                $addons['workshop'] = [T::__('Werkstatt (building)', 'game'), 'town_workshop', 2];

            if ($b->getPrototype()->getName() === 'small_round_path_#00' && $this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
                $addons['battlement'] = [T::__('Wächt', 'game'), 'town_nightwatch', 3];

            if ($b->getPrototype()->getName() === 'small_trash_#00')
                $addons['dump'] = [T::__('Müllhalde', 'game'), 'town_dump', 4];

            if ($b->getPrototype()->getName() === 'item_courroie_#00')
                $addons['catapult'] = [T::__('Katapult', 'game'), 'town_dashboard', 5];
            

            $data["builtbuildings"][] = $b;

        }

        $data['addons'] = $addons;
        $data['home'] = $this->getActiveCitizen()->getHome();
        $data['chaos'] = $town->getChaos();
        $data['town'] = $town;

        if ($section == "citizens")
            $data['votesNeeded'] = $this->get_needed_votes();

        $data["new_message"] = $this->citizen_handler->hasNewMessage($this->getActiveCitizen());
        return parent::addDefaultTwigArgs( $section, $data );
    }

    /**
     * @Route("jx/town/dashboard", name="town_dashboard")
     * @param TownHandler $th
     * @return Response
     */
    public function dashboard(TownHandler $th): Response
    {
        $town = $this->getActiveCitizen()->getTown();

        $has_zombie_est_today    = !empty($th->getBuilding($town, 'item_tagger_#00'));
        $has_zombie_est_tomorrow = !empty($th->getBuilding($town, 'item_tagger_#02'));

        $citizens = $town->getCitizens();
        $alive = 0;
        foreach ($citizens as $citizen) {
            if($citizen->getAlive())
                $alive++;
        }

        $z_today_min = $z_today_max = $z_tomorrow_min = $z_tomorrow_max = null; $z_q = 0;
        if ($has_zombie_est_today) $z_q = $th->get_zombie_estimation_quality( $town, 0, $z_today_min, $z_today_max );
        if ($has_zombie_est_today && $has_zombie_est_tomorrow && $z_q >= 1) $th->get_zombie_estimation_quality( $town, 1, $z_tomorrow_min, $z_tomorrow_max );

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

        $est0 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay());
        $has_estimated = $est0 && ($est0->getCitizens()->contains($this->getActiveCitizen()) || $th->get_zombie_estimation_quality($town) >= 1.0);

        $display_home_upgrade = false;
        foreach ($citizens as $citizen) {
            if($citizen->getHome()->getPrototype()->getLevel() > $this->getActiveCitizen()->getHome()->getPrototype()->getLevel()){
                $display_home_upgrade = true;
                break;
            }
        }

        $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();
        $has_voted = array();

        if(!$town->isOpen() && !$town->getChaos())
            foreach ($roles as $role)
                $has_voted[$role->getName()] = ($this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($this->getActiveCitizen(), $role) !== null);

        $can_edit_blackboard = $this->getActiveCitizen()->getProfession()->getHeroic() && $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'dictator') && !$this->getActiveCitizen()->getBanished();

        return $this->render( 'ajax/game/town/dashboard.html.twig', $this->addDefaultTwigArgs(null, [
            'town' => $town,
            'def' => $th->calculate_town_def($town, $defSummary),
            'zeds_today'    => [ $has_zombie_est_today, $z_today_min, $z_today_max, round($z_q*100) ],
            'zeds_tomorrow' => [ $has_zombie_est_tomorrow, $z_tomorrow_min, $z_tomorrow_max ],
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
            'has_been_active' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_chk_active'),
            'display_home_upgrade' => $display_home_upgrade,
            'has_upgraded_house' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_home_upgrade'),
            'can_edit_blackboard' => $can_edit_blackboard,
        ]) );
    }

    /**
     * @Route("jx/town/visit/{id}/headshot", name="town_visit_headshot", requirements={"id"="\d+"})
     * @param int $id
     * @param AdminActionHandler $admh
     * @return Response
     */
    public function visitHeadshot(int $id, AdminActionHandler $admh): Response
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
     * @param TownHandler $th
     * @return Response
     */
    public function visit(int $id, EntityManagerInterface $em, TownHandler $th): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return $this->redirect($this->generateUrl('town_house'));

        /** @var Citizen $c */
        $c = $em->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
            return $this->redirect($this->generateUrl('town_dashboard'));

        $home = $c->getHome();
        $user = $this->getUser();

        $th->calculate_home_def($home, $summary);
        $deco = 0;
        foreach ($home->getChest()->getItems() as $item)
            $deco += $item->getPrototype()->getDeco();

        $town = $this->getActiveCitizen()->getTown();
        $lastActionTimestamp = $c->getLastActionTimestamp();

        // Getting delta time between now and the last action
        $time = time() - $lastActionTimestamp; 
        $time = abs($time); 

        if ($time > 10800) {
            // If it was more than 3 hours, let's get the full date/time format
            $lastActionText =$this->translator->trans('am', [], 'game') . ' '. date('d/m/Y, H:i', $lastActionTimestamp);
        } else {
            // Tableau des unités et de leurs valeurs en secondes
            $times = array( 3600     =>  T::__('Stunde(n)', 'game'),
                            60       =>  T::__('Minute(n)', 'game'),
                            1        =>  T::__('Sekunde(n)', 'game'));  

            foreach ($times as $seconds => $unit) {
                $delta = round($time / $seconds); 

                if ($delta >= 1) {
                    $unit = $this->translator->trans($unit, [], 'game');
                    $lastActionText = $this->translator->trans('vor %time%', ['%time%' => "$delta $unit"], 'game');
                    break;
                }
            }
        }

        $hidden = (bool)($em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($home,
            $em->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName('curtain')
        )) && $this->citizen_handler->houseIsProtected($c);

        $is_injured    = $this->citizen_handler->isWounded($c);
        $is_infected   = $this->citizen_handler->hasStatusEffect($c, 'infection');
        $is_thirsty    = $this->citizen_handler->hasStatusEffect($c, "thirst2");
        $is_addicted   = $this->citizen_handler->hasStatusEffect($c, 'addict');
        $is_terrorised = $this->citizen_handler->hasStatusEffect($c, 'terror');
        $has_job       = $c->getProfession()->getName() != 'none';
        $is_admin      = $c->getUser()->getRightsElevation() >= User::ROLE_ADMIN;
        $already_stolen = $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_steal') && !$this->getActiveCitizen()->getTown()->getChaos();

        $hasClairvoyance = false;
        $clairvoyanceLevel = 0;

        if ($this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), 'clairvoyance') && $this->getActiveCitizen()->getProfession()->getHeroic()) {
            $hasClairvoyance = true;
            if($this->citizen_handler->hasStatusEffect($c, 'tg_chk_forum')){
                $clairvoyanceLevel++;
            }
            if($this->citizen_handler->hasStatusEffect($c, 'tg_chk_active')){
                $clairvoyanceLevel++;
            }
            if($this->citizen_handler->hasStatusEffect($c, 'tg_chk_workshop')){
                $clairvoyanceLevel++;
            }
            if($this->citizen_handler->hasStatusEffect($c, 'tg_chk_build')){
                $clairvoyanceLevel++;
            }
            if($this->citizen_handler->hasStatusEffect($c, 'tg_chk_movewb')){
                $clairvoyanceLevel++;
            }
        }

        return $this->render( 'ajax/game/town/home_foreign.html.twig', $this->addDefaultTwigArgs('citizens', [
            'owner' => $c,
            'can_attack' => !$this->citizen_handler->isTired($this->getActiveCitizen()) && $this->getActiveCitizen()->getAp() >= 5,
            'can_devour' => $this->getActiveCitizen()->hasRole('ghoul'),
            'allow_devour' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_ghoul_eat'),
            'allow_devour_corpse' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_ghoul_corpse'),
            'home' => $home,
            'actions' => $this->getItemActions(),
            'complaint' => $this->entity_manager->getRepository(Complaint::class)->findByCitizens( $this->getActiveCitizen(), $c ),
            'complaints' => $this->entity_manager->getRepository(Complaint::class)->countComplaintsFor( $c ),
            'chest' => $home->getChest(),
            'chest_size' => $this->inventory_handler->getSize($home->getChest()),
            'has_cremato' => $th->getBuilding($town, 'item_hmeat_#00', true) !== null,
            'lastActionText' => $lastActionText,
            'def' => $summary,
            'deco' => $deco,
            'time' => $time,
            'is_injured' => $is_injured,
            'is_infected' => $is_infected,
            'is_thirsty' => $is_thirsty,
            'is_addicted' => $is_addicted,
            'is_terrorised' => $is_terrorised,
            'has_job' => $has_job,
            'is_admin' => $is_admin,
            'log' => $this->renderLog( -1, $c, false, null, 10 )->getContent(),
            'day' => $c->getTown()->getDay(),
            'already_stolen' => $already_stolen,
            'hidden' => $hidden,
            'hasClairvoyance' => $hasClairvoyance,
            'clairvoyanceLevel' => $clairvoyanceLevel,
            'attackAP' => $this->getTownConf()->get( TownConf::CONF_MODIFIER_ATTACK_AP, 4 )
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
     * @param TownHandler $th
     * @param ItemFactory $if
     * @return Response
     */
    public function dispose_visit_api(int $id, EntityManagerInterface $em, JSONRequestParser $parser, TownHandler $th, ItemFactory $if): Response {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $ac = $this->getActiveCitizen();

        /** @var Citizen $c */
        $c = $em->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getAlive() || !$c->getHome()->getHoldsBody())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $action = (int)$parser->get('action');

        if ($action < 1 || $action > 3)
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $spawn_items = [];
        $pictoName = "";
        $message = "";
        switch ($action) {
            case 1:
                // Thrown outside
                if ($ac->getAp() < 2 || $this->citizen_handler->isTired( $ac ))
                    return AjaxResponse::error( ErrorHelper::ErrorNoAP );
                $this->citizen_handler->setAP($ac, true, -2);
                $pictoName = "r_cgarb_#00";
                $message = $this->translator->trans('Du hast die Leiche von %disposed% außerhalb der Stadt entsorgt. Eine gute Sache, die Sie getan haben!', ['%disposed%' => '<span>' . $c->getUser()->getUsername() . '</span>'], 'game');
                $c->setDisposed(Citizen::Thrown);
                $c->addDisposedBy($ac);
                break;
            case 2:
                // Watered
                $items = $this->inventory_handler->fetchSpecificItems( $ac->getInventory(), [new ItemRequest('water_#00')] );
                if (!$items) return AjaxResponse::error(ErrorHelper::ErrorItemsMissing );
                $this->inventory_handler->forceRemoveItem( $items[0] );
                $pictoName = "r_cwater_#00";
                $message = $this->translator->trans('Der Körper verflüssigte sich zu einer ekelerregenden, übel riechenden Pfütze. Deine Schuhe haben ganz schön was abgekriegt, das steht fest...', [], 'game');
                $c->setDisposed(Citizen::Watered);
                $c->addDisposedBy($ac);
                break;
            case 3:
                // Cooked
                $town = $ac->getTown();
                if (!$th->getBuilding($town, 'item_hmeat_#00', true))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                $spawn_items[] = [ 'item' => $em->getRepository( ItemPrototype::class )->findOneByName( 'hmeat_#00' ), 'count' => 4 ];
                $pictoName = "r_cooked_#00";
                $message = $this->translator->trans('Sie brachten die Leiche von %disposed% zum Kremato-Cue. Man bekommt %ration% Rationen davon...  Aber zu welchem Preis?', ['%disposed%' => '<span>' . $c->getUser()->getUsername() . '</span>','%ration%' => '<span>4</span>'], 'game');
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
        $pictoPrototype = $em->getRepository(PictoPrototype::class)->findOneByName($pictoName);
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

        if ($this->getActiveCitizen()->getBanished())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $severity = (int)$parser->get('severity', -1);
        if ($severity < Complaint::SeverityNone || $severity > Complaint::SeverityKill)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest );

        $author = $this->getActiveCitizen();
        $town = $author->getTown();

        /** @var Citizen $culprit */
        $culprit = $em->getRepository(Citizen::class)->find( $id );
        if (!$culprit || $culprit->getTown()->getId() !== $town->getId() || !$culprit->getAlive() )
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        // Check permission: dummy accounts may not complain against non-dummy accounts (dummy is any account which email ends on @localhost)
        if ($this->isGranted('ROLE_DUMMY', $author) && !$this->isGranted('ROLE_DUMMY', $culprit))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError );

        $existing_complaint = $em->getRepository( Complaint::class )->findByCitizens($author, $culprit);
        $severity_before = $existing_complaint ? $existing_complaint->getSeverity() : 0;

        if ($severity > Complaint::SeverityNone) {
            $counter = $this->getActiveCitizen()->getSpecificActionCounter(ActionCounter::ActionTypeComplaint);
            if ($counter->getCount() >= 4)
                return AjaxResponse::error(self::ErrorComplaintLimitHit );
            $counter->increment();
            $this->entity_manager->persist($counter);
        }

        $complaint_level = 0;
        if (!$existing_complaint) {
            $existing_complaint = (new Complaint())
                ->setAutor( $author )
                ->setCulprit( $culprit )
                ->setSeverity( $severity )
                ->setCount( ($author->getProfession()->getHeroic() && $th->getBuilding( $town, 'small_court_#00', true )) ? 2 : 1 );
            $culprit->addComplaint( $existing_complaint );

            $complaint_level = ($severity > Complaint::SeverityNone) ? 1 : 0;

        } else {

            if ($existing_complaint->getSeverity() > Complaint::SeverityNone && $severity === Complaint::SeverityNone)
                $complaint_level = -1;
            else if ($existing_complaint->getSeverity() === Complaint::SeverityNone && $severity > Complaint::SeverityNone)
                $complaint_level = 1;

            $existing_complaint->setSeverity( $severity );
        }

        try {
            if ($severity !== $severity_before && ($severity === 0 || $severity_before === 0)) $em->persist( $this->log->citizenComplaint( $existing_complaint ) );
            $em->persist($culprit);
            $em->persist($existing_complaint);
            $em->flush();

            if ($complaint_level != 0) {
                $this->crow->postAsPM( $culprit, '', '', $complaint_level > 0 ? PrivateMessage::TEMPLATE_CROW_COMPLAINT_ON : PrivateMessage::TEMPLATE_CROW_COMPLAINT_OFF, $existing_complaint->getId() );
                $em->flush();
            }

        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if ($this->citizen_handler->updateBanishment( $culprit, $th->getBuilding( $town, 'r_dhang_#00', true ), $th->getBuilding( $town, 'small_fleshcage_#00', true ) ))
            try {
                $em->persist($town);
                $em->persist($culprit);
                $em->flush();
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

        $direction = $parser->get('direction', '');

        $up_inv   = $direction === 'down' ? $ac->getInventory() : $ac->getHome()->getChest();
        $down_inv = $c->getHome()->getChest();
        return $this->generic_item_api( $up_inv, $down_inv, false, $parser, $handler);
    }

    /**
     * @Route("jx/town/well", name="town_well")
     * @param TownHandler $th
     * @return Response
     */
    public function well(TownHandler $th): Response
    {
        $town = $this->getActiveCitizen()->getTown();
        $pump = $th->getBuilding( $town, 'small_water_#00', true );

        $allow_take = 1;
        if($pump) {
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
            'pump' => $pump,

            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeWell, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/town/well/log", name="town_well_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_well_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, TownLogEntry::TypeWell, null);
    }

    /**
     * @Route("api/town/well/item", name="town_well_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $factory
     * @param TownHandler $th
     * @return Response
     */
    public function well_api(JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $factory, TownHandler $th, BankAntiAbuseService $ba): Response {
        $direction = $parser->get('direction', '');

        if (in_array($direction, ['up','down'])) {
            $citizen = $this->getActiveCitizen();

            $town = $citizen->getTown();

            $limit = 1;
            if($th->getBuilding($town, 'small_water_#00', true)) {
                if($town->getChaos()) {
                    $limit = 3;
                } else if  (!$this->getActiveCitizen()->getBanished()) {
                    $limit = 2;
                }
            }

            if ($direction == 'up') {
                if ($town->getWell() <= 0) return AjaxResponse::error(self::ErrorWellEmpty);

                $counter = $citizen->getSpecificActionCounter(ActionCounter::ActionTypeWell);

                if ($counter->getCount() >= $limit) return AjaxResponse::error(self::ErrorWellLimitHit);

                $inv_target = $citizen->getInventory();
                $inv_source = null;
                $item = $factory->createItem( 'water_#00' );

                if ($counter->getCount() > 0 && !$ba->allowedToTake( $citizen ))
                    return AjaxResponse::error( InventoryHandler::ErrorBankLimitHit );

                if (($error = $handler->transferItem(
                    $citizen,
                    $item,$inv_source, $inv_target
                )) === InventoryHandler::ErrorNone) {
                    if ($counter->getCount() > 0)
                        $ba->increaseBankCount( $citizen );

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
                    return AjaxResponse::success();
                } else return AjaxResponse::error($error);
            } else {

                $items = $handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest('water_#00')] );
                if (empty($items)) return AjaxResponse::error(self::ErrorWellNoWater);

                $inv_target = null;
                $inv_source = $citizen->getInventory();

                if (($error = $handler->transferItem(
                        $citizen,
                        $items[0],$inv_source, $inv_target
                    )) === InventoryHandler::ErrorNone) {
                    $town->setWell( $town->getWell()+1 );
                    try {
                        $this->entity_manager->persist( $this->log->wellAdd( $citizen, $items[0]->getPrototype(), 1) );
                        $this->entity_manager->remove($items[0]);
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
            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeBank, 10 )->getContent(),
            'day' => $town->getDay()
        ]) );
    }

    /**
     * @Route("api/town/bank/log", name="town_bank_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_bank_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, TownLogEntry::TypeBank, null);
    }

    /**
     * @Route("api/town/bank/item", name="town_bank_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function item_bank_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
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

        return $this->render( 'ajax/game/town/citizen.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $citizenInfos,
            'me' => $this->getActiveCitizen(),
            'hidden' => $hidden,
            'prof_count' => $prof_count,
            'death_count' => $death_count,
        ]) );
    }

    /**
     * @Route("jx/town/citizens/vote/{roleId}", name="town_citizen_vote", requirements={"id"="\d+"})
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function citizens_vote(int $roleId, EntityManagerInterface $em): Response
    {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($town->getChaos()){
            // No vote possible in chaos
            return $this->redirect($this->generateUrl('town_citizens'));
        }

        $needed_roles = $this->get_needed_votes();
        /** @var CitizenRole $role */
        $role = $this->entity_manager->getRepository(CitizenRole::class)->find($roleId);
        if($role === null || !isset($needed_roles[$role->getName()]) || !$needed_roles[$role->getName()])
            return $this->redirect($this->generateUrl('town_citizens'));

        $vote = $this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($this->getActiveCitizen(), $role);

        return $this->render( 'ajax/game/town/citizen_vote.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $town->getCitizens(),
            'me' => $this->getActiveCitizen(),
            'selectedRole' => $role,
            'vote' => $vote
        ]) );
    }

    /**
     * @Route("api/town/citizens/send_vote", name="town_citizens_send_vote")
     * @param JSONRequestParser $parser
     * @param TownHandler $th
     * @return Response
     */
    public function citizens_send_vote_api(JSONRequestParser $parser, TownHandler $th): Response {
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
     * @Route("api/town/constructions/build", name="town_constructions_build_controller")
     * @param JSONRequestParser $parser
     * @param TownHandler $th
     * @return Response
     */
    public function construction_build_api(JSONRequestParser $parser, TownHandler $th): Response {
        // Get citizen & town
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if the request is complete
        if (!$parser->has_all(['id','ap'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');
        $ap = (int)$parser->get('ap');

        // Check if slave labor is allowed (ministry of slavery must be built)
        $slavery_allowed = $th->getBuilding($town, 'small_slave_#00', true) !== null;

        // If no slavery is allowed, block banished citizens from working on the construction site
        // If slavery is allowed and the citizen is banished, permit slavery bonus
        if (!$slavery_allowed && $citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $slave_bonus = $citizen->getBanished();

        /** @var Building|null $building */
        // Get the building the citizen wants to work on; fail if we can't find it
        $building = $this->entity_manager->getRepository(Building::class)->find($id);
        if (!$building || $building->getTown()->getId() !== $town->getId() || $ap <= 0)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Check if all parent buildings are completed
        $current = $building->getPrototype();
        while ($parent = $current->getParent()) {
            if (!$th->getBuilding($town, $parent, true))
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
            $current = $parent;
        }

        $workshopBonus = 1;
        $hpToAp = 2;
        if(($workshop = $th->getBuilding($town, "small_refine_#00")) !== null){
            $level = $workshop->getLevel();
            switch($level){
                case 1:
                    $workshopBonus = 0.94;
                    break;
                case 2:
                    $workshopBonus = 0.88;
                    break;
                case 3:
                    $workshopBonus = 0.82;
                    break;
                case 4:
                    $workshopBonus = 0.76;
                    $hpToAp = 3;
                    break;
                case 5:
                    $workshopBonus = 0.70;
                    $hpToAp = 4;
                    break;
            }
        }

        // Remember if the building has already been completed (i.e. this is a repair action)
        $was_completed = $building->getComplete();

        // Check out how much AP is missing to complete the building; restrict invested AP to not exceed this
        if(!$was_completed) {
            $missing_ap = ceil( (round($building->getPrototype()->getAp()*$workshopBonus) - $building->getAp()) * ( $slave_bonus ? (2.0/3.0) : 1 )) ;
            $ap = max(0,min( $ap, $missing_ap ) );
        } else {
            $neededApForFullHp = ($building->getPrototype()->getHp() - $building->getHp()) / $hpToAp;
            $missing_ap = ceil( (round($neededApForFullHp) * ( $slave_bonus ? (2.0/3.0) : 1 ))) ;
            $ap = max(0,min( $ap, $missing_ap ) );
        }

        // If the citizen has not enough AP, fail
        if ($ap > 0 && ($citizen->getAp() + $citizen->getBp()) < $ap || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Get all resources needed for this building
        $res = $items = [];
        if (!$building->getComplete() && $building->getPrototype()->getResources())
            foreach ($building->getPrototype()->getResources()->getEntries() as $entry)
                if (!isset($res[ $entry->getPrototype()->getName() ]))
                    $res[ $entry->getPrototype()->getName() ] = new ItemRequest( $entry->getPrototype()->getName(), $entry->getChance() );
                else $res[ $entry->getPrototype()->getName() ]->addCount( $entry->getChance() );

        // If the building needs resources, check if they are present in the bank; otherwise fail
        if (!empty($res)) {
            $items = $this->inventory_handler->fetchSpecificItems($town->getBank(), $res);
            if (empty($items)) return AjaxResponse::error( self::ErrorNotEnoughRes );
        }

        // Create a log entry
        if ($th->getBuilding($town, 'item_rp_book2_#00', true)) {
            if (!$was_completed)
                $this->entity_manager->persist( $this->log->constructionsInvestAP( $citizen, $building->getPrototype(), $ap ) );
            else
                $this->entity_manager->persist( $this->log->constructionsInvestRepairAP( $citizen, $building->getPrototype(), $ap ) );
        }


        // Calculate the amount of AP that will be invested in the construction
        $ap_effect = floor( $ap * ( $slave_bonus ? 1.5 : 1 ) );

        // Deduct AP and increase completion of the building
        $this->citizen_handler->deductAPBP( $citizen, $ap );

        if($missing_ap <= 0 || $missing_ap - $ap <= 0){
            // Missing ap == 0, the building has been completed by the workshop upgrade.
            $building->setAp($building->getPrototype()->getAp());
        } else {
            $building->setAp($building->getAp() + $ap_effect);
        }

        // If the building was not previously completed but reached 100%, complete the building and trigger the completion handler
        $building->setComplete( $building->getComplete() || $building->getAp() >= $building->getPrototype()->getAp() );

        if (!$was_completed && $building->getComplete()) {
            // Remove resources, create a log entry, trigger
            foreach ($items as $item)
                $this->inventory_handler->forceRemoveItem( $item, $res[ $item->getPrototype()->getName() ]->getCount() );

            $this->entity_manager->persist( $this->log->constructionsBuildingComplete( $citizen, $building->getPrototype() ) );
            $th->triggerBuildingCompletion( $town, $building );
            $votes = $building->getBuildingVotes();
            foreach ($votes as $vote) {
                $vote->getCitizen()->setBuildingVote(null);
                $vote->getBuilding()->removeBuildingVote($vote);
                $this->entity_manager->remove($vote);
            }
        } else if ($was_completed) {
            $newHp = min($building->getPrototype()->getHp(), $building->getHp() + $ap_effect * $hpToAp);
            $building->setHp($newHp);
            if($building->getPrototype()->getDefense() > 0) {
                $newDef = min($building->getPrototype()->getDefense(), $building->getPrototype()->getDefense() * $building->getHp() / $building->getPrototype()->getHp());
                $building->setDefense($newDef);
            }
        }

        // Set the activity status
        $this->citizen_handler->inflictStatus($citizen, 'tg_chk_active');
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

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/constructions", name="town_constructions")
     * @param TownHandler $th
     * @return Response
     */
    public function constructions(TownHandler $th): Response
    {
        $town = $this->getActiveCitizen()->getTown();
        $buildings = $town->getBuildings();

        $workshopBonus = 1;
        $hpToAp = 2;

        if(($workshop = $th->getBuilding($town, "small_refine_#00")) !== null){
            $level = $workshop->getLevel();
            switch($level){
                case 1:
                    $workshopBonus = 0.94;
                    break;
                case 2:
                    $workshopBonus = 0.88;
                    break;
                case 3:
                    $workshopBonus = 0.82;
                    break;
                case 4:
                    $workshopBonus = 0.76;
                    $hpToAp = 3;
                    break;
                case 5:
                    $workshopBonus = 0.70;
                    $hpToAp = 4;
                    break;
            }
        }

        $root = [];
        $dict = [];
        $items = [];

        foreach ($buildings as $building) {
            $dict[ $building->getPrototype()->getId() ] = [];
            if (!$building->getPrototype()->getParent()) $root[] = $building;
            if (!$building->getComplete() && !empty($building->getPrototype()->getResources()))
                foreach ($building->getPrototype()->getResources()->getEntries() as $resource)
                    if (!isset($items[$resource->getPrototype()->getId()]))
                        $items[$resource->getPrototype()->getId()] = $this->inventory_handler->countSpecificItems( $this->getActiveCitizen()->getTown()->getBank(), $resource->getPrototype(), false, false );
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
            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeConstruction, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
            'canvote' => $this->user_handler->hasSkill($this->getActiveCitizen()->getUser(), "dictator") && !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_build_vote'),
            'voted_building' => $votedBuilding,
        ]) );
    }

    /**
     * @Route("api/town/constructions/vote", name="town_constructions_vote_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function upgrades_votes_api(JSONRequestParser $parser): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

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
        return $this->renderLog((int)$parser->get('day', -1), null, false, TownLogEntry::TypeConstruction, null);
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
        if ($action === 'open'  && $this->door_is_locked($th))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($action === 'close' && !$town->getDoor())
            return AjaxResponse::error( self::ErrorDoorAlreadyClosed );

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

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

        // Set the activity status
        $this->citizen_handler->inflictStatus($citizen, 'tg_chk_active');

        if ($special !== 'sneak')
            $this->entity_manager->persist( $this->log->doorPass( $citizen, false ) );
        $zone->addCitizen( $citizen );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    private function door_is_locked(TownHandler $th): bool {
        $town = $this->getActiveCitizen()->getTown();
        if ( !$town->getDoor() ) {
            if($town->getType()->getName() === 'custom' && $town->isOpen()) return true;
            if((($s = $this->time_keeper->secondsUntilNextAttack(null, true)) <= 1800)) {
                if ($th->getBuilding( $town, 'small_door_closed_#02', true )) {
                    if ($s <= 60) return true;
                } elseif ($th->getBuilding( $town, 'small_door_closed_#01', true )) {
                    if ($s <= 1800) return true;
                } elseif ($th->getBuilding( $town, 'small_door_closed_#00', true )) {
                    if ($s <= 1200) return true;
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

        $door_locked = $this->door_is_locked($th);
        $can_go_out = !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tired') && $this->getActiveCitizen()->getAp() > 0;

        $town = $this->getActiveCitizen()->getTown();

        return $this->render( 'ajax/game/town/door.html.twig', $this->addDefaultTwigArgs('door', array_merge([
            'def'               => $th->calculate_town_def($town, $defSummary),
            'town'              => $town,
            'door_locked'       => $door_locked,
            'can_go_out'        => $can_go_out,
            'show_ventilation'  => $th->getBuilding($this->getActiveCitizen()->getTown(), 'small_ventilation_#00',  true) !== null,
            'allow_ventilation' => $this->getActiveCitizen()->getProfession()->getHeroic(),
            'show_sneaky'       => $this->getActiveCitizen()->hasRole('ghoul'),
            'log'               => $this->renderLog( -1, null, false, TownLogEntry::TypeDoor, 10 )->getContent(),
            'day'               => $this->getActiveCitizen()->getTown()->getDay(),
        ], $this->get_map_blob())) );
    }

    /**
     * @Route("api/town/door/log", name="town_door_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_door_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, TownLogEntry::TypeDoor, null);
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
        if (!$data || !is_array($data) || count($data) > 32 || count($data) < 2)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($citizen->getExpeditionRoutes()->count() >= 12)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $last = null; $ap = 0;
        foreach ($data as $entry)
            if (!is_array($entry) && count($entry) !== 2) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
            else {
                list($x,$y) = $entry;
                if (!is_int($x) || !is_int($y)) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if (!$this->entity_manager->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), $x, $y))
                    return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

                if ($last !== null) {
                    if ($last[0] !== $x && $last[1] !== $y) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    if ($last[0] === $x && $last[1] === $y) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
                    $ap += (abs($last[0] - $x) + abs($last[1] - $y));
                }
                $last = [$x,$y];
            }

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
     * @Route("jx/town/planner", name="town_planner")
     * @return Response
     */
    public function planner(): Response
    {
        return $this->render( 'ajax/game/town/planner.html.twig', $this->addDefaultTwigArgs('door', array_merge([
            'town'  =>  $this->getActiveCitizen()->getTown(),
            'allow_extended' => $this->getActiveCitizen()->getProfession()->getHeroic()
        ], $this->get_map_blob())) );
    }

    /**
     * @Route("api/town/dashboard/wordofheroes", name="town_dashboard_save_woh")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function dashboard_save_wordofheroes_api(JSONRequestParser $parser): Response {
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_WORDS_OF_HEROS, false) || !$this->getActiveCitizen()->getProfession()->getHeroic())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($this->getActiveCitizen()->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        // Get town
        $town = $this->getActiveCitizen()->getTown();

        $new_words_of_heroes = $parser->get('content');

        $town->setWordsOfHeroes($new_words_of_heroes);

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
        if($citizen->getPM() < 2 || $this->citizen_handler->hasStatusEffect($citizen, ['drugged', 'drunk', 'infected', 'terror'])) {
            $message[] = $this->translator->trans('In deinem aktuellen Zustand kannst du diese Aktion nicht ausführen.', [], 'game');
            $this->addFlash('notice', implode('<hr />', $message));
            return AjaxResponse::success();
        }

        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getZone())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        $healableStatus = [
            'terror' => array(
                'success' => T::__('Es gibt nichts Besseres als die Furcht, um eine Angststarre zu heilen. Man nimmt die Schamanenmaske ab und bläst dem Patienten ein selbst hergestelltes halluzinogenes Pulver auf das Gesicht, um einen schlafwandelnden Zustand herbeizuführen. Das provoziert schnell "pavor nocturnus". Als %citizen% wieder erwacht, scheint er von seiner Angststarre befreit zu sein.', 'game'),
                'transfer' => T::__('Allerdings hat dich der Anblick dieses bis aufs Mark verängstigen Bürgers selbst in eine Angststarre versetzt.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, %citizen% umzubringen ist zu hoch...', 'game'),
            ),
            'infected' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt an der Basis des infizierten Körperteils. Der Entgiftungsprozess ist im Gange, wenn auch langsam.', 'game'),
                'transfer' => T::__('Plötzlich platzt eine infizierte Eiterblase auf. Deine bereits verbrannte Haut bricht schnell in offene Wunden aus, und die infektiösen Keime beschließen, diese zu ihrem Zuhause zu machen.', 'game'),
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, %citizen% umzubringen ist zu hoch...', 'game'),
            ),
            'drunk' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt nahe der Leber. %citizen% ist aus den Krallen des Alkohols befreit.', 'game'),
                'transfer' => 'You end up with this status yourself !', //TODO: translate this text with the original one (from D2N maybe)
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, %citizen% umzubringen ist zu hoch...', 'game'),
            ),
            'drugged' => array(
                'success' => T::__('Du hebst dein heiliges Messer aus der Scheide und beginnst, dich nach einer gut eingeübten Abfolge ritueller Bewegungen "vorzubereiten". Der Energiefluss leitet dich, und ohne zu zögern machst du einen Einschnitt nahe der rechten Lunge. So sehr du auch versuchst, den Kräften zu widerstehen, die dich führen, kannst du nicht verhindern, dass deine Klinge tief in %citizen% eindringt und eine klare Flüssigkeit aus seinem frisch verstümmelten Körper austritt.', 'game'),
                'transfer' => 'You end up with this status yourself !', //TODO: translate this text with the original one (from D2N maybe)
                'fail' => T::__('Nichts... du fühlst nichts, keine Energie, kein Fluss auf den du dich verlassen könntest. Das Risiko, %citizen% umzubringen ist zu hoch...', 'game'),
            ),
        ];

        if(!$this->citizen_handler->hasStatusEffect($c, array_keys($healableStatus)) || $c->getZone() || $this->citizen_handler->hasStatusEffect($c, 'tg_shaman_heal')){
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
        $healChances = $this->random_generator->chance(0.6);
        if($healChances) {

            $this->citizen_handler->removeStatus($c, $healedStatus);

            $message[] = $this->translator->trans($healableStatus[$healedStatus]['success'], ['%citizen%' => "<span>" . $c->getUser()->getUsername() . "</span>"], 'game');

            $transfer = $this->random_generator->chance(0.1);
            if($transfer){
                $this->citizen_handler->inflictStatus($citizen, $healedStatus);
                $message[] = $this->translator->trans($healableStatus[$healedStatus]['transfer'], ['%citizen%' => "<span>" . $c->getUser()->getUsername() . "</span>"], 'game');
            }
        } else {
            $message[] = $this->translator->trans($healableStatus[$healedStatus]['fail'], ['%citizen%' => "<span>" . $c->getUser()->getUsername() . "</span>"], 'game');
        }
        $citizen->setPM($citizen->getPM() - 2);

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
        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId())
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
    public function visit_recycle_home(int $id, ItemFactory $if): Response
    {
        if ($id === $this->getActiveCitizen()->getId())
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        /** @var Citizen $c */
        $c = $this->entity_manager->getRepository(Citizen::class)->find( $id );
        if (!$c || $c->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId() || $c->getAlive())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable);

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        if($c->getHome()->getRecycling() >= 15){
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $this->citizen_handler->setAP($citizen, true, -1);
        $home = $c->getHome();
        $home->setRecycling($home->getRecycling() + 1);

        if($home->getRecycling() >= 15 && $home->getPrototype()->getResources()) {
            // Fetch upgrade resources
            if ($home->getPrototype()->getResources()) {
                $entries = $home->getPrototype()->getResources()->getEntries();
                foreach ($entries as $entry) {
                    for($i = 0 ; $i < $entry->getChance(); $i++){
                        $this->inventory_handler->forceMoveItem( $citizen->getTown()->getBank(), $if->createItem($entry->getPrototype()->getName()));
                    }
                }
            }

            foreach ($home->getChest()->getItems() as $item) {
                $this->inventory_handler->forceMoveItem($citizen->getTown()->getBank(), $item);
            }
        }

        $this->entity_manager->persist($c);
        $this->entity_manager->persist($citizen);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }
}
