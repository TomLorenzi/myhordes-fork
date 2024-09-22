<?php

namespace App\Controller\REST;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractCoreController;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\MayorMark;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRulesTemplate;
use App\Entity\User;
use App\Response\AjaxResponse;
use App\Service\Actions\Ghost\CreateTownFromConfigAction;
use App\Service\Actions\Ghost\SanitizeTownConfigAction;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownSetup;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/rest/v1/town-creator', name: 'rest_town_creator_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile('skip')]
class TownCreatorController extends CustomAbstractCoreController
{

    /**
     * @param EntityManagerInterface $em
     * @param Packages $asset
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    public function index(EntityManagerInterface $em, Packages $asset): JsonResponse {

        $all_events = array_values(array_map(
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
        ));

        return new JsonResponse([
            'strings' => [
                'common' => [
                    'create' => $this->translator->trans('Diese Stadt gründen', [], 'ghost'),
                    'confirm' => $this->translator->trans('Bestätigen?', [], 'global'),
                    'help' => $this->translator->trans('Hilfe', [], 'global'),
                    'need_selection' => "[ {$this->translator->trans('Bitte auswählen', [], 'global')} ]",
                    'notice' => $this->translator->trans('Achtung!', [], 'ghost'),
                    'negate' => $this->translator->trans('Falls die Stadt night in 2 Tagen gefüllt ist, wird sie wieder negiert.', [], 'ghost'),
                    'incorrect_fields' => $this->translator->trans('Die Stadt kann mit diesen Parametern nicht erstellt werden, einige Felder sind entweder unvollständig oder ungültig.', [], 'ghost'),
                    'delete_icon' => $asset->getUrl( "build/images/icons/small_remove.gif" )
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

                    'participation' => $this->translator->trans('Teilnahme', [], 'ghost'),
                    'participation_presets' => [
                        ['value' => 'incarnate', 'label' => $this->translator->trans('Verkörperung in der Stadt', [], 'ghost'), 'help' => $this->translator->trans('Verkörpert dich in der Stadt bei ihrer Entstehung.', [], 'ghost')],
                        ['value' => 'forum',     'label' => $this->translator->trans('Forenzugang (selbst)', [], 'ghost'), 'help' => $this->translator->trans('Du erhälst Zugang zum Stadtforum, wirst jedoch nicht verkörpert.', [], 'ghost')],
                        ['value' => 'forum-all', 'label' => $this->translator->trans('Forenzugang (Gilde)', [], 'ghost'), 'help' => $this->translator->trans('Du und alle Mitglieder der Animateursgilde erhalten Zugang zum Stadtforum', [], 'ghost')],
                        ['value' => 'none',      'label' => $this->translator->trans('Keine', [], 'ghost'), 'help' => $this->translator->trans('Du wirst weder verkörpert, noch erhälst du Zugang zum Stadtforum.', [], 'ghost')],
                    ],

                    'schedule' => $this->translator->trans('Stadtstart planen', [], 'ghost'),
                    'schedule_help' => $this->translator->trans('Wenn du die Stadt erst zu einem zukünftigen Zeitpunkt eröffnen möchtest, kannst du diesen hier eingeben (in Serverzeit). Erst wenn dieser Zeitpunkt erreicht ist, wird die Stadt zur Verfügung stehen. WICHTIG: Die Option "Verkörperung in der Stadt" schließt einen geplanten Stadtstart aus!', [], 'ghost'),

                    'management' => [
                        'section' => $this->translator->trans('Verwaltung', [], 'ghost'),

                        'event_tag' => $this->translator->trans('Als Event-Stadt markieren', [], 'ghost'),
                        'event_tag_help' => $this->translator->trans('Event-Städte werden nicht ins Ranking aufgenommen und erhalten eine spezielle Markierung in der Stadtliste.', [], 'ghost'),
                    ],

                ],

                'template' => [
                    'section' => $this->translator->trans('Vorlagen', [], 'ghost'),
                    'description' => $this->translator->trans('Mithilfe von Vorlagen kannst du deine Privatstadt-Einstellungen für später speichern. Du kannst diese Einstellungen jederzeit laden, aktualisieren oder löschen.', [], 'ghost'),
                    'description_2' => $this->translator->trans('Beachte, das die "Grundlegenden Einstellungen", die du oben getätigst hast, NICHT in der Vorlage gespeichert werden!', [], 'ghost'),

                    'select' => $this->translator->trans('Ausgewählte Vorlage', [], 'ghost'),
                    'none' => $this->translator->trans('Keine Auswahl', [], 'ghost'),

                    'save' => $this->translator->trans('Aktuelle Einstellungen als neue Vorlage speichern', [], 'ghost'),
                    'saveConfirm' => $this->translator->trans('Bitte gib deiner neuen Vorlage einen Namen.', [], 'ghost'),
                    'saveDone' => $this->translator->trans('Deine Vorlage wurde erfolgreich erstellt.', [], 'ghost'),
                    'saveNameError' => $this->translator->trans('Der Name deiner Vorlage muss zwischen 3 und 64 Zeichen lang sein.', [], 'ghost'),

                    'update' => $this->translator->trans('Aktualisieren', [], 'ghost'),
                    'updateConfirm' => $this->translator->trans('Der Inhalt der aktuellen Vorlage wird durch deine aktuellen Einstellungen ersetzt. Fortfahren?', [], 'ghost'),
                    'updateDone' => $this->translator->trans('Deine Vorlage wurde erfolgreich aktualisiert.', [], 'ghost'),

                    'load' => $this->translator->trans('Laden', [], 'ghost'),
                    'loadConfirm' => $this->translator->trans('Eine aktuellen Einstellungen werden durch den Inhalt der gewählten Vorlage ersetzt. Fortfahren?', [], 'ghost'),
                    'loadDone' => $this->translator->trans('Deine Einstellungen wurden erfolgreich geladen.', [], 'ghost'),

                    'delete' => $this->translator->trans('Löschen', [], 'ghost'),
                    'deleteConfirm' => $this->translator->trans('Deine ausgewählte Vorlage wird gelöscht. Dieser Vorgang kann nicht rückgängig gemacht werden. Fortfahren?', [], 'ghost'),
                    'deleteDone' => $this->translator->trans('Deine Vorlage wurde erfolgreich gelöscht.', [], 'ghost'),
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

                    'explorable' => $this->translator->trans('Größe der Begehbaren Ruinen', [], 'ghost'),
                    'explorable_timing' => $this->translator->trans('Sauerstoffvorrat', [], 'ghost'),
                    'explorable_timing_presets' => [
                        ['value' => 'low', 'label' => $this->translator->trans('Gering', [], 'ghost')],
                        ['value' => 'normal', 'label' => $this->translator->trans('Normal', [], 'ghost')],
                        ['value' => 'long', 'label' => $this->translator->trans('Umfangreich', [], 'ghost')],
                        ['value' => 'extra-long', 'label' => $this->translator->trans('Exorbitant', [], 'ghost')]
                    ],
                    'explorable_presets' => [
                        ['value' => 'classic', 'label' => $this->translator->trans('Eine Etage', [], 'ghost')],
                        ['value' => 'normal', 'label' => $this->translator->trans('Zwei Etagen', [], 'ghost')],
                        ['value' => 'large', 'label' => $this->translator->trans('Drei Etagen', [], 'ghost')],
                        ['value' => '_custom', 'label' => $this->translator->trans('Eigene Einstellung', [], 'ghost')]
                    ],
                    'explorable_floors' => $this->translator->trans('Etagen', [], 'ghost'),
                    'explorable_rooms' => $this->translator->trans('Anzahl Räume', [], 'ghost'),
                    'explorable_min_rooms' => $this->translator->trans('Mindestanzahl an Räumen pro Etage', [], 'ghost'),
                    'explorable_space_x' => $this->translator->trans('Kartenbreite', [], 'ghost'),
                    'explorable_space_y' => $this->translator->trans('Kartenhöhe', [], 'ghost'),

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
                        ['value' => 'central', 'label' => $this->translator->trans('Zentral', [], 'ghost')],
                        ['value' => '_custom', 'label' => $this->translator->trans('Eigene Einstellung', [], 'ghost')]
                    ],
                    'position_north' => $this->translator->trans('Nördlicher Abstand', [], 'ghost'),
                    'position_south' => $this->translator->trans('Südlicher Abstand', [], 'ghost'),
                    'position_west' => $this->translator->trans('Westlicher Abstand', [], 'ghost'),
                    'position_east' => $this->translator->trans('Östlicher Abstand', [], 'ghost'),
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

                        'fft' => $this->translator->trans('Team-Beschränkung deaktivieren', [], 'ghost'),
                        'fft_help' => $this->translator->trans('Diese Stadt gehört keinem Team an.', [], 'ghost'),
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

                        'strange_soil' => $this->translator->trans('Eigenartiger Boden', [], 'ghost'),
                        'strange_soil_help' => $this->translator->trans('Der Boden, auf dem die Stadt errichtet wurde, ist mit Chemikalien verseucht. Dies hat Einfluss auf die Qualität des Brunnenwassers und damit auch auf die Landwirtschaft...', [], 'ghost'),

                        'redig' => $this->translator->trans('Erneutes Buddeln', [], 'ghost'),
                        'redig_help' => $this->translator->trans('Ermöglicht es, auf bereits besuchten Zonen erneut zu buddeln.', [], 'ghost'),

                        'carry_bag' => $this->translator->trans('Matroschka-Taschen', [], 'ghost'),
                        'carry_bag_help' => $this->translator->trans('Spieler können mehrere Rucksackerweiterungen gleichzeitig tragen. Es wird jedoch kein zusätzlicher Platz im Rucksack freigeschaltet.', [], 'ghost'),
                    ]
                ],

                'animation' => [
                    'section' => $this->translator->trans('Raben-Optionen', [], 'ghost'),

                    'pictos' => $this->translator->trans('Vergabe von Auszeichnungen', [], 'ghost'),
                    'pictos_presets' => [
                        ['value' => 'all',              'label' => $this->translator->trans('Alle', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten alle Auszeichnungen, die sie in der Stadt verdient haben.', [], 'ghost')],
                        ['value' => 'reduced',          'label' => $this->translator->trans('Roulette', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten ein Drittel der Nicht-seltenen Auszeichnungstypen, die sie in der Stadt verdient haben, in voller Höhe. Die restlichen Auszeichnungen werden entfernt. Welche Auszeichnungstypen entfernt werden ist zufällig.', [], 'ghost')],
                        ['value' => 'reduced_classic',  'label' => $this->translator->trans('Reduziert', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten alle Nicht-seltenen Auszeichnungstypen, die sie in der Stadt verdient haben. Die Höhe aller Auszeichnungen wird gedrittelt.', [], 'ghost')],
                        ['value' => 'none',             'label' => $this->translator->trans('Deaktiviert', [], 'ghost'), 'help' => $this->translator->trans('Spieler erhalten keine Auszeichnungen für diese Stadt.', [], 'ghost')],
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

                    'management' => [
                        'section' => $this->translator->trans('Verwaltung', [], 'ghost'),

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
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/town-types', name: 'town-types', methods: ['GET'])]
    public function town_types(EntityManagerInterface $em): JsonResponse {
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

    /**
     * @param TownClass $townClass
     * @param bool $private
     * @param SanitizeTownConfigAction $sanitizeTownConfigAction
     * @return JsonResponse
     */
    #[Route(path: '/town-rules/{id}', name: 'town-rules', defaults: ['private' => false], methods: ['GET'])]
    #[Route(path: '/town-rules/private/{id}', name: 'private-town-rules', defaults: ['private' => true], methods: ['GET'])]
    public function town_type_rules(TownClass $townClass, bool $private, SanitizeTownConfigAction $sanitizeTownConfigAction): JsonResponse {
        if ($townClass->getHasPreset()) {

            $preset = $this->conf->getTownConfigurationByType($townClass, $private)->getData();

            $preset['wellPreset'] = $townClass->getName() === TownClass::HARD ? 'low' : 'normal';
            $preset['mapPreset']  = $townClass->getName() === TownClass::EASY ? 'small' : 'normal';

            return new JsonResponse( $sanitizeTownConfigAction->sanitize_outgoing_config( $preset ) );
        }

        return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param JSONRequestParser $parser
     * @param SanitizeTownConfigAction $sanitizeTownConfigAction
     * @param CreateTownFromConfigAction $createTownFromConfigAction
     * @param EntityManagerInterface $em
     * @param UserHandler $userHandler
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(path: '/create-town', name: 'create-town', methods: ['POST'])]
    public function create_town(JSONRequestParser        $parser,
                                SanitizeTownConfigAction $sanitizeTownConfigAction,
                                CreateTownFromConfigAction $createTownFromConfigAction,
                                EntityManagerInterface   $em,
                                UserHandler              $userHandler
    ): JsonResponse {

        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($em->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return AjaxResponse::success( true, ['url' => $this->generateUrl('soul_death')] );

        $limit = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_TOWNS_MAX_PRIVATE, 10);
        if (!$this->isGranted('ROLE_CROW') && count(array_filter($em->getRepository(Town::class)->findOpenTown(), fn(Town $t) => $t->getType()->getName() === 'custom')) >= $limit)
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $header = $parser->get_array('head');
        $rules = $parser->get_array('rules');

        $user_slots = [];
        if (!$sanitizeTownConfigAction( $header, $rules, $user_slots, $user ))
            return new JsonResponse($header, Response::HTTP_UNPROCESSABLE_ENTITY);

        $result = $createTownFromConfigAction($header, $rules, creator: $user, userSlots: $user_slots);
        if ($result->hasError()) return AjaxResponse::error( $result->error() );

        return AjaxResponse::success( true, ['url' => $result->citizen() ? $this->generateUrl('game_jobs') : $this->generateUrl('ghost_welcome')] );
    }

