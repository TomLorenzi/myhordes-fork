<?php

namespace App\Controller\Town;

use App\Controller\InventoryAwareController;
use App\Controller\TownInterfaceController;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\Complaint;
use App\Entity\ExpeditionRoute;
use App\Entity\ItemPrototype;
use App\Entity\TownLogEntry;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Translation\T;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\Translator;
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



    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];

        $addons = [];
        $town = $this->getActiveCitizen()->getTown();

        $data["builtbuildings"] = array();

        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {

            if ($b->getPrototype()->getMaxLevel() > 0)
                $addons['upgrade']  = [T::__('Verbesserung des Tages', 'game'), 'town_upgrades'];

            if ($b->getPrototype()->getName() === 'item_tagger_#00')
                $addons['watchtower'] = [T::__('Wachturm', 'game'), 'town_watchtower'];

            if ($b->getPrototype()->getName() === 'small_refine_#00')
                $addons['workshop'] = [T::__('Werkstatt', 'game'), 'town_workshop'];

            if ($b->getPrototype()->getName() === 'small_round_path_#00')
                $addons['battlement'] = [T::__('Wächt', 'game'), 'town_dashboard'];

            if ($b->getPrototype()->getName() === 'small_trash_#00')
                $addons['dump'] = [T::__('Müllhalde', 'game'), 'town_dump'];

            if ($b->getPrototype()->getName() === 'item_courroie_#00')
                $addons['catapult'] = [T::__('Katapult', 'game'), 'town_dashboard'];
            

            $data["builtbuildings"][] = $b;

        }

        $data['addons'] = $addons;
        $data['home'] = $this->getActiveCitizen()->getHome();
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

        $item_def_count = $this->inventory_handler->countSpecificItems($town->getBank(),$this->inventory_handler->resolveItemProperties( 'defence' ));

        $est0 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay());
        $has_estimated = $est0 && $est0->getCitizens()->contains($this->getActiveCitizen());

        $display_home_upgrade = false;
        foreach ($citizens as $citizen) {
            if($citizen->getHome()->getPrototype()->getLevel() > $this->getActiveCitizen()->getHome()->getPrototype()->getLevel()){
                $display_home_upgrade = true;
                break;
            }
        }

        return $this->render( 'ajax/game/town/dashboard.html.twig', $this->addDefaultTwigArgs(null, [
            'town' => $town,
            'def' => $th->calculate_town_def($town, $defSummary),
            'zeds_today'    => [ $has_zombie_est_today, $z_today_min, $z_today_max ],
            'zeds_tomorrow' => [ $has_zombie_est_tomorrow, $z_tomorrow_min, $z_tomorrow_max ],
            'living_citizens' => $alive,
            'def_summary' => $defSummary,
            'item_def_count' => $item_def_count,
            'item_def_factor' => $item_def_factor,
            'has_battlement' => $has_battlement,
            'has_watchtower' => $has_watchtower,
            'has_levelable_building' => $has_levelable_building,
            'active_citizen' => $this->getActiveCitizen(),
            'has_estimated' => $has_estimated,
            'has_visited_forum' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_chk_forum'),
            'has_been_active' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_chk_active'),
            'display_home_upgrade' => $display_home_upgrade,
            'has_upgraded_house' => $this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_home_upgrade'),
        ]) );
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

        $th->calculate_home_def($home, $summary);
        $deco = 0;
        foreach ($home->getChest()->getItems() as $item)
            $deco += $item->getPrototype()->getDeco();

        $town = $this->getActiveCitizen()->getTown();
        return $this->render( 'ajax/game/town/home_foreign.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizen' => $c,
            'home' => $home,
            'actions' => $this->getItemActions(),
            'complaint' => $this->entity_manager->getRepository(Complaint::class)->findByCitizens( $this->getActiveCitizen(), $c ),
            'complaints' => $this->entity_manager->getRepository(Complaint::class)->countComplaintsFor( $c ),
            'chest' => $home->getChest(),
            'chest_size' => $this->inventory_handler->getSize($home->getChest()),
            'has_cremato' => $th->getBuilding($town, 'item_hmeat_#00', true) !== null,

            'def' => $summary,
            'deco' => $deco,

            'log' => $this->renderLog( -1, $c, false, null, 10 )->getContent(),
            'day' => $c->getTown()->getDay()
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

        switch ($action) {

            case 1:
                if ($ac->getAp() <= 0 || $this->citizen_handler->isTired( $ac ))
                    return AjaxResponse::error( ErrorHelper::ErrorNoAP );
                $this->citizen_handler->setAP($ac, true, -1);
                break;
            case 2:
                $items = $this->inventory_handler->fetchSpecificItems( $ac->getInventory(), [new ItemRequest('water_#00')] );
                if (!$items) return AjaxResponse::error(ErrorHelper::ErrorItemsMissing );
                $this->inventory_handler->forceRemoveItem( $items[0] );
                break;
            case 3:
                $town = $ac->getTown();
                if (!$th->getBuilding($town, 'item_hmeat_#00', true))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                $spawn_items[] = [ $em->getRepository( ItemPrototype::class )->findOneByName( 'hmeat_#00' ), 2 ];
                break;
        }

        foreach ($spawn_items as $item_spec)
            for ($i = 0; $i < $item_spec[1]; $i++)
                $this->inventory_handler->forceMoveItem( $ac->getTown()->getBank(), $if->createItem( $item_spec[0] )  );
        $em->persist( $this->log->citizenDisposal( $ac, $c, $action, $spawn_items ) );
        $c->getHome()->setHoldsBody( false );

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
        if ($severity < Complaint::SeverityNone || $severity > Complaint::SeverityKill)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest );

        $autor = $this->getActiveCitizen();
        $town = $autor->getTown();

        /** @var Citizen $c */
        $culprit = $em->getRepository(Citizen::class)->find( $id );
        if (!$culprit || $culprit->getTown()->getId() !== $town->getId() || !$culprit->getAlive() )
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable );

        $existing_complaint = $em->getRepository( Complaint::class )->findByCitizens($autor, $culprit);
        $severity_before = $existing_complaint ? $existing_complaint->getSeverity() : 0;

        if (!$existing_complaint) {
            $existing_complaint = (new Complaint())
                ->setAutor( $autor )
                ->setCulprit( $culprit )
                ->setSeverity( $severity )
                ->setCount( ($autor->getProfession()->getHeroic() && $th->getBuilding( $town, 'small_court_#00', true )) ? 2 : 1 );
            $culprit->addComplaint( $existing_complaint );
        } else $existing_complaint->setSeverity( $severity );

        try {
            if ($severity !== $severity_before && ($severity === 0 || $severity_before === 0)) $em->persist( $this->log->citizenComplaint( $existing_complaint ) );
            $em->persist($culprit);
            $em->persist($existing_complaint);
            $em->flush();
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

        $up_inv   = $ac->getInventory();
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

        return $this->render( 'ajax/game/town/well.html.twig', $this->addDefaultTwigArgs('well', [
            'rations_left' => $this->getActiveCitizen()->getTown()->getWell(),
            'first_take' => $this->getActiveCitizen()->getWellCounter()->getTaken() === 0,
            'allow_take' => $this->getActiveCitizen()->getWellCounter()->getTaken() < (($pump && !$this->getActiveCitizen()->getBanished()) ? 2 : 1),
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
     * @return Response
     */
    public function well_api(JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $factory, TownHandler $th): Response {
        $direction = $parser->get('direction', '');

        if (in_array($direction, ['up','down'])) {
            $citizen = $this->getActiveCitizen();

            $town = $citizen->getTown();
            $wellLock = $citizen->getWellCounter();

            $limit = ($th->getBuilding( $town, 'small_water_#00', true ) && !$this->getActiveCitizen()->getBanished()) ? 2 : 1;

            if ($direction == 'up') {

                if ($town->getWell() <= 0) return AjaxResponse::error(self::ErrorWellEmpty);
                if ($wellLock->getTaken() >= $limit) return AjaxResponse::error(self::ErrorWellLimitHit);

                $inv_target = $citizen->getInventory();
                $inv_source = null;
                $item = $factory->createItem( 'water_#00' );

                if (($error = $handler->transferItem(
                    $citizen,
                    $item,$inv_source, $inv_target
                )) === InventoryHandler::ErrorNone) {
                    $this->entity_manager->persist( $this->log->wellLog( $citizen, $wellLock->getTaken() >= 1 ) );
                    $wellLock->setTaken( $wellLock->getTaken()+1 );
                    $town->setWell( $town->getWell()-1 );
                    try {
                        $this->entity_manager->persist($item);
                        $this->entity_manager->persist($town);
                        $this->entity_manager->persist($wellLock);
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
                        $this->entity_manager->persist( $this->log->wellAdd( $citizen, $items[0], 1) );
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
     * @return Response
     */
    public function bank(): Response
    {
        return $this->render( 'ajax/game/town/bank.html.twig', $this->addDefaultTwigArgs('bank', [
            'bank' => $this->renderInventoryAsBank( $this->getActiveCitizen()->getTown()->getBank() ),
            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeBank, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
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
     * @return Response
     */
    public function citizens(EntityManagerInterface $em): Response
    {
        $hidden = [];

        $prof_count = [];
        $death_count = 0;

        foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $c) {
            $hidden[$c->getId()] = (bool)($em->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($c->getHome(),
                $em->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName('curtain')
            ));

            if (!$c->getAlive()) $death_count++;
            else {

                if (!isset($prof_count[ $c->getProfession()->getId() ])) {
                    $prof_count[ $c->getProfession()->getId() ] = [
                        1,
                        $c->getProfession()
                    ];
                } else $prof_count[ $c->getProfession()->getId() ][0]++;

            }
        }

        return $this->render( 'ajax/game/town/citizen.html.twig', $this->addDefaultTwigArgs('citizens', [
            'citizens' => $this->getActiveCitizen()->getTown()->getCitizens(),
            'me' => $this->getActiveCitizen(),
            'hidden' => $hidden,
            'prof_count' => $prof_count,
            'death_count' => $death_count,
        ]) );
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

        // Check out how much AP is missing to complete the building; restrict invested AP to not exceed this
        $missing_ap = ceil( ($building->getPrototype()->getAp() - $building->getAp()) * ( $slave_bonus ? (2.0/3.0) : 1 )) ;
        $ap = max(0,min( $ap, $missing_ap ) );

        // If the citizen has not enough AP, fail
        if (($citizen->getAp() + $citizen->getBp()) < $ap || $this->citizen_handler->isTired( $citizen ))
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

        // Remember if the building has already been completed (i.e. this is a repair action)
        $was_completed = $building->getComplete();

        // Create a log entry
        if ($th->getBuilding($town, 'item_rp_book2_#00', true))
            $this->entity_manager->persist( $this->log->constructionsInvestAP( $citizen, $building->getPrototype(), $ap ) );

        // Calculate the amount of AP that will be invested in the construction
        $ap_effect = floor( $ap * ( $slave_bonus ? 1.5 : 1 ) );

        // Deduct AP and increase completion of the building
        $this->citizen_handler->deductAPBP( $citizen, $ap );
        $building->setAp( $building->getAp() + $ap_effect );

        // If the building was not previously completed but reached 100%, complete the building and trigger the completion handler
        $building->setComplete( $building->getComplete() || $building->getAp() >= $building->getPrototype()->getAp() );
        if (!$was_completed && $building->getComplete()) {
            // Remove resources, create a log entry, trigger
            foreach ($items as $item)
                $this->inventory_handler->forceRemoveItem( $item, $res[ $item->getPrototype()->getName() ]->getCount() );

            $this->entity_manager->persist( $this->log->constructionsBuildingComplete( $citizen, $building->getPrototype() ) );
            $th->triggerBuildingCompletion( $town, $building );
        }

        // Set the activity status
        $this->citizen_handler->inflictStatus($citizen, 'tg_chk_active');

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

        $root = [];
        $dict = [];
        $items = [];

        foreach ($buildings as $building) {
            $dict[ $building->getPrototype()->getId() ] = [];
            if (!$building->getPrototype()->getParent()) $root[] = $building;
            if (!$building->getComplete() && !empty($building->getPrototype()->getResources()))
                foreach ($building->getPrototype()->getResources()->getEntries() as $ressource)
                    if (!isset($items[$ressource->getPrototype()->getId()]))
                        $items[$ressource->getPrototype()->getId()] = $this->inventory_handler->countSpecificItems( $this->getActiveCitizen()->getTown()->getBank(), $ressource->getPrototype() );
        }

        foreach ($buildings as $building)
            if ($building->getPrototype()->getParent())
                $dict[$building->getPrototype()->getParent()->getId()][] = $building;

        return $this->render( 'ajax/game/town/construction.html.twig', $this->addDefaultTwigArgs('constructions', [
            'root_cats'  => $root,
            'dictionary' => $dict,
            'bank' => $items,
            'slavery' => $th->getBuilding($town, 'small_slave_#00', true) !== null,

            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeConstruction, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
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
     * @Route("api/town/door/exit", name="town_door_exit_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function door_exit_api(JSONRequestParser $parser): Response {
        if (!$this->getActiveCitizen()->getTown()->getDoor())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), 0, 0);

        if (!$zone)
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        // Set the activity status
        $this->citizen_handler->inflictStatus($citizen, 'tg_chk_active');

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
        if ( !$town->getDoor() && (($s = $this->time_keeper->secondsUntilNextAttack(null, true)) <= 1800) ) {
            if ($th->getBuilding( $town, 'small_door_closed_#02', true )) {
                if ($s <= 60) return true;
            } elseif ($th->getBuilding( $town, 'small_door_closed_#01', true )) {
                if ($s <= 1800) return true;
            } elseif ($th->getBuilding( $town, 'small_door_closed_#00', true )) {
                if ($s <= 1200) return true;
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
        return $this->render( 'ajax/game/town/door.html.twig', $this->addDefaultTwigArgs('door', array_merge([
            'town'  =>  $this->getActiveCitizen()->getTown(),
            'door_locked' => $door_locked,
            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeDoor, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
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
     * @param TownHandler $th
     * @return Response
     */
    public function dashboard_save_wordofheroes_api(JSONRequestParser $parser, TownHandler $th): Response {
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
}
