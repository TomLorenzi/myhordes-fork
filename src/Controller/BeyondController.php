<?php

namespace App\Controller;

use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenEscortSettings;
use App\Entity\CitizenStatus;
use App\Entity\DigRuinMarker;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\ScoutVisit;
use App\Entity\Zone;
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
    const ErrorNoMovementWhileHiding= ErrorHelper::BaseBeyondErrors + 10;

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
     * @param TranslatorInterface $translator
     * @param GameFactory $gf
     * @param RandomGenerator $rg
     * @param ItemFactory $if
     * @param ZoneHandler $zh
     * @param LogTemplateHandler $lh
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

    protected function deferZoneUpdate() {
        $str = $this->zone_handler->updateZone( $this->getActiveCitizen()->getZone(), null, $this->getActiveCitizen() );
        if ($str) $this->addFlash( 'notice', $str );
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $zone = $this->getActiveCitizen()->getZone();
        $blocked = !$this->zone_handler->check_cp($zone, $cp);
        $escape = $this->get_escape_timeout( $this->getActiveCitizen() );
        $citizen_tired = $this->getActiveCitizen()->getAp() <= 0 || $this->citizen_handler->isTired( $this->getActiveCitizen());
        $citizen_hidden = $this->getActiveCitizen()->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_hide' )) || $this->getActiveCitizen()->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' ));

        $scout_level = null;
        $scout_sense = false;

        if ($this->getActiveCitizen()->getProfession()->getName() === 'hunter') {
            $scout_level = $zone->getScoutLevel();
            $scout_sense = true;
        }

        $scout_movement = $this->inventory_handler->countSpecificItems(
            $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
        ) > 0;

        $trash_count = ($this->getActiveCitizen()->getBanished() || $this->getActiveCitizen()->getTown()->getDevastated()) ? $this->getActiveCitizen()->getSpecificActionCounterValue(ActionCounter::ActionTypeTrash) : 0;

        $rucksack_sizes = [];
        foreach ($this->getActiveCitizen()->getValidLeadingEscorts() as $escort)
            if ($escort->getAllowInventoryAccess())
                $rucksack_sizes[ $escort->getCitizen()->getId() ] = $this->inventory_handler->getSize( $escort->getCitizen()->getInventory() );

        return parent::addDefaultTwigArgs( $section,array_merge( [
            'zone_players' => count($zone->getCitizens()),
            'zone_zombies' => max(0,$zone->getZombies()),
            'zone_cp' => $cp,
            'zone'  =>  $zone,
            'allow_movement' => (!$blocked || $escape > 0 || $scout_movement) && !$citizen_tired && !$citizen_hidden,
            'active_scout_mode' => $scout_movement,
            'scout_level' => $scout_level,
            'scout_sense' => $scout_sense,
            'heroics' => $this->getHeroicActions(),
            'actions' => $this->getItemActions(),
            'camping' => $this->getCampingActions(),
            'recipes' => $this->getItemCombinations(false),
            'km' => $this->zone_handler->getZoneKm($zone),
            'ap' => $this->zone_handler->getZoneAp($zone),
            'lock_trash' => $trash_count >= ( $this->getActiveCitizen()->getProfession()->getName() === 'collec' ? 4 : 3 ),
            'citizen_hidden' => $citizen_hidden,
            'rucksack_sizes' => $rucksack_sizes,
        ], $data, $this->get_map_blob()) );
    }

    public function get_escape_timeout(Citizen $c): int {
        $active_timer = $this->entity_manager->getRepository(EscapeTimer::class)->findActiveByCitizen( $c );
        return $active_timer ? ($active_timer->getTime()->getTimestamp() - (new DateTime())->getTimestamp()) : -1;
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

        $blocked = !$this->zone_handler->check_cp($zone, $cp);
        $escape = $this->get_escape_timeout( $this->getActiveCitizen() );

        $require_ap = ($is_on_zero && $th->getBuilding($town, 'small_labyrinth_#00',  true));

        if (!$is_on_zero && $this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false)) {
            // Camping Information
            $camping_zone_texts = [
                1 => T::__("Wenn du hier schläfst, kannst du dich gleich selbst umbringen. Das geht schneller und du kannst deinen Tod selbst bestimmen.", 'game'),
                2 => "", // T::__('text','domain')
                3 => T::__("Hier ist so gut wie nichts, mit dem du dich verstecken könntest. Du fühlst dich leicht schutzlos...", 'game'),
                4 => T::__("Außer ein paar 'natürlichen' Schutzgelegenheiten bietet diese Zone nicht viel. Du musst dich irgendwie durchwursteln.", 'game'),
                5 => T::__("Wenn man hier bisschen sucht, lässt sich bestimmt ein adäquates Versteck finden.", 'game'),
                6 => T::__("An diesem Ort gibt es ein paar gute Versteckmöglichkeiten. Wenn du hier heute Nacht schlafen möchtest...", 'game'),
                7 => T::__("In diesem Sektor gibt es ein paar wirklich gute Unterschlupfmöglichkeiten.", 'game'),
                8 => T::__("Das ist der ideale Ort, um hier zu schlafen. An Versteckmöglichkeiten mangelt es wahrlich nicht.", 'game'),
            ];
            $zone_camping_base = ($zone->getPrototype() ? $zone->getPrototype()->getCampingLevel() : 0) + ($zone->getImprovementLevel() );
            if ($zone_camping_base <= 0) {
                $camping_zone = $camping_zone_texts[1];
            } else if ($zone_camping_base <= 2) {
                $camping_zone = $camping_zone_texts[2];
            } else if ($zone_camping_base <= 4) {
                $camping_zone = $camping_zone_texts[3];
            } else if ($zone_camping_base <= 6) {
                $camping_zone = $camping_zone_texts[4];
            } else if ($zone_camping_base <= 8) {
                $camping_zone = $camping_zone_texts[5];
            } else if ($zone_camping_base <= 10) {
                $camping_zone = $camping_zone_texts[6];
            } else if ($zone_camping_base <= 12) {
                $camping_zone = $camping_zone_texts[7];
            } else {
                $camping_zone = $camping_zone_texts[8];
            }

            $camping_zombie_texts = [
                0 => '', // T::__('text','domain')
                1 => T::__("Die Anwesenheit von ein paar Zombies in dieser Umgebung beunruhigt dich etwas...", 'game'),
                2 => T::__("Die große Anzahl der herumstreunenden Zombies ist bestimmt kein Vorteil... Verstecken könnte etwas schwierig werden.", 'game'),
            ];
            if ($zone->getZombies() >= 11) {
                $camping_zombies = $camping_zombie_texts[2];
            } else if ($zone->getZombies() >= 5) {
                $camping_zombies = $camping_zombie_texts[1];
            } else {
                $camping_zombies = $camping_zombie_texts[0];
            }

            $camping_chance_texts = [
                0 => T::__("Du schätzt, dass deine Überlebenschancen hier quasi Null sind... Besser gleich 'ne Zyanidkapsel schlucken.", 'game'),
                1 => T::__("Du schätzt, dass deine Überlebenschancen hier sehr gering sind. Vielleicht hast du ja Bock 'ne Runde Kopf oder Zahl zu spielen?", 'game'),
                2 => T::__("Du schätzt, dass deine Überlebenschancen hier gering sind. Hmmm... schwer zu sagen, wie das hier ausgeht.", 'game'),
                3 => T::__("Du schätzt, dass deine Überlebenschancen hier mittelmäßig sind. Ist allerdings einen Versuch wert.. obwohl, Unfälle passieren schnell...", 'game'),
                4 => T::__("Du schätzt, dass deine Überlebenschancen hier zufriedenstellend sind - vorausgesetzt du erlebst keine böse Überraschung.", 'game'),
                5 => T::__("Du schätzt, dass deine Überlebenschancen hier korrekt sind. Jetzt heißt's nur noch Daumen drücken!", 'game'),
                6 => T::__("Du schätzt, dass deine Überlebenschancen hier gut sind. Du müsstest hier problemlos die Nacht verbringen können.", 'game'),
                7 => T::__("Du schätzt, dass deine Überlebenschancen hier optimal sind. Niemand wird dich sehen - selbst wenn man mit dem Finger auf dich zeigt.", 'game'),
            ];
            $survival_chance = $this->getActiveCitizen()->getCampingChance() > 0
            ? $this->getActiveCitizen()->getCampingChance()
            : $this->citizen_handler->getCampingChance($this->getActiveCitizen());
            if ($survival_chance <= .15) {
                $camping_chance = $camping_chance_texts[0];
            } else if ($survival_chance <= .3) {
                $camping_chance = $camping_chance_texts[1];
            } else if ($survival_chance <= .45) {
                $camping_chance = $camping_chance_texts[2];
            } else if ($survival_chance <= .6) {
                $camping_chance = $camping_chance_texts[3];
            } else if ($survival_chance <= .75) {
                $camping_chance = $camping_chance_texts[4];
            } else if ($survival_chance <= .9) {
                $camping_chance = $camping_chance_texts[5];
            } else if ($survival_chance <= .99) {
                $camping_chance = $camping_chance_texts[6];
            } else if ($survival_chance == 1) {
                $camping_chance = $camping_chance_texts[7];
            } else {
                $camping_chance = "";
            }

            $camping_improvable = ($survival_chance < $this->citizen_handler->getCampingChance($this->getActiveCitizen())) ? T::__("Nicht weit entfernt von deinem aktuellen Versteck erblickst du ein noch besseres Versteck... Hmmm...vielleicht solltest du umziehen?", 'game') : "";

            $camping_blueprint = ($zone->getBlueprint() === Zone::BlueprintAvailable)
                ? T::__("Du erhälst einen Bauplan, wenn Du in diesem Gebäude campst.", 'game')
                : T::__("Hier wurde bereits ein Bauplan gefunden.", 'game');

            // Uncomment next line to show camping values in game interface.
            #$camping_debug = "DEBUG CampingChances\nSurvivalChance for Comparison: " . $survival_chance . "\nCitizenCampingChance: " . $this->getActiveCitizen()->getCampingChance() . "\nCitizenHandlerCalculatedChance: " . $this->citizen_handler->getCampingChance($this->getActiveCitizen()) . "\nCalculationValues:\n" . str_replace( ',', "\n", str_replace( ['{', '}'], '', json_encode($this->citizen_handler->getCampingValues($this->getActiveCitizen()), 8) ) );
        }

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
            'digging' => $this->getActiveCitizen()->isDigging(),
            'dig_ruin' => empty($this->entity_manager->getRepository(DigRuinMarker::class)->findByCitizen( $this->getActiveCitizen() )),
            'actions' => $this->getItemActions(),
            'floor' => $zone->getFloor(),
            'other_citizens' => $zone->getCitizens(),
            'log' => $this->renderLog( -1, null, $zone, null, 10 )->getContent(),
            'day' => $this->getActiveCitizen()->getTown()->getDay(),
            'camping_zone' => $camping_zone ?? '',
            'camping_zombies' => $camping_zombies ?? '',
            'camping_chance' => $camping_chance ?? '',
            'camping_improvable' => $camping_improvable ?? '',
            'camping_blueprint' => $camping_blueprint ?? '',
            'camping_debug' => $camping_debug ?? '',
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
     * @param PictoHandler $picto_handler
     * @return Response
     */
    public function trash_api(JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $factory): Response {

        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();
        if (!$town->getChaos() && (!$citizen->getBanished() || $citizen->getZone()->getX() !== 0 || $citizen->getZone()->getY() !== 0))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getAp() <= 0 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $trashlock = $citizen->getSpecificActionCounter(ActionCounter::ActionTypeTrash);

        $limit = $citizen->getProfession()->getName() === 'collec' ? 4 : 3;
        if ($trashlock->getCount() >= $limit) return AjaxResponse::error(self::ErrorTrashLimitHit);

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

            $trashlock->increment();
            $this->citizen_handler->setAP($citizen, true, -1);
            $this->addFlash( 'notice', $this->translator->trans( 'Nach einigen Anstrengungen hast du folgendes gefunden: %item%!', [
                '%item%' => "<span> {$this->translator->trans($item->getPrototype()->getLabel(), [], 'items')}</span>"
            ], 'game' ));

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

        if ( $citizen->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_hide' )) || $citizen->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' )) ) {
            return AjaxResponse::error( self::ErrorNoMovementWhileHiding );
        }

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

        $movers = [];
        foreach ($citizen->getValidLeadingEscorts() as $escort)
            $movers[] = $escort->getCitizen();

        $movers[] = $citizen;
        $scouts = [];

        $others_are_here = $zone->getCitizens()->count() > count($movers);
        $away_from_town = (abs($zone->getX()) + abs($zone->getY())) < (abs($new_zone->getX()) + abs($new_zone->getY()));

        foreach ($movers as $mover) {

            // Check if citizen moves as a scout
            $scouts[$mover->getId()] = $this->inventory_handler->countSpecificItems(
                    $mover->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('vest_on_#00')
                ) > 0;

            // Check if citizen can move (zone not blocked and enough AP)
            if (!$cp_ok && $this->get_escape_timeout( $mover ) < 0 && !$scouts[$mover->getId()]) return AjaxResponse::error( self::ErrorZoneBlocked );
            if ($mover->getAp() < 1 || $this->citizen_handler->isTired( $mover ))
                return AjaxResponse::error( $citizen->getId() === $mover->getId() ? ErrorHelper::ErrorNoAP : ErrorHelper::ErrorEscortFailure );

            // Check if escortee wants to go home
            if (count($movers) > 1 && $mover->getEscortSettings() && $mover->getEscortSettings()->getForceDirectReturn() && $away_from_town)
                return AjaxResponse::errorMessage( $this->translator->trans('%citizen% will zurück zur Stadt und wird dir nicht in diese Richtung folgen.', ['%citizen%' => $mover->getUser()->getUsername()], 'game') );
        }

        foreach ($movers as $mover) {

            // Moving disables the dig timer
            if ($dig_timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen($citizen)) {
                $dig_timer->setPassive(true);
                $this->entity_manager->persist($dig_timer);
            }

            // Moving invalidates any escape timer the user may have had
            foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($citizen) as $et)
                $this->entity_manager->remove($et);

            $clothes = $this->inventory_handler->fetchSpecificItems($citizen->getInventory(), [new ItemRequest('basic_suit_#00')]);
            if (!empty($clothes)) $clothes[0]->setPrototype($this->entity_manager->getRepository(ItemPrototype::class)->findOneByName('basic_suit_dirt_#00'));

            $zone->removeCitizen($citizen);
            $new_zone
                ->addCitizen($citizen)
                ->setDiscoveryStatus(Zone::DiscoveryStateCurrent)
                ->setZombieStatus(max(Zone::ZombieStateEstimate, $new_zone->getZombieStatus()));

            if ($citizen->getProfession()->getName() === 'hunter' && !$this->entity_manager->getRepository(ScoutVisit::class)->findByCitizenAndZone($citizen, $new_zone)) {
                $new_zone->addScoutVisit((new ScoutVisit())->setScout($citizen));
                if ($scout_movement && !$this->zone_handler->check_cp($new_zone)) {

                    $new_zed_count = $new_zone->getZombies();
                    $new_zone_lv = $new_zone->getScoutLevel();
                    $factor = pow(max(0, $new_zed_count - 3 * $new_zone_lv), 1.0 + (max(0, $new_zed_count - 3 * $new_zone_lv)) / 60.0) / 100.0;

                    if ($this->random_generator->chance($factor) && $this->uncoverHunter($citizen))
                        $this->addFlash('notice', 'Deine Tarnung ist aufgeflogen!');
                }
            }


            $this->citizen_handler->setAP($citizen, true, -1);
            $citizen->setWalkingDistance($citizen->getWalkingDistance() + 1);
            if ($citizen->getWalkingDistance() > 10) {
                $this->citizen_handler->increaseThirstLevel($citizen);
                $citizen->setWalkingDistance(0);
            }

            $others_are_here = $zone->getCitizens()->count() > 0;

            if ($others_are_here || ($zone->getX() === 0 && $zone->getY() === 0)) $this->entity_manager->persist($this->log->outsideMove($citizen, $zone, $new_zone, true));
            $this->entity_manager->persist($this->log->outsideMove($citizen, $new_zone, $zone, false));

            try {
                $this->zone_handler->handleCitizenCountUpdate($zone, $cp_ok);
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorInternalError);
            }

            // If the new zone is controlled by citizens, invalidate all escape timers
            if ($this->zone_handler->check_cp($new_zone)) foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByZone($new_zone) as $et)
                $this->entity_manager->remove($et);

            try {
                $this->entity_manager->persist($citizen);
                $this->entity_manager->persist($zone);
                $this->entity_manager->persist($new_zone);
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
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
                $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );
        };

        return $this->generic_action_api($parser, $uncover_fun);
    }

    /**
     * @Route("api/beyond/desert/heroic", name="beyond_desert_heroic_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function heroic_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        $this->deferZoneUpdate();
        $zone = $this->getActiveCitizen()->getZone();

        $uncover_fun = function(ItemAction &$a) use ($zone) {
            if (!$a->getKeepsCover() && !$this->zone_handler->check_cp( $zone ) && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );
        };

        return $this->generic_heroic_action_api( $parser, $uncover_fun);
    }

    /**
     * @Route("api/beyond/desert/camping", name="beyond_desert_camping_controller")
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    public function camping_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
      $this->deferZoneUpdate();

      return $this->generic_camping_action_api( $parser);
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
                $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );
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

        $down_inv = $this->getActiveCitizen()->getZone()->getFloor();
        $escort = $parser->get('escort', null);
        if ($escort !== null) {
            /** @var Citizen $c */
            $c = $this->entity_manager->getRepository(Citizen::class)->find((int)$escort);
            if ($c && ($es = $c->getEscortSettings()) && $es->getLeader() &&
                $es->getLeader()->getId() === $this->getActiveCitizen()->getId() && $es->getAllowInventoryAccess())
                $up_inv   = $c->getInventory();
            else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        } else $up_inv   = $this->getActiveCitizen()->getInventory();

        if (!$this->zone_handler->check_cp( $this->getActiveCitizen()->getZone() ) && $this->uncoverHunter($this->getActiveCitizen()))
            $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );
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
                $this->entity_manager->persist( $this->log->zombieKill($citizen, null, 1));
                // Add the picto Heroic Action
	            $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_wrestl_#00");
	            $this->picto_handler->give_picto($citizen, $picto);
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
     * @Route("api/beyond/desert/dig/{ext}", name="beyond_desert_dig_controller")
     * @param null|int|string $ext
     * @return Response
     */
    public function desert_dig_api($ext = null): Response {
        $this->deferZoneUpdate();

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$this->zone_handler->check_cp( $zone ))
            return AjaxResponse::error( self::ErrorZoneBlocked );
        if ($zone->getX() === 0 && $zone->getY() === 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        if ($ext === null)
            $target_citizens = [$citizen];
        elseif ($ext === 'all') {
            $target_citizens = [];
            foreach ($citizen->getValidLeadingEscorts() as $escort)
                $target_citizens[] = $escort->getCitizen();
        } elseif (is_numeric($ext)) {
            /** @var Citizen|null $t */
            $t = $this->entity_manager->getRepository(Citizen::class)->find( (int)$ext );
            if (!$t || !$t->getEscortSettings() || !$t->getEscortSettings()->getLeader() || $t->getEscortSettings()->getLeader()->getId() !== $citizen->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $target_citizens = [$t];
        } else $target_citizens = [];

        foreach ($target_citizens as $target_citizen)
            try {
                $timer = $this->entity_manager->getRepository(DigTimer::class)->findActiveByCitizen( $target_citizen );
                if (!$timer) $timer = (new DigTimer())->setZone( $zone )->setCitizen( $target_citizen );
                else if ($timer->getTimestamp() > new DateTime()) {
                    if (count($target_citizens) === 1)
                        return AjaxResponse::error( self::ErrorNotDiggable );
                    else continue;
                }

                $timer->setPassive( false )->setTimestamp( new DateTime('-1sec') );
                $this->entity_manager->persist( $target_citizen );
                $this->entity_manager->persist( $timer );
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorInternalError );
            }

        try {
            $this->entity_manager->persist( $zone );
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
            $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );

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

                $distance = round(sqrt(pow($zone->getX(),2) + pow($zone->getY(),2)));
                $pictoName = "";
                if($distance >= 6 && $distance <= 17) {
                    $pictoName = "r_explor_#00";
                } else if($distance >= 18) {
                    $pictoName = "r_explo2_#00";
                }
                if($pictoName != ""){
                    $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName($pictoName);
                    $this->picto_handler->give_picto($citizen, $picto);
                }
                $this->addFlash( 'notice', $this->translator->trans( 'Nach einigen Anstrengungen hast du folgendes gefunden: %item%!', [
                    '%item%' => "<span><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $prototype->getIcon() . '.gif' )}'> {$this->translator->trans($prototype->getLabel(), [], 'items')}</span>"
                ], 'game' ));
            } else {
                $this->addFlash( 'notice', $this->translator->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' ));
            }
        } else {
            $this->addFlash( 'notice', $this->translator->trans( 'Beim Durchsuchen der Ruine merkst du, dass es nichts mehr zu finden gibt. Leider...', [], 'game' ));
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
            $this->addFlash( 'notice', $this->translator->trans('Deine Tarnung ist aufgeflogen!',[], 'game') );

        $this->citizen_handler->setAP($citizen, true, -1);
        $zone->setBuryCount( $zone->getBuryCount() - 1 );
        $this->entity_manager->persist( $this->log->outsideUncover( $citizen ) );

        $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_digger_#00");
        $this->picto_handler->give_picto($citizen, $picto);

        try {
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/escort/self", name="beyond_desert_escort_self_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function desert_escort_self_api(JSONRequestParser $parser): Response {
        $this->deferZoneUpdate();

        if (!$this->activeCitizenIsNotCamping()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('on')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $on = (bool)$parser->get('on');

        $cf_ruc = (bool)$parser->get('cf_ruc', false);
        $cf_ret = (bool)$parser->get('cf_ret', false);

        $citizen = $this->getActiveCitizen();
        if ($citizen->getBanished()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$on) {
            if ($citizen->getEscortSettings()) $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        } elseif ($on && !$citizen->getEscortSettings()) {
            $citizen->setEscortSettings((new CitizenEscortSettings())
                ->setCitizen($citizen));
        }

        if ($on)
            $citizen->getEscortSettings()->setAllowInventoryAccess($cf_ruc)->setForceDirectReturn($cf_ret);

        //try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        //} catch (Exception $e) {
        //    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        //}

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/escort/{cid<\d+>}", name="beyond_desert_escort_controller")
     * @param int $cid
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function desert_escort_api(int $cid, JSONRequestParser $parser): Response {
        $this->deferZoneUpdate();

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('on')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $on = (bool)$parser->get('on');

        $citizen = $this->getActiveCitizen();

        /** @var Citizen|null $target_citizen */
        $target_citizen = $this->entity_manager->getRepository(Citizen::class)->find( $cid );

        if (!$target_citizen || $target_citizen->getZone()->getId() !== $citizen->getZone()->getId())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (!$citizen->getProfession()->getHeroic() || $citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($target_citizen->getBanished() || !$target_citizen->getEscortSettings() ||
            ($on && $target_citizen->getEscortSettings()->getLeader() !== null) || (!$on && ($target_citizen->getEscortSettings()->getLeader() === null || $target_citizen->getEscortSettings()->getLeader()->getId() !== $citizen->getId())))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        }

        $target_citizen->getEscortSettings()->setLeader( $on ? $citizen : null );

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $target_citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/desert/escort/all", name="beyond_desert_escort_drop_controller")
     * @param JSONRequestParser $parser
     * @return Response
     */
    public function desert_escort_api_drop_all(JSONRequestParser $parser): Response {
        $this->deferZoneUpdate();

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $citizen = $this->getActiveCitizen();

        foreach ($citizen->getValidLeadingEscorts() as $escort) {
            $escort->setLeader(null);
            $this->entity_manager->persist($escort);
        }

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

}
