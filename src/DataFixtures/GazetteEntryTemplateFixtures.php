<?php

namespace App\DataFixtures;

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
                ['type'=>"citizen",'name'=>'citizen1'],['type'=>"citizen",'name'=>'citizen2'],
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
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
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
        ['text'=>'Bemerkenswert: Die Tür wurde über Nacht offen gelassen. Den Aufzeichnungen zufolge ist es {cadaver1}, der dafür verantwortlich ist. Nun, es ist immer noch in Ordnung... Trotz dieses Fehlers gab es nicht allzu viele Opfer.',
            'name'=>'gazetteTownDeathsDoor_001',
            'type'=>GazetteEntryTemplate::TypeGazetteDeathWithDoorOpen,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'fot' => GazetteEntryTemplate::FollowUpTypeDoubt,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
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

        // Text that appears the night the town gets Devastated
        ['text'=>'Die Stadt ist nichts weiter als ein widerwärtiger Friedhof. Es war niemand hier, der den letzten Angriff der Zombies hätte aufhalten können: <strong>das Stadttor wurde aufgebrochen</strong> und <strong>liegt nun in Trümmern</strong>. {town} existiert nicht mehr...',
            'name'=>'gazetteTownLastAttack',
            'type'=>GazetteEntryTemplate::TypeGazetteNews,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"string",'name'=>'town'],
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
            elseif ($entity->getText() !== $entry['text']) $out->writeln("{$entity->getName()}\n\t{$entity->getText()}\n\t{$entry['text']}\n\n");

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

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Log Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_gazette_templates( $manager, $output );
        $output->writeln("");
    }
}
