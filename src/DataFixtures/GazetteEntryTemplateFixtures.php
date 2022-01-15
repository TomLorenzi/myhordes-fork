<?php

namespace App\DataFixtures;

use App\Entity\CouncilEntryTemplate;
use App\Entity\GazetteEntryTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class GazetteEntryTemplateFixtures extends Fixture
{
    public static $gazette_entry_template_data = [
        // Gazette: Fun Texts
        ['text'=>'Gestern war ein unbedeutender Tag. Einem Gerücht zufolge wurden {citizen1} und {citizen2} dabei beobachtet, wie sie zusammen im Brunnen badeten. Wenn morgen alle mit einer Pilzinfektion flach liegen, ist ja wohl klar, an wem das lag.',
            'name'=>'gazetteFun_001',
            'type'=>GazetteEntryTemplate::TypeGazetteFlavour,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Was für ein denkwürdiger Tag! Die Zombies spielten keine Rolle mehr, nachdem {citizen1} zur Mittagszeit nackt auf der Mauer einmal um die Stadt rannte. Kommentar von {citizen2} dazu: "Der Anblick war nicht von schlechten Eltern."',
            'name'=>'gazetteFun_002',
            'type'=>GazetteEntryTemplate::TypeGazetteFlavour,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Anmerkung: einer der {attack} Zombies, die letzte Nacht angegriffen haben, wurde <strong>im Brunnen</strong> gefunden... Ich rate euch dringend davon ab, heute Morgen Wasser zu trinken, sonst fangt ihr euch noch eine Infektion ein...',
            'name'=>'gazetteFun_004',
            'type'=>GazetteEntryTemplate::TypeGazetteFlavour,
            'requirement'=>GazetteEntryTemplate::RequiresInvasion,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num", 'name'=>'attack']
            ],
        ],
        ['text'=>'Außerdem haben mehrere Zombies unerwartet die <strong>Arbeiten</strong> am Westflügel genutzt, um in die Stadt einzudringen und {deaths} Menschen zu verschlingen... Ihr sollten <strong>eure Baustellen besser nicht so ungesichert lassen</strong>.',
            'name'=>'gazetteFun_003',
            'type'=>GazetteEntryTemplate::TypeGazetteFlavour,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num", 'name'=>'deaths'],
            ],
        ],

        // Gazette: No deaths in town
        ['text'=>'{citizen1} verbrachten die ganze Nacht heulend in ihrem Haus, bis zu dem Punkt, dass jeder dachte, die Zombies würden Bürger-Steaks aus ihm machen. Es stellte sich heraus, dass sie gerade einen <strong>massiven Zusammenbruch</strong> hatten. Letzte Nacht gab es keine Toten in der Stadt.',
            'name'=>'gazetteTownNoDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'{citizen1} und {citizen2} wurden in letzter Minute gerettet, als sie sich gestern Abend bereit machten, sich in ihren Häusern <strong>zu erhängen</strong>. Kommentar: "Ich dachte, sie würden mich bei lebendigem Leib auffressen, und das wollte ich nicht mehr erleben". Im Nachhinein betrachtet war es eine schlechte Entscheidung, da es gestern Abend <strong>keine Zombies</strong> in die Stadt geschafft haben.',
            'name'=>'gazetteTownNoDeaths_002',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Eine großartige Nacht für einige wohlverdiente Feierlichkeiten. Keine Todesopfer in der Stadt infolge des Angriffs! Eine beträchtliche Horde griff von Osten aus an.',
            'name'=>'gazetteTownNoDeaths_003',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Eine friedliche Nacht in der Stadt. Die Horde von {attack} Zombies, die letzte Nacht kam, traf einige Teile der Stadt ziemlich hart, aber es gibt nichts Bemerkenswertes zu berichten.',
            'name'=>'gazetteTownNoDeath_004',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Abgesehen davon, gut, dass letzte Nacht niemand starb. Eine Horde von fast {attack} Zombies heulte die ganze Nacht draußen, aber keiner von ihnen schaffte es, unsere Verteidigung zu durchbrechen.',
            'name'=>'gazetteTownNoDeath_005',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Nahrungsmittelknappheit bei der Horde : {attack} Zombies, und nicht einer von ihnen bekam letzte Nacht etwas zu fressen, unsere Abwehr hielt gut stand.',
            'name'=>'gazetteTownNoDeath_006',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Es sieht so aus, als hätten wir alles richtig gemacht, da letzte Nacht keine Zombies die Mauern durchbrochen haben.',
            'name'=>'gazetteTownNoDeath_007',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Es sei darauf hingewiesen, dass unsere Verteidigung nicht weit von der großen Südmauer entfernt dem furchterregenden Angriff der Horden letzte Nacht standgehalten hat. Um die {attack} Zombies versuchten alles, aber keine Verluste an Menschenleben während des Angriffs!',
            'name'=>'gazetteTownNoDeath_008',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Keine Todesopfer von letzter Nacht zu berichten. Man könnte sogar sagen, dass die Gemeinschaft (endlich) herausgefunden hat, wie sie sich organisieren muss, um nicht ausgelöscht zu werden.',
            'name'=>'gazetteTownNoDeath_009',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Keine Todesopfer heute Abend. Alles ist in Ordnung. Ich garantiere Euch jedoch nur, dass die Zombies heute Nacht verhungern werden: Unsere morgigen Chancen sind..., naja, sagen wir, verbesserungswürdig.',
            'name'=>'gazetteTownNoDeath_010',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Nach dem Angriff sind keine Verluste in den Reihen zu melden (zumindest keine in der Stadt). "Ja, ich denke aber schon, dass wir morgen Nacht alle sterben werden!", so {citizen1}, ein skeptischer Bürger.',
            'name'=>'gazetteTownNoDeath_011',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Unsere Verteidigung an der Ostmauer scheint zufriedenstellend zu sein. Die furchterregenden Kreaturen der Horde wurden in Schach gehalten... dieses Mal!',
            'name'=>'gazetteTownNoDeath_012',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Einige Bürger kamen letzte Nacht ins Schwitzen. Eine Welle von etwa {attack} Monstern versuchte, unsere Stadt zu zerstören, wenn auch ohne Erfolg.',
            'name'=>'gazetteTownNoDeath_013',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Die Zombies von gestern Abend hatten nichts außer unseren Fetzen, in die sie ihre Zähne bekommen konnten, und einige Tierkadaver... Unsere Verteidigung hat sich gut gehalten.',
            'name'=>'gazetteTownNoDeath_014',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Die Zombies griffen uns eine Zeit lang hart an, aber keiner kam durch... Aber zweifelt nicht eine Sekunde daran, dass sie heute Abend zurückkommen werden, noch hungriger und sicherlich zahlenmäßig größer...',
            'name'=>'gazetteTownNoDeath_015',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Heute Morgen feierte {citizen1} das Vereiteln der Zombiehorden von gestern Abend, indem <strong>er splitternackt durch die Straßen rannte</strong>. "Ich wollte den Anbruch dieses neuen Tages auf angemessene Weise feiern", erklärte der Bürger.',
            'name'=>'gazetteTownNoDeath_016',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Wir verabschiedeten uns liebevoll von dem alten Hund, der im Dorf lebte... Das unaufhörliche Bellen, das alle in der Nachbarschaft verärgerte, ist seit dem Angriff von gestern Abend für immer verstummt. Das arme Ding... Die gute Nachricht ist, dass es in der Stadt keine nennenswerten Verluste an Menschenleben gab.',
            'name'=>'gazetteTownNoDeath_017',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir hatten alles, was wir brauchten nahe dem westlichen Viertel, um die schlurfenden Leichen von gestern Abend fernzuhalten, soviel ist sicher. Null Verluste - abgesehen von ein paar Häusern, die sie getroffen haben.',
            'name'=>'gazetteTownNoDeath_018',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Ihr hast sie letzte Nacht gehört... die Schreie, das Stöhnen. Um die {attack} Zombies herum griffen an. Diesmal konnten wir uns durchsetzen, aber morgen... morgen wird es noch schlimmer...',
            'name'=>'gazetteTownNoDeath_019',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Da gestern Abend rund {attack} Zombies vor den Toren standen, hätten wir das Schlimmste befürchten können, aber kein einziger ist reingekommen: gute Zeiten! Brecht Euch sich aber nicht den Arm, wenn Ihr Euch gegenseitig auf die Schulter klopft!',
            'name'=>'gazetteTownNoDeath_020',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'<strong>{citizen1}s</strong> {animal} wurde heute Morgen in mehrere Stücke zerfetzt aufgefunden. Ein Stück wurde sogar aus dem Brunnen gefischt. Das erklärt auch den seltsamen Geschmack der Wasserrationen von heute Morgen...',
            'name'=>'gazetteTownNoDeath_021',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"transString",'name'=>'animal'],
            ],
        ],
        ['text'=>'Das mysteriöse Verschwinden unserer Stadtziege {mascot} hat heute morgen die Stadt in helle Aufruhr versetzt. Einige behaupten, dass es sich um einen Racheakt handle (der Name <strong>{citizen1}</strong> wurde öfters genannt). Wir bitten alle Einwohner, keine Gerüchte in die Welt zu setzen, solange die Untersuchungen nicht abgeschlossen sind.',
            'name'=>'gazetteTownNoDeath_022',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"transString",'name'=>'mascot'],
            ],
        ],
        ['text'=>'Die Zombieabwehr gestern war ein Kinderspiel. Heute wird das schon schwieriger werden, denn unsere "Freunde" werden noch hungriger sein...',
            'name'=>'gazetteTownNoDeath_023',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Ach, war das lustig, als <strong>{citizen1}s</strong> {animal} {mascot} aus Versehen vom Bier genascht hatte! Nun ja... {mascot} wurde heute morgen auf der Baustelle tot aufgefunden... Wir bitten darum, <strong>{citizen1}</strong> ein bisschen Trost zu spenden.',
            'name'=>'gazetteTownNoDeath_024',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"transString",'name'=>'mascot'],
                ['type'=>"transString",'name'=>'animal'],
            ],
        ],

        // Gazette: One death in town.
        ['text'=>'<i class="dagger">†</i> {cadaver1} hatte gestern Abend kein Glück. Abgesehen davon war es eine ruhige Nacht in der Stadt...',
            'name'=>'gazetteTownOneDeath_001',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Alle scheinen sich still und leise über den Tod von <i class="dagger">†</i> {cadaver1} gestern Abend zu freuen... Allerdings hat niemand erklärt, warum. {citizen1} kommentierte: "Seine Mutter war ein Hamster, und sein Vater roch nach Holunderbeeren".',
            'name'=>'gazetteTownOneDeath_002',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Jeder hörte <i class="dagger">†</i> {cadaver1} schreien, als er von den Zombies auseinander gerissen wurde. Offensichtlich versuchte niemand zu helfen. Überlebensinstinkt. Wirst du jetzt nachts schlafen können?',
            'name'=>'gazetteTownOneDeath_003',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Ich bin sicher, dass nicht nur ich der Meinung bin, dass wir eine Rechnung mit <i class="dagger">†</i> {cadaver1} zu begleichen hatten. Letztendlich scheint es also Karma gewesen zu sein, dass ausgerechnet er heute Nacht ums Leben kam.',
            'name'=>'gazetteTownOneDeath_004',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige Bürger wurden Zeugen eines seltsamen Vorfalls... Man könnte sagen, dass die Zombies gestern Abend ausschließlich für <i class="dagger">†</i> {cadaver1} gekommen sind. Sie trugen die Leiche den ganzen Weg zur Baustelle, bevor die Zerstückelung begann!',
            'name'=>'gazetteTownOneDeath_005',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige sagen, daß der Tod von <i class="dagger">†</i> {cadaver1} nicht dem Glück zu verdanken ist... das einzige Opfer gestern Abend... Könnte jemand unter uns seinen Tod provoziert haben?',
            'name'=>'gazetteTownOneDeath_006',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<i class="dagger">†</i> {cadaver1}, das einzige Opfer des letzten Angriffs. Viel Spaß in der Hölle... viel schlimmer als hier kanns nicht sein...',
            'name'=>'gazetteTownOneDeath_007',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Alle haben sich gestern Nacht unter ihre Decken verkrochen, anstatt <i class="dagger">†</i> {cadaver1}, unserem einzigem Opfer, zu helfen. Wir sind wirklich \'ne super Gemeinschaft!',
            'name'=>'gazetteTownOneDeath_008',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Es ist offensichtlich: das Blut auf der Straße gehört <i class="dagger">†</i> {cadaver1}. Sieht so aus als hätten die Zombies ihr Opfer gefunden...',
            'name'=>'gazetteTownOneDeath_009',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Armer <i class="dagger">†</i> {cadaver1}, das einzige Opfer des nächtlichen Angriffs... <strong>{citizen1}</strong> murmelte seine Sympathiebekundung: "Gut! ...war eh ein Arsch... schuldet mir noch \'nen Zehner".',
            'name'=>'gazetteTownOneDeath_010',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'<i class="dagger">†</i> {cadaver1} wurde heute morgen mausetot in seinem Bett aufgefunden. Das Problem ist nur: Es war <strong>kein</strong> Zombie... <strong>Wer</strong> könnte so etwas Schreckliches nur tun?',
            'name'=>'gazetteTownOneDeath_011',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die Zombies haben sich nicht mit Nettigkeiten aufgehalten... alles, was von <i class="dagger">†</i> {cadaver1} noch übrig ist ist ein Kieferknochen sowie verschiedene Organe, die {location} gefunden wurden. Kommt schon, lächelt mal wieder! Er war das einzige Opfer, stellt euch mal vor es hätte auch noch <strong>{citizen1}</strong> getroffen!',
            'name'=>'gazetteTownOneDeath_012',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"transString",'name'=>'location'],
            ],
        ],
        ['text'=>'Niemand kennt den Grund dafür, aber es scheint, dass <i class="dagger">†</i> {cadaver1} letzte Nacht den Verstand verloren hat. Mehrere Bürger berichten, sie hätten ihn während des Angriffs nackt draußen herumlaufen sehen! Das eine erklärt das andere...',
            'name'=>'gazetteTownOneDeath_013',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die ganze Stadt <strong>freut</strong> sich insgeheim über <i class="dagger">†</i> {cadaver1} Tod... Niemand sagt allerdings warum - außer <strong>{citizen1}</strong>: "Den konnte hier doch eh keiner leiden!".',
            'name'=>'gazetteTownOneDeath_014',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],

        // Gazette: Two deaths in town
        ['text'=>'Ausgangssperre gilt für alle. Auch für <i class="dagger">†</i> {cadaver1} und <i class="dagger">†</i> {cadaver2} – das haben sie nun davon.',
            'name'=>'gazetteTownTwoDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteTwoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCadavers,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],
        ['text'=>'So richtig scheint keiner über den Tod von <i class="dagger">†</i> {cadaver1} und <i class="dagger">†</i> {cadaver2} zu trauern. Sie waren wohl nicht die beliebtesten in der Stadt.',
            'name'=>'gazetteTownTwoDeaths_002',
            'type'=>GazetteEntryTemplate::TypeGazetteTwoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCadavers,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],

        // Gazette: Multiple deaths in town.
        ['text'=>'Eine schreckliche Nacht für die Stadt. Die lebenden Toten massakrierten {deaths} unserer Gemeinde während des Angriffs. Vielleicht möchtet ihr vor heute Abend noch einmal einen Blick auf unsere Verteidigung werfen...',
            'name'=>'gazetteTownMultiDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Die Zombies fanden gestern Abend an der Nordwand eine Schwäche in unserer Verteidigung... Einige Häuser hielten dem Angriff stand. Andere nicht... ... {deaths} tot. Ende der Geschichte.',
            'name'=>'gazetteTownMultiDeaths_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Einige Bewohner brachen heute Morgen ob der Opfer in Tränen aus. Tränen der Freude mit Sicherheit, nicht eines der {deaths} Opfer des letzten Angriffs zu sein.',
            'name'=>'gazetteTownMultiDeaths_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Handvoll Zombies durchbrach unsere Verteidigungsanlagen in der Nähe des nördlichen Viertels, wir haben keine Ahnung, wie... Wie es das "Glück" wollte, sind {deaths} Bürger tot, aber ihr habt seltsamerweise überlebt. ... Klingt das nicht ein wenig verdächtig?',
            'name'=>'gazetteTownMultiDeaths_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Flutwelle von Zombies stürzte letzte Nacht gegen unsere Stadt! Bürger wurden in ihren eigenen Häusern verschlungen oder in die Wüste geschleift... Noch so eine Nacht, und wir werden nicht mehr hier sein, um darüber zu reden.',
            'name'=>'gazetteTownMultiDeaths_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir müssen uns beeilen; unsere Unfähigkeit, zufriedenstellende Verteidigungsanlagen zu errichten, kostete letzte Nacht {deaths} Bürgern das Leben. Zu eurer Information: Gestern Abend wurde die Stadt von {attack} Zombies angegriffen.',
            'name'=>'gazetteTownMultiDeaths_006',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Die Verteidigungsanlagen waren gestern Abend unzureichend. {deaths} Bürger bezahlten für eure mangelnde Organisation mit ihrem Leben.',
            'name'=>'gazetteTownMultiDeaths_007',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Letzte Nacht haben es {deaths} Bürger nicht rechtzeitig nach Hause geschafft. Einige Teile von ihnen wurden in der Nähe des westlichen Viertels gefunden. Augenzeugen berichten, dass die Anwohner riefen: "Lauf, Forrest, lauf!", bevor sie vor Lachen ausbrachen und in ihr Haus rannten.',
            'name'=>'gazetteTownMultiDeaths_008',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Was für ein Riesenschlamassel: {deaths} starben letzte Nacht in der Stadt! Ein Massaker, zu dem noch der zertrümmerter Schädel eines Haustiers zu zählen ist, der in den Toren verkeilt gefunden wurde. Vermisst jemand einen Hund?',
            'name'=>'gazetteTownMultiDeaths_009',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Wir trauern und beten für die {deaths} Seelen, die uns gestern verlassen haben. Leider ist es uns nicht gelungen, die zirka {attack} Untoten aufzuhalten... Gott erbarme sich unser! Alles Pappnasen hier...',
            'name'=>'gazetteTownMultiDeaths_010',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Getrocknete Blutlachen entwürdigten heute Morgen unsere stolze Stadt. Die Horde hat uns eine Lehre erteilt und uns gemahnt, in unseren Bemühungen nicht nachzulassen.',
            'name'=>'gazetteTownMultiDeaths_011',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Der entsetzliche Angriff letzte Nacht kostete das Leben von {cadavers} Menschen. Circa <strong>{attack} Zombies</strong>! Die Gewalt, die bei diesem jüngsten Angriff zu beobachten war, lässt uns das Schlimmste erwarten...',
            'name'=>'gazetteTownMultiDeaths_012',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Unsere Maßnahmen waren angemessen und ausreichend! Leider sind ein paar Zombies "auf die Idee gekommen", (ob die Viecher denken können steht auf einem anderen Blatt...) {item} zu verwenden, um sich {location} ihren Weg in die Stadt zu bahnen. Bilanz: <strong>{deaths} getötete Bürgers</strong>.',
            'name'=>'gazetteTownMultiDeaths_013',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"transString",'name'=>'location'],
                ['type'=>"transString",'name'=>'item'],
            ],
        ],
        ['text'=>'Unsere Verteidigungen waren gestern zu knapp: Ein kleiner Zombietrupp hat unsere Stadt {location} betreten und {deaths} Bürger gemetzelt.',
            'name'=>'gazetteTownMultiDeaths_014',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"transString",'name'=>'location'],
            ],
        ],
        ['text'=>'Die abgebissenen Gliedmaßen und aus den Bäuchen quellenden Eingeweide unserer {deaths} Mitbürger sprechen eine klare Sprache: Wir müssen schnellstens unsere Verteidigung verbessern...',
            'name'=>'gazetteTownMultiDeaths_015',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Letzte Nacht war die Stadt von Zombies umzingelt! Einige schafften es, die Mauern zu durchbrechen, ein Zustrom, der uns {deaths} gute Leute kostete.',
            'name'=>'gazetteTownMultiDeaths_016',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Aus unerklärlichen Gründen ist es gestern Nacht einer kleinen Zombiegruppe gelungen, die Stadt zu betreten. <strong>{deaths} Bürger</strong> mussten dafür mit ihrem Leben bezahlen.',
            'name'=>'gazetteTownMultiDeaths_017',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Die Verluste von gestern Nacht halten sich in Grenzen. Wir hatten nur <strong>{cadavers} Tote</strong>. <strong>{citizen1}</strong> sprach den Angehörigen der Verstorbenen seinen Beileid aus und bat sich an, ihnen bei der Entsorgung der Leichen behilflich zu sein. Altruismus? Ironie?',
            'name'=>'gazetteTownMultiDeaths_018',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Keine wirklich schlechten Nachrichten von letzter Nacht, die Verluste beschränkten sich auf {deaths} Bürger. Niemand, der vermisst werden wird (laut <strong>{citizen1}</strong>)...',
            'name'=>'gazetteTownMultiDeaths_019',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Die Zombies haben gestern so richtig <strong>geschlemmt</strong>: {deaths} Einwohner <strong>wurden ihnen fast wie auf dem Silbertablett serviert</strong>. <strong>{citizen1}</strong> meinte dazu: "Ich hätte nie gedacht, dass wir so schnell als Zombiefraß enden..." Die Hölle wartet auf uns.',
            'name'=>'gazetteTownMultiDeaths_020',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Das war eine höllische Nacht. Unsere Verteidigung konnte nur einen Teil des Angriffs abwehren, sodass es mehreren Zombies gelang, {location} einzudringen. Das Ergebnis: <strong>{deaths} Tote</strong>.',
            'name'=>'gazetteTownMultiDeaths_021',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"transString",'name'=>'location'],
            ],
        ],


        // Door open deaths
        ['text'=>'Bemerkenswert: Die Tür wurde über Nacht offen gelassen. Den Aufzeichnungen zufolge ist es {cadaver1}, der dafür verantwortlich ist. Nun, es ist immer noch in Ordnung... Trotz dieses Fehlers gab es nicht allzu viele Opfer.',
            'name'=>'gazetteTownDeathsDoor_001',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> hat die Initiative ergriffen und das Tor um {randomHour} Uhr geöffnet, aber niemand hat daran gedacht, es auch wieder zu schließen. Gute Arbeit, echt...',
            'name'=>'gazetteTownDeathsDoor_002',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"num",'name'=>'randomHour'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> hat heute Nacht versucht, uns alle umzubringen. Glücklicherweise schlug sein Plan fehl, und unsere Verluste sind minimal. Ich schlage vor, wir werden ihn irgendwie los.',
            'name'=>'gazetteTownDeathsDoor_003',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'{mascot}, <strong>{citizen1}\'s</strong> {animal} ist heute Nacht durch das offene Stadttor entkommen. Ach ja, und ein paar Bürger haben wir beim Angriff auch verloren. Das Tor schließen... schonmal gehört?',
            'name'=>'gazetteTownDeathsDoor_004',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"transString",'name'=>'mascot'],
                ['type'=>"transString",'name'=>'animal'],
            ],
        ],
        ['text'=>'Es sieht so aus, als ob gestern Tag der offenen Tür in der Stadt war: <strong>{deaths} Tote</strong>! Ein großes Dankeschön an <strong>{citizen1}</strong>, der die Tore geöffnet hat, <strong>und dann vergessen hat, sie wieder zu schließen</strong>! Worauf wartet ihr noch, bevor ihr ihn verbannt?!',
            'name'=>'gazetteTownDeathsDoor_005',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Was bringen uns die ganzen Verteidigungsmaßnahmen, wenn dann das <strong>Stadttor offen</strong> steht? Wir danken <strong>{citizen1}</strong> für den Tod von {deaths} Bürgern... "vergessen"... tststs...',
            'name'=>'gazetteTownDeathsDoor_006',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Nun denn. Lasst mich euch <strong>die Grundlagen</strong> erklären. Nach 23:30 Uhr schließen wir <strong>das Stadttor</strong>. Das ist keine Raketenwissenschaft! Zum Glück gab es gestern nur minimale Verluste...',
            'name'=>'gazetteTownDeathsDoor_007',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[],
        ],
        ['text'=>'Überraschung!! Das Tor stand (mal wieder) sperrangelweit offen! Herzlichen Dank an <strong>{citizen1}</strong>: "Du hast jetzt {deaths} Bürger auf dem Gewissen, ich hoffe du kannst nicht mehr schlafen, du Versager!"',
            'name'=>'gazetteTownDeathsDoor_008',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Rote Karte für <strong>{citizen1}</strong>, der letzte Nacht vergessen hat, das Stadttor zu schließen! {deaths} Bürger sind gestorben! <strong>Doch eine Frage bleibt: was das wirklich nur ein Versehen?</strong>',
            'name'=>'gazetteTownDeathsDoor_009',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Es ist ja nett von <strong>{citizen1}</strong>, dass er sich aufopferungsvoll darum gekümmert hat, das Tor gegen {randomHour} Uhr zu öffnen, aber wenn niemand daran denkt, es danach wieder zu schließen ... Glücklicherweise sind die Verluste gering.',
            'name'=>'gazetteTownDeathsDoor_010',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'randomHour'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Und was ist mit dem Tor? Macht das niemand zu? Sollen wir wirklich alle draufgehen?',
            'name'=>'gazetteTownDeathsDoor_011',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir sollten darüber nachdenken, jemanden zu bestimmen, der das Tor <strong>schließt</strong>. Gestern ging es noch gut, kaum Verluste ... Aber heute Abend ... Ich denke lieber nicht darüber nach. Wie wäre es mit <strong>{citizen1}</strong> (der sie gestern geöffnet hat)?</strong>',
            'name'=>'gazetteTownDeathsDoor_012',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Wir müssen annehmen, dass <strong>{citizen1}</strong> vergessen hat, in der Nacht das Tor zu schließen. Das hätte uns sehr teuer zu stehen kommen können, aber zum Glück ist die Bilanz der Nacht nicht allzu ernst. Eine kleine Erhängung?',
            'name'=>'gazetteTownDeathsDoor_013',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Was nützt es, Verteidigungsanlagen zu bauen, wenn Bürger wie <strong>{citizen1}</strong> die Tore öffnen und nicht daran denken, sie vor Mitternacht zu schließen? Zum Glück gab es nur begrenzte Schäden... <strong>VERBANNUNG!</strong> Das ist alles, was ich zu sagen habe...',
            'name'=>'gazetteTownDeathsDoor_014',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Das Böse vor unseren Toren... die gestern Abend offen gelassen wurden. Eindeutiges Ergebnis: <strong>{deaths} Todesfälle</strong>. Laut dem Register war <strong>{citizen1}</strong> schuld, da er die Tore kurz vor dem Angriff offen gelassen hat. Einige sagen, dass es sich nicht um einen Akt der Fahrlässigkeit handelte...',
            'name'=>'gazetteTownDeathsDoor_015',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Manche haben gesehen, wie <strong>{citizen1}</strong> gestern Nacht kurz vor Mitternacht das Tor geöffnet hätte. Andere sagen, dass sei keinesfalls ein Unfall gewesen... Ergebnis: <strong>{deaths} Tote</strong>.',
            'name'=>'gazetteTownDeathsDoor_016',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Die Organisation der Stadt läuft wie geschmiert. Ein Beispiel: Gestern hat <strong>{citizen1}</strong> das Stadttor um {randomHour} Uhr geöffnet und niemand ist auf die Idee gekommen, es wieder zu schließen. Weiter so!',
            'name'=>'gazetteTownDeathsDoor_017',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'randomHour'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Wenn sich gestern jemand dazu bequemt hätte das Stadttor vor dem Angriff zu verriegeln, hätten wir uns die {deaths} Toten sparen können...',
            'name'=>'gazetteTownDeathsDoor_018',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Die kalte Brise letzte Nacht kam durch das Stadttor... welches <strong>niemand</strong> geschlossen hatte. Der Geruch von verfaulendem Fleisch heute Morgen, der kommt von den Bürgern, die den <strong>ultimativen Preis</strong> für diese mangelnde Organisation bezahlt haben...',
            'name'=>'gazetteTownDeathsDoor_019',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Das Stadttor wurde heute Abend geöffnet, aber nicht wieder geschlossen... In diesem Fall hattet ihr Glück, denn wir haben nicht viele Leute verloren. Zumindest niemanden von Bedeutung.',
            'name'=>'gazetteTownDeathsDoor_020',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[],
        ],
        ['text'=>'Besser hätten wir es nicht hinbekommen können! Das Stadttor wurde um {randomHour} Uhr geöffnet und blieb danach offen. Resultat: {deaths} Bürger endeten als Zombiemahlzeit.',
            'name'=>'gazetteTownDeathsDoor_021',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'randomHour'],
            ],
        ],
        ['text'=>'Was zum Teufel macht eigentlich unsere Torwachmannschaft?! <strong>{citizen1}</strong> hat gestern, ohne zu fragen, das Tor geöffnet und dadurch für ein wahres Massaker gesorgt: {deaths} Tote! Geht\'s noch?!',
            'name'=>'gazetteTownDeathsDoor_022',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Ihr könnt euch alle bei <strong>{citizen1}</strong> bedanken! Er hat <strong>das Stadtor</strong> kurz vor dem Angriff geöffnet und uns die Viecher auf den Hals gehetzt! Glücklicherweise sind wir noch einmal glimpflich davongekommen. Und weil es so lustig war, schlage ich vor, das Gleiche heute Nacht zu wiederholen... ',
            'name'=>'gazetteTownDeathsDoor_023',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Was zum Teufel macht eigentlich unsere Torwachmannschaft?! <strong>{citizen1}</strong> hat gestern, ohne zu fragen, das Tor geöffnet und dadurch für ein wahres Massaker gesorgt: {deaths} Tote! Geht\'s noch?!',
            'name'=>'gazetteTownDeathsDoor_024',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Man beachte die {deaths} unnötigen Tode, die man hätte vermeiden können, indem man gestern Abend <strong>das Stadttor wieder geschlossen</strong> hätte! Ist da jemand verantwortlich? <strong>{citizen1}</strong>?',
            'name'=>'gazetteTownDeathsDoor_025',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Wenn es nach mir ginge, hätte ich mit <strong>{citizen1}</strong> abgerechnet, der "vergessen" hat, das Stadttor zu schließen, nachdem er es geöffnet hatte. Doch die Entscheidung liegt bei euch...',
            'name'=>'gazetteTownDeathsDoor_026',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeathsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Ein Tor ist zum Öffnen und zum Schließen da, oder liegt ich da falsch? Außer ihr wollt jede Nacht {deaths} Menschen verlieren?',
            'name'=>'gazetteTownDeathsDoor_027',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Hallo? Was war gestern mit dem Stadttor los? Hat keiner mal daran gedacht, es zu schließen? Wollt ihr, dass wir alle draufgehen?!',
            'name'=>'gazetteTownDeathsDoor_028',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[],
        ],

        // Shaman Death
        ['text'=>'Außerdem wäre es gut, daran zu denken, dass der Schamane der Stadt letzte Nacht gestorben ist. Heute Abend findet eine Neuwahl statt, bei der ein neuer Kandidat aus den Reihen der Überlebenden gewählt wird.',
            'name'=>'gazetteTownDeadShaman_001',
            'type'=>GazetteEntryTemplate::TypeGazetteShamanDeath,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Halten wir kurz inne und gedenken unserem verlorenen Schamanen. Auf Wiedersehen, nutzloser Scharlatan! Wer ist motiviert, ihn zu ersetzen?',
            'name'=>'gazetteTownDeadShaman_002',
            'type'=>GazetteEntryTemplate::TypeGazetteShamanDeath,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],

        // Guide death
        ['text'=>'Unser geliebter Reiseleiter in der Außenwelt ist gestern verstorben. Heute Abend findet eine Neuwahl statt, bei der ein neuer Kandidat aus den Reihen der Überlebenden gewählt wird.',
            'name'=>'gazetteTownDeadGuide_001',
            'type'=>GazetteEntryTemplate::TypeGazetteGuideDeath,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],

        // Shaman & Guide death
        ['text'=>'Tolle Leistung gestern Abend, der Schamane und der Reiseleiter in der Außenwelt sind beide tot, daher könnte der heutige Tag etwas eintönig werden. Heute Abend findet eine Neuwahl aus den Reihen der Überlebenden statt.',
            'name'=>'gazetteTownDeadShamanGuide_001',
            'type'=>GazetteEntryTemplate::TypeGazetteGuideShamanDeath,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],

        // Suicide Death
        ['text'=>'"Auf wiedersehen, du schnöde Welt...", dachte sich wohl <i class="dagger">†</i> {cadaver1}. Jedenfalls hat er den Zombies Arbeit abgenommen und sich selbst umgebracht.',
            'name'=>'gazetteTownSuicide_001',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wir haben geahnt, dass dies passieren würde. <i class="dagger">†</i> {cadaver1} hat gestern Selbstmord begangen. Immerhin hatte dieser Bürger mehrere Tage lang seinen Kopf gegen die Wände geschlagen und Sand gegessen, anstatt seine Wasserrationen einzunehmen... Traurig.',
            'name'=>'gazetteTownSuicide_002',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wir werden nie wieder etwas von <i class="dagger">†</i> {cadaver1} hören, der sich entschlossen hat, dem Angriff der Zombies durch Selbstmord zu entkommen.',
            'name'=>'gazetteTownSuicide_003',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Niemandem war es aufgefallen, wie schlecht es <strong>† {cadaver1}</strong> ging. Von Allen im Stich gelassen, hat er es vorgezogen, <strong>seinem Leben ein Ende zu setzten</strong>, anstatt weiter mit uns zu leben...',
            'name'=>'gazetteTownSuicide_004',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Gestern ist einigen Bürgern aufgefallen, dass <strong>† {cadaver1}</strong> verdächtig still war. Anscheinend hat er es inzwischen vorgezogen, <strong>Selbstmord</strong> zu begehen.',
            'name'=>'gazetteTownSuicide_005',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Gute Nachrichten! Wir können uns alle von <strong>† {cadaver1}</strong> verabschieden, der gestern beschlossen hat, sich von seinem Leben zu trennen.',
            'name'=>'gazetteTownSuicide_006',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> soll heute Morgen die Leiche von <strong>† {cadaver1}</strong> inmitten einer Baustelle gefunden haben. Laut diesem Bürger handelte es sich um einen Selbstmord.... Nur konnte <strong>{citizen1}</strong> uns das Messer, das im Rücken der Leiche steckte, nicht erklären...',
            'name'=>'gazetteTownSuicide_007',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong>† {cadaver1}</strong> hat gestern endlich mal eine gute Entscheidung getroffen: Selbstmord zu begehen. "Besser spät als nie", kommentierte <strong>{citizen1}</strong>...',
            'name'=>'gazetteTownSuicide_007',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Gestern hat <strong>† {cadaver1}</strong> zum ersten Mal etwas richtig gemacht - allerdings auch zum letzten mal... Er beendete sein Leben auf eine... ziemlich gewaltsame Weise.',
            'name'=>'gazetteTownSuicide_008',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Multi Suicide Death
        ['text'=>'Weitere Nachrichten: <strong>{cadavers} Bürger haben gestern Selbstmord begangen</strong>. Schöner Ort zum Leben, nicht wahr?',
            'name'=>'gazetteTownMultiSuicide_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleSuicides,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Nette Welle von Selbstmorden gestern, {cadavers} Kandidaten, von denen nicht einer versagt hat. Wenigstens das haben sie geschafft...',
            'name'=>'gazetteTownMultiSuicide_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleSuicides,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'In der Stadt herrscht große Niedergeschlagenheit. Nicht weniger als <strong>{cadavers} Menschen</strong> haben sich entschlossen, <strong>ihrem Leben auf verschiedene Arten ein Ende zu setzen</strong>. Welche Freude...',
            'name'=>'gazetteTownMultiSuicide_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleSuicides,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Ich habe gestern <strong>{cadavers} Selbstmorde</strong> gezählt. Schlussendlich kommt die Gefahr vielleicht nicht von außen... <strong>Was</strong> habt ihr ihnen angetan?',
            'name'=>'gazetteTownMultiSuicide_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleSuicides,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Der Mut der <strong>{cadavers} Bürger</strong> von gestern, die es vorzogen, <strong>Selbstmord</strong> zu begehen, anstatt sich dem Angriff heute Abend zu stellen, wird gebührend gewürdigt werden.',
            'name'=>'gazetteTownMultiSuicide_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleSuicides,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Addiction Death
        ['text'=>'Ohne großes Bedauern starb <i class="dagger">†</i> {cadaver1} heute Nacht in Folge seiner Abhängigkeit. "Ganz ehrlich, das ist kein großer Verlust", kommentierte {citizen1}.',
            'name'=>'gazetteTownAddiction_001',
            'type'=>GazetteEntryTemplate::TypeGazetteAddiction,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Hanging Death
        ['text'=>'Der Gerechtigkeit ist Genüge getan, ihr habt gestern dafür gestimmt, <i class="dagger">†</i> {cadaver1} zu hängen. Hat doch Spaß gemacht, nicht wahr?',
            'name'=>'gazetteTownHanging_001',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Amüsant, <i class="dagger">†</i> {cadaver1} hat es nicht lange in der Stadt ausgehalt. Hängen auf dem öffentlichen Platz und Steinigung. Zivilisation ist schön!',
            'name'=>'gazetteTownHanging_002',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<i class="dagger">†</i> {cadaver1} wird niemanden mehr belästigen, da er mittlerweile an einem Seil schwingt... Die Gesetze der Stadt sind kein Witz!',
            'name'=>'gazetteTownHanging_003',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Es gab gestern ein großes Stadtfest. <strong><i class="dagger">†</i> {cadaver1} wurde gehängt</strong>. Endlich mal was los hier!',
            'name'=>'gazetteTownHanging_004',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Großes Melodrama gestern während der Hängung von <strong>† {cadaver1}</strong>: <strong>{citizen1}</strong> soll aus Trauer versucht haben, die Feierlichkeiten zu verhindern, was er schlussendlich durch Gewalt sowie anderes <strong>anti-soziales Verhalten</strong> erreichen wollte. Einige Nachbarn sollen empfohlen haben, auch diesen Störenfried aufzuhängen...',
            'name'=>'gazetteTownHanging_005',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die Gemeinde wollte dem Bürger <strong>† {cadaver1}</strong> den Schrecken des heutigen Angriffs ersparen, indem sie ihn tagsüber <strong>aufknüpften</strong>.',
            'name'=>'gazetteTownHanging_006',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die Stadt hatte gestern beschlossen, <strong>† {cadaver1}</strong> zu erhängen. Leider verlief die Hinrichtung nicht reibungslos. Drei Anläufe waren nötig, um ihm schließlich das Genick zu brechen.',
            'name'=>'gazetteTownHanging_007',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Anstatt die Zombies gewähren zu lassen, beschlossen die Bürger gestern, <strong>† {cadaver1}</strong> im Voraus zu eliminieren. "Wir haben uns köstlich amüsiert", berichtet <strong>{citizen1}</strong>...',
            'name'=>'gazetteTownHanging_008',
            'type'=>GazetteEntryTemplate::TypeGazetteHanging,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Multi Hanging Death
        ['text'=>'{cadavers} Bürgerinnen und Bürger haben wirklich zu viel getan. Oder nicht genug. Wie auch immer, die Gemeinschaft entschied daher, dass man sie gestern im Laufe des Tages <strong>entfernen</strong> sollte.',
            'name'=>'gazetteTownMultiHanging_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiHanging,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleHangings,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Bei uns wird versucht, alles so effizient wie möglich zu gestalten. Also dachten wir uns gestern, dass wir das <strong>Potential</strong> des Galgen voll ausnutzen müssen: {cadavers} Bürger wurden zu diesem Zweck bestimmt und aufgehängt.',
            'name'=>'gazetteTownMultiHanging_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiHanging,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleHangings,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Nette Aktion in der Stadt gestern mit der <strong>Festnahme</strong> und dem <strong>Hängen</strong> von {cadavers} Bürgern. Gerichtsverfahren? Verhandlung? Nein, keine Zeit.',
            'name'=>'gazetteTownMultiHanging_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiHanging,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleHangings,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Der Bürger <strong>{citizen}</strong> war heute Morgen überglücklich über die summarische Hinrichtung von {cadavers} "Verrätern der Gemeinschaft". Ein "Beispiel für unsere schöne partizipative Demokratie in Aktion", soll dieser Bürger kommentiert haben.',
            'name'=>'gazetteTownMultiHanging_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiHanging,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleHangingsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Das waren ein paar schöne <strong>Hängungen</strong> gestern, man kann sagen, dass ihr es faustdick hinter den Ohren habt, das ist kein Spaß...',
            'name'=>'gazetteTownMultiHanging_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiHanging,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[],
        ],

        // Chocolate Cross Death
        ['text'=>'Große Feier in der Stadt gestern, mit der <strong>Kreuzigung von </strong><strong>† {cadaver1}</strong>. Da ist mal was los und ein Schokoladenkreuz kann sich als köstlich UND nützlich erweisen.',
            'name'=>'gazetteTownCross_001',
            'type'=>GazetteEntryTemplate::TypeGazetteChocolateCross,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Multi Chocolate Cross Death
        ['text'=>'Nette Aktion in der Stadt gestern mit der <strong>Festnahme</strong> und der <strong>Kreuzigung</strong> von {cadavers} Bürgern. Gerichtsverfahren? Verhandlung? Nein, keine Zeit.',
            'name'=>'gazetteTownMultiCross_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiChocolateCross,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleCrosses,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Dehydration Death
        ['text'=>'Man kann es nicht oft genug sagen: Ab und zu müsst ihr mal etwas trinken. <i class="dagger">†</i> {cadaver1} ist das beste Beispiel, was ansonten passiert.',
            'name'=>'gazetteTownDehydration_001',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Abgesehen davon hat der Bürger <i class="dagger">†</i> {cadaver1} gestern wohl endlich verstanden, dass Wasser manchmal nützlich ist. Ein exemplarischer Tod durch Dehydrierung.',
            'name'=>'gazetteTownDehydration_002',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Anderes Thema; der Tod von <i class="dagger">†</i> {cadaver1} durch Verdurstung ist ein schlechtes Omen, findet ihr nicht auch?',
            'name'=>'gazetteTownDehydration_003',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Ein netter Versuch von <i class="dagger">†</i> {cadaver1}, der mit mir wetten wollte, dass man ohne Wasser überleben kann. Ich habe gewonnen.',
            'name'=>'gazetteTownDehydration_004',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<i class="dagger">†</i> {cadaver1} liegt seit gestern als vertrocknete Leiche in seinem Haus. Der Mangel an Wasser, was kann man erwarten. Manche Leute glauben immer noch, sie könnten darauf verzichten.',
            'name'=>'gazetteTownDehydration_005',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Multi Dehydration Death
        ['text'=>'Fürs Protokoll, es waren {cadavers} Bürger, die uns gestern Abend verlassen haben. Endgültige Dehydrierung. Ja, das Todesröcheln kam von ihnen...',
            'name'=>'gazetteTownMultiDehydration_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Anderes Thema, es waren {cadavers} Bürger, die uns gestern Abend verlassen haben. Endgültige Dehydrierung. Ja, das Todesröcheln kam von ihnen...',
            'name'=>'gazetteTownMultiDehydration_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Der Durst hat gestern {cadavers} Bürger übermannt. Ein schöner und langer Todeskampf, der direkt zu einem grausamen Tod führt. Wir haben also kein Wasser mehr? Oh oh oh...',
            'name'=>'gazetteTownMultiDehydration_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Abgesehen davon haben {cadavers} Bürger vergessen, dass man ohne Wasser nicht lange überleben kann. Morgen mehr Todesfälle durch Dehydrierung?',
            'name'=>'gazetteTownMultiDehydration_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Übrigens, Wassermangel ist teuer, {cadavers} die Bürger sind gestern in der Stadt ausgetrocknet. Oder haben sie "vergessen", ihre Ration zu nehmen? Stellt euch das mal vor!',
            'name'=>'gazetteTownMultiDehydration_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Ein anderes Thema, die Stadt trocknet langsam aus (ha ha, verstanden?). {cadavers} Bürger starben in der Nacht an Dehydrierung... Oh oh oh...',
            'name'=>'gazetteTownMultiDehydration_006',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'{cadavers} Mitbürger sind gestern von uns geschieden. Eine letale <strong>hypertone Dehydratation</strong> war die Ursache. Jaja, die Schmerzensschreie gestern Nacht, das war sie, die Dehydratation...',
            'name'=>'gazetteTownMultiDehydration_007',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleDehydrations,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Poison Death
        ['text'=>'Eindeutig! <i class="dagger">†</i> {cadaver1} starb an einer Vergiftung. Wie genau das passieren konnte, weiß niemand so recht, aber {citizen1} verhielt sich sehr verdächtig.',
            'name'=>'gazetteTownPoison_001',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wenn du dich fragst, wer <i class="dagger">†</i> {cadaver1} gestern (mit Curare) ermordet hat, neige ich stark zu {citizen1}. Stell nicht zu viele Fragen, sagen wir einfach, ich habe meine Quellen. Verbannt den Verräter!',
            'name'=>'gazetteTownPoison_002',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong>† {cadaver1} wurde ermordet</strong>. Ich war es nicht, obwohl ich schon Lust gehabt hätte... Also, wer war\'s? Raus mit der Sprache!',
            'name'=>'gazetteTownPoison_003',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die Meinungen über die Ursache für <strong>† {cadaver1}</strong> plötzlichen Tod gehen auseinander: Einige glauben, dass es sich um Selbstmord handelt, andere denken, dass <strong>{citizen1}</strong> der Mörder ist. Ich persönlich neige eher zur <strong>zweiten Hypothese</strong>...',
            'name'=>'gazetteTownPoison_004',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Neben den Zombieangriffen, müssen wir uns ab sofort auch auf Morde und Mordversuche in unseren eigenen Reihen einstellen. <strong>† {cadaver1}</strong> wurde bereits ermordert.',
            'name'=>'gazetteTownPoison_005',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Gute Nachrichten, meine Freunde! Ein Verräter hat sich in unsere Reihen geschlichen. Als ob die Toten von Mitternacht nicht schon genug wären, müssen wir uns von nun an mit einer neuen Gefahr in unseren Mauern auseinandersetzen. Gestern wurde <strong>† {cadaver1} ermordet</strong>. Vergiftung mit {poison}! Bürger, Zeit für Panik!',
            'name'=>'gazetteTownPoison_006',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'poison'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Du solltest auch einen Blick auf die Leiche von <strong>† {cadaver1}</strong> werfen. Die Umstände des Todes könnten nicht fragwürdiger sein ... Hmm? Mord? Wer hat "Mord" gesagt?',
            'name'=>'gazetteTownPoison_007',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'In der Stadt gibt es perfideere Menschen als mich. Ein Beweis dafür ist der gewaltsame Tod von <strong>† {cadaver1}</strong> gestern, der einem Mord zum Opfer fiel (Vergiftung mit {poison}, meiner Meinung nach). Es war sauber, ohne viel Dreck, obwohl es scheint dass <strong>† {cadaver1}</strong> sehr gelitten hat. Gute Arbeit, mach weiter so, Kollege.',
            'name'=>'gazetteTownPoison_008',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'poison'],
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Vorsicht ist geboten! Ein Mörder versteckt sich unter uns! Gestern wurde die Leiche von <strong>† {cadaver1}</strong> gefunden, die Opfer einer Vergiftung wurde, vermutlich {poison} (fragt mich nicht, wie ich das herausgefunden habe). Wer ist der Mörder?',
            'name'=>'gazetteTownPoison_009',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'poison'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Jemand konnte <strong>† {cadaver1}</strong> offensichtlich nicht leiden. Das <strong>Gift</strong>, welches gestern in sein Essen gemischt wurde, war äußerst effektiv.',
            'name'=>'gazetteTownPoison_010',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wenn das Wasser komisch schmeckt, bist du vielleicht kurz davor, an einer Vergiftung zu vergehen. Wie <strong>† {cadaver1}</strong> gestern, dessen <strong>verdächtiger Tod</strong> viele Fragen aufwirft ...',
            'name'=>'gazetteTownPoison_011',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'poison'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Infection Death
        ['text'=>'Zu deiner Information: Wenn du dich fragst, wie hoch das Risiko einer <strong>Infektion</strong> ist, kannst du einen Blick auf den verwesten Körper von <i class="dagger">†</i> {cadaver1} werfen, der letzte Nacht in sein Bett gestorben ist.',
            'name'=>'gazetteTownInfection_001',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<i class="dagger">†</i> {cadaver1} wird sich mehrere Stunden lang gequält haben, bevor er <strong>buchstäblich auf dem Boden verwest</strong> ist. Eine Infektion kennt keine Gnade...',
            'name'=>'gazetteTownInfection_002',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Kleine Anekdote, der Geruch in der Stadt heute Morgen war <i class="dagger">†</i> {cadaver1}, der von einer <strong>systemischen Infektion</strong> gezeichnet war. Könnte jemand die Leiche herausholen?',
            'name'=>'gazetteTownInfection_003',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Ich hoffe, niemand kam <i class="dagger">†</i> {cadaver1} zu nahe. Dieser Bürger ist über Nacht an einer <strong>systemischen Infektion</strong> gestorben. Wir alle haben seine furchtbaren <strong>Schreie der Qual</strong> gehört, machen wir uns nichts vor...',
            'name'=>'gazetteTownInfection_004',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Dieses geschwollene Ding an <i class="dagger">†</i> {cadaver1}... nun, das ist <i class="dagger">†</i> {cadaver1}. Seine Infektion verlief extrem virulent.',
            'name'=>'gazetteTownInfection_005',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die ganze Stadt freut sich über <strong>† {cadaver1}s</strong> Tod. Seine <strong>Infektion</strong> hat ihn endlich dahingerafft.',
            'name'=>'gazetteTownInfection_006',
            'type'=>GazetteEntryTemplate::TypeGazetteInfection,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Multi Infection Death
        ['text'=>'Komisch, in der Stadt riecht es wirklich nach Tod. Die <strong>tödlichen Infektionen</strong> haben letzte Nacht mehrere Bürger schwer getroffen... Wir haben also nichts in der Bank, um so etwas zu verhindern?',
            'name'=>'gazetteTownMultiInfection_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[],
        ],
        ['text'=>'Ihre Haut begann sich zu schälen, aber sie machten sich keine allzu großen Gedanken darüber... {cadavers} Bürger starben letzte Nacht an einer Infektion.',
            'name'=>'gazetteTownMultiInfection_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleInfections,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Wir sollten dafür sorgen, dass wir uns um unsere Kranken kümmern. {cadavers} Bürger sind gestern an verschiedenen Infektionen gestorben. Notfalls hängen wir die infizierten Bürger auf, dann müssen wir weniger von ihnen behandeln..',
            'name'=>'gazetteTownMultiInfection_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleInfections,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Die <strong>Krankheit</strong> brauchte nicht lange, um mehrere Bürger ({cadavers}, wie mir scheint) in der Nacht niederzustrecken. Kaum <strong>zwei Tage Inkubationszeit</strong>, und schwupps, waren sie ganz kalt.',
            'name'=>'gazetteTownMultiInfection_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleInfections,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Unsere infizierten <strong>{cadavers} Patienten</strong> haben heute Nacht endlich den Löffel abgegeben. "Nicht zu früh", kommentiert <strong>{citizen1}</strong>.',
            'name'=>'gazetteTownMultiInfection_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleInfectionsC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Wir müssen uns keine Sorgen mehr um unsere {cadavers} Kranken machen. Sie sind heute Nacht alle oder fast alle gestorben. Champagner für alle!',
            'name'=>'gazetteTownMultiInfection_006',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiInfection,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleInfections,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Reactor Death
        ['text'=> 'Mehrere Brandherde wurden im Reaktorkern gesichtet, bevor er komplett in Flammen aufging. Kurz danach wurde das gesamte Gebiet von einem grünen Blitz vollkommen verwüstet. Die wenigen Überlebenden sind innerhalb nur weniger Sekunden an Strahlenkrankheit gestorben. Was für ein Ende!',
            'name'=>'gazetteTownReactor_001',
            'type'=>GazetteEntryTemplate::TypeGazetteReactor,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],

        // Red soul death
        ['text'=>'Wenn du wissen willst, was euer Schamane wert ist, frag <i class="dagger">†</i> {cadaver1}. Er würde wahrscheinlich etwas in der Art von "dieser verf#/}M@$ Scharlatan!!" sagen.',
            'name'=>'gazetteTownRedSoul_001',
            'type'=>GazetteEntryTemplate::TypeGazetteRedSoul,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1']
            ],
        ],
        ['text'=>'Ihr müsst nicht mehr auf <strong>† {cadaver1}</strong> warten, er wird noch einige Zeit in der Wüste bleiben, nachdem er beschlossen hat, sich selbst zu erwürgen... auf nachdrückliches Anraten der gequälten Seele, die die Kontrolle über seinen Körper übernommen hat.',
            'name'=>'gazetteTownRedSoul_002',
            'type'=>GazetteEntryTemplate::TypeGazetteRedSoul,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1']
            ],
        ],

        // Multi Red soul death
        ['text'=>'Übrigens, <strong>{cadavers} Bürger</strong> die ein wenig zu vertrauensselig waren, sind auf abscheuliche Weise und durch ihre eigenen schmutzigen Hände gestorben. Hat jemand den Schamanen gesehen?!',
            'name'=>'gazetteTownMultiRedSoul_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiRedSoul,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleRedSouls,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Vanish and other Deaths
        ['text'=>'Nichts genaues weiß man nicht, auf jeden Fall hat seit geraumer Zeit niemand mehr <i class="dagger">†</i> {cadaver1} gesehen.',
            'name'=>'gazetteTownVanished_001',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong><i class="dagger">†</i> {cadaver1}</strong> ist bis jetzt noch nicht in die Stadt zurückgekehrt...',
            'name'=>'gazetteTownVanished_002',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wieder ein Punkt für die Untoten. <strong><i class="dagger">†</i> {cadaver1}</strong> gestern in der Außenwelt verschwunden.',
            'name'=>'gazetteTownVanished_003',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Keine Spur von<strong><i class="dagger">†</i> {cadaver1}</strong>. Es geht das Gerücht um, dass er gestern die Stadt verlassen hätte...',
            'name'=>'gazetteTownVanished_004',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Die Wüste wird zusehends gefährlicher! Jetzt ist auch <strong><i class="dagger">†</i> {cadaver1}</strong> verschwunden. Verlasst die Stadt bitte nicht mehr allein!',
            'name'=>'gazetteTownVanished_005',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'<strong><i class="dagger">†</i> {cadaver1}</strong> wurde in der Außenwelt im Stich gelassen. Ihr habt ihn ermordet, ihr Egoisten! Ich hoffe, dass euch das Gleiche widerfährt!',
            'name'=>'gazetteTownVanished_006',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Hat irgendjemand Neuigkeiten von <strong><i class="dagger">†</i> {cadaver1}</strong>? Er gilt seit gestern als verschwunden.',
            'name'=>'gazetteTownVanished_007',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Wir haben noch immer keine Neuigkeiten von <strong><i class="dagger">†</i> {cadaver1}</strong>. Er wollte die Nacht draußen schlafen. Die nächsten Stunden werden uns sagen, ob das eine gute Idee war oder nicht.',
            'name'=>'gazetteTownVanished_008',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Heute morgen wurde eine Nachricht an <strong><i class="dagger">†</i> {cadaver1}s</strong> Haustür gefunden, auf der zu lesen war: "Ihr braucht zum Abendessen nicht auf mich zu warten. Ich schlafe heut draußen. Hab \'ne Stelle entdeckt, an der sehr gute Gegenstände vergraben sind. Wenn alles gut läuft, bin ich morgen in der Früh wieder zurück. <strong><i class="dagger">†</i> {cadaver1}</strong>"',
            'name'=>'gazetteTownVanished_009',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        // Multi Vanished
        ['text'=>'Eine kleine Anekdote, wir wissen nicht wie, aber einer der {cadavers} Bürger, die in dieser Nacht draußen verschwanden, scheint seinen Weg zurück zur Stadt gefunden zu haben und wäre <strong>in der Nähe der nördlichen Mauer</strong> gesehen worden! Allerdings fehlten ihm die Beine, und dieser Bürger sei innerhalb weniger Minuten gestorben. Igitt.',
            'name'=>'gazetteTownMultiVanished_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Eine kleine Anekdote, wir wissen nicht wie, aber einer der {cadavers} Bürger, die in dieser Nacht draußen verschwanden, scheint seinen Weg zurück zur Stadt gefunden zu haben und wäre <strong>in der Nähe der südlichen Mauer</strong> gesehen worden! Allerdings fehlten ihm die Beine, und dieser Bürger sei innerhalb weniger Minuten gestorben. Igitt.',
            'name'=>'gazetteTownMultiVanished_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Eine kleine Anekdote, wir wissen nicht wie, aber einer der {cadavers} Bürger, die in dieser Nacht draußen verschwanden, scheint seinen Weg zurück zur Stadt gefunden zu haben und wäre <strong>in der Nähe der westlichen Mauer</strong> gesehen worden! Allerdings fehlten ihm die Beine, und dieser Bürger sei innerhalb weniger Minuten gestorben. Igitt.',
            'name'=>'gazetteTownMultiVanished_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Eine kleine Anekdote, wir wissen nicht wie, aber einer der {cadavers} Bürger, die in dieser Nacht draußen verschwanden, scheint seinen Weg zurück zur Stadt gefunden zu haben und wäre <strong>in der Nähe der östlichen Mauer</strong> gesehen worden! Allerdings fehlten ihm die Beine, und dieser Bürger sei innerhalb weniger Minuten gestorben. Igitt.',
            'name'=>'gazetteTownMultiVanished_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'{cadavers} Bürger fehlten heute Morgen beim Appell. Wir wissen, dass sie gestern die Stadt verlassen haben. Nicht einer ist zurückgekehrt - <strong>NICHT MAL EINER</strong>!',
            'name'=>'gazetteTownMultiVanished_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'{cadavers} unserer Mitbürger sind gestern Nacht wahrscheinlich in den Bäuchen der Zombies verschwunden. Jedenfalls gibt es schon seit Stunden kein Lebenszeichen mehr von ihnen...',
            'name'=>'gazetteTownMultiVanished_006',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> stand gestern am Ausguck. Heute morgen hat er uns seine Beobachtungen geschildert. Demnach hätte er mit seinem Fernglas gesehen, wie die {cadavers} Ausflügler von gestern versucht hätten, sich zu verstecken, dann aber dennoch von der Zombiemeute entdeckt wurden. Es sei ein entsetzlicher Anblick gewesen, meinte er.',
            'name'=>'gazetteTownMultiVanished_007',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanishedC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'"Ihr solltet besser hier bleiben", ermahnte <strong>{citizen1}</strong> die {cadavers} Sturköpfe, die gestern aufgebrochen sind. Wie recht er doch hatte.',
            'name'=>'gazetteTownMultiVanished_008',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanishedC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'"Die kommen doch eh nicht mehr zurück und das geht uns doch am Arsch vorbei!", meinte <strong>{citizen1}</strong> heute Morgen auf die Frage, was er über die {cadavers} Vermissten denke.',
            'name'=>'gazetteTownMultiVanished_009',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanishedC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Unsere {cadavers} tapferen Wüstenwanderer, die gestern aufgebrochen sind, um Materialien zu suchen, sind nicht mehr aufgetaucht. Ich glaube wir können sie abschreiben.',
            'name'=>'gazetteTownMultiVanished_010',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Es gibt keine Neuigkeiten von unseren {cadavers} tapferen Bürgern, die gestern in die Außenwelt aufgebrochen sind. Ich denke, wir können langsam anfangen, ihre Häuser zu plündern.',
            'name'=>'gazetteTownMultiVanished_011',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Wir haben ein paar Leute, die gestern Nacht verschwunden und bis jetzt nicht mehr aufgetaucht sind. Lasset uns beten, dass sie vielleicht noch am Leben sind, auch wenn ich glaube, dass das nicht der Fall ist...',
            'name'=>'gazetteTownMultiVanished_012',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Die {cadavers} Bürger, die gestern in die Außenwelt aufgebrochen sind, sind bis jetzt noch nicht zurückgekehrt. Wir können mit ziemlicher Sicherheit davon ausgehen, dass sie tot sind.',
            'name'=>'gazetteTownMultiVanished_013',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Bis zum jetzigen Zeitpunkt wissen wir noch nicht, wie viele Personen wir in der Außenwelt verloren haben. Unseren ersten Schätzungen zufolge, könnten es bis zu {cadavers} gewesen sein.',
            'name'=>'gazetteTownMultiVanished_014',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Von den {cadavers} Bürgern, die gestern eine Wüstenwanderung gemacht haben, fehlt weiterhin jede Spur. "Das sind ja gute Neuigkeiten!", scherzte <strong>{citizen1}</strong> ironisch.',
            'name'=>'gazetteTownMultiVanished_015',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanishedC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Da gestern niemand den Mut aufgebracht hat, unsere {cadavers} versprengten Mitbürger in der Wüste zu retten, sind diese jetzt tot. Ich hoffe ihr könnt heute Nacht ruhig schlafen.',
            'name'=>'gazetteTownMultiVanished_016',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],
        ['text'=>'Einige aus unserer Stadt haben die Nacht gestern draußen verbracht. Sie sind bis jetzt nicht zurückgekehrt. Ich glaube, es besteht nur noch wenig Hoffnung, dass wir sie lebend wiedersehen...',
            'name'=>'gazetteTownMultiVanished_017',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[],
        ],
        ['text'=>'Laut <strong>{citizen1}</strong>, der letzte Nacht {location} Wache geschoben hat, sind die {cadavers} Ausflügler von gestern noch nicht zurück. Das ist schlecht.',
            'name'=>'gazetteTownMultiVanished_018',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanishedC1,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"transString", 'name'=>'location'],
            ],
        ],
        ['text'=>'Unsere Gemeinschaft ist ein wenig geschrumpft. {cadavers} Einwohner haben es gestern Nacht nicht mehr rechtzeitig heim geschafft.',
            'name'=>'gazetteTownMultiVanished_019',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiVanished,
            'requirement'=>GazetteEntryTemplate::RequiresMultipleVanished,
            'fot' => GazetteEntryTemplate::FollowUpTypeBad,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'cadavers'],
            ],
        ],

        // Text that appears the night the town gets Devastated
        ['text'=>'Die Stadt ist nichts weiter als ein widerwärtiger Friedhof. Es war niemand hier, der den letzten Angriff der Zombies hätte aufhalten können: <strong>das Stadttor wurde aufgebrochen</strong> und <strong>liegt nun in Trümmern</strong>. {town} existiert nicht mehr...',
            'name'=>'gazetteTownLastAttack',
            'type'=>GazetteEntryTemplate::TypeGazetteNews,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"string",'name'=>'town'],
            ],
        ],

        // Day 1
        ['text'=>'<strong>{citizen1}</strong> soll gestern mehrere Stunden damit verbracht haben, in den in der Bank gelagerten Kleinigkeiten herumzuschnüffeln. Trickserei oder Dienst an der Stadt?',
            'name'=>'gazetteTownDayOne_001',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> berichtet uns Informationen, die mit Vorsicht zu genießen ist. Laut diesem Bürger soll <strong>{citizen2}</strong> nämlich gesehen worden sein, wie er den ganzen Tag über in der Nähe der Bank herumlungerte... Zu welchem Zweck? Ein Mysterium.',
            'name'=>'gazetteTownDayOne_002',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> hatte sich bislang seelenruhig aus unseren Vorräten bedient. Gestern jedoch machte ihm <strong>{citizen2}</strong> unmissverständlich klar, dass, wenn er so weiter mache, er eines Morgens nicht mehr aufwachen würde.',
            'name'=>'gazetteTownDayOne_003',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Im Register steht, wie oft sich <strong>{citizen1}</strong> an der Bank zu schaffen gemacht hat. Hat da jemand <strong>verdächtig</strong> gesagt?',
            'name'=>'gazetteTownDayOne_004',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Eine ziemliche Menge an Betrügereien von <strong>{citizen1}</strong>, der gestern in der Bank herumstöberte... Wirf einen Blick in das Register...',
            'name'=>'gazetteTownDayOne_005',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Einige fragen sich, was <strong>{citizen1}</strong> gestern den ganzen Tag <strong>in der Bank</strong> gemacht hat... Wir bemerken einige <strong>sehr</strong> verdächtige Vorgänge...',
            'name'=>'gazetteTownDayOne_006',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Die Aufzeichnungen zeigen, dass <strong>{citizen1}</strong> sich mit unseren <strong>Bankreserven</strong> gut amüsiert hat...',
            'name'=>'gazetteTownDayOne_007',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Es scheint, dass <strong>{citizen1}</strong> beschlossen hat, seine fragwürdigen Fundstücke in unserer Bank zu stapeln. Es geht nicht um <strong>Quantität</strong>, nicht wahr <strong>{citizen1}</strong> ...',
            'name'=>'gazetteTownDayOne_008',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Es gibt einige, die sich auf unsere Kosten einen Spaß machen, das sage ich euch, zum Beispiel <strong>{citizen1}</strong>, der {random} Mal vorbeikam, um eine Kleinigkeit aus der Bank zu holen... Wir verweigern uns nichts.',
            'name'=>'gazetteTownDayOne_009',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"num", 'name'=>'random'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Ich weiß nicht wie ihr das seht, aber <strong>{citizen1}</strong> hat die Bank gestern offenbar mit einem Haufen nutzlosem Dreck gefüllt.',
            'name'=>'gazetteTownDayOne_010',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Der <strong>Bank</strong> geht es ziemlich gut, vor allem dank des Beitrags von <strong>{citizen1}</strong>, der ziemlich viel mitgebracht hat...',
            'name'=>'gazetteTownDayOne_011',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Angeblich wurden der Bank gestern einige verdächtige Entnahmen gemeldet. Die Zeugenaussagen deuten darauf hin, dass es sich um <strong>{citizen1}</strong> handeln soll, einige sprechen aber auch von <strong>{citizen2}</strong>.',
            'name'=>'gazetteTownDayOne_012',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'<strong>{citizen1}</strong> ist so oft zur Bank gerannt, dass es ja irgendwann mal auffallen MUSSTE! Allein gestern waren es <strong>{random} Mal</strong>.',
            'name'=>'gazetteTownDayOne_013',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"num", 'name'=>'random'],
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Mal was anderes: ein Blick ins Bankregisterbuch hat leider Unerfreuliches offenbart. <strong>{citizen1}</strong> hat sich <strong>in der Bank ausgetobt</strong> und so allerhand <strong>mitgehen lassen</strong>...',
            'name'=>'gazetteTownDayOne_014',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Einige Einwohner wundern sich, was <strong>{citizen1}</strong> gestern den ganzen Tag in der Bank getrieben hat... Er sollte wissen, dass sein <strong>ständiges Kommen und Gehen</strong> nicht unbemerkt geblieben ist...',
            'name'=>'gazetteTownDayOne_015',
            'type'=>GazetteEntryTemplate::TypeGazetteDayOne,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
            ],
        ],

        // Devastated town
        ['text'=>'Die Stadt ist zerstört! Flieht, irh Narren!',
            'name'=>'gazetteTownDevastated',
            'type'=>GazetteEntryTemplate::TypeGazetteNews,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],

        // Wind Direction
        ['text'=>'Letzte Nacht wurde der {sector} von starken Winden heimgesucht.',
            'name'=>'gazetteWindNotice_001',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Abend gab es starke Windböen im {sector}.',
            'name'=>'gazetteWindNotice_002',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'{sector2} haben gestern ein paar heftige Sandstrürme gewütet.',
            'name'=>'gazetteWindNotice_003',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'{sector2} wurden gestern ein paar meteorologische Anomalien gesichtet.',
            'name'=>'gazetteWindNotice_004',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Ein paar Sandstürme wurden im {sector} beobachtet.',
            'name'=>'gazetteWindNotice_005',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Ungewöhnlich starke Winde haben gestern den Sand {sector} aufgewirbelt.',
            'name'=>'gazetteWindNotice_006',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Verschiedene Aufzeichnungen zeigen Wetteranomalien {sector2}.',
            'name'=>'gazetteWindNotice_007',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Abend war der gesamte {sector} Schauplatz mehrerer Wetteranomalien.',
            'name'=>'gazetteWindNotice_008',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Unsere Messungen deuten darauf hin, dass im {sector} Wetteranomalien aufgetreten sind.',
            'name'=>'gazetteWindNotice_009',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Einige vereinzelte Phänomene wurden {sector2} entdeckt.',
            'name'=>'gazetteWindNotice_010',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'In der vergangenen Nacht brach im {sector2} ein heftiger Sturm aus...',
            'name'=>'gazetteWindNotice_011',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'{sector2} wurden gestern heftige Sturmwinde beobachtet.',
            'name'=>'gazetteWindNotice_012',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Natch wurden im {sector} mehrere Sandstürme beobachtet.',
            'name'=>'gazetteWindNotice_013',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
    ];

    public static $council_entry_template_data = [

        'shaman_root_first' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst,
                CouncilEntryTemplate::CouncilRootNodeShamanFollowUpFirst,
                CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],
        
        'shaman_root_next' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                CouncilEntryTemplate::CouncilRootNodeShamanIntroNext,
                CouncilEntryTemplate::CouncilRootNodeShamanFollowUpNext,
                CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],

        'shaman_root_single' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeShamanIntroSingle,
                CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
            ],
            'variables' => [ 'config' => [ '_winner_constraint' => ['from' => '_winner'] ] ]
        ],

        'shaman_root_none' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeShamanIntroNone,
            ]
        ],

        'shaman_root_few' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootShamanFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeShamanIntroFew,
                CouncilEntryTemplate::CouncilRootNodeShamanIntroFew2,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawFew,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],

        'guide_root_first' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst,
                CouncilEntryTemplate::CouncilRootNodeGuideFollowUpFirst,
                CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],

        'guide_root_next' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGenericMCIntro,
                CouncilEntryTemplate::CouncilRootNodeGuideIntroNext,
                CouncilEntryTemplate::CouncilRootNodeGuideFollowUpNext,
                CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],

        'guide_root_single' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGuideIntroSingle,
                CouncilEntryTemplate::CouncilRootNodeShamanFinalAny,
            ],
            'variables' => [ 'config' => [ '_winner_constraint' => ['from' => '_winner'] ] ]
        ],

        'guide_root_none' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGuideIntroNone,
            ]
        ],

        'guide_root_few' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeRootGuideFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branches' => [
                CouncilEntryTemplate::CouncilRootNodeGuideIntroFew,
                CouncilEntryTemplate::CouncilRootNodeGuideIntroFew2,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawFew,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny,
                CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny,
                CouncilEntryTemplate::CouncilRootNodeGuideFinalAny,
            ],
            'variables' => [ 'config' => [ '_mc_constraint' => ['from' => '_mc'], '_winner_constraint' => ['from' => '_winner'], '_council' => ['from' => '_council?'] ] ]
        ],

        'generic_root_mc_intro' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGenericMCIntro, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGenericMCIntro]
        ],

        'shaman_root_intro_few2' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFew2, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGenericIntroFew]
        ],

        'shaman_root_intro_first' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroFirst]
        ],

        'shaman_root_intro_next' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroNext]
        ],

        'shaman_root_intro_single' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroSingle]
        ],

        'shaman_root_intro_none' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroNone]
        ],

        'shaman_root_intro_few' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanIntroFew]
        ],

        'shaman_root_follow_up_first' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeShamanFollowUpFirst,CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
        ],

        'shaman_root_follow_up_next' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
        ],

        'shaman_root_begin_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanBeginVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanBeginVoteAny,CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny]
        ],

        'shaman_root_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [2,10], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteAny,CouncilEntryTemplate::CouncilNodeGenericVoteAny]
        ],

        'shaman_root_end_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanEndVoteAny,CouncilEntryTemplate::CouncilNodeGenericEndVoteAny]
        ],

        'shaman_root_straw' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawAny,CouncilEntryTemplate::CouncilNodeGenericStrawAny]
        ],

        'shaman_root_straw_few' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawFew,CouncilEntryTemplate::CouncilNodeGenericStrawFew]
        ],

        'shaman_root_straw_response' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResponseAny]
        ],

        'shaman_root_straw_final' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawFinalAny,CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny]
        ],

        'shaman_root_straw_result' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResultAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResultAny]
        ],

        'shaman_root_straw_result_response' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanStrawResultResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,3], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny]
        ],

        'shaman_root_final' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeShamanFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeShamanFinalAny]
        ],

        'guide_root_intro_few2' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFew2, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGenericIntroFew]
        ],

        'guide_root_intro_first' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroFirst]
        ],

        'guide_root_intro_next' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroNext]
        ],

        'guide_root_intro_single' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroSingle, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroSingle]
        ],

        'guide_root_intro_none' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNone, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroNone]
        ],

        'guide_root_intro_few' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideIntroFew]
        ],

        'guide_root_follow_up_first' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroFirst, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeGuideFollowUpFirst,CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
        ],

        'guide_root_follow_up_next' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideIntroNext, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 2, 'branches' => [CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,CouncilEntryTemplate::CouncilNodeGenericFollowUpAny]
        ],

        'guide_root_begin_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideBeginVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideBeginVoteAny,CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny]
        ],

        'guide_root_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [2,10], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteAny,CouncilEntryTemplate::CouncilNodeGenericVoteAny]
        ],

        'guide_root_end_vote' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideEndVoteAny,CouncilEntryTemplate::CouncilNodeGenericEndVoteAny]
        ],

        'guide_root_straw' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawAny,CouncilEntryTemplate::CouncilNodeGenericStrawAny]
        ],

        'guide_root_straw_few' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawFew, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawFew,CouncilEntryTemplate::CouncilNodeGenericStrawFew]
        ],

        'guide_root_straw_response' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResponseAny]
        ],

        'guide_root_straw_final' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawFinalAny,CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny]
        ],

        'guide_root_straw_result' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResultAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResultAny]
        ],

        'guide_root_straw_result_response' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideStrawResultResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,3], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny]
        ],

        'guide_root_final' => [
            'semantic' => CouncilEntryTemplate::CouncilRootNodeGuideFinalAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branches' => [CouncilEntryTemplate::CouncilNodeGuideFinalAny]
        ],

        'generic_follow_up_any_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericFollowUpAny,
            'text' => 'Wieso darf hier überhaupt {mc} die Leitung übernehmen? Kann mir das mal jemand erklären?', //  Why's [MC] get to run the show anyway? That's what I wanna know!
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'mc']], 'config' => [ 'main' => ['from' => '_council?'], 'mc' => ['from' => '_mc'] ] ]
        ],
        'generic_follow_up_any_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericFollowUpAny,
            'text' => 'Warum hat dieser nichtsnutzige {mc} überhaupt das Sagen!?', // Why's that no good [MC] running the show anyway!?
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'mc']], 'config' => [ 'main' => ['from' => '_council?'], 'mc' => ['from' => '_mc'] ] ]
        ],

        'generic_follow_up_any_q_response_001' => [
            'text' => 'Noob!', // Noob!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_begin_vote_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
            'text' => 'Keine Freiwilligen?', // No volunteers?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_begin_vote_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
            'text' => 'Ein Versuch kann ja nicht schaden, vielleicht ist hier jemand wirklich verrückt genug dafür?', // Well it can't hurt to try, there might be someone crazy enough out there?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_begin_vote_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
            'text' => 'Okay... Freiwillige vor?', // Soooo... Any volunteers?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_begin_vote_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
            'text' => 'Echt jetzt?!?', // Seriously?!?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_begin_vote_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericBeginVoteAny,
            'text' => 'Irgendwer?', // Anyone?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'generic_mc_intro_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
            'text' => 'Hallo zusammen, wir müssen das hier über die Bühne bringen, also ähem, melde ich mich mehr oder weniger freiwillig als Zeremonienmeister...', // Hey there everyone, we need to get this show on the road so errrr, I relucantly volunteer as MC...
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
        ],
        'generic_mc_intro_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
            'text' => 'Da Respekt für eine effektive Kommunikation unerlässlich ist, werde ich hier der Zeremonienmeister sein.', // Respect being essential to effective communication, I'll be the master of ceremonies around here.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
        ],
        'generic_mc_intro_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
            'text' => 'Hört an, hört an!', // Hear ye hear ye!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => false ]] ] ]
        ],
        'generic_mc_intro_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericMCIntro,
            'text' => 'Hm hm ... Ich will ja kein Spielverderber sein, aber wir sind noch nicht wirklich fertig...', // Hum hum... Je veux pas faire le rabat joie, mais on en a pas vraiment terminé encore...
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc', 'flags' => [ 'same_mc' => true ]] ] ]
        ],

        'generic_intro_few_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericIntroFew, 'vocal' => false,
            'text' => 'Allen steht die Erschöpfung ins Gesicht geschrieben.', // Fatigue on the other hand is front and center...
        ],
        // THIS IS AN INTENTIONAL DUPLICATE! DO NOT REMOVE IT!
        'generic_intro_few_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericIntroFew, 'vocal' => false,
            'text' => 'Allen steht die Erschöpfung ins Gesicht geschrieben.', // Fatigue on the other hand is front and center...
        ],

        'generic_vote_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Ich denke, {voted} sollte das übernehmen, dann hätte er etwas zu tun! Was meint ihr?', //  I reckon it should be Jensine, that'd give him something to do! What' you guys think?
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'generic_vote_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Ich stimme für {voted}.', // I vote for Rammas
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'generic_vote_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Lasst uns dieses langweilige Ritual abschaffen und einfach mich auswählen! Ihr wisst alle, dass ich der perfekte Kandidat bin!', // Let's do away with this borning ritual and just pick me! You all know I'm the perfect candidate!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Ich ich ich ich ich ich!', //  Me me me me me me!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Wie spät ist es eigentlich?', // Has anyone got the time?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Können wir jetzt endlich jemanden auswählen? Ich will mich hinhauen!', // Can we just choose already? I wanna hit the hay!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_007' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Wenn wir diesen Raum neu dekorieren würden...', // You know if we re-did the decorations in this room...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_008' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Ich hab Hunger!', // I'm hungry!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_009' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Und was wäre, wenn wir wie üblich Strohhalme ziehen würden?', // And what about if we just drew straws like usual?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_010' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Hört auf, mich anzugucken!', //  Stop looking!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_011' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteAny,
            'text' => 'Hat jemand gerade einen Jugendlichen vorbeigehen sehen?', // Did someone just see a a youngster go past?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_vote_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => '+1 !', //  +1 !
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Er stinkt nach Alkohol.', // He stinks of alcohol
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Er stinkt wie die Rückseite einer...', // He stinks like the backside of a...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ich vertraue ihm nicht.', // I don't trust him.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Na das überrascht mich ja mal so gar nicht!', // Well now that doesn't surprise me one bit!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Nun, ich stimme zu, dieser Job würde sehr gut zu ihm passen.', // Well for me I agree, that job would suit him down to the ground.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_007' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ach komm schon!', // Oh man, come on!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_008' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Wir könnten ja auch warten, bis sich jemand anderes freiwillig meldet...', // We could always wait for someone else to volunteer...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_009' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Es gehört sich nicht für andere zu sprechen, {parent}.', // It ain't nice to speak for others Acedia.
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'parent']], 'config' => [ 'main' => ['from' => '_council?'], 'parent' => ['from' => '_parent'] ] ]
        ],
        'generic_vote_response_010' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Typisch für dich {parent}, immer jemand anderen vorschicken!', // Now that's just typical you Sagittaeri, always calling out someone else!
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'parent']], 'config' => [ 'main' => ['from' => '_council?'], 'parent' => ['from' => '_parent'] ] ]
        ],
        'generic_vote_response_011' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ja, ich habe genug von seinen Spielchen! Hängen wir ihn auf!', // Yeah I'm sick of his horseplay! Let's string him up!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_012' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Muss ich darauf antworten?', // Do I have to respond to that?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_013' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Du warst schon immer feige! Hinter dem Rücken der anderen reden und so!', // Do I have to respond to that?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_014' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ich habe nicht wirklich eine Meinung, aber ich werde trotzdem meinen Senf dazu geben!', // I don't really have an opinion, but I'm gonna share my 2 cents anyway!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_015' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Solange nicht ich ausgewählt werde, bin ich zufrieden.', //  As long as y'all don't pick me I'm happy!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_016' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ich möchte euch sagen, wie wenig mich das alles interessiert, aber ich habe keine Lust dazu.', //  I kind of feel like telling you all just how little I care about this, but I can't be bothered.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_017' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Es gibt nichts Schlimmeres, als einen Traum zu verfolgen, der nie in Erfüllung geht.', //  There's nothing worse than pursuing a dream that never comes to fruition.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_018' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Solange ich das nicht bin geht mir das sowas von am Arsch vorbei!', //  As long as it ain't me, I don't give a damn!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_019' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Solange ich das nicht bin, ist mir das völlig egal!', // As long as it's not me I don't give two hoots!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_020' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Also, ich fange an zu glauben, dass es mir eigentlich egal ist...', // Yeah I'm starting to think that I don't really give a damn...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_021' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ja, aber es ist entweder er oder jemand anderes.', // Yeah, but it's him or someone else.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_022' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Yeah!', // Yeah!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_023' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Guter Witz!', // That's a good one!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_024' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'looooooooool', // looooooooool
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_025' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Ach, jetzt bist du einfach nur gemein. Meinst du nicht, dass er es schon schwer genug hat, wenn er so behindert ist und so?!', // Oh now you're just being mean. Don't you think he's got it bad enough handicapped like that and all?!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_026' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Also meiner Meinung nach passt das gut zu ihm.', //  Yeah, that suits him, in my humble opinion...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_response_027' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny,
            'text' => 'Lasst uns hiermit nicht herumalbern!', // No way, don't mess around with this!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_vote_end_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
            'text' => 'Ich habe einseitig beschlossen, dass wir hier keine Zeit mehr damit verschwenden werden, über etwas völlig Unwichtiges zu streiten.', // I have decided unilaterally, that we're not going to waste any more time arguing over something of singular unimportance.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_vote_end_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
            'text' => 'Es ist jedes Mal dasselbe! Können wir nicht einfach schnell und in Ruhe eine Entscheidung treffen, ohne ihne dass alles in einem Debakel endet?', // Every time it's the same! Can't we just make a decision quickly and quietly without it turning into a debacle?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'generic_vote_end_response_a_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
            'text' => 'Die Hoffnung stirbt zuletzt...', // Hope is the last thing to die...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_end_response_a_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
            'text' => 'Der Traum von einem organisierten Treffen ist der sprichwörtliche Topf voll Gold!', // The dream of an organised meeting is the proverbial pot of gold!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_end_response_a_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
            'text' => 'Diejenigen von euch, die nach uns kommen, verhärtet nicht eure Herzen gegen uns...', // Those of you who will come after, harden not your hearts against us...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_end_response_a_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB],
            'text' => 'Wer keine Hoffnung mehr hat, kann auch nichts mehr bereuen.', //  He who has no more hope has no more regrets.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_vote_end_response_b_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
            'text' => 'Ja, halt die Klappe, Shakespeare.', // Yeah, pipe down Shakespeare
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_end_response_b_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
            'text' => 'Trottel.', // Nincompoop
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_vote_end_response_b_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseB,
            'text' => 'Zum Galgen mit ihm!', // Quick! To the gallows!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
            'text' => 'Nimm einfach einen Strohhalm und bring es hinter dich!', // Just pick a straw and get it over with already!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_straw_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
            'text' => 'Ja, ja, jetzt gib mir schon einen verfluchten Strohhalm!', //  Yeah yeah, gimme a stinking straw already!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'generic_straw_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericStrawInitAny],
            'text' => 'Ok, lasst uns wie immer Strohhalme ziehen und die Sache hinter uns bringen.', //  Ok let's draw straws as usual and get this done.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'generic_straw_few_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFew,
            'text' => 'Wir ziehen Strohhalme, wie wir es immer machen. Der Kürzeste gewinnt. Kommt schon, kommt und zieht einen Stohhalm.', // We'll do the straw like we usually do. Shorts straw gets it. Come on now, come pick a straw.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'generic_straw_init_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawInitAny,
            'text' => 'Ok, jeder nimmt sich einen Strohhalm.', // Ok everyone come take a straw.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        // THIS IS AN INTENTIONAL DUPLICATE! DO NOT REMOVE IT!
        'generic_straw_init_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawInitAny,
            'text' => 'Ok, jeder nimmt sich einen Strohhalm.', // Ok everyone come take a straw.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'generic_straw_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => ['generic_straw_response_001_r001','generic_straw_response_001_r002'],
            'text' => 'Warum werfen wir zur Abwechslung nicht mal eine Münze?', //  Why don't we toss a coin for a change?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_001_r001' => [
            'text' => 'Klar Einstein, Kopf oder Zahl zm eine Persion aus {_voted} auszuwählen.', // Yeah genius, heads or tales to pick 1 person out of 34.
            'variables' => [ 'types' => [['type'=>"num", 'name'=>'_voted']], 'config' => [ 'main' => ['from' => '_council?'], '_constraint_vote3_1' => ['from' => '_voted'], '_constraint_vote3_2' => ['from' => '_voted'], '_constraint_vote3_3' => ['from' => '_voted'] ] ]
        ],
        'generic_straw_response_001_r002' => [
            'text' => 'Hast du denn eine Münze?', // Have you got a coin?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_response_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 2, 'branches' => ['generic_straw_response_002_r001','generic_straw_response_002_r002'],
            'text' => 'Wir wählen aus wer gefressen wird, richtig?', // So we're choosing who's gonna be eaten right?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_002_r001' => [
            'text' => 'Facepalm!', //  Facepalm!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_002_r002' => [
            'text' => 'Echt jetzt?', // Seriously?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_response_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => ['generic_straw_response_003_r001','generic_straw_response_003_r002','generic_vote_end_response_b_002'],
            'text' => 'Und was machen wir bei einem Gleichstand?', // And if it's a draw then what?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_003_r001' => [
            'text' => 'Wir hängen beide und fangen nochmal von vorne an!?', // We'll hang 'em both and start again!?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_003_r002' => [
            'text' => 'Komm, lass gut sein.', // Get outta here!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_response_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny,
            'text' => 'Oh Mann! Der Einstein hier drüben hat seinen Strohhalm gefressen!', //  Oh man! Einstein over there has already eaten his straw!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_response_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeStructured,
            'branch_count' => 2, 'branches' => ['generic_straw_response_005_r001','generic_straw_response_005_r002'],
            'text' => 'Kann ich meinen Strohhalm essen wenn wir fertig sind?', // Can I eat my straw once we're done?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_005_r001' => [
            'text' => 'Wo hat er hier überhaupt Stroh her?', // Where'd he get some straw from anyway?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_005_r002' => [
            'text' => '...Wer sagt, dass das Stroh ist?...', // ...Who says it's straw?...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_response_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResponseAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => ['generic_straw_response_006_r001','generic_straw_response_006_r002'],
            'text' => 'Ihr wisst, dass ... nun, .... wie ich schon sagte ... ahhhh, auf mich hört sowieso niemand.', // You know that... well .... as I was saying ... ahhhh, nobody listens to me anyway.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_006_r001' => [
            'text' => 'Hat jemand etwas gesagt?', // Did someone say something?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_response_006_r002' => [
            'text' => 'Was?', // What's that?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'generic_straw_final_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
            'text' => '...das Ziehen der Strohhalme findet in jugendlicher Unordnung statt, wobei jeder seinen Strohhalm mit dem des Nachbarn vergleicht...', // ...the drawing of the straws takes place in juvenile disorder, each person comparing their straw with the person beside them...
        ],
        'generic_straw_final_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
            'text' => '...', // ...
        ],
        'generic_straw_final_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawFinalAny, 'vocal' => false,
            'text' => '...Und so beginnt das Strohhalmziehen...', // ...And so the drawing of the straw ensues...
        ],

        'generic_straw_result_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
            'text' => 'Er war sowieso schon ziemlich seltsam...', // He was plenty weird to start with..
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_result_response_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
            'text' => 'Pfffffff', // Pfffffff
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_result_response_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
            'text' => 'Solange er zumindest ein paar Tage durchhält... Ich möchte dieses Treffen nicht jeden Morgen wiederholen müssen!', //  As long as he lasts a couple of days... I don't want to have to redo this meeting every morning!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'generic_straw_result_response_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGenericStrawResultResponseAny,
            'text' => 'Ich wusste es!', // I knew it!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'shaman_intro_first_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroFirst,
            'text' => 'Der erste Punkt auf der Tagesordnung ist die Wahl eines neuen Scharlatans, ich meine, ähm, Schamanen! Ja, einen Schamanen... Ich meine, jede verzweifelte Stadt braucht einen Schamanen, nicht wahr?', // First order of business is electing a new charlatan, I mean, errr, Shaman! Yeah, a Shaman... I mean, every desperate town needs a Shaman don't it?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'shaman_intro_next_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
            'text' => 'Da unser Schamane heute Nacht von uns gegangen ist, müssen wir jetzt einen Ersatz wählen.', // Notre chaman nous ayant quitté cette nuit, il nous faut en choisir un autre dès à présent.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'shaman_intro_next_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
            'text' => 'Unser Schamane, den wir alle sehr, sehr, sehr vermissen (lacht), ist kürzlich verstorben. Ich beantrage, dass wir über einen Nachfolger abstimmen.', // Our dearly, dearly, dearly missed shaman (snorts of laughter) passed recently. I move that we vote on a replacement.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'shaman_intro_next_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNext,
            'text' => 'Wir alle wissen ja, wie das enden wird, zumindest die meisten von uns... Wir sollten also einen neuen Schamanen wählen, damit er sich um unsere Seelen kümmern und den letzten ersetzen kann, der, wie ich sagen muss, ein verdammt guter Voodooman war!', // Now we all know how this gonna end, least most of us do... So T say we elect a new shaman so that he can take care of our souls and replace the last one, who was, I have to say, one hell of a voodooman!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'shaman_intro_single_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroSingle,
            'text' => 'Gut, alles was ich jetzt noch zu tun habe ist mich selbst zum Schamanen zu wählen. Endlich gibt es mal ein einstimmiges Ergebnis.', // Bon, il ne me reste plus qu'à m'élire Chaman, pour une fois qu'il y a unanimité !
            'variables' => [ 'config' => [ 'main' => ['from' => '_winner'] ] ]
        ],

        'shaman_intro_none_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroNone, 'vocal' => false,
            'text' => 'Niemand ist mehr in der Stadt, keiner kann diese Versammlung halten, daher überspringen wir die Wahl zum Schamanen.', // There is noone left in town, noone to hold this assembly, so today we're just going to skip the election of the Shaman
        ],

        'shaman_intro_few_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanIntroFew,
            'text' => 'OK, es sind nicht mehr viele von uns übrig, also lasst uns das schnell hinter uns bringen.', // OK, there's not many of us left, so let's get this over and done with.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'shaman_follow_up_any_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
            'text' => 'Ich hau den Zombies ganz einfach die Schädel ein, egal ob mit oder ohne Schamanen!', // I'm gonna split some zombie skull with or without a shaman!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
            'text' => 'Ja, das sehe ich auch so. Ich meine, selbst wenn er als Hexendoktor nichts taugt, sehe ich ihn gerne in diesem lächerlichen Kostüm!', // Yeah I second that. I mean even if he's no good as a witchdoctor, I sure love seeing him in that stupid costume!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
            'text' => 'Aber wir brauchen einen Schamanen, das ist wichtig!', // But we need a shaman, it's important!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
            'text' => 'Er jagt mir eine Heidenangst ein!', // He scares the heebie-jeebies out of me!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny,
            'text' => 'Kommt schon, so verzweifelt sind wir noch nicht!', // Come on now, we're not so desperate yet!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,2], 'branches' => [
                'shaman_follow_up_any_q_response_001','shaman_follow_up_any_q_response_002','shaman_follow_up_any_q_response_003','shaman_follow_up_any_q_response_004',
                'generic_follow_up_any_q_response_001'
            ],
            'text' => 'Was genau ist überhaupt ein Schamane?', // What the hell's a Shaman anyway?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_q_response_001' => [
            'text' => 'Ah, jetzt ist er durchgedreht. Die letzte Nacht auf der Wacht war wohl zu viel...', // Ahh he's fried! One too many nights on the watch...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_q_response_002' => [
            'text' => 'Er ist unser Vertreter im Jenseits und Herr über unsere verdammten Seelen!', // He's our representative with the beyond, the master of our damned souls!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_q_response_003' => [
            'text' => 'Er ist eigentlich genau wie du, nur interessanter.', // He's just like you, but more interesting
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_any_q_response_004' => [
            'text' => 'Er ist eigentlich genau wie du, nur nützlicher.', // He's just like you but more useful
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'shaman_follow_up_next_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,
            'text' => 'Krasse Sache! Ein neuer Schamane!', // Hot damn! A new Shaman!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_follow_up_next_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFollowUpNext,
            'text' => 'Super, eine neue Wahl eines Schamanen!', // Great, a new election of the Shaman job!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'shaman_vote_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Also ich schlage unseren Freund {voted} vor, der einfach soooo gerne redet.', // Well I propose that it be our dear [randomVotedPerson] because he just looooves talkin'.
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'shaman_vote_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Ich wusste schon immer, dass {voted} sich gerne verkleidet...', //  I always knew that [randomVotedPerson] liked playing dress up..
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'shaman_vote_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny,
            'text' => 'Kann man gleichzeitig Ghul und Schamane sein?', // Can we be a ghoul and the shaman at the same time?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_vote_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Wie wäre es mit {voted}? Er hat immerhin vorhergesehen, dass {previous} etwas dämliches sagen wird...', // Why not -Sieg ried-? He did predict that DefenestrateMe was gonna say mething stupid...
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted'],['type'=>"citizen", 'name'=>'previous']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true], 'previous' => ['from' => '_siblings'] ] ]
        ],

        'shaman_vote_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanVoteResponseAny,
            'text' => 'Mit so einem Kopf wird er den bösen Blick auf sich ziehen!', // He's gonna attract the evil eye with a head like that!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'shaman_vote_end_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
            'text' => 'Ruhe! Wir müssen dieses Treffen zum Ende bringen! Immerhin haben wir eine Stadt zu verteidigen! Der Schamane ist sowieso nur hier, um uns Hoffnung zu machen', // Silence!  We've got to finish this meeting! We've got a town to defend! The shaman's only here to keep our hopes up anyway.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'shaman_straw_result_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultAny,
            'text' => 'Nun, da das erledigt ist, haben wir einen neuen Schamanen bekommen: {winner}!', //  Well now it's done and dusted, we've got ourselves a new Shaman: [Shaman]!
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'main' => ['from' => '_mc'], 'winner' => ['from' => '_winner'] ] ]
        ],

        'shaman_straw_result_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Alles in allem wäre {voted} im Nachhinein betrachtet vielleicht eine bessere Wahl gewesen.', // All things seen in hindsight maybe [randomVotedPerson] was a good choice.
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'shaman_straw_result_response_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Eigentlich wollte ich Schamane werden!', // Actually, I wanted to be the Shaman!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Oh Mann, wir sind komplett im Arsch!', // Oh man we're so screwed!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Pff, der überlebt die Nacht doch eh nicht....', // Bah, he won't make it through the night....
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Lasst uns ihn aufhängen! Wer macht mit!?', // Let's hang him! Who's with me!?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Wie buchstabiert man das eigentlich: Schamane oder Schamahne?', // How do you spell that anyway : chaman ou shaman?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_007' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Schaut doch mal, wie er schon jetzt angezogen ist... Wir werden den Unterschied gar nicht merken!', // Look at how he dresses already... We're not gonna notice the difference!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_008' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Wenn wir so wählen, bist du der nächste Kandidat...', // Well if that's how we're choosing, you're shaping up as the next candidate...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_009' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Mann, ich wollte die Vorstellung übernehmen!', // Man I was doing the introduction!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_010' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Leck mich doch!', // Get out of here!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_011' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Gepriesen sei der Schamane!', // Blessed be the Shaman!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'shaman_straw_result_response_012' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanStrawResultResponseAny,
            'text' => 'Was ist ein Schaaaar Maaane?', // What's a shaaar maaan?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'shaman_final_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeShamanFinalAny, 'vocal' => false,
            'text' => '{winner} ist zum Schamanen gewählt worden, seine zweifelhaften schamanischen Kräfte treten sofort in Kraft. Hoffentlich können sie den Bewohnern der Stadt helfen, ihr erbärmliches Schicksal zu meistern.', // Peter has been elected as the Shaman, their dubious shamanic powers take effect immeadiately. Let's hope they can help the townsfolk improve their wretched lot in life.
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'winner' => ['from' => '_winner'] ] ]
        ],

        'guide_intro_first_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroFirst,
            'text' => 'Wir brauchen einen neuen Reiseleiter für die Außenwelt im Eiltempo!', // We need a new Guide to the World Beyond on the double!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'guide_intro_next_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
            'text' => 'Da unser geliebter Reiseleiter in der Außenwelt uns heute Nacht verlassen hat, müssen wir ab sofort einen neuen wählen.', // Notre Guide de l'Outre-Monde aimé nous ayant quitté cette nuit, il nous faut en choisir un autre dès à présent.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'guide_intro_next_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
            'text' => 'Mit Traurigkeit und einem gewissen Sinn für Ironie beklagen wir den Verlust unseres Reiseleiters...', // It's with sadness, and a certain sense of irony that we lament the loss of our Guide...
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],
        'guide_intro_next_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNext,
            'text' => 'Unser Reiseleiter hat sich gestern Abend irgendwie verlaufen und kommt nicht mehr zurück! Wer möchte ihn also ersetzen?', // Our Guide somehow managed to get lost last night, and he ain't coming back! So who wants to replace him?
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'guide_intro_single_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroSingle,
            'text' => 'Gut, wenn niemand etwas dagegen einzuwenden hat, dann ernenne ich mich hiermit selbst zum Reiseleiter in der Außenwelt!', // Bon, puisque tout le monde est d'accord, je me prononce Guide de l'Outre-Monde
            'variables' => [ 'config' => [ 'main' => ['from' => '_winner'] ] ]
        ],

        'guide_intro_none_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroNone, 'vocal' => false,
            'text' => 'Niemand ist mehr in der Stadt, keiner kann diese Versammlung halten, daher überspringen wir die Wahl zum Reiseleiter in der Außenwelt.', // There is noone left in town, noone to hold this assembly, so today we're just going to skip the election of the Guide to the World Beyond
        ],

        'guide_intro_few_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideIntroFew,
            'text' => 'OK, es sind nicht mehr viele von uns übrig, also lasst uns das schnell hinter uns bringen.', // OK, there's not many of us left, so let's get this over and done with.
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'guide_follow_up_any_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
            'text' => 'Wir brauchen einen guten Reiseleiter. Eine gute Reiseleitung ist wichtig.', // You've got to have a good guide. Good guidance is important.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
            'text' => 'Es ist ziemlich offensichtlich, dass die Navigation nach den Sternen Schwachsinn ist!', // Pretty obvious that navigating by the stars is a crock!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny,
            'text' => 'Ich wäre gerne der Reiseleiter!', // I'd love to be the Guide
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [1,2], 'branches' => [
                'guide_follow_up_any_q_response_001','guide_follow_up_any_q_response_002','guide_follow_up_any_q_response_003',
                'generic_follow_up_any_q_response_001'
            ],
            'text' => 'Was ist denn ein Speiseschreiter?', // What's a guyed anyway?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_q_response_001' => [
            'text' => 'Es sind die Individuen, die uns mit Sicherheit in den sicheren Tod führen...', // They're the individual responsible for leading us surely to our certian death...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_q_response_002' => [
            'text' => 'Die sind wie du? Nur hübscher?', // They're like you? But better looking?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_any_q_response_003' => [
            'text' => 'Sie sind wie dieser Hexendoktor, nur nützlicher!', // They're like the witchdoctor guy, but useful!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'guide_follow_up_next_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
            'text' => 'Ich werde dich rächen, Kumpel! Du musst mir nur den Zombie bringen, der dich erwischt hat, dann wirst du schon sehen!', // I'll revenge you buddy! Just you bring me the zombie that got you, you'll see!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_next_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
            'text' => 'Ich bin ein bisschen traurig... Ich werde den Kerl und seinen klapprigen alten Kompass vermissen.', // I'm a bit sad... I'll miss that guy and his dodgey old compass.
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_next_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
            'text' => 'Oh, Mann! Ich habe ihm gesagt, dass er nach Osten gehen muss!', // Jeez! I told him he had to go east!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_follow_up_next_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFollowUpNext,
            'text' => 'Eine neue Wahl zum Reiseleiter durch die Außenwelt!', // Chouette une nouvelle élection de Guide de l'Outre-Monde !
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'guide_vote_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Nun, ich schlage vor, dass es unser lieber {voted} sein soll, er hat die malerischste kleine Hütte...', // Well I propose that it be our dear [randomVotedPerson] they've got the quaintest little hovel...
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'guide_vote_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => [0,2], 'branches' => [CouncilEntryTemplate::CouncilNodeGuideVoteResponseAny,CouncilEntryTemplate::CouncilNodeGenericVoteResponseAny],
            'text' => 'Ich schlage vor, dass es nicht {voted} sein sollte, da es eine zu wichtige Rolle ist, um ein solches Risiko einzugehen.', // Je propose que ce ne soit pas Anarchik, c'est un rôle trop important pour prendre un tel risque.
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],

        'guide_vote_end_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideEndVoteAny, 'mode' => CouncilEntryTemplate::CouncilBranchModeRandom,
            'branch_count' => 1, 'branches' => [CouncilEntryTemplate::CouncilNodeGenericEndVoteResponseA],
            'text' => 'Ruhe! Wir müssen dieses Treffen zu Ende bringen! Wir haben eine große Wüste zu erforschen und keinen Reiseleiter, der uns hilft!', // Silence! We've got to finish this meeting! We've got a big ol' desert to explore and no Guide to help us!
            'variables' => [ 'config' => [ 'main' => ['from' => '_mc'] ] ]
        ],

        'guide_straw_result_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultAny,
            'text' => 'So, jetzt haben wir ganz offiziell einen neuen Reiseleiter bestimmt: {winner}!', // So now we officially have our new Guide to the World Beyond: [Guide]!
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'main' => ['from' => '_mc'], 'winner' => ['from' => '_winner'] ] ]
        ],

        'guide_straw_result_response_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Hmmmm alles in allem wäre {voted} vielleicht die bessere Wahl gewesen...', // Hmmmm all things considered maybe [randomVotedPerson] would have been a better choice...
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'voted']], 'config' => [ 'main' => ['from' => '_council?'], 'voted' => ['from' => 'voted', 'consume' => true] ] ]
        ],
        'guide_straw_result_response_002' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Ich hoffe, er hält wenigstens ein paar Tage durch... Ich will nicht jeden verdammten Tag abstimmen müssen!', // Here's hoping they last a few days... Don't want to be voting every bloody day!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_003' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Weis der überhaupt, wie man aus der Stadt kommt?', // Does he even know how to get out of town?
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_004' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Und er dachte, er wäre vorher gut gewesen...', // And he thought he was good before...
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_005' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Jetzt sind wir zwar immer noch auf dem sprichwörtlichen Holzweg, aber wir haben zumindest einen Reiseleiter!', // Now we're of the proverbial creek, but we have a paddle!
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_006' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Hurra für den Reiseleiter!', // Hooray for the Guide
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_007' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Er hat schon vorher geprahlt.', // Déjà qu'il se la racontait avant..
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],
        'guide_straw_result_response_008' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideStrawResultResponseAny,
            'text' => 'Wir sind sowas von im Ar...', // Nous voilà pas dans la m****
            'variables' => [ 'config' => [ 'main' => ['from' => '_council?'] ] ]
        ],

        'guide_final_001' => [
            'semantic' => CouncilEntryTemplate::CouncilNodeGuideFinalAny, 'vocal' => false,
            'text' => '{winner} wurde zum Führer gewählt. Hoffen wir, dass er uns aus diesem Schlamassel heraushelfen kann...', // [Guide] has been elected as the Guide, let's hope they can help get us out of this mess...
            'variables' => [ 'types' => [['type'=>"citizen", 'name'=>'winner']], 'config' => [ 'winner' => ['from' => '_winner'] ] ]
        ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_gazette_templates(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Gazette Entry Templates: ' . count(static::$gazette_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$gazette_entry_template_data) );

        // Iterate over all entries
        foreach (static::$gazette_entry_template_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(GazetteEntryTemplate::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new GazetteEntryTemplate();

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setRequirement( $entry['requirement'] )
                ->setVariableTypes($entry['variableTypes'])
                ->setFollowUpType( $entry['fot'] ?? 0 )
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_council_templates(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Council Entry Templates: ' . count(static::$council_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$council_entry_template_data) * 2 );

        $cache = [];
        $index = [];

        $cache_as = function(CouncilEntryTemplate $t, $index) use (&$cache): void {
            if (!isset($cache[$index])) $cache[$index] = [];
            $cache[$index][$t->getName()] = $t;
        };

        $cache_get = null;
        $cache_get = function($index) use (&$cache, &$cache_get): array {
            if (!is_array($index)) return array_values($cache[$index] ?? []);
            $tmp = [];
            foreach ($index as $this_index) $tmp = array_merge( $tmp, $cache_get( $this_index ) );
            return array_unique( $tmp );
        };

        // Iterate over all entries
        foreach (static::$council_entry_template_data as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CouncilEntryTemplate::class)->findOneBy( ['name' => $name] );
            if ($entity === null) $entity = new CouncilEntryTemplate();

            $branch_mode = empty($entry['branches']) ? CouncilEntryTemplate::CouncilBranchModeNone : ( $entry['mode'] ?? CouncilEntryTemplate::CouncilBranchModeNone );
            $branch_count = $branch_mode === CouncilEntryTemplate::CouncilBranchModeRandom
                ? (isset( $entry['branch_count'] ) ? ( is_array($entry['branch_count']) ? $entry['branch_count'] : [$entry['branch_count'],$entry['branch_count']] ) : [1,1])
                : [0,0];

            // Set property
            $entity
                ->setName( $name )
                ->setBranchMode( $branch_mode )
                ->setBranchSizeMin( $branch_count[0] )
                ->setBranchSizeMax( $branch_count[1] )
                ->setSemantic( $entry['semantic'] ?? CouncilEntryTemplate::CouncilNodeContextOnly )
                ->setVariableTypes( isset($entry['variables']) ? ($entry['variables']['types'] ?? []) : [] )
                ->setVariableDefinitions( isset($entry['variables']) ? ($entry['variables']['config'] ?? []) : [] )
                ->setText( $entry['text'] ?? null )
                ->setVocal( isset($entry['text']) ? ($entry['vocal'] ?? true) : false )
                ->getBranches()->clear();
            ;

            $index[$entity->getName()] = [$entity, $entry['branches'] ?? []];
            $cache_as($entity, $entity->getName());
            if ( $entity->getSemantic() !== CouncilEntryTemplate::CouncilNodeContextOnly )
                $cache_as($entity, $entity->getSemantic());

            $progress->advance();
        }

        // Iterate over all entries again
        foreach ($index as list($entity,$branches)) {

            //var_dump( $entity->getName() ); echo "\n";
            //var_dump($branches); echo "\n";
            //var_dump( array_map( fn(CouncilEntryTemplate $t) => $t->getName(), $cache_get( $branches ) ) ); echo "\n";

            foreach ($cache_get( $branches ) as $branch)
                $entity->addBranch( $branch );

            $this->entityManager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Gazette Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_gazette_templates( $manager, $output );
        $output->writeln("");

        $output->writeln( '<info>Installing fixtures: Council Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_council_templates( $manager, $output );
        $output->writeln("");
    }
}
