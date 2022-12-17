<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\TownClass;
use App\Entity\TownSlotReservation;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\TownSetup;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Asset\Packages;
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
     * @param EntityManagerInterface $em
     * @param Packages $asset
     * @return JsonResponse
     */
    public function index(EntityManagerInterface $em, Packages $asset): JsonResponse {

        $all_events = array_map(
            function(string $name) {
                $title_key = "event_{$name}_title";
                $desc_key = "event_{$name}_description";

                $title = $this->translator->trans($title_key, ['year' => ''], 'events');
                $desc  = $this->translator->trans($desc_key, ['year' => ''], 'events');

                return [
                    'id' => $name,
                    'label' => $title === $title_key ? "[ $name ]" : $title,
                    'desc'  => $desc === $desc_key ? '' : $desc,
                ];
            },
            array_filter( $this->conf->getAllEventNames(), fn(string $name) => $this->conf->eventIsPublic( $name ) || $this->getUser()->getRightsElevation() >= User::USER_LEVEL_CROW )
        );

        return new JsonResponse([
            'strings' => [
                'common' => [
                    'create' => $this->translator->trans('Diese Stadt gründen', [], 'ghost'),
                    'confirm' => $this->translator->trans('Bestätigen?', [], 'global'),
                    'help' => $this->translator->trans('Hilfe', [], 'global'),
                    'need_selection' => "[ {$this->translator->trans('Bitte auswählen', [], 'global')} ]",
                    'notice' => $this->translator->trans('Achtung!', [], 'ghost'),
                    'negate' => $this->translator->trans('Falls die Stadt night in 2 Tagen gefüllt ist, wird sie wieder negiert.', [], 'ghost'),
                ],

                'head' => [
                    'section' => $this->translator->trans('Grundlegende Einstellungen', [], 'ghost'),

                    'town_name' => $this->translator->trans('Name der Stadt', [], 'ghost'),
                    'town_name_help' => $this->translator->trans('Leer lassen, um Stadtnamen automatisch zu generieren.', [], 'ghost'),

                    'lang' => $this->translator->trans('Sprache', [], 'global'),
                    'name_lang' => $this->translator->trans('Sprache des Stadtnamens', [], 'global'),
                    'langs' => array_merge(
                        array_map(fn($lang) => ['code' => $lang['code'], 'label' => $this->translator->trans( $lang['label'], [], 'global' )], $this->generatedLangs),
                        [ [ 'code' => 'multi', 'label' => $this->translator->trans('Mehrsprachig', [], 'global') ] ]
                    ),

                    'reserve' => $this->translator->trans('Platzreservierung', [], 'ghost'),
                    'reserve_none' => $this->translator->trans('Du hast aktuell noch keine Plätze reserviert.', [], 'soul'),
                    'reserve_num' => $this->translator->trans('Reservierte Plätze:', [], 'soul'),
                    'reserve_add' => $this->translator->trans('Eine Reservierung hinzufügen', [], 'ghost'),
                    'reserve_help' => implode( '', array_map( fn(string $s) => "<p>$s</p>", [
                                                                                                $this->translator->trans('Du kannst hier festlegen, welche Spieler in deiner Privatstadt spielen dürfen. Andere Spieler können die Stadt nicht betreten! Als Stadtgründer kannst du in deiner Privatstadt immer auch selber mitspielen. Gibst du nur 39 Spieler an, wärst du der also der 40. Bürger.', [], 'ghost'),
                                                                                                $this->translator->trans('So geht\'s: Tippe einen Spielernamen in das Feld ein. Wähle ihn dann aus den angezeigten Namen aus. Klicke dann auf "Hinzufügen". Wiederhole das für alle Spieler, die du auf die Liste setzen möchtest. Alternativ kannst du auch eine mit Komma getrennte Liste von Spielernamen eingeben, um mehrere Spieler auf einmal hiinzuzufügen. Falls du ein Passwort vergeben hast, vergiss nicht, es den Spielern zu schicken (z.B. in einer privaten Nachricht).', [], 'ghost'),
                                                                                                $this->translator->trans('<strong>Hinweis:</strong> Beachte, dass sich diese Liste nicht nachträglich ändern lässt. Es ist darum empfehlenwert, <strong>mehr als nur 39 Spieler</strong> anzugeben. Dann hast du eine Reserve, falls ein oder mehrere Spieler nicht teilnehmen können.', [], 'ghost'),
                                                                                                $this->translator->trans('<strong>Hinweis:</strong> Wenn du <strong>weniger als 39 Spieler</strong> angibst, wird die Stadt automatisch für alle Spieler geöffnet, sobald alle Spieler auf der Liste die Stadt betreten haben.', [], 'ghost'),
                                                                                                $this->translator->trans('<strong>Falls bis Mitternacht des übernächsten Tags nicht 40 Spieler die Stadt betreten haben, wird sie automatisch negiert. Alle Spieler, die sich dann bereits in der Stadt eingefunden haben, sind dann wieder frei für andere Städte.</strong>', [], 'ghost'),
                                                                                            ] ) ),

                    'code' => $this->translator->trans('Zugangscode', [], 'ghost'),
                    'code_help' => $this->translator->trans('Wenn Bürger deine Stadt betreten möchten, wird ein Zugangsdode abgefragt. Nur mit korrektem Zugangscode erhalten sie Zutritt. Verteile den Zugangscode darum an die Spieler, die du einladen möchtest. <strong>Und benutze nicht dein privates Passwort als Zugangscode!!!</strong>', [], 'global'),

                    'citizens' => $this->translator->trans('Einwohnerzahl', [], 'ghost'),
                    'citizens_help' => $this->translator->trans('Muss zwischen 10 und 80 liegen.', [], 'ghost'),

                    'seed' => $this->translator->trans('Karten-Seed', [], 'ghost'),
                    'seed_help' => $this->translator->trans('Seed, das eine kontrollierte Kartenerstellung ermöglicht. Erlaubt im Falle von Ereignissen, ähnliche Karten zu generieren. Gib eine positive ganze Zahl ein, um es zu aktivieren. DENKT DARÜBER NACH, DIESE GANZE ZAHL VON EINEM EREIGNIS ZUM ANDEREN ZU ÄNDERN!', [], 'ghost'),

                    'type' => $this->translator->trans('Stadttyp', [], 'ghost'),
                    'base' => $this->translator->trans('Vorlage', [], 'ghost'),
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

                        'alias' => $this->translator->trans('Bürger-Aliase', [], 'ghost'),
                        'alias_help' => $this->translator->trans('Ermöglicht dem Bürger, einen Alias anstelle seines üblichen Benutzernamens zu wählen.', [], 'ghost'),

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
                        'improveddump_buildings' => ['small_trash_#01', 'small_trash_#02', 'small_trash_#03', 'small_trash_#04', 'small_trash_#05', 'small_trash_#06', 'small_howlingbait_#00', 'small_trashclean_#00' ],

                        'api' => $this->translator->trans('Externe APIs', [], 'ghost'),
                        'api_help' => $this->translator->trans('Externe Anwendungen für diese Stadt aktivieren.', [], 'ghost'),

                        'ffa' => $this->translator->trans('Seelenpunkt-Beschränkung deaktivieren', [], 'ghost'),
                        'ffa_help' => $this->translator->trans('Jeder Spieler kann dieser Stadt beitreten, unabhängig davon wie viele Seelenpunkte er oder sie bereits erworben hat.', [], 'ghost'),
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

                        'super_poison' => $this->translator->trans('Paradies der Giftmörder', [], 'ghost'),
                        'super_poison_help' => $this->translator->trans('Verändert das Verhalten im Bezug auf vergiftete Gegenstände und erschwert deren Erkennung.', [], 'ghost'),

                        'redig' => $this->translator->trans('Erneutes Buddeln', [], 'ghost'),
                        'redig_help' => $this->translator->trans('Ermöglicht es, auf bereits besuchten Zonen erneut zu buddeln.', [], 'ghost'),

                        'carry_bag' => $this->translator->trans('Matroschka-Taschen', [], 'ghost'),
                        'carry_bag_help' => $this->translator->trans('Spieler können mehrere Rucksackerweiterungen gleichzeitig tragen. Es wird jedoch kein zusätzlicher Platz im Rucksack freigeschaltet.', [], 'ghost'),
                    ]
                ],

                'animation' => [
                    'section' => $this->translator->trans('Raben-Optionen', [], 'ghost'),

                    'schedule' => $this->translator->trans('Stadtstart planen', [], 'ghost'),
                    'schedule_help' => $this->translator->trans('Wenn du die Stadt erst zu einem zukünftigen Zeitpunkt eröffnen möchtest, kannst du diesen hier eingeben (in Serverzeit). Erst wenn dieser Zeitpunkt erreicht ist, wird die Stadt zur Verfügung stehen. WICHTIG: Die Option "Verkörperung in der Stadt" schließt einen geplanten Stadtstart aus!', [], 'ghost'),

                    'pictos' => $this->translator->trans('Vergabe von Auszeichnungen', [], 'ghost'),
                    'pictos_presets' => [
                        ['value' => 'all',     'label' => $this->translator->trans('Alle', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten alle Auszeichnungen, die sie in der Stadt verdient haben.', [], 'ghost')],
                        ['value' => 'reduced', 'label' => $this->translator->trans('Reduziert', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten ein Drittel der Auszeichnungen, die sie in der Stadt verdient haben.', [], 'ghost')],
                        ['value' => 'none',    'label' => $this->translator->trans('Deaktiviert', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten keine Auszeichnungen für diese Stadt.', [], 'ghost')],
                    ],

                    'picto_rules' => $this->translator->trans('Auszeichnungen beschränken', [], 'ghost'),
                    'picto_rules_presets' => [
                        ['value' => 'normal', 'label' => $this->translator->trans('Keine Beschränkung', [], 'ghost'), 'help' => $this->translator->trans('Vergabe von Auszeichnungen erfolgt nach normalen Regeln.', [], 'ghost')],
                        ['value' => 'small',  'label' => $this->translator->trans('Strikte Vergabe', [], 'ghost'), 'help' => $this->translator->trans('Vergabe von Auszeichnungen folgt den Regeln kleiner Städte.', [], 'ghost')],
                    ],

                    'sp' => $this->translator->trans('Vergabe von Seelenpunkten', [], 'ghost'),
                    'sp_presets' => [
                        ['value' => 'all',  'label' => $this->translator->trans('Alle', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten Seelenpunkte für die Teilnahme an dieser Stadt.', [], 'ghost')],
                        ['value' => 'none', 'label' => $this->translator->trans('Keine', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten KEINE Seelenpunkte für die Teilnahme an dieser Stadt.', [], 'ghost')],
                    ],

                    'participation' => $this->translator->trans('Teilnahme', [], 'ghost'),
                    'participation_presets' => [
                        ['value' => 'incarnate', 'label' => $this->translator->trans('Verkörperung in der Stadt', [], 'ghost'), 'help' => $this->translator->trans('Verkörpert dich in der Stadt bei ihrer Entstehung.', [], 'ghost')],
                        ['value' => 'none',      'label' => $this->translator->trans('Keine', [], 'ghost'), 'help' => $this->translator->trans('Du wirst weder verkörpert, noch erhälst du Zugang zum Stadtforum.', [], 'ghost')],
                    ],

                    'management' => [
                        'section' => $this->translator->trans('Verwaltung', [], 'ghost'),

                        'event_tag' => $this->translator->trans('Als Event-Stadt markieren', [], 'ghost'),
                        'event_tag_help' => $this->translator->trans('Event-Städte werden nicht ins Ranking aufgenommen und erhalten eine spezielle Markierung in der Stadtliste.', [], 'ghost'),

                        'negate' => $this->translator->trans('Nach 2 Tagen negieren', [], 'ghost'),
                        'negate_help' => $this->translator->trans('Negiert die Stadt, wenn sie nach 2 Tagen nicht gefüllt ist.', [], 'ghost'),

                        'lock_door' => $this->translator->trans('Tor versperren', [], 'ghost'),
                        'lock_door_help' => $this->translator->trans('Das Stadttor kann erst geöffnet werden, wenn die Stadt voll ist.', [], 'ghost'),
                    ],
                ],

                'advanced' => [
                    'section' => $this->translator->trans('Erweiterte Einstellungen', [], 'ghost'),
                    'show_section' => $this->translator->trans('Individuelle Konfiguration', [], 'ghost'),

                    'jobs'  => $this->translator->trans('Verfügbare Berufe', [], 'ghost'),
                    'jobs_help'  => $this->translator->trans('Üblicherweise werden die verfügbaren Berufe in einer Stadt durch andere Optionen gesteuert, zum Beispiel den Schamanen-Modus. Mit dieser Option kannst du die automatischen Einstellungen ausser Kraft setzen und die Berufe stattdessen manuell ein- oder ausschalten.', [], 'ghost'),

                    'buildings'  => $this->translator->trans('Verfügbare Konstruktionen', [], 'ghost'),
                    'buildings_help'  => $this->translator->trans('Üblicherweise werden die verfügbaren Konstruktionen in einer Stadt durch andere Optionen gesteuert, zum Beispiel den Stadt-Typ. Mit dieser Option kannst du die automatischen Einstellungen ausser Kraft setzen und die Konstruktionen stattdessen manuell konfigurieren.', [], 'ghost'),

                    'events'  => $this->translator->trans('Manuelles Event-Management', [], 'ghost'),
                    'events_help'  => $this->translator->trans('Deaktiviert die normale Event-Verwaltung der Stadt und legt stattdessen manuell fest, welche Event-Inhalte für die Dauer der Stadt verfügbar sind.', [], 'ghost'),

                    'job_list' => array_map( function(CitizenProfession $job) use ($asset) {
                        return [
                            'icon' => $asset->getUrl( "build/images/professions/{$job->getIcon()}.gif" ),
                            'label' => $this->translator->trans($job->getLabel(), [], 'game'),
                            'name' => $job->getName()
                        ];
                    }, $em->getRepository( CitizenProfession::class )->findSelectable()),

                    'building_props' => [
                        $this->translator->trans('Gebaut', [], 'game'),
                        $this->translator->trans('Baubar', [], 'game'),
                        $this->translator->trans('Findbar', [], 'game'),
                        $this->translator->trans('Deaktiviert', [], 'game')
                    ],
                    'buildings_list' => array_map( function(BuildingPrototype $building) use ($asset) {
                        return [
                            'icon' => $asset->getUrl( "build/images/building/{$building->getIcon()}.gif" ),
                            'label' => $this->translator->trans($building->getLabel(), [], 'buildings'),
                            'name' => $building->getName(),
                            'id' => $building->getId(),
                            'parent' => $building->getParent()?->getId() ?? null,
                            'unlockable' => $building->getBlueprint() > 0
                        ];
                    }, $em->getRepository( BuildingPrototype::class )->findNonManual()),

                    'event_management' => $this->translator->trans('Event-Management', [], 'ghost'),
                    'event_list' => $all_events,
                    'event_auto' => $this->translator->trans('Automatisch', [], 'ghost'),
                    'event_auto_help' => $this->translator->trans('Verwendet die normale Event-Verwaltung der Stadt.', [], 'ghost'),
                    'event_none' => $this->translator->trans('Deaktiviert', [], 'ghost'),
                    'event_none_help' => $this->translator->trans('Deaktiviert die normale Event-Verwaltung der Stadt, sodass keine Events aktiviert werden.', [], 'ghost'),
                    'event_any_help'  => $this->translator->trans('Deaktiviert die normale Event-Verwaltung der Stadt und aktiviert stattdessen das ausgewählte Event für die gesamte Dauer der Stadt.', [], 'ghost')
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

    protected function sanitize_config(array $conf): array {
        static $unset_props = [
            'ruin_items', 'zone_items', 'explorable_ruin_params', 'map_params',
            'allow_local_conf',
            'bank_abuse', 'spiritual_guide', 'times',
            'distribute_items', 'distribution_distance',
            'instant_pictos',
            'open_town_grace', 'population',
            'stranger_citizen_limit', 'stranger_day_limit',
        ];

        static $unset_features = [
            'last_death', 'last_death_day', 'survival_picto', 'words_of_heros'
        ];

        static $unset_modules = [
            'assemble_items_from_floor', 'citizen_attack',
            'complaints', 'destroy_defense_objects_attack', 'ghoul_infection_begin', 'hide_home_upgrade',
            'infection_death_chance', 'massive_respawn_factor', 'meaty_bones_within_town',
            'preview_item_assemblage', 'red_soul_max_factor', 'sandball_nastyness',
            'watchtower_estimation_offset', 'watchtower_estimation_threshold', 'wind_distance',
            'wound_terror_penalty', 'camping'
        ];

        foreach ($unset_props as $prop) unset ($conf[$prop]);
        foreach ($unset_features as $prop) unset ($conf['features'][$prop]);
        foreach ($unset_modules as $prop) unset ($conf['modifiers'][$prop]);

        unset( $conf['features']['escort']['max'] );

        return $conf;
    }

    protected function sanitize_outgoing_config(array $conf): array {
        static $unset_props = [
            'well', 'map', 'ruins'
        ];

        $conf = $this->sanitize_config( $conf );

        foreach ($unset_props as $prop) unset ($conf[$prop]);
        return $conf;
    }

    protected function sanitize_incoming_config(array $conf, TownClass $base): array {
        $conf = $this->sanitize_config($conf);

        $map_preset = $conf['mapPreset'] ?? null;
        unset( $conf['mapPreset'] );

        $map_margin_preset = $conf['mapMarginPreset'] ?? null;
        unset( $conf['mapMarginPreset'] );
        unset( $conf['map']['margin'] );

        $well_preset = $conf['wellPreset'] ?? null;
        unset( $conf['wellPreset'] );

        if ($map_preset) {
            $conf['map'] = $conf['map'] ?? [];
            switch ($map_preset) {
                case 'small':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::EASY )->getData();
                    $conf['map']['min'] = $tc['map']['min'] ?? 12;
                    $conf['map']['max'] = $tc['map']['max'] ?? 14;
                    $conf['ruins'] = $tc['ruins'] ?? 7;
                    $conf['explorable_ruins'] = $tc['explorable_ruins'] ?? 0;
                    break;
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['min'] = $tc['map']['min'] ?? 25;
                    $conf['map']['max'] = $tc['map']['max'] ?? 27;
                    $conf['ruins'] = $tc['ruins'] ?? 20;
                    $conf['explorable_ruins'] = $tc['explorable_ruins'] ?? 1;
                    break;
                case 'large':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['min'] = 32;
                    $conf['map']['max'] = 35;
                    $conf['ruins'] = 30;
                    $conf['explorable_ruins'] = ($tc['explorable_ruins'] ?? 1) + 1;
                    break;
            }
        }

        if ($map_margin_preset) {
            $conf['map'] = $conf['map'] ?? [];
            switch ($map_margin_preset) {
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( $base )->getData();
                    $conf['map']['margin'] = $tc['map']['margin'] ?? 0.25;
                    break;
                case 'close':
                    $conf['map']['margin'] = 0.33;
                    break;
                case 'central':
                    $conf['map']['margin'] = 0.50;
                    break;
            }
        }

        if ($well_preset) {
            $conf['well'] = $conf['well'] ?? [];
            switch ($well_preset) {
                case 'normal':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::DEFAULT )->getData();
                    $conf['well']['min'] = $tc['well']['min'] ?? 90;
                    $conf['well']['max'] = $tc['well']['max'] ?? 180;
                    break;
                case 'low':
                    $tc = $this->conf->getTownConfigurationByType( TownClass::HARD )->getData();
                    $conf['well']['min'] = $tc['well']['min'] ?? 60;
                    $conf['well']['max'] = $tc['well']['max'] ?? 90;
                    break;
            }
        }

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

    private function building_prototype_is_selectable(?BuildingPrototype $prototype, bool $for_construction = false ): bool {
        return !(!$prototype || $prototype->getBlueprint() >= 5 || (!$for_construction && $prototype->getBlueprint() <= 0));
    }

    protected function fix_rules( array &$head, array &$rules, EntityManagerInterface $em ): void {
        // Apply town type settings
        $head['townType'] = $em->getRepository( TownClass::class )->find( $head['townType'] )?->getName() ?? 'custom';
        if ($head['townType'] !== 'custom') $head['townBase'] = $head['townType'];
        else $head['townBase'] = $em->getRepository( TownClass::class )->find( $head['townBase'] )?->getName() ?? TownClass::DEFAULT;
        if ($head['townBase'] === 'custom') $head['townBase'] = TownClass::DEFAULT;

        $lang = $head['townLang'] ?? 'multi';
        if ($lang !== 'multi' && !in_array( $lang, $this->generatedLangsCodes )) unset( $head['townLang'] );

        $lang_name = $head['townNameLang'] ?? $lang;
        if ($lang_name !== 'multi' && !in_array( $lang_name, $this->generatedLangsCodes )) unset( $head['townNameLang'] );

        // Make sure the event value is valid
        if (($head['event'] ?? 'auto') === 'auto') unset( $head['event'] );
        elseif ($head['event'] !== 'none' && !in_array( $head['event'], $this->conf->getAllEventNames() )) $head['event'] = 'none';

        // Remove setting objects for custom constructions / jobs if the option to use them is disabled
        if (!isset($head['customJobs'])) unset($rules['disabled_jobs']);
        if (!isset($head['customConstructions'])) {
            unset($rules['initial_buildings']);
            unset($rules['unlocked_buildings']);
            unset($rules['disabled_buildings']);
        }

        // Fix town schedule
        if ( !empty($head['townSchedule'] ) ) {
            try {
                $head['townSchedule'] = new \DateTime($head['townSchedule']);
                if ($head['townSchedule'] <= new \DateTime()) unset( $head['townSchedule'] );
            } catch (\Throwable) {
                unset( $head['townSchedule'] );
            }
        }

        // Town population
        if (!is_int( $head['townPop'] ?? 'x' )) unset( $head['townPop'] );
        if (isset($head['townPop'])) {
            $head['townPop'] = max(10, min($head['townPop'], 80));
            $rules['population']['min'] = $rules['population']['max'] = $head['townPop'];
        }
        unset( $head['townPop'] );

        // Town Seed
        if (!is_int( $head['townSeed'] ?? 'x' ) || (int)$head['townSeed'] <= 0) unset( $head['townSeed'] );

        // Ensure map min/max is between 10 and 35
        if (!is_int( $rules['map']['min'] ?? 'x' )) unset( $rules['map']['min'] ); if (!is_int( $rules['map']['max'] ?? 'x' )) unset( $rules['map']['max'] );
        if ( ($rules['map']['min'] ?? 10) < 10 ) $rules['map']['min'] = 10; if ( ($rules['map']['max'] ?? 10) < 10 ) $rules['map']['max'] = 10;
        if ( ($rules['map']['min'] ?? 10) > 35 ) $rules['map']['min'] = 35; if ( ($rules['map']['max'] ?? 10) > 35 ) $rules['map']['max'] = 35;
        if ( ($rules['map']['min'] ?? 0) > ($rules['map']['max'] ?? 0) ) $rules['map']['min'] = $rules['map']['max'];

        // Ensure map margin is between 0.25 and 0.5
        if (!is_float( $rules['map']['margin'] ?? 'x' )) unset( $rules['map']['margin'] );
        if ( ($rules['map']['margin'] ?? 0.25) < 0.25 ) $rules['map']['margin'] = 0.25;
        if ( ($rules['map']['margin'] ?? 0.25) > 0.50 ) $rules['map']['margin'] = 0.50;

        // Ensure # of ruins / e-ruins is between 0-30 / 0-5
        if (!is_int( $rules['ruins'] ?? 'x' )) unset( $rules['ruins'] );
        if ( ($rules['ruins'] ?? 0) < 0 ) $rules['ruins'] = 0;
        if ( ($rules['ruins'] ?? 0) > 30 ) $rules['ruins'] = 30;
        if (!is_int( $rules['explorable_ruins'] ?? 'x' )) unset( $rules['explorable_ruins'] );
        if ( ($rules['explorable_ruins'] ?? 0) < 0 ) $rules['explorable_ruins'] = 0;
        if ( ($rules['explorable_ruins'] ?? 0) > 5 ) $rules['explorable_ruins'] = 5;

        // Ensure well min/max is above 0
        if (!is_int( $rules['well']['min'] ?? 'x' )) unset( $rules['well']['min'] ); if (!is_int( $rules['well']['max'] ?? 'x' )) unset( $rules['well']['max'] );
        if ( ($rules['well']['min'] ?? 0) < 0 ) $rules['well']['min'] = 0; if ( ($rules['well']['max'] ?? 0) < 0 ) $rules['well']['max'] = 0;
        if ( ($rules['well']['min'] ?? 0) > ($rules['well']['max'] ?? 0) ) $rules['well']['min'] = $rules['well']['max'];

        // Ensure all jobs are valid, and no job is doubled
        if (isset( $rules['disabled_jobs'] ))
            $rules['disabled_jobs'] = array_filter( array_unique( $rules['disabled_jobs'] ), fn(string $job) => $job !== CitizenProfession::DEFAULT && $em->getRepository(CitizenProfession::class)->findOneBy(['name' => $job]) );

        // Ensure all disabled buildings are valid (exist), and no building is doubled
        if (isset( $rules['disabled_buildings'] ))
            $rules['disabled_buildings'] = array_filter( array_unique( $rules['disabled_buildings'] ), fn(string $building) => $em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]) );

        // Ensure all unlocked buildings are valid (exist and are unlockable by a blueprint), and no building is doubled
        if (isset( $rules['unlocked_buildings'] ))
            $rules['unlocked_buildings'] = array_filter( array_unique( $rules['unlocked_buildings'] ), fn(string $building) => !in_array($building, $rules['disabled_buildings'] ?? []) && $this->building_prototype_is_selectable($em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]) ) );

        // Ensure all initially constructed buildings are valid (exist and are either unlockable by a blueprint or unlocked by default), and no building is doubled
        if (isset( $rules['initial_buildings'] ))
            $rules['initial_buildings'] = array_filter( array_unique( $rules['initial_buildings'] ), fn(string $building) => !in_array($building, $rules['disabled_buildings'] ?? []) && $this->building_prototype_is_selectable($em->getRepository(BuildingPrototype::class)->findOneBy(['name' => $building]), true ) );
    }

    protected function elevation_needed( array &$head, array &$rules, ?int $trimTo = null ): int {

        $elevation = User::USER_LEVEL_BASIC;

        // Non-private town needs CROW permissions
        if ($head['townType'] !== 'custom') $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) $head['townType'] = 'custom';

        // Custom town name needs CROW permissions
        if (!empty($head['townName'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townName']);

        // Custom town seed needs CROW permissions
        if (isset($head['townSeed'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townSeed']);

        // Event tag needs CROW permissions
        if ($head['townEventTag'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['townEventTag']);

        // Custom event needs CROW permissions
        if ($head['event'] ?? null) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($head['event']);

        // Crow options
        if ($rules['features']['give_all_pictos'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['give_all_pictos']);
        if ($rules['features']['enable_pictos'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['enable_pictos']);
        if ($rules['features']['give_soulpoints'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['give_soulpoints']);
        if ($rules['modifiers']['strict_picto_distribution'] ?? false) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['modifiers']['strict_picto_distribution']);
        if (!($rules['lock_door_until_full'] ?? true)) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['lock_door_until_full']);
        if (isset($rules['open_town_limit'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['open_town_limit']);

        // Custom job and role settings require CROW permissions
        if (!empty($rules['disabled_jobs']) && $rules['disabled_jobs'] !== ['shaman']) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_jobs']);
        if (!empty($rules['disabled_roles']) && $rules['disabled_roles'] !== ['shaman']) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_roles']);

        // Custom building settings require CROW permissions
        if (!empty($rules['initial_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['initial_buildings']);
        if (!empty($rules['unlocked_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['unlocked_buildings']);
        if (!empty($rules['disabled_buildings'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset($rules['disabled_buildings']);

        // Using the town schedule setting requires CROW permissions
        if (!empty($head['townSchedule'])) $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) unset( $head['townSchedule'] );

        // Using any other than the "incarnate" setting requires CROW permissions
        if (!empty($head['townIncarnation']) && $head['townIncarnation'] !== 'incarnate') $elevation = max($elevation, User::USER_LEVEL_CROW);
        if ($trimTo < User::USER_LEVEL_CROW) $head['townIncarnation'] = 'incarnate';

        // Deviating population numbers need CROW permissions
        if (($rules['population']['min'] ?? 40) !== 40 || ($rules['population']['max'] ?? 40) !== 40) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['population']['min'] = $rules['population']['max'] = 40;
        }

        // Maps larger than 27x27 need CROW permissions
        if (max($rules['map']['min'] ?? 0, $rules['map']['max'] ?? 0) > 27) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['map']['min'] = $rules['map']['max'] = 27;
        }

        // Maps with non-standard town position need CROW permissions
        if (($rules['map']['margin'] ?? 0.25) !== 0.25) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['map']['margin'] = 0.25;
        }

        // More than 3 explorable ruins need CROW permissions
        if (($rules['explorable_ruins'] ?? 0) > 3) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['explorable_ruins'] = 3;
        }

        // Well with more than 300 rations need CROW permissions
        if (max($rules['well']['min'] ?? 0, $rules['well']['max'] ?? 0) > 300) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) $rules['well']['min'] = $rules['well']['max'] = 300;
        }

        // Initial chest items need CROW permissions
        if (!empty( $rules['initial_chest'] )) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['initial_chest']);
        }

        // An open town limit other than 2 requires CROW permissions
        if ( ($rules['open_town_limit'] ?? 2) !== 2 ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['open_town_limit']);
        }

        // Citizen aliases require CROW permissions
        if ( ($rules['features']['citizen_alias'] ?? false) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['citizen_alias']);
        }

        // FFA requires CROW permissions
        if ( ($rules['features']['free_for_all'] ?? false) ) {
            $elevation = max($elevation, User::USER_LEVEL_CROW);
            if ($trimTo < User::USER_LEVEL_CROW) unset($rules['features']['free_for_all']);
        }

        return $elevation;
    }

    protected function move_lists( &$rules ) {

        $lists = ['disabled_jobs', 'disabled_roles', 'initial_buildings', 'unlocked_buildings', 'disabled_buildings'];

        foreach ($lists as $list)
            if (isset( $rules[$list] ))
                $rules[$list] = ['replace' => $rules[$list]];
    }

    protected function scrub_config( array &$subject, array $reference ) {

        if (empty($subject)) return;

        $ref_is_associative = !empty($reference) && array_keys($reference) !== range(0, count($reference) - 1);

        if (!$ref_is_associative) {
            $subject = array_values( $subject );
            $item_ref = array_reduce( $reference, fn( array $carry, $item ) => is_array( $item ) ? array_merge_recursive( $carry, $item ) : $carry, [] );

            // If the reference array does not contain objects, filter all object values from the subject
            if (empty($item_ref)) $subject = array_filter( $subject, fn($item) => !is_array($item) );
            else {
                // If the reference array contains objects, filter all non-object values from the subject
                // Then, scrub each element according to the item reference
                $subject = array_filter( $subject, fn($item) => is_array($item) );
                foreach ($subject as &$sub) $this->scrub_config($sub, $item_ref);
            }

        } else {

            $props = array_keys( $subject );

            foreach ( $props as $prop ) {
                // Remove all object keys not present in the reference array
                if (!array_key_exists($prop, $reference)) unset( $subject[$prop] );
                // Remove object keys where the object state mismatches between reference and subject
                elseif (is_array( $subject[$prop] ) !== is_array( $reference[$prop] )) unset( $subject[$prop] );
                // Recurse into sub-objects
                elseif (is_array( $subject[$prop] )) $this->scrub_config( $subject[$prop], $reference[$prop] );
            }
        }


    }

    /**
     * @Route("/create-town", name="create-town", methods={"POST"})
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @param GameFactory $gameFactory
     * @param GameProfilerService $profiler
     * @param TownHandler $townHandler
     * @return JsonResponse
     */
    public function create_town(JSONRequestParser $parser,
                                EntityManagerInterface $em,
                                UserHandler $userHandler,
                                GameFactory $gameFactory,
                                GameProfilerService $profiler,
                                TownHandler $townHandler): JsonResponse {

        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_death')] );

        if ($user->getRightsElevation() < User::USER_LEVEL_CROW && !$userHandler->hasSkill($user, 'mayor'))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable, ['url' => $this->generateUrl('initial_landing')] );

        $header = $parser->get_array('head');

        /** @var ?TownClass $primaryConf */
        $primaryConf = $em->getRepository( TownClass::class )->find( $header['townType'] ?? -1 );
        if (!$primaryConf) return new JsonResponse($header, Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var ?TownClass $templateConf */
        $templateConf = $em->getRepository( TownClass::class )->find( $header['townBase'] ?? -1 );
        if (!$primaryConf->getHasPreset() && !$templateConf?->getHasPreset()) return new JsonResponse($header, Response::HTTP_UNPROCESSABLE_ENTITY);

        $user_slots = array_filter($em->getRepository(User::class)->findBy(['id' => array_map(fn($a) => (int)$a, $header['reserve'] ?? [])]), function(User $u) {
            return $u->getEmail() !== 'crow' && $u->getEmail() !== $u->getUsername() && !str_ends_with($u->getName(), '@localhost');
        });

        if (count($user_slots) !== count($header['reserve'] ?? []))
            return new JsonResponse($header, Response::HTTP_UNPROCESSABLE_ENTITY);

        $base = $primaryConf->getHasPreset() ? $primaryConf : $templateConf;
        $rules = $this->sanitize_incoming_config( $parser->get_array('rules'), $base );

        $template = $this->conf->getTownConfigurationByType( $base, !$primaryConf->getHasPreset() )->getData();
        $this->scrub_config( $rules, $template );
        $this->fix_rules( $header, $rules, $em );
        $this->elevation_needed( $header, $rules, $user->getRightsElevation() );

        $this->move_lists( $rules );

        $seed = $header['townSeed'] ?? -1;

        if ($header['event'] ?? null) {
            $current_events = $header['event'] === 'none' ? [] : [ $this->conf->getEvent( $header['event'] ) ];
        } else $current_events = $this->conf->getCurrentEvents();

        $name_changers = array_values(
            array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
        );

        $town = $gameFactory->createTown(new TownSetup( $header['townType'],
            name:           $header['townName'] ?? null,
            language:       $header['townLang'] ?? 'multi',
            nameLanguage:   $header['townNameLang'] ?? null,
            typeDeriveFrom: $header['townBase'] ?? null,
            customConf:     $rules,
            seed:           $seed,
            nameMutator:    $name_changers[0] ?? null
        ));

        $town->setCreator($user);
        if(!empty($header['townCode'])) $town->setPassword($header['townCode']);
        if ($header['event'] ?? null) $town->setManagedEvents( true );

        foreach ($user_slots as $user_slot)
            $em->persist((new TownSlotReservation())->setTown($town)->setUser($user_slot));

        $em->persist($town);

        if (!empty( $header['townSchedule'] )) $town->setScheduledFor( $header['townSchedule'] );

        try {
            $em->flush();
            $profiler->recordTownCreated( $town, $user, 'custom' );
            $em->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }

        if ($header['townEventTag'] ?? false) {
            $em->persist($town->getRankingEntry()->setEvent(true));
            $em->flush();
        }

        if (!empty(array_filter($current_events, fn(EventConf $e) => $e->active()))) {
            if (!$townHandler->updateCurrentEvents($town, $current_events)) {
                $em->clear();
            } else try {
                $em->persist($town);
                $em->flush();
            } catch (Exception $e) {}
        }

        $incarnation = $header['townIncarnation'] ?? ($user->getRightsElevation() < User::USER_LEVEL_CROW ? 'incarnate' : 'none');
        $incarnated = $incarnation === 'incarnate';

        if ($incarnated) {
            $citizen = $gameFactory->createCitizen($town, $user, $error, $all);
            if (!$citizen) return AjaxResponse::error($error);
            try {
                $em->persist($citizen);
                $em->flush();
                foreach ($all as $new_citizen)
                    $profiler->recordCitizenJoined( $new_citizen, $new_citizen === $citizen ? 'create' : 'follow' );
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }

            try {
                $em->flush();
            } catch (Exception $e) {
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        return AjaxResponse::success( true, ['url' => $incarnated ? $this->generateUrl('game_jobs') : $this->generateUrl('ghost_welcome')] );
    }

}
