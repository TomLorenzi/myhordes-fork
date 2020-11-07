<?php

namespace App\Controller\Town;

use App\Entity\Building;
use App\Entity\CitizenWatch;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\DailyUpgradeVote;
use App\Entity\ItemPrototype;
use App\Entity\Recipe;
use App\Entity\TownLogEntry;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\ErrorHandler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        if ($citizen->getDailyUpgradeVote() || $citizen->getBanished())
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
     * @param TownHandler $th
     * @param InventoryHandler $iv
     * @return Response
     */
    public function addon_watchtower(TownHandler $th, InventoryHandler $iv): Response
    {
        $town = $this->getActiveCitizen()->getTown();
        if (!$th->getBuilding($town, 'item_tagger_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $has_zombie_est_tomorrow = !empty($th->getBuilding($town, 'item_tagger_#02'));

        $z_today_min = $z_today_max = $z_tomorrow_min = $z_tomorrow_max = null; $z_q2 = 0;
        $z_q1 = $th->get_zombie_estimation_quality( $town, 0, $z_today_min, $z_today_max );
        if ($has_zombie_est_tomorrow && $z_q1 >= 1) $z_q2 = $th->get_zombie_estimation_quality( $town, 1, $z_tomorrow_min, $z_tomorrow_max );

        /** @var ZombieEstimation $est0 */
        $est0 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay());
        /** @var ZombieEstimation $est1 */
        $est1 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()+1);

        return $this->render( 'ajax/game/town/watchtower.html.twig', $this->addDefaultTwigArgs('watchtower', [
            'z0' => [ true, true, $z_today_min, $z_today_max, round($z_q1*100) ],
            'z1' => [ $has_zombie_est_tomorrow, $z_q1 >= 1, $z_tomorrow_min, $z_tomorrow_max, round($z_q2*100) ],
            'z0_av' => $est0 && !$est0->getCitizens()->contains( $this->getActiveCitizen() ),
            'z1_av' => $est1 && !$est1->getCitizens()->contains( $this->getActiveCitizen() ),
            'has_scanner' => !empty($th->getBuilding($town, 'item_tagger_#01')),
            'has_calc'    => $has_zombie_est_tomorrow,
        ]) );
    }

    /**
     * @Route("api/town/watchtower/est", name="town_watchtower_estimate_controller")
     * @param JSONRequestParser $parser
     * @param TownHandler $th
     * @param RandomGenerator $rg
     * @return Response
     */
    public function watchtower_est_api(JSONRequestParser $parser, TownHandler $th, RandomGenerator $rg): Response {
        $town = $this->getActiveCitizen()->getTown();

        if (!$th->getBuilding($town, 'item_tagger_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has_all(['day'], false))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $day = (int)$parser->get('day');
        if ($day < 0 || $day > 1) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($day === 1) {
            if (!$th->getBuilding($town, 'item_tagger_#02', true))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            if ($th->get_zombie_estimation_quality($town, 0) < 1)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()+$day);
        if (!$est) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($est->getCitizens()->contains($this->getActiveCitizen()))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $c = 1;
        if ($th->getBuilding($town, 'item_tagger_#01', true)) $c *= 2;
        if ($this->inventory_handler->countSpecificItems($town->getBank(), 'scope_#00', false, false) > 0) $c *= 2;

        for ($i = 0; $i < $c; $i++)
            if ($est->getOffsetMin() + $est->getOffsetMax() > 10) {
                $increase_min = $rg->chance( $est->getOffsetMin() / ($est->getOffsetMin() + $est->getOffsetMax()) );
                if ($increase_min) $est->setOffsetMin( $est->getOffsetMin() - 1);
                else $est->setOffsetMax( $est->getOffsetMax() - 1);
            }
        $est->addCitizen($this->getActiveCitizen());

        if($day === 1) {
            // If we are calculating for tomorrow, we also add that this citizen estimated for today
            $est_current = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay());
            if(!$est_current->getCitizens()->contains($this->getActiveCitizen())) {
                $est_current->addCitizen($this->getActiveCitizen());
                $this->entity_manager->persist($est_current);    
            }
        }

        try {
            $this->entity_manager->persist($est);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

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
        $town = $this->getActiveCitizen()->getTown();
        $c_inv = $this->getActiveCitizen()->getInventory();
        $t_inv = $town->getBank();

        if (!$th->getBuilding($town, 'small_refine_#00', true))
            return $this->redirect($this->generateUrl('town_dashboard'));

        $have_saw  = $iv->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( 'saw_tool_#00' ), false, false ) > 0;
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

            'log' => $this->renderLog( -1, null, false, TownLogEntry::TypeWorkshop, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/town/workshop/log", name="town_workshop_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_workshop_api(JSONRequestParser $parser): Response {
        return $this->renderLog((int)$parser->get('day', -1), null, false, TownLogEntry::TypeWorkshop, null);
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
            'machine_gun_#00', 'gun_#00', 'chair_basic_#00', 'machine1_#00', 'machine2_#00', 'machine3_#00', 'pc_#00'
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
        ]) );
    }

    /**
     * @Route("api/town/dump/do", name="town_dump_do_controller")
     * @param JSONRequestParser $parser
     * @param CitizenHandler $ch
     * @param TownHandler $th
     * @return Response
     */
    public function dump_do_api(JSONRequestParser $parser, CitizenHandler $ch, TownHandler $th): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        // Check if citizen is banished or dump is not build
        if ($citizen->getBanished() || !($dump = $th->getBuilding($town, 'small_trash_#00', true)))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Get prototype ID
        if (!$parser->has_all(['id','ap'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');
        $ap = (int)$parser->get('ap');

        /** @var ItemPrototype $prototype */
        // Get the item prototype and make sure it is dump-able
        $prototype = $this->entity_manager->getRepository(ItemPrototype::class)->find( $id );
        if ($prototype === null || !($dump_def = $this->get_dump_def_for( $prototype, $th ))  )
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        // Check if dumping is free
        $free_dumps = $th->getBuilding( $town, 'small_trashclean_#00', true ) !== null;

        // Check if citizen has enough AP
        if (!$free_dumps && $citizen->getAp() < $ap)
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Check if items are available
        $items = $this->inventory_handler->fetchSpecificItems( $town->getBank(), [new ItemRequest($prototype->getName(),$ap)] );
        if (!$items) return AjaxResponse::error( ErrorHelper::ErrorItemsMissing );

        // Remove items
        $this->inventory_handler->forceRemoveItem( $items[0], $ap );

        // Reduce AP
        if (!$free_dumps) $citizen->setAp( $citizen->getAp() - $ap );

        // Increase def
        $dump->setTempDefenseBonus( $dump->getTempDefenseBonus() + $ap * $dump_def );

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
     * @Route("jx/town/nightwatch", name="town_nightwatch")
     * @param TownHandler $th
     * @return Response
     */
    public function addon_nightwatch(TownHandler $th): Response
    {
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
                            'deathImpact' => 10
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
            'deathChance' => $deathChance,
            'woundAndTerrorChance' => $deathChance + $this->getTownConf()->get(TownConf::CONF_MODIFIER_WOUND_TERROR_PENALTY, 0.05),
            'me' => $this->getActiveCitizen(),
            'total_def' => $total_def,
            'has_counsel' => $has_counsel
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
        } else if ($action == "watch") {

            if ($activeCitizenWatcher !== null)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            $citizenWatch = new CitizenWatch();
            $citizenWatch->setTown($town)->setCitizen($this->getActiveCitizen())->setDay($town->getDay());
            $town->addCitizenWatch($citizenWatch);

            $this->getActiveCitizen()->addCitizenWatch($citizenWatch);

            $this->entity_manager->persist($citizenWatch);
        }

        $this->entity_manager->persist($this->getActiveCitizen());
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    

}
