<?php

namespace App\DataFixtures;

use App\Entity\RolePlayText;
use App\Entity\RolePlayTextPage;
use App\Entity\TownClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TextFixtures extends Fixture
{
    public static $texts = [
        'arzt' => [
            'title' => 'Arztbescheinigung',
            'author' => 'Waganga',
            'content' => [
                '<h1>Arztbescheinigung</h1><p>Der uns überwiesene Bürger ist für den 6.9. zu entschuldigen. Aufgrund des Zustandes seines rechten Beines war ihm das Arbeiten auf der Baustelle nicht möglich.</p><p>Auch in den nächsten Tage ist strenge Bettruhe anzuraten, um die fortschreitende Verwesung zu stoppen.</p>',
                '<p>Sollte das Beim trotz intensiver Pflege nicht zu retten sein und wenn die Verwesung auf weitere Körperteile übergreift, ist strengst geboten den Bürger in die Wüste zum Graben zu schicken und schnellstmöglich das Tor zu schließen.</p><p>Hochachtungsvoll,</p><p>Dr. Waganga</p>',
            ],
            'lang' => 'de',
            'design' => 'stamp',
            'background' => 'stamp',
        ],
        'crema1_de' => [
            'title' => 'Auslosung',
            'author' => 'Stravingo',
            'content' => [
                '<quote>Zufluchtsort der verlorenen Hoffnungen, 19. Februar</quote><p>Wir haben schon seit Wochen nichts mehr zu essen. Wir sind hungrig, so hungrig wie in unserem Leben nie zuvor. Der Hunger nagt an uns, presst uns die Gedärme, doch wir können die Stadt nicht verlassen.</p><p>Jenseits unserer schäbigen Verteidigungen lauern sie uns auf... Ich kann nicht mehr. Ich habe keine Kraft mehr. Ich kann den Holzkohlegeruch des Kremato-Cue schon riechen.</p><p>Heute morgen haben wir jemanden ausgelost. Das Los fiel auf mich, aber das ist mir egal. Meine Mitbürger sind dreckige Heuchler, die sich mitleidig geben. Um mir "Trost" zu spenden, sagten Sie, dass ich fast nichts spüren würde. Sie hätten mir eine Flasche Bier aufgehoben - elendige Lügner! Es gibt kein Bier mehr!! Ich hab die letzte Flasche gestern Abend ausgesoffen! Ha!</p><p><em>Stravingo</em></p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'notepad',
        ],
        'sbef' => [
            'title' => 'Befehl',
            'author' => 'Nobbz',
            'content' => [
                '<quote>Befehl</quote><p>Morgen um AG YQ AG AG Zulu wird der Welpe an die Garage im Baum geliefert. Der Dosenöffner nimmt die Route über die A KL. Es wird eine Abschätzung des Kartoffel Gemüse erbeten.</p>'
            ],
            'lang' => 'de',
            'design' => 'typed',
            'background' => 'notepad',
        ],
        'dv_003' => [
            'title' => 'Befehl',
            'author' => 'Nobbz',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_004' => [
            'title' => 'Bekanntmachung: Abtrünnige',
            'author' => 'Sigma',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'bwas' => [
            'title' => 'Bekanntmachung: Wasser',
            'author' => 'Fyodor',
            'content' => [
                '<div class="hr"></div><h1>Bekanntmachung</h1><p>Der Zentralrat hat beschlossen der maßlosen Wasserverschwendung entgegen zu steuern: Ab sofort werden Wasserbezugsmarken ausgegeben!</p><p>Entgegen der Propaganda der Opposition ist der Brunnen nicht leer! Er enthält lediglich kein Wasser mehr.</p><p>Eine Gefahr für die Bevölkerung bestand zu keinem Zeitpunkt.</p><p>Ab sofort wird Wasser nur noch bei Nachweis eines außerordentlichen Bedürfnisses ausgegeben. Wasserbezugsscheine können beim Zentralrat beantragt werden.</p><p>MdZR Fyodor</p>'
            ],
            'lang' => 'de',
            'design' => 'typed',
            'background' => 'carton',
        ],
        'noel_de' => [
            'title' => 'Brief an den Weihnachtsmann',
            'author' => 'zhack',
            'content' => [
                '<p>Lieber Weihnachtsmann,</p><p>Ich war dieses Jahr ganz brav. Vor allem auch, weil meine Mama traurig ist, seitdem Papa losgezogen ist, um die bösen Monster umzubringen.</p><p>Weiß du, dass wir nicht mehr schlafen können? Diese Monster greifen uns nachts immer an und machen einen Höllenlärm.</p><p>Wenn du dieses Jahr meinem Papi was schenken könntest, wäre ich dir sehr dankbar. Er fehlt mir sehr und ich möchte, dass es ihm gut geht. Bring ihm bitte ganz viele Geschenke und diesen Brief, denn ich weiß nicht wo er ist und meine Mama will es mir nicht sagen. (Papa ich habe dich furchtbar lieb!!)</p>',
                '<p>Letztes Jahr hast du mir nicht die Schmetterlingsfee Barbie gebracht. Das ist nicht so schlimm. Wir mussten umziehen und das konntest du ja nicht wissen. Ich will keine Barbie mehr. Dafür möchte ich eine Aquasplash haben, damit Mama uns verteidigen kann, wenn die bösen Monster wieder kommen. Wir werden in der Stadt immer weniger und ich will nicht dass uns die Monster mitnehmen (Mama sagt, dass sie ganz böse sind und immer Hunger haben).</p><p>Das ist alles was ich dieses Jahr möchte. Ich habe dich sehr gern lieber Weihnachtsmann und danke dir für alles.</p><p>Vielleicht wunderst du dich warum ich eine Wasserpistole haben möchte, aber Mama sagt dass die Monster total bescheuert sind weil sie kein Wasser mögen (sie duschen wahrscheinlich nicht so oft).</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'letter',
        ],
        'lettr2_de' => [
            'title' => 'Brief an Emily',
            'author' => 'Ralain',
            'content' => [
                '<p>Liebe Schwester,</p><p>Du fehlst mir so sehr. Du weißt gar nicht was ich jetzt geben würde, dich jetzt in meine Arme schließen zu dürfen... Wir haben uns jetzt schon seit 8 Tagen nicht mehr gesehen und ich habe meine Versprechen nicht vergessen. Ich schwöre bei Gott, dass Ich dich finden werde. Koste es was es wolle. Landauf, landab laufe ich durch die Gegend, immer auf der Suche nach dieser "Rückständigen Siedlung ohne Hoffnung". Du bist dort schon gesund und heil angekommen, nicht wahr? Schon als kleines Mädchen warst du intelligenter und gewiefter als ich...  Du hast mir schon so oft aus der Patsche geholfen... was war ich nur für ein schlechter Bruder...</p><p>Heute bist es immer noch du, die mir Kraft gibt. Hab ich dir das schon mal gesagt? Wenn ich dich nicht in Sicherheit wüsste, hätte ich schon längst aufgegeben. Wenn du nicht da gewesen wärst, würde ich jetzt bestimmt nicht mehr am Leben sein.</p>',
                '<p>Emily, wenn du wüsstest, was ich alles ansehen und durchstehen musste: Unsere Familie, unsere Nachbarn, Petra, dieser Vollidiot von Daniel, Mama und auch Papa... Sie sind... sie haben sich...</p><p>Gestern bin ich in einer neuen Stadt angekommen, genauso wie viele andere auch. Sie sind alle aus dem selben Grund hier wie ich. Alle suchen sie ihre Verwandten, Eltern, Schwester, Söhne und Töchter. Eine komplett aufgelöste Mutter flehte mich heute auf der Straße an. Schluchzend und wimmernd presste sie mir ein Fotos ihres Sohnes auf die Brust und fragte mich, ob ich ihn gesehen hätte. Das hat mich ganz schön mitgenommen. Ich versuchte sie zu beruhigen, indem ich ihr über den Kopf streichelte, doch da wurde sie hysterisch und rannte überstürzt weg.</p><p>Morgen brechen die meisten von hier wieder auf, so auch ich. Wir hoffen bis dahin Straßen vom Sand befreit zu haben. Wenigstens ist die Gemeinschaft gut, jeder packt mit an. Ich habe eine Kopie dieses Briefs jemanden mitgegeben, mit dem ich hier Freunschaft geschlossen habe.</p>',
                '<p>Sein Name ist Sebastian. Ich habe ihm gesagt, dass er es dem nach Vanille duftenden Mädchen geben soll, die auch "Emmy" genannt wird. Dies ist allerdings nur für den Fall, dass mir etwas zustößt. Daran möchte ich jetzt nicht denken. </p><p>Ich liebe dich. Bis bald, meine kleine Vanilleblume.</p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'noteup',
        ],
        'nancy' => [
            'title' => 'Brief an Nancy',
            'author' => 'Zekjostov',
            'content' => [
                '<p>Liebe Nancy,</p><p>viele Grüße aus der heißen Wüste. Wie du dir sicherlich vorstellen kannst, ist es tagsüber sehr heiß hier, aber auch sehr, sehr spaßig. Unser Wüstenführer ist sehr lustig und ein wahres Genie in der Wüste. Ich weiß nicht wie er das macht, aber er kann uns abends immer super unterhalten. Er tut immer so, als ob jemand um das Lager schleichen würde, was mir und der Gruppe jedes Mal einen gehörigen Schrecken einjagt!</p><p>Heute habe ich mal das Lagerfeuer verlassen um im Zelt zu schreiben. Ob du es glaubst oder nicht, heute hat der Führer wohl die ganze Gruppe eingeweiht und nun versuchen sie mir Angst zu machen! Sie stöhnen, schreien und grunzen. Aber ich kenne das langsam und werde keine Angst bekommen. Ich wünschte du wärst hier.</p><p>In L</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'blood',
        ],
        'dv_009' => [
            'title' => 'Brief an Nelly',
            'author' => 'aera10',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'brmu1' => [
            'title' => 'Brief einer Mutter',
            'author' => 'MonochromeEmpress',
            'content' => [
                '<div class="hr"></div><center><div>An meine geliebte Tochter.</div></center>',
                '<p>Liebe Viktoria,</p><p>Bereits seit knapp zwei Monaten verbringen dein Vater, Markus, Tante Judith und Ich unseren alljährlichen Urlaub zusammen.</p><p>Trotz der Bedenken deiner Großmutter, bin ich froh, dass wir uns diesen Urlaub nicht entgehen haben lassen. Schade, dass du dich so vehement geweigert hast mitzukommen. Mit dir wäre es bestimmt noch schöner, aber ich schätze du bist einfach in dem Alter, in dem man sich etwas von seinen Eltern lösen will.</p><p>Wir sind irgendwo draußen in der Wüste und ich habe schon seit längerem die Orientierung verloren, bin aber froh, dass ich noch am Leben bin (was man von Markus nicht sagen kann!).Ich wünschte, ich wüsste, wie es dir geht. Ich hoffe und bete so sehr, dass du noch am Leben bist und nicht von den Nachbarn ausgeraubt wurdest.</p>',
                '<p>In fast jeder Stadt an der wir auf unserer Reise vorbeikamen, herrschte der Ausnahmezustand (dein Vater wurde beinahe einmal erhängt! Wenn ich wieder zuhause bin, werde ich dir das Foto mit seinem entsetzten Gesicht zeigen. :) ). Außerdem wurde jede Stadt von einem seltsamen, sprechenden Raben bewacht, der eine eigene Zeitung herausgab! Ich fragte mich oft in den vielen schlaflosen Nächten, was es mit diesem Raben auf sich hat…</p><p>Werden wir das je herausfinden?</p><p>Ich schicke dir eine Schlange mit, allerdings beißt sie ganz schön viel (Ich habe einen großen lilagrünblauen Fleck am Unterarm - schon seit 2 Tagen!). Ich habe ihr den Namen „Chantal“ gegeben, kümmer dich bitte gut um sie.</p>',
                '<p>Gerade höre ich Gegröle. Es scheint, als ob sich uns ein Zombie nähert und dein Vater rennt schon mit einem Kater bewaffnet los. Ich gehe besser und feuere den Zombie an! Schließlich habe ich mit deiner Tante Judith um eine Beruhigungsspritze gewettet! Hoffentlich erreicht dich dieser Brief irgendwann.</p><p>Ich kann noch nicht sagen, wann oder wer genau wieder zu dir zurückkommt, aber ich bleibe optimistisch. In meinem nächsten Brief, werde ich dir dann erzählen ob dein Vater den Kampf gewonnen hat!</p><p>Ich vermisse dich sehr. Küsschen.</p><p>Deine dich liebende Mutter</p>',
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'letter',
        ],
        'christ' => [
            'title' => 'Christin',
            'author' => 'Fexon',
            'content' => [
                '<p>Christin,</p><p>Ich weiß nicht, ob dies das letzte sein wird, was du von mir lesen wirst, und ich weiß nicht, ob, wenn du diesen letzten Brief in den Händen hältst, schon mehr bekannt ist, als jetzt – ich weiß nur, dass etwas Unerklärliches hier vorgeht, und die ganze Stadt seit vier ganzen Tagen unter Quarantäne steht.</p><p>Quarantäne! Soldaten stehen an provisorischen Schranken und überall stinkt es furchtbar. Sie sagen es sei eine neue, antigenetische Grippe-Variante. Niemand hier versteht, was das bedeutet.</p><p>Die Schrauben- und Mutternfabrik, die den meisten hier Arbeit gegeben hat, bevor die Grippe ausgebrochen ist, ist heute Morgen geschlossen worden – wegen Arbeitermangel… Aber wenn das hier solche Ausmaße annimmt, wie es sich derzeit abzeichnet – wofür braucht man dann Schrauben?!</p>',
                '<p>Ich wünschte, du wärst nie gefahren. Ich wünschte, du wärst hier gewesen, als Porky gestorben ist. Er war so gesund, bis zu der Nacht - noch bevor uns die erste Meldung über das Virus erreichte - als er plötzlich stark aus der Nase zu bluten begann. Es dauerte vielleicht fünf quälende Minuten. Er sah mich nur an. Gott, vorgestern war ich an der Stelle im Wald, wo ich ihn vergraben hatte, irgendetwas hatte das Loch wieder aufgerissen.</p><p>Kranke Stadt.</p><p>Man solle das Haus nicht verlassen. Also verlasse ich das Haus kaum. Die Enge der Wände, der Geruch des eigenen Atems, die Stille, die, mit muffigen Vorhängen zugezogenen Fenster und die unzumutbare Ungewissheit hinterlassen Spuren. Und Porkys leeres Körbchen. Gott, ich will nicht mit blutender Nase sterben. Ich werde die Türe niemandem öffnen. Es sollen Plünderer umgehen, sagen die Leute.</p>',
                '<p>Ich war nie ein gläubiger Mensch. Doch das, was gerade passiert, ist die Strafe für alles, was hier in der Vergangenheit geschehen ist. Eine Krankheit für eine kranke Stadt. Für eine kranke Welt.</p><p>Vielleicht sollte ich sehen, ob ich im Haus von Frau Watzek, der alten Dame von nebenan, eine Bibel finde. Die Haustür steht seit vier Tagen offen, auch wenn ich meine, sie gestern regungslos am Fenster stehen gesehen zu haben. Ob alles in Ordnung ist?</p><p>Ich denke an Dich.</p>',
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'letter',
        ],
        'coctl1_de' => [
            'title' => 'Coctails Tagebuch Teil 1',
            'author' => 'coctail',
            'content' => [
                '<p>Pantokrat hatte das Gebäude als Erster gesehen. Es war gar nicht so schlecht, zumal ich überhaupt keine Lust hatte wieder eine Nacht draußen zu schlafen.</p><p>Wir, das heißt Pantokrat, Zoby und Ich (Coctail), liefen schon seit Tagen durch diese staubige Einöde. Bei unserem Aufbruch hatten wir noch alle möglichen und unmöglichen Behälter mit Wasser gefüllt, doch es war zu wenig. Das warme Wasser verflüchtigte sich schneller als unsere Hoffnungen. Ich ertappte mich regelmäßig beim Träumen... In meiner Fantasie kam ein zugefrorener See vor, in dem wir alle badeten. Von den Zombies war da weit und breit keine Spur. Zum Glück.</p><p>Zombies sind unermüdlich und stinken zum Himmel. Glücklicherweise schaffen sie es mit ihrem schlürfenden Gang nie uns einzuholen... was sie allerdings nicht daran hindert, es immer wieder zu versuchen.</p> ',
                '<p>Tagsüber ist es leicht ihnen zu entwischen, aber nachts sind wir jedes Mal gezwungen blitzartig unsere Zelte abzubauen und überhastet zu flüchten.</p><p>Manchmal können wir sie schon von weitem erkennen. Elendige Hampelmänner sind das, das sage ich euch! Wir müssen dann noch schneller gehen und unsere Verfolger stoßen früher oder später auf die große Horde, welche jeden Tag größer wird.</p><p>Wir standen also vor diesem Gebäude. Das Erdgeschoss war ziemlich groß und es hatte einen ersten Stock. Auf dem Dach war eine Antennen zu erkennen. Skelettgerippe lagen überall um das Gebäude herum verstreut.</p><p>Ich schaute Zoby in die Augen. Es ging ihm offensichtlich nicht gut, getrocknete Blutpfropfen bedeckten seine Lippen. Zoby war von uns dreien derjenige, dem die Hitze am meisten zu schaffen machte, regelmäßig wurde ihm schwarz vor Augen.</p>',
                '<p>Ich betete insgeheim dafür, dass er noch lange durchhalten möge, denn er war für die Gruppe unersetzlich. Er konnte fast alles reparieren und war sehr erfindungsreich. Ich kann mich noch genau erinnern, als er mir diese geschärfte Blechplatte gegeben hat, "Reibe" hat er sie genannt. Ich habe sie noch immer, sie baumelt an meinem Gürtel. Für fast nichts auf dieser Welt würde ich sie eintauschen.</p><p>Die Luft flimmerte vor Hitze. Jeder Schritt war ein Qual und wir hatten nur noch das schützende Gebäude vor Augen. Langsam, ganz langsam kamen wir ihm näher...</p><p>Auf einmal fing Pantokrat zu schreien an.</p><p>Er hatte fünf Zombies entdeckt, die schnurstracks auf uns zukamen und die uns daran hinderten, das Gebäude zu erreichen.</p>',
                '<p>Ich konnte und wollte nicht mehr laufen. Ich hatte Durst, einen schrecklichen Durst. Und überhaupt: Ich hatte doch nicht studiert, um hier wie ein Tier ständig von einer Zombiehorde gehetzt zu werden.</p><p>Ich hatte die Schnauze so voll.</p><p>Seit Tagen hatte ich mich auf dieses Gebäude gefreut. Egal wie abgefuckt es war, es versprach Schutz und ein paar Stunden Durchschlafen. In der Wüste ist das unmöglich. Kein Bock mehr.</p><p>Heut Nachmittag liefen wir mal wieder in brütender Hitze als uns fünf halbverrottete Kadaver entgegentorkelten. Pantokrat und Zoby begannen sich daraufhin hektisch auszutauschen, wie wir ihnen am besten ausweichen könnten usw... </p>',
                '<p>Zoby warf eine flüchtigen Blick Richtung Sonne. Diese befand sich schon am Untergehen. Wir hatten Angst, sie vor Nachteinbruch nicht mehr abschütteln zu können.</p><p>Ich kann nicht mehr.</p><p>Um mir Mut zu machen, wollte ich mich mit meiner "Reibe" lautschreiend auf zwei Zombies stürzen, doch ich bekam keinen Laut aus meiner Kehle. Meine Stimme war weg...</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'notepad',
        ],
        'dv_012' => [
            'title' => 'Coctails Tagebuch Teil 2',
            'author' => 'coctail',
            'content' => [
                ''
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'notepad',
        ],
        'coctl3_de' => [
            'title' => 'Coctails Tagebuch Teil 3',
            'author' => 'coctail',
            'content' => [
                '<p>Ich starrte auf die Autoantenne unter mir, als ich sie hören kam. Sie gaben gutturale Laute von sich und bewegten sich langsam auf unser Gebäude zu. Bald würden sie hier sein und mit ihnen dieser strenge Madengeruch.</p><p>Die ersten Male musste ich mich übergeben, aber man gewöhnt sich an alles. Der menschliche Körper kann sich an vieles gewöhnen... selbst an Zombies...</p><p>Wie von Sinnen hämmerten sie gegen die Autohauben. Pantokrats genialer Einfall zahlt sich jetzt aus: Die Blechhauben hielten stand. Allerdings veursachten sie einen Höllenlärm, an Schlaf war diese Nacht nicht zu denken.</p>',
                '<p>Ich blieb die ganze Nacht lang auf. Kurz vor Sonnenaufgang jagte mir so ein Biest eine verdammte Angst ein! Ich war nur einen kurzen Moment unachtsam. Als ich wieder zum Fenster sah tauchte ein Kopf vor mir auf. Er hatte keinen Kiefer mehr und seine Augen waren blutrot angelaufen. Ich schrie so laut ich konnte und griff reflexartig nach meiner Reibe.</p><p>Dieses etwas kratzte mit seinen Händen gegen das Fenster und gab stöhnenden Bärenlaute von sich.</p><p>Die anderen waren zwischenzeitlich von meinem Geschrei wach geworden. Zoby stürmte ins Zimmer und fuchtelte wild mit seinen Armen.</p><p>Vor lauter Schreck hatte ich gar nicht bemerkt, dass vier Zombies eingedrungen waren. Wir mussten sie aufhalten, denn weitere Viecher würden ihnen bald folgen!</p>',
                '<p>Zoby brüllte mich an: <quote>"Die Tür, die Tür!!"</quote></p><p>Blitzartig schaute ich nach links. Die Eingangstür schwang auf und zu! Panto und Zoby stemmten sich mit aller Kraft gegen sie und versuchten sie irgendwie zu schließen, aber es gelang ihnen nicht. Hals über Kopf rannte ich los, um etwas zu finden, womit wir sie blockieren konnten.</p><quote>"Das Sofa, das Sofa! Hol das verdammte Sofa!!", schrie mich Panto an.</quote><p>Die Couch vor mich herschiebend, konnte ich aus dem Wohnzimmer bereits sehen, dass die Haustür zu splittern begann und nicht mehr lang standhalten würde.</p><p>Ich weiß nicht wie, aber ich habe die Couch ganz allein vor die Eingangstür geschleppt.</p><p>Zoby kam mir sofort zur Hilfe, sodass wir den Eingangsbereich blockieren konnten. Wir hatten ein Riesenglück, die Couch füllte den Raum zwischen Haustür und gegenüberliegender Wand passgenau. Ich glaube, dass hat uns das Leben gerettet...</p><p>Die Zombies belagerten uns noch ein paar Stunden, ohne dass es ihnen gelang einzudringen. Als wieder Ruhe herrschte, verließ Ich mit Zoby vorsichtig das Haus. Wir stellten fest, dass die Zombies den Kleintransporter genutzt hatten, um sich ihren Weg ins Haus zu bahnen. Ich dachte mir: "Das kann doch wohl nicht wahr sein! Was für eine Scheißidee, die Eingangstür mit einem Wagen zu versperren...".</p>',
                '<p>Panto stieß zu uns und kletterte in den Transporter, um ihn nach ein paar Sekunden wieder zu verlassen. Er war ganz blass. Panto war sowieso schon die ganze Zeit bleich, die ganzen Sonnenstiche und Sonnenbrände hatten ihm ziemlich zugesetzt, doch diesmal stammelte er etwas vor sich. Ein paar Zombies hätten es bis in der ersten Stock geschafft. Wir mussten handeln.</p><p>Ohne ein Wort zu sagen zog ich los, um die Alkoholflasche zu holen. Dann gönnte ich mir einen vollen Schluck und stieg auf das Dach des Kleintransporters. Pantokrat warf mir sein Feuerzeug zu.</p><p>Das Gebrüll der brennenden Zombies war noch ein paar Stunden zu hören.</p>',
                '<p>Am Abend zogen wir dann Bilanz. Pantokrat hatte das Gebäude nochmal verstärkt und den Kleintransporter vom Eingang weggeschoben. Zoby hatte auf der Lade des Kettenfahrzeugs eine Metallkabine gebaut und ich hatte noch einmal das gesamte Gebäude nach brauchbaren Gegenständen abgesucht. Ich habe dann noch ein paar Kisten gefunden. Nachts wollten wir sie aufmachen.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'notepad',
        ],
        'chief_de' => [
            'title' => 'Der Verrat',
            'author' => 'Liior',
            'content' => [
                '<p>Er hat unsere Waffen gestohlen und unsere letzten Fleischvorräte mitgenommen und dann... dann ist er abgehauen... Er hat uns ganz allein zurückgelassen und sagte uns, dass er sich in der Wüste alleine durchschlagen wollte.</p><p>Wir wissen nicht mehr weiter. Wie sollen wir uns heute Nacht vor der Zombiehorde schützen? Ein paar Leute verbringen ihre letzten Stunden in der neuen Kneipe. Wahrscheinlich denken sie, dass dieser Horror mit ein paar Schnäpsen leichter zu ertragen sei...</p><p>Wir sind hier nicht mehr sicher. Er hat all unsere Waffen mitgenommen, unser Essen... Er ist weg. Einfach so, ohne ein Wort zu sagen. Wieso? War es ein Abschied?</p>',
                '<p>Vielleicht will er einfach nur ein paar von diesen Biestern umbringen und dann wieder heimkommen?</p><p>Unser "Anführer" ist weg, wir fühlen uns verraten und belogen.</p>'
            ],
            'lang' => 'de',
            'design' => 'typed',
            'background' => 'grid',
        ],
        'bb' => [
            'title' => 'Ein Briefbündel',
            'author' => 'Ferra',
            'content' => [
                '<p>24. Februar</p>	<p>Mein Lieber,</p><p>Du weißt, wir haben schwere Zeiten durchgemacht. Es ist nicht leicht, Dich immer fortgehen zu sehen, ohne zu wissen, daß Du wiederkommst. Ich habe viel nachgedacht in der letzten Zeit und ich weiß einfach nicht, ob ich so weiterleben kann.</p><p>Ich bin hier in der Stadt so isoliert, meine Familie ist fort, nur Du bist mir geblieben. Aber immer wenn Du wieder zu den Expeditionen aufbrichst, ist es, als würdest Du mich verlassen, immer wieder.</p><p>Ich kann das einfach nicht mehr. Lass uns reden, wenn Du wieder hier bist. Es geht so nicht weiter.</p><p>Dennoch: ich liebe Dich.</p>',
                '<p>15. März</p><p>Mein Lieber,</p><p>es war so schön, Dich hier zu haben. Wir schaffen es! Gemeinsam. Wie gut zu wissen, daß dies Dein letzter Auftrag sein wird. Ich kann es nicht erwarten, Dich wiederzusehen.</p><p>Der alte Rotti will uns übrigens seine gebrauchten Geräte vermachen, es ist nicht viel, aber gut gepflegt. Damit wird die Arbeit leichter werden. Er will auch bei der Konstruktion der Wasseranlage helfen. Ich habe das Land schon vorbereitet, soweit das möglich ist. Es war hart und mein Rücken wird wohl nie wieder aufhören zu schmerzen.</p><p>Nächstes Jahr um diese Zeit können wir vielleicht schon das erste ernten! Stell Dir nur vor, wir könnten auch ein kleines Blumenbeet anlegen - und hätten immer einen Strauß Leben im Haus.</p>',
                '<p>Ich weiß, das Wasser ist knapp, aber es tut manchmal einfach gut, sich so etwas auszumalen.</p><p>Komm bald zurück!</p><p>Ich liebe Dich.</p>',
                '<p>6. April</p><p>Mein Lieber,</p><p>Deine Nachricht hat mich erreicht. Noch vier weitere Monate ohne Dich. Ich wünschte manchmal, ich könnte Dich in deine entfernte Region begleiten. Der Bau der Wasseranlage geht gut voran, es fehlen nur noch ein paar Teile. Ich halte durch, Du kennst mich ja.</p><p>Es gibt noch mehr Neuigkeiten. Ich weiß nicht, ob ich mich freuen soll, es ist so viel zu tun und alles so knapp. Vorräte und Freunde. Du wolltest immer eine kleine Familie, doch ich weiß nicht, ob ich in diesen Zeiten noch ein Kind wollen kann.</p><p>Aber mir wurde die Entscheidung abgenommen. Wir sind bald zu dritt.</p><p>Ich brauche Dich und liebe Dich, mehr als ich sagen kann.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'grid',
        ],
        'utpia1_de' => [
            'title' => 'Ein Schmierzettel',
            'author' => null,
            'content' => [
                '<p>Der Typ hatte recht. Koordinaten (ungefähr): <strike>210</strike>125 Nord 210 West. </p><p>To do:</p><ul><li>Fahrzeug (Parkplatz im Norden absuchen)</li><li>Wasser (15 Liter)</li><li>Nahrung (bei Bretov besorgen; keine infiziertes Zeug andrehen lassen)</li><li>"Zitadelle" ? Was ist das??</li></ul><p>Ich muss die <strong>B 74</strong> finden.</p><p>Der Rabe???!? Wer ist das? Rausfinden und UMBRINGEN</p><quote>Termin um 16h !!!<strong>!!!!</strong></quote><p><strong>ZITADELLE</strong> finden</p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'secret',
        ],
        'nails_de' => [
            'title' => 'Ein paar Schrauben und Muttern',
            'author' => 'totokogure',
            'content' => [
                '<p>Ich habe keine Ahnung was da draußen los ist, aber ich hab so das Gefühl, dass es um mich geht...</p><p>Im Moment sitze ich hier in meiner schützenden Baracke, aber der Menschenauflauf vor meiner Haustür wird von Tag zu Tag größer. Dabei verstehe ich gar nicht, was ich verbrochen haben soll? Ok, ich geb\'s ja zu: Ich habe mir ein paar Schrauben und ein paar Muttern aus der Bank geborgt, um meinen Rasenmäher zu reparieren, aber ich konnte ja nicht wissen, dass die Teile so wichtig sind... Da lag ne ganze Kiste von dem Zeug rum und es sah so aus, als ob sie niemand bräuchte... Na da habe ich mir ein Handvoll davon genommen.</p><p>"Hängt ihn, hängt ihn!", schallt es vor meiner Tür. Hoffentlich machen sie ihre Drohung nicht wahr... Das wäre schon zu komisch: Den Galgen, den habe nämlich ich gebaut...</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'secret',
        ],
        'letmys_de' => [
            'title' => 'Ein seltsamer Brief',
            'author' => null,
            'content' => [
                '<p>Es ist Freitag abend.<br>
                    Wieder ein ruhiger Tag.<br>
                    Was besseres kann man sich in dieser Welt hier gar nicht wünschen!<br>
                    Die Soldaten passen sehr gut auf unser Lager auf.<br>
                    Sie gehen ihrer Arbeit immer sehr professionell und ernst nach.<br>
                    Sie haben zu uns gesagt, dass sie sich auch um eure Stadt kümmern und euch sehr bald zur Hilfe kommen würden.<br>
                    <br>
                    Deshalb müsst ihr sie gebührend empfangen!<br>
                    Wir brauchen sie dringend hier. Einer mehr oder weniger, das <br>
                    macht oft den Unterschied.<br>
                    Ohne sie wären wir aufgeschmissen.<br>
                    <br>
                    Du weißt wovon ich rede.<br>
                    Wir kennen uns jetzt schon so lange!<br>
                    Ist ja nicht so, dass das der erste Brief ist, den wir uns schreiben, nicht wahr?<br>
                    Gib bitte allen Bescheid und sorge dafür, dass die notwendigen Vorbereitungen getroffen werden.<br>
                    <br>
                    <em>Dein dich liebender Bruder</em>
                    </p>'
            ],
            'lang' => 'de',
            'design' => 'typedsmall',
            'background' => 'letter',
        ],
        'ezet' => [
            'title' => 'Einkaufszettel',
            'author' => 'Zippo',
            'content' => [
                '<p><strong>Einkaufszettel: Montag</strong></p>
                    <p>3 Knöpfe (schwarz perlmuttglanz) (OK)</p>
                    <p>2 Garnspulen (OK)</p>
                    <p>2 Nadel kurz (OK)</p>
                    <p>4 Paar Socken (OK)</p>
                    <p>1 Brot (geschnitten) (OK)</p>
                    <p>2 Stück Butter (OK)</p>
                    <p>Wurstaufschnitt (OK)</p>
                    <p>3 Liter Milch (OK)</p>',
                '<p><strong>Einkaufszettel: Mittwoch</strong></p>
                    <p>2 Treibstoffkanister</p>
                    <p>Decken</p>
                    <p>Spaten</p>
                    <p>Hammer</p>
                    <p>Axt/Beil</p>
                    <p>2 Schachtel Schrauben</p>
                    <p>3 Schachtel Nägel</p>
                    <p>Holzbretter / Kisten</p>',
                '<p>Atemschutz</p>
                    <p>Zigaretten</p>
                    <p>Chlortabletten</p>
                    <p>Trinkwasser</p>
                    <p>Batterien</p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'postit',
        ],
        'fpelze' => [
            'title' => 'Faulpelze',
            'author' => null,
            'content' => [
                '<p>Hallo Cordo,</p>
                <p>Wir haben dich zu den wenigen Auserwählten der Generation der Depressiven erkoren.</p>
                <p>Es steht ein kleiner Ausflug in den Westen der Stadt steht an. Dieser Renegatenbande, die sich um Nobbius geschart hat, wird heute ein blaues Wunder erleben. In der Generation der 
                Depressiven dulden wir keine Faulpelze.</p>
                <p>Der Ausbau der Stadtmauer muss so schnell wie möglich fertig gestellt werden! Eine Stadt ohne entwicklungsfähiger Mauer ist unser sicherer Tod.</p>
                <p>Bring dein Werkzeug mit und halte dich um 23.45 am Forschungsturm bereit.</p>',
                '<p>Wir werden ihre kranken und egoistischen Gehirne rausreißen, ihre Körper schänden und sie danach unserem noch lebenden Zombie zum Fraß vorwerfen. In der Stadt wird das gut ankommen.</p>
                <p>	Die Nichtsnutze MÜSSEN beseitigt werden. ALLE.</p>
                <p>	Sprich mit niemandem darüber.</p>
                <p class="other">Ed.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'letter',
        ],
        'dv_017' => [
            'title' => 'Frys Erlebnis',
            'author' => 'Sardock4r',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'todg' => [
            'title' => 'Gedanken eines Togeweihten',
            'author' => 'Knolle',
            'content' => [
                '<p>Gedanken eines Todgeweihten</p>
                <p>Der Tod, er rückt näher! Gleichsam mit der Nacht.
                Ich höre, wie das Verderben in meine Seele lacht.
                Die Sonne, sie brennt! Gleich tut’s ihr mein Herz.
                Wie in einem Albtraum, wie als wär’s nur ein Scherz.</p>
                <p>Der Tod, er rückt näher! Gleich hat er gewonnen.
                Mein ganzes bittre Leben, gleich ist es zerronnen.
                Die Sonne, sie lacht! Gleich tut’s ihr der Tod.
                Es gibt kein Entkommen, die Nacht sie wird rot.</p>
                <p>(Verfasser unbekannt)</p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'secret',
        ],
        'glager' => [
            'title' => 'Gelbes Lager, Tag 14',
            'author' => 'Rotti',
            'content' => [
                '<p>Gelbes Lager, Tag 14</p>
                <p>Mein Kater ist verschwunden.</p>
                <p>Ich hatte ihn in meiner Truhe, nur leider habe ich es versäumt mir einen Vorhang zu bauen.
                Also waren meine Nachbarn da, während ich draußen in der Wüste für ihr Weiterleben geblutet habe und haben ihn geklaut. Seltsamerweise haben sie ihn zweimal zurückgebracht, mir sogar Nachrichten in welschen Zungen hinterlassen.
                Beim Dritten Mal war er dann endgültig weg.</p>
                <p>Wie ich später herausfand, war das der Typ, der immer die Frösche gefressen hat, später haben wir ihn aufgehängt.</p>
                <p>Den Kater habe ich nie wieder gesehen.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'secret',
        ],
        'dv_018' => [
            'title' => 'Gewinnlos',
            'author' => null,
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'ilh' => [
            'title' => 'Ich liebe sie',
            'author' => 'Kouta',
            'content' => [
                '<p>Ich liebe sie. Seitdem ich ihren Arm das erste Mal berührte, will ich sie nicht mehr loslassen. Ich liebe sie so sehr. Ich will sie auf ewig umarmen, hier, neben mir, unter unserem kleinen Felsen. Sie lächelt mich immerzu an. Manchmal frage ich 
                sie, wie lange sie schon hier ist. Doch sie antwortet mir nie. Manchmal frage ich sie, wann wir eine Stadt suchen wollen. Doch nie sagt sie ein Wort. Aber das ist mir nicht wichtig. Auch, wenn sie mir noch nie ihre Liebe gestanden hat, so weiß ich es 
                doch. Ihr friedliches Lächeln sagt es mir. Sie wacht immer, auch wenn ich tief schlafe passt sie auf mich auf. Ich frage mich, wie lang unsere Vorräte noch reichen. Ich habe ihr gesagt, dass wir eine Stadt suchen müssen. Doch sie reagiert nie, sie 
                lächelt mich immerzu an. Ich weiß, dass sie mir damit Mut machen will. Ich lasse mir nichts anmerken und lege mich einfach hin, esse einen Krumen Brot und trinke ein Schlückchen Wasser. Doch sie isst nie. Jeden Morgen ist ihre Ration unangetastet. </p>',
                '<p>Ich frage sie manchmal, warum sie nicht isst und nicht trinkt. Doch sie lächelt nur. Sie erzählt mir von ihrem Leben. Von ihren Freundinnen und schönen Spielen in ihrer Stadt. Obwohl sie nichts sagt, kann ich ihre Stimme hören. Manchmal höre ich 
                auch andere Leute. Ganz dumpf sagen sie mir, ich soll auf sie aufpassen, bis sie kommen. Ich habe sie einmal nach ihrem Namen gefragt. Doch sie lächelte mich nur an. Sie macht ein Geheimnis daraus. Sie kommen sicher bald, habe ich ihr gesagt. Sie hat 
                nicht geantwortet, doch ich weiß, dass sie glücklich ist. Ich habe ihr einmal meine Liebe gestanden. Es war mir sehr peinlich, denn ich dachte, sie lacht mich vielleicht aus. Doch sie lag friedlich da und hat mich angelächelt. Ich weiß, dass sie sich 
                sehr gefreut hat. Bald sind sie da. Ich höre sie ganz deutlich. Meine Eltern und Bewohner meines Dorfes. Nicht nur nachts, auch am Tag sprechen sie mir Mut zu. Ich sehe sie nicht, aber sie sind bei uns. Sie werden uns retten. Und dann werde ich mit 
                ihr Spielen, wie ich es ihr versprochen habe. Ich liebe sie. In ihrem weißen Kleid ist wunderschön. Ich liebe sie so sehr.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'letter',
        ],
        'wratte' => [
            'title' => 'In Bier geschmorte Ratte',
            'author' => 'Akasha',
            'content' => [
                '<div class="hr"></div>
                <center>In Bier geschmorte Ratte</center>
                <ul>
                <li>1 Ratte, küchenfertig</li>
                <li>Starke Gewürze</li>
                <li>Öl (soweit vorhanden)</li>
                <li>2 Stück verdächtiges Gemüse</li>
                <li>1 Flasche Bier</li>
                <li>1 Ration Wasser</li>
                </ul>',
                '<p>Das verdächtige Gemüse schälen und in Stücke schneiden. Die küchenfertige Ratte je nach Geschmack mit den scharfen Gewürzen einreiben und in einem heißen Topf von allen Seiten gut anbraten. Das Gemüse zugeben und ebenfalls für ein paar Minuten 
                mitbraten. Mit Wasser und Bier ablöschen und alles zum Kochen bringen. Den Topf abdecken und bei geringer Hitze fünf Stunden lang sanft köcheln lassen. Durch die lange Garzeit wird das Fleisch einfach butterzart.</p>'
            ],
            'lang' => 'de',
            'design' => 'typed',
            'background' => 'grid',
        ],
        'ketkat' => [
            'title' => 'Kettensäge & Kater',
            'author' => 'TuraSatana',
            'content' => [
                '<h1>Kettensäge:</h1>
                <p>Falls du eine Batterie entdeckst,</p>
                <p>Und du sie in die Lampe steckst</p>
                <p>Kannst du mit Licht im Schlaf verrecken...</p>
                <p>Oder dir einen Vibrator in [fehlender Text] stecken!</p>
                <p>Doch willst du der König der Wüste sein</p>
                <p>So steck sie in die Kettensäge rein !</p>',
                '<h1>Großer knuddeliger Kater:</h1>
                <p>So süß, so brav und doch so wild</p>
                <p>Der Kater weiss gut wie man killt !</p>
                <p>Und falls du Abends hungrig bist</p>
                <p>Koch dir das Kätzchen und nimm einen Biss.</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'carton',
        ],
        'delir1_de' => [
            'title' => 'Makabre Warnung',
            'author' => 'coctail',
            'content' => [
                '<div class="hr"></div>
                <p>Sie sind überall, überall sag ich euch! Sie haben riesige Klauen und immer Hunger. Unstillbarer und unersättlicher Hunger. Fleisch, frisches Fleisch, immer nur Fleisch wollen sie. Doch das ist noch nicht das Schlimmste! Lange nicht! Das Schlimmste ist, dass ihr nicht tot seid, wenn sie euch beißen! Ihr seid nicht tot, nein, nein...  ihr vegetiert so lange vor euch hin bis ihr so werdet wie sie...</p>'
            ],
            'lang' => 'de',
            'design' => 'written',
            'background' => 'blood',
        ],
        'raya' => [
            'title' => 'Mein bester Freund KevKev',
            'author' => 'Rayalistic',
            'content' => [
                '<p>Liebes Tagebuch</p>
                <p>Heute ist der 2. Februar 2010, genau, mein Geburtstag! Die Kollegen haben mir deswegen eine feine Flasche Marinostov auf die Seite gelegt.</p>
                <p>Dennoch ist es ein düsterer Tag für mich, mein bester Freund "KevKev" ist heute Nacht gestorben. Das Tor war bereits geschlossen und ich war am Ende meiner Kräfte. Deswegen habe ich dann 500mg Twionid geschluckt, doch der Riegel war bereits fest 
                verkeilt. Ich hörte ihn schreien und weinen, es war schrecklich. Auf seinen Willen habe ich ihm unseren Batteriewerfer mit der letzten Batterie rausgeworfen. Die Batterie war nicht für die Zombies gedacht. Das Geräusch werde ich nie Vergessen... 
                <strong>pflogg</strong>!</p>',
                '<p>Heute Morgen lag nur noch der Batteriewerfer vor dem Tor. Ich vermisse ihn so sehr! Die Angst, das Leid und die Umstände sind unerträglich, deswegen habe ich mich entschlossen das ganze mit meinem Vodka und einer Zyanidkapsel zu beenden. Es ist 
                das beste für mich.</p>
                <p>Den einzige Grund hier zu bleiben gab es heute zu Mittag, armer Flauschi! Ich mach mich jetzt auf den Weg in das verlassene Haus 5km von hier, da werde ich niemandem zur Last fallen.
                KevKev ich komme!</p>
                <p>Machs gut liebes Tagebuch,</p> 
                <p>Dein Rayalistic</p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'notepad',
        ],
        'mertxt' => [
            'title' => 'Merkwürdiger Text',
            'author' => 'Moyen',
            'content' => [
                '<div class="hr"></div>
                <p></p><p>An Coctail:</p>
                <p>MHSZJOLMHLOYAL</p><p></p>'
            ],
            'lang' => 'de',
            'design' => 'small',
            'background' => 'notepad',
        ],
        'dv_024' => [
            'title' => 'Mitteilung',
            'author' => 'DBDevil',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_025' => [
            'title' => 'Morsecode (21.Juni)',
            'author' => 'zhack',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_026' => [
            'title' => 'Mysteriöse Befunde - Tote weisen menschliche Bissspuren auf',
            'author' => null,
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_027' => [
            'title' => 'Papierfetzen',
            'author' => 'gangster',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_028' => [
            'title' => 'Post-It',
            'author' => 'Sunsky',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_029' => [
            'title' => 'Rabe, schwarz',
            'author' => 'accorexel',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_030' => [
            'title' => 'Richards Tagebuch',
            'author' => 'Cronos',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_031' => [
            'title' => 'Schmerzengels Überlebensregeln',
            'author' => 'Schmerzengel',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_032' => [
            'title' => 'Sprinkleranlage im Eigenbau',
            'author' => 'Tycho',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_033' => [
            'title' => 'Seite 62 eines Buches',
            'author' => 'kozi',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_034' => [
            'title' => 'Sicherer Unterschlupf',
            'author' => 'Loadim',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_035' => [
            'title' => 'Sie nennen sie Zombies - Politisch korrekter Umgang mit Vermindert Lebenden',
            'author' => 'accorexel',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_036' => [
            'title' => 'Twinoidetikett',
            'author' => null,
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_037' => [
            'title' => 'Warnhinweis an zukünftige Wanderer',
            'author' => 'coctail',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_038' => [
            'title' => 'WG',
            'author' => null,
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        'dv_039' => [
            'title' => 'Zahlen',
            'author' => 'Nomad',
            'content' => [
                ''
            ],
            'lang' => 'de'
        ],
        /**
         * FRENCH ROLE PLAY TEXTS
         */
        "oldboy" => [
            "title" => "A toi, vieux compagnon...",
            "author" => "Sekhmet",
            "content" => [
                '<p><em>À toi, vieux compagnon, qui nous quittas trop tôt,<br />
Nous avons tout tenté pour te sauver la vie,<br />
Nous avons combattu des dizaines de zombies,<br />
	Mais nous n\'avons pas pu vaincre le manque d\'eau.</em></p>

<p><em>À toi, vieux compagnon, qui gis sous cette terre,<br />
Même si cette tombe n\'est faite que de bois,<br />
Même si le vent, la mort, le temps l\'effacera,<br />
	Nous n\'oublierons jamais cette dernière prière.</em></p>

<p><em>À toi, vieux compagnon, qui peut-être ce soir,<br />
Te relèveras comme les autres morts-vivants,<br />
Semant la panique, la peur et les tourments,<br />
	La terreur et la mort, l\'effroi, le désespoir.</em></p>

<p><em>À toi, vieux compagnon, qui cessas de souffrir,<br />
Puisse ton âme, au moins, partir d\'ici en paix,<br />
Loin de ce monde maudit, cruel et sans pitié,<br />
	Et que reste en nos coeurs, longtemps, ton souvenir.</em></p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "alime" => [
            "title" => "Alimentation saine",
            "author" => "Darkhan",
            "content" => [
                '>
                <p>COALITIONS RUSTRES - Journal de bord J7</p>
                <p>Ce matin, on a retrouvé mort dans son taudis celui qui pillait la banque de toute la bouffe. Chez lui, faute de ventilation, il flottait une épouvantable odeur de gaz. Au début, ça nous a pas vraiment aidé pour deviner les raisons de son décès … jusqu\'à ce que deux d\'entre nous, venus retirer son cadavre, tombent malades à leur tour. En fait, c\'est quand cette odeur de méthane a disparu qu\'on a compris que l\'homme était mort à cause de son alimentation exclusivement à base de fayots en conserve. </p>'
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "white"
        ],
        "citya1" => [
            "title" => "Annonce : astrologie",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Suite aux attaques récentes, l\'horoscope matinal de Radio Survivant ne concernera que 7 signes astrologiques au lieu des 9 habituels. De plus, Natacha sera remplacé par Roger. Adieu Natacha.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "citya3" => [
            "title" => "Annonce : banquier",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Le gardien de la banque vous informe qu\'il n\'accepte plus les tickets de rationnement. Quelques faussaires amateurs ont crus bon de profiter de sa myopie en copiant des tickets à la main.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "citya5" => [
            "title" => "Annonce : catapulte",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Les ouvriers du secteur 2 ont tentés de créer une catapulte pour expulser les cadavres. Un chat errant "volontaire" a participé aux tests. Malheureusement, il a "atterrit" contre le mur d\'enceinte.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "citya4" => [
            "title" => "Annonce : concert",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Le groupe Hord\'Zik a improvisé un concert hier soir. Les citoyens ont beaucoup appréciés, tout comme les zombies qui ont attaqués en cadence et détruit partiellement le mur sud.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "citya2" => [
            "title" => "Annonce : puits",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Nous rappelons aux plaisantins du Bloc E qu\'il est formellement interdit de jeter des zombies dans le puits. La fumée en résultant est trop proche du signal annonçant l\'évacuation d\'urgence.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "cityb1" => [
            "title" => "Annonce : séparatistes",
            "author" => "Sigma",
            "content" => [
                '<div class="hr"></div>
                <h1>Annonce publique</h1>
                <p>Des citoyens séparatistes avaient tentés de se barricader dans une caverne au sud de la ville. Un éclaireur a rapporté qu\'ils avaient tous perdus la tête. Au sens propre.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "news2" => [
            "title" => "Article - Meurtre sauvage",
            "author" => "anonyme",
            "content" => [
                '<small>(début de l\'article en page 1)</small>
<p>[...] Le couple retrouvé mort dans leur cuisine portait en effet des blessures évoquant des "morsures" selon une source proche des autorités.</p>
<p>Ce drame porte le nombre de cas à 9 dans notre région, soit 16 personnes retrouvées mortes dans des circonstances similaires. Si la thèse du tueur en série reste la plus probable, certains confrères n\'hésitent plus à relayer la théorie d\'une attaque de bête : les premières analyses auraient en effet révélé la présence d\'ADN humain sous une forme altérée. Ce dernier fait restant pour l\'heure à confirmer, les autorités ayant démenti ces informations.</p>
<h1>La course aux ressources de l\'Arctique</h1>'
            ],
            "lang" => "fr",
            "background" => "news",
            "design" => "news"
        ],
        "news3" => [
            "title" => "Article - Nouveau cas de cannibalisme",
            "author" => "anonyme",
            "content" => [
                '<h1>Nouveau cas de cannibalisme en Hongrie</h1>
                <hr />
                <p>Selon les autorités hongroises, quatre individus, trois hommes et une femme âgés de 24 à 30 ans, auraient été abattus au terme d\'une longue course-poursuite dans les rues de Kalocsa.</p>
                <p>Le groupe, signalé à la police par un riverain, avait été aperçu une première fois la veille au soir en train de dévorer un jeune homme qu\'ils avaient roués de coups, avant de prendre la fuite. La police appelle à la plus grande vigilance face à la recrudescence des cas de démences similaires.</p>
                <small>Lire la suite de l\'article en page 6</small>'
            ],
            "lang" => "fr",
            "background" => "news",
            "design" => "news"
        ],
        "autop1" => [
            "title" => "Autopsie d'un rat (partie 1 sur 3)",
            "author" => "sanka",
            "content" => [
                '<h2>Compte rendu du 28 août : Autopsie d\'un rat contaminé par le virus L.P.E : Dr Malaky (1/3)</h2>
                <p>Signes cliniques (directement observables) : Le rat, à l\'origine blanc, présente une pigmentation tirant sur le brun. Sa queue, normallement dépourvue de toute pilosité, est parcourue de nombreux petits poils naissant à sa surface. Les yeux du rongeur sont rouge sang et les pupilles légèrement dilatées. Ses dents semblent plus acérées et désordonnées et les muscles de sa mâchoire ont doublés de volume. Je note également une hyper-sécrétion de salive que j\'explique par la taille conséquente des glandes salivaires de l\'animal, observables au fond de sa gueule.</p>',
                '<p>Les pattes du rongeur sont extrêmement musclées et ses griffes sont devenues plus rigides et plus longues. Enfin, avant sa mort, le rat présentait un comportement de plus en plus agressif avec de nombreux cris stridents et des attaques répétées contre les parois des cages dans laquelle il était enfermé.</p>'
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "typed"
        ],
        "autop2" => [
            "title" => "Autopsie d'un rat (partie 2 sur 3)",
            "author" => "sanka",
            "content" => [
                 '<h2>Compte rendu du 28 août : Autopsie d\'un rat contaminé par le virus L.P.E : Dr Malaky (2/3)</h2>
                <p>Observation des organes après dissection : Je commence ma dissection par la boîte crânienne. Cette dernière s\'ouvre brusquement alors que je viens à peine d\'inciser l\'os, ce qui traduit une forte pression intra-crânienne et confirme l\'appellation de “maladie encéphalique”. Le cerveau baigne dans une petite quantité de sang, est légèrement atrophié et présente des débuts de nécrose. L\'étude de la cavité buccale confirme mon observation des glandes salivaires qui sont pratiquement doublées par rapport à la normale. En suivant le trajet digestif j\'en arrive à l\'estomac. Celui-ci présente une surface beaucoup plus rigide et mieux protégée.</p>',
                '<p>Je mesure le PH des sucs gastriques encore présents et m\'aperçois que celui-ci est égal à 1, donc très acide, alors qu\'il est censé être compris entre 1,6 et 3,2. Le rongeur présente une hépatomégalie (augmentation de taille du foie), sans doute dûe à la réaction immunitaire de l\'animal vis à vis du virus. Je constate également une légère splénomégalie (augmentation de volume de la rate), qui atteste d\'une forte activité immunitaire par sécrétion d\'anticorps et de la destruction de déchets sanguins. Enfin, l\'intestin grêle et le gros intestin son eux aussi revêtus d\'une couche de cellules protectrices et voient leur PH passer de 8 à 4,5.</p>'
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "typed"
        ],
        "autop3" => [
            "title" => "Autopsie d'un rat (partie 3 sur 3)",
            "author" => "sanka",
            "content" => [
                '<h2>Compte rendu du 28 août : Conclusions suite à l\'autopsie du rat contaminé : Dr Malaky (3/3)</h2>
                <p>Conclusions : Le cerveau du rongeur ressemble désormais plus à une éponge du fait de la pression supérieure à la moyenne au sein de sa boîte crânienne. Cette pression élevée détruit le cerveau petit à petit et altère les facultés de vie et de jugement de l\'animal : actions irréfléchies, violence, désinhibition totale et non reconnaissance des siens semble-t\'-il. De plus ceci explique les cris constants du rongeur, qui doit être soumis à une forte souffrance et des maux de têtes insoutenables. </p>',
                '<p>L\'augmentation de taille de la mâchoire, des griffes et de la musculation traduit le fait que l\'animal est conditionné pour le combat et la survie en milieu difficile.</p>
				<p>L\'appareil digestif, mieux protégé, plus acide et de taille légèrement supérieure à la normale signifie que le rat contaminé peut être capable d\'ingérer n\'importe quel aliment sans pour autant risquer sa vie (os, dents, morceaux de tissus : retrouvés dans les selles du cobaye).</p>',
				'<p>Enfin les organes du système immunitaire, de taille disproportionnée, montrent que l\'organisme du rongeur lutte réellement contre le virus mais de manière inefficace. Vu la vitesse avec laquelle ils augmentent de volume je pense que la réponse immunitaire finit par s\'essoufler au bout d\'1 ou 2 jours, laissant ainsi le champ libre à l\'installation de la maladie. </p>'
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "typed"
        ],
        "delir1" => [
            "title" => "Avertissement macabre",
            "author" => "coctail",
            "content" => [
                '<div class="hr"></div>
                <p>Ils sont partout, je vous dis, partout ! Ils sont là avec leurs griffes et leur faim. Leur faim insatiable de viande fraiche, de viande fraiche. Mais ce n\'est pas ça le pire. Oh, non, ce n\'est pas ça le pire&nbsp;! Le pire, c\'est quand vous avez été grignoté, vous n\'êtes pas encore mort&nbsp;! Et ils vous laissent comme ça jusqu\'à ce que vous deveniez l\'un des leurs...</p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "sign1" => [
            "title" => "Avis aux citoyens",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "bilan" => [
            "title" => "Bilan de la réunion du 7 novembre",
            "author" => "Liior",
            "content" => [
                '<h1>Réunion du village du 7 novembre :<small>(Retranscrit par le citoyen Liior, en charge de la Gazette)</small></h1>
                <p>Le chef explique que nous avons entamé une construction énorme, qui peut-être nous "sauvera la vie" :</p>
                <quote>"C\'est un projet totalement insensé ! Mais cela pourrait marcher. Nous avons déjà mis beaucoup d\'énergie à ranger le village d\'une manière plus efficace pour lutter contre ces créatures, mais nous avons encore un effort à faire. J\'ai pensé que peut-être, si on créait un leurre gigantesque, les zombies ne viendraient plus.. Il faut que nous construisions une fausse ville.. Cela peut paraitre bizarre, mais je pense que les zombies sont incapables de faire la différence entre notre village, et un autre.."</quote>',
                '<p>L\'assemblée semble dubitative : </p>
                <quote>"Une fausse ville ? Et ça duperait les zombie&nbsp;?", semblent se demander les autres citoyens dans un brouhaha incompréhensible.</quote>
                <p>L\'assemblée a pourtant voté pour ce projet.. Il faut croire qu\'il ne reste pas beaucoup d\'espoirs..</p>'
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "white"
        ],
        "xmasad" => [
            "title" => "Buffet de Noël",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "carmae" => [
            "title" => "Carnet de Maeve",
            "author" => "Maeve",
            "content" => [
                '<p>Tout le monde est rentré. Nos quelques 30 citoyens survivants sont (provisoirement) en sûreté derrière les murailles. Elles branlent un peu, quand on cogne dans le mur, on se prend une avalanche de cailloux sur la tête, mais pour ce soir ça sera suffisant. Si on a de la chance...</p>
                <p>Portes fermées, tous s\'enferment. Chacun marmonne dans son coin, attendant  dans l\'angoisse l\'attaque de la nuit. Dans un coin, une fille pleure. Encore une qui cède à la panique. Pas assez d\'énergie pour se soucier des autres, personne n\'ira l\'aider.</p>
                <p>Pour fuir cette ambiance lourde, je grimpe en haut d\'un toit, guette l\'avancée de la horde, plongée dans mes pensées. Et puis, soudain, venu du désert, un son insolite me tire de ma rêverie morbide. Quelqu\'un chante dehors. Pourtant... tout le monde est en ville.</p>',
                '<p>Plissant les yeux, je distingue une forme. Puis des couleurs. Quelques lambeaux de vêtements, des traces de morsures, des croûtes, un regard vide. Non, c\'est pas un des nôtres. Un mort-vivant. Je ne savais pas qu\'ils pouvaient chanter... Je me laisse prendre au piège de la chanson. Et si...</p>
                <p>Les zombies ne sont peut-être pas si mauvais. D\'accord, ils mangent de la chair humaine. Mais qu\'est-ce que j\'ai fait moi ce matin ? C\'est bien Survivor que j\'ai découpé ? La chanson me supplie. Tout le monde est bien chez soi ? Personne ne me verra descendre et ouvrir les portes...</p>'
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "macsng" => [
            "title" => "Chansons macabres",
            "author" => "Sekhmet",
            "content" => [
                '<p>Combien de temps survivrons nous ? Je l\'ignore. J\'évite d\'être pessimiste devant mes compagnons d\'infortune. Je pense qu\'eux aussi.</p>
                <p>Mais pour moi, ce soir est la fin. Je suis gravement blessé, je ne passerai pas la nuit.</p>
                <p>Je pense que je délire déjà. Au moment de rentrer dans ce qui me sert de maison, j\'ai assisté à la plus étrange des scènes. Une silhouette était assise, sur le toit de sa cabane. M\'approchant, j\'ai reconnu une de mes camarades de chantier. Une femme plutôt taciturne, renfermée.</p>
                <p>Elle chantait.</p>',
                '<p>Je suis resté là, fasciné, à écouter ce chant doux et mélancolique alors que le soir tombait. Chant qui sonnait d\'autant plus doux qu\'il contrastait avec la pâleur de son teint, les cernes sous ses yeux et les traces de boue et de sang sur ses vêtements.</p>
                <p>Je suis resté là, à regarder cette frêle silhouette, cette jeune femme qui, dans d\'autre circonstances, dans une autre vie, aurait pu être belle.</p>
                <p>Son fredonnement, presque envoûtant, semblait appartenir à un autre monde, venu pour apporter un peu de paix dans ce monde sans pitié.</p>
                <p>Comme un minuscule et fragile ilôt d\'apaisement au milieu de la tourmente.</p>',
                '<p>Le chant s\'est arrêté en même temps que le dernier rayon de soleil éclairait la ville. Comme si la mort reprenait ses droits. Mon coeur s\'est serré et j\'ai laissé échapper une larme.</p>
                <p>Je vais mourir ce soir, je le sais. Mais peu m\'importe. </p>'
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "written"
        ],
        "coloc" => [
            "title" => "Colocation",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "denonc" => [
            "title" => "Conspiration",
            "author" => "anonyme",
            "content" => [
                '<p>Voisin,</p>
                <p>Si tu reçois cette missive, c\'est que tu fais partie des <strong>élus</strong> des Bas-Fonds de la Pourriture Occidentale.</p>
                <p>Nous organisons ce soir une <strong>expédition punitive</strong> dans le quartier ouest de la ville, pour porter un coup fatal aux ordures du Comité du Bon goût, responsables de tous nos maux : chantiers avortés, gaspillage de l\'eau, vol de rations...</p>
                <p>Tiens toi prêt ce soir, à 23h45. </p>
                <p>Comme nos autres sympathisants, nous savons que tu disposes chez toi d\'une arme. Ce soir, <strong>elle frappera avec force et vigueur le crâne de ces raclures du Comité</strong> !</p>',
                '<p>Nous trainerons alors leurs corps brisés dans les rues du quartier ouest, occupant ainsi pour cette nuit les Hordes qui cognent à nos portes !</p>
                <p>Il en va de notre survie à tous, <strong>les malades et les feinéants doivent mourir</strong>.</p>
                <p>TOUS.</p>
                <h1>N\'en parle à personne !</h1>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "typed"
        ],
        "contine" => [
            "title" => "Contine : SilverTub",
            "author" => "TubuBlobz",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "sngsek" => [
            "title" => "Contine des jours sans lendemain",
            "author" => "Sekhmet",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "lettre" => [
            "title" => "Correspondance",
            "author" => "Teia",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "alone1" => [
            "title" => "Coupés du reste du monde",
            "author" => "Liior",
            "content" => [
                '<h2>Le 20 janvier, 17h:</h2>
                <p>Cela fait maintenant 3 semaines qu\'aucune communication avec d\'autres refuges n\'est possible.. Il semble qu\'un vent venu de l\'ouest amène des odeurs, des parfums de putréfaction.. Le puits du village est notre dernière ressource d\'eau potable.. La terre est desséchée et les seuls fruits que le potager nous offre sont d\'une couleur bizarre et sentent la pourriture.</p>
                <p>J\'ai peur. Des créatures dont je ne saurai expliquer l\'existence s\'amassent soir après soir autour de notre cité.. Ils tapent sur les murs, je pense qu\'ils nous en veulent.. Si ils reviennent.. On ne tiendra pas.</p>',
                '<h2>Le 22 janvier, 10h15 :</h2>
                <p>Nous avons perdu la moitié de nos compagnons d\'infortune, dans ce qui semble être une attaque. Les créatures sont venues..</p>
                <p>Du haut du mirador, j\'ai clairement distingué une foule "humaine", ce qui ne me rassure pas.. Sont-ce des cannibales ? En tout cas, ces créatures ne sont pas armées.. Le chef du village pense qu\'elles reviendront ce soir. Et demain. Et tout les jours maintenant..</p>',
                '<h2>Le 24 janvier, 20h30 :</h2>
                <p>J\'ai peur, je suis seul et je ne descend plus du mirador sauf pour aller chercher des légumes au potager.. Hier soir, une créature m\'a vu..</p>
                <p>Elle était trop occupée à dévorer le chef du village pour me prêter plus d\'attention que cela..</p>
                <p>J\'ai peur, car j\'ai vu des cadavres avoir des spasmes..</p>
                <div class="hr"></div>
                <p>J\'ai la vague impression qu\'ils pourraient se réveiller.. Je ne descends plus.. Je les entends.. Ils arrivent.</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "dfive1" => [
            "title" => "Courrier d'un citoyen 1",
            "author" => "dragonfive",
            "content" => [
                '<small>Une lettre sans destinataire et sans signature. Peut-être n\'était-elle pas écrite pour être envoyée ?</small>
                <div class="hr"></div>
                <p>Notre passé, notre futur... Ils contrôlent notre vie. </p>
                <p>Alors que nous somme tous condamnés à mourir et à nous réincarner éternellement pour reconnaître le même destin, alors que nous luttons pour pouvoir survivre ne serait-ce qu\'un seul jour de plus, les zombies, eux, attendent la moindre faille dans les défenses de notre ville, attendent qu\'un citoyen s\'égare la nuit, et n\'ont qu\'une idée en tête : nous dévorer. Nos tentatives de survie sont vaines, un jour où l\'autre, ils finiront par nous avoir. Et si nous ne mourons pas dévorés par nos ex-concitoyens, nous mourrons desséchés dans le désert.</p>',
                '<div class="hr"></div>
                <p>Notre vie est éphémère, et seules nos carcasses peuvent attester de notre présence, tant qu\'elles sont encore identifiables. Mais peut-on réellement appeler ça une vie ? Nous mangeons de la nourriture avariée, et il nous arrive même de manger les restes de nos voisins, nous ne sortons que pour trouver de quoi défendre la ville en prévision de l\'attaque du soir, nous vivons l\'horreur même, et ce pendant chacune de nos vies ! Quelle personne normale pourrait tenir un seul jour dans ces conditions ?</p>
                <p>Très peu, je peux vous l\'assurer.</p>',
                '<div class="hr"></div>
                <p> Et c\'est peut-être pour ça que nous en sommes là aujourd\'hui. </p>
                <p>Peut-être qu\'un Dieu nous surveille, là-haut, et a décidé de s\'amuser un peu, de voir combien de personnes pourraient survivre à ces hordes de monstres, dehors.</p>
                <p>Et si un tel Dieu existe, j\'aimerais bien le rencontrer et lui prouver toute ma gratitude en lui collant mon pied au derrière.</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "lettr1" => [
            "title" => "Courrier d'un citoyen 2",
            "author" => "Nikka",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "water" => [
            "title" => "De l'eau pour tous",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "last1" => [
            "title" => "Dernier survivant",
            "author" => "Arma",
            "content" => [
                '<small>La transmission suivante a été enregistrée sur une fréquence longue dans la région du Lac. </small>
                <h2><small>Reçue : 20/10 15:53:31, Fréq : 158.7, Origine : N/C</small></h2>
                <p>"Je suis le dernier ! Héhé... Ils sont tous morts ! Plus personne ne pourra me tourmenter. (rire) Stupides Humains ! Ils pensaient pouvoir vivre et maintenant ils ont rejoint leurs rangs ! <em>(phrase inaudible, parasites)</em> Moi, je savais ! Je savais qu\'ils allaient mourir ! Tous ! Mais moi je suis vivant ! Haha... J\'ai survécu ! Je suis le plus fort! Ils sont faibles et moi je suis résistant, héhé?.</p>
                <p>Venez ! Approchez bande de lâches ! Je vous mettrais au tapis avec seulement mes poings. Je suis vivant, vous êtes morts ! JE SUIS PLUS PUISSANT QUE VOUS !... Pourquoi attendre minuit ?! Venez me chercher ! </p>',
                '<p>Je m\'impatiente... je vous tuerais tous... tous... jusqu\'au dernier... Je suis fort, ils sont faibles... Je suis fort, ils sont faibles... Je n\'ai pas peur." </p>
                <small>Fin de transmission.</small>'
                            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "typed"
        ],
        "short1" => [
            "title" => "Derniers mots",
            "author" => "Exdanrale",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "notes1" => [
            "title" => "Des notes griffonnées",
            "author" => "Melie",
            "content" => [
                '<p>J\'arrivais avec mon maigre baluchon sur l\'épaule.</p>
                <p>11H00 du matin. Je fais partie des 12 explorateurs désignés contre mon gré. Les portes s\'ouvrirent. Le froid du désert me frappa le visage.</p>
                <quote>-Allez-y.</quote>
                <p>Un des douze explorateurs m\'interpella  sèchement.</p>
                <quote>-Melie ! Où vas-tu ?</quote>
                <quote>-Nord, répondis-je d\'un ton acerbe.</quote>
                <p>Armée de mon pistolet à eau chargé, je m\'avançai la première. Les autres me suivirent. Je creusai avec mes mains. Rien. Quelques autres citoyens eurent plus de chance : des planches tordues, et même de la ferraille. Nous nous avançâmes. Pas de zombie.</p>',
                '<p>Ca ne me rassurait pas : ils nous attendaient sûrement plus loin. Cette fois, j\'ai trouvé une souche de bois pourrie. On pourra la transformer à l\'atelier !</p>
                <p>Plusieurs heures passèrent,  la <strike>peur</strike> fatigue me gagne. Je m\'écroule par terre, ne pouvant plus avancer. 5 zombies m\'entouraient. A travers mes yeux entrouverts, j\'apercevais mes compagnons m\'abandonner lentement... Ils avaient eu la force de manger pour repartir.</p>
                <quote>-Ne partez pas ! Non !</quote>
                <p>Je ne parlais pas, je balbutiais. Le soir tombait.</p>',
                '<div class="hr"></div>
                <p>19H30. Cette fois, c\'est la fin. Je suis seule. J\'arrive à peine à sortir mon pistolet de mon sac.</p>
                <p>Je ne peux même pas espérer fuir... Les <strike>mort-vi</strike> morts-vivants me bloquent le passage.</p>',
                '<div class="hr"></div>
                <p>22H00. Je n\'étais pourtant pas si loin de la ville... Je vois presque les portes derrière moi. Je crie, je hurle, mais personne ne vient.</p>
                <p>Une dernière image du désert, des zombies, puis le noir.</p>'
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "poem2" => [
            "title" => "Deux vies et un festin",
            "author" => "SeigneurAo",
            "content" => [
                '<p>Harmonie véritable, un bout d\'oreille qui pend</p>
                <p>Folie du vénérable, dément se repentant</p>
                <p>Souffle dans la vallée, une femme debout attend</p>
                <p>Les zombies s\'approcher, doucement elle entend</p>
                <hr>
                <p>Pour qui sonne le glas, pour qui la mort s\'apprête ?</p>
                <p>Vers elle j\'avance las, tire une balle dans sa tête</p>
                <p>Je l\'aimais de tout coeur, aussi trouvé-je bête</p>
                <p>De laisser mon âme soeur, affronter cette tempête</p>
                <hr>
                <p>De coeur il est question, et me voilà bientôt</p>
                <p>À prendre possession, du sien Dieu qu\'il est beau</p>
                <p>Un repas pour ce soir, un bon repas bien chaud</p>
                <p>Mon âme sera-t-elle noire, aurai-je été un sot ?</p>
                <hr>',
                '<p>Harmonie féérique, un aventurier part</p>
                <p>Chevauchée héroïque, il reviendra très tard</p>
                <p>Ou peut-être même pas, si par ce jour blafard</p>
                <p>Sa route le mènera, en un lieu très bizarre</p>
                <hr>
                <p>Peuplé de créatures, de cloportes et défunts</p>
                <p>Serein il nous assure, qu\'il n\'ira pas trop loin</p>
                <p>Les condamnés se gaussent, oublient presque leur faim</p>
                <p>Sa confiance sonne bien fausse, la mort est son destin</p>
                <hr>
                <p>De fait le lendemain, nous trouvâmes ses restes</p>
                <p>Dépouillés yeux et reins, bien pire que par la peste</p>
                <p>Mais les rats sont là eux, car peu de différence</p>
                <p>Pestiféré ou preux, seul compte de faire bombance </p>'
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "poem"
        ],
        "dement" => [
            "title" => "Démenti officiel",
            "author" => "Tyekanik",
            "content" => [
                '>
                <h2>Communiqué de presse</h2>
                <p>Il est prouvé scientifiquement que les micros ondes n’altèrent en rien le corps humain. Et encore moins la matière inerte et sans vie.<br>
                Toutes ces rumeurs ont été lancées par des marques concurrentes qui jalousent notre succès.
                C’est une méthode de marketing déloyale et absolument immorale qui reste malgré tout très efficace et employé par certain commerciaux sans scrupules.
                </p>
                <p>
                Quant aux troubles qui se sont déroulé dans l’une de nos usines, cela reste un incident isolé qui n’a rien à voir avec l’ensemble de produit.
                Je vous assure que l’ensemble de la gamme des électroménagers rebelles est absolument sans aucuns risques d’utilisation.
                </p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "classic"
        ],
        "degen" => [
            "title" => "Dégénérescence",
            "author" => "Fabien08",
            "content" => [
                '<p>C\'est étrange je suis <strike>crev</strike>mort mais je vie<strike>s</strike>, je ne sen<strike>s</strike> plus rien et pourtant mon corps est ensanglante<strike>r  </strike> ... J\'était un <strike>ho</strike>citoyen<strike>s</strike> avant ... avant <strike>le</strike> l\'attaque ...</p>
                <p>Je suis des leurs ! <strike> Je </strike></p>
                <p>Je ne sais pas comment c\'est possible mais je pense<strike>nt</strike> encore, j\'écri<strike>t</strike>s et je suis sur<strike>r</strike> que je pourrais parl<strike>lrlrioo</strike>er si il ne m\'avait pas dévorer la moite<strike>r</strike> du visage. Je profite de <strike>ce tr</strike>"don" pour m\'excuser de <strike>se</strike> ce que je vai fair, <strike>on</strike> je le sais <strike>je</strike> un jour<strike>s </strike> je vais devoir vous dévorer ... dans <strike>ma</strike> tête je doit <strike>lut</strike> luter san cesse et je ne <strike>vais</strike>pas suporter encore <strike>clon</strike>lontemp  ... Je vous mengerai<strike>s</strike> qe je <strike>le</strike> veuille ou non ! <strike> j</strike>e</p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "written"
        ],
        "leavng" => [
            "title" => "Départ",
            "author" => "1984",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "diner" => [
            "title" => "Dîner aux chandelles",
            "author" => "Maeve",
            "content" => [
                '>
                <p><em>"Retrouve-moi à la bibliothèque de quartier. ♥"</em></p>
                <p>Quelle idée, pour un rendez-vous romantique ! A-t-on déjà vu moins sexy ? Autrefois, d\'austères rangées de livres rebutants… <br>
                Maintenant, des étagères brisées, des connaissances envolées, de longues traces brunies au sol… Le lieu idéal pour les trafics louches.<br>
                Et qui est cette inconnue qui m’y convie si discrètement ? Les quelques femmes en ville ont le visage gris, les mains sales et les cheveux cassants. <br>
                Elles me castreraient, plutôt. Il n’y en a qu’une, discrète… que j\'imagine ici. Elle semble assez sauvage, je ne la voyais pas mettre des cœurs sur ses billets. C\'est bien trop niais.<br>
                </p>
                <p>Oh, et puis, un bon repas mérite bien un petit compromis...</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "scient" => [
            "title" => "Emission radiophonique d'origine inconnue",
            "author" => "pepitou",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "white"
        ],
        "dead1" => [
            "title" => "Épitaphe pour Alfred",
            "author" => "Planeshift",
            "content" => ['<small>[ Ce bout de carton devait sûrement servir d\'épitaphe pour une pierre tombale ]</small>
            <h1>Alfred (1948 - ??)</h1>
            <p>Alfred était peut-être le dernier des abrutis, mais il a toujours eu bon goût, je le maintiens. Décorant avec soin son intérieur, faisant attention à ce que chaque objet soit à sa place, afin que, si l\'on passait par hasard chez lui, on ait au moins le sentiment que ce soit confortable. Même à sa mort, hurlant à l\'aide alors que les zombies le dévoraient, il prit soin de ne pas les attaquer avec cette chaise si artistiquement exposée à côté de sa table en bois à moitié pourrie, sans parler de se défendre en utilisant le pistolet, pourtant chargé, accroché au mur. Un mystère qui restera pour nous entier, mais Alfred avait du goût, comme chacun d\'entre nous ici le sait, et préféra mourir que de mettre en désordre son intérieur.</p>
            <p>Et effectivement, je vous le dis. Bien cuit, Alfred a vraiment un goût délicieux.</p>'],
            "lang" => "fr",
            "background" => "carton",
            "design" => "written"
        ],
        "errnce" => [
            "title" => "Errance",
            "author" => "Crow",
            "content" => [
                '<p>Ni Enfer, ni Paradis... Ci-gît ma dernière pensée articulée.</p>
                <p>Le délire est trop puissant, le soleil trop accablant... Qu\'avons nous fait pour mériter tel châtiment ? Comment le destin peut-il faire montre d\'une telle cruauté ? Condamnés à nous réincarner dans un monde de mort et de désolation ou, y finir en zombies... Errance astrale ou matérielle... J\'ai vu !</p>
                <p>J\'ai vu dans les méandres sirupeux de mon infection, les grandes et les petites choses... Nous ne sommes rien d\'autre que de l\'expérience pour la Mère. Nous avons été des enfants indignes... Nous sommes les seuls responsables ! Nous nous sommes pris pour des Dieux... Qu\'elle ironie ! Nous aurions dû apprendre à marcher avant de vouloir courir; désormais, nous rampons !!!</p>
                <p>Quelle ironie. Qu\'elle fin pleine de panache ! Toi qui lis ces divagations, saches que la richesse est l\'Expérience...  </p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "written"
        ],
        "ster" => [
            "title" => "Etiquette de stéroïdes pour chevaux",
            "author" => "dragonfive",
            "content" => [
                '<h2>Stéroïdes pour chevaux</h2>
                <small>Visa et n° d\'exploitation : M0T10NTW1N - 93847</small>
                <blockquote>Composition chimique : Cholestérol, Testostérone, divers autres produits plus ou moins dangereux dont vous ne voulez pas connaître le nom.</blockquote>
                <small>Effets secondaires indésirables : Hypertension, arythmie, lésions du foie, tumeurs cérébrales, crise cardiaque, chute des poils et mort subite.</small>
                <p>Date de péremption : voir dos de la boîte.</p>
                <small>Mises en garde : La consommation de stéroïdes pour chevaux chez l\'homme peut entraîner une dépendance immédiate. Ne pas ingérer. Pour plus d\'informations sur les méthodes d\'administration, reportez-vous au fascicule proposé en pharmacie. Nous nous déchargeons de toute responsabilité en cas de décès. La liste des effets secondaires citée plus haut ne contient que les informations légales et officielles.</small>'
            ],
            "lang" => "fr",
            "background" => "stamp",
            "design" => "modern"
        ],
        "study1" => [
            "title" => "Etude médicale 1 : morphologie comparative",
            "author" => "ChrisCool",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "study3" => [
            "title" => "Etude médicale 3 : reproduction",
            "author" => "ChrisCool",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "study4" => [
            "title" => "Etude médicale 4 : alimentation",
            "author" => "ChrisCool",
            "content" => [
                '<div class="hr"></div>
                <h1>Etude analytique de l\'anatomie et du comportement des non-vivants</h1>
                <h1>Chapitre 4 : l\'alimentation</h1>',
                '<p>Tout simplement fascinant !</p>
                <p>Après avoir enchaîné un spécimen et lui avoir présenté divers aliments, on peut constater que les non-vivants ont un appétit dévorant pour la viande fraîche. Ils semblent tout particulièrement raffoler de matière grise. Nos études tendent à montrer qu\'un seul non-vivant peut dévorer un humain en huit minutes. L\'humain étant bien évidemment vivant à ce moment-là.</p>
                <p>Il est également intéressant de souligner le fait que même les spécimens dépourvus d\'intestins ou d\'?sophage ressentent le besoin de s\'alimenter.</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "study5" => [
            "title" => "Étude médicale 5 : décès",
            "author" => "ChrisCool",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "pehach" => [
            "title" => "Exil",
            "author" => "Pehache",
            "content" => [
                '<div class="hr"></div>
                <p>Je m\'appelle Pehache, je résidais il y a encore peu dans la ville de la « Tranchée des Oubliés »... Et si vous lisez cet écrit, c\'est que je ne suis plus de ce monde.</p>',
                '<p>Il est 11h30. Je me retrouve perdus dans cette immensité désertique, et il commence à faire bien froid par ici. La nuit tombe, et j\'entends au loin des hurlements qui me filent la chair de poule.</p>
                <p>Mais qu\'est ce que je fous là bon sang ?!</p>
                <p>«C\'est à ton tour, Pehache, de partir en expédition». C\'est ce qu\'ils m\'ont dit, en me donnant une cuisse de poulet. La vérité est plutôt que sur la place du forum je m\'étais une fois de plus opposé à eux sur la construction des barbelés. La ferraille est si rare de nos jours? Ces imbéciles ne veulent pas comprendre. Où peut-être est-ce parce que j\'étais le seul à posséder une radio, une lampe, et un matelas? Pour faire bref, j\'avais le choix entre l\'expédition solitaire ou le bannissement.</p>',
                '<p>Après m\'être épuisé à marcher pendant des heures, à ramasser les souches de bois que je trouvais un peu partout, je me suis résigné. Je suis perdus et jamais je ne retrouverai la ville. Il fait maintenant bien trop sombre pour y voir quoi que ce soit de toutes façons.</p>
                <p>Je regarde ma montre, il est 11h55. Mes chances de survie me semblent bien minces. Le Portier, Melissa, Kévin... Tout ceux qui ne sont pas rentrés avant minuit ne sont jamais revenus en ville. </p>
                <p>Bon, 11h59.</p>
                <p>Je me remets en route dans une minute.</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "expalp" => [
            "title" => "Expédition alpha",
            "author" => "Shogoki",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "eclr1" => [
            "title" => "Explorations",
            "author" => "elyria",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "white"
        ],
        "dodod" => [
            "title" => "Fais dodo",
            "author" => "NabOleon",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "crazy" => [
            "title" => "Folie",
            "author" => "Arco",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "poem1" => [
            "title" => "Gazette du Gouffre du Néant, décembre",
            "author" => "lordsolvine",
            "content" => [
                '<h1>Le chasseur et le Mort-vivant</h1>
                <p><strong>Bravo à notre gagnant qui se voit attribuer, en plus de sa parution dans notre journal, un lot de babioles en tout genre : ferrailles, planches de bois, vis et écrous... Merci aux autres citoyens participants.</strong></p>
                <quote>
                <p>Au loin, un corps décomposé</p>
                <p>S\'approche lentement pour vous dévorer.</p>
                <p>Marchant d\'un pas timide,</p>
                <p>Le cerveau complètement vide,</p>
                <p>Il n\'hésitera surement pas,</p>
                <p>A te choper le bras.</p>
                </quote>',
                '<quote>
                <p>Mais sur son cheval blanc,</p>
                <p>Le chasseur dans la nuit,</p>
                <p>S\'élance sur ces morts-vivants.</p>
                <p>D\'un coup de sabre et de cure-dent,</p>
                <p>Il coupe et pique tout.</p>
                <p>Et toi, tu deviens complètement fou.</p>
                </quote>',
                '<quote>
                <p>Soudain, un monstre surgit,</p>
                <p>Et toi, tu ris.</p>
                <p>Tu tentes de le tuer à l\'aide d\'une carotte,</p>
                <p>Mais tu ris, on te chatouille la glotte.</p>
                <p>Tout est fini, tout s\'arrête...</p>
                <p>Il t\'a bouffé la tête.</p>
                </quote>
                <p>Mr.PapyL (08/12/2003)</p>'
            ],
            "lang" => "fr",
            "background" => "news",
            "design" => "news"
        ],
        "gcm" => [
            "title" => "Gros Chat Mignon",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "haiku1" => [
            "title" => "Haiku I",
            "author" => "stravingo",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "money",
            "design" => "written"
        ],
        "infect" => [
            "title" => "Infection",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "alone2" => [
            "title" => "Isolement",
            "author" => "Arco",
            "content" => [
                '<p>que le citoyen soit devenu fou...</p>
                <p>Il aurait peur pour sa vie.</p>
                <p>Il a pillé la banque, pris toutes les planches, et le frigo que nous avions récupéré la veille.</p>
                <p>Il s\'est barricadé chez lui. Il nous observe de sa fenêtre occultée. Je le sais.</p>
                <p>Le fou.</p>
                <p>Nous nous sommes rassemblés devant sa porte.</p>
                <p>Le chef n\'a même pas pris la peine de frapper. Il a tiré au lance pile, les planches ont explosé.</p>',
                '<p>Nous sommes entrés dans son taudis.</p>
                <p>Il tremblait, planqué dans un recoin. Derrière le frigo.</p>
                <p>Le médecin s\'est avancé, lui a tendu un médicament.</p>
                <p>L\'autre le lui a pris, fébrile. Il l\'a avalé, d\'un coup.</p>
                <p>C\'était du cyanure.</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "modern"
        ],
        "coward" => [
            "title" => "Je suis un trouillard",
            "author" => "Sengriff",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "stamp",
            "design" => 'written'
        ],
        "alan" => [
            "title" => "Journal d’Alan Morlante",
            "author" => "lycanus",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "written"
        ],
        "slept" => [
            "title" => "Journal d'un citoyen : Doriss",
            "author" => "Arma",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "log2" => [
            "title" => "Journal d'un citoyen inconnu 1",
            "author" => "Muahahah",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "coctl1" => [
            "title" => "Journal de Coctail, partie 1",
            "author" => "coctail",
            "content" => [
                '<p>Pantocrat avait aperçu le bâtiment en premier.</p>
                <p>Ce n’était pas plus mal, j’en avais marre de marcher et je n’avais pas envie de passer une nouvelle nuit à la belle étoile.</p>
                <p>Pantocrat, Zoby et moi-même (coctail) marchions sur cette terre poussiéreuse parsemée de broussailles depuis des jours et des jours. Nous avions rempli tous les récipients possibles avant de partir mais notre eau chaude s’épuisait encore plus vite que nos derniers espoirs. Mon esprit vagabondait parfois et m’imaginait baignant dans un lac gelé, loin de tous ces zombies qui nous suivaient.</p>
                <p>Ils étaient lents, puants et infatigables. Ils marchaient plus lentement que nous mais semblaient attirés par notre présence. Le jour, nous parvenions à les semer, mais la nuit... la nuit, lorsque nous les entendions approcher, nous étions forcés de lever le camp en toute hâte.</p>',
                '<p>Nous pouvions parfois voir de loin en loin d’autres silhouettes, misérables pantins désarticulés qui se rapprochaient de nous. Nous accélérions alors notre pas et ils se joignaient alors à la horde qui nous suivait, grossissant leurs rangs un peu plus chaque jour.</p>
                <p>A fur et à mesure de notre approche, nous voyions mieux la bâtisse. Sa base était large et il y avait un petit étage. Une antenne se dressait au-dessus. Je vis bientôt des carcasses à côté de la construction.</p>
                <p>Je regardais mon compagnon, Zoby. Il avait l’air encore plus misérable que le jour où nous nous étions rencontrés. Ses lèvres étaient couvertes de croûtes de sang. Il supportait le moins bien le soleil de nous trois mais était le plus bricoleur, ce qui était indispensable pour survivre dans ce monde. C’est lui qui m’avait construit ma « râpe ». Je ne me serais séparé pour rien au monde de ce bout de tôle tranchant.</p>',
                '<p>L’air tremblait sous l’effet de la chaleur. Chaque pas était une lutte mais l’abri se rapprochait lentement... lentement...</p>
                <p>C’est alors que Pantocrat poussa un cri.</p>
                <p>Il venait d’apercevoir les cinq zombies qui se dirigeaient vers nous et qui nous empêchaient d’atteindre le bâtiment.</p>
                <p>Je ne voulais plus marcher. J’avais soif. J’en avais marre d’être traqué comme une bête. Je n’avais pas étudié et travaillé dur pour finir traqué par une horde de morts-vivants.</p>',
                '<p>J’en avais marre.</p>
                <p>Cette construction était ce que j’attendais depuis de jours. Qu’au moins, nous puissions fermer nos yeux cernés par le manque de sommeil.</p>',
                '<p>J’en avais marre.</p>
                <p>Le soleil tapait sur ma tête et les cinq cadavres putrides se rapprochaient. Pantocrat et Zoby discutaient sur la manière de les contourner. Zoby regardait anxieusement le soleil descendant et craignait de ne pouvoir faire un détour suffisamment grand pour lels éviter complètement.</p>',
                '<p>J’en avais marre.</p>
                <p>J’aurais voulu crier mais aucun son ne sortit de ma gorge lorsque je courus vers les zombies, ma râpe levée.</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "coctl2" => [
            "title" => "Journal de Coctail, partie 2",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "coctl3" => [
            "title" => "Journal de Coctail, partie 3",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "coctl4" => [
            "title" => "Journal de Coctail, partie 4",
            "author" => "coctail",
            "content" => [
                '<p>Ce soir-là, étrangement, il n’y eut pas de bruits autre que ceux de Zoby. Je m’étais écroulé de fatigue et Zoby s’acharnait à faire sauter les cadenas avec son tournevis.</p>
                <p>Au milieu de la nuit, Pantocrat vint me réveiller. Il avait fini par scier le fond des caisses. Je vis que Zoby avait un bandage plein de sang autour de sa main et qu’à ses pieds se trouvaient trois tournevis cassés...</p>
                <p>J’allais pester contre ce gaspillage d’outils précieux lorsque Pantocrat me tendit ce que contenaient les caisses.</p>',
                '<p>Je pris la mitrailleuse lourde en main et en testais l’équilibre. Je savais bien qu’il s’agissait d’une cache de matériel militaire.</p>
                <p>Je posais l’arme au sol et fis une accolade à mes amis.</p>
                <p>Je sautais de joie dans le salon jusqu’à ce que Zoby lance amèrement :</p>
                <quote>- « ‘manque plus que des munitions... »</quote>
                <p>Il n’y eut pas d’attaque cette nuit-là.</p>',
                '<p>La journée suivante fut consacrée à déblayer frénétiquement la cache.</p>
                <p>La suivante aussi.</p>
                <p>La journée d’après fut consacrée à combler la brèche qui avait été faite dans le mur par l’attaque de la nuit précédente.</p>
                <p>La nuit suivante fut la plus épouvantable depuis que nous étions arrivés. Nous avons dû nous enfermer dans la salle de bain.</p>
                <p>Les zombies criaient et hurlaient. J’avais cassé ma râpe pour les retenir dans le couloir le temps que Pantocrat et Zoby rassemblent de quoi nous barricader.</p>
                <p>La dernière plaque de tôle allait céder sous leur poids sans cesse croissant.</p>',
                '<p>Pantocrat a alors regardé autour de lui et la seule chose qu’il restait dans la pièce qui ne bloquait pas déjà la porte était la baignoire en fonte. Notre seule réserve d’eau.</p>
                <p>Zoby et moi avons hurlé et pleuré mais Pantocrat l’avait déjà renversée en disant : « -Comme ça, ‘y a plus à hésiter, vide, elle ne peut plus servir qu’à barricader. » De toutes façons, ou nous mourrions comme des rats piégés tout de suite, ou nous mourrions de soif mais plus tard. Autant mourir plus tard.</p>
                <p>Nous avons tout de même hésité à balancer Pantocrat dehors pour occuper les zombies.</p>
                <p>D’autant plus que les zombies se sont repliés dès que nous avons renversé la baignoire contre l’entrée de la porte.</p>',
                '<p>Nous n’avions toujours pas trouvé de munitions et nous n’avions plus d’eau.</p>
                <p>Heureusement que Zoby avait fini avec le half-track. Nous avons donc chargé quelques armes (vides) à l’intérieur, les outils que nous avions trouvé et toutes les vis que nous avons pu rassembler car il devenait évident que notre bâtiment ne tiendrait plus le coup d’une nouvelle attaque de la horde sans cesse plus nombreuse.</p>
                <p>Nous avions décidé de fouiller jusqu’à l’arrivée des zombies. Pantocrat avait été désigné pour grimper au sommet de la bâtisse.</p>
                <p>Pantocrat poussa un cri alors que Zoby et moi chargions à la hâte une caisse d’explosifs que nous venions de trouver.</p>
                <p>Pantocrat conduisait, Zoby assis à côté de lui. Il alluma le lecteur de cassette et du rock dansant plein de parasites couvrit le bruit du gros moteur.</p>',
                '<p>Assis à l’arrière, je regardais derrière nous la horde qui arrivait en boitant, leurs yeux vides tournés vers le nuage de poussière que soulevait notre véhicule.</p>
                <p>Le soleil se couchait, découpant les silhouettes sinistres.</p>
                <p>Derrière nous, accroché à l’antenne du toit, le vent soulève un morceau de tissus sale avec une Licorne malhabilement dessinée dessus...</p>
                <p>Auteur : coctail</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "logpin" => [
            "title" => "Journal de Pierre Ignacio Tavarez",
            "author" => "ChrisCool",
            "content" => [
                '<p>Voici les mémoires du grand Pierre Ignacio Tavarez. Puissent-elles servir d\'exemple aux générations futures !</p>
                <p><strong>3 septembre :</strong> Je peux le dire sans me vanter, je suis un baroudeur. J\'ai déjà été dans de nombreuses villes, et grâce à mes talents naturels, elles ont vraiment prospéré !</p>
                <p>Je viens de me réfugier dans une nouvelle ville, visiblement ces gens ne savent pas y faire... J\'en vois déjà en train de voler des planches dans l\'entrepôt qui nous sert de banque, afin de les garder pour eux. Je vais vite crier au et fort au bannissement, afin de faire un exemple.</p>',
                '<div class="hr"></div>
                <p><strong>4 septembre :</strong> rien ne va ! Certains ont commencé à vouloir construire une pompe pour avoir plus d\'eau. Après avoir fait un peu de porte-à-porte (ou plutôt, tente-à-tente) et rassemblé de bonnes volontés, nous les avons bannis, afin de rappeler les règles essentielles.</p>',
                '<p><strong>5 septembre :</strong> encore une journée laborieuse en perspective : je suis allé faire un tour dans le désert aujourd\'hui, je rentre ce soir, et je constate qu\'il ont commencé à construire un nouveau bâtiment. J\'ignore ce qu\'ils veulent en faire, ces amateurs sont vraiment touchants. Mais ce n\'est pas cette construction qui va nous défendre contre les zombies à mon avis, surtout quand on voit leur façon de faire des noeuds.</p>
                <p class="other">6 septembre : ci-gît Pi<strike>erre</strike> Na<strike>cimo</strike> Ta<strike>varez</strike>., le noeud qui l\'a pendu a été plus coulant que lui. </p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "typed"
        ],
        "intime" => [
            "title" => "Journal intime",
            "author" => "Homerzombi",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "white"
        ],
        "nowatr" => [
            "title" => "Joyeux réveillon",
            "author" => "sanka",
            "content" => [
                '<h2>25 Décembre :</h2>
                <p>Ca y\'est, le puit est à sec depuis 2 jours et je sens que mes forces commencent à me lâcher... </p>
                <p>Il ne reste plus que 5 citoyens, moi y compris, dans cette ville remplie de cadavres qui hier encore étaient mes compagnons. L\'attaque de cette nuit aura eu raison de la plupart des habitants, les travaux restent en suspend faute de main d\'oeuvre et de matériaux. </p>
                <p>La faim se faisant également ressentir, je me vois dans l\'obligation de troquer ma plume pour un couteau si je ne veux pas que mes collègues terminent les restes de mon fils sans moi...</p>'
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "written"
        ],
        "thief" => [
            "title" => "Jugement",
            "author" => "stravingo",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "cenhyd" => [
            "title" => "La centrale hydraulique",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "money",
            "design" => "written"
        ],
        "cigs1" => [
            "title" => "La clope du condamné",
            "author" => "Amnesia",
            "content" => [
                '<p>Et merde... La prochaine fois que je <strike>parti</strike>ferais gaffe avant de me précipiter dans un terrain découvert où trainent deux pauvres cactus et une bonne quinzaine de putrides, la bave aux lèvres en sachant pertinemment qu\'ils vont peut être pouvoir se tailler un bon tartare avec quelques morceaux d\'os, des bouts de cigarettes et des lambeaux de tissus, le tout accompagné de sa sauce sanglante aux poussières du désert... Appétissant n\'est pas ? </p>',
                '<p>Maintenant j\'ai plus qu\'à attendre que quelqu\'un vienne ou que la nuit tombe, en me fumant les dernières clopes de ce paquet sur lequel je suis en train d\'écrire des inepties pour passer le temps, c\'est assez désolant, surtout que j\'ai presque plus d\'allumettes. Au pire je pourrais leur demander si ils en veulent pas une, aux râleurs là bas, peut être que c\'étaient des rastas avant de clamser...</p>
                <small>Le reste du message est assez illisible à cause de la poussière, mais au vu du pavé de petites lettres qui entoure le reste du paquet, vous vous doutez que l\'auteur n\'avait rien d\'autre à faire pendant un long moment... Nul ne sait qui il est, ni s\'il a réussi à rentrer en ville ou s\'il est mort ici, mais comme il reste encore une cigarette dans le paquet, autant en profiter.</small>'
            ],
            "lang" => "fr",
            "background" => "tinystamp",
            "design" => "small"
        ],
        "logsur" => [
            "title" => "La colline aux survivants",
            "author" => "Darkhan27",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "hang" => [
            "title" => "La potence",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "chief" => [
            "title" => "La trahison",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "cidcor" => [
            "title" => "Le CID de Pierre Corbeau",
            "author" => "bartock",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "aohero" => [
            "title" => "Le Héros",
            "author" => "SeigneurAo",
            "content" => [
                '<p>Jour 1</p>
                <p>Ao est sorti dans l\'Outre-Monde.</p>
                <p>Il a collecté une quantité ahurissante de ressources, les chantiers avancent bien.</p>
                <p>Gloire à notre éclaireur !</p>',
                '<p>Jour 2</p>
                <p>Certaines choses ne peuvent tout simplement pas être expliquées, je pense.</p>
                <p>Comme ce qui vient d\'arriver.</p>
                <p>Ao est rentré d\'exploration vers un tumulus que les Veilleurs avaient repéré à plusieurs kilomètres, il a rapporté des outils et des armes, peut-être que nous survivrons plusieurs jours finalement.</p>',
                '<p>Jour 3</p>
                <p>Aujourd\'hui pour la 4ème fois, Ao a ramené l\'un de nos camarades qui était cerné par des zombies.</p>
                <p>Il est sorti, l\'a porté sur ses épaules sous un soleil de plomb, au péril de sa vie, et déposé sain et sauf en ville.</p>
                <p>Je commence à comprendre pourquoi les autres le traitent comme un héros.</p>',
                '<p>Jour 4</p>
                <p>Je n\'ai pas vu Ao ce matin, il a dû sortir avant même le lever du jour.</p>
                <p>Quelle bravoure, quelle abnégation.</p>
                <p>On commence à signaler quelques vols à la banque, les gens deviennent nerveux.</p>
                <p>J\'ai bien des soupçons, mais pour l\'instant il faut savoir faire preuve de retenue... je n\'ai déposé que 4 plaintes anonymes.</p>
                <p>Sûrement Ao pourra tirer tout ça au clair à son retour.</p>',
                '<p>Jour 5</p>
                <p>On a découvert 5 camarades le cou tranché cette nuit.</p>
                <p>C\'est amusant, les marques ressemblent au couteau à dents qu\'Ao a trouvé dans le commissariat abandonné, l\'autre jour.</p>
                <p>Certains ont déjà commencé à le harceler, mais moi je sais bien ce qu\'il en est. Ils sont jaloux, c\'est un complot pour le faire chuter.</p>',
                '<p>Jour 6</p>
                <p>Je ne m\'étais pas rendu compte que la maison d\'Ao était si bien protégée.</p>
                <p>Il a sûrement une bonne raison pour avoir prélevé ces planches dans la banque.</p>
                <p>Peut-être une veuve et son enfant qu\'il a tiré des griffes du désert, et qu\'il veut garder en sécurité.</p>
                <p>Tout à l\'heure il m\'a souri, je pense que tout va bien se passer.</p>',
                '<p>Jour 7</p>
                <p>Ao m\'a demandé une pierre à aiguiser. Quel honneur de pouvoir contribuer à ses activités.</p>
                <p>Un problème avec son couteau, il l\'aurait ébréché sur une pierre ou je ne sais quoi.</p>
                <p>J\'ai toute confiance en l\'avenir maintenant. </p>'
            ],
            "lang" => "fr",
            "background" => "postit",
            "design" => "postit"
        ],
        "mixer" => [
            "title" => "Le batteur électrique",
            "author" => "Esuna114",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "chaos" => [
            "title" => "Le chaos",
            "author" => "Liior",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "crema2" => [
            "title" => "Le crémato-cue",
            "author" => "Liior",
            "content" => [
                '<h1>Note pour les citoyens :</h1>
                <p>Un nouveau buffet ouvre. Il n\'est plus possible de tenir sans nourriture dans le village. Nous avons pris une décision, une lourde décision. Pour le bien de la ville et de ses citoyens, ne protestez pas. L\'éthique n\'est plus qu\'une option dans ces temps obscurs.</p>
                <p><strong>Les premières grillades auront lieu demain, quand les gens infectés auront quitté ce monde.</strong></p>
                <p>N\'oubliez pas, notre monde devient de moins en moins moral et de plus en plus violent.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "roidp" => [
            "title" => "Le roi de pique",
            "author" => "Kirtash",
            "content" => [
                '<h1>C666</h1>
                <p>Il faut que tu prennes cette carte ! Je l’ai volé pour toi.</p>
                <p>Garde la avec toi c’est un roi de pique !</p>
                <p>Et ce soir quand on te demandera de montrer quelle carte tu as tiré montre le roi ! Tu m’entends ? Tu DOIS montrer le roi de pique !</p>
                <p>Ils ne prennent que les cœurs et je ne veux pas qu’ils t’arrachent le tien mon aimé.
                Je ne veux pas que tu y ailles cette nuit ni aucune autre, il n’y a sur les murailles des veilleurs que terreurs et larmes.</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "written"
        ],
        "noel" => [
            "title" => "Lettre au Père Noël",
            "author" => "zhack",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "anarch" => [
            "title" => "Lettre d'Anarchipel",
            "author" => "Sigma",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "nelly" => [
            "title" => "Lettre pour Nelly",
            "author" => "aera10",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "lettr2" => [
            "title" => "Lettre à Émilie",
            "author" => "Ralain",
            "content" => [
                '<p>Ma chère Soeur, ma Emilie, </p>
                <p>Comme tu me manques. Oh comme j\'aimerais te prendre dans mes bras en ce moment, si tu savais à quel point... Nous voilà séparés depuis 8 jours aujourd\'hui, je n\'ai pas oublié ma promesse et je te retrouverai. Devant Dieu et devant Lucifer je jure de te retrouver par tous les moyens. Crois en moi ma petite soeur, je parcours notre triste pays dévasté à la recherche de ce maudit Pic des Songes. Et je me rapproche, de jour en jour, toujours plus de toi... J\'espère que tu y es arrivé sain et sauf. Je suis sûr que tu es en parfaite santé. N\'est-ce pas ? Tu as toujours été futée et débrouillarde, plus que moi en tout cas... Tu étais toujours celle qui me sortait des situations compliquées. Quel frère je faisais...</p>',
                '<p>Aujourd\'hui c\'est toujours toi qui me tire vers l\'avant, tu sais ? Si je ne te savais pas en sécurité, j\'aurais déjà abandonné. Si tu n\'étais pas là, je me serais laisser mourir dès le premier jour. Ah si tu savais les horreurs dont j\'ai été témoin : notre famille, nos amis, Claire, ce con de Thomas, Papa, Maman... Ils sont devenus... Tu sais. Tu sais...</p>
                <p>J\'ai rejoins cette nouvelle ville hier soir et beaucoup de personnes sont dans le même cas que moi ici. On ne va pas rester longtemps dans le coin, le temps que nous rassemblions quelques provisions et nous repartirons sur nos routes respectives. Probablement demain matin si le chemin est assez dégagé. J\'ai confié un exemplaire de cette lettre à mes compagnons. Ils la remettront à la jolie fille qui sent la vanille et qui répond au doux surnom d\'Emie s\'ils devaient la trouver avant moi. Ma Emilie... J\'aimerais tant être le premier à te remettre cette lettre...</p>
                <p>Je t\'aime. A bientôt ma petite Fleur de Vanille.</p>'
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "letann" => [
            "title" => "Lettres d'un milicien",
            "author" => "coctail",
            "content" => [
                '<p>Anne,</p>
                <p>je n\'aurais pas dû m\'engager dans la milice. Ils prétendent protéger la ville, mais ce ne sont que des voyous qui profitent de la situation des gens. Hier, nous avons raquetté une vieille dame pour manger son chat. Ensuite, les zombies sont arrivés et ils n\'ont rien trouvé de mieux que de leur balancer la vieille dame pour les occuper.</p>',
                '<p>Anne,</p>
                <p>nous manquons de tout. Heureusement, le sergent nous a distribué de la viande aujourd\'hui. Après les pertes énormes d\'hier, j\'avais bien besoin de me remplir l\'estomac pour me remonter le moral. Je me demande encore où il a pu la trouver cette viande, lui qui dit toujours qu\'il faut nous rationner ?</p>',
                '<p>Anne,</p>
                <p>quitte la ville au plus tôt. Le sergent vient de nous dire d\'évacuer la tranchée, que la horde était bien trop nombreuse pour que nous puissions la retenir. Il était blême. Je ne l\'ai jamais vu comme ça. Nous avons rassemblé nos affaires et quand je suis allé trouver le sergent dans sa tente pour lui demander où aller, j\'ai entendu un coup de feu. Il s\'est tiré une balle dans la tête.</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "typed"
        ],
        "offrex" => [
            "title" => "Maison à vendre",
            "author" => "Pyrolis",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "mascar" => [
            "title" => "Mascarade",
            "author" => "irewiss",
            "content" => [
                '<div class="hr"></div>
                <h1>Lyrics for M.G.</h1>',
                '<p>Il était tard ce matin quand mes yeux ont bien voulu s\'ouvrir sur ce spectacle plat. Cet ersatz de ville. Cette mascarade. Et toujours les mêmes questions me viennent en tête.</p>
                <p>Qui croit-on duper ?</p>
                <p>Pourquoi nous mentons nous à nous mêmes dans cet ultime élan désespéré ?</p>
                <p>Cette forteresse éphémère que nous avons érigé avec tout le bric-à-brac possible et imaginable ne tiendra plus très longtemps. La horde grandit dehors. Chaque jour ils sont plus nombreux.</p>',
                '<p>Hier ils étaient à peine plus d\'une vingtaine. J\'ai presque pu les compter, du haut de la tour je les ai vu se rassembler, traîner leur corps décédé jusqu\'à nos portes comme s\'ils sentaient nos coeurs bien vivants battre de l\'autre côté. Battre un rythme de frayeur et de dégoût mêlés.</p>
                <p>Ils ont cogné avec la force inhumaine qui leur est propre. Ils ont frappé jusqu\'à faire exploser la chair qui reste collée à leur membres pourris. Cogné jusqu\'à faire grincer les tendons contre l\'acier de la porte blindée. Cogné jusqu\'à briser leur os creux sur ce dernier rempart, ce dernier espoir qui nous scinde en deux camps bien distincts : Celui des vivants et celui des "Pas-tout-à-fait-morts".</p>',
                '<p>Mes yeux se sont ouvert tard ce matin. Et quelque part je crois que j\'aurais préféré qu\'ils ne s\'ouvrent plus jamais.</p>'
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "typed"
        ],
        "messagecommiss" => [
            "title" => "Message à la commission",
            "author" => "Gizmonster",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "csaatk" => [
            "title" => "Menaces du CSA",
            "author" => "anonyme",
            "content" => [
                '<h1>AVIS À LA POPULATION</h1>
                <p>Devant la <strike>recrue</strike> recrudes<strike>s</strike>cence des actes de tortures animales, le <strong>Comité<strike>e</strike> de Soutien des Animaux</strong> de notre ville de Frontières de l\'automne cinglant, composé de courageux citoyens <strong>responsables</strong> et <strong>anonymes</strong>, a décidé de mener une action de repression "coup de poing".</p>
                <p>Il est demandé aux citoyens responsables de ces actes de barbarie de cesser immédiatement leurs agissements odieux, sous peine de subir notre <strong>vendetta sanglante</strong> dans les <strike>proch</strike> jours à venir.</p>
                <h1>Assassins, vos têtes tomberont !</h1>
                <h1><em>La paix dans vos coeurs.</em></h1>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "written"
        ],
        "warnad" => [
            "title" => "Mise en garde",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "jgoule" => [
            "title" => "Mon ami la Goule - 1ère partie",
            "author" => "ninjaja",
            "content" => [
                '<h2>Mon ami la Goule, première partie</h2>
                <p>Sur le trajet mon pied heurtât un petit carnet enfoui dans le sable et me fit trébucher.
                Les pages étaient vierges, le temps avait effacé les textes dont subsistait quelques lettres pâles.</p>
                <p>Deux, trois frottement de mine sur la semelle de ma vieille sandale redonna vit au stylo encore attaché à l\'ouvrage.
                Machinalement, je mis le tout dans ma poche. J\'étais loin d\'imaginer que je m\'en servirais si tôt.</p>',
                '<p>On nous avait indiqué une petite bourgade, la haut au nord, au milieu d\'une grande plaine désertique. Sir Trotk et moi même, accompagné de deux frangins Mido et Ven nous y rendions.</p>
                <p>L\'optique d\'une ville sans religion nous enthousiasmait. Peut être pourrions nous participer à l’ascension de Grotas, du Corbeau, ou du PMV.
                Mais cela aurait-il valu d\'être raconté ?</p>
                <p>N\'en parlons plus, la ville resta athée.</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoule2" => [
            "title" => "Mon ami la Goule - 2ème partie",
            "author" => "ninjaja",
            "content" => [
                '<h2>Mon ami la Goule, 2ème partie</h2>
                <p>Premier jour :</p>
                <p>Nous voici arrivés. Nous ne passons pas les portes ensemble par mesure de sécurité.
                La ville est en effervescence. De nombreuses personnes paraissent déjà se connaître. Sûrement d\'autres groupes tel que nous.</p>
                <p>Un homme semble sortir du lot et diriger les opérations.
                Nous l’appellerons M. N.
                "Monsieur" par respect pour son travail et son acharnement.
                "N." par pudeur, au vu de ce que nous lui feront subir par la suite.
                </p>',
                ' <p>A peine arrivé, nous nous préparons à repartir vers le désert.</p>
                <p>M. Mido a disparu. Il a appris l’existence d\'une armurerie en limite de la zone explorable.
                Il est déjà dessus. Nous ne le révérons que rarement, cet ancien architecte d\'intérieur dépouillera la région de tous ses objets brillants.
                Mes deux autres amis partent tranquillement explorer les environs, tandis que je me dirige vers une ruine fraîchement découverte.</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoule3" => [
            "title" => "Mon ami la Goule - 3ème partie",
            "author" => "ninjaja",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoule4" => [
            "title" => "Mon ami la Goule - 4ème partie",
            "author" => "ninjaja",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoule5" => [
            "title" => "Mon ami la Goule - 5ème partie",
            "author" => "ninjaja",
            "content" => [
                '<h2>Mon ami la Goule, 5ème partie</h2>
                <p>Un voyageur de passage arrive à son tour sur la ruine alors que nous discutons sans prendre la moindre précaution.
                Vient-il visiter le site ou récupérer la scie ? A t\'il entendu nos élucubrations ?</p>',
                '<p>Le dîner est servi !</p>
                <p>Le sang vicié de mon amie la goule ne fait qu\'un tour et elle s\'empresse de faire un casse-croûte avec notre visiteur incongru.</p>
                <p>Au vu des explorations de la journée nous allons être tout deux en tête de liste des suspects de ce petit déjeuner champêtre.</p>',
                '<p>Nous regagnons tranquillement la ville en suivant les agitations via nos radios de fortune. Comme prévu nous sommes en ligne de mire. L\'un de nous deux est forcement la goule.</p>
                <p>Autant prendre le vaccin de suite donc. Mon compagnon est redevenu normal, il a arrêté de baver même lorsqu\'il me reluque les fesses.</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoule6" => [
            "title" => "Mon ami la Goule - 6ème partie",
            "author" => "ninjaja",
            "content" => [
                '<h2>Mon ami la Goule, 6ème partie</h2>
                <p>Une agression non désirée est toujours imminente. Il faut nous disculper avant de rentrer en ville...</p>
                <p>Nous crions au scandale.... Quelqu\'un en as après nous c\'est sûr et tente de nous faire accuser.</p>
                <p>Le soir venu nous vidons nos gourdes et demandons à une personne de confiance de venir vérifier nos états aux portes de la ville.</p>',
                '<p>M. N. en personne fait le déplacement. Nous en profitons pour l\'innocenter également et gagnons un nouvel ami. Nous rentrons donc lavés de tous soupçons et en héros avec la scie !</p>
                <p>Troisième jour :</p>
                <p>Nouvelle visite de la ruine pour récupérer encore quelques une de nos trouvailles cachées.
                Accompagnés cette fois-ci par un inconnu, nos sac sont remplis d\'objets que la ville n\'a pas besoin de voir. Il faut bien porter l\'eau et la nourriture...</p>
                <p>A peine arrivé sur le bâtiment, le baby-phone grésille, les frangins ont trouvé un nouveau cadavre.
                (Ils ont également monté un lance-pile sophistiqué que je retrouverais dans mon coffre en rentrant... Ils ont un humour particulier...)</p>',
                '<p>Nous discutons donc, discrètement de la prochaine victime. M. N. est dangereux, mais faut il se tourner vers la facilité ? Non !</p>
                <p>Le choix se portera finalement sur l\'heureux possesseur d\'une ceinture de poche. Hé oui! Nos sacs commencent à ce faire petit, il faut bien trouver des alternatives.</p>
                <p>La nuit tombée, à l’abri des regards, nous procédons à une séance de troc entre amis : morceaux de cadavres, armes, cadenas. Rien n\'est gratuit dans ce monde.</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "jgoulb_fr" => [
            "title" => "Mon ami la Goule - 7ème partie",
            "author" => "ninjaja",
            "content" => [
                '<h2>Mon ami la Goule, 7ème partie</h2>
                <p>Quatrième jour :</p>
                <p>Aujourd\'hui, je pars seul m\'amuser avec les zombies. Je me prend tellement au jeu que je fini par taper dessus à main nu pour rejoindre la ville.</p>
                <p>La goule est restée entre les murs, à l’affût d\'un dîner potentiel car M. N. veille consciencieusement.
                Elle profite finalement d\'une minute d’inattention pour rejoindre un groupe en expédition et l\'attaquer. Sortie discrète et fumigène, elle mange proprement.</p>
                <p>Restée en ville pour "surveiller la goule", elle dresse aussitôt une liste farfelue des suspects potentiels avant M. N.</p>
                <p>Tout le monde se cale sur cette liste assez longtemps pour que les vraies pistes soient froides.</p>
                <p>En fin de journée, échaudés, les agressions commencent à tomber.</p>',
                '<p>Cinquième et sixième jours :</p>
                <p>Nous faisons des expés "nettoyage" pour ne pas rester en ville.</p>
                <p>Les agressions vont bon train, les frangins n\'y coupent pas et l\'un d\'eux est blessé. Heureusement nous avons de quoi le soigner.</p>',
                '<p>L\'ambiance est tendue en ville.</p>
                <p>La goule n\'attaquera pas. Peut-être est-elle morte dans le désert. Nous ne contredirons pas cette théorie.</p>
                <p>Il deviens vraiment dangereux de se nourrir.</p>
                <p>Mais demain Trotk sera obligé de tuer, il ne pourra pas vaincre la mort tous les jours...</p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "letwar" => [
            "title" => "Mort d'un baroudeur",
            "author" => "Planeshift",
            "content" => [
                '<p>Me voilà bien.</p>
                <p>Trop confiant dans mes capacités, je me suis éloigné de la ville sans faire attention. Et comme de juste, je me suis retrouvé en compagnie de quelques zombies. Youpi. Heureusement, j\'ai réussi à les tuer sans problème, mais hélas pour moi, cela m\'a épuisé. Me voici donc comme un idiot, assis sur une moitié de zombie, en train d\'écrire l\'histoire de ma triste et courte vie. Je l\'ai appelé Léon Le zombie. Pas mon histoire.</p>',
                '<p>Je reste optimiste, malgré tout. Peut-être que je mourrais sans souffrir, hein ? Et puis, je devrais apprécier ma chance : je dois être l\'un des rares êtres vivants des environs à voir ce magnifique coucher de soleil, insouciant de mon sort. N\'est-ce pas Léon ?</p>
                <p>Vous ai-je dit que je n\'avais pas achevé Léon ? Ah, il s\'agite. J\'ai beau être assis sur son dos et utiliser sa tête poisseuse comme un repose-pieds, il lui manque peut-être un bras et </p>',
                '<p>tout ce qui se trouve sous son bassin, il continue à s\'agiter pour me dévorer. Il est mignon, n\'est-ce pas ?</p>
                <p>La nuit tombe, et je crois apercevoir d\'autres compagnons de jeu<strike>x</strike> approcher. Eh bien, qu\'il en soit ainsi ! Sur ce, je vous laisse, j\'ai des gens à aller tuer. Et après, peut-être que j\'aurai la chance de vous dévorer, qui sait ?</p>'
            ],
            "lang" => "fr",
            "background" => "tinystamp",
            "design" => "small"
        ],
        "ultim1" => [
            "title" => "Mort ultime",
            "author" => "IIdrill",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "poem"
        ],
        "cave1" => [
            "title" => "Mot déchiré",
            "author" => "gangster",
            "content" => [
                '<p>Saletées, elles m\'ont eu ... je suis reclu dans ma cave, la porte est frappée, des grognement sourds résonnent dans mes oreilles, ma colonne vertébrale me brule c\'est atroce ...</p>
                <p>Ils les ont tous eu, tous dévorés vivants, je n\'ai réussi qu\'a prolonger ma vie de quelques heures mais je sais que ma fin est proche, j\'ai été mordu au molet...</p>
                <p>Aucune issue possible, ce n\'est plus qu\'une question de minutes, la porte va cèder sous les coups de ces choses, ils ne sont plus humains.</p>
                <p>Je préfère mourir que devenir comme ça ...</p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "written"
        ],
        "teia" => [
            "title" => "Note d'un citoyen banni",
            "author" => "???",
            "content" => [
                '>
                <img src="http://www.hordes.fr/gfx/fanart/worldmap03.png" alt="">'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "written"
        ],
        "code1" => [
            "title" => "Note illisible",
            "author" => "anonyme",
            "content" => [
                ' <p><strong>-1</strong></p>
                <p>oktr qhdm m drs rtq</p>
                <p>st cnhr pthssdq kz uhkkd zt oktr uhsd</p>
                <p>hkr rnms sntr cdudmtr entr hbh</p>
                <p>qdsqntud lnh z kz uhdhkkd onlod gxcqztkhptd z bhmp gdtqdr</p>
                <p>hk x z tmd lnsn bzbgdd kz azr</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "written"
        ],
        "ie" => [
            "title" => "Note pour les prochains promeneurs",
            "author" => "coctail",
            "content" => [
                '<p>Coctail, Pantocrat et Zoby sont passés ici. Cette zone ne contient plus rien d\'utile. Attention aux zo<strike>m</strike>b<strike>ie</strike>s cachés sous le sable. Danger de mort.</p>
                <div class="other">&nbsp;c\'est bon, j\'ai fait le ménage !!</div>
                <div class="other">&nbsp;&nbsp;&nbsp;- half</div>'
            ],
            "lang" => "fr",
            "background" => "money",
            "design" => "written"
        ],
        "thonix" => [
            "title" => "Notes de Thonix",
            "author" => "Thonix",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "nightm" => [
            "title" => "Nuit courte",
            "author" => "elfique20",
            "content" => [
                '<p>Je me suis encore réveillé en nage.</p>
                <p>La nuit a été longue, chaque ombre et chaque bruit suspect me fait penser à ce qui ce trouve dehors. Ils sont là, ils sont partout et toujours plus nombreux. Cela fait maintenant 17 jours que je me suis réveillé dans cette ville. Depuis plus de la moitié des citoyens ont disparu. Ils ne sont jamais revenus de l\'extérieur, ce sont toujours les plus valeureux qui partent en premier, certains restent bien au chaud cloîtrés chez eux a renforcer leurs baraque sans penser aux autres?..</p>
                <p>... Je ne vais pas sortir aujourd\'hui, je vais aider les autres pour la construction des défenses. Certains sont sortis après l\'attaque juste pour ramener du petit bois et des défenses, mais cela suffira-t-il ? J\'en doute. On y passera tous un jour ou l\'autre...</p>
                <p>... A demain, si je suis toujours en vie. La soif et la faim me tiraillent l\'estomac. Que dieu nous protège...</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "written"
        ],
        "cold1" => [
            "title" => "Obscurité glaciale",
            "author" => "Planeshift",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "ode" => [
            "title" => "Ode aux corbeaux",
            "author" => "Firenz",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "page51" => [
            "title" => "Page 51 d'un roman",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "rednck" => [
            "title" => "Page de carnet déchirée",
            "author" => "Savignac",
            "content" => [
                ' <div class="hr"></div>
                <p>Des trucs de politicars, ou des trucs de toubibs qui ont foiré qu\'ils disaient. Mouais, en tous cas, le fric vaut plus rien ici, et j\'ai osé échanger mes clopes contre un peu de bouffe. J\'ne sais pas trop si des gars ont pu survivre, moi, j\'me suis réfugié dans mon ranch du Texax avec mon bon vieux fusil. A bien y réfléchir, les curés avaient ptet raison, on a sûrement fait trop de conneries. Ouep toutes ces expériences sur le clonage, sur les électrochocs sur les cadavres, toutes ces conneries là, c\'était trop pour Dieu.</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "small"
        ],
        "citsig" => [
            "title" => "Panneau de ville",
            "author" => "coctail",
            "content" => [
                '<div class="hr"></div>
                <center>
                <big>Terres de l\'abîme.</big>
                <div>4<strike>0 hab</strike>itants.</div>
                <div class="other"><strong>Ville zombie, PAS de survivant. Fouillée et hantée. DANGER !!!</strong></div>
                </center>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "written"
        ],
        "condm" => [
            "title" => "Paroles d'un condamné",
            "author" => "Arma",
            "content" => [
                '<p>J\'ai froid, la nuit vient de tomber et je suis toujours à l\'extérieur de la ville, je crois que ma jambe est cassée... De toute façon je suis perdu, les <strike>cadav</strike>Morts-vivants m\'ont poussé vers des dunes lointaines...</p>
                <p>Je vais mourir... Ma famille me manque...</p>
                <p>Toi, qui lis ces mots, dis leur que je les aime et que j\'ai toujours pensé à eux...</p>
                <p>Ils sont partout, et pourtant, ils m\'observent, sans bouger. Ils... attendent ?</p>
                <p>Périr est... réconfortant. La vie n\'est qu\'un éternel stress devant la multitude de chemins que le <strike>futu</strike>Destin nous dessine...Je crois...</p>
                <p>Je n\'ai plus le choix, <strike>je d</strike>il ne me reste plus qu\'une route à suivre. Peut-être la meilleure de toutes?</p>
                <p>J\'entends au loin les douze coups de minuit. C\'est fini...</p>
                <p>Ne m\'oubliez pas.</p>'
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "small"
        ],
        "hangng" => [
            "title" => "Pendaison",
            "author" => "Liior",
            "content" => [
                ' <p><em>Le chef :</em> Tu n\'aurais jamais du te servir, encore moins sans en parler à personne. Tu sais bien que cela n\'est facile pour personne de se passer de quelqu\'un, surtout en ce moment, où nous avons besoin de tous les bras.</p>
                <p><em>Le voleur :</em> C\'était insupportable, je mourais de faim.. Soyez indulgents.. Je vous jure que je ne le referais plus...</p>
                <p><em>Le chef :</em> Je sais que tu regrettes, mais nous ne pouvons pas ne pas te punir, les gens le prendraient comme une faiblesse et penseraient qu\'ils peuvent, eux aussi voler impunément.</p>
                <p><em>Le voleur :</em> S\'il vous plait, enlevez moi cette corde, je vous demande de réfléchir encore un peu, vous n\'aurez qu\'à expliquer aux autres que cela passe pour cette fois mais que c\'est la dern...</p>'
            ],
            "lang" => "fr",
            "background" => "old",
            "design" => "classic"
        ],
        "alcthe" => [
            "title" => "Pensées sur Post-its",
            "author" => "coctail",
            "content" => [
                '<p>La ville attire de plus en plus de zombies.</p>
                <p>Sortons tous de la ville : si les zombies veulent entrer, qu\'ils entrent, nous, on aura le désert.</p>
                <p>On finira bien par trouver assez de ressources pour construire une nouvelle ville.</p>',
                '<p>J\'ai trouvé un moteur et un cochon.</p>
                <p>Avec le moteur et un peu de ferraille, une caisse à outils et un peu d\'alcool, je peux fabriquer un camion.</p>
                <p>Alimenté par l\'alcool, l\'engin pourrait nous transporter tous loin de tous ces cadavres mouvants.</p>
                <p>(le cochon ne sert à rien)</p>',
                '<p>Les zombies s\'ammassent chaque nuit devant nos maisons.</p>
                <p>Ils doivent rechercher quelqu\'un en particulier; c\'est pas possible sinon. Tirons à la courte paille et jettons quelqu\'un dehors chaque nuit pour savoir si c\'est lui qu\'ils cherchent.</p>
                <p>Après un moment, nous aurons bien trouvé de qui il s\'agit et les autres seront tranquilles.</p>',
                '<div class="hr"></div>
                <p>Je vais lire un conte d\'horreur aux zombies, il n\'oseront plus jamais revenir.</p>',
                '<p>Dans le désert, les zombies font les malins. dans la ville, c\'est nous.</p>
                <p>Baissons le prix des terrains, construisons des stades de foot, implantons un Mac Donald\'s, faisons venir des célébrités, construisons des HLM.</p>
                <p>Nous aurons une métropole et les zombies serons relégués dans des réserves.</p>',
                '<div class="hr"></div>
                <p>Je vends des costumes d\'Halloween. Les zombies ne vous reconnaîtront pas.</p>',
                '<p>Les zombies n\'aiment pas l\'eau, c\'est connu. On peut les tuer avec des pistolets à eau.</p>
                <p>Mettons-nous à chanter pour faire pleuvoir. Nous serons tranquilles.</p>',
                '<p>Les zombies essaient d\'entrer en ville.</p>
                <p>Quelqu\'un a-t-il déjà essayé de leur parler ? Quelqu\'un a-t-il déjà essayé de savoir ce qu\'ils veulent ?</p>
                <p>Ils veulent peut-êter juste être comme nous ?</p>
                <p>Je me souviens d\'un concert, les fans du monsieur qui chantait torse nu voulaient monter sur le podium.</p>
                <p>Ce sont peut-être juste nos fans ?</p>',
                '<p>Pourquoi a-t-il fallu construire cette ville JUSTE dans un couloir de migration de zombies ?</p>
                <p>Qui a eu cette idée ?</p>',
                '<p>zombies par-ci, zombies par-là. Mais vous êtes tous obsédés ou quoi ?</p>
                <p>Et ... ne me dites pas que personne n\'est exorciste ici !!!</p>',
                '<p>Je viens de développer mon sixième sens... Je vois des morts.</p>
                <p>Le problème c\'est que les morts me voient aussi...</p>',
                '<p>Au fond, pourquoi ne laisse-t-on pas entrer les zombies ?</p>
                <p>Ils nous mangeraient, nous deviendrions des zombies aussi et tous les zombies seraient heureux...</p>',
                '<p>Il faut deux zombies pour bloquer un citoyen, mais un seul citoyen pour bloquer deux zombies.</p>
                <p>Ils veulent peut-être juste l’égalité des votes et, tout en manifestant, prennent la ville pour un groupe de CRS...</p>
                <p>... Forcément, les citoyens la font ressembler à un château-fort.</p>',
                '<p>Depuis des années, les vivants ont mis les morts dans les tombes. Les morts en ont marre et en sont sortis.</p>
                <p>Pourquoi ne pas l’accepter et nous cacher dans leurs tombes ? Ils ne penseront JAMAIS à nous chercher là-bas !</p>',
                '<p>Les zombies nous admirent. La journée, nous sortons faire des expéditions et la nuit, nous rentrons dans la ville.</p>
                <p>Eux aussi, la journée, ils sont dans le désert et essaient même de nous garder avec eux, preuve qu’ils nous aiment. La nuit, ils veulent dormir avec nous...</p>',
                '<div class="hr"></div>
                <p>Une ville, c’est comme un bocal. Tout le monde vient regarder ce qu’il y a dedans. De temps en temps, on en pèche un ou deux vivants pour les manger...</p>',
                '<p>Mais arrêtez de tirer bon sang !!! Ce ne sont pas des zombies, c’est une rave-party !!!</p>',
            ],
            "lang" => "fr",
            "background" => "postit",
            "design" => "postit"
        ],
        "popoem" => [
            "title" => "Poème amer",
            "author" => "Bovin",
            "content" => [
                '>
                <p>Mon nom s\'efface dans les mémoires tout comme il le fut dans les écrits.
                Mon corps se dégrade, s\'éparpille, se décompose à travers le monde connu.
                Mes paroles s\'oublient tandis que seuls les discours vides de sens restent.
                Mes récits ne sont plus que poussière pendant que leur signification s\'évapore.
                Ma conscience se morcèle tandis que je recherche une enveloppe où m\'incarner.
                Ma flamme de rhéteur seule tentera de rester éveillée.
                </p>
                <p>
                Voici comment je partis de l\'autre côté, damné pour l\'éternité.
                Je suis condamné à toujours hanter un nouveau corps.
                </p>
                <p>
                Mon nom est Bovin, et je suis mort.
                </p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "bricot" => [
            "title" => "Prospectus Brico-Tout",
            "author" => "sanka",
            "content" => [
                '<p>Vous en avez assez de trouver tous vos tournevis cassés ? Marre de devoir emprunter la tondeuse du voisin car la votre est toujours en panne ?</p>
<p>Et bien tout ceci est enfin terminé grace à votre nouveau&nbsp;:</p>
<h1>BRICO-TOUT<small>7 place des Molineux</small></h1>
<small>Pour l\'occasion, une journée portes ouvertes est prévue le 15 juin avec 25% de réduction sur tous les articles pour les 50 premiers clients alors surtout ne traînez pas!!! </small>
<h1>Pillez-nous avant que d\'autres ne s\'en chargent pour vous !</h1>
<div class="hr"></div>
<small>Ne pas jeter sur la voie publique.</small>'
            ],
            "lang" => "fr",
            "background" => "manual",
            "design" => "modern"
        ],
        "adbnkr" => [
            "title" => "Publicité Bunker-4-Life",
            "author" => "zhack",
            "content" => [
                '<h1>Nouveau !!</h1>
                <p>Votre ancienne vie vous manque ?</p>
                <p>Vous en avez assez de vous terrer dans votre habitation en espérant passer la nuit ?</p>
                <p>Les cris de votre voisin vous exaspèrent ?</p>
                <h1>Nous avons la solution !!!</h1>
                <p>Nous vous offrons la possibilité de vous abriter dans l\'un de nos nombreux bunkers 5 étoiles.</p>',
                '<h1>Fantastique !!</h1>
                <p>Au programme :</p>
                <p>-une pièce de 10 mètres carré avec électricité et eau <sup>1</sup></p>
                <p>-une communauté accueillante et chaleureuse</p>
                <p>-des soins et une nourriture adapté à chacun <sup>2</sup></p>
                <p>-Lumière artificielle remplaçant le soleil ! Vous retrouverez enfin le teint de votre jeunesse.</p>
                <p>-La télé ! <sup>3</sup></p>
                <p>-Et de nombreuses activités pour le plaisir de chacun !</p>
                <h1>Incroyable !!</h1>
                <p>Alors n\'hésitez plus ! Contactez nous  au 3-Bunker-4-life.</p>',
                '<small>
                <div class="hr"></div>
                <ol>
                <li>Eau potable en option. L\'électricité vous sera fournie pendant les horaires définie par le régisseur.</li>
                <li>Dans la limite des stocks disponible.</li>
                <li>Nous ne garantissons pas la disponibilité des chaines télés.</li>
                </ol>
                <p>La société bunker for life se réserve le droit de regard sur chaque dossier.</p>
                <p>Bunker for life  est une filiale de Motion-Twin . Ces marques sont soumises à un copyright.</p>
                <p>Pour votre santé, mangez au moins cinq fruits et légumes par jour.</p>
                </small>'
            ],
            "lang" => "fr",
            "background" => "stamp",
            "design" => "ad"
        ],
        "puree" => [
            "title" => "Purée de charogne",
            "author" => "Zorone",
            "content" => [
                '>
                <h1>Pensée d\'un gourmand</h1>
                <p>Certains racontent que les purées de charognardes seraient en fait les conséquences des premiers expéditionnaires envoyés depuis une catapulte.<br>
                Quel bande d\'idiots.<br>
                Les expéditionnaires sont beaucoup plus goûtus.</p><br>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "fishes" => [
            "title" => "Pêche",
            "author" => "Irmins",
            "content" => [
                '<p><strong>Registre de la Ville: Espoirs Retrouvés, le 11 novembre<strike> 1966</strike></strong></p>
                <p>Depuis hier, nous regagnons l\'espoir de survivre ! Les créatures déferlent les unes après les autres sur les portes de la ville, chaque jours plus nombreuses ... Nous avons foré les nappes phréatiques, et notre puis nous permettra de tenir plus de 3 mois sans problèmes d\'eau ... Nos canons a eau fonctionnent a plein régime <em>[...]</em> de moins en moins nombreux <em>[...]</em>.</p>
                <p>Hier, nos éclaireurs sont partis avec leurs motos en direction de l\'Est <em>[...]</em> Grande découverte <em>[...]</em> changera nos vies a tout jamais ! Après plusieurs jours de progression dans l\'outre monde, ils ont trouvés <em>[...]</em> d\'eau, <em>[...]</em>possibilité de construire un bateau <em>[...]</em> système de pompage et de filtrage <em>[...]</em></p>
                <p>Nous nous interrogeons sur l\'état des poissons... Sont ils vivants et comestibles, ou se sont ils transformés en Zombie également ? Dans le premier cas, un bon bain de mer et du poisson frit nous remonteraient le moral ! Dans le deuxième <em>[...]</em> </p>'
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "army1" => [
            "title" => "Rapport d'opération Nov-46857-A",
            "author" => "zhack",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "poem"
        ],
        "heli1" => [
            "title" => "Rapport d'une unité de soutien",
            "author" => "sanka",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "poem"
        ],
        "truck1" => [
            "title" => "Rapport de combat 1",
            "author" => "sanka",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "printer",
            "design" => "poem"
        ],
        "repor5" => [
            "title" => "Rapport de ville 1",
            "author" => "Arma",
            "content" => [
                '<h1>Rapport du cinquième Jour</h1>
                <p>La journée était bonne. Tout s\'est déroulé à merveille. Nos stocks de ressources se sont remplis d\'une manière fulgurante, les plus intrépides d\'entre nous sont revenus avec de nombreuses provisions et nous avons même eu le temps de creuser la tombe de La-Teigne, le chien.</p>
                <p>Miwako, la pharmacienne du village, est très satisfaite. Tout le monde semble en pleine forme et nous avons assez de médicaments pour tenir des semaines. Avec l\'aide d\'autres citoyens ingénieux, elle a échafaudé de nombreux plans de défense. Nous nous sommes mis directement au travail. Les constructions se sont terminées étonnamment vite, il faut dire que les bonnes blagues de Max ont remonté le moral de la troupe. Nous avons maintenant un vrai labyrinthe de trous, de scies, de pieux, d\'explosifs devant la ville. Personne ne pourra passer !</p>
                <p>La nuit va être tranquille. Nous allons enfin pouvoir dormir. J\'espère que les autres villes se débrouillent...</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "small"
        ],
        "refabr" => [
            "title" => "Refuge Abrité",
            "author" => "Loadim",
            "content" => [
                '<p><em>Refuge Abrité.</em> C\'était un nom bien choisi. Treize morts cette nuit. S\'épuiser à traîner les corps, fouiller les coffres. Il a fallu renverser des tentes lacérées pour récupérer quelques vis, un déchet moisi qu\'on allait avaler. Le courage d\'une poignée de gens peine à ralentir les hordes.</p>
                <p>On a pourtant réussi à creuser, à dresser des pièges. Ils sont passés. Ils se sont nourris. Quand je pense que si nous n\'avions pas assemblé un atelier précipitamment nous serions déjà tous... Une semaine à peine et ils sont innombrables. </p>
                <p>Nous n\'épargnons plus nos rations. Ils nous dévoreront bientôt, et nous en emporterons le plus possible avec nous. Je sens encore ce crâne décomposé éclater sous mon poing. Le sursis de la ville sera court mais pas la terreur mes camarades, mes chers camarades.</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "small"
        ],
        "outrat" => [
            "title" => "Rongeur reconnaissant",
            "author" => "lerat",
            "content" => [
                '<p>Un pauvre petit ouvrier s\'est aventuré dehors aujourd\'hui, dans l\'outre-monde, désert hostile, dangereux, parsemé de pièges vicieux.</p>
                <p>Il a suffit d\'un rien, une pierre un peu trop imposante pour mon pied fébrile et ce fut la chute.</p>
                <p>Après un roulé-boulé dans la poussière j\'ai levé la tête, cherché mes compagnons d\'expédition. Mais j\'étais seul... Enfin... Seul en vie . Partout autour de moi des zombies. Onze. Toute une équipe de foot.</p>',
                '<p>La réalité m\'a frappé de plein fouet, la panique a empli mon coeur trop fragile, j\'ai hurlé, j\'ai appelé.</p>
                <p>Ils m\'ont répondu, m\'ont rassuré, ont mis leurs méninges fatiguées à contribution et m\'ont finalement envoyé une équipe de sauvetage. La panique a cédé la place à la reconnaissance, à l\'émotion. Malgré mon erreur impardonnable ils sont venus, ne m\'ont pas abandonné au triste sort qui m\'attendait.</p>
                <p>Je vous remercie, je vous aime.</p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "necro" => [
            "title" => "Rubrique nécrologique",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "cutleg" => [
            "title" => "Récit d'un habitant",
            "author" => "coctail",
            "content" => [
                '<p>J\'ai alors tailladé la jambe à Ervin. Il m\'a regardé sans comprendre. Je lui ai dit : « Tu vois les zombies là, en haut de la colline ? Toi, tu es jeune, moi, je suis vieux. Tu courrais plus vite que moi, mais plus maintenant. Merci de m\'avoir attendu.  Et n\'oublie pas mon garçon : quand la horde arrive, il ne faut pas courir vite, il ne faut juste pas être celui qu\'ils vont rattraper. Maintenant, menotte-toi à la poutre, je ne voudrais pas te tirer dans la jambe, les munitions sont si rares... Bonne chance, je les entends arriver... Et merci de m\'avoir passé ton fusil.</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "typed"
        ],
        "lords1" => [
            "title" => "Récits de LordSolvine, partie 1",
            "author" => "lordsolvine",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "lords2" => [
            "title" => "Récits de LordSolvine, partie 2",
            "author" => "lordsolvine",
            "content" => [
                ' <h2>Jour 2 après l\'accident</h2>
                <p>Les matins ne sont plus ce qu\'ils étaient. La légère brise qui caressait mon visage le brûle à présent. L\'absence d\'eau se fait sentir, mes pas adoptent un rythme de désespoir, la fin est proche. Si je m\'en sors, je serais marqué... A vie.</p>
                <p>Je les entends au moment où je vous écris... Les Autres comme je les nomme. Ils crient, pas de douleur... Oh non ! Ils crient de faim. Je ne vais pas m\'attarder et continuer à tracer mon chemin... Principal but, trouver de l\'eau... Potable ou non.</p>',
                '<p>Voilà 5 heures que je ne t\'avais pas touché ce cher journal... Mais j\'ai une grande nouvelle: mon existence est sauvée pour un temps indéterminé. J\'ai croisé et pris part à la vie... Celle d\'un village. Quel bonheur. Des hommes et des femmes, ensemble, pour survivre. Un puits, des ressources, je vais m\'investir.</p>
                <p>Etrangement, ils sortent... Pourquoi sortir quand on est protégé à l\'intérieur ? Un collecteur du nom de Zetide m\'a expliqué le tout en détail... Les expéditions, les ressources, les odeurs que l\'on dégage et qui attirent les Autres... Ils les appellent Zombies. Ces morts-vivants qu\'on ne voit que dans les films...Qu\'on ne voyait que dans les films.</p>
                <p>Demain, c\'est décidé, je sors.</p>
                <p> </p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "typed"
        ],
        "adaper" => [
            "title" => "Réclame Overture technology",
            "author" => "Sengriff",
            "content" => [
                '<h1>Combinaison Radix</h1>
                <p>Un système de blindage antiradiation parmi les plus fiables du marché : garanti quarante ans sans mutation gênante ! <sup>1</sup></p>',
                '<h1>PTDG/C Mark II</h1>
                <p>Peur de la mauvaise ambiance ? Pas d\'inquiétude. Les <strong>PDTG (C)</strong> vous permettront, d\'une pile bien ajustée, de vous débarrasser définitivement des problèmes de voisinage ! Au contraire, si l\'ambiance est à la rigolade, les projectiles de paintball s\'adaptent parfaitement au canon, pour la plus grande joie des petits et des grands !</p>',
                '<h1>Traitements Overture-Cyanozen</h1>
                <p>Vous êtes cardiaque ou cancéreux et vous manquez de soins ? Toutes les pharmacies des Abris seront munis d\'un traitement express et révolutionnaire à tous les maux : la pilule de Cyanozen (contient du cyanure) ! De quoi oublier rapidement toutes vos douleurs.</p>',
                '<small>En cas de problème technique (explosion de la pile nucléaire, défaillance du système de filtrage, impossibilité de fermer le sas, effondrement du plafond) Overture Technology vous propose une assistance sous six mois à un an <sup>2</sup> ; et grâce au performant <strong>GECK</strong> (Guide d\'Évaluation des Chances et du Karma), vous pourrez facilement déterminer vos probabilités de survie jusque là et comment les optimiser grâce à divers facteurs (cannibalisme, rationnement d\'eau, etc).</small>
                <small>Souscrivez au programme : « <strong>Être à l\'abri, c\'est être en vie</strong> » : protection assurée contre les cataclysmes nucléaires, écologiques et les épidémies, pour la modique somme de soixante mille francs : la meilleure des assurances vies !</small>
                <small><strong>Ce que l\'avenir vous promet, Overture Technology vous en protège.</strong></small>
                <small>1. offre non-valable pour les êtres cellulaires.</small>
                <small>2. jours ouvrables uniquement. </small>'
            ],
            "lang" => "fr",
            "background" => 'stamp',
            "design" => "stamp"
        ],
        "sadism" => [
            "title" => "Sadique",
            "author" => "esuna114",
            "content" => [
                '<p>J’étais... sadique.</p>
                <p>Depuis le début, la seule force qui me poussait à survivre était de voir ces zombies massacrés, sanguinolents, le corps écartelé, les yeux arrachés. Je me complaisais dans cette boucherie. D’autres sont effrayés à la suite de ce spectacle horrible, et préfèrent se terrer dans leur maison, en attendant tout simplement leur fin. Personnellement, je ne voulais pas voir s’arrêter ce maelström de violence, ce feu d’artifice morbide,  à aucun prix. Que cela soit au bâton, pour faire durer le plaisir, au couteau, pour les plus professionnels,  plus les techniques étaient variées, plus j\'avais envie de voir cette ville survivre longtemps. Je ne tirais que peu de complaisance de voir mes collègues massacrés, non pas parce cela me peinait, mais parce que la méthode des zombies manquait fortement d\'originalité tout au long des attaques de ces 18 journées.</p>',
                '<p>C\'est alors que je trouvais, la panacée, que dis-je, le Saint Graal de la souffrance, des plaies écorchées, des effusions de sang! Nous avons eu besoin de nos dernières ressources pour "le" construire. Cette machine démoniaque, cette engeance de l\'enfer mécanique, nous l\'avons baptisé le "lance-tôle". Cette création m\'a procuré un plaisir incroyable lors de la dernière attaque, les zombies explosant littéralement de toute part, leurs membres épars étant à leur tour déchiquetés par cet impitoyable destructeur.  Ahhh, quel plaisir... </p>
                <p>Aucune autre invention ne surpasse celle-ci.</p>
                <p>J\'attends avec impatience cette nouvelle nuit...</p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "nohope" => [
            "title" => "Sans espoir",
            "author" => "Boulay",
            "content" => [
                '<p>Il est près de minuit, et depuis l\'extérieur de la ville me parviennent les bruits de la horde en train de se rassembler. Combien de fois avons-nous déjà repoussé les zombies? Trop.</p>
                <p>Hier, nous avons eu du mal à repousser l\'attaque, certaines de ces horribles créatures ont envahi la ville, de nombreux citoyens sont morts. J\'ai retrouvé la tête de mon voisin dans le puits ce matin. J\'ai échappé à leurs crocs de justesse, en me cachant sous un tas de détritus. Mais maintenant, les détritus sont en grande partie des morceaux de nos amis décédés. Impensable de retourner s\'y cacher.</p>',
                '<p>Nous ne sommes plus qu\'une poignée dans la ville. Beaucoup de personnes qui se sont suicidées, les autres étant définitivement terrorisés. Je suis le seul être vivant encore sain d\'esprit, même la poule tremble nerveusement. Ce qui me fait penser que demain, nous serons tous morts. J\'ai passé la journée à démonter les abris des morts pour me confectionner une véritable forteresse, à grands renforts de vitamines, mais ça ne suffira certainement pas. Je ne me fais aucune illusion sur l\'avenir. Il est obscur.</p>
                <p>Ceci était mon message d\'adieu au monde des vivants. Si jamais un jour, contre toute vraisemblance, quelqu\'un lit ces lignes, qu\'il sache qu\'il ne sert à rien de lutter pour vivre. Il n\'y a aucun espoir, à quoi bon souffrir pour repousser l\'échéance?</p>'
            ],
            "lang" => "fr",
            "background" => "white",
            "design" => "typed"
        ],
        "savoir" => [
            "title" => "Savoir-vivre en société",
            "author" => "Than",
            "content" => [
                '>
                <p>J’va t’dire mon gars, quand t’a faim, tu r’garde pas c’te tu manges !<br>
                Sûr qu’un p’tit chat c’est décoratif pis qu’ça fait d’la compagnie dans ta cahute. Mais j’vais t’dire : un p’tit chat bien préparé bah c’est goûtu pis ça ravigotte !!<br>
                Pis une fois qu’t’as la main, le serpent, ça s’fait bien aussi. Moins savoureux mais niveau tendresse de la viande ça s’pose là !<br>
                Alors oué. Le citoyen Martin l’était pas mauvais non plus. L’truc c’est qu’j’aurais pas dû partager avec les autres. Z’ont paniqués les autres !<br>
                M’ont collé en cage les gueux !<br>
                … y fait faim…</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "shun1" => [
            "title" => "Shuny : Témoignage des derniers jours",
            "author" => "Shuny",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "surviv" => [
            "title" => "Survivre",
            "author" => "Liior",
            "content" => [
                '<h2>Le 3 septembre :</h2>
                <p>Le corbeau tourne en dessus du village, il ne sait plus où donner de la tête, car les cadavres jonchent le sol de la place. Certains bougent encore, malgré que je n\'en aie trouvé aucun de « cliniquement » vivant. Je le regarde faire ses tours pendant des heures, et je me demande lequel de mes amis va pouvoir lui servir d\'encas. Je suis libre comme l\'air, pendant encore quelques heures. Je peux me promener, entrer chez les gens, me servir de leurs affaires et prendre ce qui me plait à la banque. Je suis seul, et je suis perdu. Je crois que la drogue que j\'ai pris en banque commence à faire son effet. </p>',
                '<p>Je me sens fort mais faible à la fois. Je sais que ce soir, je serai seul face aux hordes. Dernier survivant de mon village, j\'ai très peur, mais je ressens comme une sorte de fierté. Celle-ci ne tiendra pas quand je serai devant les zombies du soir. Une fierté bien éphémère.</p>'
            ],
            "lang" => "fr",
            "background" => "noteup",
            "design" => "written"
        ],
        "nice" => [
            "title" => "Sélection naturelle",
            "author" => "stravingo",
            "content" => [
                ' <p>On dit souvent de moi que je suis d\'une gentillesse sans pareille.</p>
                <p>C\'est vrai, j\'aime rendre service, m\'acquitter de tâches qui rebuteraient pourtant bien d\'autres. Je m\'investit sans compter pour la communauté, prodiguant des encouragements aux plus faibles.</p>
                <p>Je suis toujours d\'une extrême politesse. On m\'admire d\'ailleurs pour mes talents de négociateur. Lorsqu\'il y a des tensions, qu\'éclatent des altercations, les gens sont tout de suite soulagés lorsque je m\'en mêle. Ils savent que la paix va très vite revenir.</p>
                <p>J\'inspire confiance et les gens ont confiance en moi.</p>
                <p>Aujourd\'hui, ils sont tous partis en expédition pour trouver de quoi subsister un jour de plus.</p>
                <p>Je les entend frapper à la porte depuis des heures maintenant. Les insultes ont fait place aux supplications. Il est vrai que la nuit tombe rapidement.</p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "theor1" => [
            "title" => "Théories nocturnes 1",
            "author" => "Planeshift",
            "content" => [
                '<p>mais la question que je me pose, c\'est comment nous sommes arrivés ici. J\'ai plusieurs théories, toutes aussi inquiétantes les unes que les autres. Plus personne ne veut répondre à mes questions, maintenant. Ils croient que je suis fou, une sorte de nouvelle sorte de zombification. Les idiots. Si seulement ils savaient?</p>',
                '<p>Comment sommes-nous arrivés ? Je ne me souviens de rien. Lorsque je presse les autres de questions, ils s\'énervent, me repoussent. Mais je vois leurs regards, oh, oui, je les vois ! Eux aussi sont effrayés, car ils ne savent pas. Ils ont oublié, tout comme moi. Pourtant, je sens que je sais, au fond de moi. Quelque part, mon âme connaît le dessein caché derrière tout cela.</p>',
                '<p>Et si c\'était cela la réponse ? L\'âme ! Mais alors... Cela voudrait donc dire que [...]</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "modern"
        ],
        "theor2" => [
            "title" => "Théories nocturnes 2",
            "author" => "Bigmorty",
            "content" => [
                '<p>Minuit, j’ouvre les yeux. Les premiers grognements résonnent... chaque nuit c’est le même cauchemar. D’abord les premiers raclements, puis les grattements, les grondements et enfin leurs poings, leurs ongles... parfois leur tête qui résonnent sur les murailles. Un martèlement qui me terrifie... surtout quand les portes grincent sinistrement... un martèlement qui rend fou les plus fragiles d’entre nous... mais ne le sommes nous tous pas déjà ? Parfois je crois les voir abattre nos défenses, dévorer mes camarades... j’entends les hurlements, les hoquets de terreurs, les larmes de désespoir et toujours les grognements sourds...</p>',
                '<p>Certaines nuits je les vois fracasser ma maison, remplir mon espace comme une marée putride et démoniaque... je ne lutte jamais longtemps et, l’espace d’un court instant, j’entends nettement ma peau qui se déchire, les ligaments se rompre, mes os craquer... tout est tellement réel et pourtant... je me réveille à chaque fois dans mon lit, en sueur... A chaque réveil des gens différents mais le même cauchemar en commun... </p>
                <p>Est ce l’enfer ? Sommes nous damnés ? Ou une succession de mauvais rêves ? </p>
                <p>Quand vais-je enfin me réveiller ?</p>'
            ],
            "lang" => "fr",
            "background" => "grid",
            "design" => "modern"
        ],
        "wintck" => [
            "title" => "Ticket gagnant",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "tinystamp",
            "design" => "small"
        ],
        "lostck" => [
            "title" => "Ticket perdant",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "tinystamp",
            "design" => "small"
        ],
        "crema1" => [
            "title" => "Tirage au sort",
            "author" => "stravingo",
            "content" => [
                '<quote>Refuge des illusions perdues, le 19 février</quote>
                <p>Cela fait des <strike>jour</strike>semaines que nous n\'avons plus de nourriture. La faim nous tenaille mais nous ne pouvons pas sortir<strike>e</strike>. Au-delà de nos maigres barricades, les créatures sont maintenant beaucoup trop nombreuses. Je n\'en peux plus. Je n\'ai plus la force.</p>
                <p>Ce matin, nous avons tiré au sort. C\'est tombé sur moi mais je m\'en fiche. Les autres m\'ont presque envié. Je sens à l\'odeur du charbon de bois qu\'ils ont allumé le crématocue. Ils m\'ont dit que je ne sentirai <strike>presque</strike> rien, qu\'ils m\'ont réservé toute une bouteille de vodka. Mais je sais qu\'ils mentent. </p>
                <p>J\'avais volé la dernière<strike>..</strike></p>
                <p><em>Stravingo</em></p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "written"
        ],
        "theend" => [
            "title" => "Tout est donc fini.",
            "author" => "CeluiQuOnNeNommePas",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "cult1" => [
            "title" => "Tract du culte de la morte-vie",
            "author" => "coctail",
            "content" => [
                '<div class="hr"></div>
                <h1><small>Vous avez perdu un être cher ? Confiez-le au</small></h1>
                <h1>Culte de la morte-vie.</h1>
                <p>Le culte de la morte vie redonne vie à l\'être décédé. Apportez le cadavre, une forte somme en liquide et une muselière. La morte vie, pour la réincarnation de l\'humanité dans sa nouvelle splendeur.</p>',
                '<p>L\'humanité avance vers son nouveau stade d\'évolution. Les humains deviennent plus résistants, plus forts. Ils survivent dans la mort. Préparez-vous et votre famille à franchir le pas pour entrer dans votre nouvelle vie : la morte vie. Les cultes sont célébrés dans l\'ancien métro, toutes les nuits à minuit. </p>
                <h1>Venez nombreux nous rejoindre pour découvrir la joie de la morte-vie et recevoir l\'illumination du grand gourou Magnus Ier en personne.</h1>
                <p>Ne croyez pas les propos tenus par les autorités. Nous avançons vers notre futur. Un jour, la Terre entière sera recouverte de zombis. Soyez parmi les premiers à obtenir l\'état de grâce, rejoignez le mouvement de la morte-vie.</p>',
                '<p>Grâce au culte de la morte-vie, vivez une expérience inoubliable ! Rencontrez en tête à tête et seul à seul des personnages ayant survécus à la mort. Vous aussi, survivez à la mort et devenez comme eux !</p>
                <p>Besoin de main d\'oeuvre peu chère ? Adressez-vous au culte de la morte-vie. Chaque jour, nous tenons à votre disposition de plus en plus de travailleurs. Livrés en camion-benne.</p>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "typed"
        ],
        "utpia1" => [
            "title" => "Un brouillon",
            "author" => "anonyme",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "loginc" => [
            "title" => "Un journal incomplet",
            "author" => "coctail",
            "content" => [
                '<p>en faudrait plus.</p>
                <p>Et elle pensait : « Ils ne savent pas encore. Non, ils ne savent pas encore. Ils regardent tous vers la porte mais ils ne savent pas encore. Ils entendent la horde qui arrive mais ils ne savent pas encore. Non, ils ne savent pas encore que j\'ai dévissé les plaques du mur de derrière ».</p>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "written"
        ],
        "granma" => [
            "title" => "Un post-it",
            "author" => "sunsky",
            "content" => [
                '<p>Mamie, ca fait 3 jours que tu dors.</p>
                <p>J\'ai froid et tu ne me réponds pas !</p>
                <p>Quand tu te réveilleras, viens me retrouver.</p>
                <p>Il y a des gens qui font du bruit quand il est tard le soir. Je vais aller voir s\'ils veulent pas jouer au ballon avec moi.</p>'
            ],
            "lang" => "fr",
            "background" => "postit",
            "design" => "postit"
        ],
        "letmys" => [
            "title" => "Une lettre étrange",
            "author" => "sunsky",
            "content" => [
                '<p>
                Vendredi soir, je t\'écris ce petit mot.<br>
                Encore une journée calme.<br>
                Une bonne chose quand on sait le monde dans lequel on vit !<br>
                Les soldats qui gardent notre camp sont très <br>
                Efficaces et professionnels dans tout ce qu\'ils font. Ils<br>
                Nous ont dit qu\'ils passeraient dans votre ville<br>
                Très bientôt pour vous aider.<br>
                <br>
                Vous pourrez les accueillir comme il se doit !<br>
                On a vraiment besoin de leur présence réconfortante et<br>
                Une main de plus est toujours la bienvenue, hein.<br>
                Sans eux, ça serait plus dur.<br>
                <br>
                Toi tu sais de quoi je parle.<br>
                Une amie comme toi, depuis le temps qu\'on se connait !<br>
                Et c\'est pas la première lettre qu\'on s\'écrit, hein, tu <br>
                Raconteras bien à tous de préparer un accueil mérité...<br>
                </p>
                <br>
                <em>Ton frère qui tient à toi</em>'
            ],
            "lang" => "fr",
            "background" => "letter",
            "design" => "typedsmall"
        ],
        "night1" => [
            "title" => "Une nuit comme les autres...",
            "author" => "Ahmen",
            "content" => [
                '<p>Lentement, sous la lune pesante, ils marchent dans le sable ardent. Leurs douleurs effacées, leur humanité oubliée, sans cesse, ils avancent. A travers eux nous y avons vus des étoiles mais quand, au loin, les premiers grognements se répandent, que les premiers pas s\'entendent; nous nous blotissons contre nos objets dérisoires, grinçant des dents. Fermant les yeux mais, hélas, pas nos oreilles, ils sont là !</p>
                <p>Nous sentons, alors, leurs putréfactions galopantes martellant la taule et le bois. Nous entendons, encore et toujours, les cris de nos frères restés dehors qui, comme eux, marcheront demain dès l\'aurore, toujours plus nombreux. </p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "classic"
        ],
        "night2" => [
            "title" => "Une nuit dehors",
            "author" => "mrtee50",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "blood"
        ],
        "jay" => [
            "title" => "Une pile de post-its",
            "author" => "todo",
            "content" => [
                '<p>Hier, le vieux Jay nous a tous réunis autour de la potence pour un de ses discours moralisateur à la noix, «Il faut qu\'on se serrent les coudes les gars, si on veut avoir une chance de survivre, tout ce qu\'on a ramené du désert doit impérativement être utilisé  pour fortifier les défenses de la ville » qu\'il  disait.</p>',
                '<p>Au début, on a vraiment cru qu\'on allait tous s\'en sortir, on les a d\'abord vus  littéralement exploser les uns après les autres en pénétrant dans le champ de mines à eau, ensuite beaucoup d\'entre eux sont tombés dans le grand fossé pour ne jamais en ressortir? du moins c\'est ce qu\'on croyait.  Apres un moment, on en a vu un refaire surface, un pieu lui transperçait la poitrine, mais malgré ca, il s\'est mis à escalader nos remparts, et en l\'espace d\'un instant,</p>',
                '<p>il fut rejoint par une poignée d\'autres qui se sont mis à suivre son exemple. </p>
                <p>Aujourd\'hui, le vieux Jay est mort.</p>',
                '<p>Après l\'attaque, la ville était en état de choc, j\'en ai profité pour me  faufiler en douce avec mon caddie, et j\'ai pris les quelques planches et bouts de ferrailles qu\'il restait dans les stocks de la ville. J\'ai ensuite passé toute l\'après-midi à me bâtir une vraie petite baraque, rustique mais solide.</p>',
                '<div class="hr"></div>
                <p>Cette nuit, ils seront plus nombreux encore, mais ils ne m\'auront pas, parce que moi, je suis plus malin que ce bon vieux Jay.</p>'
            ],
            "lang" => "fr",
            "background" => "postit",
            "design" => "postit"
        ],
        "revnge" => [
            "title" => "Vengeance",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "tinystamp",
            "design" => "small"
        ],
        "nails" => [
            "title" => "Vis et écrous",
            "author" => "totokogure",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "secret",
            "design" => "written"
        ],
        "contam" => [
            "title" => "Zone contaminée",
            "author" => "coctail",
            "content" => [
                ''
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "typed"
        ],
        "morse1" => [
            "title" => "Communication en morse (16 mai)",
            "author" => "zhack",
            "content" => [
                '<small>16 mai, ETA: 07:10</small>
                <small>[Début de la transmission]</small>
                <p>.... .. - -- .- -. / ..--- ...-- ..---/... --- ... / --.. --- -- -... .. . / - .-. --- .--. / -. --- -- -... .-. . ..- -..- / .---- -.... / ... ..- .-. ...- .. ...- .- -. - .../ .--. ..- .. - ... / .- / ... . -.-. /-... . ... --- .. -. / .- .--. .--. ..- .. / -- .. .-.. .. - .- .. .-. ./ .. -- -- . -.. .. .- - . -- . -. -/-.-. --- --- .-. -.. --- -. -. . . .../ .-.. .- - / ....- ---.. .-.-.- ---.. ...-- ...-- / ....- ---.. / ....- ----. .----. -./.-.. --- -. --. / ..--- .-.-.- ...-- ...-- ...-- / ..--- / .---- ----. .----. ./ --- ...- . .-.</p>
                <small>[Fin de la transmission]</small>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "typed"
        ],
        "morse2" => [
            "title" => "Communication en morse (31 août)",
            "author" => "anonyme",
            "content" => [
                '<small>31 août, ETA: 23:30</small>
                <small>[Début de la transmission]</small>
                <p>. - .- - / -- .- .--- --- .-. / / - .-. .- -. -.-. .... . / -. .---- ..--- / / .-. .- ...- .. - .- .. .-.. .-.. . -- . -. - / -.-. --- ..- .--. --..-- / .--. .- ... ... .- --. . / - . -. ..- / .--. .- .-. / .-.. .----. . -. -. . -- .. .-.-.- / .. -- .--. --- ... ... .. -... .-.. . / -.. . / .-. . .--. .-. . -. -.. .-. . / .-.. . / ... . -.-. - . ..- .-. .-.-.- / - . -. . --.. / .--. --- ... .. - .. --- -. / .-.. . / .--. .-.. ..- ... / .-.. --- -. --. - . -- .--. ... / .--. --- ... ... .. -... .-.. . .-.-.- / -.. .. . ..- / ...- --- ..- ... / --. .- .-. -.. . </p>
                <small>[Fin de la transmission]</small>'
            ],
            "lang" => "fr",
            "background" => "blood",
            "design" => "typed"
        ],
        "regler_fr" => [
            "title" => "Regretté Regler",
            "author" => "???",
            "content" => [
                '>
                <p>COALITIONS RUSTRES - EPITAPHE</p>
                <p>Il s\'appelait Regler. Jusqu\'au bout il s\'est battu contre l\'infection.
                Un homme presqu’ordinaire, ni meilleur, ni pire que le commun d\'entre nous, un homme qui, face à la maladie, a révélé, autant qu’il s’est découvert, une humanité, une grandeur, une densité.
                Ce n\'était pas un héros, ni un grand homme…
                Juste un homme entier, avec ses certitudes, ses ambitions, sa prétention, ses convictions, ses faiblesses, ses travers, sa mauvaise foi et sa part d’ombre…
                Mais aussi une homme fragile, un doux dur, un inquiet, un père, un exigeant, un fidèle en amitié… IL S’APPELAIT Regler. IL ÉTAIT MON AMI… un frère.
                </p>'
            ],
            "lang" => "fr",
            "background" => "notepad",
            "design" => "classic"
        ],
        "lawtab_fr" => [
            "title" => "Table de la loi",
            "author" => "???",
            "content" => [
                '>
                <h2>La Bible Apo-Catholique Orange</h2>
                <p>Tu n\'auras d\'autre dieu que le Corbeau.<br>
                Tu ne proféreras d\'insulte envers le monde des Hordes, car Le Corbeau ne tolère d\'insulte.<br>
                Tu n\'utiliseras de double identité, car le Corbeau ne le tolère.<br>
                Tu travailleras chaque jour, mais un jour sur deux est consacré à l\'offrande d\'eau envers ton prochain.<br>
                Honore ton voisin pour vivre dans le pays qu\'offre le Corbeau.<br>
                Tu ne commettras de meurtre, autre qu\'une goule.<br>
                Tu ne commettras d\'adultère nécrophile.<br>
                Tu ne commettras de vol, sauf en panique.<br>
                Tu ne porteras de faux témoignage sous peine.<br>
                Tu n\'envieras ton prochain, tant qu\'il vit.<br>
                </p>'
            ],
            "lang" => "fr",
            "background" => "carton",
            "design" => "modern"
        ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_rp_texts(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>RP texts: ' . count(static::$texts) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$texts) );

        // Iterate over all entries
        foreach (static::$texts as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(RolePlayText::class)->findOneByName($name);
            if ($entity === null){
                $entity = new RolePlayText();   
            } else {
                if (!empty($entity->getPages())){
                    foreach ($entity->getPages() as $page) {
                        $manager->remove($page);
                    }   
                    $manager->flush();
                } 
            }

            // Set property
            $entity
            ->setName( $name )
            ->setAuthor( $entry['author'] )
            ->setTitle( $entry['title'] )
            ->setLanguage($entry['lang'])
            ;

            if(isset($entry['background']))
                $entity->setBackground($entry['background']);

            if(isset($entry['design']))
                $entity->setDesign($entry['design']);

            for($i = 0; $i < count($entry['content']); $i++){
                $page = new RolePlayTextPage();
                $page->setPageNumber($i + 1);
                $page->setRolePlayText($entity);
                $page->setContent($entry['content'][$i]);
                $entity->addPage($page);
                $manager->persist($page);
            }

            $manager->persist($entity);
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Texts</info>' );
        $output->writeln("");

        $this->insert_rp_texts( $manager, $output );
        $output->writeln("");
    }
}
