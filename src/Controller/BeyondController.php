<?php

namespace App\Controller;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Entity\AccountRestriction;
use App\Entity\ActionCounter;
use App\Entity\ChatSilenceTimer;
use App\Entity\Citizen;
use App\Entity\CitizenEscortSettings;
use App\Entity\CitizenStatus;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\EscortActionGroup;
use App\Entity\HomeIntrusion;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\RuinExplorerStats;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Entity\ZoneTag;
use App\Enum\EventStages\BuildingValueQuery;
use App\Enum\Game\TransferItemOption;
use App\Enum\ScavengingActionType;
use App\Enum\ZoneActivityMarkerType;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\DeathHandler;
use App\Service\DoctrineCacheService;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\HookExecutor;
use App\Service\HTMLService;
use App\Service\InventoryHandler;
use App\Service\PictoHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Structures\EventConf;
use App\Structures\ItemRequest;
use App\Structures\TownConf;
use App\Translation\T;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(only_alive: true, only_beyond: true)]
#[Semaphore('town', scope: 'town')]
class BeyondController extends InventoryAwareController
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
    const ErrorEscortLimitHit       = ErrorHelper::BaseBeyondErrors + 11;
    const ErrorEscortFailure        = ErrorHelper::BaseBeyondErrors + 12;
    const ErrorTerrorized           = ErrorHelper::BaseBeyondErrors + 13;
    const ErrorEscortActionRefused  = ErrorHelper::BaseBeyondErrors + 14;
    const ErrorEscortFailureRuin    = ErrorHelper::BaseBeyondErrors + 15;
    const ErrorEscortTerrorized     = ErrorHelper::BaseBeyondErrors + 16;

    protected $game_factory;
    protected ZoneHandler $zone_handler;
    protected $item_factory;
    protected DeathHandler $death_handler;

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
     * @param UserHandler $uh
     * @param CrowService $armbrust
     * @param TownHandler $th
     */
    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk,
        DeathHandler $dh, PictoHandler $ph, TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg,
        ItemFactory $if, ZoneHandler $zh, LogTemplateHandler $lh, ConfMaster $conf, Packages $a, UserHandler $uh,
        CrowService $armbrust, TownHandler $th, DoctrineCacheService $doctrineCache, EventProxyService $events, HookExecutor $hookExecutor)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $ph, $translator, $lh, $tk, $rg, $conf, $zh, $uh, $armbrust, $th, $a, $doctrineCache, $events, $hookExecutor);
        $this->game_factory = $gf;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
    }

    protected function deferZoneUpdate() {
        $str = $this->zone_handler->updateRuinZone( $this->getActiveCitizen()->getZone()->activeExplorerStats() );
        if ($str) $this->addFlash( 'error', $str );
        $str = $this->zone_handler->updateZone( $this->getActiveCitizen()->getZone(), null, $this->getActiveCitizen() );
        if ($str) $this->addFlash( 'notice', $str );
        $this->entity_manager->flush();
    }

    public function before(): bool
    {
        if (parent::before()) {
            $this->deferZoneUpdate();
            return true;
        } else return false;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null, $merge_map = true ): array {
        $zone = $this->getActiveCitizen()->getZone();
        $blocked = !$this->zone_handler->isZoneUnderControl($zone, $cp);
        $escape = $this->get_escape_timeout( $this->getActiveCitizen(), true );
        $citizen_tired = $this->getActiveCitizen()->getAp() <= 0 || $this->citizen_handler->isTired( $this->getActiveCitizen());
        $citizen_hidden = !$this->activeCitizenIsNotCamping();

        $scavenger_sense = $this->getActiveCitizen()->getProfession()->getName() === 'collec';
        $scout_level = null;
        $scout_sense = false;

        if ($this->getActiveCitizen()->getProfession()->getName() === 'hunter') {
            $scout_level = $zone->getScoutLevel();
            $scout_sense = true;
        }

        $scout_movement = $this->inventory_handler->countSpecificItems(
            $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
        ) > 0;

        $trash_count = ($this->getActiveCitizen()->getBanished() || $this->getActiveCitizen()->getTown()->getDevastated()) ? $this->getActiveCitizen()->getSpecificActionCounterValue(ActionCounter::ActionTypeTrash) : 0;

        $zone_players = count($zone->getCitizens());

        if ($zone->isTownZone()) {
            $zone_players += $this->entity_manager->getRepository(Citizen::class)->count(['town' => $this->getActiveCitizen()->getTown(), 'zone' => null, 'alive' => true]);
        }

        $allow_movement = (!$blocked || $escape > 0 || $scout_movement) && !$citizen_tired && !$citizen_hidden && ($this->getActiveCitizen()->getEscortSettings() === null || $this->getActiveCitizen()->getEscortSettings()->getLeader() === null);
        return parent::addDefaultTwigArgs( $section, array_merge( [
            'zone_players' => $zone_players,
            'zone_zombies' => max(0,$zone->getZombies()),
            'zone_splatter' => max(0, $zone->getInitialZombies() - $zone->getZombies()),
            'can_attack_citizen' => !$this->citizen_handler->isTired($this->getActiveCitizen()) && $this->getActiveCitizen()->getAp() >= $this->getTownConf()->get(TownConf::CONF_MODIFIER_ATTACK_AP, 5) && !$this->citizen_handler->isWounded($this->getActiveCitizen()) && !$zone->isTownZone(),
            'can_devour_citizen' => $this->getActiveCitizen()->hasRole('ghoul') && !$zone->isTownZone(),
            'allow_devour_citizen' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'tg_ghoul_eat'),
            'zone_cp' => $cp,
            'zone'  =>  $zone,
            'allow_movement' => $allow_movement,
            'active_scout_mode' => $scout_movement,
            'scout_level' => $scout_level,
            'scout_sense' => $scout_sense,
            'scavenger_sense' => $scavenger_sense,
            'heroics' => $this->getHeroicActions(),
            'specials' => $this->getSpecialActions(),
            'camping' => $this->getCampingActions(),
            'km' => $this->zone_handler->getZoneKm($zone),
            'town_ap' => $this->zone_handler->getZoneAp($zone),
            'lock_trash' => $trash_count >= ( $this->getActiveCitizen()->getProfession()->getName() === 'collec' ? 4 : 3 ),
            'citizen_hidden' => $citizen_hidden,
            'can_explore' => $zone->getPrototype() && $zone->getPrototype()->getExplorable() &&
                !$this->citizen_handler->hasStatusEffect( $this->getActiveCitizen(), ['terror'] ) &&
                !$this->citizen_handler->isWounded( $this->getActiveCitizen() ) &&
                (!$blocked || $scout_movement) && !$zone->activeExplorerStats() && !$this->getActiveCitizen()->currentExplorerStats(),
            'exploration_blocked_wound'     => $zone->getPrototype() && $zone->getPrototype()->getExplorable() && $this->citizen_handler->isWounded( $this->getActiveCitizen() ),
            'exploration_blocked_blocked'   => $zone->getPrototype() && $zone->getPrototype()->getExplorable() && ($blocked && !$scout_movement),
            'exploration_blocked_infection' => false,
            'exploration_blocked_terror'    => $zone->getPrototype() && $zone->getPrototype()->getExplorable() && $this->citizen_handler->hasStatusEffect( $this->getActiveCitizen(), 'terror' ),
            'exploration_blocked_in_use'    => $zone->getPrototype() && $zone->getPrototype()->getExplorable() && $zone->activeExplorerStats(),
            'exploration_blocked_already'   => $zone->getPrototype() && $zone->getPrototype()->getExplorable() && $this->getActiveCitizen()->currentExplorerStats(),
            'tired' => $this->citizen_handler->isTired($this->getActiveCitizen()),
            'status_info' => [
                'can_drink' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'hasdrunk'),
                'can_eat' => !$this->citizen_handler->hasStatusEffect($this->getActiveCitizen(), 'haseaten')
            ],
        ], $data, $merge_map ? [
            'map_public_json'   => json_encode( $this->town_handler->get_public_map_blob( $this->getActiveCitizen()->getTown(), $this->getActiveCitizen(), $allow_movement ? 'beyond' : 'beyond-static', $this->getTownConf()->isNightTime() ? 'night' : 'day', "radar" ) )
        ] : []) );
    }

    public function get_escape_timeout(Citizen $c, bool $allow_desperate = false): int {
        $active_timer = $this->entity_manager->getRepository(EscapeTimer::class)->findActiveByCitizen( $c, false, $allow_desperate );
        return ($active_timer && (!$active_timer->getDesperate() || $allow_desperate)) ? ($active_timer->getTime()->getTimestamp() - (new DateTime())->getTimestamp()) : -1;
    }

    public function uncoverHunter(Citizen $c): bool {
        $prot = $this->inventory_handler->fetchSpecificItems( $c->getInventory(), [new ItemRequest('vest_on_#00')] );
        if ($prot) {
            $prot[0]->setPrototype( $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy(['name' => 'vest_off_#00']) );
            return true;
        } else return false;
    }

    /**
     * @param TownHandler $th
     * @param string $sect
     * @return Response
     */
    #[Route(path: 'jx/beyond/desert/{sect}', name: 'beyond_dashboard')]
    public function desert(TownHandler $th, string $sect = ''): Response
    {
        $citizen = $this->getActiveCitizen();
        $request = Request::createFromGlobals();
        $inline = $request->headers->get('X-Render-Target') === 'beyond_desert_content';

        if (!$citizen->getHasSeenGazette())
            return $this->redirect($this->generateUrl('game_newspaper'));
            
        $town = $citizen->getTown();
        $zone = $citizen->getZone();

        $port_distance = $this->events->queryTownParameter( $town, BuildingValueQuery::BeyondTeleportRadius );
        $distance = round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));

        $can_enter = $distance <= $port_distance && !$citizen->isCamping();
        $is_on_zero = $zone->getX() == 0 && $zone->getY() == 0;

        $citizen_tired = $citizen->getAp() <= 0 || $this->citizen_handler->isTired($citizen);

        $blocked = !$this->zone_handler->isZoneUnderControl($zone, $cp);
        $escape = $this->get_escape_timeout($citizen);
        $escape_desperate = ($escape < 0) ? $this->get_escape_timeout( $citizen, true ) : -1;

        $require_ap = ($is_on_zero && $th->getBuilding($town, 'small_labyrinth_#00',  true));

        if (!$is_on_zero && $this->getTownConf()->get(TownConf::CONF_FEATURE_CAMPING, false)) {
            $zone_camping_base = ($zone->getPrototype() ? $zone->getPrototype()->getCampingLevel() : 0) + ($zone->getImprovementLevel() );

            // Camping Information
            $camping_zone = match (true) {
                $zone_camping_base <= 0     => T::__("Wenn du hier schläfst, kannst du dich gleich selbst umbringen. Das geht schneller und du kannst deinen Tod selbst bestimmen.", 'game'),
                $zone_camping_base <= 2     => '',
                $zone_camping_base <= 4     => T::__("Hier ist so gut wie nichts, mit dem du dich verstecken könntest. Du fühlst dich leicht schutzlos...", 'game'),
                $zone_camping_base <= 6     => T::__("Außer ein paar 'natürlichen' Schutzgelegenheiten bietet diese Zone nicht viel. Du musst dich irgendwie durchwursteln.", 'game'),
                $zone_camping_base <= 8     => T::__("Wenn man hier bisschen sucht, lässt sich bestimmt ein adäquates Versteck finden.", 'game'),
                $zone_camping_base <= 10    => T::__("An diesem Ort gibt es ein paar gute Versteckmöglichkeiten. Wenn du hier heute Nacht schlafen möchtest...", 'game'),
                $zone_camping_base <= 12    => T::__("In diesem Sektor gibt es ein paar wirklich gute Unterschlupfmöglichkeiten.", 'game'),
                default                     => T::__("Das ist der ideale Ort, um hier zu schlafen. An Versteckmöglichkeiten mangelt es wahrlich nicht.", 'game'),
            };

            $camping_capacity = "";
            $ruin_capacity = $zone->getBuildingCampingCapacity();

            if($zone->getPrototype() && $ruin_capacity !== -1) {
                $zone_capacity = max(0, min(5, $zone->getBuildingCampingCapacity() - $this->entity_manager->getRepository(Zone::class)->findPreviousCampersCount($citizen)));

                // Ruin capacity Information
                $camping_capacity = match($zone_capacity) {
                    0       => T::__("Du siehst nicht wirklich, wo du dich hier verstecken könntest...", 'game'),
                    1       => T::__("Egal, wie sehr du suchst, es erscheint offensichtlich, dass es an diesem Ort nur ein einziges geeignetes Versteck gibt. Entscheide selbst...", 'game'),
                    2, 3    => T::__("Dieser Ort bietet Möglichkeiten zum Verstecken, wenn du kreativ genug bist.", 'game'),
                    4       => T::__("Du beobachtest mehrere geeignete Verstecke, aber es wird nicht für jeden etwas dabei sein.", 'game'),
                    5       => T::__("Es sollte nicht allzu schwer sein, diesen Ort zum Untertauchen auszunutzen.", 'game'),
                    default => '',
                };

                // When building at full capacity, display a special text
                if($zone->getPrototype()->getCapacity() > 0 && $zone_capacity <= 0) {
                    $camping_capacity = T::__("Es scheint, dass alle guten Verstecke bereits von deinen \"Freunden\" besetzt sind. Du musst also einen anderen Ort finden oder improvisieren...", 'game');

                    // Set building text to default (outside) when no slot is left
                    $camping_zone = T::__("Wenn du hier schläfst, kannst du dich gleich selbst umbringen. Das geht schneller und du kannst deinen Tod selbst bestimmen.", 'game');
                }
            }

            $camping_zombies = match (true) {
                $zone->getZombies() >= 11 => T::__("Die Anwesenheit von ein paar Zombies in dieser Umgebung beunruhigt dich etwas...", 'game'),
                $zone->getZombies() >=  5 => T::__("Die große Anzahl der herumstreunenden Zombies ist bestimmt kein Vorteil... Verstecken könnte etwas schwierig werden.", 'game'),
                default => ''
            };

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
            $survival_chance = $citizen->getCampingChance() > 0
                ? $citizen->getCampingChance()
                : $this->citizen_handler->getCampingOdds($citizen);

            $camping_chance = match (true) {
                $survival_chance <= .10     => T::__("Du schätzt, dass deine Überlebenschancen hier quasi Null sind... Besser gleich 'ne Zyanidkapsel schlucken.", 'game'),
                $survival_chance <= .30     => T::__("Du schätzt, dass deine Überlebenschancen hier sehr gering sind. Vielleicht hast du ja Bock 'ne Runde Kopf oder Zahl zu spielen?", 'game'),
                $survival_chance <= .50     => T::__("Du schätzt, dass deine Überlebenschancen hier gering sind. Hmmm... schwer zu sagen, wie das hier ausgeht.", 'game'),
                $survival_chance <= .65     => T::__("Du schätzt, dass deine Überlebenschancen hier mittelmäßig sind. Ist allerdings einen Versuch wert.. obwohl, Unfälle passieren schnell...", 'game'),
                $survival_chance <= .80     => T::__("Du schätzt, dass deine Überlebenschancen hier zufriedenstellend sind - vorausgesetzt du erlebst keine böse Überraschung.", 'game'),
                $survival_chance <= .90     => T::__("Du schätzt, dass deine Überlebenschancen hier korrekt sind. Jetzt heißt's nur noch Daumen drücken!", 'game'),
                $survival_chance  < 1.0     => T::__("Du schätzt, dass deine Überlebenschancen hier gut sind. Du müsstest hier problemlos die Nacht verbringen können.", 'game'),
                $survival_chance === 1.0    => T::__("Du schätzt, dass deine Überlebenschancen hier optimal sind. Niemand wird dich sehen - selbst wenn man mit dem Finger auf dich zeigt.", 'game'),
                default                     => '',
            };

            $camping_improvable = ($survival_chance < $this->citizen_handler->getCampingOdds($citizen))
                ? $this->translator->trans("Nicht weit entfernt von deinem aktuellen Versteck erblickst du ein noch besseres Versteck... Hmmm...vielleicht solltest du umziehen?", [], 'game')
                : "";

            $camping_blueprint = "";
            $blueprintFound = false;
            if ($zone->getBuryCount() <= 0) {
                if ($zone->getBlueprint() === Zone::BlueprintAvailable) {
                    $camping_blueprint = T::__("Du erhälst einen Bauplan, wenn Du in diesem Gebäude campst.", 'game');
                } else if ($zone->getBlueprint() === Zone::BlueprintFound) {
                    $camping_blueprint = T::__("Hier wurde bereits ein Bauplan gefunden.", 'game');
                    $blueprintFound = true;
                }
            } else $camping_blueprint = T::__("Du erhälst einen Bauplan wenn Du in diesem Gebäude campst, aber du musst zunächst die Zone aufräumen.", 'game');

            // Uncomment next line to show camping values in game interface.
            #$camping_debug = "DEBUG CampingChances\nSurvivalChance for Comparison: " . $survival_chance . "\nCitizenCampingChance: " . $citizen->getCampingChance() . "\nCitizenHandlerCalculatedChance: " . $this->citizen_handler->getCampingOdds($citizen) . "\nCalculationValues:\n" . str_replace( ',', "\n", str_replace( ['{', '}'], '', json_encode($this->citizen_handler->getCampingValues($citizen), 8) ) );
        }

        $zone_tags = [];
        if(!$is_on_zero) {
            $zone_tags = $this->entity_manager->getRepository(ZoneTag::class)->findAll();
        }

        $args = $this->addDefaultTwigArgs(null, array_merge([
            'scout' => $citizen->getProfession()->getName() === 'hunter',
            'allow_enter_town' => $can_enter,
            'doors_open' => $town->getDoor(),
            'town' => $town,
            'show_ventilation'  => $is_on_zero && $th->getBuilding($town, 'small_ventilation_#00',  true) !== null,
            'allow_ventilation' => $citizen->getProfession()->getHeroic(),
            'show_sneaky' => $is_on_zero && $citizen->hasRole('ghoul') && $town->getDoor(),
            'enter_costs_ap' => $require_ap,
            'can_escape' => !$this->citizen_handler->isWounded( $citizen ) && !$citizen_tired,
            'can_attack' => !$citizen_tired && !$this->citizen_handler->hasStatusEffect($citizen, 'wound2'),
            'can_attack_nr' => $citizen_tired ? 'tired' : ( $this->citizen_handler->isWounded($citizen) ? 'wounded' : false ),
            'can_escape_nr' => $citizen_tired ? 'tired' : ( $this->citizen_handler->isWounded($citizen) ? 'wounded' : false ),
            'zone_blocked' => $blocked,
            'zone_escape' => $escape,
            'zone_escape_desperate' => $escape_desperate,
            'digging' => $citizen->isDigging(),
            'dig_ruin' => $citizen->getZone()->getActivityMarkerFor( ZoneActivityMarkerType::RuinDig, $citizen ) === null,
            'actions' => $this->getItemActions(),
            'other_citizens' => $zone->getCitizens(),
            'day' => $citizen->getTown()->getDay(),
            'camping_zone' => $camping_zone ?? '',
            'camping_capacity' => $camping_capacity ?? '',
            'camping_zombies' => $camping_zombies ?? '',
            'camping_chance' => $camping_chance ?? '',
            'camping_improvable' => $camping_improvable ?? '',
            'camping_blueprint' => $camping_blueprint ?? '',
            'blueprintFound' => $blueprintFound ?? '',
            'camping_debug' => $camping_debug ?? '',
            'zone_tags' => $zone_tags ?? [],
            'sect' => $sect,
        ], $this->desert_partial_inventory_args() ), !$inline);

        return $inline
            ? $this->renderBlocks( 'ajax/game/beyond/desert.html.twig', ['content','js'], [ 'ajax/game/game.html.twig' => 'gma' ], $args )
            : $this->render( 'ajax/game/beyond/desert.html.twig', $args );
    }

    protected function desert_partial_inventory_args(): array {
        $citizen = $this->getActiveCitizen();

        $rucksack_sizes = [];
        foreach ($this->getActiveCitizen()->getValidLeadingEscorts() as $escort)
            if ($escort->getAllowInventoryAccess())
                $rucksack_sizes[ $escort->getCitizen()->getId() ] = $this->inventory_handler->getSize( $escort->getCitizen()->getInventory() );

        $floorItems = $citizen->getZone()->getFloor()->getItems()->toArray();
        usort($floorItems, function ($a, $b) {
            return strcmp($this->translator->trans($a->getPrototype()->getLabel(), [], 'items'), $this->translator->trans($b->getPrototype()->getLabel(), [], 'items'));
        });

        $has_hidden_items =
            ($citizen->getBanished() || $citizen->getTown()->getChaos()) &&
            !$citizen->getZone()->getFloor()->getItems()->filter(function(Item $i) { return $i->getHidden(); })->isEmpty();

        $escort_actions = [];
        foreach ($this->getActiveCitizen()->getValidLeadingEscorts() as $escort)
            $escort_actions[ $escort->getCitizen()->getId() ] = $this->action_handler->getAvailableItemEscortActions( $escort->getCitizen() );

        return [
            'citizen' => $citizen,
            'conf' => $this->getTownConf(),
            'other_citizens' => $citizen->getZone()->getCitizens(),
            'town_chaos' => $citizen->getTown()->getChaos(),
            'banished' => $citizen->getBanished(),
            'rucksack' => $citizen->getInventory(),
            'floorItems' => $floorItems,
            'hidden_items' => $has_hidden_items,
            'allow_floor_access' => !$citizen->getZone()->isTownZone(),
            'rucksack_size' => $this->inventory_handler->getSize( $citizen->getInventory() ),
            'rucksack_sizes' => $rucksack_sizes,
            'citizen_hidden' => !$this->activeCitizenIsNotCamping(),
            'zone_blocked' => !$this->zone_handler->isZoneUnderControl($citizen->getZone(), $cp),
            'escort_actions' => $escort_actions,
            'active_scout_mode' => $this->inventory_handler->countSpecificItems(
                    $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
                ) > 0,
        ];
    }

    protected function desert_partial_item_action_args(): array {

        return [
            'actions' => $this->getItemActions(),
            'recipes' => $this->getItemCombinations(false),
            'citizen_hidden' => !$this->activeCitizenIsNotCamping(),
            'active_scout_mode' => $this->inventory_handler->countSpecificItems(
                    $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
                ) > 0,
			'conf' => $this->getTownConf()
        ];
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/beyond/partial/desert/inventory', name: 'beyond_dashboard_partial_inventory')]
    public function desert_partial_inventory(): Response
    {
        return $this->render( 'ajax/game/beyond/partials/inventory.standalone.html.twig', $this->desert_partial_inventory_args() );
    }

    /**
     * @return Response
     */
    #[Route(path: 'jx/beyond/partial/desert/actions', name: 'beyond_dashboard_partial_item_actions')]
    public function desert_partial_item_actions(): Response
    {
        return $this->render( 'ajax/game/beyond/partials/item-actions.standalone.html.twig', $this->desert_partial_item_action_args() );
    }

    protected function activeCitizenIsNotEscorted() {
        $c = $this->getActiveCitizen();
        return !$c->getEscortSettings() || !$c->getEscortSettings()->getLeader();
    }

    protected function activeCitizenIsNotCamping() {
        $c = $this->getActiveCitizen();
        return
            !$c->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_hide'])) &&
            !$c->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_tomb']));
    }

    protected function activeCitizenCanAct() {
        return $this->activeCitizenIsNotEscorted() && $this->activeCitizenIsNotCamping();
    }

    /**
     * @param EventProxyService $proxy
     * @param GameProfilerService $gps
     * @return Response
     */
    #[Route(path: 'api/beyond/trash', name: 'beyond_trash_controller', condition: '')]
    public function trash_api(EventProxyService $proxy, GameProfilerService $gps): Response {

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

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

        $good = $this->random_generator->chance(0.125);

        $item_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneBy(['name' => $good ? 'trash_good' : 'trash_bad']);
        $proto = $this->random_generator->pickItemPrototypeFromGroup( $item_group, $this->getTownConf(), $this->conf->getCurrentEvents( $town ) );
        if (!$proto)
            return AjaxResponse::errorMessage( $this->translator->trans('Obwohl du minutenlang den Stadtmüll durchwühlst, findest du <strong>nichts Nützliches</strong>...', [], 'game') );

        $item = $this->item_factory->createItem($proto);
        $gps->recordItemFound( $proto, $citizen, null, 'trash' );



        if (($error = $proxy->transferItem(
            $citizen, $item,
            $inv_source, $inv_target,
            options: [ TransferItemOption::AllowMultiHeavy ]
        )) === InventoryHandler::ErrorNone) {

            $trashlock->increment();
            $this->citizen_handler->setAP($citizen, true, -1);
            $this->addFlash( 'notice', $this->translator->trans( 'Beim Durchwühlen des Mülls, der am Stadtrand herumliegt, findest du schließlich folgendes: {item}.<hr />Du hast <strong>1 Aktionspunkt(e)</strong> verbraucht.', [
                '{item}' => "<span class='tool'> <img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" )}'> {$this->translator->trans($item->getPrototype()->getLabel(), [], 'items')}</span>"
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
     * @param JSONRequestParser $parser
     * @param EventFactory $ef
     * @param EventDispatcherInterface $ed
     * @return Response
     */
    #[Route(path: 'api/beyond/bury_rucksack', name: 'beyond_bury_rucksack_controller', condition: '')]
    public function bury_rucksack_api(JSONRequestParser $parser, EventFactory $ef, EventDispatcherInterface $ed): Response {

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $down_inv = $citizen->getZone()->getFloor();
        $up_inv   = $citizen->getInventory();
        $town = $citizen->getTown();
        if ((!$town->getChaos() && !$citizen->getBanished()) || $citizen->getZone()->isTownZone())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getAp() < 2 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $hide_items = $hide_success = true;
        foreach ($citizen->getZone()->getCitizens() as $fellow_citizen)
            if(!$fellow_citizen->getBanished() && !$town->getChaos()) // If there's a non-banished citizen on the zone during a non-chaos town, the items are not hidden
                $hide_success = false;

        if (!$this->zone_handler->isZoneUnderControl( $this->getActiveCitizen()->getZone() ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
            $this->addFlash('collapse', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!', [], 'game'));
        $r = $this->generic_item_api( $up_inv, $down_inv, true, $parser, $ef, $ed, $citizen, true, $processed);
        if ($r->isSuccessResponse() && $processed > 0) {
            if (!$hide_success)
                $this->addFlash('notice', $this->translator->trans('Ein oder mehrere nicht-verbannte Bürger in der Umgebung haben dich dabei beobachtet.<hr/>Du hast 2 Aktionspunkte verbraucht.', [], 'game'));
            else {
                $this->citizen_handler->setAP($citizen, true, -2);
                $citizen->getZone()?->setItemsHiddenAt( new \DateTimeImmutable() );
                $this->entity_manager->persist($citizen);
                $this->addFlash('notice', $this->translator->trans('Du brauchst eine Weile, bis du alle Gegenstände versteckt hast, die du bei dir trägst... Ha Ha... Du hast 2 Aktionspunkte verbraucht.', [], 'game'));
            }

        } elseif ($r->isSuccessResponse() && !$hide_items && $processed > 0)
            $this->addFlash('notice', $this->translator->trans('Du kannst keine Gegenstände verstecken, solange jemand zuschaut ...', [], 'game'));
        elseif ($r->isSuccessResponse() && $hide_items && $processed === 0) {
            $this->addFlash('notice', $this->translator->trans('Du hast keine Gegenstände, die du verstecken könntest.', [], 'game'));
            return AjaxResponse::success();
        }

        return $r;
    }

    /**
     * @param string $special
     * @param TownHandler $th
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/exit/{special}', name: 'beyond_desert_exit_controller')]
    public function desert_exit_api(TownHandler $th, string $special = 'normal'): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();
        $town = $citizen->getTown();

        $port_distance = $this->events->queryTownParameter( $town, BuildingValueQuery::BeyondTeleportRadius );
        $distance = round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));

        if ($distance > $port_distance)
            return AjaxResponse::error( self::ErrorNoReturnFromHere );

        switch ($special) {
            case 'normal':
            case "normal-escort":
                if (!$citizen->getTown()->getDoor())
                    return AjaxResponse::error( self::ErrorDoorClosed );
                break;
            case 'sneak':
                if (!$citizen->hasRole('ghoul'))
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                if (!$citizen->getTown()->getDoor())
                    return AjaxResponse::error( self::ErrorDoorClosed );
                break;
            case 'hero':
                if (!$citizen->getProfession()->getHeroic())
                    return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
                break;
            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        $movers = [];
        $movers[] = $citizen;
        if ($special === 'normal-escort' || ($special === 'normal' && $distance > 0))
            foreach ($citizen->getValidLeadingEscorts() as $escort)
                $movers[] = $escort->getCitizen();
        else
            foreach ($citizen->getLeadingEscorts() as $escort)
                $escort->getCitizen()->getEscortSettings()->setLeader(null);

        $others_are_here = $zone->getCitizens()->count() > count($movers);

        $labyrinth = ($zone->getX() === 0 && $zone->getY() === 0 && in_array($special, ['normal', 'normal-escort', 'sneak']) && $th->getBuilding($town, 'small_labyrinth_#00',  true));

        foreach ($movers as $mover){
            // Check if the labyrinth is built and the user enters from 0/0
            if ($labyrinth && ($mover->getAp() <= 0 || $this->citizen_handler->isTired($mover)))
                return AjaxResponse::error( $mover->getId() === $citizen->getId() ? ErrorHelper::ErrorNoAP : BeyondController::ErrorEscortFailure );
        }

        $cp_ok = $this->zone_handler->isZoneUnderControl( $zone );

        foreach ($movers as $mover) {
            // If labyrinth is active, deduct 1AP
            if ($labyrinth) $this->citizen_handler->setAP($mover, true, -1);

            // Disable the escort
            if ($mover->getEscortSettings()) {
                $remove[] = $mover->getEscortSettings();
                $mover->setEscortSettings(null);
            }

            // Disable the dig timer
            if ($dig_timer = $mover->getCurrentDigTimer()) {
                $dig_timer->setPassive(true);
                $this->entity_manager->persist( $dig_timer );
            }

            if(($special === 'normal' || $special === 'normal-escort') && ($zone->getX() !== 0 || $zone->getY() !== 0)
                && $others_are_here)
                $this->entity_manager->persist($this->log->citizenTeleport($mover, $zone));

            // Remove zone from citizen
            $mover->setZone( null );
            $zone->removeCitizen( $mover );
            foreach ($this->entity_manager->getRepository(HomeIntrusion::class)->findBy(['victim' => $mover]) as $homeIntrusion)
                $this->entity_manager->remove($homeIntrusion);

            // Produce log entries
            if ($special !== 'sneak') {
                $this->entity_manager->persist( $this->log->doorPass( $mover, true ) );
                $this->entity_manager->persist($mover);
            }
        }

        $this->zone_handler->handleCitizenCountUpdate( $zone, $cp_ok, $movers[array_key_last($movers)] );

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
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/enter', name: 'beyond_desert_enter_ruin_controller')]
    public function ruin_enter_api() {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $citizen = $this->getActiveCitizen();

        // Make sure the ruin is explorable
        if (!$citizen->getZone()?->getPrototype()?->getExplorable())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Make sure the citizen is not wounded or terrorized
        if ($this->citizen_handler->isWounded( $citizen ) || $this->citizen_handler->hasStatusEffect( $citizen, ['terror'] ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        // Make sure the citizen has not already explored the ruin today
        if ($citizen->currentExplorerStats()) {
            $this->addFlash('error', $this->translator->trans('Du kannst heute nicht hierher zurückkehren... Dein Körper würde das nicht durchstehen.', [], 'game'));
            return AjaxResponse::success();
        }

        // Make sure no one else is exploring the ruin right now
        if ($citizen->getZone()->activeExplorerStats()) {
            $this->addFlash('error', $this->translator->trans('Jemand anders untersucht gerade diesen Ort.', [], 'game'));
            return AjaxResponse::success();
        }

        // Block exploring if currently escorting citizens
        if (!empty($citizen->getValidLeadingEscorts()))
            return AjaxResponse::error( self::ErrorEscortFailureRuin );

        // Block exploring if the zone is controlled by zombies
        $scout_movement = $this->inventory_handler->countSpecificItems(
                $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
            ) > 0;
        if (!$this->zone_handler->isZoneUnderControl( $citizen->getZone() ) && !$scout_movement)
            return AjaxResponse::error( self::ErrorZoneBlocked );

        // Make sure the citizen has enough AP
        if ($this->citizen_handler->isTired( $citizen ) || $citizen->getAp() < 1)
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        // Entering a ruin disables the dig timer
        if ($dig_timer = $citizen->getCurrentDigTimer()) {
            $dig_timer->setPassive(true);
            $this->entity_manager->persist( $dig_timer );
        }

        // Disable escort mode for citizens entering a ruin
        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove( $citizen->getEscortSettings() );
            $citizen->setEscortSettings(null);
        }

        // Begin the exploration!
        $this->picto_handler->give_picto($citizen, 'r_ruine_#00', 1);
        $this->citizen_handler->setAP( $citizen, true, -1 );

        $citizen->addExplorerStat((new RuinExplorerStats())->setActive(true)->setTimeout( (new DateTime())->add(DateInterval::createFromDateString(
            $this->getTownConf()->get($citizen->getProfession()->getName() === 'collec' ?
                TownConf::CONF_TIMES_EXPLORE_COLLEC :
                TownConf::CONF_TIMES_EXPLORE_NORMAL, '+5min')
        ) )));
        $this->entity_manager->persist($citizen);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param GameProfilerService $gps
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/move', name: 'beyond_desert_move_controller')]
    public function desert_move_api(JSONRequestParser $parser, GameProfilerService $gps): Response {
        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if ( !$this->activeCitizenIsNotCamping() )
            return AjaxResponse::error( self::ErrorNoMovementWhileHiding );

        if (!$this->activeCitizenIsNotEscorted()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $px = $parser->get('x', PHP_INT_MAX);
        $py = $parser->get('y', PHP_INT_MAX);

        $cp_ok = $this->zone_handler->isZoneUnderControl( $zone );
        $scout_movement = $this->inventory_handler->countSpecificItems(
            $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
        ) > 0;

        if (abs($px - $zone->getX()) + abs($py - $zone->getY()) !== 1) return AjaxResponse::error( self::ErrorNotReachableFromHere );
        if (!$cp_ok && $this->get_escape_timeout( $citizen, true ) < 0 && !$scout_movement) return AjaxResponse::error( self::ErrorZoneBlocked );

        /** @var Zone $new_zone */
        $new_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition( $citizen->getTown(), $px, $py );
        if (!$new_zone) return AjaxResponse::error( self::ErrorNotReachableFromHere );

        $cp_ok_new_zone = $this->zone_handler->isZoneUnderControl($new_zone, $cp_before_new_zone);
        if($cp_before_new_zone <= 0) $cp_ok_new_zone = null;

        $movement_interrupted = false;

        if ($this->citizen_handler->hasStatusEffect($citizen, 'wound4') && $this->random_generator->chance(0.20)) {
            $this->addFlash('notice', $this->translator->trans('Wenn du anfängst zu gehen, greift ein sehr starker Schmerz in dein Bein. Du fällst stöhnend zu Boden. Man verliert eine Aktion...', [], 'game'));
            $this->entity_manager->persist($this->log->outsideMoveoutsideMoveFailInjury( $citizen ));
            $movement_interrupted = true;
        }

        if ($zone->getZombies() > 0 && $this->citizen_handler->hasStatusEffect($citizen, 'terror') && $this->random_generator->chance(0.05)) {
            $this->addFlash('notice', $this->translator->trans('Als du dich umschaust, <strong>überfällt dich eine plötzliche, unkontrollierbare Panik</strong>! Es ist unmöglich, auch nur einen Schritt weiterzugehen.<hr/>Deine Bewegung wurde <strong>unterbrochen</strong> und du hast <strong>1 AP</strong> verloren.', [], 'game'));
            $this->entity_manager->persist($this->log->outsideMoveoutsideMoveFailTerror( $citizen ));
            $movement_interrupted = true;
        }

        if ($movement_interrupted) {
            $this->citizen_handler->setAP( $citizen, true, -1 );
            $this->entity_manager->persist($citizen);
            $this->entity_manager->flush();
            return AjaxResponse::success();
        }

        $movers = [$citizen];
        foreach ($citizen->getValidLeadingEscorts() as $escort)
            $movers[] = $escort->getCitizen();

        $scouts = [];

        $others_are_here = $zone->getCitizens()->count() > count($movers);
        $away_from_town = (abs($zone->getX()) + abs($zone->getY())) < (abs($new_zone->getX()) + abs($new_zone->getY()));

        foreach ($movers as $mover) {
            // Check if citizen moves as a scout
            $scouts[$mover->getId()] = $this->inventory_handler->countSpecificItems(
                    $mover->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
                ) > 0;

            // Check if citizen can move (zone not blocked and enough AP)
            if (!$cp_ok && $this->get_escape_timeout( $mover, true ) < 0 && !$scouts[$mover->getId()]) return AjaxResponse::error( self::ErrorZoneBlocked );
            if ($mover->getAp() < 1 || $this->citizen_handler->isTired( $mover ))
                return AjaxResponse::error( $citizen->getId() === $mover->getId() ? ErrorHelper::ErrorNoAP : BeyondController::ErrorEscortFailure );

            // Check if escortee wants to go home
            if (count($movers) > 1 && $mover->getEscortSettings() && $mover->getEscortSettings()->getForceDirectReturn() && $away_from_town)
                return AjaxResponse::errorMessage( $this->translator->trans('{citizen} möchte nicht in diese Richtung gehen! <strong>Er bittet dich darum, ihn in die Stadt zu bringen...</strong>', ['{citizen}' => "<span>{$mover->getName()}</span>"], 'game') );

            $movement_interrupted = false;
            if ($mover !== $citizen && $this->citizen_handler->hasStatusEffect($mover, 'wound4') && $this->random_generator->chance(0.20)) {
                $this->entity_manager->persist($this->log->outsideMoveoutsideMoveFailInjury( $mover ));
                $movement_interrupted = true;
            }

            if ($mover !== $citizen && $zone->getZombies() > 0 && $this->citizen_handler->hasStatusEffect($mover, 'terror') && $this->random_generator->chance(0.05)) {
                $this->entity_manager->persist($this->log->outsideMoveoutsideMoveFailTerror( $mover ));
                $movement_interrupted = true;
            }

            if ($movement_interrupted) {
                $this->citizen_handler->setAP( $mover, true, -1 );
                $this->entity_manager->persist($mover);
                $this->entity_manager->flush();
                return AjaxResponse::error( BeyondController::ErrorEscortFailure );
            }
        }

        foreach ($movers as $mover) {
            // Moving disables the dig timer
            if ($dig_timer = $mover->getCurrentDigTimer()) {
                $dig_timer->setPassive(true);
                $this->entity_manager->persist( $dig_timer );
            }

            // Moving invalidates any escape timer the user may have had
            foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByCitizen($mover) as $et)
                $this->entity_manager->remove( $et );

            // Single movers get their escort mode disabled
            if (count($movers) === 1 && $mover->getEscortSettings()) {
                $es = $mover->getEscortSettings();
                $mover->setEscortSettings(null);
                $this->entity_manager->remove( $es );
            }

            // Get them clothes dirty!
            $clothes = $this->inventory_handler->fetchSpecificItems($mover->getInventory(),[new ItemRequest('basic_suit_#00')]);
            if (!empty($clothes)) $clothes[0]->setPrototype( $this->entity_manager->getRepository( ItemPrototype::class )->findOneBy(['name' => 'basic_suit_dirt_#00']) );

            // Actually move to the new zone
            $zone->removeCitizen( $mover );
            $new_zone->addCitizen( $mover );

            // Scout check
            if ($mover->getProfession()->getName() === 'hunter') {
                $new_zone->addActivityMarker((new ZoneActivityMarker())
                    ->setCitizen($mover)
                    ->setTimestamp(new DateTime())
                    ->setType(ZoneActivityMarkerType::ScoutVisit)
                );
                $cpNewZone = $this->zone_handler->getZoneControlPoints($new_zone);
                if ($scouts[$mover->getId()] && $cpNewZone < $new_zone->getZombies()) {

                    $zedsAboveCps = $new_zone->getZombies() - $cpNewZone;
                    $delta = floor($zedsAboveCps * 1.3);

                    if ($this->getTownConf()->isNightMode()) {
                        $delta -= 5;
                    }
                    if ($delta <= 6) {
                        $delta *= 0.5;
                    }

                    $factor = max(0, $delta - (3 * $new_zone->getScoutLevel()))/100;

                    if ($this->random_generator->chance($factor) && $this->uncoverHunter($mover)) {
                        if ($mover->getId() === $citizen->getId())
                            $this->addFlash('notice', $this->translator->trans('Du wurdest von einem <strong>Zombie in der Zone entdeckt</strong>! Er hat sich in deine Richtung gedreht!<hr/>Deine Tarnung ist aufgeflogen!', [], 'game'));
                        else
                            $this->addFlash('notice', $this->translator->trans('Die Tarnung von {name} ist aufgeflogen!', ['{name}' => $mover->getName()], 'game'));
                        $gps->recordlostHood($mover, $new_zone, "outsideMove");
                    }
                }
            }

            // Set AP and increase walking distance counter
            $this->citizen_handler->setAP($mover, true, -1);
            $mover->setWalkingDistance( $mover->getWalkingDistance() + 1 );
            if ($mover->getWalkingDistance() > 10) {
                $this->citizen_handler->increaseThirstLevel($mover);
                $mover->setWalkingDistance( 0 );
            }

            $this->citizen_handler->inflictStatus($mover, "tg_chk_movewb");

            $smokeBombs = $zone->getChatSilenceTimers();
            $hideMove = false;
            foreach ($smokeBombs as $smokeBomb) {
                /** @var ChatSilenceTimer $smokeBomb */
                if($smokeBomb->getCitizen() == $mover){
                    if($smokeBomb->getTime() > new \DateTime("-1min")) {
                        $hideMove = true;
                    } else {
                        $zone->removeChatSilenceTimer($smokeBomb);
                        $this->entity_manager->remove($smokeBomb);
                    }
                }
                
            }

            // This text is a newly added one, but it breaks the "Sneak out of town"
            if ($others_are_here && !($zone->getX() === 0 && $zone->getY() === 0) && !$hideMove) $this->entity_manager->persist( $this->log->outsideMove( $mover, $zone, $new_zone, true  ) );
            if (!($new_zone->getX() === 0 && $new_zone->getY() === 0)) $this->entity_manager->persist( $this->log->outsideMove( $mover, $new_zone, $zone, false ) );

            // Banished citizen's stash check
            if(!$mover->getBanished() && $this->zone_handler->hasHiddenItem($new_zone) && $this->random_generator->chance(0.05)){
                $itemsForLog = [];
                $zone->setItemsHiddenAt(null);
                foreach ($new_zone->getFloor()->getItems() as $item) if ($item->getHidden()) {
                    /** @var Item $item */
                    if(isset($itemsForLog[$item->getPrototype()->getId()])) {
                        $itemsForLog[$item->getPrototype()->getId()]['count'] += 1;
                    } else {
                        $itemsForLog[$item->getPrototype()->getId()] = [
                            'item' => $item,
                            'count' => 1
                        ];
                    }
                }
                $this->entity_manager->persist($this->log->outsideFoundHiddenItems($mover, $itemsForLog));
                foreach ($new_zone->getFloor()->getItems() as $item) {
                    if($item->getHidden()){
                        $item->setHidden(false);
                        $this->entity_manager->persist($item);
                    }
                }
            }

            if ($new_zone->isTownZone() && $mover->getEscortSettings() && $mover->getEscortSettings()->getForceDirectReturn() && $mover->getTown()->getDoor()) {
                // The citizen want to go back home. When we're on the town zone, make it go inside automatically
                $mover->setZone(null);
                $zone->removeCitizen($mover);
                $this->addFlash('notice', $this->translator->trans("{citizen} bedankt sich herzlich bei dir, dass du ihn nach Hause gebracht hast!", ['{citizen}' => $mover->getName()], 'game'));
                $this->entity_manager->persist($this->log->beyondEscortCitizenBackHome($mover, $mover->getEscortSettings()->getLeader()));
                $this->entity_manager->remove($mover->getEscortSettings());
                $mover->setEscortSettings(null);
            }

            $this->entity_manager->persist($mover);
        }

        $upgraded_map = $this->town_handler->getBuilding($citizen->getTown(),'item_electro_#00', true);

        $new_zone
            ->setDiscoveryStatus( Zone::DiscoveryStateCurrent )
            ->setZombieStatus( max($upgraded_map ? Zone::ZombieStateExact : Zone::ZombieStateEstimate, $new_zone->getZombieStatus() ) );

        try {
            $this->zone_handler->handleCitizenCountUpdate($zone, $cp_ok, $movers[array_key_last($movers)]);
            $this->zone_handler->handleCitizenCountUpdate($new_zone, $cp_ok_new_zone);
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        // If the new zone is controlled by citizens, invalidate all escape timers
        if ($this->zone_handler->isZoneUnderControl( $new_zone )) foreach ($this->entity_manager->getRepository(EscapeTimer::class)->findAllByZone($new_zone) as $et)
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
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/action', name: 'beyond_desert_action_controller')]
    public function action_desert_api(JSONRequestParser $parser): Response {
        if (!$this->activeCitizenCanAct())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $uncover_fun = function(ItemAction &$a) {

            if (!$a->getKeepsCover() && !$this->zone_handler->isZoneUnderControl( $this->getActiveCitizen()->getZone() ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'collapse', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game') );
        };

        return $this->generic_action_api($parser, $uncover_fun);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/escort/action', name: 'beyond_desert_escort_action_controller')]
    public function escort_action_desert_api(JSONRequestParser $parser): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has_all(['citizen','meta','action'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Citizen $citizen */
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find( (int)$parser->get('citizen', -1) );
        if (!$citizen) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$citizen->getEscortSettings() ||
            !$citizen->getEscortSettings()->getLeader() ||
            $citizen->getEscortSettings()->getLeader()->getId() !== $this->getActiveCitizen()->getId()
        ) return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        /** @var EscortActionGroup $esc_act */
        $esc_act = $this->entity_manager->getRepository(EscortActionGroup::class)->find( (int)$parser->get('meta', -1) );
        if (!$esc_act) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$citizen->getEscortSettings()->getAllowInventoryAccess()) {

            if ($parser->get('item') !== 'p' || $parser->get('action') !== 'p')
                return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

            $groups = $this->action_handler->getAvailableItemEscortActions($citizen, $esc_act);
            if (count($groups) !== 1) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            list($item,$action) = $groups[0]->getPrimaryAction();
            if (!$item || !$action) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $parser->inject('item', $item->getId());
            $parser->inject('action', $action->getId());

        } else {
            $action  = $this->entity_manager->getRepository(ItemAction::class)->find( (int)$parser->get('action', -1) );
        }

        if (!$action) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (!$esc_act->getActions()->contains($action))
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);

        $uncover_fun = function(ItemAction &$a) use ($citizen) {
            if (!$a->getKeepsCover() && !$this->zone_handler->isZoneUnderControl( $citizen->getZone() ) && $this->get_escape_timeout( $citizen ) < 0 && $this->uncoverHunter($citizen))
                $this->addFlash( 'notice', $this->translator->trans('Die Tarnung von {name} ist aufgeflogen!', ['name' => $citizen], 'game') );
        };

        return $this->generic_action_api($parser, $uncover_fun, $citizen);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/heroic', name: 'beyond_desert_heroic_controller')]
    public function heroic_desert_api(JSONRequestParser $parser): Response {
        $zone = $this->getActiveCitizen()->getZone();

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $uncover_fun = function(ItemAction &$a) use ($zone) {
            if (!$a->getKeepsCover() && !$this->zone_handler->isZoneUnderControl( $zone ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'collapse', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game') );
        };

        return $this->generic_heroic_action_api( $parser, $uncover_fun);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/special_action', name: 'beyond_desert_special_action_controller')]
    public function special_action_api(JSONRequestParser $parser): Response {
        return $this->generic_special_action_api( $parser );
    }

    /**
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/camping', name: 'beyond_desert_camping_controller')]
    public function camping_desert_api(JSONRequestParser $parser, InventoryHandler $handler): Response {
        if (!$this->activeCitizenIsNotEscorted()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        return $this->generic_camping_action_api( $parser);
  }

    /**
     * @param JSONRequestParser $parser
     * @param ActionHandler $handler
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/recipe', name: 'beyond_desert_recipe_controller')]
    public function recipe_desert_api(JSONRequestParser $parser, ActionHandler $handler): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $uncover_fun = function(Recipe &$r) {
            if (!$r->getStealthy() && !$this->zone_handler->isZoneUnderControl( $this->getActiveCitizen()->getZone() ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'collapse', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game') );
        };

        return $this->generic_recipe_api( $parser, $handler, $uncover_fun);
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/item', name: 'beyond_desert_item_controller')]
    public function item_desert_api(JSONRequestParser $parser, EventFactory $ef, EventDispatcherInterface $ed): Response {
        $down_inv = $this->getActiveCitizen()->getZone()->getFloor();
        $escort = $parser->get_int('escort', null);

        if ($this->getActiveCitizen()->getZone()->isTownZone())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($escort !== null) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($escort);
            if ($citizen && ($es = $citizen->getEscortSettings()) && $es->getLeader() && $es->getLeader()->getId() === $this->getActiveCitizen()->getId() && $es->getAllowInventoryAccess()) {
                $up_inv   = $citizen->getInventory();
            }
            else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        } else {
            $up_inv = $this->getActiveCitizen()->getInventory();
            $citizen = $this->getActiveCitizen();
        }

        if (!$this->zone_handler->isZoneUnderControl( $citizen->getZone() ) && $this->get_escape_timeout( $citizen ) < 0 && $this->uncoverHunter($citizen))
            $this->addFlash( 'collapse', $citizen === $this->getActiveCitizen()
                ? $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game')
                : $this->translator->trans('Die Tarnung von {name} ist aufgeflogen!', ['name' => $citizen], 'game')
            );
        return $this->generic_item_api( $up_inv, $down_inv, $escort === null, $parser, $ef, $ed, $this->getActiveCitizen());
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/escape', name: 'beyond_desert_escape_controller')]
    public function escape_desert_api(): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        if ($this->zone_handler->isZoneUnderControl( $citizen->getZone() ) || $this->get_escape_timeout( $citizen, true ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->inventory_handler->countSpecificItems(
            $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
        ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );
        
        if($this->citizen_handler->hasStatusEffect($citizen, "terror"))
            return AjaxResponse::error(self::ErrorTerrorized);

        if ($this->citizen_handler->isWounded( $citizen ))
            return AjaxResponse::error( self::ErrorAlreadyWounded );

        $wound = $this->citizen_handler->inflictWound( $citizen );
        if ($wound !== null) switch ($wound->getName()) {
            case 'wound1': $wound = $this->translator->trans('Kopf', [], 'game'); break;
            case 'wound2': $wound = $this->translator->trans('Hand', [], 'game'); break;
            case 'wound3': $wound = $this->translator->trans('Arme', [], 'game'); break;
            case 'wound4': $wound = $this->translator->trans('Bein', [], 'game'); break;
            case 'wound5': $wound = $this->translator->trans('Auge', [], 'game'); break;
            case 'wound6': $wound = $this->translator->trans('Fuß', [], 'game'); break;
            default: $wound = null;
        }

        if ($wound !== null) {
            $this->addFlash('notice', $this->translator->trans('Bei deinem Fluchtversuch ist es einem Zombie gelungen dir eine Verletzung zuzufügen: {injury}! Du solltest hier besser schnell verschwinden!', ['injury' => "<strong>$wound</strong>"], 'game'));
            $this->entity_manager->persist( $this->log->escapeInjury($citizen));
        }
        try {
            $escape = (new EscapeTimer())
            ->setZone( $citizen->getZone() )
            ->setCitizen( $citizen )
            ->setDesperate( true )
            ->setTime( new DateTime('+5min') );
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $escape );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @param RandomGenerator $generator
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/attack', name: 'beyond_desert_attack_controller')]
    public function attack_desert_api(RandomGenerator $generator): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if ($this->citizen_handler->hasStatusEffect( $citizen, 'terror' ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableTerror );

        if ($this->zone_handler->isZoneUnderControl( $zone ) || $this->get_escape_timeout( $citizen, true ) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->inventory_handler->countSpecificItems($this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])) > 0)
            return AjaxResponse::error( self::ErrorZoneUnderControl );

        if ($this->citizen_handler->hasStatusEffect($citizen, 'wound2')) {
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailableWounded);
        }

        if ($citizen->getAp() <= 0 || $this->citizen_handler->isTired( $citizen ))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $old_cp_ok = $this->zone_handler->isZoneUnderControl($zone);

        $this->citizen_handler->setAP( $citizen, true, -1 );
        $ratio = 0.08;
        $messages = [];
        if ($this->citizen_handler->hasStatusEffect($citizen, "drunk"))
            $ratio /= 2;
        if ($generator->chance( $ratio )) {
            $zone->setZombies( $zone->getZombies() - 1 );
            $this->entity_manager->persist( $this->log->zombieKill($citizen, null, 1, 'barehand_attack'));
            // Add the picto Bare hands
            $this->picto_handler->give_picto($citizen, 'r_wrestl_#00');
            // Add the picto zed kill
            $this->picto_handler->give_picto($citizen, 'r_killz_#00');

            $messages[] = $this->translator->trans('Nach einem zähen Kampf gelingt es dir endlich <strong>einen Zombie</strong> gegen einen Felsen zu werfen... Sein Kopf ist <strong>explodiert</strong> und die ganze Soße klebt jetzt an deinen Füßen! Du torkelst ein paar Meter vom Ort des Geschehens weg und keuchst leise vor dich hin: Einer weniger.. Ha... ha...', [], 'game');
        } else {
            $this->entity_manager->persist( $this->log->zombieKillHandsFail($citizen));
            $messages[] = $this->translator->trans('Du stürzt dich auf eine dieser Kreaturen und <strong>umklammerst sie mit beiden Armen</strong>, um sie zu Fall zu bringen. Der Kontakt mit seiner <strong>verrotteten Haut</strong> bringt dich fast zum Kotzen... Du kämpfst und versuchst ihn irgendwie umzustoßen, doch ohne Erfolg. <strong>Das Biest hat dich mehrere Male um ein Haar gebissen!</strong> Erschöpft und demoralisiert lässt du von ihm ab, um dich zurückzuziehen...', [], 'game');
            if ($this->citizen_handler->hasStatusEffect($citizen, "drunk"))
                $messages[] = $this->translator->trans('Dein <strong>Trunkenheitszustand</strong> hilft dir wirklich nicht weiter. Das ist nicht gerade einfach, wenn sich alles dreht und du nicht mehr klar siehst.', [], 'game');
        }

        if (!empty($messages)) {
            $this->addFlash('notice', implode('<hr />', $messages));
        }

        $this->zone_handler->handleCitizenCountUpdate($zone, $old_cp_ok);

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
     * @param null|int|string $ext
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/dig/{ext}', name: 'beyond_desert_dig_controller')]
    public function desert_dig_api(null|int|string $ext = null): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$this->zone_handler->isZoneUnderControl( $zone ))
            return AjaxResponse::error( self::ErrorZoneBlocked );
        if ($zone->isTownZone())
            return AjaxResponse::error( self::ErrorNotDiggable );

        if ($ext === null)
            $target_citizens = [$citizen];
        elseif ($ext === 'all') {
            $target_citizens = [];
            foreach ($citizen->getValidLeadingEscorts() as $escort)
                $target_citizens[] = $escort->getCitizen();
            shuffle($target_citizens);
        } elseif (is_numeric($ext)) {
            /** @var Citizen|null $t */
            $t = $this->entity_manager->getRepository(Citizen::class)->find( (int)$ext );
            if (!$t || !$t->getEscortSettings() || !$t->getEscortSettings()->getLeader() || $t->getEscortSettings()->getLeader()->getId() !== $citizen->getId())
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
            $target_citizens = [$t];
        } else $target_citizens = [];

        $allow_redig = $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_ALLOW_REDIGS, false);

        foreach ($target_citizens as $target_citizen)
            try {
                $timer = $target_citizen->getCurrentDigTimer();
                if ($timer && (!$allow_redig || $timer->getTimestamp() > new DateTime())) {
                    if (count($target_citizens) === 1)
                        return AjaxResponse::error( self::ErrorNotDiggable );
                    else continue;
                } elseif (!$timer) $timer = (new DigTimer())->setZone( $zone )->setCitizen( $target_citizen );

                $timer->setPassive( false )->setTimestamp( new DateTime('-1sec') )->setNonAutomatic(true);
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
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/scavenge', name: 'beyond_desert_scavenge_controller')]
    public function desert_scavenge_api(GameProfilerService $gps, EventProxyService $proxyService): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$zone->getPrototype() || $zone->getPrototype()->getExplorable() || $zone->getBuryCount() > 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        //$scout = $this->inventory_handler->countSpecificItems(
        //    $this->getActiveCitizen()->getInventory(), $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy(['name' => 'vest_on_#00'])
        //) > 0;

        //if (!$this->zone_handler->isZoneUnderControl( $zone ) && !$scout)
        //    return AjaxResponse::error( self::ErrorZoneBlocked );
        if ($zone->getX() === 0 && $zone->getY() === 0)
            return AjaxResponse::error( self::ErrorNotDiggable );

        if ($zone->getActivityMarkerFor( ZoneActivityMarkerType::RuinDig, $citizen ))
            return AjaxResponse::error( self::ErrorNotDiggable );

        if ($zone->getRuinDigs() > 0) {
            if (!$this->zone_handler->isZoneUnderControl( $this->getActiveCitizen()->getZone() ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
                $this->addFlash( 'collapse', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game') );

            /*$factor = $this->zone_handler->getDigChanceFactor( $this->getActiveCitizen(), $zone );

            if ($zone->getPrototype()->getEmptyDropChance() >= 1) $total_dig_chance = 0;
            elseif ($zone->getPrototype()->getEmptyDropChance() <= 0) $total_dig_chance = 1;
            else $total_dig_chance = min(max(0.1, $factor * (1.0 - $zone->getPrototype()->getEmptyDropChance())), 0.95);*/

			$total_dig_chance = $proxyService->citizenQueryDigChance( $citizen, $zone, ScavengingActionType::Scavenge, $this->getTownConf()->isNightMode() );
            $item_found = $this->random_generator->chance($total_dig_chance);

            $zone->addActivityMarker( (new ZoneActivityMarker())
                                          ->setCitizen( $citizen )
                                          ->setType( ZoneActivityMarkerType::RuinDig )
                                          ->setTimestamp( new DateTime() )
            );

            if ($item_found) {
                $zone->setRuinDigs( $zone->getRuinDigs() - 1 );

                $event_conf = null; $event_confs = [];
                foreach ($this->conf->getCurrentEvents($zone->getTown()) as $ev)
                    foreach ($ev->get(EventConf::EVENT_DIG_RUINS, []) as $e)
                        if ($e['name'] === $zone->getPrototype()->getIcon())
                            $event_confs[] = $e;

                if (!empty($event_confs)) $event_conf = $this->random_generator->pick( $event_confs );

                $named_groups = $this->getTownConf()->get( TownConf::CONF_OVERRIDE_NAMED_DROPS, [] );
                $group = $event_conf
                    ? ( $this->random_generator->chance($event_conf['chance'])
                        ? $this->entity_manager->getRepository(ItemGroup::class)->findOneBy(['name' => $event_conf['group']])
                        : $zone->getPrototype()->getDropByNames( $named_groups ) )
                    : $zone->getPrototype()->getDropByNames( $named_groups );

                $prototype = $group ? $this->random_generator->pickItemPrototypeFromGroup( $group, $this->getTownConf(), $this->conf->getCurrentEvents( $zone->getTown() ) ) : null;
                $gps->recordDigResult($prototype, $citizen, $zone->getPrototype(), 'ruin_scavenge', $event_conf && $group->getName() == $event_conf['group']);
                if ($prototype) {
                    $item = $this->item_factory->createItem( $prototype );
                    $gps->recordItemFound( $prototype, $citizen, $zone->getPrototype() );
                    $noPlaceLeftMsg = "";
                    if ($item) {
                        $inventoryDest = $proxyService->placeItem( $citizen, $item, [ $citizen->getInventory(), $zone->getFloor() ] );
                        if($inventoryDest === $zone->getFloor()){
                            $this->entity_manager->persist($this->log->beyondItemLog($citizen, $item->getPrototype(), true));
                            $noPlaceLeftMsg = "<hr />" . $this->translator->trans('Der Gegenstand, den du soeben gefunden hast, passt nicht in deinen Rucksack, darum bleibt er erstmal am Boden...', [], 'game');
                        }
                        $this->entity_manager->persist( $item );
                        $this->entity_manager->persist( $citizen->getInventory() );
                        $this->entity_manager->persist( $zone->getFloor() );
                    }

                    // If we get a Chest XL, we earn a picto
                    if ($prototype->getName() == 'chest_xl_#00') {
                        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_chstxl_#00"]);
                        $this->picto_handler->give_picto($citizen, $pictoPrototype);
                    }

                    $distance = round(sqrt(pow($zone->getX(),2) + pow($zone->getY(),2)));
                    $pictoName = "";
                    if($distance >= 6 && $distance <= 17) {
                        $pictoName = "r_explor_#00";
                    } else if($distance >= 18) {
                        $pictoName = "r_explo2_#00";
                    }
                    if($pictoName != ""){
                        $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $pictoName]);
                        $this->picto_handler->give_picto($citizen, $picto);
                    }
                    $this->addFlash( 'notice', $this->translator->trans( 'Als du folgendes Gebäude: {building} erkundest hast, lief es eiskalt den Rücken runter... Aber es war nicht umsonst! Du hast folgenden Gegenstand gefunden: {item}.', [
                        '{item}' => "<span class='tool'><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $prototype->getIcon() . '.gif' )}'> {$this->translator->trans($prototype->getLabel(), [], 'items')}</span>",
                        "{building}" => "<strong>" . $this->translator->trans($zone->getPrototype()->getLabel(), [], "game") . "</strong>"
                    ], 'game' ) . "$noPlaceLeftMsg");
                } else {
                    // Should not happen... This is an error case.
                    return AjaxResponse::error(ErrorHelper::ErrorInternalError);
                }
            } else {
                // Nothing found.
                $this->addFlash( 'notice', $this->translator->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' ));
                $gps->recordDigResult(null, $citizen, $zone->getPrototype(), 'ruin_scavenge');
            }
        } else {
            $this->addFlash( 'notice', $this->translator->trans( 'Beim Durchsuchen der Ruine merkst du, dass es nichts mehr zu finden gibt. Leider...', [], 'game' ));
        }

        try {
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorInternalError );
        }

        return AjaxResponse::success();
    }

    /**
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/uncover', name: 'beyond_desert_uncover_controller')]
    public function desert_uncover_api(): Response {
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $citizen = $this->getActiveCitizen();
        $zone = $citizen->getZone();

        if (!$zone->getPrototype() || $zone->getBuryCount() <= 0 || ($zone->getX() === 0 && $zone->getY() === 0))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getAp() < 1 || $this->citizen_handler->isTired($citizen))
            return AjaxResponse::error( ErrorHelper::ErrorNoAP );

        $this->citizen_handler->setAP($citizen, true, -1);
        $zone->setBuryCount( $zone->getBuryCount() - 1 );
        $this->entity_manager->persist( $this->log->outsideUncover( $citizen ) );

        $str = [];

        if($zone->getBuryCount() > 0)
            $str[] = $this->translator->trans('Du hast einen Teil des Sektors freigelegt, aber es gibt immer noch eine beträchtliche Menge an Trümmern, die den Weg versperren...',[], 'game');
        else {
            $str[] = $this->translator->trans('Herzlichen Glückwunsch, die Zone ist vollständig freigelegt worden! Du kannst nun mit der Suche nach Gegenständen im: {ruin} beginnen!',["{ruin}" => "<span>" . $this->translator->trans($zone->getPrototype()->getLabel(), [], 'game') . "</span>"], 'game');
            $this->entity_manager->persist( $this->log->outsideUncoverComplete( $citizen ) );
        }

        $str[] = $this->translator->trans("Du hast {count} Aktionspunkt(e) benutzt.", ['{count}' => "<strong>1</strong>", '{raw_count}' => 1], 'game');

        if (!$this->zone_handler->isZoneUnderControl( $this->getActiveCitizen()->getZone() ) && $this->get_escape_timeout( $this->getActiveCitizen() ) < 0 && $this->uncoverHunter($this->getActiveCitizen()))
            $str[] = $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!',[], 'game');

        if(!empty($str))
            $this->addFlash( 'notice', implode("<hr />", $str) );

        $picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_digger_#00']);
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
     * @param int $cid
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/attack_citizen/{cid<\d+>}', name: 'beyond_desert_attack_citizen_controller')]
    public function desert_attack_api(int $cid): Response {
        $citizen = $this->getActiveCitizen();
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var Citizen|null $target_citizen */
        $target_citizen = $this->entity_manager->getRepository(Citizen::class)->find( $cid );

        if (!$target_citizen || $target_citizen->getZone()->getId() !== $citizen->getZone()->getId())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($target_citizen->activeExplorerStats() || ($citizen->getZone()->getX() == 0 && $citizen->getZone()->getY() == 0) )
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        return $this->generic_attack_api( $citizen, $target_citizen );
    }

    /**
     * @param int $cid
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/devour_citizen/{cid<\d+>}', name: 'beyond_desert_devour_citizen_controller')]
    public function desert_devour_api(int $cid): Response {
        $citizen = $this->getActiveCitizen();
        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        /** @var Citizen|null $target_citizen */
        $target_citizen = $this->entity_manager->getRepository(Citizen::class)->find( $cid );

        if (!$target_citizen || $target_citizen->getZone()?->getId() !== $citizen->getZone()?->getId() || $target_citizen->getZone()?->isTownZone())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if ($target_citizen->activeExplorerStats())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        return $this->generic_devour_api( $citizen, $target_citizen );
    }

    /**
     * @param JSONRequestParser $parser
     * @param ConfMaster $conf
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/escort/self', name: 'beyond_desert_escort_self_controller')]
    public function desert_escort_self_api(JSONRequestParser $parser, ConfMaster $conf): Response {
        if (!$conf->getTownConfiguration($this->getActiveCitizen()->getTown())->get( TownConf::CONF_FEATURE_ESCORT, true ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->activeCitizenIsNotCamping()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('on')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $on = (bool)$parser->get('on');

        $cf_ruc = (bool)$parser->get('cf_ruc', false);
        $cf_ret = (bool)$parser->get('cf_ret', true);

        $citizen = $this->getActiveCitizen();

        if (!$on) {
            if ($citizen->getEscortSettings()) $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
            $this->entity_manager->persist($this->log->beyondEscortDisable($citizen));
        } elseif ($on && !$citizen->getEscortSettings()) {
            $citizen->setEscortSettings((new CitizenEscortSettings())->setCitizen($citizen));
            $this->entity_manager->persist($this->log->beyondEscortEnable($citizen));
        }

        if ($on)
            $citizen->getEscortSettings()->setAllowInventoryAccess($cf_ruc)->setForceDirectReturn($cf_ret && !$citizen->getZone()->isTownZone());

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $cid
     * @param JSONRequestParser $parser
     * @param ConfMaster $conf
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/escort/{cid<\d+>}', name: 'beyond_desert_escort_controller')]
    public function desert_escort_api(int $cid, JSONRequestParser $parser, ConfMaster $conf): Response {
        if (!$conf->getTownConfiguration($this->getActiveCitizen()->getTown())->get( TownConf::CONF_FEATURE_ESCORT, true ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$parser->has('on')) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $on = (bool)$parser->get('on');

        $citizen = $this->getActiveCitizen();

        /** @var Citizen|null $target_citizen */
        $target_citizen = $this->entity_manager->getRepository(Citizen::class)->find( $cid );

        if (!$target_citizen || $target_citizen->getZone() === null || $target_citizen->getZone()->getId() !== $citizen->getZone()->getId())
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (!$citizen->getProfession()->getHeroic() && !$citizen->hasRole('guide'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getBanished())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($on && !$citizen->getUser()->getExpert())
            return AjaxResponse::errorMessage( $this->translator->trans( 'Du kannst diese Aktion im Lernmodus nicht ausführen. <strong>Zuerst musst du noch etwas Spielerfahrung sammeln</strong>.<hr/>Klicke auf den Link bei Lernmodus, um diesen Modus zu deaktivieren.', [], 'game' ) );

        $max_escort_size = $conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_FEATURE_ESCORT_SIZE, 4);

        if ($on && $citizen->getLeadingEscorts()->count() >= $max_escort_size)
            return AjaxResponse::error( self::ErrorEscortLimitHit );

        if (!$target_citizen->getEscortSettings() ||
            ($on && $target_citizen->getEscortSettings()->getLeader() !== null) || (!$on && ($target_citizen->getEscortSettings()->getLeader() === null || $target_citizen->getEscortSettings()->getLeader()->getId() !== $citizen->getId())))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($citizen->getEscortSettings()) {
            $this->entity_manager->remove($citizen->getEscortSettings());
            $citizen->setEscortSettings(null);
        }

        if($on){
            $this->entity_manager->persist($this->log->beyondEscortTakeCitizen($citizen, $target_citizen));
        } else {
            $this->entity_manager->persist($this->log->beyondEscortReleaseCitizen($citizen, $target_citizen));
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
     * @param ConfMaster $conf
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/escort/all', name: 'beyond_desert_escort_drop_controller')]
    public function desert_escort_api_drop_all(ConfMaster $conf): Response {
        if (!$conf->getTownConfiguration($this->getActiveCitizen()->getTown())->get( TownConf::CONF_FEATURE_ESCORT, true ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->activeCitizenCanAct()) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $citizen = $this->getActiveCitizen();

        foreach ($citizen->getLeadingEscorts() as $escort) {
            $this->entity_manager->persist($this->log->beyondEscortReleaseCitizen($citizen, $escort->getCitizen()));
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

    /**
     * @param JSONRequestParser $json
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/rain', name: 'beyond_desert_shaman_rain')]
    public function desert_shaman_rain(JSONRequestParser $json): Response {
        if (!$this->activeCitizenCanAct())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        $citizen = $this->getActiveCitizen();

        $mode = $json->get('mode', null, ['role','job']);
        if (!$mode) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        $zone = $citizen->getZone();

        // Forbidden if not outside
        if($zone == null)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($mode === 'role') {

            if (!$citizen->hasRole('shaman'))
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if ($citizen->getPM() < 3)
                return AjaxResponse::error( ErrorHelper::ErrorNoMP );


        } elseif ($mode === 'job') {

            if ($citizen->getProfession()->getName() !== "shaman")
                return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

            if( $citizen->getAp() < 1 )
                return AjaxResponse::error( ErrorHelper::ErrorNoAP );
            else if ( $this->inventory_handler->countSpecificItems($citizen->getInventory(), 'soul_blue_#00') <= 0 ) {
                $this->addFlash('error', $this->translator->trans('Ohne die benötigten Elemente kannst du diesen Zauber nicht aussprechen.', [], 'game'));
                return AjaxResponse::success();
            }

        } else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (!$zone->getActivityMarkersFor( ZoneActivityMarkerType::ShamanRain )->isEmpty()) {
            $this->addFlash('error', $this->translator->trans('Diese Aktion auszuführen ist sinnlos. Heute wurde schon versucht, diese Zone zu reinigen.', [], 'game'));
            return AjaxResponse::success();
        }

        $str = [];
        $str[] = $this->translator->trans('Du vollführst einen Schamanentanz und betest zum Himmel, dass er Regen bringen und diese unselige Zone reinigen möge.', [], 'game');

        $success = $this->random_generator->chance(0.75); //same than Hordes

        if (!$success) {
            $str[] = $this->translator->trans('Doch nichts passiert... Soviel steht fest, du stehst nun wie ein lausiger Amateur da. Außer Blasen an den Füßen hat das alles nichts eingebracht...', [], 'game');
            $this->entity_manager->persist($this->log->failureShaman($citizen));
        } else {
            if (!$zone->isTownZone()) {
                $nbKills = min(mt_rand(3, 6), $zone->getZombies());
                $this->entity_manager->persist($this->log->zombieKillShaman($citizen, $nbKills));
                $zone->setZombies($citizen->getZone()->getZombies() - $nbKills);
                $this->entity_manager->persist($zone);
                $str[] = $this->translator->trans('Und die Energie, die in diesen Tanz gesteckt wurde, zahlt sich schließlich aus, die ersten Tropfen fallen auf die Zombies und du genießt diesen delikaten Moment, in dem ihr Fleisch wie Schnee in der Sonne schmilzt und du geduldig wartest, bis sich ihre Körper verflüssigen.', [], 'game');
            } else {
                $str[] = $this->translator->trans('Ob durch deine Kräfte oder unverschämtes Wetterglück, dicke Regentropfen fallen auf die Stadt herab und füllen ein wenig den Brunnen auf.', [], 'game');
                $town = $citizen->getTown();
                $town->setWell($town->getWell() + 5);
                $this->entity_manager->persist($town);
                $this->entity_manager->persist($this->log->wellAddShaman($citizen, 5));
            }
        }

        if ($mode === 'role')
            $citizen->setPM($citizen->getPM() - 3);

        elseif ($mode === 'job') {

            $citizen->setAp($citizen->getAp() - 1);
            $soul = $this->inventory_handler->fetchSpecificItems($citizen->getInventory(), [new ItemRequest("soul_blue_#00")]);
            if (!empty($soul)) $this->inventory_handler->forceRemoveItem(array_pop($soul));

        } else return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        //try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $zone->addActivityMarker( (new ZoneActivityMarker())
                ->setType( ZoneActivityMarkerType::ShamanRain )
                ->setCitizen( $citizen )
                ->setTimestamp( new DateTime() )
            ) );
            $this->entity_manager->flush();
        //} catch (\Throwable $t) {
        //    return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        //}

        $this->addFlash('notice', implode("<hr />", $str));

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/beyond/desert/zone_marker', name: 'beyond_desert_change_zone_marker')]
    public function beyond_change_zone_marker(JSONRequestParser $parser): Response {
        $tagRef = $parser->get('tag', null);
        if ($tagRef < 0 || !is_numeric($tagRef) )
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $zone = $this->getActiveCitizen()->getZone();

        if(!$zone){
            return AjaxResponse::error(ErrorHelper::ErrorActionNotAvailable);
        }

        $tag = $this->entity_manager->getRepository(ZoneTag::class)->findOneBy(['ref' => $tagRef]);

        if(!$tag){
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $zone->setTag($tag);

        try {
            $this->entity_manager->persist($zone);
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }
}
