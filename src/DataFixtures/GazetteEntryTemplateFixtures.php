<?php

namespace App\DataFixtures;

use App\Entity\GazetteEntryTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class GazetteEntryTemplateFixtures extends Fixture
{
    public static $gazette_entry_template_data = [
        // Gazette: Fun Texts
        ['text'=>'Gestern war ein unbedeutender Tag. Einem Gerücht zufolge wurden %citizen1% und %citizen2% dabei beobachtet, wie sie zusammen im Brunnen badeten. Wenn morgen alle mit einer Pilzinfektion flach liegen, ist ja wohl klar, an wem das lag.',
            'name'=>'gazetteFun_001',
            'type'=>GazetteEntryTemplate::TypeGazetteNews,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],
        ['text'=>'Was für ein denkwürdiger Tag! Die Zombies spielten keine Rolle mehr, nachdem %citizen1% zur Mittagszeit nackt auf der Mauer einmal um die Stadt rannte. Kommentar von %citizen2% dazu: "Der Anblick war nicht von schlechten Eltern."',
            'name'=>'gazetteFun_002',
            'type'=>GazetteEntryTemplate::TypeGazetteNews,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCitizens,
            'variableTypes'=>[
                ['type'=>"citizen", 'name'=>'citizen1'],
                ['type'=>"citizen", 'name'=>'citizen2'],
            ],
        ],

        // Gazette: No deaths in town
        ['text'=>'%citizen1% verbrachten die ganze Nacht heulend in ihrem Haus, bis zu dem Punkt, dass jeder dachte, die Zombies würden Bürger-Steaks aus ihm machen. Es stellte sich heraus, dass sie gerade einen <strong>massiven Zusammenbruch</strong> hatten. Letzte Nacht gab es keine Toten in der Stadt.',
            'name'=>'gazetteTownNoDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresOneCitizen,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'%citizen1% und %citizen2% wurden in letzter Minute gerettet, als sie sich gestern Abend bereit machten, sich in ihren Häusern <strong>zu erhängen</strong>. Kommentar: "Ich dachte, sie würden mich bei lebendigem Leib auffressen, und das wollte ich nicht mehr erleben". Im Nachhinein betrachtet war es eine schlechte Entscheidung, da es gestern Abend <strong>keine Zombies</strong> in die Stadt geschafft haben.',
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
        ['text'=>'Eine friedliche Nacht in der Stadt. Die Horde von %attack% Zombies, die letzte Nacht kam, traf einige Teile der Stadt ziemlich hart, aber es gibt nichts Bemerkenswertes zu berichten.',
            'name'=>'gazetteTownNoDeath_004',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Abgesehen davon, gut, dass letzte Nacht niemand starb. Eine Horde von fast %attack% Zombies heulte die ganze Nacht draußen, aber keiner von ihnen schaffte es, unsere Verteidigung zu durchbrechen.',
            'name'=>'gazetteTownNoDeath_005',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Nahrungsmittelknappheit bei der Horde : %attack% Zombies, und nicht einer von ihnen bekam letzte Nacht etwas zu fressen, unsere Abwehr hielt gut stand.',
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
        ['text'=>'Es sei darauf hingewiesen, dass unsere Verteidigung nicht weit von der großen Südmauer entfernt dem furchterregenden Angriff der Horden letzte Nacht standgehalten hat. Um die %attack% Zombies versuchten alles, aber keine Verluste an Menschenleben während des Angriffs!',
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
        ['text'=>'Nach dem Angriff sind keine Verluste in den Reihen zu melden (zumindest keine in der Stadt). "Ja, ich denke aber schon, dass wir morgen Nacht alle sterben werden!", so %citizen1%, ein skeptischer Bürger.',
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
        ['text'=>'Einige Bürger kamen letzte Nacht ins Schwitzen. Eine Welle von etwa %attack% Monstern versuchte, unsere Stadt zu zerstören, wenn auch ohne Erfolg.',
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
        ['text'=>'Heute Morgen feierte %citizen1% das Vereiteln der Zombiehorden von gestern Abend, indem <strong>er splitternackt durch die Straßen rannte</strong>. "Ich wollte den Anbruch dieses neuen Tages auf angemessene Weise feiern", erklärte der Bürger.',
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
        ['text'=>'Ihr hast sie letzte Nacht gehört... die Schreie, das Stöhnen. Um die %attack% Zombies herum griffen an. Diesmal konnten wir uns durchsetzen, aber morgen... morgen wird es noch schlimmer...',
            'name'=>'gazetteTownNoDeath_019',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Da gestern Abend rund %attack% Zombies vor den Toren standen, hätten wir das Schlimmste befürchten können, aber kein einziger ist reingekommen: gute Zeiten! Brecht Euch sich aber nicht den Arm, wenn Ihr Euch gegenseitig auf die Schulter klopft!',
            'name'=>'gazetteTownNoDeath_020',
            'type'=>GazetteEntryTemplate::TypeGazetteNoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttack,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'attack'],
            ],
        ],

        // Gazette: One death in town.
        ['text'=>'<i class="dagger">†</i> %cadaver1% hatte gestern Abend kein Glück. Abgesehen davon war es eine ruhige Nacht in der Stadt...',
            'name'=>'gazetteTownOneDeath_001',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Alle scheinen sich still und leise über den Tod von <i class="dagger">†</i> %cadaver1% gestern Abend zu freuen... Allerdings hat niemand erklärt, warum. %citizen1% kommentierte: "Seine Mutter war ein Hamster, und sein Vater roch nach Holunderbeeren".',
            'name'=>'gazetteTownOneDeath_002',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'citizen1'],
            ],
        ],
        ['text'=>'Jeder hörte <i class="dagger">†</i> %cadaver1% schreien, als er von den Zombies auseinander gerissen wurde. Offensichtlich versuchte niemand zu helfen. Überlebensinstinkt. Wirst du jetzt nachts schlafen können?',
            'name'=>'gazetteTownOneDeath_003',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Ich bin sicher, dass nicht nur ich der Meinung bin, dass wir eine Rechnung mit <i class="dagger">†</i> %cadaver1% zu begleichen hatten. Letztendlich scheint es also Karma gewesen zu sein, dass ausgerechnet er heute Nacht ums Leben kam.',
            'name'=>'gazetteTownOneDeath_004',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige Bürger wurden Zeugen eines seltsamen Vorfalls... Man könnte sagen, dass die Zombies gestern Abend ausschließlich für <i class="dagger">†</i> %cadaver1% gekommen sind. Sie trugen die Leiche den ganzen Weg zur Baustelle, bevor die Zerstückelung begann!',
            'name'=>'gazetteTownOneDeath_005',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],
        ['text'=>'Einige sagen, daß der Tod von <i class="dagger">†</i> %cadaver1% nicht dem Glück zu verdanken ist... das einzige Opfer gestern Abend... Könnte jemand unter uns seinen Tod provoziert haben?',
            'name'=>'gazetteTownOneDeath_006',
            'type'=>GazetteEntryTemplate::TypeGazetteOneDeath,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Gazette: Two deaths in town
        ['text'=>'Ausgangssperre gilt für alle. Auch für <i class="dagger">†</i> %cadaver1% und <i class="dagger">†</i> %cadaver2% – das haben sie nun davon.',
            'name'=>'gazetteTownTwoDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteTwoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCadavers,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],
        ['text'=>'So richtig scheint keiner über den Tod von <i class="dagger">†</i> %cadaver1% und <i class="dagger">†</i> %cadaver2% zu trauern. Sie waren wohl nicht die beliebtesten in der Stadt.',
            'name'=>'gazetteTownTwoDeaths_002',
            'type'=>GazetteEntryTemplate::TypeGazetteTwoDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresTwoCadavers,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
                ['type'=>"citizen",'name'=>'cadaver2'],
            ],
        ],

        // Gazette: Multiple deaths in town.
        ['text'=>'Eine schreckliche Nacht für die Stadt. Die lebenden Toten massakrierten %deaths% unserer Gemeinde während des Angriffs. Vielleicht möchtet ihr vor heute Abend noch einmal einen Blick auf unsere Verteidigung werfen...',
            'name'=>'gazetteTownMultiDeaths_001',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Die Zombies fanden gestern Abend an der Nordwand eine Schwäche in unserer Verteidigung... Einige Häuser hielten dem Angriff stand. Andere nicht... ... %deaths% tot. Ende der Geschichte.',
            'name'=>'gazetteTownMultiDeaths_002',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Einige Bewohner brachen heute Morgen ob der Opfer in Tränen aus. Tränen der Freude mit Sicherheit, nicht eines der %deaths% Opfer des letzten Angriffs zu sein.',
            'name'=>'gazetteTownMultiDeaths_003',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Handvoll Zombies durchbrach unsere Verteidigungsanlagen in der Nähe des nördlichen Viertels, wir haben keine Ahnung, wie... Wie es das "Glück" wollte, sind %deaths% Bürger tot, aber ihr habt seltsamerweise überlebt. ... Klingt das nicht ein wenig verdächtig?',
            'name'=>'gazetteTownMultiDeaths_004',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Eine Flutwelle von Zombies stürzte letzte Nacht gegen unsere Stadt! Bürger wurden in ihren eigenen Häusern verschlungen oder in die Wüste geschleift... Noch so eine Nacht, und wir werden nicht mehr hier sein, um darüber zu reden.',
            'name'=>'gazetteTownMultiDeaths_005',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[],
        ],
        ['text'=>'Wir müssen uns beeilen; unsere Unfähigkeit, zufriedenstellende Verteidigungsanlagen zu errichten, kostete letzte Nacht %deaths% Bürgern das Leben. Zu eurer Information: Gestern Abend wurde die Stadt von %attack% Zombies angegriffen.',
            'name'=>'gazetteTownMultiDeaths_006',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresAttackDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
                ['type'=>"num",'name'=>'attack'],
            ],
        ],
        ['text'=>'Die Verteidigungsanlagen waren gestern Abend unzureichend. %deaths% Bürger bezahlten für eure mangelnde Organisation mit ihrem Leben.',
            'name'=>'gazetteTownMultiDeaths_007',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Letzte Nacht haben es %deaths% Bürger nicht rechtzeitig nach Hause geschafft. Einige Teile von ihnen wurden in der Nähe des westlichen Viertels gefunden. Augenzeugen berichten, dass die Anwohner riefen: "Lauf, Forrest, lauf!", bevor sie vor Lachen ausbrachen und in ihr Haus rannten.',
            'name'=>'gazetteTownMultiDeaths_008',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],
        ['text'=>'Was für ein Riesenschlamassel: %deaths% starben letzte Nacht in der Stadt! Ein Massaker, zu dem noch der zertrümmerter Schädel eines Haustiers zu zählen ist, der in den Toren verkeilt gefunden wurde. Vermisst jemand einen Hund?',
            'name'=>'gazetteTownMultiDeaths_009',
            'type'=>GazetteEntryTemplate::TypeGazetteMultiDeaths,
            'requirement'=>GazetteEntryTemplate::RequiresDeaths,
            'variableTypes'=>[
                ['type'=>"num",'name'=>'deaths'],
            ],
        ],

        // Suicide Death
        ['text'=>'"Auf wiedersehen, du schnöde Welt...", dachte sich wohl <i class="dagger">†</i> %cadaver1%. Jedenfalls hat er den Zombies Arbeit abgenommen und sich selbst umgebracht.',
            'name'=>'gazetteTownSuicide_001',
            'type'=>GazetteEntryTemplate::TypeGazetteSuicide,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Addiction Death
        ['text'=>'Ohne großes Bedauern starb <i class="dagger">†</i> %cadaver1% heute Nacht in Folge seiner Abhängigkeit. "Ganz ehrlich, das ist kein großer Verlust", kommentierte %citizen1%.',
            'name'=>'gazetteTownAddiction_001',
            'type'=>GazetteEntryTemplate::TypeGazetteAddiction,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Dehydration Death
        ['text'=>'Man kann es nicht oft genug sagen: Ab und zu müsst ihr mal etwas trinken. <i class="dagger">†</i> %cadaver1% ist das beste Beispiel, was ansonten passiert.',
            'name'=>'gazetteTownDehydration_001',
            'type'=>GazetteEntryTemplate::TypeGazetteDehydration,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Poison Death
        ['text'=>'Eindeutig! <i class="dagger">†</i> %cadaver1% starb an einer Vergiftung. Wie genau das passieren konnte, weiß niemand so recht, aber %citizen1% verhielt sich sehr verdächtig.',
            'name'=>'gazetteTownPoison_001',
            'type'=>GazetteEntryTemplate::TypeGazettePoison,
            'requirement'=>GazetteEntryTemplate::RequiresOneOfEach,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'citizen1'],
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Vanish and other Deaths
        ['text'=>'Nichts genaues weiß man nicht, auf jeden Fall hat seit geraumer Zeit niemand mehr <i class="dagger">†</i> %cadaver1% gesehen.',
            'name'=>'gazetteTownVanished_001',
            'type'=>GazetteEntryTemplate::TypeGazetteVanished,
            'requirement'=>GazetteEntryTemplate::RequiresOneCadaver,
            'variableTypes'=>[
                ['type'=>"citizen",'name'=>'cadaver1'],
            ],
        ],

        // Text that appears the night the town gets Devastated
        ['text'=>'Die Stadt ist nichts weiter als ein widerwärtiger Friedhof. Es war niemand hier, der den letzten Angriff der Zombies hätte aufhalten können: <strong>das Stadttor wurde aufgebrochen</strong> und <strong>liegt nun in Trümmern</strong>. %town% existiert nicht mehr...',
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
        ['text'=>'Letzte Nacht wurde der %sector% von starken Winden heimgesucht.',
            'name'=>'gazetteWindNotice_001',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Abend gab es starke Windböen im %sector%.',
            'name'=>'gazetteWindNotice_002',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'%sector2% haben gestern ein paar heftige Sandstrürme gewütet.',
            'name'=>'gazetteWindNotice_003',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'%sector2% wurden gestern ein paar meteorologische Anomalien gesichtet.',
            'name'=>'gazetteWindNotice_004',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Ein paar Sandstürme wurden im %sector% beobachtet.',
            'name'=>'gazetteWindNotice_005',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Ungewöhnlich starke Winde haben gestern den Sand %sector% aufgewirbelt.',
            'name'=>'gazetteWindNotice_006',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Verschiedene Aufzeichnungen zeigen Wetteranomalien %sector2%.',
            'name'=>'gazetteWindNotice_007',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Abend war der gesamte %sector% Schauplatz mehrerer Wetteranomalien.',
            'name'=>'gazetteWindNotice_008',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Unsere Messungen deuten darauf hin, dass im %sector% Wetteranomalien aufgetreten sind.',
            'name'=>'gazetteWindNotice_009',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Einige vereinzelte Phänomene wurden %sector2% entdeckt.',
            'name'=>'gazetteWindNotice_010',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'In der vergangenen Nacht brach im %sector2% ein heftiger Sturm aus...',
            'name'=>'gazetteWindNotice_011',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'%sector2% wurden gestern heftige Sturmwinde beobachtet.',
            'name'=>'gazetteWindNotice_012',
            'type'=>GazetteEntryTemplate::TypeGazetteWind,
            'requirement'=>GazetteEntryTemplate::RequiresNothing,
            'variableTypes'=>[
                ['type'=>"transString",'name'=>'sector'],
                ['type'=>"transString",'name'=>'sector2'],
            ],
        ],
        ['text'=>'Gestern Natch wurden im %sector% mehrere Sandstürme beobachtet.',
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

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setRequirement( $entry['requirement'] )
                ->setVariableTypes($entry['variableTypes'])
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
