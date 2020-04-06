<?php

namespace App\Controller;

use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\DigRuinMarker;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\Recipe;
use App\Entity\ScoutVisit;
use App\Entity\TownClass;
use App\Entity\TrashCounter;
use App\Entity\User;
use App\Entity\UserPendingValidation;
use App\Entity\Zone;
use App\Repository\DigRuinMarkerRepository;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\Locksmith;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\ItemRequest;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\MemcachedStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class BeyondController extends InventoryAwareController implements BeyondInterfaceController
{

    const ErrorNoReturnFromHere     = ErrorHelper::BaseBeyondErrors + 1;
    const ErrorNotReachableFromHere = ErrorHelper::BaseBeyondErrors + 2;
    const ErrorZoneBlocked          = ErrorHelper::BaseBeyondErrors + 3;
    const ErrorZoneUnderControl     = ErrorHelper::BaseBeyondErrors + 4;
    const ErrorAlreadyWounded       = ErrorHelper::BaseBeyondErrors + 5;
    const ErrorNotDiggable          = ErrorHelper::BaseBeyondErrors + 6;
    const ErrorDoorClosed           = ErrorHelper::BaseBeyondErrors + 7;
    const ErrorChatMessageInvalid   = ErrorHelper::BaseBeyondErrors + 8;
    const ErrorTrashLimitHit        = ErrorHelper::BaseBeyondErrors + 9;

    protected $game_factory;
    protected $zone_handler;
    protected $item_factory;
    protected $death_handler;

    /**
     * BeyondController constructor.
     * @param EntityManagerInterface $em
     * @param InventoryHandler $ih
     * @param CitizenHandler $ch
     * @param ActionHandler $ah
     * @param DeathHandler $dh
     * @param TimeKeeperService $tk
     * @param TranslatorInterface $translator
     * @param GameFactory $gf
     * @param RandomGenerator $rg
     * @param ItemFactory $if
     * @param ZoneHandler $zh
     * @param LogTemplateHandler $lh
     */
    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk, DeathHandler $dh,
        TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg, ItemFactory $if, ZoneHandler $zh, LogTemplateHandler $lh)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $translator, $lh, $tk, $rg);
        $this->game_factory = $gf;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
    }

    protected function deferZoneUpdate() {
        $this->zone_handler->updateZone( $this->getActiveCitizen()->getZone() );
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $zone = $this->getActiveCitizen()->getZone();
        $blocked = !$this->zone_handler->check_cp($zone, $cp);
        $escape = $this->get_escape_timeout( $this->getActiveCitizen() );
        $citizen_tired = $this->getActiveCitizen()->getAp() <= 0 || $this->citizen_handler->isTired( $this->getActiveCitizen());

        $scout_level = null;
        $scout_sense = false;

        if ($this->getActiveCitizen()->getProfession()->getName() === 'hunter') {
            $scout_level = $zone->getScoutLevel();
            $scout_sense = true;
        }

        $scout_movement = $this->inventory_handler->countSpecificItems(
            $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
        ) > 0;

        $trashlock = $this->getActiveCitizen()->getBanished() ? $this->getActiveCitizen()->getTrashCounter() : null;

        return parent::addDefaultTwigArgs( $section,array_merge( [
            'zone_players' => count($zone->getCitizens()),
            'zone_zombies' => max(0,$zone->getZombies()),
            'zone_cp' => $cp,
            'zone'  =>  $zone,
            'allow_movement' => (!$blocked || $escape > 0 || $scout_movement) && !$citizen_tired,
            'active_scout_mode' => $scout_movement,
            'scout_level' => $scout_level,
            'scout_sense' => $scout_sense,
            'heroics' => $this->getHeroicActions(),
            'actions' => $this->getItemActions(),
            'recipes' => $this->getItemCombinations(false),
            'km' => round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) )),
            'lock_trash' => $trashlock && $trashlock->getTaken() >= ( $this->getActiveCitizen()->getProfession()->getName() === 'collec' ? 4 : 3 ),
        ], $data, $this->get_map_blob()) );
    }

    public function get_escape_timeout(Citizen $c): int {
        $active_timer = $this->entity_manager->getRepository(EscapeTimer::class)->findActiveByCitizen( $c );
        return $active_timer ? ($active_timer->getTime()->getTimestamp() - (new DateTime())->getTimestamp()) : -1;
    }

    public function get_dig_timeout(Citizen $c, ?bool &$active = null): int {
        $active_timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen( $c );
        if (!$active_timer) return -1;
        $active = !$active_timer->getPassive();
        return $active_timer->getTimestamp()->getTimestamp() - (new DateTime())->getTimestamp();
    }

    public function uncoverHunter(Citizen $c): bool {
        $prot = $this->inventory_handler->fetchSpecificItems( $c->getInventory(), [new ItemRequest('vest_on_#00')] );
        if ($prot) {
            $prot[0]->setPrototype( $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( 'vest_off_#00' ) );
            return true;
        } else return false;
    }

    /**
     * @Route("jx/beyond/desert", name="beyond_dashboard")
     * @param TownHandler $th
     * @return Response
     */
    public function desert(TownHandler $th): Response
    {
        $this->deferZoneUpdate();
        $town = $this->getActiveCitizen()->getTown();
        $zone = $this->getActiveCitizen()->getZone();

        $watchtower = $th->getBuilding($town, 'item_tagger_#00',  true);
        if ($watchtower) switch ($watchtower->getLevel()) {
            case 4: $port_distance = 1;  break;
            case 5: $port_distance = 2;  break;
            default:$port_distance = 0; break;
        } else $port_distance = 0;
        $distance = round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));

        $can_enter = $distance <= $port_distance;
        $is_on_zero = $zone->getX() == 0 && $zone->getY() == 0;

        $citizen_tired = $this->getActiveCitizen()->getAp() <= 0 || $this->citizen_handler->isTired( $this->getActiveCitizen());
        $dig_timeout = $this->get_dig_timeout( $this->getActiveCitizen(), $dig_active );

        $blocked = !$this->zone_handler->check_cp($zone, $cp);
        $escape = $this->get_escape_timeout( $this->getActiveCitizen() );

        $require_ap = ($is_on_zero && $th->getBuilding($town, 'small_labyrinth_#00',  true));

        return $this->render( 'ajax/game/beyond/desert.html.twig', $this->addDefaultTwigArgs(null, [
            'scout' => $this->getActiveCitizen()->getProfession()->getName() === 'hunter',
            'allow_enter_town' => $can_enter,
            'show_ventilation'  => $is_on_zero && $th->getBuilding($town, 'small_ventilation_#00',  true) !== null,
            'allow_ventilation' => $this->getActiveCitizen()->getProfession()->getHeroic(),
            'enter_costs_ap' => $require_ap,
            'allow_floor_access' => !$is_on_zero,
            'can_escape' => !$this->citizen_handler->isWounded( $this->getActiveCitizen() ),
            'can_attack' => !$citizen_tired,
            'zone_blocked' => $blocked,
            'zone_escape' => $escape,
            'digging' => $dig_timeout >= 0 && $dig_active,
            'dig_ruin' => empty($this->entity_manager->getRepository(DigRuinMarker::class)->findByCitizen( $this->getActiveCitizen() )),
            'dig_timeout' => $dig_timeout,
            'actions' => $this->getItemActions(),
            'floor' => $this->getActiveCitizen()->getZone()->getFloor(),

            'log' => $this->renderLog( -1, null, $zone, null, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay()
        ]) );
    }

    /**
     * @Route("api/beyond/desert/log", name="beyond_desert_log_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function log_desert_api(JSONRequestParser $parser): Response {
        $zone = $this->getActiveCitizen()->getZone();
        if (!$zone || ($zone->getX() === 0 && $zone->getY() === 0))
            return $this->renderLog((int)$parser->get('day', -1), null, null, null, 0);
        return $this->renderLog((int)$parser->get('day', -1), null, $zone, null, null);
    }

    /**
     * @Route("api/beyond/trash", name="beyond_trash_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $factory
     * @return Response
     */
    public function trash_api(JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $factory): Response {

        $citizen = $this->getActiveCitizen();
        if (!$citizen->getBanished() || $citizen->getZone()->getX() !== 0 || $citizen->getZone()->getY())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $trashlock = $citizen->getTrashCounter();
        if (!$trashlock)
            $trashlock = (new TrashCounter())->setCitizen($citizen);

        $limit = $citizen->getProfession()->getName() === 'collec' ? 4 : 3;
        if ($trashlock->getTaken() >= $limit) return AjaxResponse::error(self::ErrorTrashLimitHit);

        $inv_target = $citizen->getInventory();
        $inv_source = null;

        $item_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneByName($this->random_generator->chance(0.125) ? 'trash_good' : 'trash_bad');
        $proto = $this->random_generator->pickItemPrototypeFromGroup( $item_group );
        if (!$proto)
            return AjaxResponse::error(ErrorHelper::ErrorInternalError);

        $item = $this->item_factory->createItem($proto);

        if (($error = $handler->transferItem(
                $citizen,
                $item,$inv_source, $inv_target
            )) === InventoryHandler::ErrorNone) {

            $trashlock->setTaken( $trashlock->getTaken()+1 );
            try {
                $this->entity_manager->persist($item);
                $this->entity_manager->persist($citizen);
                $this->entity_manager->persist($trashlock);
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
            return AjaxResponse::success();
        } else return AjaxResponse::error($error);
    }

    /**
     * @Route("api/beyond/desert/chat", name="beyond_desert_chat_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function chat_desert_api(JSONRequestParser $parser): Response {
        $message = $parser->get('msg', null);
        if (!$message || mb_strlen($message) < 2 || mb_strlen($message) > 256 )
            return AjaxResponse::error(self::ErrorChatMessageInvalid);

        try {
            $this->entity_manager->persist( $this->log->beyondChat( $this->getActiveCitizen(), $message ) );
            $this->entity_manager->flush(  );
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }


    /**
     * @Route("api/beyond/desert/hero_exit", name="beyond_desert_hero_exit_controller")
     * @param TownHandler $th
     * @return Response
     */
    public function desert_exit_hero_api(TownHandler $th): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();
        $town = $citizen->getTown();

        if (!$th->getBuilding($town, 'small_ventilation_#00',  true))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$citizen->getProfession()->getHeroic())
            return AjaxResponse::error( ErrorHelper::ErrorMustBeHero );

        $cp_ok = $this->zone_handler->check_cp( $zone );
        $citizen->setZone( null );
        $zone->removeCitizen( $citizen );
        $this->entity_manager->persist( $this->log->doorPass( $citizen, true ) );
        $this->zone_handler->handleCitizenCountUpdate( $zone, $cp_ok );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/exit", name="beyond_desert_exit_controller")
     * @param TownHandler $th
     * @return Response
     */
    public function desert_exit_api(TownHandler $th): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();
        $town = $citizen->getTown();

        $watchtower = $th->getBuilding($town, 'item_tagger_#00',  true);
        if ($watchtower) switch ($watchtower->getLevel()) {
            case 4: $port_distance = 1;  break;
            case 5: $port_distance = 2;  break;
            default:$port_distance = 0; break;
        } else $port_distance = 0;
        $distance = round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));

        if ($distance > $port_distance)
            return AjaxResponse::error( self::ErrorNoReturnFromHere );

        if (!$town->getDoor())
            return AjaxResponse::error( self::ErrorDoorClosed );

        if ($zone->getX() === 0 && $zone->getY() === 0 && $th->getBuilding($town, 'small_labyrinth_#00',  true)) {
            if ($citizen->getAp() <= 0 || $this->citizen_handler->isTired( $citizen ))
                return AjaxResponse::error( ErrorHelper::ErrorNoAP );
            $this->citizen_handler->setAP($citizen, true, -1);
        }

        $cp_ok = $this->zone_handler->check_cp( $zone );
        $citizen->setZone( null );
        $zone->removeCitizen( $citizen );
        $others_are_here = $zone->getCitizens()->count() > 0;

        if ( $distance > 0 ) {
            $zero_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition( $zone->getTown(), 0, 0 );
            if ($others_are_here) $this->entity_manager->persist( $this->log->outsideMove( $citizen, $zone, $zero_zone, true ) );
            $this->entity_manager->persist( $this->log->outsideMove( $citizen, $zero_zone, $zone, false ) );
        }
        $this->entity_manager->persist( $this->log->doorPass( $citizen, true ) );

        $this->zone_handler->handleCitizenCountUpdate( $zone, $cp_ok );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }



    /**
     * @Route("api/beyond/desert/move", name="beyond_desert_move_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function desert_move_api(JSONRequestParser $parser): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        $px = $parser->get('x', PHP_INT_MAX);
        $py = $parser->get('y', PHP_INT_MAX);

        $cp_ok = $this->zone_handler->check_cp( $zone );
        $scout_movement = $this->inventory_handler->countSpecificItems(
                $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
            ) > 0;

        if (abs($px - $zone->getX()) + abs($py - $zone->getY()) !== 1) return AjaxResponse::error( self::ErrorNotReachableFromHere );
        if (!$cp_ok && $this->get_escape_timeout( $citizen ) < 0 && !$scout_movement) return AjaxResponse::error( self::ErrorZoneBlocked );

        /** @var Zone $new_zone */
        $new_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition( $citizen->getTown(), $px, $py );
        if (!$new_zone) return AjaxResponse::error( self::ErrorNotReachableFromHere );

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Moving disables the dig timer
        if ($dig_timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen($citizen)) {
            $dig_timer->setPassive(true);
            $this->entity_manager->persist( $dig_timer );
        }

        // Moving invalidates any escape timer the user may have had
        foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
            $this->entity_manager->remove( $et );

        $clothes = $this->inventory_handler->fetchSpecificItems($citizen->getInventory(),[new ItemRequest('basic_suit_#00')]);
        if (!empty($clothes)) $clothes[0]->setPrototype( $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( 'basic_suit_dirt_#00' ) );

        $zone->removeCitizen( $citizen );
        $new_zone
            ->addCitizen( $citizen )
            ->setDiscoveryStatus( Zone::DiscoveryStateCurrent )
            ->setZombieStatus( max(Zone::ZombieStateEstimate, $new_zone->getZombieStatus() ) );

        if ($citizen->getProfession()->getName() === 'hunter' && !$this->entity_manager->getRepository(ScoutVisit::class)->findByCitizenAndZone($citizen,$new_zone)) {
            $new_zone->addScoutVisit( (new ScoutVisit())->setScout( $citizen ) );
            if ($scout_movement && !$this->zone_handler->check_cp( $new_zone )) {

                $new_zed_count = $new_zone->getZombies();
                $new_zone_lv = $new_zone->getScoutLevel();
                $factor = pow( max(0, $new_zed_count - 3*$new_zone_lv), 1.0 + (max(0, $new_zed_count - 3*$new_zone_lv))/60.0 ) / 100.0;

                if ($this->random_generator->chance( $factor ) && $this->uncoverHunter($citizen))
                    $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );
            }
        }


        $this->citizen_handler->setAP($citizen, true, -1);
        $citizen->setWalkingDistance( $citizen->getWalkingDistance() + 1 );
        if ($citizen->getWalkingDistance() > 10) {
            $this->citizen_handler->increaseThirstLevel( $citizen );
            $citizen->setWalkingDistance( 0 );
        }

        $others_are_here = $zone->getCitizens()->count() > 0;

        if ($others_are_here || ($zone->getX() === 0 && $zone->getY() === 0)) $this->entity_manager->persist( $this->log->outsideMove( $citizen, $zone, $new_zone, true  ) );
        $this->entity_manager->persist( $this->log->outsideMove( $citizen, $new_zone, $zone, false ) );

        try {
            $this->zone_handler->handleCitizenCountUpdate($zone, $cp_ok);
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        // If the new zone is controlled by citizens, invalidate all escape timers
        if ($this->zone_handler->check_cp( $new_zone )) foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByZone($new_zone) as $et)
            $this->entity_manager->remove( $et );

        try {
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($zone);
            $this->entity_manager->persist($new_zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/action", name="beyond_desert_action_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function action_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $this->deferZoneUpdate();

        $uncover_fun = function(ItemAction &$a) {

            if (!$a->getKeepsCover() && !$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );
        };

        return $this->generic_action_api( $parser, $uncover_fun);
    }

    /**
     * @Route("api/beyond/desert/heroic", name="beyond_desert_heroic_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function heroic_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $this->deferZoneUpdate();

        $uncover_fun = function(ItemAction &$a) {

            if (!$a->getKeepsCover() && !$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );
        };

        return $this->generic_heroic_action_api( $parser, $uncover_fun);
    }

    /**
     * @Route("api/beyond/desert/recipe", name="beyond_desert_recipe_controller")
     * @param JSONRequestParser $parser
     * @param ActionHandler $handler
     * @return Response
     */
    public function recipe_desert_api(JSONRequestParser $parser, ActionHandler $handler): Response {
        $this->deferZoneUpdate();

        $uncover_fun = function(Recipe &$r) {
            if (!$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );
        };

        return $this->generic_recipe_api( $parser, $handler, $uncover_fun);
    }

    /**
     * @Route("api/beyond/desert/item", name="beyond_desert_item_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function item_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $this->deferZoneUpdate();
        $up_inv   = $this->getActiveCitizen()->getInventory();
        $down_inv = $this->getActiveCitizen()->getZone()->getFloor();
        if (!$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
            $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );
        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $handler);
    }

    /**
     * @Route("api/beyond/desert/escape", name="beyond_desert_escape_controller")
     * @return Response
     */
    public function escape_desert_api(): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        if ($this->zone_handler->check_cp( $citizen->getZone() ) || $this->get_escape_timeout( $citizen ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->inventory_handler->countSpecificItems(
                $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
            ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->citizen_handler->isWounded( $citizen ))
            return AjaxResponse::error( self::ErrorAlreadyWounded );

        $this->citizen_handler->inflictWound( $citizen );

        try {
            $escape = (new EscapeTimer())
                ->setZone( $citizen->getZone() )
                ->setCitizen( $citizen )
                ->setTime( new DateTime('+1min') );
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $escape );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/attack", name="beyond_desert_attack_controller")
     * @param RandomGenerator $generator
     * @return Response
     */
    public function attack_desert_api(RandomGenerator $generator): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if ($this->zone_handler->check_cp( $zone ) || $this->get_escape_timeout( $citizen ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->inventory_handler->countSpecificItems(
                $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
            ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($citizen->getAp() <= 0 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $this->citizen_handler->setAP( $citizen, true, -1 );
        if ($generator->chance( 0.1 )) {
            $zone->setZombies( $zone->getZombies() - 1 );
            $this->entity_manager->persist( $this->log->zombieKill( $citizen, null, 1 ) );
        }

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $zone );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/dig", name="beyond_desert_dig_controller")
     * @return Response
     */
    public function desert_dig_api(): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$this->zone_handler->check_cp( $zone ))
            return AjaxResponse::error( self::ErrorZoneBlocked );
        if ($zone->getX() === 0 && $zone->getY() === 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        try {
            $timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen( $citizen );
            if (!$timer) $timer = (new DigTimer())->setZone( $zone )->setCitizen( $citizen );
            else if ($timer->getTimestamp() > new DateTime())
                return AjaxResponse::error( self::ErrorNotDiggable );

            $timer->setPassive( false )->setTimestamp( new DateTime('-1sec') );
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $zone );
            $this->entity_manager->persist( $timer );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/scavenge", name="beyond_desert_scavenge_controller")
     * @return Response
     */
    public function desert_scavenge_api(): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$zone->getPrototype() || $zone->getBuryCount() > 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        $scout = $this->inventory_handler->countSpecificItems(
                $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
            ) > 0;

        //if (!$this->zone_handler->check_cp( $zone ) && !$scout)
        //    return AjaxResponse::error( self::ErrorZoneBlocked );
        if ($zone->getX() === 0 && $zone->getY() === 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        if ($this->entity_manager->getRepository(DigRuinMarker::class)->findByCitizen( $citizen ))
            return AjaxResponse::error( self::ErrorNotDiggable );

        $dm = (new DigRuinMarker())->setCitizen( $citizen )->setZone( $zone );

        if (!$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
            $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );

        if ($zone->getRuinDigs() > 0) {
            $zone->setRuinDigs( $zone->getRuinDigs() - 1 );
            $group = $zone->getPrototype()->getDrops();
            $prototype = $group ? $this->random_generator->pickItemPrototypeFromGroup( $group ) : null;
            if ($prototype) {
                $item = $this->item_factory->createItem( $prototype );
                if ($item) {
                    $this->inventory_handler->placeItem( $citizen, $item, [ $citizen->getInventory(), $zone->getFloor() ] );
                    $this->entity_manager->persist( $this->log->outsideDig( $citizen, $prototype ) );
                    $this->entity_manager->persist( $item );
                    $this->entity_manager->persist( $citizen->getInventory() );
                    $this->entity_manager->persist( $zone->getFloor() );
                }
            }
        }

        try {
            $this->entity_manager->persist($dm);
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/uncover", name="beyond_desert_uncover_controller")
     * @return Response
     */
    public function desert_uncover_api(): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$zone->getPrototype() || $zone->getBuryCount() <= 0 || ($zone->getX() === 0 && $zone->getY() === 0))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired($citizen))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        if (!$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
            $this->addFlash( 'notice', 'Deine Tarnung ist aufgeflogen!' );

        $this->citizen_handler->setAP($citizen, true, -1);
        $zone->setBuryCount( $zone->getBuryCount() - 1 );
        $this->entity_manager->persist( $this->log->outsideUncover( $citizen ) );

        try {
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

}
