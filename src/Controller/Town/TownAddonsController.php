<?php

namespace App\Controller\Town;

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
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
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
        if($town->getDevastated())
            return $this->redirect($this->generateUrl('town_dashboard'));

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

        if ($citizen->getDailyUpgradeVote() || $citizen->getBanished() || $town->getDevastated())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

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
            round($estims[0]->getEstimation()*100) // Progress
        ];
        $z1 = [
            $has_zombie_est_tomorrow,
            $estims[0]->getEstimation() >= 1,
            isset($estims[1]) ? $estims[1]->getMin() : 0,
            isset($estims[1]) ? $estims[1]->getMax() : 0,
            isset($estims[1]) ? round($estims[1]->getEstimation()*100) : 0
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
        $town = $this->getActiveCitizen()->getTown();

        if (!$this->town_handler->getBuilding($town, 'item_tagger_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneBy(['town' => $town, 'day' => $town->getDay()]);
        if (!$est) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($est->getCitizens()->contains($this->getActiveCitizen()))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

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

            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeWorkshop, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/town/workshop/log", name="town_workshop_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_workshop_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeWorkshop, null);
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
        if ($citizen->getBanished() || !$th->getBuilding($town, 'small_refine_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

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
            $this->citizen_handler->inflictStatus($citizen, 'tg_chk_active');
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

    protected function get_dump_def_for( ItemPrototype $proto, TownHandler $th ): int {

        $town = $this->getActiveCitizen()->getTown();
        $improved = $th->getBuilding($town, 'small_trash_#06', true) !== null;

        // Weapons
        if ($proto->hasProperty('weapon') || in_array( $proto->getName(), [
            'machine_gun_#00', 'gun_#00', 'chair_basic_#00', 'machine_1_#00', 'machine_2_#00', 'machine_3_#00', 'pc_#00'
            ] ) )
            return ($improved ? 2 : 1) + ( $th->getBuilding($town, 'small_trash_#03', true) ? 5 : 0 );

        // Defense
        if ($proto->hasProperty('defence') && $proto->getName() !== 'tekel_#00' && $proto->getName() !== 'pet_dog_#00' && $proto->getName() !== 'concrete_wall_#00')
            return ($improved ? 5 : 4) + ( $th->getBuilding($town, 'small_trash_#05', true) ? 2 : 0 );

        // Food
        if ($proto->hasProperty('food'))
            return ($improved ? 2 : 1) + ( $th->getBuilding($town, 'small_trash_#04', true) ? 3 : 0 );

        // Wood
        if ($proto->getName() === 'wood_bad_#00' || $proto->getName() === 'wood2_#00')
            return ($improved ? 2 : 1) + ( $th->getBuilding($town, 'small_trash_#01', true) ? 1 : 0 );

        // Metal
        if ($proto->getName() === 'metal_bad_#00' || $proto->getName() === 'metal_#00')
            return ($improved ? 2 : 1) + ( $th->getBuilding($town, 'small_trash_#02', true) ? 1 : 0 );

        // Animals
        if ($proto->hasProperty('pet'))
            return ($improved ? 2 : 1) + ( $th->getBuilding($town, 'small_howlingbait_#00', true) ? 6 : 0 );

        return 0;
    }

    /**
     * @Route("jx/town/dump", name="town_dump")
     * @param TownHandler $th
     * @return Response
     */
    public function addon_dump(TownHandler $th): Response
    {
        if (!$this->getActiveCitizen()->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));

        $town = $this->getActiveCitizen()->getTown();

        if (!($dump = $th->getBuilding($town, 'small_trash_#00', true)))
            return $this->redirect($this->generateUrl('town_dashboard'));


        $cache = [];
        foreach ($town->getBank()->getItems() as $item) {
            if (!isset($cache[$item->getPrototype()->getId()]))
                $cache[$item->getPrototype()->getId()] = [
                    $item->getPrototype(),
                    $item->getCount(),
                    $this->get_dump_def_for( $item->getPrototype(), $th )
                ];
            else $cache[$item->getPrototype()->getId()][1] += $item->getCount();
        }

        $cache = array_filter( $cache, function(array $a) { return $a[1] > 0 && $a[2] > 0; } );
        usort( $cache, function(array $a, array $b) {
            return ($a[2] === $b[2]) ? ( $a[0]->getId() < $b[0]->getId() ? -1 : 1 ) : ($a[2] < $b[2] ? 1 : -1);
        } );

        return $this->render( 'ajax/game/town/dump.html.twig', $this->addDefaultTwigArgs('dump', [
            'free_dumps' => $th->getBuilding( $town, 'small_trashclean_#00', true ) !== null,
            'items' => $cache,
            'dump_def' => $dump->getTempDefenseBonus(),
            'total_def' => $th->calculate_town_def( $town ),
            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeDump, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
        ]) );
    }

    /**
     * @Route("api/town/dump/do", name="town_dump_do_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function dump_do_api(JSONRequestParser $parser): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if citizen is banished or dump is not build
        if ($citizen->getBanished() || !($dump = $this->town_handler->getBuilding($town, 'small_trash_#00', true)))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Get prototype ID
        if (!$parser->has_all(['id','ap'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');
        $ap = (int)$parser->get('ap');

        /** @var ItemPrototype $prototype */
        // Get the item prototype and make sure it is dump-able
        $prototype = $this->entity_manager->getRepository(ItemPrototype::class)->find( $id );
        if ($prototype === null || !($dump_def = $this->get_dump_def_for( $prototype, $this->town_handler ))  )
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Check if dumping is free
        $free_dumps = $this->town_handler->getBuilding( $town, 'small_trashclean_#00', true ) !== null;

        // Check if citizen has enough AP
        if (!$free_dumps && $citizen->getAp() < $ap)
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Check if items are available
        $items = $this->inventory_handler->fetchSpecificItems( $town->getBank(), [new ItemRequest($prototype->getName(), $ap)] );
        if (!$items) return AjaxResponse::error( ErrorHelper::ErrorItemsMissing );

        $itemsForLog = [];
        foreach ($items as $item){
            if(isset($itemsForLog[$item->getPrototype()->getId()])) {
                $itemsForLog[$item->getPrototype()->getId()]['count'] += $ap;
            } else {
                $itemsForLog[$item->getPrototype()->getId()] = [
                    'item' => $item->getPrototype(),
                    'count' => $ap
                ];
            }
        }

        // Remove items
        $n = $ap;
        while (!empty($items) && $n > 0) {
            $item = array_pop($items);
            $c = $item->getCount();
            $this->inventory_handler->forceRemoveItem( $item, $n );
            $n -= $c;
        }

        // Reduce AP
        if (!$free_dumps) $citizen->setAp( $citizen->getAp() - $ap );

        // Increase def
        $dump->setTempDefenseBonus( $dump->getTempDefenseBonus() + $ap * $dump_def );

        $this->entity_manager->persist($this->log->dumpItems($citizen, $itemsForLog, $ap*$dump_def));

        // Persist
        try {
            $this->entity_manager->persist( $dump );
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }

    /**
     * @Route("api/dump/log", name="town_dump_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_dump_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeDump, null);
    }

    /**
     * @Route("jx/town/nightwatch", name="town_nightwatch")
     * @param TownHandler $th
     * @return Response
     */
    public function addon_nightwatch(TownHandler $th): Response
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
        $total_def = 0;

        $has_shooting_gallery = (bool)$th->getBuilding($town, 'small_tourello_#00', true);
        $has_trebuchet        = (bool)$th->getBuilding($town, 'small_catapult3_#00', true);
        $has_ikea             = (bool)$th->getBuilding($town, 'small_ikea_#00', true);
        $has_armory           = (bool)$th->getBuilding($town, 'small_armor_#00', true);

        /** @var CitizenWatch $watcher */
        foreach ($citizenWatch as $watcher) {
            if($watcher->getCitizen()->getId() === $this->getActiveCitizen()->getId())
                $is_watcher = true;

            $citizen_def = $this->citizen_handler->getNightWatchDefense($watcher->getCitizen(), $has_shooting_gallery, $has_trebuchet, $has_ikea, $has_armory);
            $total_def += $citizen_def;
            
            $watchers[$watcher->getId()] = array(
                'citizen' => $watcher->getCitizen(),
                'def' => $citizen_def,
                'bonusDef' => $this->citizen_handler->getNightwatchProfessionDefenseBonus($watcher->getCitizen()),
                'bonusSurvival' => $this->citizen_handler->getNightwatchProfessionSurvivalBonus($watcher->getCitizen()),
                'status' => array(),
                'items' => array()
            );

            foreach ($watcher->getCitizen()->getStatus() as $status) {
                switch($status->getName()){
                    case 'drunk':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => 20,
                            'deathImpact' => 4
                        );
                        break;
                    case 'hungover':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -15,
                            'deathImpact' => 5
                        );
                        break;
                    case 'terror':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -30,
                            'deathImpact' => 45
                        );
                        break;
                    case 'drugged':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => 10,
                            'deathImpact' => 0
                        );
                        break;
                    case 'addict':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => 15,
                            'deathImpact' => 15
                        );
                        break;
                    case 'wound1':
                    case 'wound2':
                    case 'wound3':
                    case 'wound4':
                    case 'wound5':
                    case 'wound6':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -20,
                            'deathImpact' => 20
                        );
                        break;
                    case 'healed':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -10,
                            'deathImpact' => 10
                        );
                        break;
                    case 'infection':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -15,
                            'deathImpact' => 20
                        );
                        break;
                    case 'thirst2':
                        $watchers[$watcher->getId()]['status'][] = array(
                            'icon' => $status->getIcon(),
                            'label' => $status->getLabel(),
                            'defImpact' => -10,
                            'deathImpact' => 0
                        );
                        break;
                }
            }

            if ($watcher->getCitizen()->hasRole('ghoul')) 
                $watchers[$watcher->getId()]['status'][] = array(
                    'icon' => 'ghoul',
                    'label' => 'Ghul',
                    'defImpact' => 0,
                    'deathImpact' => -5
                );

            foreach ($watcher->getCitizen()->getInventory()->getItems() as $item) {
                if($item->getPrototype()->getName() == 'chkspk_#00')
                    $has_counsel = true;

            	if($item->getPrototype()->getWatchpoint() <= 0)
                    continue;
                    
            	$watchers[$watcher->getId()]['items'][] = array(
                    'prototype' => $item->getPrototype(),
                    'defImpact' => $this->citizen_handler->getNightWatchItemDefense($item, $has_shooting_gallery, $has_trebuchet, $has_ikea, $has_armory),
                );
            }
        }

        // total def cannot be negative
        $total_def = max(0, $total_def);

        if($has_counsel){
            $total_def += 20 * count($watchers);
        }

        $deathChance = $this->citizen_handler->getDeathChances($this->getActiveCitizen());
        return $this->render( 'ajax/game/town/nightwatch.html.twig', $this->addDefaultTwigArgs('battlement', [
            'watchers' => $watchers,
            'is_watcher' => $is_watcher,
            'deathChance' => max(0.0, min($deathChance, 1.0)),
            'woundAndTerrorChance' => max(0.0, min($deathChance + $this->getTownConf()->get(TownConf::CONF_MODIFIER_WOUND_TERROR_PENALTY, 0.05), 1.0)),
            'me' => $this->getActiveCitizen(),
            'total_def' => $total_def,
            'has_counsel' => $has_counsel,
            'door_section' => 'nightwatch'
        ]) );
    }

    /**
     * @Route("api/town/nightwatch/gowatch", name="town_nightwatch_go_controller")
     * @param TownHandler $th
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function api_nightwatch_gowatch(TownHandler $th, JSONRequestParser $parser): Response
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
            'log' => $this->renderLog( -1, null, false, LogEntryTemplate::TypeCatapult, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
        ]) );
    }

    /**
     * @Route("api/town/catapult/log", name="town_catapult_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_catapult_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, LogEntryTemplate::TypeCatapult, null);
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
        if (!$th->getBuilding($town, 'item_courroie_#00', true) || !$citizen->hasRole('cata'))
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
        $ap = ($catapult = $th->getBuilding($town, 'item_courroie_#01', true)) ? 2 : 4;

        // Make sure the citizen has enough AP
        if ($citizen->getAp() < $ap || $ch->isTired($citizen)) return AjaxResponse::error(ErrorHelper::ErrorNoAP);

        // Different target zone
        if ($this->random_generator->chance(0.15)) {

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
        $this->citizen_handler->setAP($citizen, true, -4);

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

        $this->addFlash('notice', $trans->trans('Sorgfältig verpackt hast du %item% in das Katapult gelegt. Der Gegenstand wurde auf %zone% geschleudert.', [
            '%item%' => "<span><img alt='' src='" . $asset->getUrl("build/images/item/item_{$item->getPrototype()->getIcon()}.gif") . "' />" . $trans->trans($item->getPrototype()->getLabel(), [], 'items') . "</span>",
            '%zone%' => "<strong>[{$target_zone->getX()}/{$target_zone->getY()}]</strong>"
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
