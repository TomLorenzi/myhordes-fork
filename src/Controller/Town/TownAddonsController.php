<?php

namespace App\Controller\Town;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\ActionCounter;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenWatch;
use App\Entity\DailyUpgradeVote;
use App\Entity\Item;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\Recipe;
use App\Entity\LogEntryTemplate;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Configuration\MyHordesSetting;
use App\Enum\EventStages\BuildingValueQuery;
use App\Event\Game\Citizen\CitizenQueryNightwatchDeathChancesEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchDefenseEvent;
use App\Event\Game\Citizen\CitizenQueryNightwatchInfoEvent;
use App\Event\Game\Town\Addon\Dump\DumpInsertionCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpRetrieveCheckEvent;
use App\Event\Game\Town\Addon\Dump\DumpRetrieveExecuteEvent;
use App\Kernel;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(only_alive: true, only_with_profession: true, only_in_town: true)]
#[Semaphore('town', scope: 'town')]
class TownAddonsController extends TownController
{
    /**
     * @return Response
     */
    #[Route(path: 'jx/town/upgrades', name: 'town_upgrades')]
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
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/town/upgrades/vote', name: 'town_upgrades_vote_controller')]
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
     * @return Response
     */
    #[Route(path: 'jx/town/watchtower', name: 'town_watchtower')]
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
     * @return Response
     */
    #[Route(path: 'api/town/watchtower/est', name: 'town_watchtower_estimate_controller')]
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
     * @param TownHandler $th
     * @param InventoryHandler $iv
     * @return Response
     */
    #[Route(path: 'jx/town/workshop', name: 'town_workshop')]
    public function addon_workshop(TownHandler $th, InventoryHandler $iv): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();
        $c_inv = $this->getActiveCitizen()->getInventory();
        $t_inv = $town->getBank();

