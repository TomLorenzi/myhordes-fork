<?php

namespace App\Controller;

use App\Entity\RuinZone;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\CrowService;
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
use App\Service\UserHandler;
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
class ExplorationController extends InventoryAwareController implements ExplorationInterfaceController, HookedInterfaceController
{
    protected $game_factory;
    protected ZoneHandler $zone_handler;
    protected $item_factory;
    protected DeathHandler $death_handler;
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
     *
     */
    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, TimeKeeperService $tk,
        DeathHandler $dh, PictoHandler $ph, TranslatorInterface $translator, GameFactory $gf, RandomGenerator $rg,
        ItemFactory $if, ZoneHandler $zh, LogTemplateHandler $lh, ConfMaster $conf, Packages $a, UserHandler $uh,
        CrowService $armbrust, TownHandler $th)
    {
        parent::__construct($em, $ih, $ch, $ah, $dh, $ph, $translator, $lh, $tk, $rg, $conf, $zh, $uh, $armbrust, $th);
        $this->game_factory = $gf;
        $this->item_factory = $if;
        $this->zone_handler = $zh;
        $this->asset = $a;
    }

    public function before(): bool
    {
        if ($this->zone_handler->updateRuinZone( $this->getActiveCitizen()->activeExplorerStats() )) {
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {}
            return false;
        } else return true;
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
            // 'floor' => $ex->getInRoom() ? $ruinZone->getRoomFloor() : $ruinZone->getFloor(),
            'floor' => $ruinZone->getFloor(),
            'heroics' => $this->getHeroicActions(),
            'actions' => $this->getItemActions(),
            'recipes' => $this->getItemCombinations(false),
            'move' => $ruinZone->getZombies() <= 0 || $ex->getEscaping(),
            'escaping' => $ex->getEscaping(),
            'zone_zombies' => $ruinZone->getZombies(),
            'zone_zombies_dead' => $ruinZone->getKilledZombies(),
            'shifted' => $ex->getInRoom(),
            'scavenge' => !$ex->getScavengedRooms()->contains($ruinZone),
            'can_imprint' => $citizen->getProfession()->getName() === 'tech',
            'ruin_map_data' => [
                'show_exit_direction' => $citizen->getProfession()->getName() === 'tamer',
                'name' => $this->generateRuinName($citizen->getZone(), 'fr'),
                'timeout' => max(0, $ex->getTimeout()->getTimestamp() - time()),
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
        $ex->setActive(false);
        $this->entity_manager->persist($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/escape", name="beyond_ruin_escape_controller")
     * @return Response
     */
    public function ruin_escape_api() {
        $ruinZone = $this->getCurrentRuinZone();
        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if ($ex->getEscaping() || $ruinZone->getZombies() <= 0)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $ex
            ->setEscaping(true)
            ->setTimeout( (new DateTime)->setTimestamp( $ex->getTimeout()->getTimestamp() )->modify( '-' . mt_rand( 15, 24) . 'sec' ) );

        $this->entity_manager->persist($ex);
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
        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if ($ruinZone->getZombies() > 0 && !$ex->getEscaping())
            return AjaxResponse::error( BeyondController::ErrorZoneBlocked );

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


        $ex
            ->setX( $ex->getX() + $dx )
            ->setY( $ex->getY() - $dy )
            ->setEscaping( false );

        // End the exploration!
        $this->entity_manager->persist($ex);
        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        $new_zone = $this->getCurrentRuinZone();
        return AjaxResponse::success(true, [
            'next' => ($new_zone->getX() !== 0 || $new_zone->getY() !== 0) ? $new_zone->getCorridor() : 'exit',
            'dp' => $new_zone->getDoorPosition(),
            'l' => $new_zone->getLocked(),
            'd' => $new_zone->getDecals(),
            'dv' => $new_zone->getDecalVariants(),
        ]);
    }

    /**
     * @Route("api/beyond/explore/shift", name="beyond_ruin_room_enter_controller")
     * @return Response
     */
    public function ruin_room_enter_api() {
        $ruinZone = $this->getCurrentRuinZone();
        $ex = $this->getActiveCitizen()->activeExplorerStats();

        if ($ruinZone->getZombies() > 0 && !$ex->getEscaping())
            return AjaxResponse::error( BeyondController::ErrorZoneBlocked );

        if (!$ruinZone->getPrototype() || $ruinZone->getLocked())
            return AjaxResponse::error( BeyondController::ErrorNotReachableFromHere );

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
        //$down_inv = $ex->getInRoom() ? $this->getCurrentRuinZone()->getRoomFloor() : $this->getCurrentRuinZone()->getFloor();
        $down_inv = $this->getCurrentRuinZone()->getFloor();
        $up_inv   = $this->getActiveCitizen()->getInventory();

        return $this->generic_item_api( $up_inv, $down_inv, true, $parser, $handler);
    }

    /**
     * @Route("api/beyond/explore/scavenge", name="beyond_ruin_scavenge_controller")
     * @param InventoryHandler $handler
     * @return Response
     */
    public function scavenge_explore_api(InventoryHandler $handler): Response {
        $citizen = $this->getActiveCitizen();
        $ex = $citizen->activeExplorerStats();
        $ruinZone = $this->getCurrentRuinZone();

        if (!$ex->getInRoom() || $ex->getScavengedRooms()->contains( $ruinZone ))
            return AjaxResponse::error( BeyondController::ErrorNotDiggable );

        $prototype = null;

        // Calculate chances
        $d = $ruinZone->getDigs() + 1;
        $chances = 1.0 / ( 1.0 + ( $d / max( 1, $this->getTownConf()->get(TownConf::CONF_EXPLORABLES_ITEM_RATE, 11) - ($d/3.0) ) ) );

        if ($this->random_generator->chance( $chances )) {
            $group = $ruinZone->getZone()->getPrototype()->getDrops();
            $prototype = $group ? $this->random_generator->pickItemPrototypeFromGroup( $group ) : null;
        }

        $ruinZone->setDigs( $ruinZone->getDigs() + 1 );

        if ($prototype) {
            $item = $this->item_factory->createItem($prototype, false, $prototype->hasProperty("found_poisoned") ? $this->random_generator->chance(0.90) : false);
            $noPlaceLeftMsg = "";
            // $inventoryDest = $this->inventory_handler->placeItem($citizen, $item, [$citizen->getInventory(), $ruinZone->getRoomFloor()]);
            $inventoryDest = $this->inventory_handler->placeItem($citizen, $item, [$citizen->getInventory(), $ruinZone->getFloor()]);
            //if ($inventoryDest === $ruinZone->getFloor())
            //    $noPlaceLeftMsg = "<hr />" . $this->translator->trans('Der Gegenstand, den du soeben gefunden hast, passt nicht in deinen Rucksack, darum bleibt er erstmal am Boden...', [], 'game');

            $this->entity_manager->persist($item);
            $this->entity_manager->persist($citizen->getInventory());
            // $this->entity_manager->persist($ruinZone->getRoomFloor());
            $this->entity_manager->persist($ruinZone->getFloor());

            $this->addFlash( 'notice', $this->translator->trans( 'Nach einigen Anstrengungen hast du folgendes gefunden: %item%!', [
                    '%item%' => "<span class='tool'><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $prototype->getIcon() . '.gif' )}'> {$this->translator->trans($prototype->getLabel(), [], 'items')}</span>"
                ], 'game' ) . "$noPlaceLeftMsg");
        } else $this->addFlash( 'notice', $this->translator->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' ));

        $ex->getScavengedRooms()->add( $ruinZone );
        $this->entity_manager->persist($ex);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/imprint", name="beyond_ruin_imprint_controller")
     * @param InventoryHandler $handler
     * @return Response
     */
    public function imprint_explore_api(InventoryHandler $handler): Response {
        $citizen = $this->getActiveCitizen();
        $ex = $citizen->activeExplorerStats();
        $ruinZone = $this->getCurrentRuinZone();

        if (!$ruinZone->getPrototype() || !$ruinZone->getLocked() || !$ruinZone->getPrototype()->getKeyImprint())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if ($ex->getInRoom() || $ex->getScavengedRooms()->contains( $ruinZone ))
            return AjaxResponse::error( BeyondController::ErrorNotDiggable );

        $item = $this->item_factory->createItem($ruinZone->getPrototype()->getKeyImprint());
        $this->inventory_handler->placeItem($citizen, $item, [$citizen->getInventory(), $ruinZone->getFloor()]);

        $this->addFlash( 'notice', $this->translator->trans( 'Du nimmst einen Abdruck vom Schloss dieser Tür und erhälst %item%!', [
                '%item%' => "<span class='tool'><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $item->getPrototype()->getIcon() . '.gif' )}'> {$this->translator->trans($item->getPrototype()->getLabel(), [], 'items')}</span>"
            ], 'game' ));

        $ex->getScavengedRooms()->add( $ruinZone );
        $this->entity_manager->persist($ex);

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
    }

    /**
     * @Route("api/beyond/explore/unlock", name="beyond_ruin_unlock_controller")
     * @param InventoryHandler $handler
     * @return Response
     */
    public function unlock_explore_api(InventoryHandler $handler): Response {
        $citizen = $this->getActiveCitizen();
        $ex = $citizen->activeExplorerStats();
        $ruinZone = $this->getCurrentRuinZone();

        if ($ex->getInRoom() || !$ruinZone->getPrototype() || !$ruinZone->getLocked() || !$ruinZone->getPrototype()->getKeyItem())
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $prototype = $ruinZone->getPrototype()->getKeyItem();
        $k_str = "<span class='tool'><img alt='' src='{$this->asset->getUrl( 'build/images/item/item_' . $prototype->getIcon() . '.gif' )}'> {$this->translator->trans($prototype->getLabel(), [], 'items')}</span>";

        $key = $this->inventory_handler->fetchSpecificItems( $citizen->getInventory(), [new ItemRequest( $ruinZone->getPrototype()->getKeyItem()->getName())] );

        if (empty($key))
            return AjaxResponse::errorMessage( $this->translator->trans( 'Du benötigst %item%, um diese Tür zu öffnen.', ['%item%' => $k_str], 'game' ) );
        else $this->inventory_handler->forceRemoveItem( $key[0] );

        $ruinZone->setLocked(false);
        $this->picto_handler->give_picto($citizen, 'r_door_#00', 1);
        $ex->getScavengedRooms()->removeElement( $ruinZone );

        $this->entity_manager->persist($ruinZone);
        $this->entity_manager->persist($citizen);
        $this->entity_manager->persist($ex);

        $this->addFlash( 'notice', $this->translator->trans( 'Mithilfe des %item% hast du die Tür aufgeschlossen!', [
            '%item%' => $k_str
        ], 'game' ));

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }

        return AjaxResponse::success();
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

    public function generateRuinName(Zone $zone, string $lang) {
        $ruinNames = [
            'fr' => [
                'deserted_hospital' => ["Hôpital Velpo", "Clinique esthétruique", "Hôpital malade", "L'Hôpital du régal", "L'hospice du vice", "L'hôpital du mal", "Clinique du couic !", "Clinique aux allergiques", "Pelle grain", "Sale pré-trier", "White et Necker", "C.H.UT", "Bordeaux grace", "Georges XXXII"],
                'deserted_hotel' => ["Hôtel charlton eston", "Le Motel enchanté", "Caesar palace", "Le palace de la place", "L'Hôtel sordide", "Hôtel terminus", "Palace pas classe", "Relais des gourmets", "Hôtel de passe", "Hôtel particulier", "Hôtel de la défenestration", "Hôtel Santa frit", "Hôtel Chez yen", "Hôtel Old port bay club", "Hôtel Sapin lodge", "Hôtel Front tenace", "Hôtel Beverly colline", "Palace Gonzalez"],
                'deserted_bunker' => ["Bunker abandonné", "Bunker thermonucléaire", "Bunker maginot", "Bunker de la peur", "Bunker de la fureur", "Abri atomique", "Pas d'abri, t'es pris !", "Blockhaus glauque", "Blockhaus abandonné", "Blockhaus plein d'os", "Blockhaus à l'os", "Centre d'expérimentation", "Bunker zone 52.1", "Bunker zone 33", "Etude des quarantaines"],
            ],
            'en' => [
                'deserted_hospital' => ["Cash Ulty Hospital", "Aesthetyxiation Unit", "Syck Niss Hospital", "Royal Troon Hospital", "The Munro Chronic STI Treatment Unit", "Bill S. Preston Memorial Hospital", "Dr Kwak's Clinic!", "The Mererid Allergy Clinic", "Pelle Grain Hospital", "Osmond St Hospital", "The Chapman Penis Reduction Clinic", "The Brunting Daily Exhaustion Center", "Bordeaux Grace", "George and Ralph Children's Hospital"],
                'deserted_hotel' => ["Charlton Eston Hotel", "The Enchanted Motel", "The Rabble Lodge", "The Unravel Inn", "The Busted Arts", "Terminal Hotel", "Hotel Von Otto", "S+M B+B", "The Passing Trade Motel", "The Hotel Peculiar", "Liza Defenestration Hotel", "The Smashed Santa Inn", "Chez Clem Hotel", "Three Door Hotel + Spa", "Hostel Partout", "The Bumbling Inn", "The Vajazzl Inn", "Hotel Venga"],
                'deserted_bunker' => ["Abandoned Bunker", "Thermonuclear Bunker", "Garrison House", "Bastion of Fear", "Bunker of Fury", "Fallout Shelter", "Nowhere to hide, even inside!", "Shady Fort", "Abandoned Troop Station", "Bone-filled Bunker", "Bone Blockhouse", "Secret Testing Center", "Area 52.1 Shelter", "Area 33 Bunker", "Quarantine Zone"],
            ],
            'de' => [
                'deserted_hospital' => ["Krankenhaus Beinapp", "Aesthetyxiations-Abteilung", "Krankenhaus Krankenstedt", "Waldorf Klinik", "STD Behandlungszentrum Bohlen", "Bill S. Preston Memorial Hospital", "Klinik von Dr. Quack Salber", "Wasserallergie-Behandlungszentrum", "Klinikum Altenburg", "Stadtkrankenhaus am Dorfplatz", "Blanko-Penisverkleinerungsklinik", "Chronische Erschöpfungsheilanstalt Dr. Sloth", "Bordeaux Grace", "George und Ralph Kinderkrankenhaus"],
                'deserted_hotel' => ["Charlton Eston Hotel", "Motel Zauberstübchen", "Rabble Lodge", "Gasthof 'Zum Spanner'", "Zur Erbrochenen Krone", "Terminal Hotel", "Hotel Von Otto", "S+M B+B", "Zm Schlafen Reichts Motel", "Hotel Peculiar", "Liza Defloration Hotel", "Santas Absteige", "Chez Clem Hotel", "Drei Eingänge Hotel + Spa", "Hostel Partout", "Bumbling Inn", "Vajazzl Inn", "Hotel Venga"],
                'deserted_bunker' => ["Verlassener Bunker", "Thermonuklear-Bunker", "Garrison-Haus", "Bastion der Angst", "Bunker der Wut", "Fallout Shelter", "Keine Hoffnung ohne Öffnung!", "Schattenfort", "Verlassene Trooper-Station", "Verwesungsversteck", "Knochenkeller", "Geheimes Testlabor", "Area 52.1 Bunker", "Area 33 Bunker", "Quarantäne-Zone"],
            ],
            'es' => [
                'deserted_hospital' => ["Hospital Matasanos", "Clínica El Serrucho", "Hospital Privado", "Hospital del Rey", "Clínica de Miércoles", "Hospital Sangriento", "Hospital Bar Discoteca", "Hospital Cementerio", "Hospital Dolores", "Hospital Milagros", "Clínica del Dr. Cuervo", "Hospital Nocturno", "Hospital del Estado", "Hospital Madre Mía"],
                'deserted_hotel' => ["Hotel California", "Hotel El Cielo II", "Death Palace Hotel", "Hostal Barato", "Hotel Maravilla", "Hotel Melody", "Hotel Paraeso", "Hostal de Paso", "Hotel Particular", "Hotel Monstruo", "Hotel Znarfo", "Hotel Transilvania", "Hotel Nirvana", "Hostal El Secreto", "Hotel El Pájaro Loco", "Hotel Gonzalez", "Hostal El Cielo I"],
                'deserted_bunker' => ["Bunker abandonado", "Bunker termonuclear", "Bunker de políticos", "Bunker del terror", "Bunker de los prófugos", "Guarida insalubre", "Refugio Fin del Mundo", "Bunker rockero", "Bunker graffitero", "Bunker lleno de huesos", "Bunker del rey", "Centro de experimentos", "Bunker zona 52.1", "Bunker zona 33", "Viejo bunker"],

            ]
        ];
        return $ruinNames[$lang][$zone->getPrototype()->getIcon()][$zone->getId() % count($ruinNames[$lang][$zone->getPrototype()->getIcon()])];
    }
}
