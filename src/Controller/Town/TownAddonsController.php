<?php

namespace App\Controller\Town;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenWatch;
use App\Entity\DailyUpgradeVote;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Recipe;
use App\Entity\LogEntryTemplate;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(only_in_town=true, only_alive=true, only_with_profession=true)
 * @Semaphore("town", scope="town")
 */
class TownAddonsController extends TownController
{
    /**
     * @Route("jx/town/upgrades", name="town_upgrades")
     * @return Response
     */
    public function addon_upgrades(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

        $buildings = [];
        $max_votes = 0;
        $total_votes = 0;
        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {
            if ($b->getPrototype()->getMaxLevel() > 0)
                $buildings[] = $b;
            $max_votes = max($max_votes, $b->getDailyUpgradeVotes()->count());
            $total_votes += $b->getDailyUpgradeVotes()->count();
        }

        if (empty($buildings)) return $this->redirect( $this->generateUrl('town_dashboard') );

        return $this->render( 'ajax/game/town/upgrades.html.twig', $this->addDefaultTwigArgs('upgrade', [
            'buildings' => $buildings,
            'max_votes' => $max_votes,
            'total_votes' => $total_votes,
            'vote' => $this->getActiveCitizen()->getDailyUpgradeVote() ? $this->getActiveCitizen()->getDailyUpgradeVote()->getBuilding() : null,
            'is_devastated' => $town->getDevastated()
        ]) );
    }