        if (!$th->getBuilding($town, 'small_refine_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $have_saw  = $iv->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy( ['name' => 'saw_tool_#00'] ), false, false ) > 0 ||
                     $iv->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy( ['name' => 'saw_tool_temp_#00'] ), false, false ) > 0;
        $have_manu = $th->getBuilding($town, 'small_factory_#00', true) !== null;

        $recipeData = $this->events->citizenWorkshopOptions( $this->getActiveCitizen() );
        $recipes = $this->entity_manager->getRepository(Recipe::class)->findBy( ['type' => $recipeData->visible_types] );

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
            'disabled_types' => $recipeData->disabled_types,
            'penalty' => $recipeData->ap_penalty_types,
            'sections' => $recipeData->section_types,
            'hints' => $recipeData->section_note_types,
            'saw' => $have_saw, 'manu' => $have_manu,
            'need_ap' => 3 - ($have_saw ? 1 : 0) - ($have_manu ? 1 : 0),
            'source' => $source_db, 'result' => $result_db,

            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @param JSONRequestParser $parser
     * @param ActionHandler $ah
     * @param TownHandler $th
     * @return Response
     */
    #[Route(path: 'api/town/workshop/do', name: 'town_workshop_do_controller')]
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

        $recipeData = $this->events->citizenWorkshopOptions( $this->getActiveCitizen() );

        if ($recipe === null || !in_array( $recipe->getType(), $recipeData->visible_types ) || in_array( $recipe->getType(), $recipeData->disabled_types ) )
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Execute recipe and persist
        if (($error = $ah->execute_recipe( $citizen, $recipe, $remove, $message, $recipeData->ap_penalty_types[$recipe->getType()] ?? 0 )) !== ActionHandler::ErrorNone )
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
     * @param TownHandler              $th
     * @param EventDispatcherInterface $dispatcher
     * @param EventFactory             $e
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'jx/town/dump', name: 'town_dump')]
    public function addon_dump(TownHandler $th, EventDispatcherInterface $dispatcher, EventFactory $e): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

		/** @var DumpInsertionCheckEvent $event */
		$dispatcher->dispatch($event = $e->gameInteractionEvent( DumpInsertionCheckEvent::class )->setup(null));

        if (!$event->dump_built)
            return $this->redirect($this->generateUrl('town_dashboard'));

		$dump = $th->getBuilding($town, 'small_trash_#00');
        return $this->render( 'ajax/game/town/dump.html.twig', $this->addDefaultTwigArgs('dump', [
            'ap_cost' => $event->ap_cost,
            'items' => $event->dumpableItems,
            'dump_def' => $dump->getTempDefenseBonus(),
            'total_def' => $th->calculate_town_def( $town ),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
        ]) );
    }

	/**
     * @param TownHandler              $th
     * @param EventProxyService        $proxy
     * @return Response
     */
    #[Route(path: 'jx/town/nightwatch', name: 'town_nightwatch')]
    public function addon_nightwatch(TownHandler $th, EventProxyService $proxy, KernelInterface $kernel): Response
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


            $total_def += $proxy->citizenQueryNightwatchDefense($watcher->getCitizen());

            $watchers[$watcher->getId()] = $proxy->citizenQueryNightwatchInfo($watcher->getCitizen());

            foreach ($watcher->getCitizen()->getInventory()->getItems() as $item) {
                if($item->getPrototype()->getName() == 'chkspk_#00')
                    $has_counsel = true;
            }
        }

        // total def cannot be negative
        $total_def = max(0, $total_def);

        if($has_counsel)
            $total_def += ($counsel_def = 15 * $count);

		$chances = $proxy->citizenQueryNightwatchDeathChance($this->getActiveCitizen());
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
            'baseDef'     => 10 + $this->getActiveCitizen()->property(CitizenProperties::WatchDefense),
            'deathChance' => $chances['death'],
            'woundChance' => $chances['wound'],
			'terrorChance' => $chances['terror'],
			'hintSentence' => $chances['hint'],
            'me' => $this->getActiveCitizen(),
            'total_def' => $total_def,
            'has_counsel' => $has_counsel,
            'counsel_def' => $counsel_def,
            'door_section' => 'nightwatch',
            'zeds_today' => $zeds_today,
            'def' => $this->town_handler->calculate_town_def($town, $defSummary),
            'cap' => $cap,
            'allow_weapons' => $proxy->queryTownParameter( $town, BuildingValueQuery::NightWatcherWeaponsAllowed ) != 0,
            'debug' => $kernel->getEnvironment() === 'dev' || $kernel->getEnvironment() === 'local' || $this->conf->getGlobalConf()->get(MyHordesSetting::StagingSettingsEnabled)
        ]) );
    }

    /**
     * @param TownHandler $th
     * @param JSONRequestParser $parser
     * @param EventProxyService $proxy
     * @return Response
     */
    #[Route(path: 'api/town/nightwatch/gowatch', name: 'town_nightwatch_go_controller')]
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
     * @param TownHandler $th
     * @return Response
     */
    #[Route(path: 'jx/town/catapult', name: 'town_catapult')]
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
     * @param TownHandler $townHandler
     * @return Response
     */
    #[Route(path: 'api/town/catapult/assign', name: 'town_catapult_assign_controller')]
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
     * @param JSONRequestParser $parser
     * @param CitizenHandler $ch
     * @param EventProxyService $event
     * @param ItemFactory $if
     * @param Packages $asset
     * @param TranslatorInterface $trans
     * @return Response
     */
    #[Route(path: 'api/town/catapult/do', name: 'town_catapult_do_controller')]
    public function catapult_do_api(JSONRequestParser $parser, CitizenHandler $ch, ItemFactory $if, Packages $asset, TranslatorInterface $trans): Response {
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

        $transform = $this->events->queryCatapultItemTransformation( $town, $item->getPrototype() );

        if ($transform !== $item->getPrototype()) {
            $this->inventory_handler->forceMoveItem($target_inv, $debris = $if->createItem($transform));
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


    #[Route(path: 'jx/town/clinic', name: 'town_tamer_clinic')]
    public function townTamerClinic(): Response {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

        if (!$this->town_handler->getBuilding($town, 'small_pet_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $lure = $this->doctrineCache->getEntityByIdentifier(ItemProperty::class, 'lure');

        $items = $this->getActiveCitizen()->getTown()->getBank()->getItems()
            ->filter(fn(Item $item) => $item->getPrototype()->getProperties()->contains( $lure ))
            ->map(fn(Item $item) => [$item->getPrototype(), $item->getCount()])
            ->toArray();

        $items_accum = [];
        foreach ($items as [$p,$c])
            if (!isset($items_accum[$p->getId()]))
                $items_accum[$p->getId()] = [$p,$c];
            else $items_accum[$p->getId()][1] += $c;

        ksort($items_accum);

        return $this->render( 'ajax/game/town/clinic.html.twig', $this->addDefaultTwigArgs('tamers', [
            'day' => $town->getDay(),
            'used' => $this->getActiveCitizen()->hasStatus('tg_tamer_lure'),
            'banished' => $this->getActiveCitizen()->getBanished(),
            'items' => $items_accum
        ]) );
    }

    #[Route(path: 'api/town/clinic/lure', name: 'town_tamer_clinic_lure_controller')]
    public function api_clinic_lure(KernelInterface $kernel, JSONRequestParser $parser, ItemFactory $if, LogTemplateHandler $log): Response
    {
        $town = $this->getActiveCitizen()->getTown();
        if (!$this->town_handler->getBuilding($town, 'small_pet_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($this->getActiveCitizen()->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableBanished );

        if ($this->getActiveCitizen()->hasStatus('tg_tamer_lure'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $prototypes = array_filter(
            array_map(fn($i) => $this->entity_manager->getRepository(ItemPrototype::class)->find( $i ?? -1 ), array_unique(  $parser->get_array('items' ) ) ),
            fn($v) => $v !== null
        );

        if (count($prototypes) !== 3)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $items = $this->inventory_handler->fetchSpecificItems(
            $this->getActiveCitizen()->getTown()->getBank(),
            array_map( fn(ItemPrototype $p) => new ItemRequest($p->getName()), $prototypes )
        );

        if (empty($items))
            return AjaxResponse::error(ErrorHelper::ErrorItemsMissing);

        $configPath = $kernel->getBundle('MyHordesFixturesBundle')->getPath() . '/content/myhordes/config/clinic.yaml';
        if (!file_exists($configPath))
            $configPath = $kernel->getBundle('MyHordesFixturesBundle')->getPath() . '/content/myhordes/config/clinic.default.yaml';

        try {
            $config = file_exists( $configPath ) ? Yaml::parseFile( $configPath ) : [];
        } catch (\Throwable $t) {
            $config = [];
        }

        $score = Arr::get( $config, "score.professions.{$this->getActiveCitizen()->getProfession()->getName()}", 0 );
        $list1 = [];

        $defaultScore = Arr::get( $config, 'score.default', 0 );
        foreach ($items as $item) {
            if ($item->getPoison()->poisoned()) $score += Arr::get( $config, 'score.poison', -1 );
            else $score += array_reduce(
                Arr::get( $config, 'score.groups' ),
                fn(int $carry, array $group) => in_array( $item->getPrototype()->getName(), Arr::get( $group, 'items', [] ) ) ? Arr::get( $group, 'value', $defaultScore ) : $carry,
                $defaultScore
            );

            $this->inventory_handler->forceRemoveItem( $item );
            $list1[] = [$item->getPrototype()];
        }

        [$prototype, $count] = $this->random_generator->pickEntryFromRawRandomArray(
            array_reduce(
                Arr::get( $config, 'result', [] ),
                fn(array $carry, array $item) => $score >= Arr::get( $item, 'min', 0 ) ? Arr::get( $item, 'items', [[[null, 0], 1]] ) : $carry,
                [[[null, 0], 1]]
            )
        );

        if ($count > 0 && $prototype !== null) {
            $spawn = $if->createItem($prototype)->setCount($count);
            $times = $count > 1 ? " × {$count}" : '';
            $this->addFlash( 'notice', $this->translator->trans( 'Nicht schlecht. Mit deinem Köder hast du es geschafft, {item} anzulocken!<hr/>Du hast es zur sicheren Aufbewahrung in die Bank gebracht.', [
                '{item}' => "<span class='tool'><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $spawn->getPrototype()->getIcon() . '.gif' )}'>{$this->translator->trans($spawn->getPrototype()->getLabel(), [], 'items')}{$times}</span>"
            ], 'game' ));

            $this->inventory_handler->forceMoveItem( $this->getActiveCitizen()->getTown()->getBank(), $spawn );
            $this->entity_manager->persist($log->clinicConvert( $this->getActiveCitizen(), $list1, [['count' => $count, 'item' => $spawn->getPrototype()]] ));
        } else {
            $this->addFlash('notice', $this->translator->trans('Es ist dir nicht gelungen, ein Tier anzulocken. Was für eine Verschwendung...', [], 'game'));
            $this->entity_manager->persist($log->clinicConvert( $this->getActiveCitizen(), $list1, [] ));
        }

        if (!($kernel->getEnvironment() === 'dev' || $kernel->getEnvironment() === 'local' || $this->conf->getGlobalConf()->get(MyHordesSetting::StagingSettingsEnabled)))
            $this->citizen_handler->inflictStatus($this->getActiveCitizen(), 'tg_tamer_lure');

        $this->entity_manager->persist($this->getActiveCitizen());
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

}