    /**
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/template', name: 'list-template', methods: ['GET'])]
    public function list_templates(
        EntityManagerInterface $em,
    ): JsonResponse {
        return new JsonResponse(array_map(
                                    fn(TownRulesTemplate $template) => ['uuid' => $template->getId(), 'name' => $template->getName()],
                                    $em->getRepository(TownRulesTemplate::class)->findBy(['owner' => $this->getUser()])
                                ));
    }

    /**
     * @param bool $create
     * @param TownRulesTemplate|null $template
     * @param EntityManagerInterface $em
     * @param JSONRequestParser $parser
     * @param SanitizeTownConfigAction $sanitizeTownConfigAction
     * @return JsonResponse
     */
    #[Route(path: '/template', name: 'create-template', defaults: ['create' => true], methods: ['PUT'])]
    #[Route(path: '/template/{id}', name: 'update-template', defaults: ['create' => false], methods: ['PATCH'])]
    public function save_template(
        bool $create,
        ?TownRulesTemplate $template,
        EntityManagerInterface $em,
        JSONRequestParser $parser,
        SanitizeTownConfigAction $sanitizeTownConfigAction
    ): JsonResponse {

        if (!$create && !$template) return new JsonResponse([], Response::HTTP_NOT_FOUND);
        if ($template && $template->getOwner() !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        if ($create) {
            $name = $parser->get('name');
            if (mb_strlen($name) < 3 || mb_strlen($name) > 64) return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);
            $template = (new TownRulesTemplate())
                ->setOwner( $this->getUser() )
                ->setName( $name )
                ->setCreated( new DateTime() );
        }

        $template
            ->setModified( new DateTime() )
            ->setValidatedBy(null)
            ->setValidationLevel( null )
            ->setData( $sanitizeTownConfigAction->sanitize_config( $parser->get_array('rules') ) );

        try {
            $em->persist( $template );
            $em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $template->getId(), 'name' => $template->getName()]);
    }

    /**
     * @param TownRulesTemplate $template
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/template/{id}', name: 'delete-template', methods: ['DELETE'])]
    public function remove_template(
        TownRulesTemplate $template,
        EntityManagerInterface $em
    ): JsonResponse {

        if ($template->getOwner() !== $this->getUser()) return new JsonResponse([], Response::HTTP_FORBIDDEN);

        try {
            $em->remove( $template );
            $em->flush();
        } catch (Exception) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['uuid' => $template->getId(), 'name' => $template->getName()]);
    }

    /**
     * @param TownRulesTemplate $template
     * @param SanitizeTownConfigAction $sanitizer
     * @return JsonResponse
     */
    #[Route(path: '/template/{id}', name: 'load-template', methods: ['GET'])]
    public function load_template(
        TownRulesTemplate $template,
        SanitizeTownConfigAction $sanitizer
    ): JsonResponse {
        return new JsonResponse(['rules' => $sanitizer->restore( $template->getData() )]);
    }

    /**
     * @param JSONRequestParser $parser
     * @param GameFactory $gameFactory
     * @param TownHandler $townHandler
     * @param GameProfilerService $gps
     * @param EntityManagerInterface $em
     * @return Response
     * @throws Exception
     */
    #[Route(path: '/create-town', name: 'create-town-mayor', methods: ['PUT'])]
    public function add_town_mayor( JSONRequestParser $parser, GameFactory $gameFactory, TownHandler $townHandler, GameProfilerService $gps, EntityManagerInterface $em): Response {

        if ($em->getRepository(Town::class)->count(['creator' => $this->getUser(), 'mayor' => true]) > 0)
            return new JsonResponse([], Response::HTTP_CONFLICT);

        if ($em->getRepository(Town::class)->count(['mayor' => true]) > 14)
            return new JsonResponse(Response::HTTP_CONFLICT);

        if ($this->getUser()->getAllSoulPoints() < 250)
            return new JsonResponse([], Response::HTTP_CONFLICT);

        $town_type = $parser->get('pTownType', null, ['small','remote','panda']);
        $town_lang = $parser->get('pTownLang', null, [...$this->generatedLangsCodes, 'multi']);
        $town_time = $parser->get('pTownStartDate', null);
        $town_use_time = $parser->get('pTownStart', null, ['now','defer']);

        if (empty($town_type) || empty($town_lang) || empty($town_use_time))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($town_use_time === 'now') $town_time = null;
        else try {
            $town_time = new \DateTime($town_time);
            if ($town_time <= new \DateTime()) $town_time = null;
        } catch (\Exception $e) {
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($town_time && $town_time > (new DateTime('today+15days')))
            return new JsonResponse([], Response::HTTP_UNPROCESSABLE_ENTITY);

        $check_time = $town_time ? (clone $town_time) : new DateTime();

        $current_events = $this->conf->getCurrentEvents();
        $name_changers = array_values(
            array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
        );

        $town = $gameFactory->createTown( new TownSetup( $town_type, language: $town_lang, nameMutator: $name_changers[0] ?? null ));
        if (!$town) return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);

        $town
            ->setScheduledFor( $town_time )
            ->setMayor( true )
            ->setCreator( $this->getUser() );

        try {
            $em->persist( $town );
            $em->persist( (new MayorMark())
                ->setUser( $this->getUser() )
                ->setMayor( true )
                ->setExpires( ($town_time ? (clone $town_time) : new DateTime())->modify('+30days') )
            );

            $em->flush();
            $gps->recordTownCreated( $town, $this->getUser(), 'mayor' );
            $em->flush();

        } catch (Exception $e) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $current_event_names = array_map(fn(EventConf $e) => $e->name(), array_filter($current_events, fn(EventConf $e) => $e->active()));
        if (!empty($current_event_names)) {
            if (!$townHandler->updateCurrentEvents($town, $current_events)) {
                $em->clear();
            } else {
                $em->persist($town);
                $em->flush();
            }
        }

        return AjaxResponse::successMessage($this->translator->trans('Deine Stadt wurde erfolgreich angelegt.', [], 'soul'));
    }

}
