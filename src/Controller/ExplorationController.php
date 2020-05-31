<?php

namespace App\Controller;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenEscortSettings;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\DigRuinMarker;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\EscortActionGroup;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\RuinExplorerStats;
use App\Entity\RuinZone;
use App\Entity\ScoutVisit;
use App\Entity\Town;
use App\Entity\Zone;
use App\Entity\ZoneTag;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\PictoHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use App\Translation\T;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class ExplorationController extends InventoryAwareController implements ExplorationInterfaceController
{
    protected $game_factory;
    protected $zone_handler;
    protected $item_factory;
    protected $death_handler;
    protected $asset;

    /**
     * BeyondController constructor.
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param ActionHandler $ah
     * @param TimeKeeperService $tk
     * @param DeathHandler $dh
     * @param PictoHandler $ph
     * @param TranslatorInterface $translator
     * @param GameFactory $gf
     * @param RandomGenerator $rg
     * @param ItemFactory $if
     * @param ZoneHandler $zh
     * @param LogTemplateHandler $lh
     * @param ConfMaster $conf
     * @param Packages $a
     */
    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh, PictoHandler $ph,
        TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg, ItemFactory $if, ZoneHandler $zh, LogTemplateHandler $lh, ConfMaster $conf, Packages $a)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $ph, $translator, $lh, $tk, $rg, $conf, $zh);
        $this->game_factory = $gf;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->asset = $a;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {

        return parent::addDefaultTwigArgs( $section,array_merge( [

        ], $data) );
    }

    protected function getCurrentRuinZone(): RuinZone {
        $citizen = $this->getActiveCitizen();
        $ex = $citizen->activeExplorerStats();
        return $this->entity_manager->getRepository(RuinZone::class)->findOneByPosition($citizen->getZone(), $ex->getX(), $ex->getY());
    }

    /**
     * @Route("jx/beyond/explore", name="exploration_dashboard")
     * @return Response
     */
    public function explore(): Response
    {
        $citizen = $this->getActiveCitizen();
        $ex = $citizen->activeExplorerStats();
        $ruinZone = $this->getCurrentRuinZone();

        return $this->render( 'ajax/game/beyond/ruin.html.twig', $this->addDefaultTwigArgs(null, [
            'prototype' => $citizen->getZone()->getPrototype(),
            'exploration' => $ex,
            'zone' => $ruinZone,
            'floor' => $ex->getInRoom() ? $ruinZone->getRoomFloor() : $ruinZone->getFloor(),
            'heroics' => $this->getHeroicActions(),
            'actions' => $this->getItemActions(),
            'recipes' => $this->getItemCombinations(false),
            'move' => $ruinZone->getZombies() <= 0,
            'zone_zombies' => $ruinZone->getZombies(),
            'shifted' => $ex->getInRoom(),
            'ruin_map_data' => [
                'zone' => $ruinZone,
                'shifted' => $ex->getInRoom(),
            ],
        ]) );
    }

    /**
     * @Route("api/beyond/explore/exit", name="beyond_ruin_enter_desert_controller")
     * @return Response
     */
    public function ruin_exit_api() {
        $citizen = $this->getActiveCitizen();

        $ex = $citizen->activeExplorerStats();
        if ($ex->getX() !== 0 || $ex->getY() !== 0)
            return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );

        // End the exploration!
        $citizen->removeExplorerStat($ex);
        $citizen->getZone()->removeExplorerStat($ex);
        $this->entity_manager->remove($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/move", name="beyond_ruin_move_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function ruin_move_api(JSONRequestParser $parser) {
        $ruinZone = $this->getCurrentRuinZone();
        if ($ruinZone->getZombies() > 0)
            return AjaxResponse::error( BeyondController::ErrorZoneBlocked );

        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if ($ex->getInRoom())
            return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );

        $dx = (int)$parser->get('x', 0);
        $dy = (int)$parser->get('y', 0);

        if (abs($dx) + abs($dy) !== 1)
            return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );

        if (
            ($dx == 1  && !$ruinZone->hasCorridor( RuinZone::CORRIDOR_E )) ||
            ($dx == -1 && !$ruinZone->hasCorridor( RuinZone::CORRIDOR_W )) ||
            ($dy == 1  && !$ruinZone->hasCorridor( RuinZone::CORRIDOR_N )) ||
            ($dy == -1 && !$ruinZone->hasCorridor( RuinZone::CORRIDOR_S ))
        ) return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );


        $ex->setX( $ex->getX() + $dx );
        $ex->setY( $ex->getY() - $dy );

        // End the exploration!
        $this->entity_manager->persist($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        $new_zone = $this->getCurrentRuinZone();
        return AjaxResponse::success(true, ['next' => ($new_zone->getX() !== 0 || $new_zone->getY() !== 0) ? $new_zone->getCorridor() : 'exit']);
    }

    /**
     * @Route("api/beyond/explore/shift", name="beyond_ruin_room_enter_controller")
     * @return Response
     */
    public function ruin_room_enter_api() {
        $ruinZone = $this->getCurrentRuinZone();
        if ($ruinZone->getZombies() > 0)
            return AjaxResponse::error( BeyondController::ErrorZoneBlocked );

        if (!$ruinZone->getPrototype() || $ruinZone->getLocked())
            return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );

        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if ($ex->getInRoom())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $ex->setInRoom( true );
        $this->entity_manager->persist($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/unshift", name="beyond_ruin_room_leave_controller")
     * @return Response
     */
    public function ruin_room_leave_api() {
        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if (!$ex->getInRoom())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $ex->setInRoom( false );
        $this->entity_manager->persist($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/item", name="beyond_ruin_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function item_explore_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $ex = $this->getActiveCitizen()->activeExplorerStats();
        $down_inv = $ex->getInRoom() ? $this->getCurrentRuinZone()->getRoomFloor() : $this->getCurrentRuinZone()->getFloor();
        $up_inv   = $this->getActiveCitizen()->getInventory();

        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $handler);
    }

    /**
     * @Route("api/beyond/explore/heroic", name="beyond_ruin_heroic_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function heroic_desert_api(JSONRequestParser $parser): Response {
        return $this->generic_heroic_action_api( $parser );
    }

    /**
     * @Route("api/beyond/explore/action", name="beyond_ruin_action_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function action_desert_api(JSONRequestParser $parser): Response {
        return $this->generic_action_api( $parser );
    }

    /**
     * @Route("api/beyond/explore/recipe", name="beyond_ruin_recipe_controller")
     * @param JSONRequestParser $parser
     * @param ActionHandler $handler
     * @return Response
     */
    public function recipe_desert_api(JSONRequestParser $parser, ActionHandler $handler): Response {
        return $this->generic_recipe_api( $parser, $handler);
    }

}
