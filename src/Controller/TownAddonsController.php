<?php

namespace App\Controller;

use App\Entity\Building;
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
        foreach ($town->getBuildings() as $b) if ($b->getComplete()) {
            if ($b->getPrototype()->getMaxLevel() > 0)
                $buildings[] = $b;
            $max_votes = max($max_votes, $b->getDailyUpgradeVotes()->count());
        }

        if (empty($buildings)) return $this->redirect( $this->generateUrl('town_dashboard') );

        return $this->render( 'ajax/game/town/upgrades.html.twig', $this->addDefaultTwigArgs('upgrade', [
            'buildings' => $buildings,
            'max_votes' => $max_votes,
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

        if ($citizen->getDailyUpgradeVote())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        $building = $this->entity_manager->getRepository(Building::class)->find($id);
        if (!$building || $building->getTown()->getId() !== $town->getId())
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
            if ($th->get_zombie_estimation_quality( $town, 0) < 1)
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        }

        /** @var ZombieEstimation $est */
        $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$town->getDay()+$day);
        if (!$est) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

        if ($est->getCitizens()->contains($this->getActiveCitizen()))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $c = 1;
        if ($th->getBuilding($town, 'item_tagger_#01', true)) $c++;

        for ($i = 0; $i < $c; $i++)
            if ($est->getOffsetMin() + $est->getOffsetMax() > 10) {
                $increase_min = $rg->chance( $est->getOffsetMin() / ($est->getOffsetMin() + $est->getOffsetMax()) );
                if ($increase_min) $est->setOffsetMin( $est->getOffsetMin() - 1);
                else $est->setOffsetMax( $est->getOffsetMax() - 1);
            }
        $est->addCitizen( $this->getActiveCitizen() );

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

        $have_saw  = $iv->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( 'saw_tool_#00' ) ) > 0;
        $have_manu = $th->getBuilding($town, 'small_factory_#00', true) !== null;

        $recipes = $this->entity_manager->getRepository(Recipe::class)->findByType( Recipe::WorkshopType );
        $source_db = []; $result_db = [];
        foreach ($recipes as $recipe) {
            /** @var Recipe $recipe */
            $min_s = $min_r = PHP_INT_MAX;
            foreach ($recipe->getProvoking() as $proto)
                $min_s = min($min_s, $iv->countSpecificItems( $t_inv, $proto ));
            $source_db[ $recipe->getId() ] = $min_s === PHP_INT_MAX ? 0 : $min_s;

            foreach ($recipe->getResult()->getEntries() as $entry)
                $min_r = min($min_r, $iv->countSpecificItems( $t_inv, $entry->getPrototype() ));
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

        if (!$th->getBuilding($town, 'small_refine_#00', true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Recipe $recipe */
        $recipe = $this->entity_manager->getRepository(Recipe::class)->find( $id );
        if ($recipe === null || $recipe->getType() !== Recipe::WorkshopType)
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (($error = $ah->execute_recipe( $citizen, $recipe, $remove, $message )) !== ActionHandler::ErrorNone )
            return AjaxResponse::error( $error );
        else try {
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

}
