<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\TownClass;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/rest/v1/town-creator", name="rest_town_creator_", condition="request.headers.get('Accept') === 'application/json'")
 * @IsGranted("ROLE_USER")
 * @GateKeeperProfile("skip")
 */
class TownCreatorController extends CustomAbstractCoreController
{

    /**
     * @Route("", name="base", methods={"GET"})
     * @return JsonResponse
     */
    public function index(): JsonResponse {

        return new JsonResponse([
            'strings' => [
                'common' => [
                    'need_selection' => "[ {$this->translator->trans('Bitte auswählen', [], 'global')} ]",
                ],

                'head' => [
                    'section' => $this->translator->trans('Grundlegende Einstellungen', [], 'ghost'),

                    'town_name' => $this->translator->trans('Name der Stadt', [], 'ghost'),
                    'town_name_hint' => $this->translator->trans('Leer lassen, um Stadtnamen automatisch zu generieren.', [], 'ghost'),

                    'lang' => $this->translator->trans('Sprache', [], 'global'),
                    'langs' => array_merge(
                        array_map(fn($lang) => ['code' => $lang['code'], 'label' => $this->translator->trans( $lang['label'], [], 'global' )], $this->generatedLangs),
                        [ [ 'code' => 'multi', 'label' => $this->translator->trans('Mehrsprachig', [], 'global') ] ]
                    ),

                    'citizens' => $this->translator->trans('Einwohnerzahl', [], 'ghost'),
                    'citizens_help' => $this->translator->trans('Muss zwischen 10 und 80 liegen.', [], 'ghost'),

                    'seed' => $this->translator->trans('Karten-Seed', [], 'ghost'),
                    'seed_help' => $this->translator->trans('Seed, das eine kontrollierte Kartenerstellung ermöglicht. Erlaubt im Falle von Ereignissen, ähnliche Karten zu generieren. Gib eine positive ganze Zahl ein, um es zu aktivieren. DENKT DARÜBER NACH, DIESE GANZE ZAHL VON EINEM EREIGNIS ZUM ANDEREN ZU ÄNDERN!', [], 'ghost'),

                    'type' => $this->translator->trans('Stadttyp', [], 'ghost'),
                    'base' => $this->translator->trans('Vorlage', [], 'ghost'),

                    'settings' => [
                        'section' => $this->translator->trans('Einstellungen', [], 'ghost'),

                        'disable_api' => $this->translator->trans('Externe APIs deaktivieren', [], 'ghost'),
                        'disable_api_help' => $this->translator->trans('Externe Anwendungen für diese Stadt deaktivieren.', [], 'ghost'),

                        'alias' => $this->translator->trans('Bürger-Aliase', [], 'ghost'),
                        'alias_help' => $this->translator->trans('Ermöglicht dem Bürger, einen Alias anstelle seines üblichen Benutzernamens zu wählen.', [], 'ghost'),

                        'ffa' => $this->translator->trans('Seelenpunkt-Beschränkung deaktivieren', [], 'ghost'),
                        'ffa_help' => $this->translator->trans('Jeder Spieler kann dieser Stadt beitreten, unabhängig davon wie viele Seelenpunkte er oder sie bereits erworben hat.', [], 'ghost'),
                    ]
                ],

                'difficulty' => [
                    'section' => $this->translator->trans('Schwierigkeitslevel', [], 'ghost'),

                    'well' => $this->translator->trans('Wasservorrat', [], 'ghost'),
                    'well_help' => $this->translator->trans('Normale Werte liegen zwischen 100 und 180. Wert ist in Pandämonium-Städten reduziert. Maximum ist 300', [], 'ghost'),
                    'well_presets' => [
                        ['value' => 'normal', 'label' => $this->translator->trans('Normal', [], 'ghost')],
                        ['value' => 'low', 'label' => $this->translator->trans('Gering', [], 'ghost')],
                        ['value' => '_fixed', 'label' => $this->translator->trans('Eigener Wert', [], 'ghost')],
                        ['value' => '_range', 'label' => $this->translator->trans('Eigener Bereich', [], 'ghost')],
                    ],

                    'map' => $this->translator->trans('Kartengröße', [], 'ghost'),
                    'map_presets' => [
                        ['value' => 'small', 'label' => $this->translator->trans('Kleine Karte', [], 'ghost')],
                        ['value' => 'normal', 'label' => $this->translator->trans('Normale Karte', [], 'ghost')],
                        ['value' => 'large', 'label' => $this->translator->trans('Riesige Karte', [], 'ghost')],
                        ['value' => '_custom', 'label' => $this->translator->trans('Eigene Einstellung', [], 'ghost')]
                    ],
                    'map_exact' => $this->translator->trans('Exakte Kartengröße', [], 'ghost'),
                    'map_ruins' => $this->translator->trans('Anzahl Ruinen', [], 'ghost'),
                    'map_e_ruins' => $this->translator->trans('Anzahl Begehbare Ruinen', [], 'ghost'),

                    'attacks' => $this->translator->trans('Stärke der Angriffe', [], 'ghost'),
                    'attacks_presets' => [
                        ['value' => 'easy',   'label' => $this->translator->trans('Leichte Angriffe', [], 'ghost')],
                        ['value' => 'normal', 'label' => $this->translator->trans('Normal', [], 'ghost')],
                        ['value' => 'hard',   'label' => $this->translator->trans('Schwere Angriffe', [], 'ghost')]
                    ],

                    'position' => $this->translator->trans('Position der Stadt', [], 'ghost'),
                    'position_presets' => [
                        ['value' => 'normal',  'label' => $this->translator->trans('Normal', [], 'ghost')],
                        ['value' => 'close',   'label' => $this->translator->trans('Eher Zentral', [], 'ghost')],
                        ['value' => 'central', 'label' => $this->translator->trans('Zentral', [], 'ghost')]
                    ],
                ],

                'mods' => [
                    'section' => $this->translator->trans('Stadtmodifikationen', [], 'ghost'),

                    'ghouls' => $this->translator->trans('Ghule', [], 'ghost'),
                    'ghouls_presets' => [
                        ['value' => 'normal',      'label' => $this->translator->trans('Normal', [], 'ghost'), 'help' => $this->translator->trans('Es erscheint zwar kein Ghul um Mitternacht. Dafür können andere Ereignisse das Auftreten von Ghulen auslösen.', [], 'ghost')],
                        ['value' => 'childtown',   'label' => $this->translator->trans('Stadt der Kuscheltiere', [], 'ghost'), 'help' => $this->translator->trans('Ghule sind deaktiviert.', [], 'ghost')],
                        ['value' => 'bloodthirst', 'label' => $this->translator->trans('Blutdurst', [], 'ghost'), 'help' => $this->translator->trans('Das Auftreten von Ghulen wird nicht geändert. Allerdings kann ein Ghul seinen Hunger nur stillen, indem er einen Mitbürger verspeist.', [], 'ghost')],
                        ['value' => 'airborne',    'label' => $this->translator->trans('Aerogen', [], 'ghost'), 'help' => $this->translator->trans('Je nach gewähltem Stadttyp erscheinen Ghule nach den üblichen Regeln.', [], 'ghost')],
                        ['value' => 'airbnb',      'label' => $this->translator->trans('Blutdurst und Aerogen', [], 'ghost'), 'help' => $this->translator->trans('Je nach gewähltem Stadttyp erscheinen Ghule nach den üblichen Regeln. Außerdem kann ein Ghul seinen Hunger nur stillen, indem er einen Mitbürger verspeist.', [], 'ghost')],
                    ],

                    'shamans' => $this->translator->trans('Seelen & Schamane', [], 'ghost'),
                    'shamans_presets' => [
                        ['value' => 'normal', 'label' => $this->translator->trans('Normal', [], 'ghost'), 'help' => $this->translator->trans('Der Schamane wird gewählt und die Seelen werden gequält.', [], 'ghost')],
                        ['value' => 'job',    'label' => $this->translator->trans('Job', [], 'ghost'), 'help' => $this->translator->trans('Der Schamane ist ein Beruf, die Seelen werden in der Werkstatt verwandelt.', [], 'ghost')],
                        ['value' => 'both',   'label' => $this->translator->trans('Kombiniert', [], 'ghost'), 'help' => $this->translator->trans('Beide Schamanen-Modi sind gleichzeitig aktiviert (experimentell!).', [], 'ghost')],
                        ['value' => 'none',   'label' => $this->translator->trans('Deaktiviert', [], 'ghost'), 'help' => $this->translator->trans('Der Schamane und die Seelen werden deaktiviert.', [], 'ghost')],
                    ],
                    'shaman_buildings' => [
                        'normal' => ['small_spa4souls_#00'],
                        'job' => ['small_vaudoudoll_#00','small_bokorsword_#00','small_spiritmirage_#00','small_holyrain_#00'],
                    ],

                    'watch' => $this->translator->trans('Nachtwache', [], 'ghost'),
                    'watch_presets' => [
                        ['value' => 'normal',  'label' => $this->translator->trans('Normal', [], 'ghost'), 'help' => $this->translator->trans('Die Nachtwache ist verfügbar, wenn das Gebäude "Brustwehr" errichtet wurde.', [], 'ghost')],
                        ['value' => 'instant', 'label' => $this->translator->trans('Sofort', [], 'ghost'), 'help' => $this->translator->trans('Die Nachtwache ist von Anfang an verfügbar.', [], 'ghost')],
                        ['value' => 'none',    'label' => $this->translator->trans('Deaktiviert', [], 'ghost'), 'help' => $this->translator->trans('Die Nachtwache ist deaktiviert.', [], 'ghost')],
                    ],
                    'watch_buildings' => [ 'small_round_path_#00' ],

                    'nightmode' => $this->translator->trans('Nachtmodus', [], 'ghost'),
                    'nightmode_presets' => [
                        ['value' => 'myhordes', 'label' => $this->translator->trans('Erweitert', [], 'ghost'), 'help' => $this->translator->trans('Der Nachtmodus ist aktiviert und die Straßenbeleuchtung ist verfügbar.', [], 'ghost')],
                        ['value' => 'hordes',   'label' => $this->translator->trans('Normal', [], 'ghost'), 'help' => $this->translator->trans('Der Nachtmodus ist aktiviert. Die Straßenbeleuchtung steht nicht zur Verfügung.', [], 'ghost')],
                        ['value' => 'none',     'label' => $this->translator->trans('Deaktiviert', [], 'ghost'), 'help' => $this->translator->trans('Der Nachtmodus ist deaktiviert.', [], 'ghost')],
                    ],
                    'nightmode_buildings' => [ 'small_novlamps_#00' ],

                    'timezone' => $this->translator->trans('Zeitzone', [], 'ghost'),
                    'timezone_presets' => [
                        ['value' => 'day',   'label' => $this->translator->trans('Tagesphase', [], 'ghost'), 'help' => $this->translator->trans('Die eingestellte Zeit gibt die Dauer des Tages an.', [], 'ghost') . ' ' . $this->translator->trans('Ist der Nachtmodus deaktiviert, hat diese Einstellung ausschließlich kosmetischen Einfluss.', [], 'ghost')],
                        ['value' => 'night', 'label' => $this->translator->trans('Nachtphase', [], 'ghost'), 'help' => $this->translator->trans('Die eingestellte Zeit gibt die Dauer der Nacht an.', [], 'ghost') . ' ' . $this->translator->trans('Ist der Nachtmodus deaktiviert, hat diese Einstellung ausschließlich kosmetischen Einfluss.', [], 'ghost')],
                    ],

                    'modules' => [
                        'section' => $this->translator->trans('Spielmodifikationen', [], 'ghost'),

                        'e_ruins' => $this->translator->trans('Exploration', [], 'ghost'),
                        'e_ruins_help' => $this->translator->trans('Begehbare Ruinen aktivieren', [], 'ghost'),

                        'escorts' => $this->translator->trans('Eskorte', [], 'ghost'),
                        'escorts_help' => $this->translator->trans('Eskorten aktivieren', [], 'ghost'),

                        'shun' => $this->translator->trans('Verbannung', [], 'ghost'),
                        'shun_help' => $this->translator->trans('Verbannung aktivieren', [], 'ghost'),

                        'camp' => $this->translator->trans('Camping', [], 'ghost'),
                        'camp_help' => $this->translator->trans('Camping in der Wildnis aktivieren', [], 'ghost'),

                        'buildingdamages' => $this->translator->trans('Gebäudeschaden', [], 'ghost'),
                        'buildingdamages_help' => $this->translator->trans('Gebäudeschaden aktivieren', [], 'ghost'),

                        'improveddump' => $this->translator->trans('Verbesserte Müllhalde', [], 'ghost'),
                        'improveddump_help' => $this->translator->trans('Verbesserte Müllhalde aktivieren', [], 'ghost'),
                        'improveddump_buildings' => ['small_trash_#01', 'small_trash_#02', 'small_trash_#03', 'small_trash_#04', 'small_trash_#05', 'small_trash_#06', 'small_howlingbait_#00', 'small_trashclean_#00' ]
                    ],

                    'special' => [
                        'section' => $this->translator->trans('Spezialregeln', [], 'ghost'),

                        'nobuilding' => $this->translator->trans('Keine Startgebäude', [], 'ghost'),
                        'nobuilding_help' => $this->translator->trans('Kein Gebäude ist zu Beginn freigeschaltet. Alle müssen in der Aussenwelt gefunden werden!', [], 'ghost'),

                        'poison' => $this->translator->trans('Kontaminierte Zone', [], 'ghost'),
                        'poison_help' => $this->translator->trans('Jedes Essen und jede Droge, die du zu dir nimmst, birgt ein geringes Infektionsrisiko.', [], 'ghost'),

                        'beta' => $this->translator->trans('Beta-Funktionen aktivieren', [], 'ghost'),
                        'beta_help' => $this->translator->trans('Jeder Spieler erhält 1x Betapropin beim Start der Stadt.', [], 'ghost'),
                        'beta_items' => ['beta_drug_#00'],

                        'with-toxin' => $this->translator->trans('Toxin aktivieren', [], 'ghost'),
                        'with-toxin_help' => $this->translator->trans('Ermöglicht es, in begehbaren Ruinen den Gegenstand "Blutdurchtränkter Verband" zu finden, aus dem Toxin hergestellt werden kann.', [], 'ghost'),

                        'hungry-ghouls' => $this->translator->trans('Hungrige Ghule', [], 'ghost'),
                        'hungry-ghouls_help' => $this->translator->trans('Ist diese Option aktiviert, haben frisch in Ghule verwandelte Bürger bereits Hunger.', [], 'ghost'),
                    ]
                ],

                'animation' => [
                    'section' => $this->translator->trans('Raben-Optionen', [], 'ghost'),

                    'pictos' => $this->translator->trans('Vergabe von Auszeichnungen', [], 'ghost'),
                    'pictos_presets' => [
                        ['value' => 'all',     'label' => $this->translator->trans('Alle', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten alle Auszeichnungen, die sie in der Stadt verdient haben.', [], 'ghost')],
                        ['value' => 'reduced', 'label' => $this->translator->trans('Reduziert', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten ein Drittel der Auszeichnungen, die sie in der Stadt verdient haben.', [], 'ghost')],
                    ],

                    'sp' => $this->translator->trans('Vergabe von Seelenpunkten', [], 'ghost'),
                    'sp_presets' => [
                        ['value' => 'all',  'label' => $this->translator->trans('Alle', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten Seelenpunkte für die Teilnahme an dieser Stadt.', [], 'ghost')],
                        ['value' => 'none', 'label' => $this->translator->trans('Keine', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten KEINE Seelenpunkte für die Teilnahme an dieser Stadt.', [], 'ghost')],
                    ],

                    'management' => [
                        'section' => $this->translator->trans('Verwaltung', [], 'ghost'),

                        'incarnate' => $this->translator->trans('', [], 'ghost'),
                        'incarnate_help' => $this->translator->trans('', [], 'ghost'),
                    ]
                ]
            ],
            'config' => [
                'elevation' => $this->getUser()->getRightsElevation(),
                'default_lang' => $this->getUserLanguage()
            ]

        ]);
    }


    /**
     * @Route("/town-types", name="town-types", methods={"GET"})
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function town_types(EntityManagerInterface $em): JsonResponse {

        $towns = $em->getRepository(TownClass::class)->findAll();

        return new JsonResponse(array_map(
            function(TownClass $town) {

                return [
                    'id' => $town->getId(),
                    'preset' => $town->getHasPreset(),
                    'name' => $this->translator->trans( $town->getLabel(), [], 'game' ),
                    'help' => $this->translator->trans( $town->getHelp(), [], 'game' ),
                ];

            }, $em->getRepository(TownClass::class)->findAll() )
        );
    }

    protected function sanitize_outgoing_config(array $conf): array {
        static $unset_props = [
            'well',
            'map', 'ruins'
        ];

        foreach ($unset_props as $prop) unset ($conf[$prop]);
        return $conf;
    }

    /**
     * @Route("/town-rules/{id}", name="town-rules", methods={"GET"}, defaults={"private"=false})
     * @Route("/town-rules/private/{id}", name="private-town-rules", methods={"GET"}, defaults={"private"=true})
     * @param TownClass $townClass
     * @param bool $private
     * @return JsonResponse
     */
    public function town_type_rules(TownClass $townClass, bool $private): JsonResponse {
        if ($townClass->getHasPreset()) {

            $preset = $this->conf->getTownConfigurationByType($townClass, $private)->getData();

            $preset['wellPreset'] = $townClass->getName() === TownClass::HARD ? 'low' : 'normal';
            $preset['mapPreset']  = $townClass->getName() === TownClass::EASY ? 'small' : 'normal';

            return new JsonResponse( $this->sanitize_outgoing_config( $preset ) );
        }

        return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

}