    /**
     * @Route("api/town/upgrades/vote", name="town_upgrades_vote_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function upgrades_votes_api(JSONRequestParser $parser): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if ($citizen->getDailyUpgradeVote() || $town->getDevastated())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableBanished );

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($id);
        if (!$building || !$building->getComplete() || $building->getTown()->getId() !== $town->getId())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        try {
            $citizen->setDailyUpgradeVote( (new DailyUpgradeVote())->setBuilding( $building ) );
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/watchtower", name="town_watchtower")
     * @return Response
     */
    public function addon_watchtower(): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();
        if (!$this->town_handler->getBuilding($town, 'item_tagger_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $has_zombie_est_tomorrow = !empty($this->town_handler->getBuilding($town, 'item_tagger_#02'));

        $estims = $this->town_handler->get_zombie_estimation($town);

        $z0 = [
            true, // Can see
            true,  // Is available
            $estims[0]->getMin(), // Min
            $estims[0]->getMax(),  // Max
            round($estims[0]->getEstimation()*100), // Progress
            $estims[0]->getMessage()
        ];
        $z1 = [
            $has_zombie_est_tomorrow,
            $estims[0]->getEstimation() >= 1,
            isset($estims[1]) ? $estims[1]->getMin() : 0,
            isset($estims[1]) ? $estims[1]->getMax() : 0,
            isset($estims[1]) ? round($estims[1]->getEstimation()*100) : 0,
            isset($estims[1]) ? $estims[1]->getMessage() : null
        ];

        /** @var ZombieEstimation $est0 */
        $est0 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneBy(['town' => $town, 'day' => $town->getDay()]);

        return $this->render( 'ajax/game/town/watchtower.html.twig', $this->addDefaultTwigArgs('watchtower', [
            'z0' => $z0,
            'z1' => $z1,
            'z0_av' => $est0 && !$est0->getCitizens()->contains( $this->getActiveCitizen() ),
            'z1_av' => $est0 && !$est0->getCitizens()->contains( $this->getActiveCitizen() ),
            'has_scanner' => !empty($this->town_handler->getBuilding($town, 'item_tagger_#01')),
            'has_calc'    => $has_zombie_est_tomorrow,
        ]) );
    }

    /**
     * @Route("api/town/watchtower/est", name="town_watchtower_estimate_controller")
     * @return Response
     */
    public function watchtower_est_api(): Response {
        if ($this->isGranted("IS_IMPERSONATOR", $this->getUser()))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableImpersonator );
        $town = $this->getActiveCitizen()->getTown();

        if (!$this->town_handler->getBuilding($town, 'item_tagger_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneBy(['town' => $town, 'day' => $town->getDay()]);
        if (!$est) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($est->getCitizens()->contains($this->getActiveCitizen()))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $current_est = $this->town_handler->get_zombie_estimation($town, null);

        if (!$this->town_handler->getBuilding($town, 'item_tagger_#02') && $current_est[0]->getEstimation() >= 1) {
            // No next-day estimation and today's estimation is maxed
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        } else if ($this->town_handler->getBuilding($town, 'item_tagger_#02') && $current_est[0]->getEstimation() >= 1 && $current_est[1]->getEstimation() >= 1) {
            // Next-day estimation and all is maxed out
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        $est->addCitizen($this->getActiveCitizen());

        try {
            $this->entity_manager->persist($est);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        $this->addFlash('notice', $this->translator->trans('Vom Turm aus hast du eine guten Überblick über die Wüste. Du musterst die Umgebung, versuchst die Zombies zu zählen und ihre Bewegungsrichtung vorauszuberechnen... <br />Nachdem du einige Minuten die Lage gecheckt hast, schreibst du deine Ergebnisse in das <strong>Wachturmregister</strong>.<hr />Zusammen mit den Informationen der anderen Bürger sollten wir eine korrekte Angriffsschätzung bekommen.', [], 'game'));

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/workshop", name="town_workshop")
     * @param TownHandler $th
     * @param InventoryHandler $iv
     * @return Response
     */
    public function addon_workshop(TownHandler $th, InventoryHandler $iv): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();
        $c_inv = $this->getActiveCitizen()->getInventory();
        $t_inv = $town->getBank();

        if (!$th->getBuilding($town, 'small_refine_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $have_saw  = $iv->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy( ['name' => 'saw_tool_#00'] ), false, false ) > 0;
        $have_manu = $th->getBuilding($town, 'small_factory_#00', true) !== null;

        $recipes = $this->entity_manager->getRepository(Recipe::class)->findBy( ['type' => Recipe::WorkshopType] );
        if($this->getActiveCitizen()->getProfession()->getName() == "shaman") {
            $recipes = array_merge($recipes, $this->entity_manager->getRepository(Recipe::class)->findBy(['type' => Recipe::WorkshopTypeShamanSpecific]));
        }
        $source_db = []; $result_db = [];
        foreach ($recipes as $recipe) {
            /** @var Recipe $recipe */
            $min_s = $min_r = PHP_INT_MAX;
            foreach ($recipe->getProvoking() as $proto)
                $min_s = min($min_s, $iv->countSpecificItems( $t_inv, $proto, false, false ));
            $source_db[ $recipe->getId() ] = $min_s === PHP_INT_MAX ? 0 : $min_s;

            foreach ($recipe->getResult()->getEntries() as $entry)
                $min_r = min($min_r, $iv->countSpecificItems( $t_inv, $entry->getPrototype(), false, false ));
            $result_db[ $recipe->getId() ] = $min_r === PHP_INT_MAX ? 0 : $min_r;
        }

        return $this->render( 'ajax/game/town/workshop.html.twig', $this->addDefaultTwigArgs('workshop', [
            'recipes' => $recipes,
            'saw' => $have_saw, 'manu' => $have_manu,
            'need_ap' => 3 - ($have_saw ? 1 : 0) - ($have_manu ? 1 : 0),
            'source' => $source_db, 'result' => $result_db,

            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/town/workshop/do", name="town_workshop_do_controller")
     * @param JSONRequestParser $parser
     * @param ActionHandler $ah
     * @param TownHandler $th
     * @return Response
     */
    public function workshop_do_api(JSONRequestParser $parser, ActionHandler $ah, TownHandler $th): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if citizen is banished or workshop is not build
        if (!$th->getBuilding($town, 'small_refine_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if ($citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableBanished );

        // Get recipe ID
        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Recipe $recipe */
        // Get recipe object and make sure it is a workshop recipe
        $recipe = $this->entity_manager->getRepository(Recipe::class)->find( $id );

        if ($recipe === null || ($recipe->getType() !== Recipe::WorkshopType && $recipe->getType() !== Recipe::WorkshopTypeShamanSpecific))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Execute recipe and persist
        if (($error = $ah->execute_recipe( $citizen, $recipe, $remove, $message )) !== ActionHandler::ErrorNone )
            return AjaxResponse::error( $error );
        else try {
            // Set the activity status
            $this->citizen_handler->inflictStatus($citizen, 'tg_chk_workshop');

            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            foreach ($remove as $e) $this->entity_manager->remove( $e );
            $this->entity_manager->flush();
            if ($message) $this->addFlash( 'notice', $message );
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }

	/**
	 * @Route("jx/town/dump", name="town_dump")
	 * @param TownHandler              $th
	 * @param EventDispatcherInterface $dispatcher
	 * @param EventFactory             $e
	 * @return Response
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function addon_dump(TownHandler $th, EventDispatcherInterface $dispatcher, EventFactory $e): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

		/** @var DumpInsertionCheckEvent $event */
		$dispatcher->dispatch($event = $e->gameInteractionEvent( DumpInsertionCheckEvent::class )->setup(null));

        if (!$event->dump_built)
            return $this->redirect($this->generateUrl('town_dashboard'));

		$dump = $th->getBuilding($town, 'small_trash_#00', true);

        return $this->render( 'ajax/game/town/dump.html.twig', $this->addDefaultTwigArgs('dump', [
            'ap_cost' => $event->ap_cost,
            'items' => $event->dumpableItems,
            'dump_def' => $dump->getTempDefenseBonus(),
            'total_def' => $th->calculate_town_def( $town ),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
        ]) );
    }

	/**
	 * @Route("jx/town/nightwatch", name="town_nightwatch")
	 * @param TownHandler              $th
	 * @param EventDispatcherInterface $dispatcher
	 * @param EventFactory             $eventFactory
	 * @param EventProxyService        $proxy
	 * @return Response
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function addon_nightwatch(TownHandler $th, EventDispatcherInterface $dispatcher, EventFactory $eventFactory, EventProxyService $proxy): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        if (!$th->getBuilding($town, 'small_round_path_#00', true) && !$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $citizenWatch = $this->entity_manager->getRepository(CitizenWatch::class)->findCurrentWatchers($town);
        $watchers = [];
        $is_watcher = false;
        $has_counsel = false;
        $counsel_def = 0;
        $total_def = 0;

        $count = 0;

        /** @var CitizenWatch $watcher */
        foreach ($citizenWatch as $watcher) {
            if ($watcher->getCitizen()->getZone() !== null) continue;
            $count++;

            if($watcher->getCitizen()->getId() === $this->getActiveCitizen()->getId())
                $is_watcher = true;
			$dispatcher->dispatch($event = $eventFactory->gameInteractionEvent( CitizenQueryNightwatchDefenseEvent::class )->setup( $watcher->getCitizen() ));
            $total_def += $event->nightwatchDefense;

			$dispatcher->dispatch($event = $eventFactory->gameInteractionEvent( CitizenQueryNightwatchInfoEvent::class )->setup( $watcher->getCitizen() ));
            $watchers[$watcher->getId()] = $event->nightwatchInfo;

            foreach ($watcher->getCitizen()->getInventory()->getItems() as $item) {
                if($item->getPrototype()->getName() == 'chkspk_#00')
                    $has_counsel = true;
            }
        }

        // total def cannot be negative
        $total_def = max(0, $total_def);

        if($has_counsel)
            $total_def += ($counsel_def = 15 * $count);

		/** @var CitizenQueryNightwatchDeathChancesEvent $event */
		$dispatcher->dispatch($event = $eventFactory->gameInteractionEvent( CitizenQueryNightwatchDeathChancesEvent::class )->setup( $this->getActiveCitizen() ));
        $has_zombie_est_today    = !empty($this->town_handler->getBuilding($town, 'item_tagger_#00'));

        $estims = $this->town_handler->get_zombie_estimation($town);
        $zeds_today = [
            $has_zombie_est_today, // Can see
            $estims[0]->getMin(), // Min
            $estims[0]->getMax(),  // Max
            round($estims[0]->getEstimation()*100) // Progress
        ];

        $cap = $proxy->queryTownParameter( $town, BuildingValueQuery::NightWatcherCap );
        if ($cap >= $town->getPopulation()) $cap = null;

        return $this->render( 'ajax/game/town/nightwatch.html.twig', $this->addDefaultTwigArgs('battlement', [
            'watchers' => $watchers,
            'is_watcher' => $is_watcher,
            'deathChance' => $event->deathChance,
            'woundChance' => $event->woundChance,
			'terrorChance' => $event->terrorChance,
			'hintSentence' => $event->hintSentence,
            'me' => $this->getActiveCitizen(),
            'total_def' => $total_def,
            'has_counsel' => $has_counsel,
            'counsel_def' => $counsel_def,
            'door_section' => 'nightwatch',
            'zeds_today' => $zeds_today,
            'def' => $this->town_handler->calculate_town_def($town, $defSummary),
            'cap' => $cap,
            'allow_weapons' => $proxy->queryTownParameter( $town, BuildingValueQuery::NightWatcherWeaponsAllowed ) != 0
        ]) );
    }

    /**
     * @Route("api/town/nightwatch/gowatch", name="town_nightwatch_go_controller")
     * @param TownHandler $th
     * @param JSONRequestParser $parser
     * @param EventProxyService $proxy
     * @return Response
     */
    public function api_nightwatch_gowatch(TownHandler $th, JSONRequestParser $parser, EventProxyService $proxy): Response
    {
        $town = $this->getActiveCitizen()->getTown();
        if (!$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH, true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$th->getBuilding($town, 'small_round_path_#00', true) && !$this->getTownConf()->get(TownConf::CONF_FEATURE_NIGHTWATCH_INSTANT, false))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $action = $parser->get("action");

        $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findCurrentWatchers($town);
        $activeCitizenWatcher = null;

        foreach ($watchers as $watcher)
            if($watcher->getCitizen() === $this->getActiveCitizen()){
                $activeCitizenWatcher = $watcher;
                break;
            }

        if($action == 'unwatch') {
            if ($activeCitizenWatcher === null)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            $town->removeCitizenWatch($activeCitizenWatcher);
            $this->getActiveCitizen()->removeCitizenWatch($activeCitizenWatcher);
            $this->entity_manager->remove($activeCitizenWatcher);
            $this->addFlash('notice', $this->translator->trans('Du verlässt deinen Posten?...',[], 'game'));

        } else if ($action == "watch") {

            if ($activeCitizenWatcher !== null)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if (count($watchers) >= $proxy->queryTownParameter( $town, BuildingValueQuery::NightWatcherCap ))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            $citizenWatch = new CitizenWatch();
            $citizenWatch->setTown($town)->setCitizen($this->getActiveCitizen())->setDay($town->getDay());
            $town->addCitizenWatch($citizenWatch);

            $this->getActiveCitizen()->addCitizenWatch($citizenWatch);

            $this->entity_manager->persist($citizenWatch);

            $this->addFlash('notice', $this->translator->trans('Bravo, du hast die verantwortungsvolle Aufgabe übernommen, deine Mitbürger vor den Untoten zu beschützen...',[], 'game'));
        }

        $this->entity_manager->persist($this->getActiveCitizen());
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("jx/town/catapult", name="town_catapult")
     * @param TownHandler $th
     * @return Response
     */
    public function addon_catapult(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

        if (!($catapult = $th->getBuilding($town, 'item_courroie_#00', true)))
            return $this->redirect($this->generateUrl('town_dashboard'));

        /** @var Citizen $cata_master */
        $cata_master = $this->entity_manager->getRepository(Citizen::class)->findLastOneByRoleAndTown($this->entity_manager->getRepository(CitizenRole::class)->findOneByName('cata'), $town);
        if ($cata_master && !$cata_master->getAlive()) $cata_master = null;

        return $this->render( 'ajax/game/town/catapult.html.twig', $this->addDefaultTwigArgs('catapult', [
            'catapult_improved' => $th->getBuilding( $town, 'item_courroie_#01', true ) !== null,
            'catapult_master' => $cata_master,
            'is_catapult_master' => $this->getActiveCitizen()->hasRole('cata'),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
        ]) );
    }

    /**
     * @Route("api/town/catapult/assign", name="town_catapult_assign_controller")
     * @param TownHandler $townHandler
     * @return Response
     */
    public function catapult_new_api(TownHandler $townHandler): Response
    {
        $selection = $townHandler->assignCatapultMaster( $this->getActiveCitizen()->getTown(), false );
        if ($selection) {
            $this->entity_manager->persist($selection);
            $this->entity_manager->flush();
            return AjaxResponse::success();
        } else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
    }

        /**
     * @Route("api/town/catapult/do", name="town_catapult_do_controller")
     * @param JSONRequestParser $parser
     * @param CitizenHandler $ch
     * @param TownHandler $th
     * @return Response
     */
    public function catapult_do_api(JSONRequestParser $parser, CitizenHandler $ch, TownHandler $th, ItemFactory $if, Packages $asset, TranslatorInterface $trans): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if catapult is build
        if (!$this->town_handler->getBuilding($town, 'item_courroie_#00', true) || !$citizen->hasRole('cata'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Get prototype ID
        if (!$parser->has_all(['item','x','y'], false))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $item_id = (int)$parser->get('item');
        $x = (int)$parser->get('x');
        $y = (int)$parser->get('y');

        $item = $this->entity_manager->getRepository(Item::class)->find($item_id);

        if ($item === null || $item->getEssential() || $item->getBroken() || !$citizen->getInventory()->getItems()->contains($item))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (in_array($item->getPrototype()->getName(), ['bagxl_#00', 'bag_#00', 'cart_#00', 'pocket_belt_#00']))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $target_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x,$y);
        if (!$target_zone) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Check if the improved catapult is built
        $ap = ($this->town_handler->getBuilding( $town, 'item_courroie_#01', true ) !== null ? 2 : 4);

        // Make sure the citizen has enough AP
        if ($citizen->getAp() < $ap || $ch->isTired($citizen)) return AjaxResponse::error(ErrorHelper::ErrorNoAP);

        // Different target zone
        if ($this->random_generator->chance(0.10)) {

            $alt_zones = [];

            $alt_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x-1,$y);
            if ($alt_zone) $alt_zones[] = $alt_zone;

            $alt_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x+1,$y);
            if ($alt_zone) $alt_zones[] = $alt_zone;

            $alt_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x,$y-1);
            if ($alt_zone) $alt_zones[] = $alt_zone;

            $alt_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x,$y+1);
            if ($alt_zone) $alt_zones[] = $alt_zone;

            if (!empty($alt_zones)) $target_zone = $this->random_generator->pick($alt_zones);
        }

        // Deduct AP
        $this->citizen_handler->setAP($citizen, true, -$ap);

        $this->entity_manager->persist($this->log->catapultUsage($citizen, $item, $target_zone));

        $target_inv = ($target_zone->getX() === 0 && $target_zone->getY() === 0) ? $town->getBank() : $target_zone->getFloor();

        if ($item->getPrototype()->getFragile()) {
            $debris_item = $item->getPrototype()->hasProperty('pet') ? 'undef_#00' : 'broken_#00';

            $this->inventory_handler->forceMoveItem($target_inv, $debris = $if->createItem($debris_item));
            $this->inventory_handler->forceRemoveItem($item);
            $this->entity_manager->persist($this->log->catapultImpact($debris, $target_zone));
        } else {
            $this->entity_manager->persist($this->log->catapultImpact($item, $target_zone));
            $this->inventory_handler->forceMoveItem( $target_inv, $item );
        }

        $this->addFlash('notice', $trans->trans('Sorgfältig verpackt hast du {item} in das Katapult gelegt. Der Gegenstand wurde auf {zone} geschleudert.', [
            '{item}' => "<span><img alt='' src='" . $asset->getUrl("build/images/item/item_{$item->getPrototype()->getIcon()}.gif") . "' />" . $trans->trans($item->getPrototype()->getLabel(), [], 'items') . "</span>",
            '{zone}' => "<strong>[{$target_zone->getX()}/{$target_zone->getY()}]</strong>"
        ], 'game'));

        // Persist
        $this->entity_manager->persist( $citizen );
        $this->entity_manager->persist( $target_zone );
        $this->entity_manager->persist( $town );

        // Flush
        try {

            $this->entity_manager->flush();
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }


}
