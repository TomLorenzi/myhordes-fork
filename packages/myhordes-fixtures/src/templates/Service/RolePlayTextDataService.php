<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class RolePlayTextDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        $data = array_replace_recursive($data, [
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
                'chance' => '20',
            ],
            'crema1_de' => [
                'title' => 'Auslosung',
                'author' => 'Stravingo',
                'content' => [
                    '<blockquote>Zufluchtsort der verlorenen Hoffnungen, 19. Februar</blockquote><p>Wir haben schon seit Wochen nichts mehr zu essen. Wir sind hungrig, so hungrig wie in unserem Leben nie zuvor. Der Hunger nagt an uns, presst uns die Gedärme, doch wir können die Stadt nicht verlassen.</p><p>Jenseits unserer schäbigen Verteidigungen lauern sie uns auf... Ich kann nicht mehr. Ich habe keine Kraft mehr. Ich kann den Holzkohlegeruch des Kremato-Cue schon riechen.</p><p>Heute morgen haben wir jemanden ausgelost. Das Los fiel auf mich, aber das ist mir egal. Meine Mitbürger sind dreckige Heuchler, die sich mitleidig geben. Um mir "Trost" zu spenden, sagten Sie, dass ich fast nichts spüren würde. Sie hätten mir eine Flasche Bier aufgehoben - elendige Lügner! Es gibt kein Bier mehr!! Ich hab die letzte Flasche gestern Abend ausgesoffen! Ha!</p><p><em>Stravingo</em></p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'notepad',
                'chance' => '7',
            ],
            'sbef' => [
                'title' => 'Befehl',
                'author' => 'Nobbz',
                'content' => [
                    '<blockquote>Befehl</blockquote><p>Morgen um AG YQ AG AG Zulu wird der Welpe an die Garage im Baum geliefert. Der Dosenöffner nimmt die Route über die A KL. Es wird eine Abschätzung des Kartoffel Gemüse erbeten.</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'notepad',
                'chance' => '4',
            ],
            'dv_004' => [
                'title' => 'Bekanntmachung: Abtrünnige',
                'author' => 'Sigma',
                'content' => [
                    '<br><br><br><b>ÖFFENTLICHE BEKANNTMACHUNG</b><br /><br />
                Ein Gruppe Abtrünniger hat vor zwei Tagen die
                Stadt verlassen, um sich in der Höhle südlich
                der Stadt zu verstecken. Ein Aufklärer meinte,
                sie hätten komplett den Verstand verloren, oder
                besser gesagt: Ihre Köpfe.'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'carton',
                'chance' => '4',
            ],
            'citya3_de' => [
                'title' => 'Bekanntmachung: Bank',
                'author' => 'Sigma',
                'content' => [
                    '<div class="hr"></div>
				<h1>Öffentliche Bekanntmachung</h1>
				<p>Die Bank gibt hiermit bekannt, dass ab sofort keine Lebensmittelkarten mehr angenommen werden. Ein paar Witzbolde wurden gestern beim Versuch erwischt, dem kurzsichtigen Bankverwalter ein paar schlecht gefälschte Karten anzudrehen.</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'carton',
                'chance' => '4',
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
                'chance' => '10',
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
                'chance' => '2',
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
                'chance' => '9',
            ],
            'sophie' => [
                'title' => 'Brief nach Hause',
                'author' => 'SixSixSeven',
                'content' => [
                    '<p>Meine allerliebste Sophie,</p>
                <p>ich weiß nicht ob euch dieser Brief erreicht oder ob er überhaupt jemals gefunden wird.</p>
                <p>In diesen Minuten, welche allem Anschein nach die Letzten in meinem Leben sein werden, möchte ich euch mitteilen wie sehr ich euch liebe. Meine einzigen Gedanken in diesen schweren Zeiten gelten Dir und dem Baby. Alle Kraft, über die ich jetzt noch verfüge, stammt allein aus der Tatsache, euch in Sicherheit zu wissen.</p>
                <p>Ich habe mich in dieser alten Tankstelle verschanzt, habe die Türen und Fenster verbarrikadiert und alles was ich an Wasservorräten und sonstigen Waffen finden konnte zusammengetragen.</p>',
                    '<p>Trotz allem wütet draußen eine so gewaltige Horde dieser Höllenbrut, dass ich keine Hoffnung haben kann euch jemals wieder in meine Arme zu schließen.</p>
				<p>Ich liebe euch über alles und kann nur hoffen, dass ihr den tag erl</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'letter',
                'chance' => '7',
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
                'chance' => '15',
            ],
            'dv_009' => [
                'title' => 'Brief an Nelly',
                'author' => 'aera10',
                'content' => [
                    '<p>Liebe Nelly,</p>
				<p>ich schreibe dir, weil ich es hier nicht mehr aushalte.</p>
				<p>In der ganzen Stadt gibt es seit Tagen keinen Strom mehr, wir haben kein Internet und alle Telefonleitungen sind tot. Vor allem aber passieren seltsame Dinge. Auf einigen Straßen liegen riesige Felsen, die vom Himmel gefallen zu sein scheinen.</p>
				<p>Gestern habe ich einen Mann gesehen... na ja, ich weiß nicht mal, ob es ein Mann war. Dieser Typ hatte ein Bettlaken auf dem Kopf und wühlte in einer Mülltonne. Ich weiß nicht, was er darin suchte. Nach zirka 2 Minuten drehte er sich um und wankte in das Haus von dieser verrückten Malerin. Ein Polizist hat sie heute morgen in ihrem Wohnzimmer aufgefunden und prompt einen Schock erlebt. Die Malerin muss ziemlich übel ausgesehen haben.</p>',
                    '<p>Ich habe diesen Brief in der Nachbargemeinde abgesendet. Will dir damit nur sagen, dass ich dich besuchen komme.</p>
				<p>Während du diese Zeilen liest, bin ich schon unterwegs.</p>
				<p>Herbert.</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'letter',
                'chance' => '5',
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
                'chance' => '9',
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
                'chance' => '8',
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
                'chance' => '9',
            ],
            'dv_012' => [
                'title' => 'Coctails Tagebuch Teil 2',
                'author' => 'coctail',
                'content' => [
                    '<p>Schmerzerfüllt öffnete ich meine Augen.</p><p>Pantokrat hatte mir ein feuchtes Wickeltuch auf die Stirn gelegt. Ich lag im Schatten und beschloss meine Augen erneut zu schließen. Nur noch ein paar Minuten diese angenehme Kühle genießen...</p><p>Einfach nur ausgestreckt daliegen und den Sonnenuntergang beobachten... Ich hätte nie gedacht, dass das mal so entspannend sein kann...</p><p>Die Sonne! Sie ist gleich weg! Sie sind gleich da! Wir müssen uns verstecken!</p>',
                    '<p>- "Er ist jetzt wach.", rief Pantokrat hinter sich.</p><p>- "Umso besser, dann soll er gleich mal mitanpacken. Wir müssen diesen Kühlschrank vor dieses Fenster schieben!", antwortete Zoby mit ruhiger Stimme.</p><p>Wir hatten das gesamte Erdgeschoss mit allen noch brauchbaren Möbeln verbarrikadiert, doch es wartete noch ein Haufen Arbeit auf uns. Ehe nicht alles hunderprozentig dicht war, konnten wir uns keine Pause gönnen.</p><p>Pantokrat und Zoby erzählten mir, wie sie mich gegen die Zombies stürmen sehen hatten. Ich hätte einen nach dem anderen zerlegt. Vor lauter Anstrengung bin ich dann zusammengeklappt. Anschließend hätten sie mich bis hierher geschleppt.</p>',
                    '<p>Selbst Wasser hatten sie gefunden! Als sie das Bad betraten lag noch jemand in der Badewanne. Glücklicherweise ist er dann zum Sterben rausgegangen... Seine Leiche liegt noch immer neben dem Waschbecken. Selbstverständlich kommt aus den Wasserhähnen kein Wasser! Von Strom brauchen wir gar nicht erst reden.</p><p>Angesichts meines geschwächten Zustands, überließen mir Pantokrat und Zoby das kaputte Sofa. Pantokrat würde heute Nacht mit seinem Batteriewerfer Wache schieben.</p><p>Am nächsten Morgen wurde ich von Zoby unsanft geweckt. Pantokrat, der ja eigentlich die Nacht auf uns aufpassen sollte, bekam einen Fußtritt verpasst... Der Helligkeit nach zu urteilen, stand die Sonne schon hoch am Himmel... gar nicht gut.</p><p>So erschöpft wie wir gestern waren, sind wir alle drei sofort eingeschlafen und haben nichts mehr gehört. Das ist wirklich erstaunlich, denn die Beulen und Kratzspuren an den Wänden deuten auf eine massive Gewalteinwirkung hin.</p>',
                    '<p> Die Biester wollten es gestern Nacht wirklich wissen... Vorsichtig verließen wir unsere Schlafstätte. Es sah so aus, als ob sich die Zombiehorde von gestern Nacht zurückgezogen hätte.</p><p>Da wir den ganzen letzten Abend damit verbracht hatten uns einzubunkern, waren uns die Autowracks von nebenan gar nicht augefallen.</p><p>Die Autos waren vom Rost regelrecht zerfressen.. als ob sie schon seit Jahrzehnten hier verrotten würden. Alles um uns herum schien in letzter Zeit noch schneller zu verfallen...</p><p>Wir versuchten eines nach dem anderen zu starten. Es war total sinnlos... plötzlich hörte ich ein lautes Brummen. Ich rannte zum anderen Ende des Schrottplatzes und sah Zoby neben einem Fahrzeug stehen. Himmel, war das ein Motor! Zoby hatte doch tatsächlich ein militärisches Kettenfahrzeug zum Laufen gebracht! Als er mich erblickte, schaltete er den Motor aus und stieg aus seiner Fahrerkabine.</p><p>Breit grinsend stand er vor mir und wollte mir etwas erzählen, als hinter ihm auch schon weiße Rauchschwaden aus der Motorhaube stiegen...</p>',
                    '<p>Pantokrat kam auf uns zugelaufen. Er erzählt uns, dass er ein eingestürztes Gebäude unter dem Sand entdeckt hatte.</p><p>Daraufhin beschlossen wir uns die Arbeit aufzuteilen. Während ich mit meiner "Reibe" graben sollte, kümmerte sich Pantokrat um die Autoblechverkleidungen. Zoby hatte hingegen eine Werkzeugkiste gefunden und machte sich daran, das Kettenfahrzeug auszuschlachten.</p><p>Das Graben war eine richtige Scheißarbeit. Zum Glück brachte mir Pantokrat nach ein paar Stunden eine richtige Schaufel, denn meine Hände waren vom Graben schon komplett aufgeschürft.</p>Am Abend haben wir uns dann in unseren selbst fabrizierten Metallkasten eingesperrt. Pantokrat hatte alle Motorhauben eingesammelt, die er finden konnte. Anschließend haben wir alle Fenster damit abgedeckt. Um ganz auf Nummer sicher zu gehen, haben wir noch einen Kleintransporter vor die Haupttür geschoben.<p></p>',
                    '<p>Auch Zoby war fleißig gewesen: Er hatte diesen riesigen Kettenfahrzeugmotor wieder zum Laufen gebracht, die Benzintanks der anderen Fahrzeuge leergesaugt und das Benzin in Kanister abgefüllt. </p><p>Wir mussten uns nun entscheiden, ob wir weiter in unserer Festung bleiben oder ob wir vor Sonnenuntergang aufbrechen würden. Nach einem kurzen Wortwechsel richteten sich die Blicke plötzlich auf mich. Ich war der einzige, der noch nichts gesagt hatte.</p><p>Ich grinste sie stumm an, verzog mich dann aber sofort in den Nebenraum. Dabei nahme ich die Bierflasche mit, die ich zuvor im Bauschutt gefunden hat.</p><p>Pantokrator und Zoby folgten mir wortlos. Das Bier wurde brüderlich geteilt. Zwei Verschlusskappen pro Nase... wir waren nicht mal beschwippst, aber für uns war es etwas besonderes.</p>',
                    '<p>Zoby und Panto schliefen nach ein paar Minuten friedlich ein. Dabei ratzten sie so laut, dass ich das Gefühl hatte, sie würden einen Wald zerlegen.</p><p>Ich stieg in den ersten Stock und schaute mir durch ein kaputtes Fenster den Mond an. Die Zombies würde es nicht schaffen hier hochzuklettern, also machte es auch keinen Sinn die Etagenfenster zu verbarrikadieren.</p>',
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'notepad',
                'chance' => '8',
            ],
            'coctl3_de' => [
                'title' => 'Coctails Tagebuch Teil 3',
                'author' => 'coctail',
                'content' => [
                    '<p>Ich starrte auf die Autoantenne unter mir, als ich sie hören kam. Sie gaben gutturale Laute von sich und bewegten sich langsam auf unser Gebäude zu. Bald würden sie hier sein und mit ihnen dieser strenge Madengeruch.</p><p>Die ersten Male musste ich mich übergeben, aber man gewöhnt sich an alles. Der menschliche Körper kann sich an vieles gewöhnen... selbst an Zombies...</p><p>Wie von Sinnen hämmerten sie gegen die Autohauben. Pantokrats genialer Einfall zahlt sich jetzt aus: Die Blechhauben hielten stand. Allerdings veursachten sie einen Höllenlärm, an Schlaf war diese Nacht nicht zu denken.</p>',
                    '<p>Ich blieb die ganze Nacht lang auf. Kurz vor Sonnenaufgang jagte mir so ein Biest eine verdammte Angst ein! Ich war nur einen kurzen Moment unachtsam. Als ich wieder zum Fenster sah tauchte ein Kopf vor mir auf. Er hatte keinen Kiefer mehr und seine Augen waren blutrot angelaufen. Ich schrie so laut ich konnte und griff reflexartig nach meiner Reibe.</p><p>Dieses etwas kratzte mit seinen Händen gegen das Fenster und gab stöhnenden Bärenlaute von sich.</p><p>Die anderen waren zwischenzeitlich von meinem Geschrei wach geworden. Zoby stürmte ins Zimmer und fuchtelte wild mit seinen Armen.</p><p>Vor lauter Schreck hatte ich gar nicht bemerkt, dass vier Zombies eingedrungen waren. Wir mussten sie aufhalten, denn weitere Viecher würden ihnen bald folgen!</p>',
                    '<p>Zoby brüllte mich an: <blockquote>"Die Tür, die Tür!!"</blockquote></p><p>Blitzartig schaute ich nach links. Die Eingangstür schwang auf und zu! Panto und Zoby stemmten sich mit aller Kraft gegen sie und versuchten sie irgendwie zu schließen, aber es gelang ihnen nicht. Hals über Kopf rannte ich los, um etwas zu finden, womit wir sie blockieren konnten.</p><blockquote>"Das Sofa, das Sofa! Hol das verdammte Sofa!!", schrie mich Panto an.</blockquote><p>Die Couch vor mich herschiebend, konnte ich aus dem Wohnzimmer bereits sehen, dass die Haustür zu splittern begann und nicht mehr lang standhalten würde.</p><p>Ich weiß nicht wie, aber ich habe die Couch ganz allein vor die Eingangstür geschleppt.</p><p>Zoby kam mir sofort zur Hilfe, sodass wir den Eingangsbereich blockieren konnten. Wir hatten ein Riesenglück, die Couch füllte den Raum zwischen Haustür und gegenüberliegender Wand passgenau. Ich glaube, dass hat uns das Leben gerettet...</p><p>Die Zombies belagerten uns noch ein paar Stunden, ohne dass es ihnen gelang einzudringen. Als wieder Ruhe herrschte, verließ Ich mit Zoby vorsichtig das Haus. Wir stellten fest, dass die Zombies den Kleintransporter genutzt hatten, um sich ihren Weg ins Haus zu bahnen. Ich dachte mir: "Das kann doch wohl nicht wahr sein! Was für eine Scheißidee, die Eingangstür mit einem Wagen zu versperren...".</p>',
                    '<p>Panto stieß zu uns und kletterte in den Transporter, um ihn nach ein paar Sekunden wieder zu verlassen. Er war ganz blass. Panto war sowieso schon die ganze Zeit bleich, die ganzen Sonnenstiche und Sonnenbrände hatten ihm ziemlich zugesetzt, doch diesmal stammelte er etwas vor sich. Ein paar Zombies hätten es bis in der ersten Stock geschafft. Wir mussten handeln.</p><p>Ohne ein Wort zu sagen zog ich los, um die Alkoholflasche zu holen. Dann gönnte ich mir einen vollen Schluck und stieg auf das Dach des Kleintransporters. Pantokrat warf mir sein Feuerzeug zu.</p><p>Das Gebrüll der brennenden Zombies war noch ein paar Stunden zu hören.</p>',
                    '<p>Am Abend zogen wir dann Bilanz. Pantokrat hatte das Gebäude nochmal verstärkt und den Kleintransporter vom Eingang weggeschoben. Zoby hatte auf der Lade des Kettenfahrzeugs eine Metallkabine gebaut und ich hatte noch einmal das gesamte Gebäude nach brauchbaren Gegenständen abgesucht. Ich habe dann noch ein paar Kisten gefunden. Nachts wollten wir sie aufmachen.</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'notepad',
                'chance' => '8',
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
                'chance' => '10',
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
                'chance' => '10',
            ],
            'utpia1_de' => [
                'title' => 'Ein Schmierzettel',
                'author' => null,
                'content' => [
                    '<p>Der Typ hatte recht. Koordinaten (ungefähr): <s>210</s>125 Nord 210 West. </p><p>To do:</p><ul><li>Fahrzeug (Parkplatz im Norden absuchen)</li><li>Wasser (15 Liter)</li><li>Nahrung (bei Bretov besorgen; keine infiziertes Zeug andrehen lassen)</li><li>"Zitadelle" ? Was ist das??</li></ul><p>Ich muss die <strong>B 74</strong> finden.</p><p>Der Rabe???!? Wer ist das? Rausfinden und UMBRINGEN</p><blockquote>Termin um 16h !!!<strong>!!!!</strong></blockquote><p><strong>ZITADELLE</strong> finden</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'secret',
                'chance' => '4',
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
                'chance' => '8',
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
                'chance' => '10',
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
                'chance' => '3',
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
                'chance' => '10',
            ],
            'dv_017' => [
                'title' => 'Frys Erlebnis',
                'author' => 'Sardock4r',
                'content' => [
                    '<div class="content"><em>Liebe Anne,</em><br><br><p>Du glaubst ja nicht, was mir vor ein paar Tagen widerfahren ist.Ich saß ihn meiner kleinen Ruheecke und versuchte mich von dieser missglückten Medikamenteeinnahme zu erholen.</p><p>Stärker als meine Bauchschmerzen, war jedoch mein Wunsch eine Beschwerde gegen diesen gottesfürchtigen, frömmelnden Typen zu schreiben, der die Weisheit mit dem Zahnstocher gefressen hat... (der Typ gehört gleich gehängt wenn du mich fragst...).</p><p>Müde in die Zeilen blickend und schon fast im Schlafe nickend, hört ich plötzlich leise klopfen. Leise, doch vernehmlich klopfen... Der kalte Schauer lief mir den Rücken runter!</p><p>Ich dachte meine letzte Stunde hätte geschlagen (Du erinnerst dich an die mysteriösen Todesfälle?) und sprang auf, um meine Hütte zu verlassen... doch meine Neugier war stärker... und so blieb ich angewurzelt stehen.</p></div>',
                    '<p>Das Fenster barst. Ich konnte meinen Augen und meinem Verstand nicht glauben.Flog da ein riesiger Vogel ins Zimmer und krallte sich meine Schreibblätter und meinen Bleistift. Zwei Augenblinzler später war er weg.</p><p>Heute morgen fand ich dann genau die gleichen Blätter samt Bleistift vor meiner Tür wieder.</p><p>Nun möchte ich deinen Rat haben: Meinst du es ist eine gute Idee den Vorfall in der nächsten Stadtsitzung anzusprechen. Lässt sich das irgendwie wissenschaftlich erklären ? Ein Vogel, der mir mein Schreibzeug klaut und nach drei Tagen wieder vor die Tür legt ?!</p><p>Hoffe bald von dir zu hören,</p><br>Dein Fry'
                ],
                'lang' => 'de',
                'design' => 'typedsmall',
                'background' => 'letter',
                'chance' => '6',
            ],
            'todg' => [
                'title' => 'Gedanken eines Togeweihten',
                'author' => 'Knolle',
                'content' => [
                    '<p>Gedanken eines Todgeweihten</p>
                <p>Der Tod, er rückt näher! Gleichsam mit der Nacht.
                Ich höre, wie das Verderben in meine Seele lacht.
                Die Sonne, sie brennt! Gleich tut\'s ihr mein Herz.
                Wie in einem Albtraum, wie als wär\'s nur ein Scherz.</p>
                <p>Der Tod, er rückt näher! Gleich hat er gewonnen.
                Mein ganzes bittre Leben, gleich ist es zerronnen.
                Die Sonne, sie lacht! Gleich tut\'s ihr der Tod.
                Es gibt kein Entkommen, die Nacht sie wird rot.</p>
                <p>(Verfasser unbekannt)</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'secret',
                'chance' => '6',
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
                'chance' => '10',
            ],
            'dv_018' => [
                'title' => 'Gewinnlos',
                'author' => null,
                'content' => [
                    '<p>Dieses Zigarettenpäckchen ist ein GEWINNLOS!</p><br /><p>Um ihren Preis zu erhalten müssen Sie dieses Etikett zusammen mit einer Zahlungsquittung an folgende Adresse senden:</p><br /><p>Klezma Cigarettenwerke GmbH,<br />Max-Bürger-Straße 44.<br />22760, Hamburg</p><br /><p>Hinweis: Mit diesem Gewinnetikett haben Sie zusätzlich Anspruch auf einen KOSTENLOSEN Rundum-Gesundheitscheck. Unser Kunde ist alles für uns - Klezma.</p>'
                ],
                'lang' => 'de',
                'design' => 'classic',
                'background' => 'tinystamp',
                'chance' => '2',
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
                'chance' => '6',
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
                'chance' => '9',
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
                'chance' => '8',
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
                'chance' => '5',
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
                'chance' => '10',
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
                'chance' => '7',
            ],
            'dv_024' => [
                'title' => 'Mitteilung: Diebe',
                'author' => 'DBDevil',
                'content' => [
                    '<div class="hr"></div>
				<h1>Mitteilung</h1>
				<p>Hiermit werden die Strafen für Diebstahl verschärft. Die Verbrecher werden ab sofort im Kremato-Cue verbrannt. Um weitere Unfälle zu verhindern, bleiben Haustiere als Diebstahlschutz weiterhin verboten und sind beim Metzger abzuliefern.</p>
				<p>- Die Bürgerversammlung</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'carton',
                'chance' => '10',
            ],
            'morse2_de' => [
                'title' => 'Morsecode (21.Juni)',
                'author' => 'zhack',
                'content' => [
                    '<small>21. Juni, MEZ: 13:30</small>
                <small>[Anfang der Übertragung]</small>
                <p>.... . . .-. . ... --. .-. ..- .--. .--. .  -..-. / -. --- .-. -.. -..-. / .- -... ... -.-. .... -. .. - - -..-. / .-  / --.. ..- --. .- -. --. -..-. / --.. ..- -- -..-. / -. .- -.-. .... ... -.-. .... ..- -... -.. . .--. --- - -..-. / .-- .. .-. -.. -..-. / ...- --- -- -..-. / ..-. . .. -. -.. -..-. / -... .-.. --- -.-. -.- .. . .-. - .-.-.- -..-. / -.- . .. -. -..-. / -.. ..- .-. -.-. .... -... .-. ..- -.-. .... -..-. / -- --- . --. .-.. .. -.-. .... .-.-.- -..-. / -.- .- . -- .--. ..-. . -. -..-. / -... .. ... -..-. / --.. ..- -- -..-. / .-.. . - --.. - . -. -..-. / -- .- -. -. .-.-.- / --. --- - - / -..-. / ... -.-. .... ..- . - --.. . / -..-. / . ..- -.-. .... .-.-.-</p>
                <small>[Ende der Übertragung]</small>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'blood',
                'chance' => '8',
            ],
            'news2' => [
                'title' => 'Mysteriöse Befunde - Tote weisen menschliche Bissspuren auf',
                'author' => null,
                'content' => [
                    '<small>(Fortsetzung von Seite 1)</small>
                <p>[...] Nach Angaben der Staatsanwaltschaft weisen beide Opfer mehrere Bissspuren auf. Außerdem lassen sich aus dem Bericht Parallelen zu jenen Morden ziehen, die in den vergangenen zwei Wochen unter ähnlich mysteriösen Umständen begangen wurden. Auch diesmal hatten die getöteten Frauen keine Haustiere.</p>
                <p>Der Autopsiebericht, aus dem die Staatsanwaltschaft zitiert, habe zum Ergebnis, dass die Bissspuren keinem Tier zugeordnet werden könnten. Vielmehr häuften sich die Indizien, dass es sich um menschliche Bissspuren handle.</p>
                <p>Eine weitere Erkenntnis aus dem Autopsiebericht stellt die Ermittler indes vor das größte Rätsel: Eine erste Analyse der Speichelproben komme zu dem Ergebnis, dass die DNS zwar von einem Menschen sei, das Erbgut aber [...]</p>',
                    '<p> [... ]atypische Mutationen und Merkmale aufweisen. Für weitergehende Analysen soll das Robert-Koch-Institut umgehend in die Untersuchungen miteinbezogen werden..</p>
                <h1>Westerwelle will Hartz IV aufstocken</h1>
                <em>von Ziya Kanpara</em>'
                ],
                'lang' => 'de',
                'design' => 'news',
                'background' => 'news',
                'chance' => '10',
            ],
            'cave1_de' => [
                'title' => 'Papierfetzen',
                'author' => 'gangster',
                'content' => [
                    '<p>Die Schweine haben mich erwischt... ich hock jetzt hier im Keller, die Tür ist eingeschlagen und ich höre ein leises Fiepen in meinen Ohren. Meine Wirbelsäule brennt wie Feuer, der Schmerz ist kaum auszuhalten...</p>
                <p>Alle sind tot, die meisten lebendig aufgefressen. Es war grauenhaft. Ich bin der Letzte. Wie heißt es doch so gleich: Den letzten beißen die Hunde. Nun ja, wenn\'s denn Hunde gewesen wären... Das Dreckstück hat mich in die Wade gebissen... das war\'s. Game over.</p>
                <p>Es wird jetzt alles ganz schnell gehen, sicher nur noch ein paar Minuten. Die Tür geht gleich auf und sie werden mich finden.</p>
                <p>Ich würde lieber sterben anstatt so zu werden wie sie ...</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'blood',
                'chance' => '5',
            ],
            'dv_028' => [
                'title' => 'Post-It',
                'author' => 'Sunsky',
                'content' => [
                    '<p>Omi du schläft schon seit drei tagen.</p><p>Mir is kalt und du antworst nicht!</p><p>Am Abend sind immer Leute da die Lärm machen. Ich frag sie ob sie mit mir Ball spilen wollen.</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'postit',
                'chance' => '7',
            ],
            'profi1' => [
                'title' => 'Profits Tagebuch',
                'author' => 'profit',
                'content' => [
                    '<p>13. Tag nach dem Zerfall meiner Heimatstadt</p>
                <p>Einsamkeit</p>
                <p>Das ist es, was man fühlt wenn man in der Wüste unterwegs ist.</p>
                <p>Einsamkeit</p>
                <p>Obwohl ich mit anderen die nähere Umgebung meiner neuen Zuflucht erkunde:Es bleibt nur </p>
                <p>Einsamkeit</p>
                <p>Die Leere der Wüste kann niemand füllen. Nichtmal unsere Gruppe, in der zwei echte Hünen sind mit einem Schild welches so groß wie sie selbst ist, wie sie selbst. Es erzeugt nur ein Trugbild von Sicherheit, von Zusammenhalt.
                Denn jeder in der Siedlung weiß, dass man immer auf seinen Rücken aufpassen muss, sonst stehst du allein dort.</p>
                <p>Es bleibt nur Einsamkeit.</p>
                <p>Das einzige Gefühl, welches versichert, das du am Leben und ein Mensch bist.</p>
                <p>Einsamkeit...</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'notepad',
                'chance' => '5',
            ],
            'bilan_de' => [
                'title' => 'Protokoll der Stadtratsitzung vom 7. November',
                'author' => 'Liior',
                'content' => [
                    '<h1>Sitzungsprotokoll vom 7. November:<small>(Protokollführer: Liior, Redakteur)</small></h1>
                <p>Bürgermeister Merkal verkündete den Baubeginn eines neuen Projekts, welches das Überleben aller Stadtbürger garantieren würde:</p>
                <blockquote>"Zugegeben, es handelt sich um eine wahnsinnige Unternehmung, die nur geringe Erfolgschancen verspricht, aber wir müssen es versuchen. <p>Wenn es klappt, könnte dieses neue Gebäude uns allen das Leben retten. Wir haben in den letzten Tagen das Optimum aus dieser Stadt herausgeholt: Ein Katapult wurde gebaut, ein Graben wurde angelegt, Zombiefallen wurde innerhalb und außerhalb der Stadt aufgestellt [...]</p>
                </blockquote>',
                    '<p>- jede einzelne Maßnahme war ein gewaltiger Kraftakt, doch jetzt ist der Zeitpunkt gekommen, an dem wir uns etwas Neues einfallen lassen mussten. 
                </p><p>Ich habe mit unseren Helden gestern Nacht schon darüber gesprochen und wir sind einstimmig zur Überzeugung gelangt, dass wir eine "Falsche Stadt" bauen müssen.</p>
                <p>Es hört sich verrückt an, aber wir denken, dass die Zombies den Unterschied nicht merken werden. Wenn es uns gelingt eine möglichst originalgetreue Stadt nachzubauen, könnten wir die Angriffslast von dieser Stadt nehmen und so langfristig unser Überleben sichern."</p>',
                    '<p>Die Versammlung reagierte skeptisch: </p>
                <blockquote>"Eine \'falsche Stadt\'? Und das soll funktionieren?", fragten sich einige Bürger sichtlich echauffiert.</blockquote>
                <p>Es scheint, als ob diese Stadt ihre Hoffnung schon aufgegeben hätte...</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'white',
                'chance' => '7',
            ],
            'rabs' => [
                'title' => 'Rabe, schwarz',
                'author' => 'accorexel',
                'content' => [
                    '<p>Ein Rabe sitz an einem Ort,</p>
                <p>Doch mir wird klar</p>
                <p>Ich hab kein Glück.</p>
                <p>Er fliegt hinfort</p>
                <p>Und es ist wahr -</p>
                <p>Schaut nicht zurück</p>
                <p>Und sprich kein Wort.</p>
                <p>Er war nie da,</p>
                <p>Was mich bedrück.</p>
                <p>Ein elend\' Narr</p>
                <p>Steht jetzt nur dort</p>
                <p>Wo nie zuvor</p>
                <p>Der Rabe war.</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'white',
                'chance' => '20',
            ],
            'dv_030' => [
                'title' => 'Richards Tagebuch',
                'author' => 'Cronos',
                'content' => [
                    '<p>7. September<br>...wir haben weitere überlebende gefunden, der alte Schulbus den Frank fährt (er sagt die ganzen schreienden Kinder hätten ihn früher immer genervt doch jetzt würde er die Lebenslust der kleinen Racker vermissen) ist voll, gut 35 Leute sind hier, in meinem alten Leben hasste ich überfüllte Busse, heute freue ich mich über jede weitere Person die einsteigt...</p><p>8. September<br>...keine gute Nachrichten, ein Unfall. Frank ist dabei um\'s Leben gekommen. Er war übermüdet gegen einen großen Felsen gefahren, hätte er sich nur angeschnallt. Wir haben ihn begraben und ein Kreuz aufgestellt, es wurden sogar ein paar Worte gesprochen, dann zogen wir alle weiter...</p>',
                    '<p>11. September<br>...eine verlassene Stadt, feste Mauern und sogar ein alter Brunnen. Das alles haben wir heute gefunden, allerdings haben wir letzte Nacht beim Zelten in dieser elenden Einöde Stephen,Peter,Roger und Francine verloren, wir haben sie begraben, für eine anständige Zeremonie blieb leider keine Zeit. Wir hungern, tun aber alle unser bestes...</p><p>14. September<br>3 Tage seit meinem letzten Eintrag. Eine Stadtmauer steht, eigentlich läuft alles gut ok bis auf die 8 weiteren Armen Seelen die gestorben sind 3 waren Junkies und haben den kalten Entzug nicht verkraftet die anderen 5 naja tot aber leider wieder auf den Beinen...</p><p>15. September<br>...seit gestern keine Vorkommnisse...außer eine Sache, es scheinen Ressourcen, und was schlimmer ist, Nahrungsmittel zu verschwinden. Ich werde dem auf den Grund gehen...</p>',
                    '<p>17. September<br>...hahaha heute hab ich ihn erwischt, den miesen Dieb und ihn zusammen mit den anderen verbliebenen gehängt, auf dem platz direkt neben dem Brunnen, yeaha hat er verdient die Ratte...</p><p>19. September<br>...ich hab das Gefühl jedes mal wenn ich in der Stadt umher spaziere gucken mich die anderen so seltsam an, ich kenne diesen Blick, sie haben so auch den Dieb angesehen, bevor sie ihn eiskalt gehängt haben, am besten ich bleibe einfach in meinem Haus und hol mir nur meine tägliche Ration...</p><p>21. September<br>...wusste es doch die andern, Milliard, George und Roy, verdächtigen mich aber ich bin ihnen zuvor gekommen hab mir alles geschnappt was mich am leben halten kann und mich verbarrikadiert, sollen die heute Nacht doch sehen wo sie bleiben...</p>',
                    '<p>23. September<br>...die andern, alle die gestorben sind, sie sind da draußen, Nachts kratzen und Klopfen sie an meine Türen und Fenster. Ich bin alleine, alle Bücher bereits gelesen, die Batterien des Kassettenrekorder leer. Wenn die Nächte wenigstens ruhiger wären...</p><p>29. September<br>...letzte Nacht war Georg an der Tür er hat zu mir gesprochen, also ich meine wenn er nicht grad vor Hunger stöhnte aber Hunger haben wir doch alle. Er sagte sie sind mir nicht böse das ich mich versteckt habe, sie verstehen das, ich könne aber jetzt raus kommen. Er sagte, alle Zombies sind irgendwie gestorben, bestimmt am Hunger. Wenn Georg heute Nacht wieder kommt bitte ich ihn rein um genaueres zu erfahren...</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'notepad',
                'chance' => '8',
            ],
            'sos1_de' => [
                'title' => 'S.O.S.',
                'author' => 'ChrisCool',
                'content' => [
                    '<div class="hr"></div>
				<p>Das ist ein Hilferuf! Ich befinde mich in der Stadt <strong>Brutstätte der Verdammten</strong>! Wenn jemand diese Nachricht lesen sollte: BRINGT MIR BITTE EINEN VIBRATOR!! Es geht um LEBEN ODER TOD!</p>'
                ],
                'lang' => 'de',
                'design' => 'postit',
                'background' => 'carton',
                'chance' => '2',
            ],
            'dv_031' => [
                'title' => 'Überlebensregeln',
                'author' => 'Schmerzengel',
                'content' => [
                    '<h1>Schmerzengels Überlebensregeln</h1>
                <p>Punkt 1.
                <br>
                Stehle niemandem sein Wasser, außer Du hast keines.	
                </p>
                <p>Punkt 2.
                <br>
                Gehe nur mit Waffen und Proviant in die Außenwelt. Solltest Du beides nicht haben. Nimm jemanden mit, der langsamer läuft als DU.	
                </p>
                <p>Punkt 3.
                <br>
                Wenn Du Nahrung findest, laß erst Deinen Kameraden davon essen. Er ist bestimmt genau so hungrig wie Du.	
                </p>',
                    '<p>Punkt 4.
                <br>
                Mit einer Fackel in der Hand ist der Verdammte gleich entbrannt.	
                </p>
                <p>Punkt 5.
                <br>
                Wenn Du meinst es geht nichts mehr kommt von irgendwo ....ach was soll´s. Ich bin ehrlich zu Dir. Du hast verschissen. Punkt.	
                </p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'grid',
                'chance' => '10',
            ],
            'dv_032' => [
                'title' => 'Seite 134 - Sprinkleranlage im Eigenbau',
                'author' => 'Tycho',
                'content' => [
                    '<p>Eine einfache Bewässerungsanlage für Ihren heimischen Garten selbst zu bauen ist gar nicht kompliziert. Hauptsächlich gehört dazu: Ein Anschluss an einen Wasserhahn, eine Befestigungsmöglichkeit für den Bewässerungskopf, der Bewässerungskopf selber und optional ein Mechanismus zur regelmäßigen Bewegung des Bewässerungskopfes.</p>
					<p>Zentral wichtig bei einer guten Sprinkleranlage ist die gleichmäßige Verteilung. Eine sehr einfache und effiziente Variante besteht aus einem längeren Stück Rohr (Länge sollte vom verfügbaren Wasserdruck abhängen), in das ein oder zwei Reihen kleiner Löcher gebohrt werden, als Bewässerungskopf. Am Ende des Rohres wird ein druckbelastetes wasserführendes System angeschlossen – beispielsweise der Wasserhahn ihres Gartenhauses.</p>',
                    '<p>Ein Teil des Wassers wird über eine separate Zuleitung abgezweigt und gegen ein kleines Wasserrad mit einer Rückstellfeder geleitet. Dreht nun das einströmende Wasser das Rad, so wird dadurch der Bewässerungskopf entlang seiner Längsachse gedreht. Ab einem gewissen Drehwinkel verschließt ein Teil des Wasserrades die Zuleitung und die Rückstellfeder dreht den Bewässerungskopf in die entgegengesetzte Richtung zurück. Dadurch schwingt der Bewässerungskopf vor und zurück, um somit abwechselnd den Boden auf beiden Seiten der Anlage zu bewässern.</p>
					<p>Soll die Anlage nur stationär eingesetzt werden, empfiehlt es sich, den Bewässerungskopf an einer Holzkonstruktion über dem Feld aufzuhängen.</p>
					<p>Exemplarische Konstruktionsskizzen und Berechnungshilfen finden sie auf der beigelegten CD unter dem Menüpunkt "Sprinkleranlage".</p>'
                ],
                'lang' => 'de',
                'design' => 'classic',
                'background' => 'old',
                'chance' => '8',
            ],
            'death' => [
                'title' => 'Spruch',
                'author' => 'Nomad',
                'content' => [
                    '<div class="hr"></div>
				<center>
				<div>Spiele nicht mit dem Tod, sonst spielen die Toten noch mit dir.</div>
				</center>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'blood',
                'chance' => '2',
            ],
            'gbuch' => [
                'title' => 'Seite aus einem alten Gesangsbuch',
                'author' => 'CarolinaCremasta',
                'content' => [
                    '<h1>Mein Herr, ich will dir ewig folgen (Choral in c-moll)</h1>
                <br>
                <p>Mein Herr ich will dir e-e-wi-i-g folgen!
                <br>
                Du führest mi-ich auf saft\'ge-e Flur!
                <br>
                Ich gebe dir mein le-etzte-es Wasser
                <br>
                und Hydrato-o-on gönn\' ich mi-ir nur.</p>
                <p>Mein Herr o lass mi-ich diese-en Balken
                <br>
                für dich nur schni-itzen ganz a-allein!
                <br>
                Und jede Schraube-e jede-e Mutter
                <br>
                soll dir nur dir! ge-e-ewidme-et sein.</p>',
                    '<p>Mein Herr wenn dich die-ie Schaue-er schütteln
                <br>
                dann geb\' ich hi-in mein Twi-inoid!
                <br>
                Ich hab\' es mir gesto-ohl\'n vo-om Freunde
                <br>
                der in der Nacht da-a-arauf verschied.</p>
                <p>Mein Herr du da-arfst mich schwe-er beladen
                <br>
                mit Mikrowelle-en und mi-it Stahl!
                <br>
                Nur dir zu diene-e-e-n, ganz alleine
                <br>
                ist Fre-e-eude für mich, keine Qual!
                <br>
                Ist Freude für mich, keine Qua-a-al!</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'grid',
                'chance' => '5',
            ],
            'dv_033' => [
                'title' => 'Seite 62 eines Buches',
                'author' => 'kozi',
                'content' => [
                    'Die Fragen 3, 4 und 5 hänge von deiner jeweiligen Situation ab,
                    die Fragen 1 und 2 sind von entscheidender Bedeutung
                    <p><b>4. Elektrische Werkzeuge</b></p>
                    Die Unterhaltungsliteratur hat uns die Ehrfurcht gebietende,
                    brutale Macht der Motorsäge gezeigt. Mit ihren blitzschnell
                    rotierenden Zähnen kann sie mühelos durch Fleisch und Knochen
                    schneiden und macht Kraft und Geschick, die für die Bedienung
                    manueller Waffen erforderlich sind, überflüssig. Außerdem kann
                    ihre Lautstärke dem Benutzer einen dringend erforderlichen
                    psychologischen Vorteil geben - ein Gefühl von Macht in einer
                    Situation, in der Todesangst vorherrschend ist. Wie viel
                    Horror-Filme hast du gesehen, in denen diese industriell
                    Killermaschine allem und jedem, das sie berührte, den Untergang
                    brachte? In Wirklichkeit jedoch nehmen Motorsägen und ähnliche
                    elektrische Geräte einen extrem niedrigen Platz in der
                    Rangordnung praktischer Waffen zum Töten von Zombies ein. [...]'
                ],
                'lang' => 'de',
                'design' => 'classic',
                'background' => 'old',
                'chance' => '10',
            ],
            'stxt' => [
                'title' => 'Seltsamer Text',
                'author' => 'Sardock4r',
                'content' => [
                    '<p>08 Wüste</p>
					<p>67 Die Entdeckung von Goldvorkommen in den fünfziger Jahren</p>
                    <p>74 Gebote gilt es zu beachten</p>
                    <p>13 Die Reihenfolge</p>
                    <p>67 Über dem Dorf erhebt sich auf einem Hügel die Ruine</p>
                    <p>40 Kaktus</p>
                    <p>03 ist mir egal</p>
                    <p>89 Addy</p>
                    <p>96 Le canelé est un petit gâteau</p>',
                    '<p>00 Ich bin das Alpha und das Omega</p>
                    <p>78 Alteisen und Holzbrett</p>
                    <p>99 Aurum summum bonum est</p>
                    <p>93 Der Naturforscher ist der Mann des strukturierten Sichtbaren</p>
                    <p>78 Miss den Nächsten nicht nach dem eigenen Maß!</p>
                    <p>05 Ich konnte mir seit 5 Monaten nicht die Fußnägel schneiden</p>
                    <p>35 Durst kann tödlich sein</p>
                    <p>25 Ein Mensch hatte zwei Söhne</p>
                    <p>61 Worte aber kann ich nicht lieben.</p>',
                    '<p>93 Das Leben kann kurz oder lang sein.</p>
                    <p>63 Geschlechtsreif sind Löwen mit 2-3 Jahren</p>
                    <p>12 Hat hier nichts zu bedeuten</p>
                    <p>32 Man kann vieles unbewusst wissen, indem man es nur fühlt.</p>
                    <p>72 Hat was mit dem Bürger Dayan zu tun</p>
                    <p>98 Freundschaft, das ist eine Seele in zwei Körpern.</p>
                    <p>96 Ein wird ein Hund mit drei Beinen kommen</p>
                    <p>18 Fantasie kennt keine Grenzen</p>
                    <p>46 Mathematiker sind dumm</p>',
                    '<p>80 Pi ist unendlich</p>
                    <p>84 Scharlachrote Paradeuniformen</p>
                    <p>96 Trennen ist wichtig</p>
                    <p>42 Du schlugst die Augen auf</p>
                    <p>31 Ich seh mein Dunkel leben.</p>
                    <p>95 Ich seh ihm auf den Grund.</p>
                    <p>41 auch da ists mein und lebt.</p>
                    <p>20 Schwelle zu Schwelle</p>
                    <p>52 Das meiste ist unwichtig</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'carton',
                'chance' => '3',
            ],
            'refabr_de' => [
                'title' => 'Sicherer Unterschlupf',
                'author' => 'Loadim',
                'content' => [
                    '<p><strong>Sicherer Unterschlupf</strong>, der Name war wirklich gut gewählt...</p><p>Allein heute Nacht hatten wir dreizehn Tote. Es folgte das gewöhnliche Prozedere: Leichen nach draußen schleppen, Privattruhen ausleeren, Zelte abbauen und so weiter und sofort... Alles, was essbar oder auch nicht essbar war, wurde vertilgt. Es ist dem Mut und dem Überlebenswillen einiger weniger Leute zu verdanken, dass wir noch immer am Leben sind...</p>
                <p>Wir hatten Fallgruben und Sprengfallen errichtet. Sogar das Stadtor wurde nochmal verstärkt. Vergebens. Sie haben sich an uns satt gegessen.</p><p> Wenn ich mir vorstelle, was jetzt wäre, wenn wir letzte Woche die Werkstatt nicht fertig bekommen hätten...</p>
                <p>Auf unseren Wasserverbrauch müssen wir jetzt aber nicht mehr achten, sie werden uns eh bald auffressen und mitnehmen.</p><p> Meine Hand juckt, ich spüre wie ein verfaulter Schädel unter meinem Faustschlag zerbirst. Diese Stadt macht es nicht nicht mehr lang, meine Freunde...</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'notepad',
                'chance' => '8',
            ],
            'polcor' => [
                'title' => 'Sie nennen sie Zombies',
                'author' => 'accorexel',
                'content' => [
                    '<div class="hr"></div>
                <center>Prof. Dr. Edgar Neubauer</center>
                <h1>Sie nennen sie Zombies - Politisch korrekter Umgang mit vermindert Lebenden</h1>',
                    '<p>Sie nennen Sie Zombies, Leichenfresser, wandelnde Tote – schon immer wurden infizierte Menschen von der scheinbar normalen und gesunden Bevölkerung diffamiert, ausgestoßen, verfolgt, in Lager gesperrt und systematisch getötet.</p>
                <p>Wer glaubt die Menschheit habe die asozialen Zustände eines pestverseuchten Frankfurts im 14. Jahrhundert oder eines von Cholera heimgesuchten Londons um 1854 überwunden, irrt. Was in der aufgeklärten Moderne, insbesondere in den westlichen 
                Industrieländern als undenkbar galt ist heute schreckliche Realität.</p>
                <p>Einzelne Individuen, Bevölkerungsgruppen, ja ganze Ethnien werden in diesem Moment mit den negativsten Attributen besetzt, die sich ein Mensch nur ausdenken kann: die vermindert Lebenden. Ihnen wird der Status Mensch entzogen und damit jedes 
                Menschenrecht. Ein Genozid in noch nie da gewesener Form.</p>',
                    '<p>Der medial geschürten Massenpanik, dem der Lynchmob mit Fackeln und Kettensäge in den Händen folgt, gilt es mit wissenschaftlicher Methode zu begegnen.</p>
                <p>Zu allererst sollten wir uns daher von dem Gedanken befreien, es handele sich bei den vermindert Lebenden um Leichen, die sich aus ihren Gräbern erhoben haben um die Menschheit heimzusuchen. Zombies, oder taxonomisch korrekt Corpse Cadarve, 
                gehören ins Reich der Fantasie – kreolische Taschenspielertricks, die den westlichen Hypnoseshows in nichts nachstehen. Vielmehr sind sie der Subspezies Ghul zuzuschreiben, dem mit einem immer noch nicht näher untersuchten Retrovirus infizierten Homo 
                sapiens sapiens. Bereits hier wird deutlich, dass die beim Ghul angewandte ursprüngliche Taxonomie Manesphagus horridus nicht nur ungenau ist, sondern völlig an der Gattung vorbei gewählt wurde. Selbst die Bezeichnung Homo sapiens wichtus trifft hier 
                allenfalls nur bedingt zu.</p>',
                    '<p>Auch wenn der Ursprung bislang ungeklärt bleibt, so weisen aktuelle Untersuchungen der verschiedenen Ausprägungen der Krankheit auf eine Vielzahl von Virenstämmen hin und damit folglich einer Vielzahl von im Entstehen begriffener Subspezies: Homo 
                sapiens ingentis monerus, Homo sapiens ingentis vrykolkas, Homo sapiens nobilis vrykolkas, oder Homo sapiens sapiens sanguisuga um nur einige zu nennen.</p>
                <p>Das Problem, das es nun zu lösen gilt ist, wie wir zukünftig ethisch vertretbar auf das Vorhandensein weiterer dominanter Spezies auf unserem Planeten reagieren und wie ein gemeinsames Miteinander gewährleistet werden kann. Die Integrationspolitik 
                ist im neuen Jahrtausend angekommen.</p>'
                ],
                'lang' => 'de',
                'design' => 'classic',
                'background' => 'old',
                'chance' => '10',
            ],
            'stafel' => [
                'title' => 'Sprechtafel',
                'author' => 'Nobbz',
                'content' => [
                    '<p>[Sprechtafel]</p>
                <p>0 - AG | Verpflegung - Welpen</p>
                <p>1 - HJ | Kompaniefeldwebel - Dosenöffner</p>
                <p>2 - DP | Feind - Kartoffel</p>
                <p>3 - XP | Anzahl - Gemüse</p>
                <p>4 - PO | Kompanie - Löwe</p>
                <p>5 - YQ | Hamburg - Baum</p>
                <p>6 - XO | Kompaniechef - Pickelhaube</p>
                <p>7 - KL | Kampfeinheit - Rasenmäher</p>
                <p>8 - PU | Truppenteile - Garage</p>
                <p>9 - QM | Kampf - Frikadelle</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'printer',
                'chance' => '3',
            ],
            'citsig_de' => [
                'title' => 'Stadtschild',
                'author' => 'coctail',
                'content' => [
                    '<div class="hr"></div>
                <center>
                <big>Gnadenlose Festung.</big>
                <div>40 Einwohner.</div>
                <div class="other"><strong>Zombie-Stadt, KEINE Überlebenden. NICHT WEITERGEHEN!!!</strong></div>
                </center>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'blood',
                'chance' => '10',
            ],
            'necro_de' => [
                'title' => 'Todesanzeigen',
                'author' => null,
                'content' => [
                    '<h1>Kürzlich verstorben:</h1>
                <p><strong>Dienstag</strong>: Ralf, Jürgen, warp, Artecz</p>
                <p><strong>Mittwoch</strong>: Dayan, Phantom, Ebola, Whitetigle <span class="other">(ach ne...)</span></p>
                <p><strong>Donnerstag</strong>: Whitetigle <span class="other">(Scheiß Infektion)</span></p>
                <p><strong>Freitag</strong>: Morkai, Amorphis, Deniz</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'carton',
                'chance' => '8',
            ],
            'dv_036' => [
                'title' => 'Twinoidetikett',
                'author' => null,
                'content' => [
                    '<h1>Twinoid 500mg</h1>
                <table>
                <tbody><tr><td>Nandrolon</td><td>0.70 %</td></tr>
                <tr><td>Allicin</td><td>0.03 %</td></tr>
                <tr><td>Nitroglyzerin</td><td>3.0 %</td></tr>
                <tr><td>Octanitrocuban</td><td>4.0 %</td></tr>
                <tr><td>Knallquecksilber</td><td>2.5 %</td></tr>
                <tr><td>Perchlorat</td><td>0.02 %</td></tr>
                <tr><td>Bleiazid</td><td>3.00 %</td></tr>
                <tr><td>RDX</td><td>0.02 %</td></tr>
                <tr><td>Natürliches Erdbeeraroma</td><td>86.73 %</td></tr>
                </tbody></table>
                <p><small>Anmerkung: Einige Wirkstoffe in diesem Medikament können unerwünschte Nebenwirkungen hervorrufen. Dazu zählen: Übelkeit, Erbrechen, Krämpfe, plötzlicher Tod und Explosion.</small></p>
                <p><small>Enthält leichtentzündliche Stoffe.</small></p>'
                ],
                'lang' => 'de',
                'design' => 'stamp',
                'background' => 'stamp',
                'chance' => '5',
            ],
            'santw' => [
                'title' => 'Verstanden!',
                'author' => 'NobbZ',
                'content' => [
                    '<blockquote>Befehl</blockquote>
                <p>Verstanden! Rasenmäher wird entsand um die Kartoffeln zu Frikadelle, gegenwärtige Gemüse ist etwa HJ AG AG</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'letter',
                'chance' => '6',
            ],
            'crazy_de' => [
                'title' => 'Wahn',
                'author' => 'Arco',
                'content' => [
                    '<p>Ich bin heute Abend auf\'s Dach gestiegen.</p>
                <p>die anderen sagen ich sei verrückt geworden.</p>
                <p>Ich glaube sie haben recht.</p>
                <p>Ungeduldig sehnte ich den Sonnenuntergang herbei und schaute zu wie die letzten blutroten Sonnenstrahlen hinter der Hügelkette verschwanden.</p>
                <p>In der Ferne konnte ich alsbald schon die Horde sehen. Ihre ungelenken und holprigen Bewegungen gaben ein bizarres Schattenspiel ab.</p>
                <p>Lüstern blitzten meine Augen.</p>
                <p>Ich bin verrückt geworden - sie haben recht.</p>
                <p>Spielt das denn noch eine Rolle?</p>
                <p>Mein Blick richtete sich erneut auf die torkelnde Masse...</p>',
                    '<p>Ein nicht zu bändigendes Rauschgefühl durchströmte meinen Körper.</p>	
                <p>Ich sah, wie sie die Stadtmauer überwanden!</p>
                <p>Ich begrüßte sie mit einem Freudenschrei.</p>
                <p>Und bekam ein tiefes, kehliges Gebrüll als Antwort!</p>
                <p>Ich bin verrückt geworden.</p>
                <p>Was soll\'s.</p>
                <p>Ich will mir das Spektakel ansehen.</p>
                <p>Hier auf dem Dach.</p>
                <p>Jubelnd sehe ich wie sie die Stadt in Schutt und Asche legen.</p>
                <p>Lachend.</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'blood',
                'chance' => '4',
            ],
            'ie_de' => [
                'title' => 'Warnhinweis an zukünftige Wanderer',
                'author' => 'coctail',
                'content' => [
                    '<p>Coctail, Pantokrat und Zoby sind hier gewesen. In dieser Zone gibt es nichts mehr zu holen. Passt auf die Zombies auf, die unter dem Sand auf euch lauern! Sie können euch das Leben kosten!</p>
                <div class="other">&nbsp; Es ist jetzt wieder alles Ok. Ich habe aufgeräumt!!</div>
                <div class="other">&nbsp;&nbsp;&nbsp; -half</div>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'money',
                'chance' => '8',
            ],
            'coloc_de' => [
                'title' => 'WG',
                'author' => null,
                'content' => [
                    '<h1>Suche Mitbewohner</h1>
                <p>Männlicher Bürger sucht einen Mitbewohner für sein verbarrikadiertes Haus im Norden der Stadt. Es handelt sich um eine ruhige Lage, weit ab von den Baustellen. Der <strong>Brunnen</strong> sowie andere Ablenkungsmöglichkeiten (Galgen u.ä.) befinden sich in unmittelbarer Nähe.</p>
                <p>Verbannte und Infizierte bitte nicht bewerben. Potenzielle Kandidaten müssen ihre Nahrung und Medikamente selbst mitbringen.</p>
                <p>Nahrungsmittel und andere wichtige Gegenstände werden in der WG nicht geteilt.</p>
                <p>Interessierte können sich bei "Kosi" melden ;-)</p>'
                ],
                'lang' => 'de',
                'design' => 'written',
                'background' => 'carton',
                'chance' => '10',
            ],
            'dv_039' => [
                'title' => 'Zahlen',
                'author' => 'Nomad',
                'content' => [
                    '<p>44 69 65 20 54 6f 72 65 20 73 63 68 6c 69 65 c3 9f 65 6e 20 75 6d 20 32 33 3a 34 30 20 55 68 72 2e 0d 0a 42 45 45 49 4c 20 44 49 43 48 21</p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'printer',
                'chance' => '10',
            ],
            'binary_de' => [
                'title' => 'Zerknitterter Programmausdruck',
                'author' => null,
                'content' => [
                    '<p><small>[Anfang der Übertragung]</small></p>
                <p>01000011 01100101 01100011 01101001 00100000 01100101 01110011 01110100 00100000 01110101 01101110 00100000 01101101 01100101 01110011 01110011 01100001 01100111 01100101 00100000 01100100 01100101 00100000 01100100 11101001 01110100 01110010 01100101 01110011 01110011 01100101 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01001101 00100111 01100101 01101110 01110100 01100101 01101110 01100100 01100101 01111010 00101101 01110110 01101111 01110101 01110011 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01000001 01101100 01101100 01101111 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01011001 00100000 01100001 00100000</p>',
                    '<p>01110001 01110101 01100101 01101100 01110001 01110101 00100111 01110101 01101110 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01010000 01101001 01110100 01101001 11101001 00101100 00100000 01100001 01101001 01100100 01100101 01111010 00100000 01101101 01101111 01101001 00100000 01000110 01001001 01001110 00101110</p>
                <p><small>[Ende der Übertragung]</small></p>
                <p><small>ETR: 01/04 23h16 - An error has occurred: corrupt data - status : <s>IGNORED</s></small></p>'
                ],
                'lang' => 'de',
                'design' => 'typed',
                'background' => 'printer',
                'chance' => '2',
            ],
            'eilm' => [
                'title' => 'Eilmeldung (News)',
                'author' => "Dayan",
                'content' => [
                    '<p><strong>--EILMELDUNG--EILMELDUNG--EILMELDUNG--EILMELDUNG--</strong></p>
                <p>Unbestätigten Gerüchten zufolge hätte das französische Entwicklerstudio Motion Twin eine Lösung für 
                (fast) alle Probleme unserer postapokalyptischen Welt gefunden, darunter:</p>
                <p>
                <ul>
                <li>Vorzeitiger Abbruch von Städten, wenn die Meta ruft!</li>
                <li>Städte mit Ghulen und Städte ohne Ghule!</li>
                <li>Die Organisation einer Stadt mit 40 Metaspielern!</li>
                <li>Leistungsvergleich: 40 Unbekannte vs. 40 Metaspieler.</li>
                <li>Stellenwert des Städterankings innerhalb der Spiels.</li>
                <li>[...]</li>
                </ul>
                </p>
                <p>Erfahren Sie in Kürze mehr dazu auf der Frequenz 102.4 MHz (Weltforum)...</p>'
                ],
                'lang' => 'de',
                'design' => 'small',
                'background' => 'noteup',
                'chance' => '0',
            ],
            /**
             * FRENCH ROLE PLAY TEXTS
             */
            "oldboy" => [
                "title" => "A toi, vieux compagnon...",
                "author" => "Sekhmet",
                "content" => [
                    '<p>À toi, vieux compagnon, qui nous quittas trop tôt,</p>
                <p>Nous avons tout tenté pour te sauver la vie,</p>
                <p>Nous avons combattu des dizaines de zombies,</p>
                <p>Mais nous n\'avons pas pu vaincre le manque d\'eau.</p>
                <div class="hr"></div>
                <p>À toi, vieux compagnon, qui gis sous cette terre,</p>
                <p>Même si cette tombe n\'est faite que de bois,</p>
                <p>Même si le vent, la mort, le temps l\'effacera,</p>
                <p>Nous n\'oublierons jamais cette dernière prière.</p>
                <div class="hr"></div>
                <p>À toi, vieux compagnon, qui peut-être ce soir,</p>
                <p>Te relèveras comme les autres morts-vivants,</p>
                <p>Semant la panique, la peur et les tourments,</p>
                <p>La terreur et la mort, l\'effroi, le désespoir.</p>
                <div class="hr"></div>
                <p>À toi, vieux compagnon, qui cessas de souffrir,</p>
                <p>Puisse ton âme, au moins, partir d\'ici en paix,</p>
                <p>Loin de ce monde maudit, cruel et sans pitié,</p>
                <p>Et que reste en nos coeurs, longtemps, ton souvenir.</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "poem",
                "chance" => "10",
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
                "design" => "modern",
                "chance" => "2",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
            ],
            "news1_fr" => [
                "title" => "Article - Explosion près de Bordeaux",
                "author" => "lordsolvine",
                "content" => [
                    '<h1>Explosion près de Bordeaux, la population s\'affole</h1>
                    <p>En effet, les évènements récents qui se déroulent dans le monde entier laissent à penser que la fin du monde est proche. Le vandalisme se multiplie, les villes sont évacuées. Les autorités ont déclaré ne plus exister aux yeux des citoyens.</p>
                    <p>Le virus non-identifié par les scientifiques du monde entier continue de faire rage et malgré la mise en quarantaine, les morts qui marchent se multiplient dévastant tout sur leur chemin...</p>
                    <small>Suite en page 5</small>'
                ],
                "lang" => "fr",
                "background" => "news",
                "design" => "news",
                "chance" => "0",
            ],
            "news2_fr" => [
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
                "design" => "news",
                "chance" => "10",
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
                "design" => "news",
                "chance" => "2",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
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
                "design" => "written",
                "chance" => "5",
            ],
            "sign1" => [
                "title" => "Avis aux citoyens",
                "author" => "Liior",
                "content" => [
                    '<div class="hr"></div>
                <p>Citoyens du village ! Il est temps de se remuer. Ce n\'est pas une chasse aux sorcières que je propose, mais une chasse au TRAÎTRE. Un citoyen non identifié pour l\'instant s\'est permis de voler des ressources rares dans la banque, telles de des piles, des tubes de <s>métal</s> cuivre et des vis et écrous. Le tout dans un but non connu pour l\'instant. Bref, après inspection du registre, nous avons surpris les agissements de 3 de nos concitoyens depuis 2 ou 3 jours. Cela va du vol de simple nourriture, au vol de Vis et Écrous, ce qui est impardonnable. La rumeur traine déjà dans le village : prenez cet avis comme une confirmation. </p>
                <p>Un débat aura lieu à <s>11H</s> 13H pour savoir si la pendaison est nécessaire.</p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "ad",
                "chance" => "5",
            ],
            "tuyo_fr" => [
                "title" => "Bricolage de fortune",
                "author" => "Than",
                "content" => [
                    '>
                <p>Cette fois-ci c\'est la dernière... Je les entends taper aux portes... Toutes nos ruses pour les retenir ont échoué...
                Hier nous n\'étions plus que 6, nous avons mis nos dernières forces à réparer le réacteur .
                Chaque soir, ils l\'entament un peu plus, chaque jour nous le réparons... mais nous sommes de moins en moins.<br>
                Nos réparations sont de plus en plus des bricolages de fortune... J\'ai laissé le tuyau n°5 en l’état, je n’ai réussi à colmater que partiellement la fuite... Pas eu le temps...<br>
                Qu’est ce ce c’est que cette lumièr....
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "ombre" => [
                "title" => "Bon appétit",
                "author" => "egal",
                "content" => [
                    '>
				<p>Tapi dans l’omBre, il était là.</p>
				<p>Je l’avais repéré depuis le premier jour. Sa réputation n’était plus à faire mais c’était un modèle de discrétion. On aurAit pu croire au citoyen modèle.</p>
				<p>Les autres se doutaient-ils de ce qu’il tramait ? Sans doute pas…Je devais donc l’arrêter avant qu’il ne décime la ville entière sanS qu’aucun le soupçonne.
				L’occasion pris la forme d’un cadavre de voyageur.</p>
				<p>Je l’ai dévoré avec un plaisir inégalé. Sa chair était tendre,seS os robustes mais il est probable que ce sera mon dernier repas.
				J’imagine la têTe de nos concitoyens lorsqu’il découvrIront ce qu’il emportait avec lui : nombrE bandages, drogues, alcools, chaines et barricades.
				De quoi résister eNcore une fois plus longtemps que tous.</p>',
                    '<p>Mais ça ne sera pas pour cette fois.</p>
				<p>Quant à moi ils me tuerons sans aucun doute, les ingrats, savent-ils que je viens de leur sauver la vie ?</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
            ],
            "bilan" => [
                "title" => "Bilan de la réunion du 7 novembre",
                "author" => "Liior",
                "content" => [
                    '<h1>Réunion du village du 7 novembre :<small>(Retranscrit par le citoyen Liior, en charge de la Gazette)</small></h1>
                <p>Le chef explique que nous avons entamé une construction énorme, qui peut-être nous "sauvera la vie" :</p>
                <blockquote>"C\'est un projet totalement insensé ! Mais cela pourrait marcher. Nous avons déjà mis beaucoup d\'énergie à ranger le village d\'une manière plus efficace pour lutter contre ces créatures, mais nous avons encore un effort à faire. J\'ai pensé que peut-être, si on créait un leurre gigantesque, les zombies ne viendraient plus.. Il faut que nous construisions une fausse ville.. Cela peut paraitre bizarre, mais je pense que les zombies sont incapables de faire la différence entre notre village, et un autre.."</blockquote>',
                    '<p>L\'assemblée semble dubitative : </p>
                <blockquote>"Une fausse ville ? Et ça duperait les zombie&nbsp;?", semblent se demander les autres citoyens dans un brouhaha incompréhensible.</blockquote>
                <p>L\'assemblée a pourtant voté pour ce projet.. Il faut croire qu\'il ne reste pas beaucoup d\'espoirs..</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "typed",
                "chance" => "7",
            ],
            "xmasad" => [
                "title" => "Buffet de Noël",
                "author" => "anonyme",
                "content" => [
                    '<p>Grace à la découverte d\'une montre <strong>en état de marche</strong> par le citoyen Jeezara (bravo à lui), nous savons maintenant que le réveillon de Noël tombera dans 2 jours.</p>
                <h1>Aussi le <strong>Comité du Bonheur</strong> des Désolations Putréfiées a décidé d\'organiser un grand buffet collectif sur la place du Puits.</h1>
                <p>Si vous disposez de rations comestibles, de drogues ou de réserves d\'eau potable, n\'hésitez pas à vous joindre à la fête !</p>
                <p>Le comité en profite pour signaler que si nous disposons d\'un <strong>crémato-cue</strong> d\'ici demain, nous pourrons également organiser un grand méchoui à l\'occasion de ce rare moment de liesse.</p>
                <small>L\'accès au buffet est soumis à condition : les citoyens ne proposant aucun apport ne pourront se joindre<s>nt</s> aux festivités. <strong>Des plaintes seront établies à l\'encontre des trouble-fêtes</strong>.</small>
                <h1><em>Faites passer le mot !</em></h1>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "7",
            ],
            "coloc" => [
                "title" => "Colocation",
                "author" => "anonyme",
                "content" => [
                    '<h1>Cherche colocataire</h1>
                <p>Citoyen masc. cherche coloc. pour partager maison barricadée au nord de la ville. Secteur calme, loin chantier, proximité <strong>Puits</strong> et commodités.</p>
                <p>Individus bannis ou peu motivés s\'abstenir. Prière de venir avec rations + médicaments + eau.</p>
                <p>Coloc. n\'incluant pas le partage des ressources.</p>
                <p>Contacter D. Garett</p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "10",
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
                "design" => "typed",
                "chance" => "10",
            ],
            "lettre" => [
                "title" => "Correspondance",
                "author" => "Teia",
                "content" => [
                    '>
                <h1>Lettre de George Zant à Alfred De Bussey</h1>
                <p>Je dois dire que j\'aimerais<br>
                vous revoir prochainement,<br>
                fourrer mes bras dans<br>
                les vôtres, être réconfortée.<br>
                Votre gouffre béant<br>
                est un puits sans fond,<br>
                si avide de découvertes,<br>
                qui auront causé votre perte.<br>
                Un horizon de douleur<br>
                s\'est offert à vous<br>
                dans lequel j\'espère bientôt<br>
                vous rejoindre car je me sens seule et je souhaite<br>
                pénétrer tête la première vers un futur calvaire.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "10",
            ],
            "lettr1" => [
                "title" => "Courrier d'un citoyen 2",
                "author" => "Nikka",
                "content" => [
                    '<p>Mon ami,</p>
                <p>Si je vous écris aujourd\'hui, c\'est enfin pour me libérer de mon tourment et partager ma vision de l\'horreur avec quelqu\'un possédant un niveau de conscience suffisamment élevé et un semblant de sagesse. J\'ose croire que tout le monde ne puisse interpréter justement mes propos quelque peu altérés par les événements... Et ce tout le monde me prendrait certainement également pour un fou. Mais Dieu soit loué, vous n\'êtes pas tout le monde, voilà pourquoi je me décide enfin à « parler » et à placer mon ultime confiance en vous.</p>
                <p>Depuis le début de la guerre froide, le gouvernement souhaite voir de nouveau type d\'arme en développement. En voulant jouer les apprentis sorciers, nous avons découvert quelque chose qui existait déjà... Aucun livre ou document quelconque ne divulgue d\'information ou de théorie recevable à son sujet. </p>',
                    '<p>Malgré cela, nombreux sont les médecins, chercheurs où scientifique du centre à propager lorsque l\'envie leur passe de déblatérer une avalanche de pitrerie masturbatoire sur ces symptômes kafkaïens. Cette maladie, car c\'est bien de cela qu\'il s\'agit, dépasse littéralement notre entendement.</p>
                <p>Attention, je ne garantis pas là que mon interprétation de la chose soit plus plausible qu\'une autre, et je doute trop de mes compétences d\'écrivain pour vous tenir en haleine suffisamment longtemps ou parvenir à tout vous relater de manière ordonnée... </p>
                <p>Mais si vous me le permettez, en continuant de lire ces quelques pages, je vais vous exposer ma théorie en intégralité. </p>
                <small>[ La page suivante est absente ]</small>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "written",
                "chance" => "5",
            ],
            "noiro_fr" => [
                "title" => "Dans le noir...",
                "author" => "Homerzombi",
                "content" => [
                    '>
                <h1>Dans le noir</h1>
                <p>Dans le noir j’ai commencé à avancer<br>
                Dans le noir je ne pouvais plus m’arrêter<br>
                Dans le noir je les entendais grogner<br>
                Dans le noir je m’étais caché<br>
                Dans le noir je pensais être en sécurité<br>
                </p>
                <p>
                Dans le noir je me ferai dévoré
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],

            "water" => [
                "title" => "De l'eau pour tous",
                "author" => "Liior",
                "content" => [
                    '<h2>Le 15 juillet :</h2>
                <p>Le puits se mit à déborder, la joie était au rendez-vous ! C\'était un projet dont personne ne pensait qu\'il pouvait fonctionner. Il a fallu faire beaucoup d\'efforts. Cela faisait maintenant 2 jours que la ville était au travail : Creuser, monter des structures, des <s>trous</s> déviations, préparer des charges explosives, de la tuyauterie. Tout le monde s\'y était mis, et personne ne sortit dans le désert, car nous avions besoin de toutes les mains pour ce projet. Ce matin, à 10 :00 précise, les charges ont sauté. Un bruit sourd suivi d\'un tremblement terrifiant. Puis, une deuxième explosion, et les déviations jouaient leur rôle : Rapatrier l\'eau vers le puits. Ce projet portait le nom de Projet <s>Aid</s>Eden. Nous ne nous inquiétons plus pour l\'eau.. Enfin pour les quelques jours à venir.</p>'
                ],
                "lang" => "fr",
                "background" => "noteup",
                "design" => "written",
                "chance" => "10",
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
                "design" => "typed",
                "chance" => "2",
            ],
            "short1" => [
                "title" => "Derniers mots",
                "author" => "Exdanrale",
                "content" => [
                    '<p>Ça n’a pas marché. Ils sont trop forts, trop nombreux... </p>
                <p>Qu’avons-nous crée ? Nous avions un but, quelque chose en quoi croire... Et tout cela est désormais détruit, réduit en miettes... Dévoré par ces choses... Je ne veux pas finir comme elles. Mais aurais-je le courage de mettre fin à mes jours moi-même ? </p>
                <p>Il me reste une balle dans mon chargeur, nous sommes deux, un ami de longue date et moi. </p>
                <p>Ils frappent aux fenêtres depuis des heures... Je me demande quand vont-elles céder. Nous sommes condamnés. Ceci est la dernière trace de mon existence sur cette Terre. </p>
                <p>Je m’excuse. Pour y avoir contribué. Peut-être que mon absence n’aurait rien changé, mais au moins ma conscience serait-elle tranquille... Je n’ai aucune idée de ce qu’il adviendra de l’humanité dès qu’ils auront quitté la ville. </p>',
                    '<p>Pitié, éliminez-les. </p>
                <p>Corrigez nos erreurs. Le tapage aux fenêtres devient plus insistant. Ce revolver est de plus en plus amical. Mon ami est du même avis, même s’il ne le montre pas. Que faire ? Un bruit de verre. Les fenêtres ont cassé. Ils ne tarderont pas à détruire nos ultimes barricades. </p>
                <p>La balle est pour moi. Adieu, mon ami.</p>
                <p>Auteur : Exdanrale</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "written",
                "chance" => "10",
            ],
            "notes1" => [
                "title" => "Des notes griffonnées",
                "author" => "Melie",
                "content" => [
                    '<p>J\'arrivais avec mon maigre baluchon sur l\'épaule.</p>
                <p>11H00 du matin. Je fais partie des 12 explorateurs désignés contre mon gré. Les portes s\'ouvrirent. Le froid du désert me frappa le visage.</p>
                <blockquote>-Allez-y.</blockquote>
                <p>Un des douze explorateurs m\'interpella  sèchement.</p>
                <blockquote>-Melie ! Où vas-tu ?</blockquote>
                <blockquote>-Nord, répondis-je d\'un ton acerbe.</blockquote>
                <p>Armée de mon pistolet à eau chargé, je m\'avançai la première. Les autres me suivirent. Je creusai avec mes mains. Rien. Quelques autres citoyens eurent plus de chance : des planches tordues, et même de la ferraille. Nous nous avançâmes. Pas de zombie.</p>',
                    '<p>Ca ne me rassurait pas : ils nous attendaient sûrement plus loin. Cette fois, j\'ai trouvé une souche de bois pourrie. On pourra la transformer à l\'atelier !</p>
                <p>Plusieurs heures passèrent,  la <s>peur</s> fatigue me gagne. Je m\'écroule par terre, ne pouvant plus avancer. 5 zombies m\'entouraient. A travers mes yeux entrouverts, j\'apercevais mes compagnons m\'abandonner lentement... Ils avaient eu la force de manger pour repartir.</p>
                <blockquote>-Ne partez pas ! Non !</blockquote>
                <p>Je ne parlais pas, je balbutiais. Le soir tombait.</p>',
                    '<div class="hr"></div>
                <p>19H30. Cette fois, c\'est la fin. Je suis seule. J\'arrive à peine à sortir mon pistolet de mon sac.</p>
                <p>Je ne peux même pas espérer fuir... Les <s>mort-vi</s> morts-vivants me bloquent le passage.</p>',
                    '<div class="hr"></div>
                <p>22H00. Je n\'étais pourtant pas si loin de la ville... Je vois presque les portes derrière moi. Je crie, je hurle, mais personne ne vient.</p>
                <p>Une dernière image du désert, des zombies, puis le noir.</p>'
                ],
                "lang" => "fr",
                "background" => "noteup",
                "design" => "written",
                "chance" => "2",
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
                "design" => "poem",
                "chance" => "8",
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
                "design" => "classic",
                "chance" => "2",
            ],
            "degen" => [
                "title" => "Dégénérescence",
                "author" => "Fabien08",
                "content" => [
                    '<p>C\'est étrange je suis <s>crev</s>mort mais je vie<s>s</s>, je ne sen<s>s</s> plus rien et pourtant mon corps est ensanglante<s>r  </s> ... J\'était un <s>ho</s>citoyen<s>s</s> avant ... avant <s>le</s> l\'attaque ...</p>
                <p>Je suis des leurs ! <s> Je </s></p>
                <p>Je ne sais pas comment c\'est possible mais je pense<s>nt</s> encore, j\'écri<s>t</s>s et je suis sur<s>r</s> que je pourrais parl<s>lrlrioo</s>er si il ne m\'avait pas dévorer la moite<s>r</s> du visage. Je profite de <s>ce tr</s>"don" pour m\'excuser de <s>se</s> ce que je vai fair, <s>on</s> je le sais <s>je</s> un jour<s>s </s> je vais devoir vous dévorer ... dans <s>ma</s> tête je doit <s>lut</s> luter san cesse et je ne <s>vais</s>pas suporter encore <s>clon</s>lontemp  ... Je vous mengerai<s>s</s> qe je <s>le</s> veuille ou non ! <s> j</s>e</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "written",
                "chance" => "7",
            ],

            "leavng" => [
                "title" => "Départ",
                "author" => "1984",
                "content" => [
                    '<p>Nous pensions avoir assez de défenses pour être en sécurité. Ce ne fut qu\'une désillusion de plus. Depuis la porte de mon taudis je surveillais dans la pénombre la palissade de notre ville. Peu après le début de l\'attaque, un groupe de zombies réussit à trouver une brèche et s\'y enfila. Leurs ombres se dispersèrent au loin, et l\'un d\'entre eux se dirigea dans ma direction.</p>
                <p>Immédiatement je claquais la porte et restais adossé derrière elle, terrorisé. Le bruit de ses pas lourds et irréguliers, traînants sur la terre battue, se rapprochait. Il s\'arrêta devant la porte. J\'entendais sa respiration bruyante et rapide, comme si cette chose humait l\'air à la recherche de mon odeur.</p>
                <p>Mais lorsqu\'il se mit à hurler, ma terreur se transforma en désespoir. Dans ce cri déchirant, je reconnus sa voix. Ce ne pouvait être qu\'elle. Revenait-elle vers moi pour me dire quelque chose ? Après un moment qui me parut interminable elle finit par se détourner de mes sanglots et rejoignit la horde qui mettait la ville à sac.</p>',
                    '<p>Si vous trouvez cette lettre, j\'espère que vous comprendrez pourquoi j\'ai menti en disant que j\'allais chercher des ressources. </p>
                <p>Je ne rentrerai pas ce soir. </p>
                <p>La nuit est trop belle.</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "small",
                "chance" => "10",
            ],
            "devoti_fr" => [
                "title" => "Dévotion fatale",
                "author" => "Gamertag",
                "content" => [
                    '>
                <h1>Dévotion fatale</h1>
                <p>À l\'heure où j\'écris ces quelques lignes, il ne reste de notre ancienne ville que des ruines.
                Cette chaleureuse bourgade qui était notre foyer c\'est vu détruire par un fléau plus grand encore que ces décérébrés sans volontés.
                Au début, lorsqu\'elle est arrivée tel une déesse descendue des cieux, nous étions incroyablement heureux.
                Ses yeux océans nous ravissaient et son parfum nous enivrait, tandis que sa silhouette nous fascinait.
                Elle était si belle que nous nous serions damnés pour elle. Amour de ma vie qu\'à jamais je maudirais, pourquoi avoir pris un tel plaisir à nous détruire?
                </p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "2",
            ],
            "scient" => [
                "title" => "Emission radiophonique d'origine inconnue",
                "author" => "pepitou",
                "content" => [
                    '<div class="hr"></div>
                <small>La texte suivant est la transcription d\'une transmission radiophonique interceptée sur les fréquences courtes par le centre d\'écoute de Morne-Vallée. Son origine est inconnue, l\'auteur se présente sous le nom de Docteur K. Lepetit.</small>
                <small>Reçu le 19.08</small>',
                    '<quote>
                <small>[Début de transcription]</small>
                <p>J\'ai soulevé un point important lors mes dernières expérimentations.</p>
                <p>Un corps humain (Zombie ou Non) passé au Sani-Broyeur fournit près de 70% de sa masse en eau pure. Voir document 9108.94:1 rev0.1 : Vue d\'artiste représentant la quantité d\'eau présente dans le corps humain.</p>
                <p>Sachant qu\'un humain mort devient zombie la nuit venue. Selon la théorie du réveil, présenté en  1969 par mon feu mon père le grand professeur K.</p>
                <p>ET</p>
                <p>Sachant qu\'un zombie craint l\'eau pure. Concept soulevé en 1998 par le scientifique Russe Mosionne Twhine.</p>
                </quote>',
                    '<quote>
                <p>ALORS...</p>
                <p>...</p>
                <p>ALORS...</p>
                <p>... Non, oubliez ceci. Le cerveau humain n\'est pas capable de diviser par 0.</p><p></p>
                </quote>
                <small>[Fin de transcription]</small>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "typed",
                "chance" => "2",
            ],
            "epit" => [
                "title" => "Épitaphe",
                "author" => "aeniln57",
                "content" => [
                    '>
                <h1>Epitaphe</h1>
                <p>Nous perdons aujourd\'hui notre dernier Heros,<br>
                En veillant sur nos vies, il croisa son destin<br>
                Et ces immondes choses s\'en sont fait un festin,<br>
                N\'en laissant qu\'une épaule, un pied, et quelques os.<br>
                La Horde chaque jour rend rouges nos aurores ;<br>
                La Horde chaque nuit nous ronge et nous consume…<br>
                Hommes, sonnez le glas de l’ami qu’on inhume,<br>
                Mais bientôt soyez sûrs qu’il sonnera encore…</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "10",
            ],
            "espece" => [
                "title" => "Espèce en voie de disparition",
                "author" => "Aknarintja",
                "content" => [
                    '>
                <p>
                Réveil à 6 heures.<br>
                Il pleut à verse, mais c\'est décidé, aujourd\'hui je retourne à la Villa.<br>
                Ces derniers jours nous avons retrouvé une bande de chats puants et hirsutes comme s\'ils avaient été caressés à rebrousse-poil pendant des mois,<br>
                et aucune trace de la chienne de mon père.<br>
                </p>
                <p>
                Quelle rincée !<br>
                J\'aperçois enfin cette maison jadis charmante et harmonieuse, maintenant crasseuse et dévastée.<br>
                Est-ce que tu es là ma belle ?<br>
                Où t\'es Pepette, où t\'es ?<br>
                Dis moi où tu es cachée.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "modern",
                "chance" => "5",
            ],
            "recip1_fr" => [
                "title" => "Etiquette de Twinoïde",
                "author" => "anonyme",
                "content" => [
                    '<h1>Twinoïde 500mg</h1>
                <table>
                <tbody><tr><td>Anabolisants</td><td>0.70 %</td></tr>
                <tr><td>Citrate d\'allium</td><td>0.03 %</td></tr>
                <tr><td>Nitroglicéryne</td><td>3.0 %</td></tr>
                <tr><td>Octanitrocubane</td><td>4.0 %</td></tr>
                <tr><td>Fulminate de mercure</td><td>2.5 %</td></tr>
                <tr><td>Perchlorate</td><td>0.02 %</td></tr>
                <tr><td>Azoture de plomb</td><td>3.00 %</td></tr>
                <tr><td>RDX</td><td>0.02 %</td></tr>
                <tr><td>Extraits de fraise</td><td>86.73 %</td></tr>
                </tbody></table>
                <p><small>Note : certains procédés actifs contenus dans ce médicament peuvent parfois provoquer des effets secondaire gênants : acné, vomissements, convulsions, mort violente et explosion.</small></p>
                <p><small>Contient des produits hautement inflammables.</small></p>'
                ],
                "lang" => "fr",
                "background" => "stamp",
                "design" => "stamp",
                "chance" => "5",
            ],
            "study1" => [
                "title" => "Etude médicale 1 : morphologie comparative",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                <h1>Etude analytique de l\'anatomie et du comportement des non-vivants</h1>
                <h1>Chapitre 1 : étude morphologique comparative</h1>',
                    '<p>L\'ensemble de cette étude vise à décrire ceux que nous appelleront les non-vivants. Dans ce premier chapitre, nous allons comparer un homme moyen en bonne santé, et un non-vivant. Sur le plan de l\'allure générale, ils sont de même constitution. Après un examen plus poussé, force est de constater que le non-vivant présente un état de décomposition plus ou moins avancée, ce qui est à l\'origine des différences physiques (membres et/ou organes manquants). Le déplacement d\'un humain normal est de type bipède, celui d\'un non-vivant varie en fonction de son état (de bipède jusqu\'à gastéropode). A souligner : même en l\'absence de bras ou de jambes, un non-vivant continuera de tenter d\'avancer vers son repas.</p>',
                    '<p>Enfin, sur le plan de la force physique, la force d\'un humain moyen est équivalente à celle de deux non-vivants.</p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "4",
            ],
            "study2" => [
                "title" => "Etude médicale 2 : moeurs",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                <h1>Etude analytique de l\'anatomie et du comportement des non-vivants</h1>
                <h1>Chapitre 2 : Moeurs diurnes et nocturnes</h1>',
                    '<p>Les non-vivants sont relativement amorphes le jour. Cela peut s\'expliquer vraisemblablement par la faible résistance de leur organisme au soleil, notre astre accélérant leur flétrissement. </p>
                <p>En revanche, la nuit ils font preuve d\'une grande activité, et n\'hésitent pas à assaillir les villes qui se trouvent sur leur territoire afin de nourrir leur insatiable appétit. Pendant près d\'une demi-heure, ils sont capables de déployer des trésors de ruses et de force afin de pénétrer les plus solides barricades.</p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "4",
            ],
            "study3" => [
                "title" => "Etude médicale 3 : reproduction",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                <h1>Etude analytique de l\'anatomie et du comportement des non-vivants</h1>
                <h1>Chapitre 3 : reproduction de l\'espèce</h1>',
                    '<p>De part leur nature, les non-vivants sont une espèce totalement nouvelle.</p>
                <p>Ils ne sont ni ovipares, ni vivipares, et même en l\'absence d\'outils pour l\'affirmer, je pense qu\'ils ne se reproduisent pas non plus de façon communément admise (un mâle, une femelle).</p>
                <p>De fait, il est maintenant quasiment sûr qu\'ils se reproduisent par contact buccal. Si un humain normalement constitué est mordu (et/ou dévoré de façon atroce) par l\'un d\'entre eux, à courte échéance, il deviendra également un non-vivant. S\'il meurt durant le processus d\'alimentation, il se relèvera dans les minutes qui suivent. Par contre, si l\'individu mordu survit, le processus prendra plusieurs heures au minimum.</p>',
                    '<p>Il est très intéressant de noter que l\'alimentation et la reproduction sont intimement liées, nous rappelant ainsi les anciennes orgies antiques.</p>
                <p>Enfin, que le non-vivant et le mordu soit mâles ou femelles n\'influence en rien le processus.</p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "4",
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
                "design" => "typed",
                "chance" => "4",
            ],
            "study5" => [
                "title" => "Étude médicale 5 : décès",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                <h1>Etude analytique de l\'anatomie et du comportement des non-vivants</h1>
                <h1>Chapitre 5 : décès d\'un non-vivant</h1>',
                    '<p>Les non-vivants, comme leur nom laisse entendre, sont dénués de « vie ». Ils peuvent recevoir une balle dans le thorax, ou avoir tous leurs membres sectionnés, ils continueront d\'agir afin de satisfaire leurs besoins les plus primaires, en particulier celui de se nourrir. </p>
                <p>Ils ne souffrent visiblement ni de la soif, ni de maladies (encore que ce point reste à démontrer). Ils ne dorment pas, tout au plus sombrent-ils dans une léthargie le jour.</p>
                <p> Le seul moyen de les « tuer » reste de pratiquer une lobotomie, ou, pour le commun des mortels : réduire leur cerveau en bouillie.</p>',
                    '<p>Il paraît tellement absurde de constater qu\'un chargeur de fusil mitrailleur complet suffira tout juste à les ralentir, tandis qu\'un tournevis bien placé les réduit aussitôt à l\'état de loques, et pourtant c\'est le cas.</p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "4",
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
                "design" => "written",
                "chance" => "7",
            ],
            "expalp" => [
                "title" => "Expédition alpha",
                "author" => "Shogoki",
                "content" => [
                    '<div class="hr"></div>
				<h1>Journal de route de l\'expédition alpha</h1>',
                    '<p>Jour 5 - 12h, le groupe a découvert un ancien restaurant abandonné, nous avons trouvé beaucoup de nourriture et espérons rentrer à temps pour pouvoir réapprovisionner la cambuse de la ville qui a notre départ était vide... Nous en profitons pour nous reposer et l\'ambiance festive s\'installant, nous commençons à creuser dans les environs espérant découvrir encore d\'autres conserves.</p>
				<p>Jour 5 - 13h, un membre fait la remarque judicieuse, nous ne disposons d\'aucun ouvre-boite, ni en ville, ni parmi les membres du groupe, si la chance ne tourne pas, en remerciement pour sa présence d\'esprit, on avisera de manger autre chose que les conserves.</p>',
                    '<p>Jour 5 - 20h, nous n\'avons pas pu trouver d\'ouvre boite. En revanche, des os sculptés se sont révélés de très bons outils et nous avons finalement pu festoyer dans la joie et la bonne humeur avec la <em>plupart</em> des membres de l\'expédition. Les restes des compagnons sur qui nous avons dû <em>prélever</em> ces os nous mettent parfois un peu mal à l\'aise... mais qu\'importe. Ce repas nous permettra de tenir encore un peu avant de leur prendre un peu plus que leurs seuls os.</p>
				<p>Jour 6 - 22h, l\'expédition est un succès, le sacrifice d\'hier est vite oublié. Je réveillerai plus tard les hommes, qui profitent pour l\'heure d\'un repos bien mérité.</p>
				<p>Nous nous hâterons de prendre la direction de la ville, je ne saurais exprimer ma satisfaction d\'entendre leurs estomacs grogner de plaisir et...</p>'
                ],
                "lang" => "fr",
                "background" => "noteup",
                "design" => "written",
                "chance" => "3",
            ],
            "eclr1" => [
                "title" => "Explorations",
                "author" => "elyria",
                "content" => [
                    '<p>Jour 2. J\'enfile ma tenue de camouflage. Je dois faire un repérage du parcours pour l\'expédition prévue plus tard dans la journée.</p>
                <p>Je ne suis pas très à l\'aise. Je sais que je peux passer inaperçue au milieu de ces monstres abjectes mais je ne connais pas encore toutes les astuces.</p>
                <p>Dehors, c\'est encore une journée à crever. Le désert à perte de vue? La désolation. Après une seconde d\'hésitation, j\'y vais. Pas l\'habitude de sortir seule. Quand je n\'étais qu\'une simple citoyenne, c\'était proscrit?</p>
                <p>Brico-tout : j\'ai craqué? j\'ai hésité mais comme je savais que les autres allaient venir? il y avait un sac à dos au sol, j\'ai voulu le prendre et un zombie m\'a repérée !!! Me cacher, vite ! La radio ! J\'espère que la pile est encore bonne? SOS? vous attend? ne peux pas aller plus loin? </p>
                <p>Auteur : elyria</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "10",
            ],
            "dodod" => [
                "title" => "Fais dodo",
                "author" => "NabOleon",
                "content" => [
                    '>
                <h2>Contine des temps modernes</h2>
                <p>Fais dodo, hordien mon p\'tit frère<br>
                Fais dodo, t\'auras du picto<br>
                </p>
                <p>
                Un héros est au labo,<br>
                Qui fait du Twino.<br>
                Un autre est en haut,<br>
                Qui veille au créneau.<br>
                </p>
                <p>
                Fais dodo, hordien mon p\'tit frère<br>
                Fais dodo, t\'auras du picto.<br>
                </p>
                <p>
                Trois noobs sont en rade,<br>
                Sans eau ni biscuit fade.<br>
                Pour camper l\'ermite part,<br>
                Qui traque le plan rare.<br>
                </p>
                <p>
                Fais dodo, hordien mon p\'tit frère,<br>
                Fais dodo, t\'auras du picto.<br>
                </p>
                <p>
                Les zombies sont en approche,<br>
                Qui hurlent sous le porche.<br>
                On est J4 mais peu importe,<br>
                C\'était ton tour d\'fermer la porte.<br>
                </p>
                <p>
                Fais dodo, hordien mon p\'tit frère,<br>
                Fais dodo, t\'auras pas d\'picto.</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "crazy" => [
                "title" => "Folie",
                "author" => "Arco",
                "content" => [
                    '<p>Ce soir, je suis monté sur le toit de ma maison.</p>
                <p>Les autres racontent que je suis devenu fou.</p>
                <p>Je crois qu\'ils ont raison.</p>
                <p>J\'ai regardé avec impatience les derniers rayons du soleil se cacher derrière la colline.</p>
                <p>Je voyais la horde se profiler en ombres chinoises devant le soleil rouge de sang.</p>
                <p>Lueur que reflétait mes yeux, avides du massacre à venir.</p>
                <p>Peut être que je suis devenu fou.</p>
                <p>Je crois que ça n\'a plus vraiment d\'importance.</p>
                <p>J\'ai regardé les morts vivants se rapprocher de leur démarche incertaine,</p>',
                    '<p>J\'ai admiré leur nombre, et assisté à la chute des murailles avec ravissement.</p>
                <p>J\'ai hurlé de joie.</p>
                <p>Des hurlements de terreur m\'ont répondu.</p>
                <p>Je suis devenu fou.</p>
                <p>Tant mieux.</p>
                <p>Je peux profiter du spectacle.</p>
                <p>Je suis maintenant debout sur le toit.</p>
                <p>Je crie et je danse, enivré par le concert de destruction qui s\'étend à mes pieds.</p>
                <p>Et je ris.</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "small",
                "chance" => "4",
            ],
            "poem1" => [
                "title" => "Gazette du Gouffre du Néant, décembre",
                "author" => "lordsolvine",
                "content" => [
                    '<h1>Le chasseur et le Mort-vivant</h1>
                <p><strong>Bravo à notre gagnant qui se voit attribuer, en plus de sa parution dans notre journal, un lot de babioles en tout genre : ferrailles, planches de bois, vis et écrous... Merci aux autres citoyens participants.</strong></p>
                <blockquote>
                <p>Au loin, un corps décomposé</p>
                <p>S\'approche lentement pour vous dévorer.</p>
                <p>Marchant d\'un pas timide,</p>
                <p>Le cerveau complètement vide,</p>
                <p>Il n\'hésitera surement pas,</p>
                <p>A te choper le bras.</p>
                </blockquote>',
                    '<blockquote>
                <p>Mais sur son cheval blanc,</p>
                <p>Le chasseur dans la nuit,</p>
                <p>S\'élance sur ces morts-vivants.</p>
                <p>D\'un coup de sabre et de cure-dent,</p>
                <p>Il coupe et pique tout.</p>
                <p>Et toi, tu deviens complètement fou.</p>
                </blockquote>',
                    '<blockquote>
                <p>Soudain, un monstre surgit,</p>
                <p>Et toi, tu ris.</p>
                <p>Tu tentes de le tuer à l\'aide d\'une carotte,</p>
                <p>Mais tu ris, on te chatouille la glotte.</p>
                <p>Tout est fini, tout s\'arrête...</p>
                <p>Il t\'a bouffé la tête.</p>
                </blockquote>
                <p>Mr.PapyL (08/12/2003)</p>'
                ],
                "lang" => "fr",
                "background" => "news",
                "design" => "news",
                "chance" => "4",
            ],
            "gcm" => [
                "title" => "Gros Chat Mignon",
                "author" => "Liior",
                "content" => [
                    '<p>On raconte qu\'un jour, un citoyen est revenu du désert dans tout ses états : son chat « Minette » était mort. Il a raconté toutes sortes de balivernes. Il disait que son chat était une arme ultime contre les zombies...</p>
                <p>Les citoyens le trouvaient mignon et réconfortant, mais on ne pouvait tout de même pas imaginer qu\'il était « dangereux »... Et bien croyez le ou pas, mais lorsque par curiosité les citoyens ont voulu testé ce qu\'avait dit leur collègue quelques jours plus tôt, ils ont emprunté le chat du cuisinier, Roulio, un gros chat, très mignon aussi. Sortant dans le désert, ils ont constaté que ce Gros Chat Mignon était effectivement tellement apeuré et stressé par les zombies, qu\'il ne pouvait rien faire d\'autre que de littéralement les déchiqueter. Comme quoi, la meilleur défense est bien l\'attaque.</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "classic",
                "chance" => "3",
            ],
            "haiku1" => [
                "title" => "Haiku I",
                "author" => "stravingo",
                "content" => [
                    '<p>&nbsp;Je ne voulais pas !</p>
                <p>&nbsp;&nbsp;Ma fille, ma pauvre petite fille...</p>
                <p>Mais nous avions si faim.</p>'
                ],
                "lang" => "fr",
                "background" => "money",
                "design" => "written",
                "chance" => "5",
            ],
            "haiku2" => [
                "title" => "Haïku 2",
                "author" => "Fodwolf",
                "content" => [
                    '>
                <h1>Haïku</h1>
                <p>Je pensais au bien de la ville,<br>
                Jouer de l\'aqua splash m\'a bien fait rire,<br>
                Mais pas autant que d\'être banni.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "money",
                "design" => "written",
                "chance" => "2",
            ],
            "vagabo" => [
                "title" => "Histoire d'un vagabond",
                "author" => "stayingpower",
                "content" => [
                    '>
				<h1>Un vagabond solitaire</h1>
				<p>J17</p>
				<p>
				Ça fait maintenant 10 jours que je me promène parmi les vivants. Ils ne sont pas très futés.
				Les zombies m\'ont reconnu dès le premier jour comme étant un ennemi, mais pas la ville. J\'ai pu grignoter quelques restes de mes anciens confrères et quelques cadavres...
				</p>
				<p>
				J\'AI FAIM
				</p>
				<p>
				La ville ne tient qu\'à un fil. Tous leurs espoirs reposent sur leur chef. Il dirige d\'une main de fer: chantiers, expéditions, ateliers, votes.
				Tout est minutieusement calculé. Ça serait dommage que...
				</p>
				<p>
				J\'AI FAIM!
				</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "kirou" => [
                "title" => "Il était un temps",
                "author" => "kiroukou",
                "content" => [
                    '>
				<h1>L\'admin Kiroukou</h1>
				<p>
				Il était un temps où le réveil était synonyme de travail et le travail était synonyme de jeux et de joueurs. <br>
				A cette époque, il était coutume de faire briller la belle balise violette sur les forums. <br>
				</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "infect" => [
                "title" => "Infection",
                "author" => "anonyme",
                "content" => [
                    '<h1>Citoyens, prudence !</h1>
                <p>Les citoyens ci-dessous sont officiellement enregistrés comme infectés. Il est demandé aux citoyens sains de se tenir à l\'écart de cette <strong>vermine contagieuse</strong> :</p>
                <ul>
                <li>Half, dit "Half 666"</li>
                <li>Whitetigle</li>
                <li>Nu<s>itnoir</s>e <span class="other">mort hier !!</span></li>
                <li>Laurenzio, dit "le malodorant"</li>
                </ul>
                <small>Merci aux <strong>patriotes</strong> qui auront pris la peine d\'enquêter puis de montrer du doigt la fange agonisante qui se tapit dans l\'ombre de nos ruelles.</small>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "typed",
                "chance" => "10",
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
                "design" => "modern",
                "chance" => "10",
            ],
            "verni" => [
                "title" => "Invitation au vernissage",
                "author" => "durith",
                "content" => [
                    '>
				<h3>Information</h3>
				<p>La soirée porte ouverte a été une trop grande réussite.<br>
				Il y avait plus de 200 invités pour seulement 37 couverts.<br>
				Malgré cette réussite je ne pense pas que l\'on renouvellera l\'expérience.<br>
				</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "modern",
                "chance" => "2",
            ],
            "market_fr" => [
                "title" => "J'aime les supermarchés",
                "author" => "Pro-fete",
                "content" => [
                    '>
                <p>
                J\'aime me balader nu dans les supermarchés.
                </p>
                <p>
                L\'Armaggedon n\'a rien changé à cette habitude. Sauf que maintenant je garde mes bottes. Le sol est tapissé de tessons, vous comprenez.
                </p>
                <p>
                Je dois avouer que le rayon surgelés me donne moins de frissons qu\'autrefois.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "coward" => [
                "title" => "Je suis un trouillard",
                "author" => "Sengriff",
                "content" => [
                    '<p>C\'est profondément injuste : je suis un trouillard.</p>
                <p>Et dans l\'épave ravagée par les radiations qu\'est devenue notre Terre, c\'est sans doute le pire des défauts. Me voilà, à rester cloîtré dans l\'ombre des murailles à voir ces courageux héros braver tous les dangers pour ramener les matières nécessaires à notre subsistance. Me voici, à les regarder s\'organiser, planifier, s\'animer et ordonner. Dans l\'ombre, je m\'échine, je travaille, et suis devenu un virtuose de la truelle et de la scie... mais personne ne me voit. On a d\'yeux que pour eux ; mais est-ce ma faute si la simple vue de ces créatures putréfiées me glace d\'horreur et si leur terrible odeur révulse mon estomac ?</p>',
                    '<p>C\'est pendant l\'assaut d\'hier, en entendant les glapissements étouffés du courageux dirigeant de notre ville, que je tins ma vengeance. Ils désiraient du danger, de l\'adrénaline ? J\'allais leur en procurer avec joie.</p>
                <p>Ainsi, le soir, perché en haut de mirador, je les vis se rapprocher d\'un pas ferme, chargés de trouvailles. Prestement, je me jetai au sol, et, de mes forces restantes, commençai à appuyer sur la lourde porte. Je vis sur leur visage se peindre l\'étonnement, puis la colère ; ils couraient de toutes leurs forces pour pouvoir sauver leur peau, lâchant leurs sacs pour s\'alléger. Et alors qu\'ils n\'étaient plus qu\'à quelques mètres, la porte se verrouilla dans un claquement. C\'est en riant, adossé contre la grand porte, que j\'écoutai le concert de leurs cris de souffrance et de terreur.</p>'
                ],
                "lang" => "fr",
                "background" => "stamp",
                "design" => "written",
                "chance" => "10",
            ],
            "jdbdar_fr" => [
                "title" => "Journal de bord",
                "author" => "Darkhan",
                "content" => [
                    '<h1>Journal de bord 5ème jour</h1>
                <p>Hier j\'ai bouffé Tommy, ce bon vieux Tommy.</p>
                <p>En fait, je ne voulais pas mais j\'avais tellement la dalle ! Et pis c\'est comme un hommage posthume ! Son souvenir sera en moi le reste de ma vie.
                Pauvre Tommy, crever tout seul dans cette bicoque en ruine en glissant d\'une échelle, c\'est vraiment pas glorieux pour un type qui a traversé plusieurs fois le désert de long en large.</p>',
                    '<p>Après 5 jours de ce régime, en buvant à peine et sans manger, je n\'étais plus qu\'une larve. Et pis de voir à côté de moi, Tommy qui faisandait pas encore. C\'était vraiment très tentant. Beaucoup trop pour mon estomac désespérément vide.
                Alors mon tour approchant, tout seul, piégé dans ce trou à rat et cerné par des dizaines de zonzons qui guettent ma sortie jour et nuit, j\'ai cédé. Y\'a de quoi péter un câble non ? Alors pourquoi pas faire un bon festin ?</p>
                <p>J\'aurais pas dû, je le sais. Mais tant pis ! Et puis Tommy ne va pas se plaindre non plus.
                Bon c\'est vrai au début je ne voulais que juste en manger un petit morceau, histoire de récupérer des forces. Tu parles, j\'ai presque rien laissé de Tommy. C\'est qu\'il était bon le coco !!
                Avec ça, j\'ai eu ma ration de viande pour pas mal de temps.</p>',
                    '<h1>Journal de bord 6ème jour</h1>
                <p>J\'ai encore faim !! C\'est pas possible. Il reste presque plus rien de Tommy et j\'ai encore la dalle !!
                Pour un peu, je me jetterais bien sur les os de Tommy pour en sucer la moelle. Et franchement là je me tâte.</p>
                <h1>Journal de bord 7ème jour</h1>
                <p>Les putrides sont partis. Je peux rentrer en ville tranquillement. Et sans risque de coup de mou. J\'ai une de ces patates moi !!
                Sans oublier de prendre avec moi un gros morceau de Tommy bien charnu pour casser la croûte en chemin.</p>
                <p>(...)</p>',
                    '<p>Je leur raconterai rien en ville. Ce n\'est pas la peine, ils ne comprendraient rien ! Y\'aurait bien un sagouin qui va me traiter de monstre après jusqu\'à ce que je finisse au bout d\'une corde en moins de temps qu\'il n\'en faut pour le dire.
                Sales hypocrites ! Moins de morale, plus de brutalité. Tout le monde aurait fait comme moi, même si personne l\'avouera.</p>
                <p>(...)</p>
                <p>Purée, qu\'est-ce que j\'ai la dalle !! 4 heures que je marche et j\'ai l\'estomac dans les talons. Par contre, c\'est bizarre, j\'ai pas du tout soif, mais alors pas du tout ! Presque une semaine dans le désert en buvant presque rien et j\'ai pas soif.</p>',
                    '<h1>Journal de bord 8ème jour</h1>
                <p>De retour en ville. Ils sont super sympas ici, pas du tout méfiants. Au contraire même. Je suis un héros pour tout le monde. Mon expérience de grand explorateur de l\'outre-monde fait de moi une sommité en ville !! Enfin on reconnaît ma valeur, ça fait du bien.</p>',
                    '<h1>Journal de bord 9ème jour</h1>
                <p>Faim !!! Faim !!! J\'en peux plus d\'avoir si faim. C\'est comme s\'il y avait quelque chose dans mon ventre qui grogne jour et nuit. Un trou sans fond que je n\'arrive jamais à combler. J\'ai beau avaler tout ce que je trouve, ça ne change rien.
                Et puis y\'a aussi tous ces cauchemars. J\'en peux plus.</p>',
                    '<h1>Journal de bord 10ème jour</h1>
                <p>Aujourd\'hui je suis parti dans le désert, une expé comme l\'appellent les jeunes de maintenant. J\'ai pris la décision d\'accompagner deux bleusailles pour leur montrer ce que je sais. Ils vont pas être déçus.</p>
                <p>(...)</p>
                <p>Je les ai tués tous les deux. C\'était plus fort que moi ! J\'ai éclaté leur tête à coups de pierre et après j\'ai bouffé leur cervelle encore chaude. Un régal. Pas vraiment prudent, mais il le fallait. Sur le bout de ma langue, il y a encore leur goût suave ... Je me sens enfin repu.</p>',
                    '<h1>Journal de bord 12ème jour</h1>
                <p>Voilà, je me suis fait avoir. J\'aurais pas dû la bouffer la petite vieille, la techno. Comment j\'aurais pu savoir qu\'elle allait gueuler ?!</p>
                <p>(...)</p>
                <p>Les voilà. Ils arrivent. Je boufferai le premier qui osera poser la main sur moi, même si elles sont crades. J\'ai trop faim pour faire la fine bouche. Si je pouvais je leur arracherai la gorge avec mes dents.
                J\'ai tellement la dalle...</p>
                <p>(...)</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "written",
                "chance" => "6",
            ],
            "alan" => [
                "title" => "Journal d’Alan Morlante",
                "author" => "lycanus",
                "content" => [
                    '<h1>Journal d’Alan Morlante</h1>
                <h2>16 avril</h2>
                <p>Ce fut avec joie que nous nous installâmes a Durain.</p>
                <p>Notre nouvelle maison, bien qu\'ancienne nous emplissait de bonheur moi et ma famille; Ida et les enfants allaient être heureux ici j\'en étais persuadé.</p>
                <h2>19 avril</h2>
                <p>Aujourd\'hui je suis rentré du travail et Ida m\'a dit qu\'elle voulait quitter la maison. Je n\'ai pas compris pourquoi...j\'ai essayé de la rassurer... mais c\'était en vain.</p>
                <p>Sans me fournir d\'explication elle m\'a dit qu\'elle partirait demain</p>',
                    '<h2>20 avril</h2>
                <p>Ida est partie avec nos enfants, elle m\'a laissé seul.</p>
                <p>En lui disant au revoir, je sentis une étrange impression, comme si je ne la reverrai plus.</p>
                <p>Je commençais à avoir peur de lui dire adieu...</p>
                <small>[ L\'encre est plus sombre ici, laissant penser que la suite a été écrite quelques heures plus tard. ]</small>
                <p>Je ne sais comment...mais...je suis coincé ici, la porte refuse de s\'ouvrir.</p>
                <p>Impossible de sortir de chez moi ! J\'ai forcé pendant des heures sur la poignée et rien n\'y fait...La porte est bloquée!!!</p>
                <p>Quelqu\'un ou quelque chose refuse de me laisser sortir.</p>',
                    '<h2>25 avril</h2>
                <p>Je n\'ai pas pu dormir cette nuit, j’ai essayé de sortir de la maison, mon esprit commence à flancher; l’idée d’être coincé comme un rat me rend fou...</p>
                <p>Je vois poindre la folie, terrifié dans mon canapé, je refuse de rester ici...</p>
                <p>JE DOIS SORTIR....ET....VITE!!!!!!!!!!!</p>',
                    '<div class="hr"></div>
                <p>15h00 passé, je me suis résigné, je vais mourir dans cette fichue maison</p>
                <p>Le sort s\'acharne contre moi, sera-ce la mort ou la folie qui m\'emportera la première...</p>',
                    '<div class="hr"></div>
                <small>[ Le paragraphe suivant est quasi illisible. ]</small>
                <p>Je n’a plus la force d\'écrire....................... je vis la mes dernier instan..... Elle m\'a eu....je ne pense plus désormais qu\'a Ida, elle avai raiso......</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
            ],
            "slept" => [
                "title" => "Journal d'un citoyen : Doriss",
                "author" => "Arma",
                "content" => [
                    '<p>Mal dormi... <s>J\'ai.</s>.. Comment bien dormir ? Les hurlements des non-morts... l\'agonie des proches... Toute la nuit ! À n\'en jamais finir ! Jamais !</p>
                <p>Les événements s\'enchaînent, nous ne sommes plus que <s>huit</s>sept... Hier, dix-huit. Aujourd\'hui sept !</p>
                <p>Il y avait beaucoup de sang... <s>Ce qui</s> Des morceaux de chair jonchent le sol...</p>
                <p>Pourquoi les Zombies partaient-ils chaque nuit ? Pourquoi ne les tuaient-ils pas tous ? Pour les faire souffrir encore plus ? Malgré leur apparence, les Morts-vivants semblaient lucides... Et cruels, abominablement cruels !</p>
                <p>J\'ai peur.</p>',
                    '<p>Arma est revenu, mon meilleur ami... I<s>ls ont</s> Il avait disparu. Dehors&nbsp;! Il est venu me rendre visite... J\'aurais voulu ne plus le voir, pas dans cet état...</p>
                <p>Puis il s\'est éloigné... Il reviendra cette nuit... Et je repartirai<s>s</s> avec lui... Pour toujours...</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "10",
            ],
            "log2" => [
                "title" => "Journal d'un citoyen inconnu 1",
                "author" => "Muahahah",
                "content" => [
                    '<h2>Jour 27, 06h02 à ma montre.</h2>
                <p>Nous étions encore en train de marcher dans ce désert quand la ville nous apparu. Quelques uns de nous, que la folie avait atteint depuis un certain temps, commencèrent à crier. Ils s\'arrachaient les cheveux, ne croyant pas ce qu\'il voyait, mais moi non plus je ne le croyais pas... Il fallait être sûr.</p>
                <p>En ne comptant pas Hector qui s\'était tiré notre unique balle dans la tête à la vu de la ville, nous étions 41 je crois.</p>
                <p>Les charognards se rapprochent déjà de nous, hardi par la faim et le cadavre frais. Nous partons, mais pas à cause d\'eux.</p>',
                    '<h2>Jour 27, 10h23.</h2>
                <p>La ville était déserte comme nous l\'avions tous prévu. Il semblerait qu\'<em>un trou empêche de lire la suite</em> avec eux.</p>
                <h2>Jour 27, 12h58.</h2>
                <p>Les choses nous ont suivit jusqu\'ici, nous nous sommes barricadé mais on les entends dehors, cognant parfois sur la frêle muraille qui était érigé là. Nous décidons tout de suite de la consolider.</p>',
                    '<h2>Jour 27, 14h18.</h2>
                <p>Des gens se proposent pour aller chercher de quoi renforcer la muraille car rien en ville ne le permet. Malgré des cris et des larmes dans la foule, ils partent. Une mère empêche son enfant pleurant de suivre son père...
                si ils sont ses parents.</p>
                <h2>Jour 27, 17h12.</h2>
                <p>L\'enfant s\'est enfin calmé, roulé en boule dans la terre depuis plus de deux heures, il sanglote au centre de la ville, à coté du puits. Sa "mère" s\'est désintéressée du gosse quelques minutes après le début de sa crise.</p>
                <p>Les cadavres des derniers occupants viennent d\'être arrosé. Nous attendons toujours le retour des autres. La radio que nous avons grésille trop
                pour les entendre parler. Il semblerait qu\'ils reviennent.</p>',
                    '<h2>Jour 27, 22h31.</h2>
                <p>Ils sont revenus, l\'un d\'eux est blessé. Mordu par une de ces choses ! La plaie ne saigne plus mais est très profonde, mélangé avec du sable.. .Nous n\'avons pas de pénicilline ou autre chose contre les infections. Notre médecin lui donne deux jours, tout au plus...</p>
                <h2>Jour 27, 23h49.</h2>
                <p>Ma lampe me permet d\'écrire. Nous sommes tous cloîtré chez nous. Sauf un que nous avons sortis dehors, il avait tenté de prendre nos réserves de planches pour sa barricader. Cela faisait 8 minutes que nous n\'entendions plus ses pardons, ils avaient été remplacé par des cris puis des gargouillis sanglant. Maintenant plus rien.</p>',
                    '<h2>Jour 28 ??</h2>
                <p>Je ne sais pas quelle heure il est, ma montre s\'est arrêtée à 00h12, je me suis réveillé. Des bruits se font entendre dans toute la ville, des gens hurlent. J\'ai peur. Oh mon dieu j\'ai peur. Un cauchemar<s>s</s>, il faudrait que ce soit un cauchemar. Ils sont en train de tuer des citoyens, et ils se rapprochent<s> de l</s>. Je ne veux pas qu\'ils me trouvent. J\'ai peur. Oh, putain oui, j\'ai peur de crever, <s>j\'ai</s>je veux <s>pas crever comm Il</s>s sont là je les entends, ILS SONT LA. Ils tournent autour de la maison. J\'ai</p>'
                ],
                "lang" => "fr",
                "background" => "noteup",
                "design" => "small",
                "chance" => "10",
            ],
            "log3" => [
                "title" => "Journal d'un citoyen inconnu 2",
                "author" => "ChrisCool",
                "content" => [
                    '<h1>Premier jour</h1>
                <p>J\'ouvre les yeux. Où suis-je ? Je ne me souviens de rien... Autour de moi, d\'autres personnes, l\'air tout aussi hagardes, errent. J\'ai l\'impression d\'être dans un petit village entouré de planches vermoulues. J\'entends des grognements étranges au loin. Un vent chaud chargé de sable me brûle le visage. Que faire ? Déjà, d\'autres personnes s\'activent pour rajouter des planches autour de la ville, mais pourquoi ...</p>
                <h1>Deuxième jour</h1>
                <p>J\'ai peur. Terriblement peur.</p>
                <p>Cette nuit, des monstres affreux sont venus s\'étaler devant la grande porte. Ils ressemblaient à des humains, mais leurs membres partaient en lambeaux, une odeur de charogne à vomir accompagnait leur marche... Aujourd\'hui j\'ai tout mis en oeuvre pour renforcer notre ville par tous les moyens. Je ne pourrais supporter longtemps ces... êtres, ou plutôt, choses...</p>',
                    '<h1>Troisième jour</h1>
                <p>Toujours en ...survie...</p>
                <p>Certains souffrent  de la soif, d\'autres encore ont choppé de vilaines infections. Je ne suis pas médecin, mais je devine déjà les souffrances qu\'ils vont endurer...</p>
                <p>Nous sommes de moins en moins nombreux. A l\'intérieur de nos murs, du moins...</p>
                <p>Nos anciens compagnons viennent grossir leurs rangs dès le trépas. J\'ai peur de mes voisins maintenant...</p>',
                    '<h1>Quatrième jour</h1>
                <p>I.I.Ils sont revenus cette nuit... Ils ont tenté de me d.d.d.dévorer vivant, c\'était terrifiant. J\'ai fracassé le crâne de l\'un d.d.d.d\'entre eux HAAAAvec une vieille lampe de chevet qui traînait dans un coin-coin. Je dois tenir, à tous prix...</p>
                <p>Je rédige ces l.l.lignes ce soir, je les entends déjà rôder, qui sait si <s>ils</s></p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "small",
                "chance" => "1",
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
                "design" => "written",
                "chance" => "9",
            ],
            "coctl2" => [
                "title" => "Journal de Coctail, partie 2",
                "author" => "coctail",
                "content" => [
                    '<p>J’ouvris douloureusement les yeux.</p>
                <p>Pantocrat me passait un linge humide sur le front. J’étais à l’ombre. Je fermais encore une fois les yeux pour profiter de cette fraicheur.</p>
                <p>Auparavant, je n’aurais jamais apprécié le simple fait d’être allongé à l’ombre à la lumière du crépus...</p>
                <p>Je fis un bond. Le soleil !</p>
                <p>Le soleil se couchait !</p>',
                    '<p>Ils allaient arriver ! Il fallait se cacher !</p>
                <blockquote>
                <p>- « Il est réveillé », cria Pantocrat derrière lui.</p>
                <p>- « Tant mieux, alors, qu’il m’aide à pousser ce réfrigérateur devant cette fenêtre ! », répondit la douce voix de Zoby.</p>
                </blockquote>
                <p>Nous finîmes de barricader le rez-de-chaussée avec les quelques meubles encore solides que nous trouvions.</p>
                <p>Nous ne nous arrêtions pas avant que la nuit soit complète. Pantocrat et Zoby me racontèrent qu’ils m’avaient vu courir vers les zombies et les découper un après un. Après quoi je m’étais évanoui. Ils m’avaient traîné jusqu’ici.</p>',
                    '<p>Ils avaient même trouvé de l’eau. Lorsque ça s’était produit, quelqu’un prenait probablement un bain. Heureusement qu’il avait eu la bonne idée de sortir avant de mourir. Nous avions retrouvé son squelette à côté du lavabo vide. Evidemment, les robinets ne donnaient plus d’eau et il n’y avait encore moins d’électricité.</p>
                <p>Etant donné mon état, mes amis me laissèrent les restes du canapé pour dormir. Pantocrat prendrait la garde cette nuit, son tromblon à la main.</p>
                <p>Je ne me réveillais que secoué par Zoby. Le soleil était bien levé. Zoby alla ensuite donner un coup de pied dans la silhouette endormie de Pantocrat qui montait fidèlement la garde...</p>
                <p>Epuisés, nous n’avions rien entendu cette nuit et à en croire les traces d’impacts de pierres et de griffures sur les murs, les zombies avaient pourtant dû être particulièrement acharnés...</p>',
                    '<p>Nous sortions prudemment. Il semblerait que la horde se soir momentanément repliée.</p>
                <p>Ayant passé notre soirée à nous barricader, nous n’avions pas exploré les carcasses de véhicules à côté.</p>
                <p>Ils étaient tous rouillés et étaient dans un état lamentable. Tout semblait se dégrader trop vite ces derniers temps, tout...</p>
                <p>Nous essayions tous les véhicules sans espoir lorsque j’entendis un bruit de moteur. Je courus alors de l’autre côté de la décharge où Zoby venait de réussir à démarrer un moteur. Et quel moteur ! Il venait d’allumer un half-track ! Un de ces véhicules tout terrain de l’armée, propulsé par des chenilles à l’arrière. Il coupa le moteur et redescendit.</p>
                <p>Son sourire faisait presque le tour de sa tête. En tout cas jusqu’au moment où la fumée blanche se mit à sortir du capot du véhicule.</p>',
                    '<p>Pantocrat revint vers nous en courant. Il venait de trouver un bâtiment effondré, recouvert par la terre.</p>
                <p>Nous nous répartîmes alors le travail. Je m’occuperais de déterrer avec ma râpe pendant que Pantocrat récupèrerait des plaques de tôle sur les véhicules pour renforcer les barricades. Zoby, quand à lui avait aperçu une caisse à outils et s’occuperait de voir ce qu’il pourrait tirer du half-track.</p>
                <p>Le travail fut dur ce jour-là mais heureusement, Pantocrat vint m’apporter une pelle après quelques heures. J’en pleurais presque d’émotion car mes mains étaient salement écorchées.</p>
                <p>Le soir, nous nous enfermions dans la bâtisse qui ressemblait à un tas de ferraille. Pantocrat avait récupéré tous les capots pour les accrocher aux fenêtres du rez-de-chaussée. Il avait même poussé une camionnette devant une des portes pour empêcher els zombies de passer.</p>',
                    '<p>Zoby de son côté avait réussi à relancer le moteur du half-track et avait siphonné tous autres les réservoirs pour en drainer le carburant. Il avait trouvé des jerricans et les avait remplies.</p>
                <p>Nous montions à l’étage pour décider si nous repartions tout de suite ou si nous renforcions le half-track. Les regards se tournèrent alors vers moi, qui n’avais encore dit mot.</p>
                <p>Je leur fis alors un grand sourire et je sortis, enveloppé dans un chiffon graisseux, une bouteille d’alcool trouvée dans les gravats.</p>
                <p>Nous prîmes dix gouttes chacun, conscients de la rareté de cette liqueur...</p>
                <p>Je décidais de prendre le premier quart.</p>
                <p>Pantocrat et Zoby se mirent à ronfler de concert après quelques minutes.</p>',
                    '<p>N’ayant rien de mieux à faire, je montais à l’étage pour regarder la lune par une des fenêtres cassées. Les zombies n’étaient pas assez adroits pour grimper un mur, inutile donc de barricader les étages.</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "8",
            ],
            "coctl3" => [
                "title" => "Journal de Coctail, partie 3",
                "author" => "coctail",
                "content" => [
                    '<p>Je regardais distraitement l’antenne de la camionnette placée en dessous lorsque je les entendis.</p>
                <p>Ils poussaient des cris gutturaux et s’avançaient dans le désert.</p>
                <p>La horde arrivait.</p>
                <p>Et avec elle, cette odeur de chairs sous le soleil. J’avais vomi les premières fois que les avais senti mais je m’étais habitué. L’organisme peut s’habituer à beaucoup de choses... Même aux pires... Même à la horde...</p>
                <p>Je les entendis taper sur les capots rivetés contre les murs. Bonne idée qu’avait eue Pantocrat. Bonne protection que les capots. Bonne protection, mais pas très silencieux. Je tiendrai éveillé toute la nuit avec un boucan comme ça.</p>',
                    '<p>Je tins d’ailleurs toute la nuit. Heureusement car peu avant le lever du soleil, je vis apparaître une tête devant la fenêtre de l’étage.</p>
                <p>Une tête sans mâchoire. J’hurlais pour prévenir les autres et je sortis ma râpe.</p>
                <p>La tête vola au-dehors mais le reste du corps du zombi continua quand même vers moi, faisant des moulinets avec ses griffes vers moi.</p>
                <p>J’en vins à bout lorsque les deux autres arrivèrent derrière moi.</p>
                <p>Zoby poussa un cri et désigna la fenêtre.</p>
                <p>Accaparé par mon combat, je n’avais pas vu que quatre autres zombies étaient rentrés et d’autres arrivaient encore.</p>',
                    '<p>Nous nous élancions comme un seul homme vers la porte et pendant que Pantocrat et Zoby s’arc-boutèrent pour la maintenir fermée, je courrais dans tous les sens pour chercher de quoi la coincer. Je finis par trouver le canapé et avec l’énergie du désespoir, je réussis même à le trainer jusqu’en haut.</p>
                <p>Il était temps car le bois de la porte commençait à partir en miettes. Zoby m’aida à le placer debout entre la porte le mur opposé. Le canapé était coincé et bloquait tout le passage mais au moins, les zombies ne pouvaient pas passer.</p>
                <p>Leur assaut dura jusqu’à midi.</p>
                <p>Nous sortions prudemment et Zoby et moi-même jetions un regard noir à Pantocrat lorsque nous découvrîmes que les zombies avaient pu entrer grâce à la camionnette qu’il avait placée devant la porte...</p>',
                    '<p>Pantocrat monta sur la camionnette à l’extérieur de la bâtisse et redescendit aussitôt. Il était tout blême. Sous ses coups de soleil et son visage qui pelait, ce n’était pas évident à voir, mais ses bégaiements nous apprirent qu’il restait une douzaine de zombies dans la pièce à l’arrière de nos barricades. Ces mêmes zombies qui avaient grimpé à l’étage la nuit précédente, n’étaient pas repartis avec le reste de la horde et occupaient maintenant une partie de notre abri.</p>
                <p>Sans un mot, j’allais chercher la bouteille d’alcool, en pris une gorgée entière pour me donner courage, pris ensuite le briquet dans la poche de Pantocrat et montais sur la camionnette.</p>',
                    '<p>Le hurlement des zombies en train de bruler dura encore quelques heures.</p>
                <p>Le soir, Pantocrat avait transformé la bâtisse en forteresse et poussé la camionnette. Zoby avait construit une cabine en tôle à l’arrière du half-track et j’avais fini de dégager le bâtiment. J’avais tiré plusieurs caisses grâce à des roulettes trouvées dans notre maison pour les ouvrir durant la nuit.</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "8",
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
                <blockquote>- « ‘manque plus que des munitions... »</blockquote>
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
                "design" => "written",
                "chance" => "8",
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
                <p class="other">6 septembre : ci-gît Pi<s>erre</s> Na<s>cimo</s> Ta<s>varez</s>., le noeud qui l\'a pendu a été plus coulant que lui. </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "typed",
                "chance" => "8",
            ],
            "intime" => [
                "title" => "Journal intime",
                "author" => "Homerzombi",
                "content" => [
                    '>
                <h1>Journal de la petite Tiphanie, onze ans.</h1>
                <p>6 février 01.</p>
                <p>Cher journal, j’ai peur, mes deux parents sont morts hier dans l’expédition, ma mère dévorée par la goule, mon père, à côté de moi,
                le cadavre palpite encore dans la marre de sang causée par mes coups incessants, j’ai cassé le couteau suisse de papy à cause de lui...
                Ce qui me fait peur, c’est que c’est la seule chose qui me donne des remords…
                </p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "10",
            ],
            "thief" => [
                "title" => "Jugement",
                "author" => "stravingo",
                "content" => [
                    '<small>Vous remarquez que ce document a été écrit au dos d\'une étiquette de bouteille.</small>
                <p>La chance a tourné, ces ordures m\'ont enfermé. Depuis la petite lucarne, je les vois dresser la potence. J\'avais pourtant réussi à passer inaperçu jusqu\'à ce matin. Jusqu\'au vol de trop.</p>
                <p>Ils avaient pourtant cherché pendant des jours l\'auteur de tous ces vols, s\'accusant mutuellement. Il est vrai que par un petit mot, par une allusion, par un mensonge, j\'avais réussi à bien semer la zizanie. Je crois avoir déposé une plainte anonyme à chacun d\'entre eux. Il fallait voir leurs têtes !</p>
                <p>Mais ce matin, j\'ai manqué de prudence. Comment aurais-je pu savoir que cet idiot allait revenir plus tôt que prévu de son expédition ? Ah, ils ont bien été contents de m\'attraper. J\'ai même eu droit à des insultes que je ne connaissais pas.</p>',
                    '<p>Mes dents cassées me font mal, mais je sais que je n\'aurai plus à souffrir très longtemps.</p>
                <p>Ma seule consolation est qu\'ils ont mis leurs dernières forces dans la construction de cette foutue potence. Leurs dernières planches aussi. Ils ne seront pas bien longs à me survivre.</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "written",
                "chance" => "10",
            ],
            "kraky" => [
                "title" => "Kraky, sa vie, son oeuvre",
                "author" => "Krakynou",
                "content" => [
                    '>
                <h1>Kraky, La vie d\'un Prophète</h1>
                <p>Ils me prennent pour un fou !<br>
                Ils me traitent au même titre que les zombis, excepté que je peux rentrer en ville.<br>
                Tout cela à cause de ma religion. Ils ne croient pas au pouvoir du grand Poulpe.<br>
                Au loin ça parle de construire un bûcher. S’ils savaient… Aujourd’hui dans le désert j’ai trouvé les restes d’un compagnon.<br>
                Nul doute qu’en croquer un morceau m’aidera à me contaminer.<br>
                J’ai voulu les purifier par mes paroles en prêchant celle de Poulpe, cela n’a pas suffi.<br>
                Il me faut désormais purifier par les actes. Ils mourront.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "cenhyd" => [
                "title" => "La centrale hydraulique",
                "author" => "coctail",
                "content" => [
                    '<p style="font-weight:normal;">Jacques est parti il y a cinq jours pour chercher un filtre à eau. Il n\'est jamais revenu. Puis ce fut le tour de Tomy deux jours plus tard. Du mirador, nous l\'avons vu se faire dévorer. Après, ce fut à Jessy. Elle était déjà blessée et n\'a pas dû aller bien loin. Maintenant, il ne reste plus que nous deux pour aller fouiller la centrale hydraulique. « Tu devrais vraiment y aller Larry. Tu devrais vraiment y aller. N\'oublie pas que c\'est moi qui ai toujours le fusil »</p>'
                ],
                "lang" => "fr",
                "background" => "money",
                "design" => "small",
                "chance" => "4",
            ],
            "cigs1" => [
                "title" => "La clope du condamné",
                "author" => "Amnesia",
                "content" => [
                    '<p>Et merde... La prochaine fois que je <s>parti</s>ferais gaffe avant de me précipiter dans un terrain découvert où trainent deux pauvres cactus et une bonne quinzaine de putrides, la bave aux lèvres en sachant pertinemment qu\'ils vont peut être pouvoir se tailler un bon tartare avec quelques morceaux d\'os, des bouts de cigarettes et des lambeaux de tissus, le tout accompagné de sa sauce sanglante aux poussières du désert... Appétissant n\'est pas ? </p>',
                    '<p>Maintenant j\'ai plus qu\'à attendre que quelqu\'un vienne ou que la nuit tombe, en me fumant les dernières clopes de ce paquet sur lequel je suis en train d\'écrire des inepties pour passer le temps, c\'est assez désolant, surtout que j\'ai presque plus d\'allumettes. Au pire je pourrais leur demander si ils en veulent pas une, aux râleurs là bas, peut être que c\'étaient des rastas avant de clamser...</p>
                <small>Le reste du message est assez illisible à cause de la poussière, mais au vu du pavé de petites lettres qui entoure le reste du paquet, vous vous doutez que l\'auteur n\'avait rien d\'autre à faire pendant un long moment... Nul ne sait qui il est, ni s\'il a réussi à rentrer en ville ou s\'il est mort ici, mais comme il reste encore une cigarette dans le paquet, autant en profiter.</small>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "small",
                "chance" => "6",
            ],
            "logsur" => [
                "title" => "La colline aux survivants",
                "author" => "Darkhan27",
                "content" => [
                    '<p>Nul ne savait d\'où venaient les survivants et cela leur importaient peu. Tous ce qui comptaient c\'était de survivre dans le désert au milieu de la horde.</p>
                <strong>Jour 1</strong>
                <p>Les survivants s\'étaient concertés, ils travaillaient ensemble, s\'entendaient bien, et rapidement sur la colline désertique s\'éleva un petit fortin protégé où tous purent passer la nuit en toute sécurité.</p>
                <p>Les râles des zombies, cette nuit là, fut la seule crainte qu\'ils subirent.</p>',
                    '<strong>Jour 2</strong>
                <p>Il était important de trouver des ressources en plus grande quantité. Des expéditions furent menées. L\'eau commença à être rationnée car c\'était une richesse malheureusement pas inépuisable.</p>
                <p>Quand les explorateurs revinrent, le fortin était devenu plus grand, plus accueillant, tandis que toujours plus de survivants arrivaient du désert. Cela diminuait d\'autant plus les ressources en eau.</p>
                <p>Le soir on déplora la perte de quatre explorateurs qui ne retrouvèrent pas le chemin du retour.</p>
                <p>Les zombies furent plus nombreux à la porte et peu de survivants trouvèrent le sommeil cette nuit là.</p>',
                    '<strong>Jour 3</strong>
                <p>Les restrictions en eau furent la cause des premières tensions : les plus courageux qui partaient dans le désert reprochaient aux nombreux ouvriers leurs inactivités dans la ville. Mais nul ignorait le véritable problème : les principales ressources disparaissaient. La ville ne se développait plus. Trop de survivants, pas assez de quoi survivre.</p>
                <p>Les quelques expéditions qui partirent revinrent avec peu de matières premières. Nul n\'eut de nouvelles des explorateurs courageux qui étaient dans des zones inconnues.</p>
                <p>Les premières disputes éclatèrent à la tombée de la nuit et seul l\'intervention de quelques survivants empêcha le pire.</p>',
                    '<p>La nuit fut des plus horribles : les zombies de plus en plus nombreux passèrent un certain nombre des défenses et entrèrent en ville. Les plus faibles, les moins protégés furent dévorés vivants ou emportés dans le désert.</p>
                <p>Cette nuit, plus de 12 survivants furent déclarés perdus.</p>',
                    '<strong>Jour 4</strong>
                <p>Les survivants, pour la première fois moins nombreux que le jour de leur arrivée concentrèrent leurs efforts pour développer les défenses de la ville malmenées par la horde la nuit précédente.</p>
                <p>Une seule expédition partit dans le désert. Elle ne revint que peu de temps après la tombée de la nuit avec peu de choses. Les survivants savaient que la nuit allait être longue, et elle le fut.</p>
                <p>Les zombies entrèrent en force. Toujours plus nombreux, ils pénétrèrent dans la ville arrachant de leurs tentes ceux qui ne s\'étaient pas assez protégés. Seuls survécurent les mieux protégés.</p>
                <p>Plus de la moitié des survivants disparurent dans les profondeurs de la nuit.</p>',
                    '<strong>Jour 5</strong>
                <p>La ville vivait déjà ces derniers jours. Chacun cherchait à se protéger du mieux possible. D\'autres restaient cloîtrés contre un mur en pleurant ou les yeux dans le vague.</p>
                <p>Certains sortirent, parfois même seul,dans le désert malgré le danger que représentait le nombre croissant de zombies.</p>
                <p>En vain. Ceux qui revinrent n\'avaient trouvé que des ressources inutiles pour renforcer les défenses de la ville.</p>
                <p>Les disputes reprirent de plus belle entraînant de violentes bagarres, malgré l\'intervention de survivants.</p>',
                    '<p>Alors que la nuit tombait, certains prirent même la décision de protéger leur propre habitation en s\'emparant de vieux matelas et autres portes qui renforçaient la porte de la ville. A leurs yeux que leur importaient la vie de leurs voisins quand la leur était en danger.</p>
                <p>Alors quand minuit sonna, et que la horde déferla, la mort faucha tous ceux qu\'elle rencontra. Des hurlements se faisaient entendre quand les victimes étaient arrachées de leur cachette par des zombies vociférants, dont certains étaient parfois leur voisin de la veille !</p>
                <p>Ne survécurent que ceux qui avait pillé les défenses de la ville. Celle-ci n\'existait plus en tant que telle.</p>',
                    '<strong>Jour 6</strong>
                <p>La ville n\'était plus. Seuls demeuraient une poignée de survivants cloîtrés dans leur fragile habitation. On se toisait, on s\'observait.</p>
                <p>Il n\'existait plus d\'ordre. On se battait, on se volait, on se méprisait. Tout ce qui importait c\'était de survivre à la nuit prochaine. Si l\'un des survivants quittait son taudis, tout était pillé à son retour.</p>
                <p>La faim. La soif. C\'étaient les principales préoccupations de la journée. Le soleil tapait fort sur la tête des survivants fatigués. L\'un d\'entre eux fut même pendu car il avait osé mangé seul, sans partager, un rat mort depuis plusieurs jours qu\'il avait trouvé.</p>',
                    '<p>Mais combien d\'autres calmèrent leur faim avec une nourriture encore plus douteuse?</p>
                <p>Les plus désespérés partirent dans le désert pour y trouver, ils l\'espéraient, une mort rapide.</p>
                <p>Quand la nuit tomba, certains l\'accueillirent comme une délivrance.</p>
                <p>La horde pénétra comme jamais elle ne l\'avait fait, à peine ralentie par les dernières défenses. Le nombre de zombies était ... effroyable. La nuit ne fut que plaintes et hurlements.</p>
                <p>La ville disparut en une multitude de cris de douleur.</p>',
                    '<strong>Jour 7</strong>
                <p>Aujourd\'hui le soleil s\'est levé sur un tas de ruine. Des mouches bourdonnent sur les corps à moitiés dévorés des survivants. Cela même qui se lèveront la nuit prochaine à la recherche de viande fraîche.</p>
                <p>Je sors de ma cachette encore tremblant . Je suis seul. Il ne reste plus rien autour de moi. Je ne passerai pas la prochaine nuit. Et partir dans le désert m\'effraie encore plus.</p>
                <p>Je décide de laisser une trace pour rappeler que j\'ai existé, que cette ville a existé.</p>
                <p>Le destin n\'a pas décidé qu\'elle reste. Qu\'en serait-il exactement si nous avions, si nous étions resté soudés au lieu de nous battre...?</p>',
                    '<p>J\'en saurai jamais rien.</p>
                <p>J\'ai si faim, et si soif. Il faut que je renforce ma cachette, peut être que quelqu\'un va venir.</p>',
                    '<strong>Jour 8</strong>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "typed",
                "chance" => "7",
            ],
            "survi2" => [
                "title" => "La drogue c'est tabou, on en viendra tous à bout !",
                "author" => "Mhyeah",
                "content" => [
                    '>
				<p>Longtemps après l\'apocalypse, le peu de scientifiques survivants se sont posés la questions suivante:
				les drogues nous aideront-t-elles à survivre face aux zombies?
				C\'est afin d\'y répondre qu\'un scientifique, moktuss, fit construire une ville qui acceuila une moitié de "junkies" et une moitié de "cleans".
				Ce papier est peut-être le dernier résidus de cette expérience désastreuse...
				Les drogues sont pires que les zombies. Les camés d\'abord ratissèrent le désert, a la recherche de cette substance, puis y consacrèrent le reste de leur courte vie.
				Rapidement, ils moururent de leur dépendance, leurs orifices affamés de substances chimiques restés béants.',
                    'Les survivants espérèrent que leur cadavres bleuâtres empoisonneraient les zombies, mais évidemment, ils n\'eurent aucun effet.
				Et alors commença l\'exode qui nous conduira tous à une mort au milieu des étendues de sable.
				Nous retournerons à la poussière... Alors que j\'écrit ces lignes, moi, mhyeah, clean, regarde une drogue.
				Le "top du top" à ce qu\'on dit. La seule qui n\'entraîne aucune addiction. Et qui, pourtant, guérit de tous les maux. Son nom? c-a--re, certaines lettres sont invisibles...
				<p></p>
				<p>
				"CARE"?
				Heureusement, j\'ai appris à ne pas me fier aux apparences, mais ma raison pourra-t-elle dominer mes impulsions?
				...peut-être...
				</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "2",
            ],
            "marche_fr" => [
                "title" => "La marche",
                "author" => "Layoreth",
                "content" => [
                    '>
                <h1>La Marche</h1>
                <p>Sombre mélodie, les pas de ces créatures putréfiées étaient orchestrés de façon à terroriser la populace qui trouvait dans l\'alcool le seul réconfort véritable.
                Des rugissements, des grognements d\'animaux résonnaient dans la nuit noire. Seule fausse note de cette terrible partition, nous découvrîmes rapidement que ces abominations étaient mortelles.
                Lorsque la lune disparut derrière un nuage fatidique, les zombies marchaient sur les premières fortifications.
                Nous promîmes tour à tour que les couvertures de nos progénitures ne deviendraient pas leur linceul. Et enfin, nous allâmes offrir nos vies à l\'enfer...
                L\'aube approche, et les rayons seront vermeils.
                </p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "classic",
                "chance" => "2",
            ],
            "hang" => [
                "title" => "La potence",
                "author" => "Liior",
                "content" => [
                    '<p>Le chef a voulu, alors nous avons exécuté.. Le jeune homme n\'avait pas l\'air méchant, mais désemparé, déçu et perdu à la fois.. Il ne comprenait pas notre geste et nous non plus en réalité.. la réalité.. <em>"Un seul écart, et elle s\'occuperait de nous"</em>, comme le chef aimait si bien à le dire.</p>
                <p>Malgré nos jérémiades, le chef est resté inflexible, suivi par quelques citoyens du village, ceux-la même qui avaient réalisé cette construction barbare à mes yeux, uniquement pour l\'occasion.</p>
                <p>La trappe s\'est ouverte, la corde s\'est tendue. Le jeune homme a succombé. Plus rien ne sera pareil maintenant, car la mort n\'est plus donné uniquement par les zombies.. </p>
                <p>Méfiez-vous de vos voisins...</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "written",
                "chance" => "5",
            ],
            "chief" => [
                "title" => "La trahison",
                "author" => "Liior",
                "content" => [
                    '<p>Il a volé nos armes, nos dernières tranches de viande, et il est parti.. Il nous a laissé seuls, pour survivre par lui-même dans le désert.</p>
                <p>Nous sommes désemparés et nous ne savons pas comment faire pour empêcher les hordes de tous nous supprimer dès ce soir.. Dans la taverne de fortune installée dans le village il y a 4 jours, certains s\'imaginent que l\'horreur sera plus supportable avec un coup dans le nez..</p>
                <p>Il semble que nous ne soyons plus du tout en sécurité.. Il nous a pris nos armes... Notre nourriture, et il est parti.. Sans prévenir.. Nous l\'avons vu s\'éloigner dans le désert, mais nous ne comprenons pas.. Est-ce un abandon ? Peut-être pense-t-il pouvoir se débarrasser de quelques créatures et revenir ce soir ?</p>',
                    '<p>Cela ne servirait à rien.. Il est parti, en nous laissant un gout de trahison... Notre "chef".</p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "10",
            ],
            "docte" => [
                "title" => "La vie de Docteurhache",
                "author" => "Docteurhache",
                "content" => [
                    '>
                <h2>Extrait du journal de Docteurhache</h2>
                <p>La ville semble bien calme à l\'aube d\'une attaque dévastatrice.
                Les citoyens gardent peu d\'espoir dans un lendemain. Les maisons sont construites, le King résonne dans les venelles et Chuck trône fièrement au sommet de la muraille.
                Seul l’ultime trophée, cet assemblage d’or qui fait tant rêver, manque pour parfaire le décor. Tout se joue maintenant, car demain nombreux seront les macchabées.
                Chacun veut être à l\'honneur pour l\'attaque létale et devenir l’incontestable favori des zombies.
                Tous attendent, parés de leurs plus beaux atours, sous les feux de bougies donnant à l\'atmosphère une enivrante quiétude à l\'aube de la mort.
                </p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
            ],
            "canula_fr" => [
                "title" => "Le Bal de Charité",
                "author" => "Shyranui",
                "content" => [
                    '<h1>Mes chers Concitoyens, Concitoyennes,</h1>
                <p>Récemment une vague de canulars a frappé notre bonne vieille ville : des petits plaisantins peinturlurés avec du sang se sont amusés à attaquer les passants.
                Rassurez vous il ne s’agissait là que d’une blague de mauvais goût, les fauteurs de troubles ont tous été amené à l’Hôpital psychiatrique afin de s’y faire soigner.</p>
                <p>Le Bal de Charité est donc maintenu à ce soir 19h30 au grand Hôtel de la Place.
                Venez nombreux, la fin du monde n’est pas pour maintenant et ce n’est pas demain la veille que notre Bunker flambant neuf servira.</p>
                <p></p>
                <p>Mr le Maire.</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "typed",
                "chance" => "5",
            ],
            "cidcor" => [
                "title" => "Le CID de Pierre Corbeau",
                "author" => "bartock",
                "content" => [
                    '>
                <h1>Le CID de Pierre Corbeau</h1>
                <p>Vers nous donc cette troupe s\'avance,<br>
                Et grogne sur un ton d’un râle d\'abondance.<br>
                Nous partîmes quarante ; mais par un prompt débord<br>
                Nous ne sommes plus que dix en arrivant au fort,<br>
                Tant, à nous voir marcher avec un tel visage,<br>
                Les plus intrépides perdaient de leur courage !<br>
                Je cache les cadavres, aussitôt qu\'arrivés,<br>,
                </p>',
                    'Dans le fond des tombeaux qui lors furent creusés ;<br>
                Le reste, dont le nombre baissait heure après heure,<br>
                Pleurant d’épouvante, autour de moi demeure,<br>
                Se couche contre terre, et sans faire aucun bruit<br>
                S’apitoie sur lui-même tout au long de la nuit.<br>
                Par mes lamentations je fais aussi de même,<br>
                Et me tenant caché, le visage vraiment blême ;<br>
                Et je feins hardiment d\'avoir reçu des coups<br>
                Pour me cacher derrière, ce à l’insu de tous.<br>',
                    'Cette obscure clarté qui tombe des étoiles<br>
                Enfin minuit sonnant leurs visages nous dévoilent ;<br>
                Des furoncles partout, et puant la malemort<br>
                Les zombies tels une mer montent jusque au fort.<br>
                On les laisse passer ; tout leur paraît tranquille ;<br>
                Point de gardiens aux tours, point aux murs de la ville.<br>
                Notre profond silence abuse-t-il leurs esprits ?<br>
                Nous croyons par notre ruse les avoir bien mépris :<br>
                Ils abordent sans peur, ils défoncent, ils pourfendent,<br>
                Et courent pour bâfrer les corps qui les attendent.<br>',
                    'Nous nous levons alors, et tous en même temps<br>
                Poussons jusques au ciel mille cris gémissants.<br>
                Les zombies, à ces cris, de la rue nous répondent ;<br>
                Ils paraissent affamés, et nous, âmes moribondes,<br>
                L\'épouvante nous prend nous voilà éperdus ;<br>
                Avant que de combattre nous nous savons perdus.<br>
                Ils couraient au diner, et arrivent au dessert ;<br>
                Nous courons, nous fuyons, certains vers le désert ,<br>
                Et voyons courir des ruisseaux de notre sang,<br>
                Car nul homme ne résiste aux zombies tout puissants.<br>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "poem",
                "chance" => "2",
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
                "design" => "small",
                "chance" => "7",
            ],
            "mixer" => [
                "title" => "Le batteur électrique",
                "author" => "Esuna114",
                "content" => [
                    '<p>Mon ami, Left, était fou, complètement fou à lier.</p>
                <p>Non... Ce n\'est pas vraiment exact, de toute façon, nous sommes tous  plus ou moins atteints par ces vagues journalières de zombies, et nous avons tous nos déficiences mentales, je suppose... La sienne était peut être plus...Flagrante.</p>
                <p>Il s\'était enamouraché d\'un appareil électrique !  Une nouvelle technologie, qui, il est vrai, m\'a en tout bonne foi impressionné. Mais de là à l\'emporter partout avec lui, et surtout... Faire ce qu\'il en fait.... On dirait en tout cas qu\'il s\'amuse beaucoup, et je ne peux le nier cet appareil nous est très utile pour nous protéger.</p>
                <p>Nous l\'avons surnommé "le batteur électrique". </p>',
                    '<p>Cela fera bien l\'affaire pour les quelques jours qu\'il nous reste à vivre. La principale chose que mon collègue s\'amuse à battre, ce sont les yeux de zombies. Même si ils bougent encore, ils ne peuvent plus nous voir...  Parfois, cela suffisait pour qu\'ils nous laissent tranquille. Souvent, il fallait insister un peu, disons... Appuyer le mouvement...</p>
                <p>...Mais pourquoi, ô pourquoi, le moment ou nous en avions le plus besoin, cette petite bestiole mécanique s\'est elle arrêtée? Left  m\'a dit qu\'il avait fait quelque chose pour la mettre en marche... Mais la panique lui à fait perdre un peu ses esprits, il ne se souvient plus de ce qu\'il a fait. C\'était notre dernière chance, il y a trop de zombies ici, et mes concitoyens sont bien désintéressés de notre sort... Si seulement il pouvait se rappeler...</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "small",
                "chance" => "5",
            ],
            "chaos" => [
                "title" => "Le chaos",
                "author" => "Liior",
                "content" => [
                    '<p>Le 10 août à 13h:</p>
                <p>Nous ne sommes plus que 7 dans le village.. L\'ambiance se fait lourde, et les gens se regardent bizarrement.. Je ne suis sur que d\'une seule personne, mon ami fidèle.. Mais les 5 autres se sont groupés, ils ont pillé la banque du village et pris tout ce qui était nécessaire à l\'établissement des défenses... Comment cela se peut-il, qu\'après tant de souffrance ensemble, ils n\'aient plus aucun scrupule à se défendre personnellement? Je le sais maintenant, ils avaient prévu cet évènement.</p>',
                    '<p>14h20 :</p>
                <p>Il était mal en point, le sang dégoulinait sur lui comme une cascade issue d\'une grotte ouverte dans sa tête. Au début nous ne comprenions pas, s\'était-il blessé en se cognant ? En tombant au sol sur un objet coupant ? Puis nous comprimes que nous n\'étions plus en sécurité, même dans les remparts de la cité... Il avait été victime d\'une violente agression. Le chaos règne maintenant parmi nous, mais je suis tout de même content... J\'étais jaloux, je ne voulais pas que les bâtisseurs soient mieux lotis que nous.</p>
                <p>Après tout, chacun a le droit d\'être défendu.</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "written",
                "chance" => "10",
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
                "design" => "typed",
                "chance" => "10",
            ],
            "rodeo_fr" => [
                "title" => "Le rodéo du chaos",
                "author" => "Cortez",
                "content" => [
                    '>
                <p>
                Si je suis là c\'est uniquement par un mauvais concourt de circonstances. <br>
                Au départ tout ce que je voulais c\'était nous divertir un peu. <br>
                Et quoi de plus drôle qu\'un rodéo sauvage sur un cochon paniqué ? <br>
                </p>
                <p>
                Sauf que le-dit cochon, après s\'être échappé, est allé tout droit vers le site de construction du grogro mur. <br>
                Il a tapé dans les fondations et le mur s\'est écroulé sur la tour de guet qui elle-même s\'est effondrée sur l\'atelier.<br> 
                Bon ça aurait pu s\'arrêter là mais manque de chance le feux de joie se trouvait à proximité et une buche s\'est retrouvé projetée dans la banque, ce qui brûlé la moitié de nos ressources. <br>
                Enfin quand même, est-ce que ça justifie vraiment la cage à viande ?<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "8",
            ],
            "others_fr" => [
                "title" => "Les autres",
                "author" => "Fokscaper",
                "content" => [
                    '>
                <h1>Lettre</h1>
                <p>Ils arrivent... titubants, bruyants, dangereux.<br>
                Ils viennent vers la ville tels des damnés... ce qu\'ils sont. Mais qui ne l\'est pas de nos jours ? <br>
                J\'ai peur de ce qui pourrait m\'arriver s\'ils entrent, ce qu\'ils pourraient me faire, à moi, aux autres, mais surtout à moi.<br>
                Ils détruiront les ressources de la ville, assècheront le puits de quelque manière que ce soit.
                </p>
                <p>
                Nous avons bien tenté de résister, mais chaque jour ils se révèlent, plus nombreux, plus forts, plus destructeurs.<br>
                Ce sont eux qui vont nous faire vivre un véritable enfer.
                </p>
                <p>
                Finalement, ils sont pires que les zombies.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "noel" => [
                "title" => "Lettre au Père Noël",
                "author" => "zhack",
                "content" => [
                    '<p>Bonjour papa noël,</p>
			   <p>Cette année j\'ai été super sage parce que maman elle est tout le temps triste vu que papa il est parti faire la guerre aux méchants.</p>
			   <p>D\'ailleurs les méchants, ils arrêtent pas d\'attaquer le campement donc on a du mal a dormir.</p>
			   <p>Si tu pouvais envoyer des cadeaux à mon papa, je serais super contente, car il me manque papa et je veux qu\'il aille bien. Donc envoie lui plein de cadeaux et ma lettre car je sais pas où il est et maman veut pas me le dire. (Papa je t\'aime fort !!)</p>',
                    '<p>L\'année dernière j\'ai pas reçu ma barbie poney magique mais je t\'en veux pas car on avait dû déménager et que tu pouvais pas le savoir et finalement je la veux plus. J\'aimerai bien un Aquasplash pour que maman elle puisse nous défendre quand les méchants arrivent parce que dans le village, on est de moins en moins et je veux pas que les méchants nous emmènent dans leurs prisons (maman m\'a dit qu\'ils sont vraiment méchants et qu\'en plus ils nous privent de desserts quand on est pas sage).</p>
				<p>C\'est tout ce que je voudrais pour cette année, je t\'aime fort papa noël et je te remercie pour tout.</p>
				<p> Je sais que c\'est idiot de commander un pistolet à eau mais maman dit qu\'ils sont encore plus idiot de craindre l\'eau (ils doivent pas prendre beaucoup de douches).</p>
				<p>Elise.</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "written",
                "chance" => "2",
            ],
            "anarch" => [
                "title" => "Lettre d'Anarchipel",
                "author" => "Sigma",
                "content" => [
                    '<p>Cher Paul,</p>
                <p>Ici, il fait beau et chaud, mes vacances à l\'Anarchipel se passent bien, dommage que tu ne sois pas venu !</p>
                <p>Néanmoins, la résidence est tellement sécurisée que l\'on se sent parfois comme dans une prison. Enfin, je ne m\'en plains pas, il y a d\'inquiétants vagabonds qui traînent dehors. L\'autre jour, pendant que je prenais un bain de minuit dans la piscine, j\'ai cru voir l\'un d\'entre eux se balader près du court de tennis. En revenant, j\'ai croisé le gardien qui allait vers l\'infirmerie. Il m\'a dit qu\'il s\'était fait mordre par un une sorte d\'animal sauvage. Comme tu vois, je me fais toujours des films ! Confondre un animal avec un vagabond, quelle histoire !</p>
                <p>PS : En regardant par la fenêtre, j\'ai vu le gardien qui raccompagnait quelques résidents. Vu leurs démarches, ils ont du trop profiter du bar de la plage ! Par contre, j\'ignorais que les personnes ivres grognaient autant.</p>
                <p>Ton frère adoré, Jean.</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "small",
                "chance" => "8",
            ],
            "letlil_fr" => [
                "title" => "Lettre pour Liliane Levent",
                "author" => "totokogure",
                "content" => [
                    '<div class="hr"></div>
                <h1>Mlle Liliane Levent</h1>
                <h1>Quartier de la muraille ouest</h1>
                <h1>Colline des vents brûlés</h1>',
                    '<p>J\'ai enfin trouvé du papier et de quoi écrire... Liliane, si tu lis ce message, sache que je suis sain et sauf. Mais pour combien de temps encore ? C\'est l\'enfer ici... la trentaine de survivants avec qui je vis en communauté et moi-même sommes presque impuissants face aux événements... Je ne sais pas ce qui se trame ?</p>
                <p>Il y a 4 jours, on a entendu une explosion assourdissante et on a vu un champignon atomique au loin, puis tout autour de nous s\'est effondré. J\'ai cru à un incident nucléaire mais certains parlent d\'essais atomiques. Quoiqu\'il en soit, il a fallu tout reconstruire. Mais sans électricité et eau potable, il ne fallait pas s\'attendre à des miracles : on se logea d\'abord plus ou moins avec une sorte de tente confectionnée avec les moyens du bord, puis on décida d\'attendre les secours, mais c\'était sans compter l\'apparition soudaine de ces créatures... ignobles et dégoûtantes...à forme humaine et qui se sont attaquées à certains d\'entre nous.</p>',
                    '<p>Rapidement nous avons alors monté des défenses autour de notre bidonville et jusque là, elles ont tenu bon. Mais ces choses viennent chaque soir de plus en plus nombreuses...Qui sait combien de temps encore nous pourrons tenir...l\'eau commence à se faire rare...ne parlons même pas de la nourriture...Personne n\'ose sortir maintenant... 4 jours déjà...4 jours seulement plutôt... et toujours aucun signe des secours...</p>
                <p>Liliane si jamais je devais y rester, sache que je t\'aime.</p>
                <p class="other">Danny.</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "written",
                "chance" => "1",
            ],
            "nelly" => [
                "title" => "Lettre pour Nelly",
                "author" => "aera10",
                "content" => [
                    '<p>Nelly, je t\'écris cette lettre parceque je peux plus vivre dans ma ville.</p>
                <p>Toute la ville est privée d\'électricité depuis plusieurs jours, Internet ne passe plus, les lignes téléphoniques sont coupées et il y a plus de réseau. Mais surtout, il se passe des trucs pas nets? Certaines routes sont coupées par d\'immenses rochers sortis de nulle part. </p>
                <p>Et hier, j\'ai vu un homme, enfin je suis pas sur que c\'était un homme, c\'était une créature bizarre cachée sous un vieux drap qui fouillait les poubelles. Ca avançait en boitant et ça s\'est dirigé vers l\'appartement de la folle qui habite deux immeubles plus loin. On l\'a retrouvée déchiquetée ce matin. J\'ai du aller poster cette lettre dans la ville a coté. Je te l\'envoie juste pour te prévenir de mon arrivée. </p>
                <p>A l\'heure ou tu la lis, je suis déjà en route. </p>
                <p>Auteur : aera10</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "written",
                "chance" => "5",
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
                "design" => "small",
                "chance" => "9",
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
                "design" => "typed",
                "chance" => "10",
            ],
            "binary_fr" => [
                "title" => "Listing froissé",
                "author" => null,
                "content" => [
                    '<p><small>[Début de transmission]</small></p>
                <p>01000011 01100101 01100011 01101001 00100000 01100101 01110011 01110100 00100000 01110101 01101110 00100000 01101101 01100101 01110011 01110011 01100001 01100111 01100101 00100000 01100100 01100101 00100000 01100100 11101001 01110100 01110010 01100101 01110011 01110011 01100101 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01001101 00100111 01100101 01101110 01110100 01100101 01101110 01100100 01100101 01111010 00101101 01110110 01101111 01110101 01110011 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01000001 01101100 01101100 01101111 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01011001 00100000 01100001 00100000</p>',
                    '<p>01110001 01110101 01100101 01101100 01110001 01110101 00100111 01110101 01101110 00100000 01010011 01010100 01001111 01010000 00101110 00100000 01010000 01101001 01110100 01101001 11101001 00101100 00100000 01100001 01101001 01100100 01100101 01111010 00100000 01101101 01101111 01101001 00100000 01000110 01001001 01001110 00101110</p>
                <p><small>[Fin de transmission]</small></p>
				<p><small>ETR: 27/07 23h16 - Erreur : données corrompues - Statut : <s>IGNORÉ</s></small></p>'
                ],
                "lang" => "fr",
                "design" => "typed",
                "background" => "printer",
                "chance" => "2",
            ],
            "offrex" => [
                "title" => "Maison à vendre",
                "author" => "Pyrolis",
                "content" => [
                    '>
                <h1>Offre exceptionnelle</h1>
                <p>Vend studio, plain-pied, 4m² avec fenêtre explosée plein sud.<br>
                WC extérieurs, cuisine sommaire mais fonctionnelle. Mobilier fourni : une grosse pierre qu’on peut transformer en lit comme en table, très pratique pour les grandes réceptions.<br>
                Jardin spacieux, ne nécessite pas d’entretien.<br>
                Très calme : les voisins sont morts. Néanmoins, tapage nocturne fréquent. Quartier dit « sensible ». Mais le supermarché pillé est à deux pas, idéal pour faire ses courses.<br>
                Et mon prix défie toute concurrence : pour un jambon-beurre seulement ce loft confortable est à vous.</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "typed",
                "chance" => "10",
            ],
            "memori" => [
                "title" => "Mémoire d'une ruine",
                "author" => "Aramirdar",
                "content" => [
                    '>
				<p>Des murs transpirant une odeur atroce de pourriture humaine,<br>
				Des couloirs reflètant les ombres des enfers,<br>
				Des chambres aux draps décorés de sang coagulé,<br>
				Pas de doutes, un hopital abandonné.<br>
				...<br>
				J\'ai trouvé une carte magnétique, Diego, excité, me dit qu\'elle ouvre LA porte<br>
				...<br>
				Nous l\'avons cherchée, en vain, je sais qu\'aujourd\'hui j\'aurais pu la trouver si je n\'avais cette maudite jambe cassée qui ralentit chacun de mes pas<br>
				<br>
				Le soleil se couche. Je suis seul, étendu à l\'arrière d\'une ambulance éventrée. Peut-être demain, qu\'une fois des leurs, j\'irai contempler ce trésor tant fantasmé</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "messagecommiss" => [
                "title" => "Message à la commission",
                "author" => "Gizmonster",
                "content" => [
                    '<blockquote>
			   <h2>Rapport n°1121 adressé à la commission de gestion des incinérateurs</h2>
			   <h2>Jeudi 14 janvier</h2>
			   </blockquote>
			   <p><strong>Objet : défaillance système.</strong></p>
			   <p>Chers agrégés de la commission,</p>
			   <p>Nous souhaitions vous faire part de la panne de l\'incinérateur n°4 survenue ce matin, et nous empêchant de brûler 12 tonnes de cadavres potentiellement infectés à 80 %. Nous avons pu démarrer l\'incinérateur de secours à temps pour brûler 3,6 tonnes de cadavres mais l\'excédent ne pourra pas être brûlé avant minuit... </p>',
                    '<p>La sécurisation du four est en cours pour contenir les zombies qui se relèveront ce soir, mais le blindage n\'est pas sûr sachant qu\'ils le cognent et le grattent jusqu\'à en perdre les doigts. Pour cela nous réclamons un soutien logistique de toute urgence, par sécurité les infrastructures ont été évacuées pour la nuit, mais on ne sait pas ce qu\'il adviendra demain matin.</p>
				<p>En attendant une réponse, veuillez recevoir l\'expression de mes sentiments distingués,</p>
				<div class="hr"></div>
				<p><strong>Kad Havre</strong>, responsable du complexe Ouest </p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "typed",
                "chance" => "1",
            ],
            "vigen1" => [
                "title" => "Message codé n°1",
                "author" => "enneitis",
                "content" => [
                    '>
                <h2>Relevé des communications militaires secteur de l’apocalypse</h2>
                <p>15/07 STFXINC SW WACHG RPATFWE UCTTPQ UAIVGS GT LNEWXECQGS . DRDH. QAA GGMMJT TINXB OATQ AWW MPZCDPQ HGRT CCOBCCJP .WTDD GT QGC.
                28/08 DE MPZCDTC HWQBAS CVZGG WZOAIGE .DRDH. QAAOFED CGJINI RCND JTK VUTG GT LEGWWSTBV PLQHSRTH .GVOA. NPJ QEHITE OC HWGUGWVE OCBSRDT
                OWTZPXKETXCP DPNAGCEG IPIECH SJIC UCRLLIAV CPZOE .DRDH IT UWP. 30/08 HTCG NMOASPTPQ TEIUISU .SEME. ESIIWG HZKBWW BASUSPQ .HLSP. QZGSDSGWW SJDGRQGRAILASU :
                STKEDIMTBV MZPSMW OJ UTIQDTK .WTDD GT QGC. 01/09 TPEHGGS ZLI USNIFCCEC AS QAAOFIP .QIGT. MPZCDPQ PYVEHGGR YMH KSIVBCNE .QIGT. MTRGCTL RZIF P SVE XMGVY .SICR
                EE DXF.02/09 GAIOUTCMEZI
                </p>',
                    '.SICR. VTJAW IN EZGIY AWSSS .HHQP. XYASHEH STRPLI VENH FWE .DRDH. HEGBKECC EWVSDBPED QPARE SCKVPLI KI TTFTEC.QIGT. RTUKMPLI S
                ITT RGCTKT HER CCU MLJPVIS .HHQP. LJAGRS CCWS CCUMKITF FAYQ QMRKTF CTZKXIYE PJGC CCHLI RTUKMPLI .KXOE SV FTL. 10/09 QMRKTF UUMG UGVTT OVTLOJW .WTDD.
                RLFQ TF TLJG XIZJTFX .SICR. MZPPD HEH VQMXCH SY PAIU BLQ .HLSP. GOVIZLH SZAGWGE OMCFINI ACL OC KWRTGS .UTZN. RZENRSU DP QJJZIT GK SZSIAINI OGRTCC KSUH DGU .DRDH IT UWP
                12/09 NZQ BGVTH GG RPJTNINI .GVOA. A TKX LP TKN .DRDH IT UWP.
                <p></p>
                <p>CLEF : il s\'agit du chiffrement Vigenère portant pour clé le mot apocalypse. la ponctuation est respectée.</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
            ],
            "vigen2_fr" => [
                "title" => "Message codé n°2",
                "author" => "Mwak",
                "content" => [
                    '>
                <p>IVYCI SOGLR, G Z\'BPYXS IF N\'KQLTW ISNEI RSNEVK, NYVI G DLTW VCMDIYGCZR JS JWYYWYFVY RY XIY OGTW.
                VWYCVK SME HKJYYY LCO. ZR R\'O LPXXCOGI JOHD PK QCXIZWYCI, KB NCEOB XP KXWAYSZSL OIY AICGKOOI HK UYZVMSM. ZR VOLWI JS AZYRS. NZYZ ZY XSTRY PWZ DUCETCCLUAS.
                OY TGFFP HK QBLQVWAYSTG KFM RS JZMTQIYRKBN. FR GINCI G SNP TKBXF GGF CW VKBZZVIOCE PKG GFVY RY DE SOCDST. GC TPY ZCDITH WPXZS FPXZFY, U\'C GILLMY RLZMZ OODWO,
                X\'YY WAWM NIXHUTR. P\'OC OSTQ WZHK QY EIDHY, WE IZY PWZ ZY YSS RY WE IFYLXAFY BY\'KGN OIBSHF TOSLCI.
                </p>',
                    'SOCD QGZACI ZCOE, NK GOTW YIL BYK HIFX BO M\'LVXOHRIX.
                OPLRZ EOP PG FUOMU BY DSOH WZYVSY, TP E O 8 UYW, RSM XIJSWTRY CHE HOH KF\'MRG UGEOSHE GUAGPRIS U NVKSL FR BOWNMT. HIY JOZM BYO H\'YXFXOMDI LCLE, OKJCYHA85
                <p></p>
                <p>CLEF :Le code est un Vigenère à 5 lettres. Clé : Goule. La ponctuation est conservée.</p>'
                ],
                "lang" => "fr",
                "background" => "white",
                "design" => "written",
                "chance" => "2",
            ],
            "csaatk" => [
                "title" => "Menaces du CSA",
                "author" => "anonyme",
                "content" => [
                    '<h1>AVIS À LA POPULATION</h1>
                <p>Devant la <s>recrue</s> recrudes<s>s</s>cence des actes de tortures animales, le <strong>Comité<s>e</s> de Soutien des Animaux</strong> de notre ville de Frontières de l\'automne cinglant, composé de courageux citoyens <strong>responsables</strong> et <strong>anonymes</strong>, a décidé de mener une action de repression "coup de poing".</p>
                <p>Il est demandé aux citoyens responsables de ces actes de barbarie de cesser immédiatement leurs agissements odieux, sous peine de subir notre <strong>vendetta sanglante</strong> dans les <s>proch</s> jours à venir.</p>
                <h1>Assassins, vos têtes tomberont !</h1>
                <h1><em>La paix dans vos coeurs.</em></h1>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "10",
            ],
            "warnad" => [
                "title" => "Mise en garde",
                "author" => "anonyme",
                "content" => [
                    '<h1>Citoyens, voisins, amis,</h1>
                <p>Le petit malin qui m\'a volé ma radio, mes piles et mes 3 jours de ration est prié de se dénoncer <strong>immédiatement</strong>. En l\'absence d\'information sur le coupable, <strong>je détruirerai les réserves de drogues que vous m\'avez confié demain, à l\'aube</strong>.</p>
                <p>Le pharmacien Kenny.</p>
                <br>
                <p class="other">Nous avons la joie de vous annoncer l\'élection impromptue d\'un nouveau pharmacien en ville. Bravo à Gemino pour sa nomination.</p>
                <p class="other"><small>Ca serait une bonne idée qu\'un citoyen se dévoue pour évacuer le corps de Kenny, décédé cette nuit de cause "inconnue".</small></p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "typed",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "20",
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
                "design" => "written",
                "chance" => "18",
            ],
            "jgoule3" => [
                "title" => "Mon ami la Goule - 3ème partie",
                "author" => "ninjaja",
                "content" => [
                    '<h2>Mon ami la Goule, 3ème partie</h2>
                <p>Nous communiquons entre nous grâce à un ingénieux système à base de baby-phones, trouvés dans une crèche calcinée.</p>
                <p>Dans la journée, nous apprenons la découverte d\'un coffre d\'architecte scellé, nous espérons et nous mettons à rêver.
                Après quelques heures où les ouvrier se sont échinés à l\'ouvrir, le contenu est enfin dévoilé !! ... Malheureusement ce n\'est pas celui escompté...</p>',
                    '<p>Je ne sais pas si c\'est de dépits, ou bien la fringale due à notre longue marche, mais Trotk décide de goûter le cadavre d\'un ancien voyageur du désert qu\'il vient de déterrer.
                Il y a prit goût. On décide finalement de rester un peu plus longtemps dans le coin et remettons à plus tard notre chasse à ces constructions merveilleuses.</p>',
                    '<p>Nos objectifs ont changé, j\'aiderais mon ami la goule à assouvir sa faim dévorante. Les frangins nous soutiendrons discrètement tout en continuant à vaquer à leurs occupations.
                Voyant que je me trouve sur un hôpital abandonné, je rappelle l’existence d\'un vaccin en ces lieux donnant la possibilité à mon ami de se soigner de son état goulifiant.</p>
                <p>Il pourra ainsi manger quelqu\'un en toute impunité et redevenir dans la foulée une personne qui ne meurt pas d’agression mais qui pourra crier au scandale face à cet acte barbare.
                Il me rejoint dans les ruines, et ô miracle le vaccin!!</p>',
                    '<p>Nous dessinons un plan du bâtiment que nous remettrons à la ville, tout en prenant la précaution de falsifier une zone nous servant de cachette pour divers objets. </p>
                <p>Nous rentrons les sac remplis d\'empruntes de serrures que nous transformons en clef à l\'atelier le soir venu.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "16",
            ],
            "jgoule4" => [
                "title" => "Mon ami la Goule - 4ème partie",
                "author" => "ninjaja",
                "content" => [
                    '<h2>Mon ami la Goule, 4ème partie</h2>
                <p>23h58, une FA ouvrière tarde à rentrer.</p>
                <p>Trotk en valeureux chevalier, sort discrètement à son secours.</p>
                <p>Malheureusement il ne le porte pas sur ces épaules mais le transporte dans son ventre. Nous prenons soin de brouiller les pistes.</p>',
                    '<p>Deuxième jour</p>
                <p>1h du matin : L’enquête à déjà commencé. Qui est le meurtrier ? Notre groupe participe activement à cette enquête et s\'impose en leader aux cotés de M. N. :</p>
                <em>"Chouette! J\'aime chasser la goule!" affirme la goule, "Non l\'attaque à eu lieu dehors.", "Ce n\'est pas forcément l’œuvre d\'une seule personne"</em>, etc etc ...',
                    '<p>Nous repartons finir la cartographie de la ruine, déverrouiller les portes et récupérer quelques unes de nos trouvailles reposants à l’abri des regards : vaccin et tronçonneuse entre autre.</p>
                <p>En chemin nous avions également trouvé une scie pour l\'atelier et contacté la ville pour qu\'elle nous envoie un récupérateur de confiance afin que l\'on puisse continuer notre promenade.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "14",
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
                "design" => "written",
                "chance" => "12",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "8",
            ],
            "jgoulc_fr" => [
                "title" => "Mon ami la Goule - 8ème partie",
                "author" => "ninjaja",
                "content" => [
                    '<h2>Mon ami la Goule, 8ème partie</h2>
                <p>Septième jour :</p>
                <p>Nous rentrons en ville tard en discutant de la stratégie à adopter pour ce soir et demain.</p><p>
                </p><p>M. N. doit périr. Moi je suis censé faire le leurre. Dans l\'ordre il est prévu : je prend deux os charnu en banque,
                Trotk dévore sa cible en effaçant les traces de son passage, je vole le chien du décédé. Tout ceci en une minute, juste avant que les zombies ne viennent frapper à nos portes.</p>
                <p>Je serais ainsi désigné comme la goule le lendemain.</p><p>
                </p>',
                    '<p>Huitième jour :</p>
                <p>On récupère tout ce que l\'on peut et je m’enfuis dans le désert. Les autres penseront que je suis la goule, me pourchasseront et mon ami pourra venir se nourrir dehors.</p>
                <p>Nous fuyons ensuite au fin fond du désert, hors de portée d\'une agression.</p><p>
                </p><p>En fin de journée nous gagnons le campement prévu depuis quelques jours et aménagé en prévision.</p>
                <p>Pendant ce temps en ville, les frangins ont agressé et blessé deux leaders. Deux de moins qui pourront tuer la goule. Il en reste trois.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "4",
            ],
            "jgould_fr" => [
                "title" => "Mon ami la Goule - 9ème partie",
                "author" => "ninjaja",
                "content" => [
                    '<h2>Mon ami la Goule, dernière partie</h2>
                <p>Il est minuit 15. Je suis dans ma tente au fin fond du désert.</p>
                <p>Les zombies semblent ne pas nous avoir vu mais je sais que ce ne sera pas toujours le cas. Que ce soit en ville, ou ici, l\'ennemi est également partout autour de nous. La fin est proche.</p>
                <p>En faisant l\'inventaire de nos réserves, j\'ai retrouvé ce petit carnet. Huit jours plus tard, je couche ses lignes par dessus les anciennes.</p>',
                    '<h2>Neuvième jour :</h2>
                <p>La chasse à la MU a commencé. La scie a disparu, il n\'y a plus d\'OD en banque. Le lien entre nous et les frangins est enfin découverts en fin de journée.</p>
                <p>On a fini par rentrer en ville pour prendre de la nourriture et se remplir le ventre de chair fraiche.</p>
                <p>Un survivant nous fait un compliment que je n\'oublierais jamais :</p>
                <p>"Vous m\'avez fait une impression assez bizarre, partagé entre la déception de vous avoir vu ruiner la ville et l\'admiration de votre talent à avoir mené ça de main de maitre!"</p>
                <p>Ce soir j\'ai préféré creuser une tombe et me coucher dedans. Une intuition peut être...</p>',
                    '<em>L\'écriture a changée.</em>
                <p>Mon compagnon de camping est mort il y a deux nuits. D\'ici je peux voir la ville. Hier je suis retourné m\'y nourrir du seul habitant encore valide et donc potentiellement dangereux pour ma vie.
                Notre ami architecte d’intérieur était le seul apte à se défendre.</p>
                <p>Il fut le dernier debout j\'en suis persuadé. Mais une chose est certaine, il est mort maintenant, je ne vois plus les portes de la ville. Les gonds sont vides.
                Le baby-phone ne répond plus. Je suis seul, dernier survivant au milieu du désert.</p>
                <p>Mes mains sont couvertes de sang. Le sang de mes 18 repas à base de viande humaine, et des 250 zombies que j\'ai croisé.
                Onze jours déjà que cette faim meurtrière m\'enivre. Je suis las, je rentre en ville dormir dans la maison si bien décorée de mon ami mort cette nuit.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "2",
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
                <p>La nuit tombe, et je crois apercevoir d\'autres compagnons de jeu<s>x</s> approcher. Eh bien, qu\'il en soit ainsi ! Sur ce, je vous laisse, j\'ai des gens à aller tuer. Et après, peut-être que j\'aurai la chance de vous dévorer, qui sait ?</p>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "written",
                "chance" => "6",
            ],
            "ultim1" => [
                "title" => "Mort ultime",
                "author" => "IIdrill",
                "content" => [
                    '<p>Ce matin, il sort de ces quatre planches qui lui servent de taudis.</p>
                <p>Toute la ville est silencieuse.</p>
                <p>Il se dirige vers la tente voisine. Il soulève le pendant de tissu, et empoigne le bras de son ancienne compagne. Il commence à traîner son corps vers les portes.</p>
                <p>Un objet tombe du corps inanimé. Une feuille de papier en mauvais état, qu\'elle avait sûrement trouvée lors de pillages des maisons d\'autres victimes, accompagnée d\'une mine de crayon pas plus longue qu\'un ongle.</p>
                <p>Il s\'assied, et lit.</p>',
                    '<quote>
                <p>« Je sais que mon heure est arrivée, c\'est pourquoi je me suis décidée à utiliser cette précieuse mine de crayon.</p>
                <p>Mais je n\'ai pas peur.</p>
                <p>Je les entends. J\'entends la nuit. Le vent vient de s\'engouffrer dans notre cité. Alors comme ça, les portes ont cédé. Alors comme ça, c\'est l\'effet que ça fait, lorsque les portes cèdent. Alors comme ça, ils sont entrés.</p>
                <p>L\'air est frais, son odeur est très agréable, ce soir. Je le sens me caresser la nuque, un frisson parcours mon dos. La nuit éclaire un peu ma tente, laisse entrevoir ma couverture et mes pieds gelés.</p>
                <p>J\'aimerais bien m\'endormir, et rêver.</p>
                <p>Mais j\'ai peur.</p>
                </quote>',
                    '<quote>
                <p>Je les entends. Je les entends dans la nuit. L\'un d\'entre eux vient de s\'engouffrer dans la tente de mon voisin. Alors comme ça, il va partir. Alors comme ça, c\'est l\'effet que ça fait, lorsqu\'un des leurs nous dévore. Alors comme ça, il est mort.</p>
                <p>Je l\'entends. C\'est mon tour. »</p>
                </quote>
                <p>Il se relève, empoigne à nouveau les bras de ce corps sans vie, et continue ce qu\'il avait commencé.</p>
                <p>Il est seul, il est le dernier.</p>
                <p>Demain, il ne sera certainement plus là.</p>
                <p>Il sait que dans d\'autres cités, l\'on parlera de lui comme d\'un héros. Il va vivre ce que les anciens appellent aujourd\'hui la «&nbsp;Mort Ultime&nbsp;».</p>'
                ],
                "lang" => "fr",
                "background" => "printer",
                "design" => "classic",
                "chance" => "5",
            ],
            "cave1_fr" => [
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
                "design" => "written",
                "chance" => "5",
            ],
            "noguid" => [
                "title" => "Non guide du survivant",
                "author" => "Balthy",
                "content" => [
                    '>
				<h1>Notice d\'accueil malhonnête pour nouveaux arrivants</h1>
				<p>Les premiers conseils pour que votre existence dure plus longtemps</p>
				<p>
				Dès le crépuscule, dans le désert, au choix :<br>
				Fouillez soigneusement le désert à la recherche d\'une zone où il y a d\'autres campeurs. Plus il y en a, mieux c\'est.<br>
				Entrez dans la ruine, et étanchez enfin votre soif.<br>
				</p>
				<p>
				Vers minuit, en ville, au choix :<br>
				Dirigez vous en veille avec une bonne gueule de bois et une radio allumée, ça vous rendra plus fort.<br>
				Ouvrez les portes, il faut aérer la ville pour éloigner le danger.<br>
				</p>
				<p>
				En toutes circonstances :
				Rien ne vaut la construction d\'un bon réacteur nucléaire. Puis, courir loin.
				</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "teia" => [
                "title" => "Note d'un citoyen banni",
                "author" => "Teia",
                "content" => [
                    '>
                {asset}build/images/fanart/worldmap03.png{endasset}'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "code1" => [
                "title" => "Note illisible",
                "author" => "anonyme",
                "content" => [
                    '<p><strong>-1</strong></p>
                <p>oktr qhdm m drs rtq</p>
                <p>st cnhr pthssdq kz uhkkd zt oktr uhsd</p>
                <p>hkr rnms sntr cdudmtr entr hbh</p>
                <p>qdsqntud lnh z kz uhdhkkd onlod gxcqztkhptd z bhmp gdtqdr</p>
                <p>hk x z tmd lnsn bzbgdd kz azr</p>'
                ],
                "lang" => "fr",
                "background" => "letter",
                "design" => "written",
                "chance" => "8",
            ],
            "notesu" => [
                "title" => "Note suspecte",
                "author" => "kukrapok",
                "content" => [
                    '>
				<em>Note suspecte</em>
				<p>Je suis Faucha, de la planète Tritis.<br>
				J\'ai des difficultés à écrire car je n\'ai pas de doigts au bout de mes membres supérieurs, mais mon plumage est doux et soyeux.</p>
				<p>
				Dans la vie, j\'aime guetter les survivants, du haut de ma branche morte, et leur foncer dessus quand ils déblatèrent des idioties. Ce sont des proies juteuses.
				Des fois c\'est le buffet, on appelle ça un Crocus avec mes congénères.
				Les mettre au sol, les picorer jusqu\'à la mort, puis dévorer leur foie, c\'est notre passion.
				</p>
				<p>
				Si vous trouvez cette note, vous êtes déjà mort...
				</p>
				<p>
				Je suis là !
				</p>
				<p>
				Crôôôâaaa !
				</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "2",
            ],
            "ie_fr" => [
                "title" => "Note pour les prochains promeneurs",
                "author" => "coctail",
                "content" => [
                    '<p>Coctail, Pantocrat et Zoby sont passés ici. Cette zone ne contient plus rien d\'utile. Attention aux zo<s>m</s>b<s>ie</s>s cachés sous le sable. Danger de mort.</p>
                <div class="other">&nbsp;c\'est bon, j\'ai fait le ménage !!</div>
                <div class="other">&nbsp;&nbsp;&nbsp;- half</div>'
                ],
                "lang" => "fr",
                "background" => "money",
                "design" => "written",
                "chance" => "8",
            ],
            "thonix" => [
                "title" => "Notes de Thonix",
                "author" => "Thonix",
                "content" => [
                    '<div class="hr"></div>
                <h1>Journal de Thonix, Jour 5, Ancienne Cité Oubliée</h1>',
                    '<div class="hr"></div>
                <p>18 août 2008, 5h24 : Je n\'ai pas beaucoup dormi cette nuit, mais je suis encore là, c\'est le plus important. Mon ami avec qui j\'avais l\'habitude de jouer aux cartes n\'a pas pu rentrer, j\'ai mal à l\'idée de penser qu\'il est devenu l\'un d\'entre eux... Nous attaquons aujourd\'hui la fausse ville, en espérant pouvoir la finir.</p>',
                    '<div class="hr"></div><div class="hr"></div>
                <p>18 août 2008, 14h48 : Je n\'ai rien mangé ce midi, il ne restait que de la viande humaine de mon ami mort. Je préférerai mourir que de le manger.</p>',
                    '<p>18 août 2008, 18h04 : J\'ai bien peur que nos défenses pour ce soir ne vont pas suffire, la fausse ville n\'est pas terminée et tout le monde est fatigué...</p>',
                    '<div class="hr"></div>
                <p>18 août 2008, 23h59 : Je les entends, ils arrivent, ils sont nombreux, bien plus qu\'on ne le pensait !! Les défenses ont cédé !! Ils sont tout autour de moi, c\'est la fin je...</p>
                <p><small>" Et le journal s\'arrête là. Paix à son âme. "</small></p>
                <p>Auteur : Thonix</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "10",
            ],
            "cold1" => [
                "title" => "Obscurité glaciale",
                "author" => "Planeshift",
                "content" => [
                    '<p>Froid... si froid...</p>
                <p>Je suis seul, maintenant. Enfin, si on oublie les grattements réguliers sur la porte en bois. Ils sont là, dehors, devenant plus insistants heure après heure. Mes compagnons d\'infortune en font peut-être partie, qui sait ? Blessé aux jambes, incapable de bouger, je leur ai dit de partir. L\'un d\'eux ... Jon je crois, je ne sais pas, je ne sais plus ; m\'a laissé un pistolet. « Deux balles dedans. Une pour eux, une pour toi. » </p>
                <p>Il a toujours été franc, Jon.</p>
                <p>Le froid devient horrible alors que la nuit tombe. Les grattements deviennent des coups répétés, et la porte va bientôt céder, mais qu\'importe. Je compresse comme je peux la blessure de ma jambe, mais le sang ne semble pas vouloir s\'arrêter de couler. Tant pis.</p>
                <p>Lecteur hypothétique, toi qui es perdu ici-bas, je laisserai<s>s</s> cette dernière balle pour toi. Ils ne nous auront pas vivants.</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "small",
                "chance" => "10",
            ],
            "ode" => [
                "title" => "Ode aux corbeaux",
                "author" => "Firenz",
                "content" => [
                    '>
				<h1>Boulimie de Plumes</h1>
				<p>Corbeaux, Jeunes Corbeaux,<br>
				Volatiles nécrophages,<br>
				Vous si lointains, là haut,<br>
				Perchés dans les nuages,<br>
				<br>
				Gardez vous de gouter<br>
				La chaire encore rose<br>
				des restes déposés<br>
				Sur l\'autel de la prose.<br>
				<br>
				Les vieux ne sont pas morts<br>
				Ils sommeillent simplement.<br>
				Poitrines médaillées d\'or<br>
				Mais gardiens vigilants.<br>
				<br>
				</p>',
                    'Vous aimez à tâter<br>
				Leur vieille peau tannée<br>
				mais à trop picorer<br>
				Les voilà dépecés...<br>
				<br>
				O affamés jabots,<br>
				Cessez donc de faucher<br>
				Un à un nos plus beaux<br>
				Trublions du passé !
				<p></p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "page51" => [
                "title" => "Page 51 d'un roman",
                "author" => "coctail",
                "content" => [
                    '<p>retourna lentement.</p>
                <p>Le sergent fixait le soldat à sa radio. Il venait de déchiffrer le message en morse : « Etat major à tranchée n°12 : ravitaillement coupé, passage tenu par l\'ennemi. Impossible de reprendre le secteur. Tenez position le plus longtemps possible. Dieu vous garde ». Le radio regardait le sergent. De grosses gouttes de sueur coulaient à son front.</p>
                <blockquote>- « Il faut avertir les autres mon sergent... Nous allons tous mourir.&nbsp;»</blockquote>
                <p>Un coup de feu retentit. Le sergent sortit de la tente, le pistolet encore fumant et cria : </p>
                <blockquote>- « Soldats, tâchez de surveiller vos nerfs. Le radio vient de se suicider. Bon, le ravitaillement va tarder un peu? va falloir compter vos balles maintenant. »</blockquote>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "classic",
                "chance" => "10",
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
                "design" => "small",
                "chance" => "4",
            ],
            "citsig" => [
                "title" => "Panneau de ville",
                "author" => "coctail",
                "content" => [
                    '<div class="hr"></div>
                <center>
                <big>Terres de l\'abîme.</big>
                <div>4<s>0 hab</s>itants.</div>
                <div class="other"><strong>Ville zombie, PAS de survivant. Fouillée et hantée. DANGER !!!</strong></div>
                </center>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "10",
            ],
            "condm" => [
                "title" => "Paroles d'un condamné",
                "author" => "Arma",
                "content" => [
                    '<p>J\'ai froid, la nuit vient de tomber et je suis toujours à l\'extérieur de la ville, je crois que ma jambe est cassée... De toute façon je suis perdu, les <s>cadav</s>Morts-vivants m\'ont poussé vers des dunes lointaines...</p>
                <p>Je vais mourir... Ma famille me manque...</p>
                <p>Toi, qui lis ces mots, dis leur que je les aime et que j\'ai toujours pensé à eux...</p>
                <p>Ils sont partout, et pourtant, ils m\'observent, sans bouger. Ils... attendent ?</p>
                <p>Périr est... réconfortant. La vie n\'est qu\'un éternel stress devant la multitude de chemins que le <s>futu</s>Destin nous dessine...Je crois...</p>
                <p>Je n\'ai plus le choix, <s>je d</s>il ne me reste plus qu\'une route à suivre. Peut-être la meilleure de toutes?</p>
                <p>J\'entends au loin les douze coups de minuit. C\'est fini...</p>
                <p>Ne m\'oubliez pas.</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "small",
                "chance" => "10",
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
                "design" => "classic",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "2",
            ],
            "grifo_fr" => [
                "title" => "Poème griffonné",
                "author" => "Ozzy",
                "content" => [
                    '>
                <h1>Poème griffonné sur un bout de papier</h1>
                <p>Rongé par le désespoir, la fatigue et la faim,<br>
                Bavant et tremblant, je tendis la main<br>
                Pour arracher de son corps encore frémissant<br>
                De quoi me sustenter, du moins pour un moment.<br>
                </p>
                <p>
                Il était mon ami, mon frère, mon confident<br>
                Mais il ne verra plus jamais le soleil se lever<br>
                Je penserai à lui, pendant un temps<br>
                Mais comme tout le reste, il sera oublié.<br>
                </p>
                <p>
                Je repris donc ma route, marchant vers le crépuscule<br>
                Sentant dans ma poche, cette putain de pilule<br>
                Entre mes doigts, le pouvoir d\'en finir<br>
                Une bonne fois pour toutes, cesser de souffrir.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
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
                "design" => "modern",
                "chance" => "5",
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
                "design" => "ad",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "2",
            ],
            "fishes" => [
                "title" => "Pêche",
                "author" => "Irmins",
                "content" => [
                    '<p><strong>Registre de la Ville: Espoirs Retrouvés, le 11 novembre<s> 1966</s></strong></p>
                <p>Depuis hier, nous regagnons l\'espoir de survivre ! Les créatures déferlent les unes après les autres sur les portes de la ville, chaque jours plus nombreuses ... Nous avons foré les nappes phréatiques, et notre puis nous permettra de tenir plus de 3 mois sans problèmes d\'eau ... Nos canons a eau fonctionnent a plein régime <em>[...]</em> de moins en moins nombreux <em>[...]</em>.</p>
                <p>Hier, nos éclaireurs sont partis avec leurs motos en direction de l\'Est <em>[...]</em> Grande découverte <em>[...]</em> changera nos vies a tout jamais ! Après plusieurs jours de progression dans l\'outre monde, ils ont trouvés <em>[...]</em> d\'eau, <em>[...]</em>possibilité de construire un bateau <em>[...]</em> système de pompage et de filtrage <em>[...]</em></p>
                <p>Nous nous interrogeons sur l\'état des poissons... Sont ils vivants et comestibles, ou se sont ils transformés en Zombie également ? Dans le premier cas, un bon bain de mer et du poisson frit nous remonteraient le moral ! Dans le deuxième <em>[...]</em> </p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "small",
                "chance" => "10",
            ],
            "raleg" => [
                "title" => "Râles goulifiques",
                "author" => "Selcota",
                "content" => [
                    '>
                <h2>Râles goulifiques</h2>
                <p>Visage pâle qui me contemple<br>
                D\'un vil sourire ample,<br>
                Immaculée de sang<br>
                Agonisant.
                </p>
                <p>
                Convulsant<br>
                Au parfum alléchant<br>
                De quelques chairs tuméfiées<br>
                Qu\'une main innocente approchée<br>
                </p>
                <p>
                Gigotait<br>
                Comme un hochet<br>
                À l\'abord des canines<br>
                De l\'animal sans discipline.
                </p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "poem",
                "chance" => "2",
            ],
            "exp1" => [
                "title" => "Rapport d'expérience, origine inconnue",
                "author" => "zhack",
                "content" => [
                    '<h1>Test de la souche n°128</h1>
				<p>Le sujet a montré une nette régression du virus pendant 2 heure, mais hélas, le virus a développé une immunité extrêmement rapidement et a conduit le sujet à une crise cardiaque.</p>
				<h1>Test de la souche n°129</h1>
				<p>Le sujet n\'a pas survécu à la souche : l\'afflux sanguin a augmenté de façon exponentielle dans certains membres.</p>
				<h1>Test de la souche n°130</h1>
				<p>Pneumothorax.</p>',
                    '<h1>Test de la souche n°131</h1>
				<p>1/ La souche n°131 semble avoir marché. Effet secondaire : les fonctions cérébrales sont réduites aux cortex primitif et le sujet semble manifester une grande peur face à de l\'eau.  Je laisse le sujet en observation.</p>
				<p>2/ L\'épiderme du sujet ne semble plus supporter l\'eau mais plus étrange encore, le sujet ne semble plus avoir besoin de s\'hydrater.</p>
				<p>Concl : J\'envoie le sujet au docteur Dalek pour la phase 2. Je continue les tests sur cette souche.</p>',
                    '<h1>Test de la souche n°131 bis</h1>
				<p>1/Cette souche est miraculeuse, elle semble résister à la plupart des maladies. La contrepartie semble être une hausse de l\'agressivité lors de l\'absence de lumière et toujours cette vulnérabilité à l\'eau. Nous sommes assurément dans la bonne voie pour le projet  VU.</p>
				<p>Auteur : zhack</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "typed",
                "chance" => "1",
            ],
            "army1" => [
                "title" => "Rapport d'opération Nov-46857-A",
                "author" => "zhack",
                "content" => [
                    '<h1>Rapport d\'opération Nov-46857-A</h1>
				<p><strong>11 novembre  23h30</strong></p>
				<p>L\'armée vient de nous déposer ici? On ne sait pas trop pourquoi ni dans quel but? Beaucoup de rumeurs circulent , pour certains c\'est une super rage, pour d\'autre c\'est la fin du monde? Pour ma part, je m\'en moque un peu : l\'armée nous sauvera le cul comme d\'hab? Après un rapide tour du camp, j\'ai pu récupérer trois, quatre affaires qui  pourraient m\'être utile.</p>',
                    '<p><strong>12 novembre  15h45</strong></p>
				<p>A priori c\'est beaucoup plus grave que ce que l\'on pensait : une vieille a été virée du camp suite au fait qu\'elle s\'est mise à mordre toutes les personnes qui passait à sa portée. L\'armée nous a dit de ne pas nous inquiéter? Sauf que l\'armée vient de nous abandonner en nous laissant quelques vivres.</p>
				<p><strong>13 novembre 00h15</strong></p>
				<p>Je sais pas trop ce qui se passe. J\'entend des cris dehors? Je me suis barricadé comme je le pouvais avec ce que j\'ai pu mais je sais pas si ça sera suffisant? Ça  se rapproche mais</p>
				<p><small>Le reste est illisible. Vous apercevez quelques taches de sang sur la feuille</small></p>'
                ],
                "lang" => "fr",
                "background" => "printer",
                "design" => "typed",
                "chance" => "5",
            ],
            "heli1" => [
                "title" => "Rapport d'une unité de soutien",
                "author" => "sanka",
                "content" => [
                    '<h2>Transcription d\'une communication radio militaire, Colline 122, 00:15 :</h2>
				<p>Notre Blackhawk approche de la cible, nous pouvons apercevoir l\'antenne médicale et le personnel de la Croix Rouge en train de s\'affairer autour des derniers malades arrivés. Nous survolons les environs un petit moment à la recherche de zombies potentiels mais avec cette obscurité il est très difficile de distinguer quoi que ce soit, et la faible lueur des projecteurs placés tout autour du camp n\'offre guère qu\'une portée de 10m...</p>
				<p>Soudain la radio de bord crépite : "Eagle One, le camp est infecté, je répète le camp est infecté". Je regarde mes collègues, dépité, cependant les ordres sont très clairs à ce sujet. </p>',
                    '<p>J\'arme la mitrailleuse M60 de bord, fais sauter le cran de sûreté, vise le camp et ferme les yeux un instant, le temps pour le pilote de se placer en vol stationnaire. </p>
				<p>Je rouvre l\'oeil droit et j\'aperçois, 30m en contrebas, les malades et le personnel soignant en train de s\'entre-dévorer. Finalement j\'appuie sur la détente et déclenche le déluge de balles traçantes sur les petites tentes de toiles et les cibles mouvantes.... saloperie...</p>'
                ],
                "lang" => "fr",
                "background" => "printer",
                "design" => "typed",
                "chance" => "2",
            ],
            "truck1" => [
                "title" => "Rapport de combat 1",
                "author" => "sanka",
                "content" => [
                    '<h2>Route départementale 26, 00:05 :</h2>
                <p>Notre colonne de blindés roule depuis maintenant plus de 2h sans rencontrer aucune âme qui vive quand soudain le chemin se trouve barré par 2 vieilles voitures encastrées. Nous demandons à une dizaine d\'hommes de sortir afin de dégager la route. Au bout de 2 min la celle-ci est déjà dégagée. Les hommes se précipitent vers les camions quand soudain de nombreuses ombres apparaissent dans les fourrés environnants et se jettent sur l\'escouade. S\'en suit un échange de coups de feux dans toutes les directions, les zombies sont tellement nombreux que nous en sommes réduits à abattre nos propres hommes.</p>',
                    '<p>Le capitaine Willard nous donne l\'ordre de sortir de là. Nous démarrons les blindés et continuons notre chemin par la route désormais libre tout en laissant la poignée d\'hommes encore en vie aux mains des contaminés...</p>'
                ],
                "lang" => "fr",
                "background" => "printer",
                "design" => "typed",
                "chance" => "2",
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
                "design" => "small",
                "chance" => "10",
            ],
            "recet" => [
                "title" => "Recette",
                "author" => "Paranoid",
                "content" => [
                    '>
				<h1>RECETTE DE RAGOUT DE ZOMBIE</h1>
				<h3>pour 6 personnes</h3>
				<p>Prendre 1 kilo de viande de zombie ( je vous conseille une cuisse bien "fraîche" ),
				la faire saisir sur feu vif dans une marmite, ajouter une poignée de sciure (elle sert à lier la sauce),
				ajouter 3 légumes suspects émincés, 1 doigt de "debout-les-morts". Recouvrir d\'eau, pure ou non, ou si défaut, du sang bien frais.
				Touiller avec un os charnu, et servir bien chaud. N\'hésitez pas à y ajouter ce que vous voulez, mais faites attention quand même.</p>
				<p>
				pincez-vous le nez, et bon appétit !
				</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "small",
                "chance" => "8",
            ],
            "letal_fr" => [
                "title" => "Remords létaux",
                "author" => "NeCa",
                "content" => [
                    '>
                <h3>Remords létaux</h3>
                <p>Hier c\'était fabuleux ! Quelle soirée ! On avait allumé un grand feu, dressé un immense buffet et l\'alcool coulait à flots.<br>
                Je nous vois encore rigoler et danser sur la grande place. On était plus d une trentaine à faire la fête jusqu\'au milieu de la nuit !<br>
                Ce matin , je ne reconnais plus l\'endroit. c\'est le chaos. J\'ai l\'impression d\'avoir atterri en Enfer...<br>
                Je crie de toutes mes forces. Mais personne ne répond...<br>
                Je... je... je crois... je crois que j\'ai oublié de fermer les portes !!<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "typed",
                "chance" => "5",
            ],
            "necro" => [
                "title" => "Rubrique nécrologique",
                "author" => "anonyme",
                "content" => [
                    '<h1>Décès récents</h1>
                <p><strong>Mardi</strong> : Raph, JeanMi, Ynohtna, Titoflo</p>
                <p><strong>Mercredi</strong> : Molly, Meuton, Ebola, Whi<s>tetigl</s>e <span class="other">(ah non...)</span></p>
                <p><strong>Jeudi</strong> : Whitetigle <span class="other">(saleté d\'infection)</span></p>
                <p><strong>Vendredi</strong> : Morkai, Amorphis, Denz</p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "small",
                "chance" => "8",
            ],
            "ana" => [
                "title" => "Ruine blockhaus abandonné - 6km ESE",
                "author" => "Ana147",
                "content" => [
                    '>
				{asset}build/images/fanart/worldmap05.jpg{endasset}'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "cutleg" => [
                "title" => "Récit d'un habitant",
                "author" => "coctail",
                "content" => [
                    '<p>J\'ai alors tailladé la jambe à Ervin. Il m\'a regardé sans comprendre. Je lui ai dit : « Tu vois les zombies là, en haut de la colline ? 
                Toi, tu es jeune, moi, je suis vieux. Tu courrais plus vite que moi, mais plus maintenant. Merci de m\'avoir attendu.  
                Et n\'oublie pas mon garçon : quand la horde arrive, il ne faut pas courir vite, il ne faut juste pas être celui qu\'ils vont rattraper. 
                Maintenant, menotte-toi à la poutre, je ne voudrais pas te tirer dans la jambe, les munitions sont si rares... 
                Bonne chance, je les entends arriver... Et merci de m\'avoir passé ton fusil.</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "typed",
                "chance" => "8",
            ],
            "lords1" => [
                "title" => "Récits de LordSolvine, partie 1",
                "author" => "lordsolvine",
                "content" => [
                    '<p>Je me nomme LordSolvine, et je suis le dernier survivant de ma famille après <em>l\'accident</em>. Je ne sais pas à quoi tout cela rime, d\'où ça vient, et qu\'est-ce que ça veut... Mais c\'est pas humain et manifestement pas très amical.</p>
                <p>On dirait des hommes, mais plus ces êtres se rapprochent, plus le sentiment de peur grandit. Mes parents et mon petit frère sont morts dévorés par ces créatures.</p>
                <p>La télévision parlait de virus, la radio d\'expériences scientifiques loupées, le journal d\'invasions d\'extraterrestres. Mais au fond, personne ne savait et ne sait vraiment.</p>',
                    '<p>Je suis installé sur un banc abandonné de mon ancien village... Village inconnu, aujourd\'hui rayé de la carte comme le monde entier. Le sable est partout. Les océans, lacs et étangs sont tous asséchés; la végétation a disparu laissant place a des arbres secs. De temps en temps, une poule, un cochon passe...Des animaux qu\'on ne reverra plus une fois perdus dans le désert.</p>
                <p>La nuit tombe... Et je ne sais où dormir. Je vais marcher toute la nuit... Marcher, il n\'y plus que ça à faire aujourd\'hui.</p>
                <p><strong>LordSolvine</strong></p>'
                ],
                "lang" => "fr",
                "background" => "grid",
                "design" => "typed",
                "chance" => "5",
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
                "design" => "typed",
                "chance" => "5",
            ],
            "clinik_fr" => [
                "title" => "Réclame clinique Habemus Spiritus",
                "author" => "Held_n_Steyab",
                "content" => [
                    '<h2>la clinique Habemus Spiritus vous propose ses services</h2>
                <p>Un de vos proches s\'est "transformé" ? La clinique Habemus Spiritus vous propose ses services pour vous soulager dans cette épreuve.</p>
                <h2>Accompagnement de fin de vie </h2>
                <p>Fin de vie simplifiée pour vos proches \'transformés\'.</p>
                <p>Au programme :</p>
                <small>- Balnéothérapie délassante avec l\'eau pure de nos montagnes.</small>
                <small>- Transformation de l\'âme grâce à nos spécialistes en mysticisme.</small>',
                    '<h2> séjour palliatif </h2>
                <p>Un séjour au calme pour vos proches éprouvés par la \'transformation\'.</p>
                <p>Au programme :</p>
                <small>- Cellule individuelle, confortable, taux d\'humidité proche de 0.</small>
                <small>- Nourriture 100% origine humaine garantie. (origine des viandes : déchets hospitaliers et surplus des ossuaires locaux)</small>
                <small>- Hygiène et soins certifiés waterless.</small>
                <small>- Suivi de la voracité de votre proche en direct par mail ou sur votre smartphone</small>
                <small>- Nos médecins sont formés pour ne jamais frapper les patients</small>'
                ],
                "lang" => "fr",
                "background" => "stamp",
                "design" => "stamp",
                "chance" => "5",
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
                "background" => "stamp",
                "design" => "stamp",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "typed",
                "chance" => "10",
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
                "design" => "written",
                "chance" => "2",
            ],
            "shun1" => [
                "title" => "Shuny : Témoignage des derniers jours",
                "author" => "Shuny",
                "content" => [
                    '<div class="hr"></div>
				<h1>Shuny : Témoignage des derniers jours d\'un homme au coeur de l\'Outre-Monde</h1>
				<h2>16 août, 0h14.</h2>',
                    '<p>Les zombies frappent sans relâche. Alors que les Hordes ont débarqués sur le Comté Noir, j\'espère pouvoir leur échapper un jour de plus grâce au Taudis construit peu de temps avant l\'attaque. Qui sait, peut-être ne sont-ils pas assez nombreux pour briser la solide paroi qui me sépare d\'eux.</p>
				<p>Recroquevillé derrière ma table en bois, j\'écoute avec horreur les hurlements désespérés de mes anciens voisins appelant à l\'aide dans un dernier souffle de vie. Terrifié, je me lève, lentement, et regarde le spectacle sanglant qui se déroule à l\'extérieur. Un habitant lutte avec son réfrigérateur et neutralise 3 zombies. C\'était sans compter la fragilité de l\'objet, qui cède sous le choc et lui enlève toute protection.</p>
				<p>Un zombie se jette alors sur lui, et lui arrache un bout de gorge avec les dents.</p>',
                    '<p>Sonné par cette boucherie, je m\'évanouis. Réveillé, je ne sais combien de minutes ai-je passé inconscient mais le bruit de mon corps contre le sol semble avoir attiré les zombies. Ils sont une centaine. Ils frappent de plus belle contre mon taudis, les fenêtres cassent, la mort est à ma porte, ...</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "written",
                "chance" => "2",
            ],
            "ensole" => [
                "title" => "Souvenir d'un été ensoleillé",
                "author" => "Drozera",
                "content" => [
                    '>
				<h1>6 juin</h1>
				<p>... histoire comme la nôtre, d’aucuns diraient qu\'il est rare que des couples survivent aussi longtemps,
				et pourtant nous l’avons fait ! Moi, après avoir raté mes études d\'enseignante, j\'avais gagné le concours Miss Epi de Maïs, une fierté, et j\'écumais les salons,
				ça payait bien et lui, infirmier de nuit, était aux premières loges quand tout a commencé.
				C\'est peut-être ce qui l\'a sauvé d’ailleurs, il a su avant tout le monde que le moment était venu de fuir.
				Nous étions déjà loin quand l’armée a perdu le contrôle et que les premières grandes villes ont été submergées ...</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "souven_fr" => [
                "title" => "Souvenirs d'un survivant perdu",
                "author" => "Selcota",
                "content" => [
                    '>
                <h1>Souvenirs d\'un survivant perdu</h1>
                <p>Pourquoi luttions-nous encore, contre ces fantômes cannibales ?<br>
                Perdus dans ces contrées insalubres, au paysage aride et à l\'allure putride, nous pourchassions notre but incertain dans une brume aveuglante.<br>
                La routine à laquelle nous étions assommés, par le sang et les rugissements perpétuels, nous dévorait un peu plus chaque jour. Nous n\'étions plus.
                Seulement des âmes vagabondes qui erraient langoureusement.
                Nous étions donc debout : haches et couteaux à la main. Face à ces portes fébriles.
                Qu\'importait l\'issue du combat. Qu\'importaient nos vies. Notre destinée s\'achevait ici, entre nous et les Hordes déchaînées.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "mirek" => [
                "title" => "Souvenir de Mirek",
                "author" => "Zanari",
                "content" => [
                    '>
                <h1>une note recouverte par le sable…</h1>
                <p>Mirek, ses yeux la mer, ses cheveux, le soleil, Mirek ton fils à toi, pauvre loque cernée, affaissée dans la poussière.
                Mirek qui courait dans le sable, Mirek, qui glissait de petits os dans sa poche. Mirek qui avait huit ans et en aurait eu neuf bientôt, s\'il avait vécu, oh, s\'il était seulement né avant...
                Tu as été sublime, autrefois, la main de ton fils dans la tienne. Que ta peau est pâle, maintenant... Les morts ont pris ton fils, ton esprit l\'a accompagné avant ton corps ne lâche finalement.
                </p>
                <p>Le Vent emporte ce mot, le Temps se souvienne de toi au côté de notre enfant, je t\'ai aimé…</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "small",
                "chance" => "7",
            ],
            "sos1" => [
                "title" => "S.O.S.",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                    <p>Ceci est un message à l\'aide ! Je suis situé dans la ville de la <strong>Toundra<s>s</s> du nord</strong> ! Si quelqu\'un reçoit <s>mon</s>ce message, QU\'IL VIENNE M\'APPORTER UN <s>PM</s>PETIT MANCHE VIBRANT, c\'est une question<s>s</s> de VIE OU DE MORT !</p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "postit",
                "chance" => "2",
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
                "design" => "typed",
                "chance" => "5",
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
                "design" => "modern",
                "chance" => "10",
            ],
            "wincar" => [
                "title" => "Ticket d'Or",
                "author" => null,
                "content" => [
                    '<h1><small>Ce paquet de cigarettes est</small> GAGNANT&nbsp;!</h1>
                <p>Pour recevoir votre lot, envoyez cette étiquette ainsi qu\'un justificatif d\'achat sur la messagerie privée :</p>
                <blockquote>
                <p>Epoq</p>
                </blockquote>
                <small>Note : cette étiquette gagnante donne droit 3 jours héros gratuit dans l\'outre-monde !</small>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "1",
            ],
            "wintck" => [
                "title" => "Ticket gagnant",
                "author" => null,
                "content" => [
                    '<h1><small>Ce paquet de cigarettes est</small> GAGNANT&nbsp;!</h1>
				<p>Pour recevoir votre lot, envoyez cette étiquette ainsi qu\'un justificatif d\'achat à :</p>
				<blockquote>
				<p>Good\'ol Chuck CC,</p>
				<p>Brown &amp; Williamson Sq.</p>
				<p>WC2H 7LA, London</p>
				</blockquote>
				<small>Note : cette étiquette gagnante donne également droit à un bilan de santé complet GRATUIT, car Good\'ol Chuck sait prendre soin de ses clients.</small>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "2",
            ],
            "lostck" => [
                "title" => "Ticket perdant",
                "author" => null,
                "content" => [
                    '<h1>Vous n\'avez pas gagné !</h1>
                <blockquote>
                <p>Mais n\'hésitez pas à tenter votre chance à nouveau en savourant d\'autres Good\'ol Chuck Classic&nbsp;! Les cigarettes Good\'ol Chuck sont 27.3% moins toxiques que les cigarettes de même catégorie.</p>
                </blockquote>
                <small>Statistiques établies sur l\'ensemble des cas de cancers du poumon enregistrés sur 6 mois au London General Hospital, Lessingshire Av., WC2H 5RD, London.</small>
                <small><strong>Savourez donc une Good\'ol Chuck Classic !</strong></small>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "10",
            ],
            "crema1" => [
                "title" => "Tirage au sort",
                "author" => "stravingo",
                "content" => [
                    '<blockquote>Refuge des illusions perdues, le 19 février</blockquote>
                <p>Cela fait des <s>jour</s>semaines que nous n\'avons plus de nourriture. La faim nous tenaille mais nous ne pouvons pas sortir<s>e</s>. Au-delà de nos maigres barricades, les créatures sont maintenant beaucoup trop nombreuses. Je n\'en peux plus. Je n\'ai plus la force.</p>
                <p>Ce matin, nous avons tiré au sort. C\'est tombé sur moi mais je m\'en fiche. Les autres m\'ont presque envié. Je sens à l\'odeur du charbon de bois qu\'ils ont allumé le crématocue. Ils m\'ont dit que je ne sentirai <s>presque</s> rien, qu\'ils m\'ont réservé toute une bouteille de vodka. Mais je sais qu\'ils mentent. </p>
                <p>J\'avais volé la dernière<s>..</s></p>
                <p><em>Stravingo</em></p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "7",
            ],
            "tobego" => [
                "title" => "To be goule or not to be ?",
                "author" => "Aaku",
                "content" => [
                    '>
                <p>Ma persévérance s’éloigne avec mes derniers espoirs de survie sur le dos,
                juste car je ne voudrais pas en arriver à sacrifier leur vie pour sauver la mienne.</p>
                <p>
                A quoi bon agir si égoïstement, après tout… ?</p>
                <p>
                En me laissant mourir, j’avais des chances de rejoindre ceux qui n’étaient déjà plus des nôtres, au paradis. Mais mon dieu accepterait-t-il un monstre dans son royaume ?</p>
                <p>
                Bannie, ma seule chance de survie reste de dévorer un citoyen à portée de ma nature goulesque.
                Alors que je suis bientôt épuisée, oserai-je tuer cet homme, qui campe misérablement dans l’Outre-monde ?</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "tomb_fr" => [
                "title" => "Tombe d'un poète",
                "author" => "Emmatopiak",
                "content" => [
                    '>
                <p>
                C\'était un poète.<br>
                Maintenant il a la tête pleine de vers. Littéralement.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "typed",
                "chance" => "2",
            ],
            "theend" => [
                "title" => "Tout est donc fini.",
                "author" => "CeluiQuOnNeNommePas",
                "content" => [
                    '<p>Tout est donc fini.</p>
				<p>Est-ce vraiment ainsi qu\'un homme doive passer de vie à trépas ? En connaitre le jour exact, la cause ?</p>
				<p>Mes deux derniers compagnons d\'infortune n\'ont plus supporté l\'épée de Damoclès au-dessus de leurs têtes.</p>
				<p>En pleine journée, le plus jeune n\'a pas répondu alors que je l\'invitais à partager le peu de nourriture qu\'il nous restait. Il était allongé sur son lit de camps, digne, les yeux encore ouverts fixant son plafond éventré la veille au soir. Seule l\'écume aux coins des lèvres nous aura fait comprendre qui avait subtilisé la dose de cyanure disparue mystérieusement deux jours auparavant.</p>',
                    '<p>Le doyen de la ville, un chef respecté et écouté, m\'a simplement serré la main en sortant dans l\'Outre-Monde. Incrédule, j\'allais lui proposer ma gourde quand son sourire ainsi que la crosse de son précieux revolver m\'ont fait comprendre qu\'il s\'agissait d\'un « Adieu ». Vingt minutes après l\'avoir perdu de vue, huit détonations se firent entendre. Puis le silence revint sur ce paysage de désolation.</p>
				<p>J\'ai passé le reste de ma journée à fixer des planches et des barbelés, mécaniquement. En vérifiant mes travaux, je me suis rendu compte que tout était bien solidifié... sauf qu\'aucune fixation ne retenait l\'ensemble au sol sableux. Je crois que je me suis mis à rire, nerveusement.</p>',
                    '<p>Ce sont là mes derniers écrits. Mon journal de bord, commencé le jour où j\'avais trouvé refuge dans cette construction de misère, au milieu de cette communauté se sachant condamnée à l\'avance, a disparu la nuit dernière. Comme présage que notre passage dans ce monde n\'est qu\'illusion.</p>
				<p>Cette nuit, je ne vais pas me rendre sans combattre. Parce que la vie est un combat perpétuel et qu\'arriver au bout du chemin, on cherche encore et toujours un échappatoire.</p>
				<p>Je regarde une dernière fois à l\'horizon. Je dois fermer les Portes.</p>
				<p>La mort n\'est pas une fin, juste un commencement...</p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "typed",
                "chance" => "10",
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
                "design" => "typed",
                "chance" => "8",
            ],
            "utpia1" => [
                "title" => "Un brouillon",
                "author" => "anonyme",
                "content" => [
                    '<p><s>ce ty</s>pe disait sûrement vrai. Coordonnées possibles vers <s>210</s>125 nord 210 ouest. </p>
                <p>à faire :</p>
                <ul>
                <li>véhicule (fouiller le parking au nord)</li>
                <li>e<s>au (15 litres)</s></li>
                <li>provisions (chez Bretov<s>ff</s>, gaffe à l\'infection)</li>
                <li>la "Citadelle" ? c\'est quoi au juste&nbsp;??</li>
                </ul>
                <p>Il faut <strong>trouver la route <s>17 </s>71</strong>.</p>
                <p>le cor<s>be</s>au ???!? qui c\'est ? <s>trouver qui c\'est</s> FAUT LE BUTER</p>
                <blockquote><s>rendR</s>DV 16h !!!<strong>!!!!</strong></blockquote>
                <p>trouver <strong>CITADELLE</strong></p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "small",
                "chance" => "4",
            ],
            "guide_fr" => [
                "title" => "Un.. 'guide' pour zombie ?!",
                "author" => "ChaosLink",
                "content" => [
                    '<div class="hr"></div>
                <h1>Guide de non-vie</h1>',
                    '<small>[ Vous ignorez quel genre de détraqué a bien pu rédiger un manuel à l\'intention d\'un mort-vivant... ]</small>
                <p>Bo<strong>njou</strong>r !</p>
                <p>Si vous avez ce manuel dans vos mains, cela veut dire que vous avez officiellement décédé et que vous avez alors ressuscité à cause d\'un problème d\'ordre technique.</p>
                <p>Mais n\'ayez crainte, en attendant votre mort définitive et votre accès au repos éternel, nous vous guiderons tout le long de votre non-vie afin qu\'elle se passe dans les meilleurs conditions possibles ! Vous trouverez dans ce manuel tout les renseignements nécessaire sur l\'entretien de votre corps afin de ralentir la décomposition et le garder en un seul morceau, l\'utilisation de votre (reste de) cerveau, les meilleures cachettes pour échapper aux militaires,</p>',
                    '<p>ainsi que tous les détails utiles sur la chasse aux humains pour vous nourrir et assurer votre suprématie sur l\'humanité.</p>
                <p>Bien évidemment, il y aura aussi des informations sur la meilleure façon de choisir sa viande, sur les techniques les plus connus pour paraître effrayant et rendre les humains inoffensifs contre vous ainsi que la localisation des concentrations humaines les plus probables. N\'oubliez pas qu\'un bon humain, est un humain mort.</p>',
                    '<p>Vous n\'avez plus qu\'à lire les pages suivantes maintenant pour vous préparez à votre non-vie et espérons que celle-ci se déroulera sans grand problème jusqu\'à la perte totale de votre cerveau. Et souvenez-vous de notre proverbe : </p>
                <p><em>« Toutes les routes mènent à la mort ; la votre aide celle des autres »</em></p>'
                ],
                "lang" => "fr",
                "background" => "printer",
                "design" => "typed",
                "chance" => "1",
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
                "design" => "written",
                "chance" => "10",
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
                "design" => "small",
                "chance" => "7",
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
                "design" => "typedsmall",
                "chance" => "10",
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
                "design" => "classic",
                "chance" => "10",
            ],
            "night2" => [
                "title" => "Une nuit dehors",
                "author" => "mrtee50",
                "content" => [
                    '<p>Une foutue nuit dehors, voila ce qui m\'attend. On m\'avait prévenue avant de sortir." Prend de quoi te défendre ou on te reverra pas ce soir". Thomas, le mec le plus méprisable de cette ville, avait même lancé un "c\'est moi qui prend ce qui reste dans son coffre!" avant même que je sois partie. Chouette ambiance. J\'ai pas pris d\'arme, j\'en voyais pas l\'utilité. Sauf que y\'a eu un hic, comme qui dirait. Je me suis retrouvée nez à nez avec une petite meute de siphonnés de la cervelle.</p>
                <p>Pas commode du tout ces types la. J\'ai même cru reconnaitre un ancien pote du village, Romain, sauf que c\'était pas vraiment lui. Il lui manquait un oeil et je crois que le seul truc qu\'il voulait c\'était de me bouffer le bras.</p>',
                    '<div class="hr"></div>
                <p>J\'ai du fuir. Mauvaise idée. Me v\'la avec un trou dans l\'pied.</p>
                <p>Je peux plus rentrer maintenant. Me reste plus qu\'à prier l\'bon samaritain pour que ce soit rapide et sans douleur.</p>
                <p>A la rigueur, j\'préfère que ce soit Romain qui me bouffe, entre potes on s\'entraide. </p>'
                ],
                "lang" => "fr",
                "background" => "blood",
                "design" => "written",
                "chance" => "5",
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
                "design" => "written",
                "chance" => "6",
            ],
            "revnge" => [
                "title" => "Vengeance",
                "author" => "coctail",
                "content" => [
                    '<p>ils n\'auraient pas dû être méchants avec moi. Ils n\'auraient pas dû construire leur bâtiment, ils n\'auraient pas du me dire de ne pas boire aujourd\'hui, ils n\'auraient pas dû m\'empêcher d\'entrer dans la banque, ils n\'auraient pas dû se moquer. Voilà maintenant, voilà, ils n\'ont que ce qu\'ils méritent. Ils pensaient que j\'allais me suicider avec tout ce cyanure ? Ha ! On verra bien qui se suicidera quand ils boiront au puits !</p>'
                ],
                "lang" => "fr",
                "background" => "tinystamp",
                "design" => "typed",
                "chance" => "10",
            ],
            "nails" => [
                "title" => "Vis et écrous",
                "author" => "totokogure",
                "content" => [
                    '<p>Je ne comprend pas bien ce qui se passe dehors, mais je crois qu\'on m\'accuse de quelque chose.</p>
                <p>Pour l\'instant je suis encore à l\'abri dans mon taudis mais il semble que les plaignants soient chaque jour un peu plus nombreux... De quoi pourrait-on bien m\'accuser ? D\'avoir pris une vis et un écrou pour réparer ma tondeuse ? Je ne savais pas que c\'était si important... J\'en avais vu plein dans un carton alors je me suis dit que les gens n\'en avaient pas besoin et je me suis servi... Maintenant, j\'espère juste que je ne vais pas finir pendu à la potence. Ce serait une fin ironique... quand on sait que c\'est moi qui l\'ai construite.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "8",
            ],
            "whiti_fr" => [
                "title" => "Whitesoldier",
                "author" => "whitesoldier",
                "content" => [
                    '>
                <h1>whitesoldier</h1>
                <p>Chiffres insaisissables et acronymes,<br>
                Griffonnés sur une feuille au papier jauni ;<br>
                Enfants, fruits et produits d’une humeur cacochyme,<br>
                Altérant les courbes de ces lignes de gris.<br>
                Calcule sans relâche et vise la légende,<br>
                Classe tes lieux-dits, bourgs et villes, l’un après l’autre ;<br>
                Poursuis Lechouan et ses multiples commandes,<br>
                Sois son sac, ses bras et son merveilleux apôtre.<br>
                Mouton écossais, oncle de brebis galeuses,<br>
                Lance ton dé cent, ou tire la plus haute carte,<br>
                Sois communautaire mais monte la tronçonneuse.<br>
                Ecris la gazette racontant tes exploits,<br>
                Imprévus, décadents, indécents et hors-charte.<br>
                Ta trahison laissera ta méta sans voix.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "contam" => [
                "title" => "Zone contaminée",
                "author" => "coctail",
                "content" => [
                    '<div class="hr"></div>
                <h1><big>Zone contaminée.</big></h1>
                <h1>Prévoir protections externes complètes et rinçage chimique.</h1>
                <div class="other" style="text-align:right;">merde si j\'avais su</div>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "5",
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
                "background" => "printer",
                "design" => "typed",
                "chance" => "5",
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
                "design" => "typed",
                "chance" => "8",
            ],
            "xmasst_fr" => [
                "title" => "Conte de Noël",
                "author" => "whitetigle",
                "content" => [
                    '<h1>Joyeux Noël...</h1>
                <p>Il est des légendes qui restent gravées dans l\'inconscient collectif, des légendes qui réapparaissent d\'elles-mêmes sans crier gare. Et chaque légende sait s\'adapter à l\'air de son temps, transportant en elle même les germes de sa floraison, répondant aux désirs de ceux qui l\'invoquent.</p>
                <p>L\'univers des Hordes ne fait pas défaut à cet axiome. Et c\'est de façon très naturelle que fleurit l\'âme d\'une vieille histoire, d\'un vieux rite, d\'une vieille comptine d\'un autre temps ; d\'une époque dont on peine à retrouver des restes...</p>
                <p>Si vous tombez sur mon histoire, alors c\'est que tout n\'est pas perdu. C\'est que mes efforts n\'auront pas été vains. J\'ai tant à vous révéler sur l\'univers des Hordes. Mais d\'abord je vais me présenter...</p>',
                    '<p>Je m\'appelle Théophile HeyteBôts, je fais apparemment partie de la première vague d\'arrivants dans le monde des Hordes. Je suis tombé dans ce monde sans savoir comment ou pourquoi. Tout ce que je sais c\'est que nous sommes actuellement une quarantaine à être regroupés dans des reliquats de vieilles tôles qui nos servent d\'abris. Nous nous nommons pompeusement des « citoyens ». En effet, il n\'y a pas de chef dans notre communauté. Nous avons tous notre mot à dire. D\'ailleurs, personne ne s\'empêche de s\'exprimer. Il n\'est pas rare que certains mots passent mal et que des disputes se créent entre nous.</p>',
                    '<p>Mis à part ça, notre « ville » - un bien grand terme quand on parle en fait d\'un bidonville malsain où chacun se vide où bon lui semble - est perdue au milieu d\'un grand désert. C\'est « l\'Outre-Monde ». Et cet Outre-Monde est un endroit qui réserve bien des surprises. Plein de débris, de vieux bâtiments enfouis, c\'est une mine d\'or, pour peu que l\'on sache l\'explorer... Ou que l\'on veuille le faire... Car ce désert est peuplé de créatures malsaines au regard aussi insondable qu\'un abîme sans fond. Et ces créatures semblent être animées par un insatiable appétit pour la chair humaine. A tel point que tous les soirs, nous sommes obligés de nous terrer au milieu de nos montagnes de détritus pour échapper à leur infernale battue.</p>
                <p>Au cours de ces effroyables évènements, certains disparaissent, d\'autres deviennent fous de terreur...</p>',
                    '<p>Mais là n\'est pas le sujet de mes propos. Non. Si je vous écris, qui que vous soyez à avoir découvert mon message; si je vous écris c\'est pour vous mettre en garde contre l\'inconscient collectif qui anime les légendes et les adapte à son environnement. Car, sachez que déjà certains héros d\'anciennes histoires semblent refaire surface. En tout cas beaucoup sont prêts à croire en leur réapparition.</p>
                <p>Ainsi, au travers d\'événements singuliers, d\'objets trouvés on n\'hésite pas à invoquer d\'anciens contes, vestiges d\'une humanité depuis longtemps disparue.</p>
                <p>Et c\'est pourquoi après avoir retrouvé un vieux livre lors de mes fouilles, le mal a commencé. J\'ai eu le malheur d\'en parler, de partager cette légende avec mes concitoyens... Depuis c\'est le chaos.</p>',
                    '<p>Aussi, avant de me taire, car je sens que quelqu\'un approche, laissez-moi vous le dire : le père noël n\'existe pas.</p>
                <p><strong>N\'acceptez jamais les cadeaux qu\'on pourrait vous faire si jamais cette vieille coutume resurgissait.</strong></p>
                <p>Rappelez-vous : dans le monde des Hordes il vaut certainement mieux offrir que recevoir.
                </p><p><small>Théophile HeyteBôts</small></p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "small",
                "chance" => "1",
            ],
            "song1_fr" => [
                "title" => "Contine : SilverTub",
                "author" => "TubuBlobz",
                "content" => [
                    '<h1>SilverTub</h1>
                <h2>Tubu</h2>
                <p>Déjà une semaine passée dans cette fichue ville,</p>
                <p>Et voilà d\'jà que le cri des zombies nous a tous rendus débiles.</p>
                <div class="hr"></div>
                <p>Le corbeau nous annonce les décès dans sa gazette,</p>
                <p>Tous les soirs les zombies nous font une petite fête.</p>
                <div class="hr"></div>
                <p>Depuis sept jours nous sommes leurs invités d\'honneur,</p>
                <p>En mangeant vingt des notres ils nous ont inspirés la peur.</p>
                <div class="hr"></div>
                <h2>Silver</h2>
                <p>Quand les autres commencent à paniquer-hé-hé,</p>
                <p>Pour moi survivre ce sera pas compliqué-hé,</p>
                <p>Suffit d\'organiser une fausse expé-hé,</p>
                <p>Et fermer la porte pour être débarrassé...</p>',
                    '<h2>Tubu et Silver</h2>
                <p>Alors que les autres se feront dévorer comme des Apérikubs,</p>
                <p>Moi je serai là grâce à SilverTub.</p>
                <div class="hr"></div>
                <h2>Tubu</h2>
                <p>Douzième jour seul trois pros ont survécu,</p>
                <p>Cette nuit les zombies ne nous ont pas déçus.</p>
                <div class="hr"></div>
                <p>Ils ont traversé l\'eau et les piques dans le grand fossé.</p>
                <p>Au final ils ont défoncés la porte blindée.</p>
                <div class="hr"></div>
                <p>Pour certains serrer les fesses n\'a pas sufit,</p>
                <p>On n\'a retrouvé d\'eux que des morceaux de chaires rabougries.</p>',
                    '<h2>Silver</h2>
                <p>Quand les autres commencent à paniquer-hé-hé,</p>
                <p>Pour moi survivre ce sera pas compliqué-hé,</p>
                <div class="hr"></div>
                <p>Suffit d\'organiser des arnaques par MP-hé,</p>
                <p>Toucher le jackpot c\'est plûtot aisé...</p>
                <div class="hr"></div>
                <h2>Tubu et Silver</h2>
                <p>Alors que les autres se feront dévorer comme des Apérikubs,</p>
                <p>Moi je serai là grâce à SilverTub (bis).</p>
                <p>Auteur : TubuBlobz</p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "1",
            ],
            "sngsek_fr" => [
                "title" => "Contine des jours sans lendemain",
                "author" => "Sekhmet",
                "content" => [
                    '<br><br><br><br><br><br>
                <h1>Contine des jours sans lendemain</h1>',
                    '<p>Notre histoire commença dans cette ville dévastée,</p>
                <p>Quelques Hommes perdus, échappés à la mort,</p>
                <p>Et retrouvés ici, tentant de subsister,</p>
                <p>Un peu moins bien que mal, face à ce triste sort.</p>
                <div class="hr"></div>
                <p><strong>[Refrain :]</strong></p>
                <p>Y aura-t-il un demain ?</p>
                <p>Serai-je encore en vie,</p>
                <p>Seras-tu près de moi ?</p>
                <p>Verrons-nous le matin ?</p>
                <p>Nouveau jour de sursis,</p>
                <p>Reculant le trépas.</p>
                <div class="hr"></div>
                <p>Pas de place pour les faibles dans ce monde tourmenté,</p>
                <p>Il faut travailler dur, sans desserrer les dents,</p>
                <p>Continuer sans relâche, défendre la cité,</p>
                <p>Avec des pierres, du bois, de la sueur et du sang.</p>
                <div class="hr"></div>
                <p><strong>[Refrain]</strong></p>',
                    '<p>Car tous les soirs la Horde vient frapper à nos portes,</p>
                <p>Sans répit, inlassable, toujours plus affamée,</p>
                <p>Toujours plus nombreuse, cette maudite cohorte,</p>
                <p>N\'attend qu\'une seule chose, pouvoir nous dévorer.</p>
                <div class="hr"></div>
                <p><strong>[Refrain]</strong></p>
                <div class="hr"></div>
                <p>Mais la nature humaine, face à l\'adversité,</p>
                <p>Doit alors se souder, sinon elle dépérit.</p>
                <p>Coude à coude, côte à côte, sans se désespérer,</p>
                <p>Les Hommes continuent leur combat sans merci.</p>
                <div class="hr"></div>
                <p><strong>[Refrain]</strong></p>
                <div class="hr"></div>
                <p>Et plus la fin s\'approche, plus nous devenons fous,</p>
                <p>Car c\'est dans cet enfer que nous nous sommes aimés.</p>
                <p>Ô Mort qui nous rapproches, tu te ris bien de nous,</p>
                <p>Tu nous as réunis pour mieux nous séparer !</p>',
                    '<p><strong>[Refrain :]</strong></p>
                <p>Y aura-t-il un demain ?</p>
                <p>Je crois que c\'est fini,</p>
                <p>La vie s\'enfuit de moi.</p>
                <p>Pour ce dernier refrain,</p>
                <p>Pour cette dernière nuit,</p>
                <p>Une pensée pour toi. </p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "poem",
                "chance" => "10",
            ],
            "conti" => [
                "title" => "Contine des jours sans lendemain",
                "author" => "Prostipoulpe",
                "content" => [
                    '>
                <p>Un, deux, trois, de l\'eau je ne bois,<br>
                Quatre, cinq, six, je chasse les écrits,<br>
                Sept, huit, neuf, maintenant jmange mon oeuf,<br>
                Dix, onze, douze, une petite fouille piquouze,<br>
                Treize, quatorze, quinze, un teddy contre une guinze,<br>
                Seize, dix-sept, dix-huit, l\'ETL réussie,<br>
                Dix-neuf, vingt, vingt et un, jmarche avec entrain,<br>
                Vingt-deux, vingt-trois, vingt-quatre, des zombies à abattre,<br>
                Vingt-cinq, vingt-six, vingt-sept, twino, alcool, machette,<br>
                Vingt-huit, vingt-neuf, trente, mourir J1, une constante,<br>
                Trente et un, trente-deux, trente-trois, picto déshydratation encore une fois.<br>
                </p>',
                    'Cloaque étrange (1)
                <p>Disette chez la Horde : aucun des 35 zombies n\'a eu quoi que ce soit à se mettre sous la dent hier soir en ville, nos défenses ont bien tenu.</p>
                <p>Amusant, † Bistouflex n\'est toujours pas rentré(e) en ville depuis hier…</p>
                - Le Corbeau
                Morts en ville : Aucun !
                Autres Victimes (1) : Bistouflex'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "written",
                "chance" => "2",
            ],
            "regler_fr" => [
                "title" => "Regretté Regler",
                "author" => "Darkhan",
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
                "design" => "classic",
                "chance" => "2",
            ],
            "lawtab_fr" => [
                "title" => "Table de la loi",
                "author" => "Skatra",
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
                "design" => "modern",
                "chance" => "2",
            ],
            "than_fr" => [
                "title" => "Cartographie de citoyens ordinaires",
                "author" => "Than",
                "content" => [
                    '>
                {asset}build/images/fanart/worldmap01.jpg{endasset}'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "solen_fr" => [
                "title" => "Chasse aux Merveilles",
                "author" => "solenator",
                "content" => [
                    '>
                {asset}build/images/fanart/worldmap04.png{endasset}'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "porta_fr" => [
                "title" => "L'exil loin de Babylone",
                "author" => "Portative",
                "content" => [
                    '>
                {asset}build/images/fanart/worldmap02.png{endasset}'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "opal2_fr" => [
                "title" => "Memoriae verso",
                "author" => "Opaline",
                "content" => [
                    '>
                {asset}build/images/fanart/toutesdentsdehors.png{endasset}'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "hulurl_fr" => [
                "title" => "Hulurlements",
                "author" => "Walvyk",
                "content" => [
                    '>
                <p>Noir. Hurlements. Flash. Silence.<br>
                Je m\'éveille et regarde autour de moi.<br>
                Flou. Sang. Brouillard. Cadavres.<br>
                J\'essaye de me remémorer les événements de la veille.<br>
                Zombies. Beaucoup. Destruction. Peur.<br>
                Ils s\'étaient engouffrés dans une brèche de la muraille.<br>
                Un. Dix. Cent. Mille. Trop.<br>
                On n\'avait pas pu les repousser tous, il y en avait tellement.<br>
                Veilleurs. Carnage. Désolation. Terreur.<br>
                J\'étais le dernier, le dernier en vie.<br>
                Famille. Proches. Voisins. Tristesse.<br>
                Je devais organiser ma survie.<br>
                Pillage. Stockage. Barricades.<br>
                J\'eu fini au moment ou le soleil se couchait.<br>
                Survie. Espoir. Avenir.<br>
                Ils arrivaient, je les entendaient, ils étaient là tout près.<br>
                Minuit. Râles. Noir.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "exp2_fr" => [
                "title" => "Journal d'un chercheur",
                "author" => "Exdanrale",
                "content" => [
                    '<p><small>Un petit journal très ancien, vous avez peur qu’il tombe en lambeaux si vous essayez de l’ouvrir. Le nom de l’auteur est effacé... Peut-être un témoignage des temps anciens ?</small></p>
                <h2>Mercredi 26 septembre 1963</h2>
                <p>Aujourd’hui nous sommes sortis pique-niquer avec Sam et les enfants. Cela fait du bien de sortir un peu du labo. Toutes ces blouses blanches, à longueur de temps, cela fatigue... Voir un peu de verdure ne peut que me faire du bien et je pense avoir besoin de tout moyen de décompresser avant l’expérience de la semaine prochaine. Ce sera l’aboutissement de nos recherches, un moyen que le monde nous croit finalement... Nous leur offrons l’immortalité...</p>',
                    '<h2>Vendredi 28 septembre 1963</h2>
                <p>Hier, John a protesté contre l’expérience, clamant nos méthodes « inhumaines ». Il hurlait « et si cela ne marchait pas ? Et si quelque chose tournait mal ? ». Nous ne pouvons nous permettre de douter... Aujourd’hui, John a l’air en retrait, il est bien loin de son enthousiasme révolutionnaire d’hier...</p>
                <h2>Samedi 29 septembre 1963</h2>
                <p>John n’était pas présent aujourd’hui... C’est étrange, en 5 ans de bons et loyaux services, il n’a pas raté une seule journée de travail. Et si ? Non, pas de doute permis. Je m’étais dis que je passerai chez lui après le travail mais je n’en ai pas eu le temps. Demain, je l’espère !</p>',
                    '<h2>Dimanche 30 septembre 1963</h2>
                <p>Exceptionnellement on nous a convoqués au labo aujourd’hui... C’est compréhensible, nous devons nous assurer que tout est prêt pour l’expérience ! Nous allons enfin passer à des sujets vivants, c’est un grand moment et la pression est palpable parmi nous... Si tout fonctionne, nous serons considérés comme des dieux... Mais si quelque chose venait à tourner mal... Non, tout ira bien. Le pessimisme n’est pas de rigueur.</p>
                <p>Après le travail, je suis allé chez John. Arrivé à sa porte, j’ai entendu des espèces de sombres grognements. J’ai frappé, une fois, deux fois, trois fois, pas de réponse. La porte était ouverte, je suis donc entré vérifier que tout allait bien. </p>',
                    '<p>Sa femme était assise à la table du salon, me tournant le dos. <em>A l’aide... </em>Je m’en approchais, l’interpellant, sans réponse. </p>
                <p>Alors que je posais ma main sur son épaule, sa tête se retourna soudainement avant de se détacher de son corps et de tomber à terre... </p><p>D’effroi, je bondis en arrière et trébuchai sur le tapis. Arrivé au niveau du sol, je vis ses deux enfants, le corps lacéré par terre, la chair de leur visage arrachée et un vaste trou dans le crâne. Je me relevai <em>..ma tête...</em> et pris la direction de la porte en courant quand j’aperçus John.</p>',
                    '<p>Il avait les yeux vitreux, la peau grisâtre, les traits tirés et était recouvert de sang. Il s’approchait de moi lentement. <em>J’ai si faim...</em> J\'étais paralysé. Arrivé à distance, il m\'empoigna et sa bouche se précipita vers mon épaule qu\'il mordit avec force. <em>J\'ai si froid...</em> Dans un cri de douleur, j\'attrapais un chandelier qui traînait sur la table et le frappait avec force à la tête. <em>J\'ai si mal...</em> Il s\'affala alors que je sortais en trombe de chez lui, me dirigeant chez moi pour me soigner.</p>
                <p>Dans la soirée, mes blessures - pas si profondes que cela - pansées <em>J\'ai si faim...</em> Je, <em>faim,... Sam approche..</em> me touche le cou. <em>J\'ai faim...</em> Tout est bientôt fini...</p>
                <small>Le reste du journal est maculé de sang.</small>
                <p>Auteur : Exdanrale</p>'
                ],
                "lang" => "fr",
                "background" => "old",
                "design" => "typed",
                "chance" => "1",
            ],
            "immot_fr" => [
                "title" => "Journal d'un immortel",
                "author" => "Vertoss",
                "content" => [
                    '>
                <h1>Journal d\'un immortel</h1>
                <p>Cette fois, mon appareil photo n\'a pas fonctionné.<br>
                Heureusement, j\'avais ce matériel de peinture en réserve. Dans un monde si éphémère, j\'aime immortaliser les choses pour ceux qui passeront après moi.<br>
                Je trempe encore mon pinceau dans le zombie que l\'individu a tué pour moi. La peinture moderne...<br>
                Voilà l\'homme, qui prend la pose, vaniteux, qui m\'urge de me dépêcher. Il est entré dans une fureur noire devant mes gesticulations, croyant que je ne voulais plus honorer ma part du contrat.<br>
                J\'ai un sujet bien plus intéressant désormais, puisqu\'il ne m\'a pas écouté. J\'ai intitulé ce futur tableau : "nature morte".<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "dorme_fr" => [
                "title" => "Le dormeur de l'Outre-Monde",
                "author" => "LArverne",
                "content" => [
                    '>
                <h1>Le dormeur de l\'Outre-Monde</h1>
                <p>
                C’est un désert immense où plane quelques oiseaux,<br>
                Accrochant follement aux herbes des haillons,<br>
                Cette étendue sans fin a tout d\'une prison,<br>
                Et son ciel étincelant est le règne des corbeaux,
                </p>
                <p>
                Un jeune habitant, bouche ouverte, tête nue,<br>
                Et la nuque baignant dans la chaude rocaille ,<br>
                Dort, il est étendu dans le sable, sous la nue,<br>
                Pâle, dans son lit désertique où la chaleur l\'assaille,
                </p>
                <p>
                Les pieds dans le talus, il dort, souriant comme<br>
                Sourirait un enfant malade il fait un somme,<br>
                Désert, berce-le chaudement : il a froid.<br>
                </p>
                <p>
                Les parfums ne font pas frissonner sa narine,<br>
                Il dort dans le soleil, la main sur sa poitrine,<br>
                Tranquille, il a une morsure, au côté droit.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "enfer_fr" => [
                "title" => "L'enfer, c'est les autres",
                "author" => "Marmowa",
                "content" => [
                    '>
                <p>
                Ca pue la merde.<br>
                Littéralement.<br> 
                J\'ai du prendre le dernier lit de camp en ville, celui à côté de la fosse communautaire. <br>
                Des mecs jouent les chefs.<br> 
                Tranquille. <br>
                Un peu trop. <br>
                </p>
                <p>
                Quelque part, cette solidarité soudaine entre des gens paumés, ça me débecte...<br> 
                C\'est que du flan, du vent, une fumée sans feu ! <br>
                Dès que ça sentira le roussi, y aura plus un rat. <br>
                Et ceux qui donnent des ordres, des directives, ils espèrent quoi, hein ? <br>
                De la reconnaissance ? Vivre ? Réchauffer les cœurs à la chandelle de l\'amitié ? <br>
                </p>
                <p>
                Conneries. On va tous crever. Ils chient de peur, dans la fosse, là, ils y mettent toutes leurs peurs intestinales.<br> 
                Je vois, leurs grimaces de douleur, le bruit de leurs intestins... Je vais les faire cramer de l\'intérieur... Leur donner une raison d\'avoir mal au bide...<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "noyel_fr" => [
                "title" => "Lettre du papa noël",
                "author" => "PatrickLaPastek",
                "content" => [
                    '>
                <p>Vous venez de trouver ce qu\'il reste d\'un petit journal rouge cramoisi et... blanc ? Le peu de pages qu\'il contient pourrai peut-être vous apprendre quelque chose…</p>
                <h3>Le journal du père noël</h3>
                <p>Dimanche 23 Décembre 2018<br>
                Tout est prêt pour cette année ! Les sucreries sont bien emballés, les rennes sont prêts, les cadeaux prêt par millier..<br>
                J\'espère que cette fois ci, tout se passera bien !<br>
                </p>',
                    'Lundi 24 Décembre 2018<br>
                Petit problème technique avec Rodolf et agitation certaines chez nos petits lutins..<br>
                Tout ce passera bien !<br>
                Jeudi.<br>
                JE LES DÉTESTE TOUS !!! JE LES HAIS ! JE LES HAIS !! IL VONT ME LE PAYER !!!!!<br>
                IL ME LE PAYERONS TOUS !!!!!!!<br>
                [La suite est illisible.. ]
                <p></p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "manif_fr" => [
                "title" => "Manifeste",
                "author" => "NabOleon",
                "content" => [
                    '>
                <h1>Manifeste pour un monde sans piles broyées</h1>
                <p>Citoyens, Citoyennes !<br>
                Pensez à votre propre avenir : Respectez le monde que vous lèguerez aux zombies.<br>
                Ne laissez pas traîner partout les piles broyées :<br>
                -elles polluent le désert<br>
                -elles détériorent les cadavres comestibles<br>
                -elles heurteront votre future sensibilité de zombie en évoquant de mauvais souvenirs<br>
                ...<br>
                </p>',
                    'Depuis la loi TWIN2.1267.1 du 24 février 2014, les maires sont tenus de proposer une solution pour la collecte et le recyclage des piles broyées.<br>
                Ramenez-les en ville<br>
                Déposez-les en banque<br>
                Exigez de vos dirigeants une solution de recyclage.<br>
                Sinon, recyclez vos dirigeants ! (c\'est toujours utile)<br>
                C.R.I.E.Z<br>
                (Comité Révolutionnaire pour une Initiative Ecologique Zombie)<br>
                <p></p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "opal1_fr" => [
                "title" => "Memoriae recto",
                "author" => "Opaline",
                "content" => [
                    '>
                <h3>Memoriae</h3>
                <p>Du sol elle s\'est épanouie<br>
                Toutes dents dehors<br>
                Rouille et sang,<br>
                Témoins d\'un autre temps<br>
                Dans les grains agglutinés j\'ai vu<br>
                J\'ai vu cette main généreuse<br>
                que j\'avais imaginée<br>
                Dans le chant du silice contre le métal<br>
                J\'ai entendu<br>
                entendu Sa Voix.<br>
                </p>
                <p>J\'ai accueilli le signe offert
                Gouté le moment
                dansé mon plaisir
                et partagé son Don.
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "grosei_fr" => [
                "title" => "Regrettée Groseille",
                "author" => "NeCa",
                "content" => [
                    '>
                <h1>A ma groseille bien aimée</h1>
                <p>Cela faisait depuis des mois,<br>
                Que je n\'avais pas revu mon chat.<br>
                Mais lorsque la faim me vint<br>
                Et que je vis cet énorme serpent,<br>
                Je pris mon couteau à deux mains<br>
                Et l\'éventrai instantanément.<br>
                Immense fut ma surprise<br>
                Quand surgit ma belle chatte grise.<br>
                Je voulus la prendre dans mes bras<br>
                mais furieuse elle esquiva.<br>
                Elle sauta pour me griffer<br>
                Mais atterrit sur les zombies.<br>
                A ce moment, je réalisai<br>
                Qu\'elle m\'avait sauvé la vie.<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "seven" => [
                "title" => "Sept jours pour en finir",
                "author" => "csbilouze",
                "content" => [
                    '>
                <p>Au commencement Dieu créa la terre.<br>
                - Le premier jour, Dieu créa la lumière, séparant ainsi le jour et la nuit.<br>
                - Le second jour, Dieu créa le ciel<br>
                - Le troisième jour, Dieu créa la végétation<br>
                - Le quatrième jour, Dieu créa les animaux<br>
                - Le cinquième jour, Dieu créa l\'homme à son image.<br>
                - Le sixième jour, l\'Homme créa les zombies.<br>
                - Le septième jour, Dieu dit: "débrouillez-vous"<br>
                </p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "meteo1" => [
                "title" => "Rapport météo d'Oraefajökull",
                "author" => null,
                "content" => [
                    '<h2>Rapport de la station météorologique d\'Oraefajökull (Islande), 3 décembre :</h2>
                    <p>Depuis maintenant plus de 3 semaines nous n\'observons plus aucunes précipitations dans la région, chose fortement inhabituelle en cette période de l\'année.</p>
                    <p>La station, qui tous les ans est dans la liste des plus touchées par les intempéries d\'hiver, n\'est survolée que par de rares cumulus fractus (premier stade de la formation des cumulus), censés annoncer une pluie prochaine...</p>',
                    '<p>L\'épaisseur de la couche d\'ozone se rétracte de jours en jours et les effets du réchauffement climatique sur les cultures et la population de l\'île commencent à se faire sentir. Le bétail commence à présenter des signes de faiblesse et la prolifération des rats au sein des villes devient inquiétante.</p>
                    <p>Nos anémomètres couplés aux thermomètres mettent en évidence un courant d\'air chaud venu du Nord de l\'Afrique ayant pour conséquences la dispersion des nuages ainsi qu\'un impact non négligeable sur la faune et la flore de l\'île.</p>'
                ],
                "lang" => "fr",
                "background" => "secret",
                "design" => "typed",
                "chance" => "1",
            ],
            "fleur" => [
                "title" => "Petite fleur démembrée",
                "author" => "ninjaja",
                "content" => [
                    '<p>Ci-gît Marguerite.<br>
                    Les zombies l\'ont aimée, un peu, beaucoup, passionnément, ...<br>
                    Mort digne d\'une passionnée de puzzles.<br>
                    </p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "2",
            ],
            "Epita" => [
                "title" => "Épitaphe",
                "author" => "Panda",
                "content" => [
                    '<p>Il est mort comme il a vécu, en râlant et errant.</p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "Epitb" => [
                "title" => "Ci-git un sacré Bonhomme...",
                "author" => "Wolverikkk",
                "content" => [
                    '<p>Furie61, reposes ici en paix !<br>
                    Ta longue errance est final\'ment terminée.<br>
                    Au cours de ta vie tu auras tout essayé...<br>
                    Quand t\'etais eclaireur, ta capuche sautait.<br>
                    T\'as dressé un bichon, Il t\'a mordu le pied.<br>
                    T\'as pris un bouclier, et oublié d\'veiller.<br>
                    Technicien dans les ruines, tu ressortais blessé.<br>
                    T\'as meme bu de la flotte en etant goulifié !<br>
                    T\'as fini comme ermite... ton bouquin a foiré...<br>
                    Moi j\'crois bien que fouineur... ta pelle aurait pété...<br>
                    </p>'
                ],
                "lang" => "fr",
                "background" => "carton",
                "design" => "poem",
                "chance" => "2",
            ],
            "Epitc" => [
                "title" => "L'expédition de Noël",
                "author" => "Faucha",
                "content" => [
                    '<p>C\'était il y a bien longtemps, un peu après l’Épiphanie, alors que quarante survivants s\'apprêtaient à faire une grande fête et à admirer de grands feux d’artifice, un grand corbeau vint se poser au sommet de la tour de guet et laissa tomber une missive.</p>
                    <p>La petite Manech s\'approcha, ramassa la lettre et la lut. C\'était un message de Deepnight et de kiroukou. Ils annonçaient la chute imminente de la ville.<br>
                    C\'est alors que tous les chantiers disparurent, il ne restait plus que des plans parsemés dans les quatre directions du scrutateur.<br>
                    Les survivants demeurèrent de glace au milieu du désert. On entendit soudain les cris de Callypige, tout le monde crut qu\'il lançait une malédiction.<br>
                    En réalité, il venait juste de se coincer le pouce entre deux planches tordues alors qu\'il consolidait le grand fossé.<br>
                    La petite Manech comprit que la situation était grave, la ville allait droit à sa perte. <br>
                    Elle prit une ration d\'eau, un paquet de chips molles, un coupe-coupe et se dirigea, seule, à l\'ouest de la ville. Elle entra dans un supermarché qui avait déjà été pillé, elle n\'y trouva qu\'une liasse de billets.<br>
                    Après avoir parcouru un long chemin, elle croisa la route de deux Oracles. Elle leur demanda leur aide pour savoir comment se défendre contre les zombies, le premier lui répondit « lol », le second lui remit en échange de sa liasse de billets un ouvre-boite. Manech continua son tracé. Elle dut affronter une meute de zombies qu\'elle massacra, deux par deux à l\'aide de son coupe-coupe. Elle arriva enfin à l\'entrée d\'un bunker abandonné, obstruée par une table Järpen sur laquelle était assis un gardien fondant comme neige au soleil. Il s\'était perdu dans l\'immense désert.<br>
                    La petite Manech lui proposa aussitôt de le raccompagner en ville, il leur suffisait de suivre le tracé en vert du Grand Frère.<br>
                    En chemin, ils rencontrèrent Bistouflex qui mourrait de soif, ils lui offrirent un café, trouvé en route, malheureusement, la caféine ne remplace pas l\'eau, Bistouflex n\'arriva jamais à bon port.<br></p>
                    Les deux expéditionnaires furent acclamés par les autres habitants à leur retour : après la lecture de quelques plans déterrés aux alentours, le technicien Xemaro était formel, la table Järpen qu\'ils venaient de rapporter leur permettrait d\'améliorer leurs défenses.<br>
                    Les citoyens voulaient fêter la bonne nouvelle, c\'est alors que le fouineur basstien revint avec le sac de feu Bistouflex et proposa pour célébrer Noël Oublié une partie de boules de sable. <br>
                    Tout le monde participa et découvrit bien vite que les défenses ne pansent pas les blessures.<br>
                    Le fouineur, quant à lui, commençait déjà à se barricader dans sa maison après avoir dérobé les paracétoïdes de la pharmacie commune...<br></p>'
                ],
                "lang" => "fr",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            /**
            * SPANISH ROLE PLAY TEXTS
            */
            "acorr_es" => [
                "title" => "Acorralado",
                "author" => "GeneralCross",
                "content" => [
                    '<p>Estoy arrinconado por los zombis, ellos avanzan a lentos pasos, los oigo venir.</p>
                <p>Estoy refugiado tras unas rocas, más cuando lleguen me morderán, me devorarán y tras un insoportable dolor, me volveré uno de ellos.</p>
                <p>Esta nota será lo único que darán cuenta de que existí, mi familia fue devorada hace mucho.</p>
                <p>No hay nadie que pueda extrañarme, así que tomaré el bolígrafo y romperé mi garganta, será menos doloroso que ser devorado.</p>
                <h1>Espero que encuentren esta nota, espero que sepan que existí.</h1>'
                ],
                "lang" => "es",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "adio_es" => [
                "title" => "Adiós",
                "author" => "Eragon96",
                "content" => [
                    '<p>A lo largo de estos días he observado cosas horribles.
                He visto amigos que se traicionan.
                Ahora ni siquiera puedo confiar en mi sombra. </p>
                <p>Si alguien quiere regresar a como era antes, está soñando, no pasará. Tienen que aceptar la realidad... </p>
                <p>Solo nos queda sobrevivir.</p>
                <p>Hoy salí y me quedé sin energía.</p>
                <p>Les pediría ayuda pero ellos no tratarán de salvarme.</p>
                <p>Tomaré cianuro, no quiero ser como ellos.</p>
                <p>Espero que alguien lea esto:</p>
                <h1>La unión hace la fuerza. No lo olviden.</h1>'
                ],
                "lang" => "es",
                "background" => "carton",
                "design" => "written",
                "chance" => "2",
            ],
            "bioz1_es" => [
                "title" => "Biología de los Caminantes- Parte 1/4",
                "author" => "Dr. Arthail Thredaral",
                "content" => [
                    '<h1>Biología de los Caminantes: <br> Lt. Dr. Arthail Thredaral</h1>
                <small>7 de Octubre de 2027</small>
                <h2>Preámbulo:</h2>
                <p>Este es el resumen sintético y breve realizado a partir de las notas y los informes de investigación del estudio de campo recuperado por el Departamento de Control de Enfermedades del Comité Especial de Emergencias Sanitarias de la ONU, realizado sobre especímenes afectados por el Síndrome de Postmortem o como se dice popularmente "Zombificados", antes y después de su defunción y reanimación.</p>
                <p>Las pruebas no son concluyentes ya que no se conservan más registros con los que contrastar estos datos, sobre todo de carácter contextual al más que probable conflicto armado a escala mundial que tuvo lugar en algún momento entre el año 2012...</p>',
                    '<p>...y el 2015 y finalizó con un colapso de las principales organizaciones y gobiernos mundiales hacia el 2016.</p>
                <p>Síntesis general de la anatomía del caminante:<br>
                El Síndrome de PostMortem confiere ciertos trastornos anatómicos muy característicos en la raíz biológica de una víctima del mismo.<br>
                A saber, los cambios sintomáticos más acuciantes nada más contraído el síndrome son la desorientación, un desorden hormonal grave acompañado de factores como el aumento de la temperatura rápidamente generando fiebres que llegan a ser incapacitantes, sudoración deshidratante a partir de las 5 horas en todos los casos, debilidad articular, mareos, vómitos, dolor generalizado en todo el sistema nervioso y aceleración del pulso por encima de lo médicamente posible.</p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "6",
            ],
            "bioz2_es" => [
                "title" => "Biología de los Caminantes- Parte 2/4",
                "author" => "Dr. Arthail Thredaral",
                "content" => [
                    '<h2>Parte 2:</h2>
                <p>Estas reacciones tan violentas suelen llegar a un punto critico a partir del cual van descendiendo y el sujeto pasa por un breve estado de lucidez (similar al del mal por radiación) en el cual recupera parte de la consciencia y el control, rápidamente acompañado de una necrosis acelerada, comenzando por los órganos internos hasta las extremidades y los órganos sensoriales.</p>
                <p>A las 8 horas de contraer el síndrome, el 99,98% de los sujetos ha entrado en un coma irreversible y a muerto por necrosis cerebral e insuficiencia cardiorrespiratoria en menos de un minuto.</p>
                <p>El proceso de reanimación se ha observado extraño y difícil de catalogar… En algún punto indeterminado, el agente activo del síndrome reacciona con la química de las neuronas motrices y sensoriales y las obliga a auto consumirse, generando calor y descargas eléctricas que despiertan el sistema nervioso motriz e hipotalámico.</p>',
                    '<p>La necrosis amaina repentinamente y el cadáver experimenta unos temblores violentos durante unos segundos de duración variable de caso a caso.</p>
                <p>Al cabo de unos minutos el cadáver inexplicablemente recobra la consciencia y la autonomía motriz con acusada torpeza debido a la acelerada necrosis de los músculos y los huesos, con señaladas carencias de equilibrio y coordinación pero con un excelente y afinadísimo a la par que inexplicable sentido de la orientación, como si viera mejor a oscuras que con luz y todos sus sentidos estuvieran hipersensibilidades. </p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "5",
            ],
            "bioz3_es" => [
                "title" => "Biología de los Caminantes- Parte 3/4",
                "author" => "Dr. Arthail Thredaral",
                "content" => [
                    '<h2>Parte 3:</h2>
                <p>La más que conocida hostil agresividad de los caminantes por todo ser vivo, especialmente los humanos es imposible de analizar desde el campo de la psicología y parece responder a unos patrones inquisitivos más emparentados con las asociaciones básicas de un animal primitivo como el instinto de alimentación y territorialidad, según el cual, siempre que un ser humano sea captado por la atención de un caminante, este responderá con agresividad extrema y con una violencia total, intentando matar y devorar a todos los humanos que encuentre (en los estudios prácticos de campo, se ha demostrado además que los sujetos reanimados acaban con la vida de los ``voluntarios´´ de prueba en el 78% de los casos y estos son infectados por el agente activo del síndrome PostMortem en el 100% de los casos), el resto de este estudio pertenece a otro campo.</p>
                <p>Síntesis general de la fisiología del caminante:<br>
                La ciencia tiene difícil hallar una explicación racional a porque los sujetos con el Síndrome de PostMortem, tras su reanimación son tan susceptibles a la combustión espontánea si se les aplica una cantidad de H2O pura, lo que acaba por concluir en el deceso del sujeto (si es que el término óbito es aplicable).</p>
                <p>Una explicación, que no exenta de cierta ironía encaja con la posibilidad de que este síndrome sea consecuencia y no causa de una Guerra Nuclear, es su coincidencia con ciertos factores ambientales que si aplicamos el contexto de una guerra Nuclear dan las condiciones necesarias.</p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "4",
            ],
            "bioz4_es" => [
                "title" => "Biología de los Caminantes- Parte 4/4",
                "author" => "Dr. Arthail Thredaral",
                "content" => [
                    '<h2>Parte 4:</h2>
                <p>La explicación a porque el agua es el catalizador más eficaz para cualquier reacción química contra la integridad de un caminante (acabar con ellos) es que su cuerpo- como consecuencia del subproducto radioactivo del agente del Síndrome Postmortem en el sistema nervioso, que también explica la rápida necrosis previa a la muerte del paciente- segrega pequeñas cantidades de compuestos similares a los metales alcalinos (Magnesio, Cesio, Sodio, etc.) que son altamente inflamables al contacto con el agua. </p>
                <p>Pero el dato que nos parece más aproximado es la posibilidad de una cantidad inferior aun de Fr (Francio) mezclado con la grasa corporal del caminante como reacción ambiental con el aire y la degradación de los tejidos necrosados.</p>',
                    '<p>El francio es un metal alcalino altamente radioactivo y altamente reactivo con el agua, aunque este estudio comprende la teoría del francio en un ámbito estadístico, puesto que no se llegaron hacer pruebas químicas moleculares por imposibilidad material y personal, parece ser la prueba que explica el porqué los caminantes son destruidos con agua.</p>
                <p>Estos datos concluyen el análisis biológico de los efectos del Síndrome de PostMortem en los pacientes afectados.</p>
                <p><small>Dr. Arthail Thredaral - Jefe de Análisis de campo del DCE-CEES</small></p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "3",
            ],
            "dest" => [
                "title" => "Diario de un desterrado",
                "author" => "Mellaa",
                "content" => [
                    '<p>Es el tercer día desde que volví infectado del Ultramundo y los aldeanos decidieron echarme... ¡Malditos desgraciados! (la escritura se vuelve ilegible...)</p>
				<p>Si alguien lee esto seguramente ya seré uno de esos que arañan las murallas cada noche, o quizás lo que quede de mi cuerpo esté atrapado entre los alambre de púas... si todavía me quedan dedos, dale mi anillo a mi hija Ireth... (las lagrimas emborronan el resto del papel).</p>'
                ],
                "lang" => "es",
                "background" => "letter",
                "design" => "written",
                "chance" => "1",
            ],
            "divv1_es" => [
                "title" => "Diario de un Moribundo",
                "author" => "Arkham_Stranger",
                "content" => [
                    '<p>La mayoría de veces prefiero ocultarme en aquel cuchitril que construí hace un día, con ayuda de unas cosas que tomé del almacén del pueblo, que estaba ya construido antes de los atroces eventos que desencadenarían el fin de nuestra miserable existencia.</p>
                <p>Ya han pasado 4 días desde que pude ver a Joaquín, un buen amigo mío que vivía en la casa de al lado, mientras construía mi cuchitril vi que salía del pueblo, pero ahora dudo que siga con vida, me entristece no haber podido despedirme de él.</p>
                <p>Somos tan sólo un puñado de hombres, no quiero morir, quiero tener una familia, casarme, poder tener nietos, la vida de una persona normal... Ahora dudo que eso sea posible, el objetivo típico de una persona se ha vuelto un sueño utópico.</p>',
                    '<p>Organicé las porquerías que hay en mi pequeña vivienda, espero salir en unas horas con un grupo de vecinos a buscar recursos en el desierto, ahora he escuchado que le llaman Ultramundo, qué desidia...</p>
                <p>Tenía el reloj de un tipo muerto, deben ser las seis de la tarde. Encontrarás mi cuerpo sin vida, a medio devorar, fui un idiota, y tu morirás también, tu familia. ¡Todos morirán, no habrá nadie más!</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "3",
            ],
            "d4deb_es" => [
                "title" => "Día 4 tras la debacle",
                "author" => "FurbyVikingo",
                "content" => [
                    '<p>Después de despedirnos de los últimos habitantes que quedaban en el pueblo hemos decidido buscar el modo de salir de este condenado desierto sin apenas recursos.</p>
                <p>Comienza a cundir un poco el pánico y algunos de los miembros de la expedición comienzan a tener náuseas y mareos por deshidratación. Hemos encontrado algunos zombies sueltos y hemos podido acabar con ellos, aunque nuestro líder ha resultado herido.</p>',
                    '<p>Tras varios días andando sin rumbo en el desierto, estoy convencida de que no hay escapatoria. Hemos podido huir de alguna turba de zombies, pero estamos sin agua no llegaremos muy lejos. Mirando cara a cara a algunos de esos zombies, ¡nos hemos dado cuenta de que eran antiguos miembros de nuestra expedición!</p>'
                ],
                "lang" => "es",
                "background" => "stamp",
                "design" => "typed",
                "chance" => "3",
            ],
            "dia_es" => [
                "title" => "Día 7",
                "author" => "maxidutre",
                "content" => [
                    '<p>Estoy en una casa abandonada en el desierto, he visto una horda de zombis por el sur acercándose...</p>
                <p>Por favor envíen refuerzos a esta ubicación, ¡háganlo rápido!, estoy escribiendo esta carta y no tengo tiempo de contar todo lo horrible que he visto hasta ahora, por favor ¡rápido envíen a alguien!</p>
                <p>Díganle a mi vecina de la casa que yo siempre la... la... a.. am... mé... aaahhhh...</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "gritdk_es" => [
                "title" => "El último grito",
                "author" => "Deck28-06",
                "content" => [
                    '<p>Día 8: Estoy tan solo, la soledad me carcome por dentro, uno de mis compañeros se sacrificó por nosotros y terminó en un charco de sangre y vísceras, fue repugnante. El otro.. He tenido que ser drástico con él, su estado era crítico, sigo pensando que le hice un favor al acabar con su existencia.</p>
                <p>Este es otro día que no quiero vivir, tan solo. Esta soledad apesta. He asesinado a 38 zombies pero hoy me miro y soy un monstruo como ellos.</p>
                <p>Veo una luz, voy hacia ella, es hermosa, lo mas bello que he visto.  Este, este fue mi ultimo mensaje al mundo de los vivos, mi último grito.</p>'
                ],
                "lang" => "es",
                "background" => "secret",
                "design" => "written",
                "chance" => "8",
            ],
            "horos_es" => [
                "title" => "Horóscopo",
                "author" => "Corvicus",
                "content" => [
                    '<p>No desmayes en tu misión, los astros te acompañarán en tu camino, en especial Júpiter, quien te dará una suerte extraordinaria, no desaproveches la ocasión de buscar lo que tanto anhelas.</p>
                <p>Confía en quienes te hablan directo y sin miedo, desconfía de los que hablan menos, podrían estar tramando algo a tus espaldas.</p>
                <p>Recuerda, la horca pude ser un juego divertido para algunos.</p>
                <p>En el amor, sin novedad, tu físico y tu poca higiene no ayudan.</p>'
                ],
                "lang" => "es",
                "background" => "carton",
                "design" => "written",
                "chance" => "3",
            ],
            "slept_es" => [
                "title" => "La agonía",
                "author" => "Selene",
                "content" => [
                    '<p>¡Qué se callen! Los gritos de los que mueren y de los que ven la muerte llegar... La agonía del cuerpo y de la razón...</p>
                <p>A veces pienso que las noches y los ataques acabarán por traer la paz. ¡Ayer éramos dieciocho, hoy solo siete!</p>
                <p>Estoy harta de caminar en charcos de sangre... de tropezarme con pedazos de gente...</p>
                <p>¿Por qué los zombies se van cada noche? ¿Por qué no vienen a matarnos a todos de una sola y buena vez? ¿Para qué hacernos sufrir tanto? A pesar de las apariencias, cuando los veo, esos monstruos están más conscientes de lo que parecen... ¡Eso los hace más crueles y abominables!</p>
                <p>Tengo miedo.</p>',
                    '<p>Carlos, mi mejor amigo... había desaparecido y le había dado por muerto. ¡Pero está vivo! Le encontré caminando en el pueblo... Pero tal vez hubiese sido mejor no verle de nuevo, no en ese estado...</p>
                <p>Preparaba sus cosas para volver a salir al Ultramundo... La próxima vez que salga... yo me iré con él... Para siempre.</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "1",
            ],
            "grit1_es" => [
                "title" => "La espera",
                "author" => "Camarada_Ndomo",
                "content" => [
                    '<p>Hoy el sol ha aparecido de nuevo después de varios días nublados. Antes de salir a excavar, como todos los días, he limpiado un poco la casa y he tomado una ración de agua del pozo.
                </p><p>Llevo ya aquí una semana, y aún no he sido herido en ninguna de mis salidas. Solo que la muerte ronda por aquí.</p>
                <p>Nunca lo hubiera imaginado hace dos días, cuando regresé al pueblo después de haber encontrado en el desierto un plano poco común. Todos mis compañeros me felicitaron, en sus ojos brillaba la ilusión, y yo me sentí orgulloso de poder contribuir a lo que podría ser la salvación del pueblo.</p>',
                    '♠<p>Cuando se terminó la construcción, descubrimos que ese plano servía para crear un matadero con el que podríamos distraer a los zombies, a la vez que nos permitiría librarnos de los habitantes antisociales del pueblo. Estos, por venganza, me denunciaron, aunque entonces no le di importancia porque sabía que el resto de mis compañeros valoraba mi aportación y mi esfuerzo.</p>
                <p>Pero hoy los zombies superarán nuestras defensas, y los constructores no tendrán tiempo de levantar otras nuevas antes del anochecer. Al salir de casa, mis camaradas evitaban mirarme a los ojos, y ni siquiera me he atrevido a despedirme. Tengo siete denuncias. Antes de que acabe el día, alguien no podrá resistirse al miedo y realizará la octava, la que me condenará a morir a las puertas del pueblo para que mis compañeros resistan al menos un día más.</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "3",
            ],
            "navid5_es" => [
                "title" => "La noche cada vez más negra",
                "author" => "moritubo",
                "content" => [
                    '<p>Tengo que ser rápido, ya no me queda mucho tiempo.
                Muchos de mis compañeros ya murieron en las garras de algo. No es un zombie normal, alguna vez fue humano y después fue un zombie cualquiera, ahora es, es... Es algo diferente. </p>
                <p>Esta cosa, esta abominación, corre en cuatro patas como si fuera un animal y en cierto sentido, lo es.
                Las armas no le afectan como si tuviera una armadura, es inteligente pues le colocó trampas a los desafortunados de mis camaradas que se dejaron caer.</p>
                <p>Puede pasar junto a los heridos y moribundos pero no los toca, los escucha, los ve, los olfatea, pero no los toca, simplemente a él no les interesa.
                Puede cortar los cuellos de un zarpazo pero no los come, él simplemente está jugando, él está cazando, ÉL NOS ESTÁ CAZANDO. </p>
                <p>Ya me encontró, por fin se acabo esta pesadilla para mi... Ahora irá por otro pueblo.
                ¿Acaso será el tuyo?</p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "3",
            ],
            "poem1_es" => [
                "title" => "Los Días en Zombinoia",
                "author" => "Evolution",
                "content" => [
                    '<p>No son noches como cualquiera,<br>
                llenas de miedo y sospechas.<br>
                Muchos morirán, otros rezarán,<br>
                a salvo querrán estar,<br>
                pero los zombies su dicha cumplirán.</p>
                <p>Tú solo intenta sobresalir,<br>
                y siempre a tu pueblo ayudar.<br>
                En equipo, cooperando como hermanos,<br>
                una vida vida más larga podrás vivir.</p>'
                ],
                "lang" => "es",
                "background" => "secret",
                "design" => "written",
                "chance" => "6",
            ],
            "malasu_es" => [
                "title" => "Mala Suerte",
                "author" => "Vladimir Vochesky",
                "content" => [
                    '<p>Estoy de camino a una casa abandonada a unos cuantos kilómetros del pueblo, tengo frío, hambre y no he encontrado nada útil...</p>
                <p>He visto a unos 3 zombies rondando por aquí, estoy herido y no tendré suficiente suerte para al menos matar a uno...</p>
                <p>A lo mejor podré seguir hasta morir a un kilómetro más no quiero ser pesimista...</p>
                <p>Ya está anocheciendo y nadie ha venido a ayudarme...</p>
                <p>Sólo es cuestión de minutos para que mi dolorosa redención llegue...</p>
                <p>Si alguien está leyendo esto quiero que sepas que...</p>
                <p>Nada vale la pena en este mundo, probablemente ya sea un arañaparedes al salir el alba...</p>'
                ],
                "lang" => "es",
                "background" => "printer",
                "design" => "ad",
                "chance" => "5",
            ],
            "mens_es" => [
                "title" => "Mensaje perdido",
                "author" => "Fuego1798",
                "content" => [
                    '<p>Mi amigo murió en el desierto, llevábamos días de marcha y el agua se acabó, nos dirigíamos a Fuego Corporation...</p>
                <p>Estábamos tan débiles que no llegaríamos hasta la ciudad. No tuve más remedio que comerme a mi amigo. ¿No era mejor que al menos uno de nosotros llegara? Tan solo llevaba unas pocas horas muerto cuando decidí dar el primer paso, tras esa sabrosa dolorosa experiencia, logró llegar mi salvación estaba a tan solo cien metros, era una ciudad hermosa, e incluso de la emoción mi sed había desaparecido pero no mi hambre.</p>
                <p>Cuando veas esta nota aléjate del cadáver donde está escrito, y avisa al pueblo, el peligro está cerca, y debes detenerme.</p>
                <p>Si sientes que algo te observa en estos momentos entonces ríndete, piensa que no morirás en vano, servirás de advertencia a los próximos viajeros. </p>'
                ],
                "lang" => "es",
                "background" => "secret",
                "design" => "written",
                "chance" => "5",
            ],
            "minfec_es" => [
                "title" => "Mi infección",
                "author" => "Goku69",
                "content" => [
                    '<p>Después de haber comido ese trozo de carne que encontré en el desierto he comenzado a sudar mucho por una extraña fiebre y siento algo de náuseas.</p>
                <p>Lo mas extraño es que la carne de mi novia me esta comenzando a parecer algo apetitosa, lo cual me preocupa. Solo espero despertarme mañana y verla. Espero no cometer una locura.</p>'
                ],
                "lang" => "es",
                "background" => "secret",
                "design" => "small",
                "chance" => "4",
            ],
            "neul_es" => [
                "title" => "Nota en la hoja de un libro",
                "author" => "Leon-ex",
                "content" => [
                    '<p>Hace dos días vine a esta vieja biblioteca alejada del pueblo, sin fuerzas para volver. Decidí quedarme a dormir, y ese fue el error que me costó la vida.</p>
                <p>La biblioteca está rodeada por los zombies, pero estoy descubriendo que los zombies no son estúpidos, no intentan entrar, porque saben que dentro tengo ventaja estratégica, sino que esperan fuera a que me muera deshidratado, porque no quieren que cuente al mundo el misterio de los libros sobre ellos.</p>',
                    '<p>Antes de las películas de terror, en la edad media, incluso en el Antiguo Egipto, los libros guardan un secreto, que yo escribiré ahora en la nota, para que si la leéis, que sepáis su mayor debilidad, su origen, y principalmente nuestro mayor error...</p>
                <p>(la nota ha sido partida, falta la otra parte...)</p>'
                ],
                "lang" => "es",
                "background" => "stamp",
                "design" => "typed",
                "chance" => "3",
            ],
            "papa_es" => [
                "title" => "Papá, ¿entrarán esta noche?, ¿me dolerá?",
                "author" => "Putonverbenero",
                "content" => [
                    '<p>Contrariamente a lo que podría parecer, la mordedura no duele, es más, se deja de sentir todo dolor, el hambre, la sed...</p>
                <p>El cerebro se embota hasta que se nublan tus sentidos y la razón...</p>
                <p>¡Qué demonios, de tu razón no queda nada! </p>
                <p>Entonces será cuando dejes de reconocer a tus amigos, cuando te dirijas inconscientemente a alimentarte de su carne caliente y fresca, cuando empieces a pudrirte por dentro... </p>'
                ],
                "lang" => "es",
                "background" => "grid",
                "design" => "written",
                "chance" => "2",
            ],
            "umn" => [
                "title" => "Una mala noche",
                "author" => "123k",
                "content" => [
                    '<p>Todos los habitantes restantes en el pueblo quieren volver como antes, es decir, hace dos dias atrás. Los zombies no habían devorado todavía ha nadie, que bien nos lo pasabamos... y además.. todavía no había muerto nuestra querida mascota, Lafy... le teníamos tantoo cariño. </p>
                <p>Según el "Cadaver matutino" le devoraron 50 zombis... Le deberíamos de echar la bronca a Fuego1798, nuestra mascota dormía en su casa, pero él no supo defendérle como se lo merecía.</p>
                <p>Yo también he dormido muy mal esta noche, supongo que a todo el pueblo le habrá ocurrido lo mismo que a mi. Oyendo los gritos de nuestros compañeros... Esta noche he quedado aterrorizado, pero gracias a mis compañeros, lo estoy superando, ellos me administran mis calmantes. Espero que esta noche, los centinelas nos defiendan muy bien y que no muera nadie.</p>'
                ],
                "lang" => "es",
                "background" => "secret",
                "design" => "small",
                "chance" => "4",
            ],
            "nightm_es" => [
                "title" => "Una noche corta",
                "author" => "Znarf",
                "content" => [
                    '<p>Me desperté transpirando y con el corazón que se me quiere salir del pecho.</p>
                <p>Logré dormirme contando ovejas para no pensar en lo que pasaría después. 1, 2, 3,... creo que llegué a 1977, el año de mi nacimiento. Creo que me desperté solo 15 minutos después, diciéndome: "Ya llegaron"."Es el fin". Y esto se repite noche tras noche. La mitad de mis compañeros ha muerto. Los primeros fueron los más valientes, quienes sí pensaron en salvarnos y se aventuraron a explorar el desierto para traer materiales, mientras que otros siguen vivos, roncando en la comodidad de sus casas. ¿Eso es justo?</p>
                <p>El sol vuelve a levantarse, yo no he pegado un ojo, tengo hambre, sed, y muchas ganas de hacer que todo vuelva a ser como antes...</p>'
                ],
                "lang" => "es",
                "background" => "grid",
                "design" => "written",
                "chance" => "2",
            ],
            "vag1_es" => [
                "title" => "Vagando",
                "author" => "raul789456",
                "content" => [
                    '<p>Vagando por el Ultramundo tratando de encontrar cosas útiles.</p>
                <p>Me desterraron por cosas que encontré en el desierto y las escondí en mi casa.</p>
                <p>Quizás tenga una mejor vida acampando lejos del pueblo solo yo y sin molestias.</p>'
                ],
                "lang" => "es",
                "background" => "stamp",
                "design" => "typed",
                "chance" => "1",
            ],
            "explut_es" => [
                "title" => "Vaya suerte",
                "author" => "Santi99",
                "content" => [
                    '<p>Viendo que mucha gente iba y venía por ese extraño portal, decidí ir al exterior.<br>
                Pasé por ahí y en un instante aparecí en un desolado Desierto.</p>
                <p>Salí a explorar hacie el oeste. Buscando refugio, huyendo de las turbas, encontré algo. Algo que nunca me podrás creer.</p>
                <p>"Eso" salvó a mi pueblo.</p>'
                ],
                "lang" => "es",
                "background" => "postit",
                "design" => "small",
                "chance" => "4",
            ],
            "cuidad_es" => [
                "title" => "¡Cuidado!",
                "author" => "Tydram",
                "content" => [
                    '<p>Lo acabo de ver, uno de los habitantes mordió un trozo de carne y algo que sucedió, no se que fue pero de repente me miró de una manera que solo haría un zombie, seguía teniendo la misma apariencia de siempre pero era un zombie con conciencia.</p>
                <p>Me intentó morder, logré escapar y llegué hasta una casa abandonada me encerré y bloqueé la única entrada.</p>',
                    '<p>Abandono toda esperanza de sobrevivir, mi corazón late muy fuerte, antes de que entre quiero avisarles que el "hombre zombie" está más cerca de lo que creen y no es nada más ni menos qu__<s>.</s></p><s>'
                ],
                "lang" => "es",
                "background" => "postit",
                "design" => "small",
                "chance" => "4",
            ],
            "billie_es" => [
                "title" => "¡Felicidad!",
                "author" => "BillieJoe",
                "content" => [
                    '<p>Me desespero, cada momento se vuelve intenso, nunca pensé sentirme tan afortunado. La lucha fue dura, las esperanzas mínimas.</p>
                <h2>No tengo palabras:</h2>
                <h1>¡Soy el único superviviente de mi pueblo!</h1>'
                ],
                "lang" => "es",
                "background" => "postit",
                "design" => "written",
                "chance" => "5",
            ],
            "aullid_es" => [
                "title" => "¿Oyes eso?",
                "author" => "Tonk",
                "content" => [
                    '<p>Escucha esos gritos. ¿Por qué crees que son?</p>
                <p>No lo sabes, yo te lo diré...</p>
                <p>Son por el temor que causa estar ahí afuera, por saber que un día te llegará, por que sabes que no puedes escapar.</p>
                <p>Y cuando por fin te das cuenta que te tocó la mano, te desmoronas y no hay más remedio que dejarse llevar por ese aullido. </p>
                <p>Serás arrastrado sin compasión ni lastima.</p>
                <h1>ESE EL AULLIDO DE LA MUERTE...</h1>'
                ],
                "lang" => "es",
                "background" => "letter",
                "design" => "written",
                "chance" => "6",
            ],
            "navid4_es" => [
                "title" => "Aquel día, ya no es hoy...",
                "author" => "frangy",
                "content" => [
                    '<p>Recuerdo aquellos días... Felices fiestas decían... Junto con tu familia y amigos, te divertías, te lo pasabas bien, y sonreías. </p>
                <p>Pero es una pena, porque todo ya cambió, ya no podemos celebrarlo. Ahora, lo único por lo que te podré felicitar, es porque sigas con vida, porque no seas uno de ellos... Pero tranquila, algún día de estos, ya no te importará esto, porque irás con ellos, todos iremos con ellos. </p>
                <p> Tu cuerpo se pudrirá, tu misión será devorar... Pero no te preocupes, porque tu alma seguirá viva, y es entonces, cuando podrás celebrarlo, cuando vuelvas a coincidir con tus seres queridos.</p>
                <p>A mi mujer, Carol; Espero que sigas viva.</p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "written",
                "chance" => "3",
            ],
            "arrep_es" => [
                "title" => "Arrepentido",
                "author" => "Mcdrack",
                "content" => [
                    '<p><strong> Lunes </strong><br>
                Ayer éramos 27 personas, hoy solo 12. Nuestras defensas son muy bajas y nos atacarán entre 80 y 110 zombies.</p>
                <p><strong> Martes </strong><br>
                El ataque ha terminado y fui uno de los 4 supervivientes.</p>
                <p>Me había robado todas las defensas del pueblo para mi casa, los otros habitantes lo saben. Temo por mi vida...</p>
                <p><strong> Miércoles </strong><br>
                Hoy fui a buscar materiales en el Ultramundo, ¡y han cerrado el portal mientras yo estaba afuera!</p>
                <p>Faltan 5 minutos para el ataque y ya puedo escuchar rugidos hambrientos de carne humana acercándose...</p>
                <p>¡PERDÓN! ¡POR FAVOR, PERDÓN!</p>'
                ],
                "lang" => "es",
                "background" => "old",
                "design" => "poem",
                "chance" => "4",
            ],
            "avisc_es" => [
                "title" => "Aviso a la colectividad",
                "author" => "El Alcalde",
                "content" => [
                    '<h1>Queridos conciudadanos</h1>
                <p>Unos desadaptados han estado lanzando trozos de cadáveres a la gente en el pueblo durante el carnaval.
                Esas bromas de mal gusto son inadmisibles. Somos un pueblo pobre pero digno.</p>
                <p>Por respeto a las personas que fallecieron heróica o estúpidamente, exijo el cese de estos actos de salvajismo.
                Los culpables serán sentenciados públicamente y haremos una barbacoa con sus restos. Porque con el hambre del pueblo, no se juega.</p>
                <p></p>
                <p>El Alcalde.</p>'
                ],
                "lang" => "es",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "gryl_es" => [
                "title" => "Caos y falsa acusación",
                "author" => "Gyrlander",
                "content" => [
                    '<p>El pueblo ha caído en el caos... La poca gente que todavía sobrevive están corriendo de un lado para otro y, otros, ahorcándose.</p>
                <p>Hay un héroe que está en todo el rato en su casa, con raciones de agua y comida ilimitadas. ¿No se da cuenta de que necesitamos su ayuda?</p>
                <p>Ayer por la noche el héroe murió envenenado. ¿Quién hizo eso? ¿Quién pudo haberlo hecho?</p>
                <p>Han pasado unas horas y todo el mundo me acusa, ¡yo no he hecho nada! ¡ Malditos séa.. a.. iiss..!</p>
                <p>(Hay una cara sonriente hecha de sangre debajo de todo el texto).</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "2",
            ],
            "desesp_es" => [
                "title" => "Desesperación en la noche",
                "author" => "Empyre",
                "content" => [
                    '<p>Día sexto en el diario de un superviviente.
                En el pueblo se ha propagado la infección y ha empezado a notarse los primeros brotes de locura en los habitantes. Unos gritan barbaridades y otros las hacen.</p>
                <p>La desesperación entra en el cuerpo de los pocos supervivientes a la infección. Algunos están encerrados en sus casas, esperando el momento de ser devorados. Mientras nosotros, aún con algo de espíritu aventurero, hemos decidido exiliarnos, y estamos acampando cerca de unas ruinas.</p>',
                    '<p>Pensamos iniciar la exploración en unas horas. Primero nos reunimos para coordinar nuestro plan y aprovechamos para abrazarnos fuerte entre todos por si es la última aventura que nos une...</p>
                <p>Si alguna vez hemos sido dignos de tener suerte, esperamos que sea ahora.</p>'
                ],
                "lang" => "es",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "jps_es" => [
                "title" => "Juntos por siempre",
                "author" => "Zombie7",
                "content" => [
                    '<p>Desde hace días la noto extraña, se que algo le pasa, pero ella no quiere decir nada. Hoy he decidido seguirla a escondidas.</p>
                <p>Dios! No puedo creer lo que he visto, ella… ella… ha matado a nuestro vecino!</p>
                <p>Ella no deja de llorar y entre lágrimas comenzó a devorar las entrañas de ese pobre viejo, mastica la carne con asco y sus manos se encuentran cubiertas de sangre… pero qué es lo que está pasando?</p>
                <p>Ya ha pasado una semana y los rumores de que un “monstruo” está devorando a los habitantes se hacen más fuertes, justo el día de ayer ahorcamos a un desafortunado por encontrar carne humana en su casa, claro que yo me encargue de dejarla ahí, solo yo sé la verdad y nadie más debe saberla…</p>',
                    '<p>Hoy no queda ningún habitante en el pueblo más que yo, nadie lo hubiera entendido, ella no quería convertirse en eso. Ahora se encuentra dormida, parece un ángel, pero pronto despertará y necesitará alimentarse, cuando eso suceda… yo estaré aquí para ella, seré su último festín…</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "3",
            ],
            "wintk2_es" => [
                "title" => "La suerte de z0rrox",
                "author" => null,
                "content" => [
                    '<p>Caminaba una noche, perdido por el Ultramundo un tal z0rrox. Harto de tragar arena y del ruido que hacían sus gastadas suelas al andar, se decide a hacer un alto. </p>
                <p>Mira sus pies y despega un viejo papel que decía:</p>
                <blockquote>
                <p>Todos vamos al infierno.</p>
                <p>Y yo voy conduciendo el autobús.</p>
                </blockquote>
                <p>Se trataba del mítico ticket mágico que le daba poderes de héroe. Sus ojos brillaron, una lágrima cayó, su fatiga desapareció... ¡Soy un héroe! gritó... ¡Soy un maldito héroe! Jajajajajajaaa...</p>
                <p>Se tragó el papel y corrió al pueblo con las pocas fuerzas que le quedaban, había que salvarlo antes del ataque.</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "classic",
                "chance" => "1",
            ],
            "navid2_es" => [
                "title" => "Masacre después de Navidad",
                "author" => "Deadpool",
                "content" => [
                    '<h2>Día 25 de Diciembre de 2013, 00:30 hs.</h2>
                <p>En el pueblo estamos celebrando la navidad aterrorizados por el ataque que se acerca, 536 zombies están esperando para atacar. La gente le está dando armas a sus amigos como regalo de navidad, a mi me dieron un machete ensangrentado y una invitación para ser centinela esta noche. Acepté, pero me arrepentiré de eso luego cuando los vea a esos muertos vivientes.</p>
                <p>Nuestras defensas son bajas, ni con los centinelas podremos vencer. Somos como 30 habitantes, 3 no vuelven todavía, estoy sospechando de Tommy por su adicción de querer lamer la piel de la gente y por el hecho de que fue con Juan a buscar tablas y regresó solo y sin ganas de comer. </p>',
                    '♠<p>La cantidad de botellas de alcohol que están en el piso podrán impedir a algunos zombies el paso, pero eso no ayudará mucho, los borrachos van a abrir y cerrar el portal todo el tiempo, es normal de ellos hacer eso, ya nos acostumbramos antes, pero como estamos ahora los mataremos a ellos si no se alejan de ahí.</p>
                <p>Si, ésta es nuestra navidad, en vez de estar descansando y disfrutando con la familia y los amigos una deliciosa cena, tenemos que estar alertas todo el tiempo sin dormir. Nos gustaría saber quién fue el que empezó a revivir a los muertos para darle su "regalito de navidad" por acabar con el mundo como lo conocemos. Hay que ser un maldito para destruir al mundo y sentirse orgulloso de eso.</p>
                <p>Les deseo una feliz navidad y una noche de supervivencia, como la de todos los días.</p>'
                ],
                "lang" => "es",
                "background" => "notepad",
                "design" => "written",
                "chance" => "3",
            ],
            "mvet_es" => [
                "title" => "Mi vida te la entrego a ti...",
                "author" => "Gummy",
                "content" => [
                    '<p>Suelo preguntarme porque...</p>
                <p>Intento salvar vidas todos los días y hoy no podré salvar mi propia vida. En mi pueblo me llaman héroe, pero no lo soy, soy simplemente una mano que ayuda a los que me necesitan.</p>
                <p>Rodeado por la muerte y la inseguridad, temo por los que emanan rencor y envidia, por los cobardes de mi pueblo. Yo los vi crecer y actuar, pero no saben que espero lo mejor de ellos. Así que dando un último aliento, daré un último esfuerzo y derramaré mi sangre solo por salvar la vida de aquellos, para que algún día, ellos puedan ser los héroes de esta historia.</p>'
                ],
                "lang" => "es",
                "background" => "stamp",
                "design" => "typed",
                "chance" => "1",
            ],
            "miul_es" => [
                "title" => "Mi último dia",
                "author" => "Crisram09",
                "content" => [
                    '<p>17 de Septiembre</p>
                <p>Hoy es el día de mi cumpleaños y tal vez sea el último... ya hace varios años ( unos 5 o 6 ) que perdí a mis padres y a mis hermanos debido a ese maldito virus, lo que después terminó volviéndose una plaga de "muertos" por así decirlo.... Creo que fue el momento más impactante de mi vida.... cuando tuve que disparar a mis familiares y amigos para salvar mi propia vida... </p>
                <p>En este momento me encuentro encerrado en un edificio, acorralado por centenares de Zombies, lo más probable es que solo me queden unas horas, mientras descubren cómo destruir la puerta que me protege en esta fría y húmeda habitación. Papá, Mamá... los extraño tanto...</p>
                <p>Dejo plasmadas en este mísero trozo de cartón lo que seguramente fueron mis últimas palabras.</p>
                <p>Atentamente: Alejandro</p>'
                ],
                "lang" => "es",
                "background" => "carton",
                "design" => "written",
                "chance" => "1",
            ],
            "lostck_es" => [
                "title" => "Ticket perdedor",
                "author" => null,
                "content" => [
                    '<h1>OOOOOOOH... <br>Este ticket pudo hacerte ganar algo, pero no fue así :(...</h1>
                <blockquote>
                <p></p><center>¡Sigue probado suerte! <br> Fuma cigarrillos Macarena, son 27.3% más tóxicos ¡y te pintan los pulmones de azul!</center><p></p>
                </blockquote>
                <small>CERTIFICADO SANITARIO. Responsable: Doctor T. Mata. Registro G58-141254-A. 88950 difuntos felices lo confirman.</small>'
                ],
                "lang" => "es",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "5",
            ],
            "trai_es" => [
                "title" => "Diario de un traicionado",
                "author" => "crizzzr2d2",
                "content" => [
                    '<p>Nunca le he dado la espalda a quien me necesite, sin importar lo difícil que resulte darle la mano y confiar en alguien en este desierto atestado de criaturas ansiosas por probar tu carne. </p>
                    <p>Hace tres días salvé a Juan de ser devorado por los zombies en el Ultramundo y logramos entrar al pueblo 5 minutos antes de que cerraran el portal. Y sobrevivimos... juntos. Me dijo que me devolvería el favor, que nunca me abandonaría cuando lo necesitara. </p>

                    <p>Al día siguiente ya faltaba poco para el ataque de esas criaturas y nos dimos cuenta que Raúl no se encontraba en su casa, nadie sabia de él.</p>
                    <p>Me aventuré a buscarlo, estaba en las cercanías del pueblo, estaba herido y acorralado en una casa abandonada, después de una desesperada lucha pudimos zafar de esas bestias pero aún así no le quedaban fuerzas para volver al pueblo, le di mi cantimplora... y ambos regresamos cansados, heridos, hambrientos, pero vivos. Me dijo que nunca lo olvidaría, que éramos amigos.</p>

                    <p> Hoy salí a buscar recursos como de costumbre. Caminé más de lo habitual llegando a un bosque quemado, entre en él para buscar madera pero sin darme cuenta me vi rodeado de zombies... pude sobrevivir a la horrible emboscada pero estaba herido y demasiado cansado como para volver solo. </p>
                    <p>Y aquí estoy abandonado en el bosque, ya es de noche y puedo sentir como esas criaturas se acercan. Esta es la hora que cierran el portal y de Juan y Raúl nada...</p>

                    <h1>Si puedes leer esto es porque morí y quiero que sepas que en este mundo ¡estas solo!.</h1>'
                ],
                "lang" => "es",
                "background" => "carton",
                "design" => "typed",
                "chance" => "1",
            ],
            "wintck_es" => [
                "title" => "Etiqueta de cigarrillos",
                "author" => "crizzzr2d2",
                "content" => [
                    '<h1><small>¡Hay mil maneras de </small> MORIR!</h1>
                    <p>Esta es una patrocinada:</p>
                    <blockquote>
                    <h1><small>Consume calidad</small>
                    <center>100% Nicotina</center>
                    <small>El cigarrillo preferido por más de 3 millones de víctimas.
                    </small>
                    </blockquote>

                    <center>Tose contento, muere feliz.</center>'
                ],
                "lang" => "es",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "1",
            ],
            /**
            * ENGLISH ROLE PLAY TEXTS
            */
            "afifr3" => [
                "title" => "A Friend in Fur",
                "author" => "Bugzilla",
                "content" => [
                    '<h1>A Friend in Fur</h1>
                <p></p>
                <p>Midnight passes through the town bringing terror and dread. The zombies, moaning and clawing held at bay for another night.</p>
                <p>Thank God for our defences, Dragnauv thinks as he heads out of town, his rucksack carrying only the bare essentials,
                a tasty dish he prepared just before the nights attack, water for the long walk ahead and a rusty chain just in case something out
                in the wasteland though he looked like a tasty dish.</p>
                <p>After several hours of exploring and searching the long day caught up with Dragnauv who stopped focusing on the task at hand,
                only for one moment, but one moment was all it took, Dragnauv was surrounded by zombies.</p>',
                    '<p></p>    
                <p>\'Taste the pain\' Dragnauv yelled, as in one long swinging arc he bought the chain down on a zombies head. 
                Stunned he watched as more rust than chain it disintegrated on impact.</p>
                <p>Luckily for Dragnauv a small group of scavengers appeared from nearby, drawn by his premature battle cry, 
                their arrival giving Dragnauv just enough of a distraction to make a break for it. </p>
                <p>Dragnauv slowly backed away from the zombies and just as he was about to make his move for town he heard a light mewling coming
                from the direction of a pile of rubbish, an upturned trash bin specifically, flipping the bin over he is both surprised and delighted
                to see a young kitty looking slightly worse for wear and covered in all sorts of stains from its time in the upturned bin.</p>',
                    '<p></p>    
                <p>Kneeling down to pick the poor defenceless kitty up, Dragnauv is in awe as the cat runs over towards the nearest zombie and in a flurry of hisses, 
                bites and scratches rips its head off, turns and saunters back claiming Dragnauv as a new master.</p>
                <p>By R3DD3R</p>
                <br>
                <p>Formerly: The Epic Saga of Mrs Whiskers, by Dragnauv</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "written",
                "chance" => "3",
            ],
            "sprfen" => [
                "title" => "Advert",
                "author" => "Bugzilla",
                "content" => [
                    '<h1>Sparky\'s electric fences!</h1>
                <p>The best solution for protecting your town against unwanted visitors or keeping your private stock of food and water rations out of reach of your neighbours.</p>
                <p>Order our latest models to keep your home safe:</p>
                <ul>
                <li>[ ] <strong>Sparky\'s Triple Jump</strong> - thee wires for better security</li>
                <li>[ ] <strong>Sparky\'s Toaster</strong> - high voltage, continuous current</li>
                <li>[ ] <strong>Sparky\'s DeLuxe Fence</strong> - with electrified barbed wire</li>
                <li>[ ] <strong>Sparky\'s Electric Surprise</strong> - very thin wires, almost invisible - your neighbours will never try twice!</li>
                <li>Note: Some models require an additional high power fence energizer, to be ordered separately.</li>
                </ul>
                <p></p>',
                    '<br>
                <p>Satisfaction guaranteed! No hassle full-refund policy: if our product does not work as advertised, you can return it to us free of charge and we will give you a full refund.</p>
                <p>Payments must be made in advance before your order can be processed. Please allow one month for delivery.</p>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <small>Disclaimer: Batteries not included. Some assembly required. Keep out of reach of children. Sparky\'s Ltd is not responsible for direct, indirect, incidental or consequential damages resulting from any defect, error or failure to perform.</small>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "modern",
                "chance" => "3",
            ],
            "dead1_en" => [
                "title" => "Alfred's Epitaph",
                "author" => "Planeshift",
                "content" => [
                    '<small>[This bit of card must have been used as a tombstone epitaph ]</small>
                <h1>Alfred (1948 - ??)</h1>
                <p>Alfred might have been the last of the village idiots, but he was a man of good taste, I must say. he carefully decorated his house, making sure there was a place for everything and everything was in place, so that if you popped by at random, you at least knew you\'d be made to feel comfortable. Even in death, screaming for help as the zombies devoured him, he was careful not to fight back with that chair he had so artistically placed next to the half-rotten wood table. It goes without say that he didn\'t defend himself with the pistol mounted on the wall, despite it being loaded. A mystery which will remain that way is why a man of such taste would rather die than mess up his interior.</p>
                <p>One more thing I will say though, is that for a man of such taste, he was genuinely delicious. Well-done Alfred...</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "written",
                "chance" => "4",
            ],
            "stval2_en" => [
                "title" => "Alt. Valentines Day",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>Roses are red,<br><br>
                Violets are blue,<br><br>
                They don\'t think it be like it is<br><br>
                But it do!<br><br><br><br>
                Happy Valentine\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "ruoutt_en" => [
                "title" => "Are you out there?",
                "author" => "Sofl",
                "content" => [
                    '<br>
                <p>Hey Stella. You out there? It\'s me again. I\'ve been broadcasting this --<strong>bzzt</strong>-- same message every night since finding this old transmitter. Seems fitting - we\'ve dug up so many of these --<strong>zrrt</strong>-- stupid portable radios that I can hold out some hope that you\'ve found one too. Plastic bottles, wrecked cars, and old transistor radios - is this all that\'s left of what we once --<strong>shhzt</strong>-- were?</p>
                <br>
                <p>Where did they come from, Stella? Days of smoke, nights of fire, weeks of darkness, then --<strong>skrrt</strong>-- they began to shamble out of the darkness. Strangers, loved ones, grandparents, children... I think I saw mom, Stella. Half her face was gone. --<strong>ffrt</strong>--</p>',
                    '<br>
                <p>Have you seen Franklin? He went missing two weeks ago. I hope he\'s still alive, because the th--<strong>zzt</strong>--ght of him out there, one of them, is too much to bear. --<strong>bzrrt</strong>--</p>
                <p>Well, the batteries are running --<strong>zztt</strong>-- low again. I hope things will be quiet enough --<strong>skhh</strong>-- tonight to dream about how happy we were, our h--<strong>hsshh</strong>--ppy little family.</p>
                <p>If you hear this --<strong>bzzt</strong>-- you can f--<strong>zzssk</strong>--nd us in the vall--<strong>skrkk</strong>-- near the --<strong>zztt</strong>-- just --<strong>zrrrkk</strong>-<strong>shh</strong>-- hope --<strong>ssss</strong>-- stupid piece of --<strong>hmmmn</strong>--</p>
                <p>I miss --<strong>brzz</strong>-- you St--<strong>zsshh</strong>--lla... are you... --<strong>szzt</strong>-- are you out there? --<strong>sssssshhhhhhh</strong>--</p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "3",
            ],
            "news2_en" => [
                "title" => "Article - Vicious Killing",
                "author" => null,
                "content" => [
                    '<small>(Continued from page 1)</small>
				<p>[...] the couple found dead in their kitchen bore injuries resembling "bite marks" according to a source close to the authorities.</p>
				<p>This tragedy takes the number if incidents in the region to 9, with 16 people found dead in similar circumstances. The serial killer theory remains the most popular, but certain people are spreading a "wild beast attack" theory : in fact, primary tests have revealed the presence of modified human DNA, a fact which has yet to be confirmed by the authorities who have previously refuted these claims...</p>
				<h1>Deepnight stole my game!</h1><p>Zuckerberg claims to own Die2Nite (and players\' souls)...</p>'
                ],
                "lang" => "en",
                "background" => "news",
                "design" => "news",
                "chance" => "2",
            ],
            "docgsw" => [
                "title" => "Based on a true story",
                "author" => "Workshop",
                "content" => [
                    '<p>Pitiless Bay of the Banished, day 4</p>
                <p>Bam! scratch scratch... Crrrrrrrr...</p>
                <p>What\'s going on? Thedoc1337 opens a careful eye.</p>
                <p>Noise... it sounds like it\'s coming from the workshop. Or maybe the bank ...at this time of the night?</p>
                <p></p>We survived the attack. No zombie got in: the gazette can\'t be wrong. The Crow can\'t be wrong!<p></p>
                <p>So what of it? Thedoc grabs a log and sneaks around... Gasp! A burglar! Shock! Terror!
                Thedoc wields his mighty log and hits the intruder on the head only to realise he had an accomplice. Right behind him! With a gun! And what\'s this: bullets? Where did they come from... By the Almighty Crow!!?</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "3",
            ],
            "tstpa2" => [
                "title" => "Black Velvet Band",
                "author" => "Dubliners",
                "content" => [
                    '<p>In a neat little town they call Brockton</p>
                <p>Apprenticed in trade I was bound </p>
                <p>And many an hour\'s sweet happiness </p>
                <p>I spent in that neat little town </p>
                <p>Then bad misfortune befell me </p>
                <p>That caused me to stray from the land </p>
                <p>Far away from my friends and companions </p>
                <p>To follow the black velvet band </p>
                <br>
                <p>Well, I was out strolling one evening </p>
                <p>Not intending to stay very long </p>
                <p>When I met a frolicsome damsel</p>
                <p>As she came tripping along</p>
                <p>A watch she pulled out her pocket</p>
                <p>And slipped it right into my hand </p>
                <p>On the very first night that I met her,</p>
                <p>Bad luck to the black velvet band </p>
                <br>',
                    '<p>Her eyes they shone like the diamonds </p>
                <p>You\'d think she was queen of the land </p>
                <p>And her hair hung over her shoulder </p>
                <p>Tied up in a black velvet band</p>
                <br>
                <p>Before judge and jury next morning </p>
                <p>Both of us did appear </p>
                <p>A gentleman claimed his jewelry</p>
                <p>And the case against us was clear </p>
                <p>Now seven long years transportation </p>
                <p>Right down to Van Dieman\'s land </p>
                <p>Far away from our friends and companions </p>
                <p>To follow the black velvet band </p>
                <br>
                <p>Her eyes they shone like the diamonds </p>
                <p>You\'d think she was queen of the land </p>
                <p>And her hair hung over her shoulder </p>
                <p>Tied up in a black velvet band</p>
                <br>',
                    '<p>In a neat little town they call Brockton</p>
                <p>Apprenticed in trade I was bound </p>
                <p>And many an hour\'s sweet happiness </p>
                <p>I spent in that neat little town </p>
                <p>Then bad misfortune befell me </p>
                <p>That caused me to stray from the land </p>
                <p>Far away from my friends and companions </p>
                <p>To follow the black velvet band </p>
                <br>
                <p>Well, I was out strolling one evening </p>
                <p>Not intending to stay very long </p>
                <p>When I met a frolicsome damsel</p>
                <p>As she came tripping along</p>
                <p>A watch she pulled out her pocket</p>
                <p>And slipped it right into my hand </p>
                <p>On the very first night that I met her,</p>
                <p>Bad luck to the black velvet band </p>
                <br>',
                    '<p>Her eyes they shone like the diamonds </p>
                <p>You\'d think she was queen of the land </p>
                <p>And her hair hung over her shoulder </p>
                <p>Tied up in a black velvet band</p>
                <br>
                <p>Before judge and jury next morning </p>
                <p>Both of us did appear </p>
                <p>A gentleman claimed his jewelry</p>
                <p>And the case against us was clear </p>
                <p>Now seven long years transportation </p>
                <p>Right down to Van Dieman\'s land </p>
                <p>Far away from our friends and companions </p>
                <p>To follow the black velvet band </p>
                <br>
                <p>Her eyes they shone like the diamonds </p>
                <p>You\'d think she was queen of the land </p>
                <p>And her hair hung over her shoulder </p>
                <p>Tied up in a black velvet band</p>
                <br>',
                    '<br><br>
                <p>So come all you jolly young fellows </p>
                <p>I\'d have you take warning by me </p>
                <p>Whenever you\'re out on the liquor </p>
                <p>Beware of the pretty Colleen</p>
                <p>She\'ll fill you with vodka and twinoid </p>
                <p>Until you\'re not able to stand</p>
                <p>And the very next thing you\'d know</p>
                <p>You\'ve landed in Van Dieman\'s Land. </p>
                <br>
                <p>Her eyes they shone like the diamonds </p>
                <p>You\'d think she was queen of the land </p>
                <p>And her hair hung over her shoulder </p>
                <p>Tied up in a black velvet band</p>
                <br>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "poem",
                "chance" => "1",
            ],
            "bopkn1" => [
                "title" => "Book of Poetry. Page 5.",
                "author" => "kean4311",
                "content" => [
                    '<p>Darkest night,</p>
                <p>in world beyond.</p>
                <p>Magic charm</p>
                <p>from magic wand.</p>
                <p>Tightens chest.</p>
                <p>Sweetness fond.</p>
                <p>Tied to you,</p>
                <p>in lovers bond.</p>
                <br>
                <p>I know my sweet heart</p>
                <p>Needs a cuddle.</p>
                <p>Arguments,</p>
                <p>leaves mind in muddle.</p>
                <p>Turns self esteems</p>
                <p>to muddy puddle</p>
                <p>Alone in hovel,</p>
                <p>knees to chest,</p>
                <p>You huddle.</p>',
                    '<p>On desert roads</p>
                <p>through dangers travel.</p>
                <p>Challenges faced,</p>
                <p>Pains do addle.</p>
                <p>Love should smooth</p>
                <p>not grate like gavel.</p>
                <p>but lovers bonds</p>
                <p>Will not unravel.</p>
                <br>
                <p>Its not easy</p>
                <p>to say alive.</p>
                <p>All these years</p>
                <p>I payed my tithe.</p>
                <p>Mistakes are made</p>
                <p>but on we strive</p>
                <p>so the love we have</p>
                <p>just might survive.</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "poem",
                "chance" => "3",
            ],
            "cwom13" => [
                "title" => "Cannibal Woman of Mars Flyer",
                "author" => "MickCooke",
                "content" => [
                    '<p></p><center>It’s 2113 and Martian princesses Yasmin and Pippa are about to be<br><br> initiated in the man-eating rituals of their cursed planet. On the <br><br>menu are Jaxxon McGhee and Largs Lido, two unsuspecting jobless <br><br>21-year-olds, newly arrived from an overcrowded and cruel Earth. <br><br>But when Yasmin and Jaxxon rebel against their destiny they <br><br>trigger an interplanetary crisis, with the Martian Queen baying for<br><br> blood and the President of Earth looking to indulge his own appetite<br><br> for destruction.<br><br> See it at the Edinburgh Festival 2014 - Twitter: @cwom2013</center>'
                ],
                "lang" => "en",
                "background" => "blood",
                "design" => "written",
                "chance" => "4",
            ],
            "cptlog" => [
                "title" => "Carpenter's Log",
                "author" => "Bugzilla",
                "content" => [
                    '<p>Zombies. I hate them.</p>
                <p>They killed my wife, my children. They destroyed my workshop, my life.</p>
                <p>They attack us every night since two weeks. We have lost our whole team of scavengers. Some cowards tried to escape. Damn fools!</p>
                <p>They are all around us. But I don\'t care. I won\'t die like a trapped animal. I will fight.</p>
                <p>I recovered the circular saws from the sawmill. They are now spinning outside, ready to rip up whatever comes at them. I love the music of my screaming saws.</p>
                <p>I have enough bottles of "Wake the Dead" to keep me awake for a week. I have a chainsaw and a machete. I\'m ready.</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "binary_en" => [
                "title" => "Crumpled printout",
                "author" => null,
                "content" => [
                    '<p><small>[Start of Transmission]</small></p>
                <p>Q29uZ3JhdHVsYXRpb25zISBZb3UncmUgYSBsb3Qgc21hcnRlci</p>
                <p>B0aGFuIHlvdSBsb29rIQ0KDQpCZWNvbWUgYSBmYW4gb24gRmFj</p>
                <p>ZWJvb2sgKHd3dy5mYWNlYm9vay5jb20vZGllMm5pdGUpIHRoZW</p>
                <p>4gc2VuZCB1cyBhIG1lc3NhZ2UgdGhyb3VnaCB0aGUgc3VwcG9y</p>
                <p>dCBzaXRlIHRlbGxpbmcgdXMgeW91J3ZlIGZvdW5kIHRoaXh7kp</p>
                <p>MsIHlvdXIgdXNlcm5hbWUgYW5kIHRvd24gbmFtZS4g</p>
                <p>WW91IHdpbGwgd2luIGEgZGF5IHRvIHBsYXkgYXMgYSBIZXJvLi4uIEFyaXNlIFNpciBDaHVtcCE=</p>
                <p><small>[End of Transmission]</small></p>
                <p><small>ETR: 07/03 12h58 - An error has occurred: corrupt data - status : <s>IGNORED</s>Base<s></s></small></p><s>
                </s>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "4",
            ],
            "stval3_en" => [
                "title" => "D2N Valentines Day",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>Roses are red,<br><br>
                I\'m pretty bored,<br><br>
                But I\'d better look lively<br><br>
                For the attack of the horde!<br><br><br><br>
                Happy Valentine\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "rasln1" => [
                "title" => "Day 22",
                "author" => "Rassalon",
                "content" => [
                    '<h1>Day 22</h1>
                <p>They came in last night. Nearly 200 strong, by all estimates. Seven people gone, just like that, but we\'re still here. Unfortunately, I was badly wounded yesterday and couldn\'t go out. I pitched in where I could, but I can only hope it will be enough for tonight.</p>
                <p>Many in town have gone on the offensive. Zombies are being cut down everywhere, and the surrounding area is as clear as it\'s ever been. It seems like less zombies are gathering in the distance than last night. We may yet withstand another attack, but how much longer can we go on? Water is scarce. Our defenses are battered. I\'m still having nightmares about last night. The fighting.</p>',
                    '<br>
                <p>The screams in the night. Seven of the monsters came for me and I fought them off, but I don\'t know how many more I could handle.</p>
                <p>As night approaches, I will be shoring up where I can. Maybe there will be a tomorrow. Maybe help is coming and we don\'t know about it. We can only hope and pray...</p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "3",
            ],
            "rescl1" => [
                "title" => "Day 3 - Part 1",
                "author" => "LordRuthven",
                "content" => [
                    '<h1>Day 3:</h1>
                <p>Woke up hungover, hungry and thirsty. For a moment life feels normal, for once I feel exactly like I would have before everything went to hell. Then a call comes on the walkie-talkie beside my bed.</p>
                <p>"Is anyone out there? I\'m in sector 2/3, surrounded by zombies. Help, please."</p>
                <p>I groan and pick up the walkie-talkie. "Alright, I\'ll come get you."</p>
                <p>I get up, pop open the microwave and eat the noodles I find there - the microwave doesn\'t work anymore, I just keep my food in there for old times sake... sometimes I make a \'bing\' noise before opening it. I digress.</p>
                <p>After eating I head over to the bank. Well, we call it a bank, it\'s just a big warehouse on the outskirts of town. A gaunt figure watches me warily as I head out to it...</p>',
                    '<p>Jim \'the Hand\' was caught stealing from us, we took his hand and his right to visit the bank as punishment. Now he just stares at us, mumbling about revenge. He looks ill, I think maybe he\'s infected. We\'ll soon find out.</p>
                <p></p>The bank is full of stuff, most of it junk - rotting logs and scrap metal - but I find something interesting. In a wooden box marked \'Military Prototype - Devastator Battery Launcher\'. I pick it up, shake it and something rattles. It\'s broken but this is where my engineering degree finally comes in useful, I find some of the other junk my fellow citizens dumped in the bank, a belt and a few nuts and bolts, and soon I\'ve got the thing working! A victory, for once. Maybe I\'ll survive a few more days after all... unless Jim comes for me in the night... <strong>[contd...]</strong><p></p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "written",
                "chance" => "4",
            ],
            "rescl2_en" => [
                "title" => "Day 3 - Part 2",
                "author" => "LordRuthven",
                "content" => [
                    '<p>...I heft the launcher and put in my backpack along with a spare battery and some twinoid - twinoid doesn’t stop the desert heat from frying your brain but it numbs your brain enough that you don’t care. What will stop your brain frying is water - a precious commodity nowadays. I go to the well, a hole in the ground desperately dug by long-dead landscapers. Come to think of it, this desert is a stupid place to build a town... it’s almost like they knew what would happen, like the whole thing’s just some government experiment to see how long we’ll last. Anyway, I haul the bucket up and fill my flask with the precious liquid.</p>
                <p>Fully prepared I head out. My backpack’s full so I can’t keep anything I find on the way. I just haul it out into the desert sun and hope someone else will pick up the unlabelled drugs some soul dropped before the event. Next sector I find an arm... wonderful. I consider keeping it, meat is meat after all, but it isn’t worth the risk of infection. I’d rather starve than turn into one of them.</p>',
                    '<p></p>
                <p>Before I reach the crest of the hill into sector 2/3 I crash out. I’m half-starved, exhausted and wherever I go I hear shuffling and moaning in the desert winds. Fuck it, it’s time for the twinoid. Maybe I can’t face reality but, hey, who needs reality when it looks like this? I put the pill in my mouth and swallow it with a sip of precious water. That’s better...</p>
                <p>The colours of the desert look sharper, the heat feels more intense but more bearable - like a searing hot bath, at once too hot and satisfyingly cleansing. I can feel the blood pulsing in my head, like the beat of the nightclubs used to be before... before what? Before the blood and the death and the drugs and the sweet oblivion that spares us the pain of knowing genuine oblivion is just around the corner. I haul myself to my feet, get the battery launcher out of my bag and prepare to waste some of those monsters.</p>',
                    '<p></p>
                <p>I crest the hill and I see the guy who called for help, Rob. I don’t know him well, I don’t know any of them well, but I’ve seen him around. Good guy, helped build the workshop, but if he’s out here in the desert alone he can’t be that bright. He’s fending off six zombies, desperately forcing them back with some old bit of pipe he found. The twinoid takes me and I... I start to dance to the beat. I’m dancing and singing and I can’t help it, the drug’s like a force driving my body to actions I never thought it would perform again... <strong>[contd...]</strong></p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "written",
                "chance" => "3",
            ],
            "rescl3" => [
                "title" => "Day 3 - Part 3",
                "author" => "LordRuthven",
                "content" => [
                    '...The zombies notice me and so does Rob. I recognise one of the zombies now, Linda. She wandered out of the town the first night after the event, said something about looking for survivors. Doubt she found any. Rob looks even more terrified, can’t say that I blame him - I’m armed with some prototype alien gun and out of my mind on twinoid. I shout at him, “Duck you stupid bastard!” I think it was a shout, maybe I sang it. Either way he hits the deck, getting a mouthful of sand no doubt. For a moment I wonder what the sand tastes like then I get a grip and fire the Devastator!</p>
                <p>It worked better than I hoped; two zombies wasted. I reload as quickly as I can, my hands shaking from the combined effects of twinoid and the desert sun. This time I took careful aim, I aimed for Linda. “Take this you self-righteous, stupid, suicidal bitch!” I screamed. Rob whimpered, I think he thought I meant him, and I fired my super-gun. Two more zombies down. Two of us versus two of them.</p>',
                    '<p>For some reason the zombies remain cowardly until midnight. They can’t face being outnumbered and back off whenever they fail to outnumber us by at least 3 to 1. Every midnight they charge at the town gates, like a tide of monsters, but until then they hide from any group of the living.</p>
                <p>“Come on, Rob,” I say, “time to go home.”</p>
                <p>“Sure,” He says, eyeing my warily. He takes out a flask of water, gulps it down in one go and starts to follow me back home.</p>
                <p>We get to the gates with no problems. When we get inside the gates, though we find something awful. Just inside the gates one of our number, don’t remember his name, lies on the ground with his intenstines out. A one-handed gaunt figure was kneeling over him, shovelling the man’s innards into his mouth.</p>
                <p>So Jim finally fell to the infection.</p>',
                    '<br>
                <p>There were two of us so he backed off instinctively. We used that to herd him outside the gates and into the desert, then we threw the body out as well.</p>
                <p>They’ll both be back at midnight...</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "written",
                "chance" => "3",
            ],
            "drdiar" => [
                "title" => "Dear Diary",
                "author" => "ISteinier",
                "content" => [
                    '<h1>Dear diary,</h1>
                <p>The weirdest thing happened today. I think I\'ve already written about the day Daddy went outside and when he came back he was all green and Mommy put him in the cellar with chain around his neck, didn\'t I?</p>
                <p>Well, from that day on he never spoke to me or Mommy and he was always screaming and making weird sounds and pulling at his chains to get free. I want to get his chains off sometimes, but I don\'t because Mommy says I can\'t. So, today I was doing like Mommy does sometimes. Sometimes she goes to see him and she talks to him and all he does is scream back. So, sometimes I do the same thing. I tell Daddy what Mommy thought me today or I tell him about what I found in the desert...</p>',
                    '<p>So, I was doing it today too. I was just telling him that I had found out that Ness-Quick can not only kill weeds but that it works on buildings covered with sand too, and then I realised why Daddy didn\'t talk normally. You know how it can be hard to talk if your mouth is dry?</p>
                <p></p>
                <p>Well, I think Daddy has a severe case of mouthdryness, so to make him better again, I went to the well and I took some water (there was just enough to fill a water ration) and then I wanted to give it to Daddy, but then I realised Mommy told me not to take of his chains and so I poured it in his mouth and then Daddy was screaming even more than before and the water was litterally going through his body like it was acid or something.</p>',
                    '<p></p>
                <p>And now everybody\'s mad at me, for taking water they said I didn\'t need, and mad at Mommy because they said it she was doing something irresponsible and she said it was still her husband after all and now the Sherrif wanted was looking for a rusty chain or something, so he could take care of the situation and I don\'t get what happened and nobody listens to me and now Mommy is crying and saying goodbye but I don\'t think we\'re going on holiday so I don\'t get what\'s going on...</p>
                <p>Dear diary, could you please remember me to ask Mommy what this is all about? Thank you. But now I have to sleep, because otherwise I\'ll be very tired tommorrow.</p>
                <p>Good night, my dear diary.</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "written",
                "chance" => "4",
            ],
            "desktp" => [
                "title" => "Desktop Factoids",
                "author" => "ShaftWildly",
                "content" => [
                    '<br><p>July used to be called Quintilis</p>
                It was renamed after the death of Julius Caesar.<p></p>
                <br>
                <p>August used to be called Sextilis</p>
                It was renamed after the new emperor Augustus Caesar.<p></p>',
                    '<div class="hr"></div>
                <p>The famous Paris - Dakar rally neither starts in Paris nor ends in Dakar.</p><br>
                <p>In 2014 it began in Rosario, Santa Fé, Argentina and finished in Valparaiso, Chile.</p>',
                    '<div class="hr"></div>
                <p>Shotgun weddings are statistically more likely when the parents know the child will be a boy.</p>',
                    '<div class="hr"></div>
                <p>Human\'s share 50% of their DNA with bananas.</p>',
                    '<div class="hr"></div>
                <p>More humans are killed every year by vending machines than sharks.</p>',
                    '<div class="hr"></div>
                <p>The most northern, eastern and western points in the USA are all in Alaska</p>',
                    '<div class="hr"></div>'
                ],
                "lang" => "en",
                "background" => "postit",
                "design" => "written",
                "chance" => "1",
            ],
            "slber1" => [
                "title" => "Diplomatic Invitation",
                "author" => "Berlusconi, S.",
                "content" => [
                    '<p><strong>Dear <s>Sir or</s> Madam</strong></p>
                <p>You are cordially invited to the 39th parliamentary Bunga Bunga party</p>
                <p>Please inform the committee if you intend to attend alone or with a partner.</p>
                <p>Please also check the box provided if you require a position in the cabinet following this event</p>
                <p>If you have no history in modelling or the sex industry please include a full-length photo</p>
                <p>Yours Bunga-tastically,</p>
                <p>Silvio</p>',
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "typed",
                "chance" => "2",
            ],
            "slber2" => [
                "title" => "Diplomatic Retraction",
                "author" => "Berlusconi, S.",
                "content" => [
                    '<p><strong>Dear <strike>Sir or</strike> Madam</strong></p>
				<p>It is with regret that we have had to cancel the 39th parliamentary Bunga Bunga party</p>
				<p>This is due to (not entirely) unforeseen circumstances.</p>
				<p>Please check the box provided if you have experience in cake baking and file smuggling</p>
				<p>and return it for the attention of Big Sadie, Cell Block D, Il Clinki Prisoni, Italia. </p>
				<p>Yours Bung-requiringly,</p>
				<p>S.B</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "typed",
                "chance" => "2",
            ],
            "swefae_en" => [
                "title" => "Eulogy for an Empire",
                "author" => "Soundwave",
                "content" => [
                    '<p>That which has transpired takes no turn.
                And so, we will vanish into the darkness
                and fade away but utterly
                until naught but silence remains.</p>
                <br>
                <p>No more will our voices be heard;
                another light goes out in civilisation.
                No more merriment, nary a laugh...
                naught but silence remains.</p>
                <br>
                <p>No more sadness, no more joy,
                an end to our folly, pride births shame,
                a long walk into the desert
                until none remembers our name.</p>
                <br>
                <p>We fought, we struggled and more
                until lifeless hands tore us asunder.
                We are now gone, we are no one,
                none remembers our name.</p>',
                    '<br>
                <p>Forty souls stood valiantly
                against the undead thousand.
                Think not poorly of us,
                but gently breathe our names.</p>
                <br>
                <p>It is with joy that I put down this pen
                for from it, hope springs anew
                in you, my unknown friend
                because now, you know us too...</p>
                <br>
                <br>
                Soundwave - Oct 7, 2011'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "burns1" => [
                "title" => "Happy Burns' Day",
                "author" => "Robert Burns",
                "content" => [
                    '<p></p><center>Of a\' the airts the wind can blaw<br><br>
                I dearly like the west,<br><br>
                For there the bonie lassie lives,<br><br>
                The lassie I lo\'e best.<br><br>
                There wild woods grow, and rivers row,<br><br>
                And monie a hill between,<br><br>
                But day and night my fancy\'s flight<br><br>
                Is ever wi\' my Jean.</center>
                <br><br>
                <p> - Robert Burns</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "written",
                "chance" => "2",
            ],
            "stpat2" => [
                "title" => "Happy for you, blind man !",
                "author" => "Traditional Irish",
                "content" => [
                    '<br><br><br>
                <p>Happy for you, blind man, who see nothing of women! </p>
                <p>Ah, if you saw what I see you would be sick even as I am.</p>
                <p>Would God I had been blind before I saw her curling hair, her white flanked splendid snowy body; ah, my life is distressful to me.</p>
                <p>I pitied blind men until my peril grew beyond all sorrow, I have changed my pity, though pitiful, to envy; I am ensnared by the maid of the curling locks.</p>',
                    '<br>
                <br>
                <p>Alas for him who has seen her, and alas for him who does not see her every day; alas for those trapped in her love, and alas for those who are set free!</p>
                <p>Alas for him who goes to meet her, and alas for him who does not meet her always, alas for him who was with her, and alas for him who is not with her!</p>
                <p>-Irish, Uilliam Ruadh; 16th Century</p> '
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "2",
            ],
            "citya1_en" => [
                "title" => "Happy Harry's Horoscope: 1",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Pisces</h1>
                <p>People don\'t get you. They enjoy parties more than you. You have bad sex and it\'s your fault. A man named Ian will take your stuff.</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "citya2_en" => [
                "title" => "Happy Harry's Horoscope: 2",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Aries</h1>
                <p>You\'re not as interesting as you think you are. Your shyness reads as arrogance. Your friends quip about your insecurities, which are obvious.</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "citya3_en" => [
                "title" => "Happy Harry's Horoscope: 3",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Gemini</h1>
                <p>Stop talking about yourself all the time. It\'s pathetic, and people think the jokey racism runs deep. You\'re also out of shape.</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "citya4_en" => [
                "title" => "Happy Harry's Horoscope: 4",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Taurus</h1>
                <p>People call you \'The Nipple\'. You\'re a bit of a tit.</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "citya5_en" => [
                "title" => "Happy Harry's Horoscope: 5",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Libra</h1>
                <p>If you heard your friends say about you what you say about them, you\'d be devastated. And they do. You\'re just a bit-part in their lives.</p>
                </div>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "cityb1_en" => [
                "title" => "Happy Harry's Horoscope: 6",
                "author" => "TheMentalist",
                "content" => [
                    '<div class="hr"></div>
                <h1>Leo</h1>
                <p>You\'ll look back and regret wasting so much time. You\'re basically doing nothing you\'ll wish you\'d done when you die.</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "typed",
                "chance" => "2",
            ],
            "code1_en" => [
                "title" => "Illegible Note",
                "author" => null,
                "content" => [
                    '<p><strong>-1</strong></p>
                <p>oktr qhdm m drs rtq</p>
                <p>st cnhr pthssdq kz uhkkd zt oktr uhsd</p>
                <p>hkr rnms sntr cdudmtr entr hbh</p>
                <p>qdsqntud lnh z kz uhdhkkd onlod gxcqztkhptd z bhmp gdtqdr</p>
                <p>hk x z tmd lnsn bzbgdd kz azr</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "4",
            ],
            "tstpa1" => [
                "title" => "Irish Anthem",
                "author" => "Phil Coulter",
                "content" => [
                    '<br><h1>Ireland\'s Call</h1><br>
                <p>Come the day and come the hour</p>
                <p>Come the power and the glory</p>
                <p>We have come to answer</p>
                <p>Our Country\'s call</p>
                <p>From the four proud provinces of Ireland</p>
                <br>
                <p>Ireland, Ireland</p>
                <p>Together standing tall</p>
                <p>Shoulder to shoulder</p>
                <p>We\'ll answer Ireland\'s call </p>
                <br>',
                    '<br><br><br>
                <p>From the mighty Glens of Antrim</p>
                <p>From the rugged hills of Galway</p>
                <p>From the walls of Limerick</p>
                <p>And Dublin Bay</p>
                <p>From the four proud provinces of Ireland</p>
                <br>
                <p>Ireland, Ireland</p>
                <p>Together standing tall</p>
                <p>Shoulder to shoulder</p>
                <p>We\'ll answer Ireland\'s call </p>
                <br>',
                    '<br><br><br>
                <p>Hearts of steel, and heads unbowing</p>
                <p>Vowing never to be broken</p>
                <p>We will fight, until</p>
                <p>We can fight no more</p>
                <p>From the four proud provinces of Ireland</p>
                <br>
                <p>Ireland, Ireland</p>
                <p>Together standing tall</p>
                <p>Shoulder to shoulder</p>
                <p>We\'ll answer Ireland\'s call </p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "1",
            ],
            "stpat4" => [
                "title" => "Irish Blessing",
                "author" => "Traditional Irish",
                "content" => [
                    '<br>
                <br>
                <p>May the road rise to meet you.</p>
                <p>May the wind be always at your back.</p>
                <p>May the sun shine warm upon your face.</p>
                <p>And rains fall soft upon your fields. </p>
                <p>And until we meet again, </p>
                <p>May God hold you in the hollow of His hand.</p>
                <br><br>
                <p>- Traditional</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "2",
            ],
            "stpat5_en" => [
                "title" => "Irish Proverbs",
                "author" => "Various",
                "content" => [
                    '<br><br>
                <p>Drink is the curse of the land. It makes you fight with your neighbor. It makes you shoot at your landlord, and it makes you miss him.</p><br>	
                <p>Your feet will bring you where your heart is.</p><br>
                <p>An Irishman is never drunk as long as he can hold onto one blade of grass and not fall off the face of the earth. </p><br>
                <p>God is good, but never dance in a small boat.</p><br>',
                    '<br><br>
                <p>It is better to be a coward for a minute than dead the rest of your life. </p><br>
                <p>May misfortune follow you the rest of your life, but never catch up. </p><br>
                <p>Here\'s to all of the women who have used me and abused me... and may they continue to do so! </p>
                <br><br>
                <p> - A selection of Irish sayings and proverbs</p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "2",
            ],
            "nwyrjk_en" => [
                "title" => "January 1",
                "author" => "JKudlick",
                "content" => [
                    '<h1>January 1</h1>
                <p>We had one hell of a celebration last night. One of the other citizens found some old fireworks while scavenging the desert yesterday. When he came back, we gave him the last bottle of “Wake the Dead” we pilfered from the Shady Bar. While he got sloshed, the rest of us went to work making additions to the wall.</p>
                <br>
                <p>The horde attacked at midnight. But this time, rather than cowering in our tents, we counted down until the attack. At midnight, we shot off the fireworks. Not into the sky, but into the horde. It turns out explosives work as well as water to kill the zeds. It’s fun watching them writhe around while their legs are a hundred feet away. Happy New Year.</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "letter",
                "chance" => "4",
            ],
            "biebs1" => [
                "title" => "Justin Time",
                "author" => null,
                "content" => [
                    '<h1>Bieber Busted!</h1>
                <br><p></p><center>Justin Bieber has a Valentine\'s Day date with a Florida judge on charges of driving under the influence, resisting arrest and driving with an expired licence.<br><br> A Miami-Dade County judge on Tuesday set a February 14 arraignment date for the 19-year-old pop star. Bieber and R&amp;B singer Khalil were arrested last week in Miami Beach after what police described as an illegal street race between a Lamborghini and a Ferrari..</center><p></p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "2",
            ],
            "noel_en" => [
                "title" => "Letter To Father Christmas",
                "author" => "zhack",
                "content" => [
                    '<p>Dear Father Christmas,</p>
                <p>This year I have been extra special good because Mummy was sad all the time since daddy went to war with the monsters.</p>
                <p>It\'s hard to sleep because the monsters attack the camp all the time.</p>
                <p>If you could send some presents to daddy, I\'d be really happy, because I miss him, and I hope he\'s ok. Please send him lots of presents and my letter because I don\'t know where he is and mummy won\'t tell me. (Love you lots daddy !!)</p>',
                    '<p>Last year I didn\'t get my Barbie magic pony but I\'m not annoyed at you because we had to move and you couldn\'t have known, and I don\'t want it any more anyway. I\'d really like an Aquasplash so that mummy can defend us against the monsters, because there are less and less of us in the village, and I don\'t want the monsters to put us in their prisons (mummy told me that they are really bad and don\'t give you any dessert if you misbehave).</p>
                <p>That\'s all I want this year. Lots of love Father Christmas and thanks for everything</p>
                <p>I know it\'s stupid to ask for a water pistol but that they are even more stupid because they are afraid of water (they probably don\'t take many showers).</p>
                <p>Elisa.</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "2",
            ],
            "nelly_en" => [
                "title" => "Letter to Nelly",
                "author" => "aera10",
                "content" => [
                    '<p>Dear Nelly,</p>
                <p>I am writing to you as I can\'t live in my town any longer.</p>
                <p>There has been no electricity in town for days now, the Internet doesn\'t work, phone lines are down and there is no mobile signal. Worst of all, there are strange things happening. Some routes have been cut off by gigantic rocks which came out of nowhere.</p>
                <p>Yesterday I saw a man, well, I\'m not all that sure if it was a man... it was a strange creature wrapped in a curtain, rummaging in the garbage. He limped along towards that crazy woman\'s apartment which was two buildings away. She was found torn to pieces this morning. I posted this letter from a neighbouring town. I\'m only sending you this to warn you of my arrival.</p>
                <p>By the time you read this letter, I\'ll be well on my way. </p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "3",
            ],
            "gbhor1" => [
                "title" => "Lost Horizons",
                "author" => "Hopkins, D. (1961-1993)",
                "content" => [
                    '<br><p>Maybe I could use you to reassure myself,</p><br>
                <p>I wouldn\'t wish this indecision on anybody else,</p><br>
                <p>Drink enough of anything to make this world look new again,</p><br>
                <p>And when the sin smiles how could it be wrong </p><br>
                <p>The last horizons I could see are now resigned to memories</p><br>
                <p>I never thought I\'d still be here today...</p><br>
                <p>Drink enough of anything to make myself look new again</p><br>
                <p>Drunk drunk drunk in the gardens and the graves</p><br>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "poem",
                "chance" => "3",
            ],
            "crema1_en" => [
                "title" => "Luck of the draw",
                "author" => "Stravingo",
                "content" => [
                    '<quote>The Bastion of Shattered Illusions,11th July</quote>
                <p>It\'s been days... weeks even, since we had any real food. Hunger grips the town, and there is no escape. Even though we have built barricades, the horde is larger than ever, and growing by the day. I can\'t go on. I don\'t have the energy or the strength... </p>
                <p>This morning we drew lots. I lost, but I don\'t care. The others seemed almost envious. I can smell the charcoal as they fire up the cremato-cue. They told me I\'ll hardly feel a thing, and that they saved me a whole bottle of vodka. I know they\'re lying though...</p>
                <p>...because i stole the last one...</p>
                <p><em>Stravingo</em></p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "written",
                "chance" => "4",
            ],
            "bilan_en" => [
                "title" => "Minutes of the meeting of 24th August",
                "author" => "Liior",
                "content" => [
                    '<h1>Town meeting: 24th August:<small>(Retranscribed by Liior, The Chronicle)</small></h1>
                <p>The chief explained that we had undertaken a huge construction project, which just might "save our lives" :</p>
                <quote>"It\'s an extreme project, (some would say crazy)! But it just might work. We have already invested lots of time and energy organising the town and increase anti-zombie efficiency, but there is still work to be done. I had the idea that maybe if we created an enormous decoy, the zombies would stop coming... We must build a false town... It might seem odd, but I am pretty sure the zombies can\'t tell the difference between this town and any other...  "</quote>',
                    '<p>The body of the hall seemed sceptical :</p>
                <quote>"A fake town? That will fool the zombies? the other citizens seemed to ask one another in an incomprehensible rabble".</quote>
                <p>Nonetheless, the project was voted in... I don\'t think much hope remains...</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "mjtjrs_en" => [
                "title" => "Miserable Junctions Journal",
                "author" => "Rassalon",
                "content" => [
                    '<br>
                <h1>Day 21 since "The Event"</h1>
                <p>Today marks the 21st day since the event that spawned the end of the world. We\'ll probably never know what actually happened, but I am proud to say we\'ve done our best.</p>
                <p>We\'ve been unable to contact anyone else, so it looks like we are the last survivors of this disaster, though not for much longer. Looking out over the ramparts, I can see thousands of the undead gathering for their attack. Our defenses, which have stood so admirably and prevented a single zombie from entering the town will not be enough.</p>
                <p>I wanted to be sure some record of our epic struggle survives, even if we don\'t. There are too many people to mention all by name, but I will name a few.</p>',
                    '<p>Our leader, HarryPot never let us lay still and never let us give up. It was his vision and direction that kept us focused, even when we disagreed.</p>
                <p>Our zombie squad hunters: Raynor, Zombiehunter4 and Rich, who selflessly cleared the paths that help us gather much needed supplies.</p>
                <p>Lastly, all the brave souls who ventured into the great beyond to gather the supplies and fortify the defenses.</p>
                <p>I shall be proud to defend these people with my last breath.</p>
                <p>-Rassalon</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "written",
                "chance" => "4",
            ],
            "stpmm1" => [
                "title" => "Molly Malone",
                "author" => "Traditional.",
                "content" => [
                    '<p>In Dublin\'s fair city, where the girls are so pretty,</p>
                <p>I first set my eyes on sweet Molly Malone</p>
                <p>As she wheeled her wheelbarrow<br> through streets broad and narrow</p>
                <p>Crying cockles and mussels alive a-live O!</p>
                <br>
                <p>A-live a-live O! A-live a-live O!</p>
                <p>Crying cockles and mussels alive a-live O!</p>
                <br>
                <p>She was a fishmonger, and sure t\'was no wonder,</p>
                <p>For so were her father and mother before,</p>
                <p>And they both wheeled their barrows<br> through streets broad and narrow,</p>
                <p>Crying cockles and mussels alive a-live O!</p>
                <br>
                <p>A-live a-live O! A-live a-live O!</p>
                <p>Crying cockles and mussels alive a-live O!</p>',
                    '<p>She died of a fever, and no-one could save her,</p>
                <p>And that was the end of sweet Molly Malone,</p>
                <p>Now her ghost wheels her barrow<br> through streets broad and narrow,</p>
                <p>Crying cockles and mussels alive a-live O!</p>
                <br>
                <p>A-live a-live O! A-live a-live O!</p>
                <p>Crying cockles and mussels alive a-live O!</p>   
                <p>A-live a-live O! A-live a-live O!</p>
                <p>Crying cockles and mussels alive a-live O!</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "2",
            ],
            "motran" => [
                "title" => "Motherhood - Part 1",
                "author" => "Raindragon16",
                "content" => [
                    '<p>“Mommie.”  he whispered in her ear.  Her eyes fluttered for a moment, she moaned rolling over to her side, smiling at her little boy.</p>
                <p>“Hey sweetie.”  He held out a small canteen light with water, “Just put it beside me baby, I’m not thirsty.”  She wasn’t, been days since she needed a drink.  One his knees he shook the canteen a little, his eyes narrow with worry.  She took the can and sipped it.  The water burned down her throat and she coughed violently, spilling water everywhere.  Specks of blood spotted the floor of plastic. </p>
                <p>Sitting down cross-legged, the son looked at her hot face, wet brown hair, her torn uniform and the gaping wound on her leg.</p>',
                    '<p>Holding the tears back, he hid his face from her, breathing his thumping heart in deep. He told nobody in the shack town about the wound, he knew there was nothing they could do, they would just kill her.  She let her head rest on the dusty plastic of the dark green tent, it crinkled a little.  “Baby, go outside and help the others.  Mommy is fine, she just needs to sleep.”  He left the tent, careful not to let anyone see inside.</p>
                <p>Her whole body was cooking in the dry desert air, sweat came somehow, even though she was empty, she where the water fell on her; patches of swollen and burning skin bubbled up like boils.  She knew it wouldn’t be long before she would become a monster, she left the canteen upright and started to moan, no tears came. </p>',
                    '<p>Maybe she should let them shoot her in the head, end it all, but quiet like and have them tell her son she died from a fever in the night.  Most of them were nice enough people, they let her in with her son, they had clean water, clean clothes; it was somewhere...</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "5",
            ],
            "motrao" => [
                "title" => "Motherhood - Part 2",
                "author" => "Raindragon16",
                "content" => [
                    '<br><br>
                <p>...She missed being social, so long ago; she was in her summer dress, her son was helping her bake cookies while she walked back and forth around the TV listening to the news update about quarantine zones.  Sprinkles fell on white icing like dust; she smiled at her son, holding the doorway with a creaking hard grip.</p>
                <p></p>Knocking feet, banging fists, the breaking window, shot her up from bed, into her thick slippers, down the hall, turning on no lights and into her son’s room.  She saw lights flicking on in other houses, the monsters wailed towards the signs of fresh life, screams followed.<p></p>',
                    '<br>
                <p></p>The picket fences were grey, porches dark under dim desert stars, the car already packed with ten gallons of water, a few clothes, first aid kit and a small stash of dry and canned food; she was prepared.  He was still asleep in his dinosaur pjs, his brown hair all messed up, nostrils flaring with his breath; all she needed to do was pick him up, grab the cars keys and drive as fast as she could in her blue pickup to somewhere.<p></p>
                <p></p>The son ran through the dirt, the big shoes flopping, laces untied, to the workshop straight to one of the men, he was an older, short and grey in the eyes, but the small boy brought some light into his eyes.<p></p>',
                    '<p>The boy was the only child there in the camp, not many survived the trek through the desert, arriving dead in their parent’s arms.  Some parents didn’t live long after; a rope, a knife, cyanide missing from the bank, their eyes always empty.   “Hey.  I’m here to work.”  The boy smiled wide, his canteen slapping his thigh, too long pants dragging in the dirt with the oversized shoes, “How’s your mom?”  The man asked.</p>
                <p></p>The boy’s eyes flashed with worry, he tried to cover with a smile, “Tired.”  <p></p>
                <p></p>He nodded his head, taking the boy by the shoulder over to a workbench covered in planks and nails.  The boy hopped up on a stool and held a plank in place, the man smiled, “You just think yah know what I want eh?”...<p></p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "4",
            ],
            "motrap" => [
                "title" => "Motherhood - Part 3",
                "author" => "Raindragon16",
                "content" => [
                    '<br><br>
                <p>The boy widely smiled, two front teeth missing, “Yep.” The man grabbed a hammer and started to align the nails to pound them in.  The project today was a watchtower to help the residence guess how many zombies may come during the night.  They were lucky so far, only four deaths, but it was only a sign of how close to death they all were.</p>
                <p>“It’s been about three days since yer mom came out.”</p>
                <p>The boy didn’t look at him, “She’s just tired.  Hasn’t slept good.”  The boy’s face twisted with stress, even though he was trying not to turn his head towards the man.  “I gotta go somewhere.”</p>',
                    '<p>Sliding off the stool, his shoes hit the dirt wood floor and the boy ran past the other workers, through the dusty paths, the man stared after him with concern, “I’ll check on em soon as I’m done here.”</p>
                <p>The boy stopped short of the green tent, glancing side to side with suspicious eyes, nobody, nothing, zilch, not even the wind blew.  “Mommie.”  He whispered, opening the flap, it was dark inside, but he saw the outline of his mother huddled near the back, “Are you feeling better?” She moved back and forth in a whole body nod.  He slid in, the flap let a sliver of sun in, his saw his mother’s hand was red and puffy. Walking on his knees, he saw the open canteen, “Oh good,”  picking it up, “you drank…”</p>',
                    '<p>A low growl made the plastic tent shake, “Mommie?”  He clutched the canteen, eyes wide with fear; slowly he stepped back on his knees.</p>
                <p>Her moans became louder, higher, rougher, he swallowed and she turned around, the streak of sun cutting across her body.  Her dirty hair clumped up in bunches, yellow patches bulged around her face, her eyes were bloodshot, she gritted her thin yellow teeth, her son’s mouth opened, she growled like a dog swollen hands swiping for him.  He knew the zombies melted at the contact of water, he held the open canteen, unsure, he looked to his mother; the yellow bulges broke open, puss and blood gushed out, he felt a surge of acid tickle his throat.   She lunged at him; knocking straight into the canteen, water dumped into the new open wounds, she couldn’t scream, her throat was melting away...</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "3",
            ],
            "motraq" => [
                "title" => "Motherhood - Part 4",
                "author" => "Raindragon16",
                "content" => [
                    '<p>...Chunks of flesh dripped onto the sandy plastic, her fingers were mostly bone from clutching her water downed throat, her eyes open in terror, head tilted, the boy just stared at his mother covered in water, blood and sand, the smell of rotten meat tickling his stomach to puke.  He finally did.</p>
                <p>Wiping his mouth, he began to cry.  “It’s ok mommie.”  He crawled to her and curled up on the reeking corpse.  Wrapping his fingers around her squishy, still warm hands, he shut his eyes and hummed a soft lullaby.</p>
                <p>“Here to check on yah.” the man said outside the tent, holding the flashlight down.  He heard nothing and opened the tent flap slowly, “Are you ok?”  The flashlight circled on the boy sleeping on what was left of his mother.</p>',
                    '<br><br>
                <p>Her body was more of a puddle seeping into her uniform, her hands were barely intact now between the boy’s, his clothes also sopping wet.  A wave of rotten smell burned his nose; he covered his face and kept starting, trying not to gag.</p><br> <br>
                <p>The boy’s eyelids moved, clicking the flashlight off he plunged into the tent feeling the ooze between his hands, lifting the boy up from the stick with a sick sound of popping bones.</p>',
                    '<br><br><br>
                <p>The thick air choked the man’s throat, the crinkling plastic was slippery on his large knees, his shoes were wet from the melting remains, “You’re ok, you’re ok, you’re ok.”  He whispered for himself and the boy when he shouldered through the flap, opening his lungs to the dry clear air, the boy still clutched his mother’s hands under the dim desert stars, a breeze whispered; taking the smell of death far away to somewhere and the man wept.</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "typed",
                "chance" => "2",
            ],
            "jgoul1" => [
                "title" => "My friend the Ghoul - Part 1",
                "author" => "ninjaja",
                "content" => [
                    '<h2>My friend the ghoul, part one</h2>
                <p>On the road, I tripped over a small notepad half buried in the sand, firing it into the air.
                The pages were blank, time had erased everything that had once been there save a few faded letters.</p>
                <p>Two or three drags across the sole of my old sandal was all it took to resurrect the attached pen.
                Without thinking, I put the lot in my pocket. I would never have imagined that I\'d be needing it so soon.</p>',
                    '<p>We had heard of a small shanty town, up in the north, in the middle of a desert plain. When we arrived, there were Sir Trotk and myself, and two bros; Mido and Ven.</p>
                <p>The idea of a religion-free town cheered us up. Maybe we\'d be able to take part in the Festival of AnnualRainfall, the Ascension of the Crow, or some group worship of the one they simply refer to as Deblyn.
                Would that merit a story?</p>
                <p>Let\'s leave it for now in any case. The town is atheist...</p>'
                ],
                "lang" => "en",
                "background" => "secret",
                "design" => "written",
                "chance" => "4",
            ],
            "jgoul2" => [
                "title" => "My friend the Gouhl - Part 2",
                "author" => "ninjaja",
                "content" => [
                    '<h2>My friend the Ghoul - Part Two</h2>
                <p>Day One:</p>
                <p>So we got here... We didn\'t all go through the gates at the same time - basic security precaution.
                The town is buzzing. It looks like loads of the people already know each other. Surely there other grups just like us.</p>
                <p>One man seemed to be standing out from the melee, giving orders.
                We\'d come to call him Sir N.
                "Sir" for respect for his work and his tenacity.
                "N." out of decency, given what we were about to put him though.
                </p>',
                    '<p>Barely in the door, we started to get ready to set off into the desert.</p>
                <p>Mr Mido had vanished. he had heard about an armory on the outer limits of the exploration zone.
                He was already on it. We\'d only see him rarely from then on, this former interior designer was focused on stripping the area of every last shiny object.
                My other friends set off calmly to explore the immediate surroundings, and I started making my way towards a freshly discovered ruin.</p>'
                ],
                "lang" => "en",
                "background" => "secret",
                "design" => "written",
                "chance" => "4",
            ],
            "nicev2" => [
                "title" => "Natural Selection",
                "author" => "Stravingo",
                "content" => [
                    '<p>I am often told that my kindness and generosity are unparalleled.</p>
                <p>It\'s true, I love helping out, taking charge of tasks which are for the good of others. I\'ve given tirelessly of myself for this community, even encouraging the weakest amongst us.</p>
                <p>I remain incredibly polite. i am also admired for my negotiating talents. When there is tension, which sparks altercations, the townsfolk are happy for me to intervene. They know that I will soon return peace to the town.</p>
                <p>I inspire confidence and others have confidence in me.</p>
                <p>Today they have all left on an expedition in search of the necessary equipment to survive one day longer.</p>
                <p>I can hear them. They\'ve been knocking on the gates for several hours now. Insults have given way to pleading. Night is fast approaching...</p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "small",
                "chance" => "1",
            ],
            "nitemr" => [
                "title" => "On Nightmares",
                "author" => "DeadLucky",
                "content" => [
                    '<p>When I was a child, like any other, I was terrified of the boogeyman. I dreamt of his cold, lifeless hands reaching for my warm, sleeping body. His eyes stared out at me from the dark corners of the night, my imagination filling the shadows with horror. I would wake up screaming bloody murder in the middle of the night, only to find myself very much alone.</p>
                <br>
                <p>My grandfather was a minister, and he placed all of his faith in the Church. One night, he heard the startled gasps that marked my release from the clutches of a nightmare and he came to console me. He explained to me that my dreams of evil were merely the Devil trying to frighten me, and that all I had to do to conquer this evil was to pray for strength and safety. That night we prayed together, and trusting my grandfather\'s wisdom, I never had that dream again.</p>',
                    '<br>
                <p>Oh, now though, now my dreams are of being safe and sound in my bed as a child. When I wake, I am affronted with the bloated, decomposition smell of the ghouls surrounding the town. I hear the scratching and pounding of the Horde mindlessly working away at our barricades in the night. I can again imagine the fingers of the dead grasping at my helpless flesh.</p>
                <br>
                <p>You see, my grandfather was wrong. Those dreams weren\'t demonic. They were a warning, a warning that none of us paid any mind. And this time...</p>
                <br>
                <p>...prayer will do nothing to save us.</p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "classic",
                "chance" => "4",
            ],
            "oathlb" => [
                "title" => "Oath of the Live2nite Brotherhood",
                "author" => "Soupfist",
                "content" => [
                    '<p>We who seek to wear the camouflage cloak and move unseen through the wastes, who seek to dart impudently through the densest ranks of the undead, who seek to beat paths into Hell so that the feet of the living shall never walk into danger, must have a code that guides our hearts and our actions. Within the tenets of this code, the scout\'s mind is sharp, his decisions sure and swift, and his survival in the wastes assured. In the footprints of the scouts of the Live2Nite brotherhood, order springs like geysers of fresh water amid the dry sands of chaos.</p>
                <p>All scouts at this time who wish to pledge themselves to the Live2Nite brotherhood raise high the thumb, index and ring fingers of the left hand and pledge the L2N Brotherhood Oath:</p>
                <ol>
                <li>A Live2Nite leaves no man in the wastes who requires his help.</li>
                </ol>',
                    '<ol start="2">
                <li>A Live2Nite leads the way for scavengers to dig in safety.</li>
                <li>A Live2Nite sleeps in nothing but tents, for wood is precious and meant to protect the helpless.</li>
                <li>A Live2Nite may call for help, but never admit his camouflage has failed.</li>
                <li>A Live2Nite never leaves the body of another Live2Nite in the desert - he will retrieve it and use it for trap bait, for a Live2Nite is dedicated to helping others, even in death.</li>
                <li>A Live2Nite does not say "hopeless." No matter how sure the demise and how nigh the end may be, he ends each evening reciting the Live2Nite brotherhood motto: "Tonight, we live!"</li>
                </ol>
                <p>Go forth with the L2N badge upon your cowl and spread order among the living.</p>',
                    '<br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <br>
                <h1><strong>"Tonight we live!"</strong></h1>'
                ],
                "lang" => "en",
                "background" => "grid",
                "design" => "modern",
                "chance" => "3",
            ],
            "pcrsh1_en" => [
                "title" => "Polycarbonate Riot Shield",
                "author" => "Workshop",
                "content" => [
                    '<h1>Polycarbonate Riot Shield</h1>
                <h2>Series #0418SP, Model MT</h2>
                <ul>
                <li>Lightweight protection device</li> 
                <li>Reinforced central area</li> 
                <li>Ideal for all your desert security needs.</li> 
                <li>Cup holder optional.</li>
                <li>Easily washable - no blood on your hands!</li>
                <li>6 months warranty (extendable).</li>
                </ul>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "ad",
                "chance" => "3",
            ],
            "granma_en" => [
                "title" => "Post-it",
                "author" => "sunsky",
                "content" => [
                    '<p>Mom, you\'ve been sleeping for three days.</p>
                <p>I\'m cold and you\'re not answering me!</p>
                <p>There are people making lots of noise late at night. I\'m going to see if they want to play with my ball.</p>'
                ],
                "lang" => "en",
                "background" => "postit",
                "design" => "small",
                "chance" => "3",
            ],
            "qwp1jl" => [
                "title" => "Quark and the Watchtower - Part 1",
                "author" => "JohnLeaf",
                "content" => [
                    '<p>Quark walked up to the watchtower, looking out over the waste that they called, \'home\'. This was the eighth town he had been in on his journey. Each one before had been destroyed by zombies, and he had escaped, using nothing but his wits. He was wearing a cloak the color of shadows, causing him to blend in, the clouds blocking the sun causing him to be able to hide in the darkness whenever he wished.</p>
                <p>He let out a sigh, spotting hordes of zombies all around the city, and in the distance, at least 200 or so zombies. He stepped on to the ledge of the tower, looking down. After a moment, he heard someone behind him. </p>
                <p>"Don\'t move." A dark voice spoke out to him. He didn\'t turn. He knew he\'d be dead if he did.</p>',
                    '<p>"What do you want?" Quark asked, his hand slowly slipping to his waist, the movements hidden by the cloak which covered his arms. He gripped his dagger.</p>
                <p>"You\'ve obviously lived in more than one town. You didn\'t live here before. You may have fooled the others into thinking you\'re just a traveler who was holed up near the town before all this began, but you can\'t fool me."</p>
                <p>"And what if you\'re right?" Quark raised an eyebrow, although the man behind him couldn\'t see...</p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "4",
            ],
            "qwp2jl" => [
                "title" => "Quark and the Watchtower - Part 2",
                "author" => "JohnLeaf",
                "content" => [
                    '<p>..."Because I need your help to get out of this hell hole. This town won\'t survive for more than a month long. It\'s been a year, and ever since you came, the zombies have been gathering in the hundreds..."</p>
                <p>Quark let out a sigh. "I\'m sorry, but you\'re wrong. the zombies have nothing to do with me. You\'re just insane."</p>
                <p>"No, I\'m not. You know I\'m right. There\'s no use denying it." the man frowned, tightening his hold on his gun, which was pointed at Quark\'s head.</p>
                <p>"Fine. You\'re right." Quark lied.</p>
                <p>"I knew it! I was r-" the man was cut off mid sentence, a slim dagger finding it\'s way from Quark\'s cloak, to the man\'s head. He fell back, blood staining the floor as he fell down the stairs. </p>
                <p>Quark looked over the ledge, leaping forward. He landed on the roof of a house, rolling to spread the pressure from the fall, avoiding injury. He jumped to the street, walking away, the echoing screams of the men who had discovered the body, ringing through his ears as he walked.</p>'
                ],
                "lang" => "en",
                "background" => "printer",
                "design" => "typed",
                "chance" => "2",
            ],
            "utpia1_en" => [
                "title" => "Rough Draft",
                "author" => null,
                "content" => [
                    '<p>The guy was sure<s>ly right</s>. Coordinates (approx): <s>210</s>125 North 210 West. </p>
                <p>To do:</p>
                <ul>
                <li>vehicle (search the parking lot in the north)</li>
                <li>wa<s>ter (15 litres)</s></li>
                <li>provisions (find Bretov\'s house; watch out for infection)</li>
                <li>"Citadel" ? What is this ??</li>
                </ul>
                <p>Need to find the <strong>I-40</strong>...<s>that IS</s>the way.</p>
                <p>the r<s>av</s>en???!? Who is the ra<s>ve</s>n ? <s>find out who they are</s>MUST KILL<s></s></p><s>
                <blockquote>Meeting @ 16h !!!</blockquote>
                <p>find <strong>CITADEL</strong></p>
                </s>'
                ],
                "lang" => "en",
                "background" => "secret",
                "design" => "small",
                "chance" => "2",
            ],
            "shbpap" => [
                "title" => "Shabby piece of paper",
                "author" => "Yummlick",
                "content" => [
                    '<p>As always, if you\'re reading this, blah, blah, it means we didn\'t make it and I\'m dead, blah, bl[...illegible...]ut that\'s not important! I\'m not impo[...illegible...]ad this and PASS IT forward. Tell them! Tell them all!</p>
                <p>Thirty nights in a row, they come. We\'re tired, sca[...illegible...]t of resources. I don\'t know how we managed it, but we hold again. Barely, again.</p>
                <p>Here comes a da[...illegible...]er beautiful dawn. But this one is different. There\'s a man. THE MAN outside the gates. Alive. Heavily armed, well equiped. Dirty, bearded an[...illegible...]ll smiled. Wanderer, survivor. Preacher.</p>
                <p>He tol[...illegible...]ut the great EXODUS. About himself and his former companions in misfortune, who decided not to wait for death, but to seek for redemption.</p>',
                    'And they found it! An OCEAN. There\'s an ocean, whole ocean of water, somewhere in t[...illegible...]T. And there\'s a bay. The bay with ships. Huge, old ships. THERE. IS. HOPE!<p></p>
                <p></p>
                <p>The man disappe[...illegible...]were preparing for our own expedition. Our exodus. But we\'re not important. What really matters is his mission. To pass the WORD!</p>
                <p></p>
                <p>East, always into the east!</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "written",
                "chance" => "3",
            ],
            "sos1_en" => [
                "title" => "S.O.S.",
                "author" => "ChrisCool",
                "content" => [
                    '<div class="hr"></div>
                <p>This is a call for help! I\'m in the town of <strong>Festering Ba\'Snag</strong> ! If someone receives this message, PLEASE COME AND BRING ME A VIBRATOR, it\'s a matter of LIFE AND DEATH !</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "postit",
                "chance" => "2",
            ],
            "stpat3" => [
                "title" => "She’s the White Flower of the Blackberry",
                "author" => "Irish Folk Song",
                "content" => [
                    '<br><p>She’s the white flower of the blackberry, she’s the sweet flower of the rasbery, she’s the best herb in excellence for the sight of the eyes.</p><br> <p>She’s my pulse, she’s my secret, she’s the scented flower of the apple, she’s summer in the cold time between Christmas and Easter.</p><br>
                <p>- Irish, folksong before 1789</p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "poem",
                "chance" => "1",
            ],
            "coloc_en" => [
                "title" => "Small Ad",
                "author" => null,
                "content" => [
                    '<h1>Flatmate Wanted</h1>
                <p>Male citizen seeks flatmate for houseshare in barricaded property in the north of the town. Quiet area, far from the construction site, next to the <strong>Well</strong> and commodities.</p>
                <p>Shunned or unmotivated citizens need not apply.</p><p>Please come laden with rations, pharms and water.</p>
                <p>Houseshare does not mean sharing resources.</p>
                <p>Contact: Nick Voleur</p>'
                ],
                "lang" => "en",
                "background" => "carton",
                "design" => "written",
                "chance" => "3",
            ],
            "sbwse1" => [
                "title" => "Stopping By Woods on a Snowy Evening",
                "author" => "Robert Frost",
                "content" => [
                    '<p><br>Whose woods these are I think I know.<br><br> His house is in the village though;<br><br> He will not see me stopping here <br><br>
                To watch his woods fill up with snow.</p><p><br>The woods are lovely, dark and deep.<br><br> But I have promises to keep,<br><br>  And miles to go before I sleep, <br><br>
                And miles to go before I sleep.</p>'
                ],
                "lang" => "en",
                "background" => "old",
                "design" => "written",
                "chance" => "2",
            ],
            "sn1fap" => [
                "title" => "Suicide Note",
                "author" => "FapLotion",
                "content" => [
                    '<p>I am almost smiling while writing this,<br><br> I am almost happy to know that its almost over.<br><br> That soon i will be overcome by the warmth of death. <br><br>
                As i bring the cyanide to my lips, my mouth almost waters.<br><br> It shouldn\'t be long now.</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "3",
            ],
            "stpmk3" => [
                "title" => "The 1st Limerick",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>An Irish builder called Mick,<br><br>
                Did a very unusual trick,<br><br>
                He would juggle his tools<br><br>
                Which was really quite cool<br><br>
                Til they landed one day on his foot!<br><br><br><br>
                Happy St Patrick\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd3" => [
                "title" => "The 2nd Limerick",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>There was an old man with a beard,<br><br>
                Who said, "it\'s just how i feared!- <br><br>
                Two owls and a hen <br><br>
                Four larks and a wren <br><br>
                Have all built their nests in my beard.<br><br><br><br>
                Happy St Patrick\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd5_en" => [
                "title" => "The 3rd Limerick",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>There once was an old man from Lyme,<br><br>
                Who married three wives at a time,<br><br>
                Whe asked, "Why a third?"<br><br>
                He replied, "One\'s absurd!,<br><br>
                and bigamy, sir, is a crime!"<br><br><br><br>
                Happy St Patrick\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd4" => [
                "title" => "The 4th Limerick",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>There was a young lady named Wright,<br><br>
                Whose speed was much faster than light,<br><br>
                She set off one day<br><br>
                In a relative way<br><br>
                And she came back the previous night!<br><br><br><br>
                Happy St Patrick\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd6" => [
                "title" => "The 5th Limerick",
                "author" => null,
                "content" => [
                    '<p><center><br><br>It filled Galileo with mirth,<br><br>
                    To watch his two rocks fall to Earth,<br><br>
                    He gladly proclaimed<br><br>
                    "Their rates are the same<br><br>
                    And quite independent of girth"!<br><br><br><br>
                    Science limerick #1!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd7" => [
                "title" => "The 6th Limerick",
                "author" => null,
                "content" => [
                    '<p><center><br><br>Then Newton announced in due course,<br><br>
                    His own law of gravity\'s force,<br><br>
                    "It goes, I declare,<br><br>
                    as the inverted square,<br><br>
                    Of the distance from object to source"!<br><br><br><br>
                    Science limerick #2!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd8" => [
                "title" => "The 7th Limerick",
                "author" => null,
                "content" => [
                    '<p><center><br><br>But remarkably Einstein\'s equation,<br><br>
                    Succeeds to describe gravitation,<br><br>
                    As spacetime that\'s curved<br><br>
                    And it\'s this that will serve<br><br>
                    As the planet\'s unique motivation"!<br><br><br><br>
                    Science limerick #3!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stprd9" => [
                "title" => "The 8th Limerick",
                "author" => null,
                "content" => [
                    '<p><center><br><br>Yet the end of the story\'s not written,<br><br>
                    By a new way of thinking we\'re smitten,<br><br>
                    We twist and we turn<br><br>
                    Attempting to learn<br><br>
                    The Superstring Theory of Witten"!<br><br><br><br>
                    Science limerick #4!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stpr10" => [
                "title" => "The 9th Limerick",
                "author" => null,
                "content" => [
                    '<p><center><br><br>A gentleman dining at Crewe,<br><br>
                    Found a rather large mouse in his stew,<br><br>
                    Said the waiter, "Don\'t shout,<br><br>
                    or wave it about,<br><br>
                    or the rest will be wanting one too"!<br><br><br><br>
                    Nonsense Limerick #74!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "stpr11" => [
                "title" => "The 10th Limerick",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>There once was a girl from Nantucket...<br><br>
                but i\'m sure you\'ve heard all about her...<br><br>
                <br><br><br><br>
                Please send complaints to the usual address!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "tddidc" => [
                "title" => "The Dangerous Dunes",
                "author" => "Indyclone77",
                "content" => [
                    '<br>
                <p>Sometimes I wondered why I bothered eating in that foul place. We lost our capacity for making proper food when Doug, the town chef, was mauled to death at midnight in the last attack. When he died, and the rest of the town mourned, I decided that pillaging his house was a better idea than crying and weeping. But forget Doug. Most of us did, and I had better things to think about.</p>
                <p>For example, the expedition into the world beyond. We were running low on building supplies, so me, Roberto and Roseangela decided that we needed to go and find nuts, bolts, cement, anything to make our lives even marginally better. We left as soon as the horde had dispersed from the town gates. We set off into the distance, seeking an abandoned construction site that a scout had once told me about.</p>',
                    '<br>
                <p>As we made our journey onwards, the sands seeped into our tattered shoes, weighing us down, making us tired. We had to rest, and soon. Therefore, we crashed down onto the hot sands, taking generous gulps of water from our individual canteens. In hindsight, it was foolish of us not to notice the zombies that were now surrounding us, and coming closer and closer. We were too involved in the idea of a quick rest, of water, and something to eat. We paid no heed to the dangerous dunes.</p>
                <p>Such regret I felt when thinking of those things has long since left my rotted flesh and splintered bones. Now, I think only of the meal that lies beyond those great gates...</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "written",
                "chance" => "3",
            ],
            "pswed1" => [
                "title" => "THE HANDFASTING OF APRIL 5, 2013",
                "author" => "Pocky & Soradobi",
                "content" => [
                    '<p>What\'s the deal with the cord?</p>
                <p>The handfasting ceremony has ancient celtic roots and it is from this custom that the phrase "tying the knot" comes from.</p>
                <p>This is because the ritual actually involves joining the couple with cords that are fastened around their wrists.</p>
                <p>Soradobi and Pocky have incorporated this into their wedding ceremony to show they are united as one...</p>
                <p>or perhaps to keep the groom from running away!</p>'
                ],
                "lang" => "en",
                "background" => "noteup",
                "design" => "typed",
                "chance" => "2",
            ],
            "stpwg1" => [
                "title" => "The Wearin' O' The Green",
                "author" => "Traditional.",
                "content" => [
                    '<p>Today is the day fer the wearin\' o\' the green</p>
                <p>Today is the day when the little people are seen</p>
                <p>Today is St. Patrick\'s Day, so if ye\'r Irish me lad</p>
                <p>Join the celebration for the grandest time ta\' be had</p>'
                ],
                "lang" => "en",
                "background" => "letter",
                "design" => "typed",
                "chance" => "2",
            ],
            "bsptr1" => [
                "title" => "Thunder Road",
                "author" => "Springsteen, B.",
                "content" => [
                    '<p><br>...contd.</p>
                There were ghosts in the eyes<br>
                Of all the boys you sent away<br>
                They haunt this dusty beach road<br>
                In the skeleton frames of burned out chevrolets<br><br>
                They scream your name at night in the street<br>
                Your graduation gown lies in rags at their feet<br>
                And in the lonely cool before dawn<br>
                You hear their engines roaring on<br>
                But when you get to the porch they\'re gone<br>
                On the wind, so mary climb in<br>
                Its a town full of losers<br>
                And I\'m pulling out of here to win.<p></p>'
                ],
                "lang" => "en",
                "background" => "noteup",
                "design" => "written",
                "chance" => "2",
            ],
            "cave1_en" => [
                "title" => "Torn Note",
                "author" => "gangster",
                "content" => [
                    '<p>The bastards have got me... I\'m in my cellar and they\'re banging on the door their muted groans are echoing in my head, my back is burning, not good times...</p>
                <p>They got everyone else too, they were all eaten alive, I\'ve only managed to survive for a few hours more, but  I know the end is nigh, I\'ve been bitten on the calf...</p>
                <p>Escape is impossible, it\'s not even a question of minutes now, the door is definitely going to give way... these things are strong... no longer human.</p>
                <p>I\'d rather die than become one of them...</p>'
                ],
                "lang" => "en",
                "background" => "blood",
                "design" => "written",
                "chance" => "1",
            ],
            "stpat1" => [
                "title" => "Treachery",
                "author" => "Liior",
                "content" => [
                    '<p>He stole our weapons, our last rations of meat, and left us on our own, to survive by himself in the desert.</p>
                <p>We have been thrown into confusion and we don\'t know what to do to stop the hordes from wiping us out tonight...In the Lucky Tavern a few days ago, some were saying that a good kick in the wheels wouldn\'t make the situation any less pleasant.</p>
                <p>I don\'t think we\'re safe any more... He took our weapons... our food, and left... without warning... were we abandoned? Maybe he\'s gone to deal with some zombies then come back this evening ?</p>',
                    '<p>Either way, our "chief" is gone, and it smells like treachery to me....</p>'
                ],
                "lang" => "en",
                "background" => "grid",
                "design" => "typed",
                "chance" => "4",
            ],
            "chief_en" => [
                "title" => "Two Women or Twelve Men",
                "author" => "Traditional Irish",
                "content" => [
                    '<p>There was a fox that had three young ones, and when the time came to teach them how to fend for themselves, the old fox took them to a house.</p>
                <p>There was great talk going on inside the house.  He asked the first two young ones if they could tell him who was in the house.</p>
                <p>They couldn’t. Then he tried the third.</p>
                <p>“Who is inside?” asked the old fox.</p>
                <p>“Either two women or twelve men,” said the young one. </p>
                <p>“You’ll do well in the world,” said the old fox.</p><br><br>
                <p>- A traditional Irish tale</p>'
                ],
                "lang" => "en",
                "background" => "notepad",
                "design" => "small",
                "chance" => "1",
            ],
            "recip1_en" => [
                "title" => "Twinoid Label",
                "author" => null,
                "content" => [
                    '<h1>Twinoid 500mg</h1>
                <table>
                <tbody><tr><td>Anabolic Steroids</td><td>0.70 %</td></tr>
                <tr><td>Allium Citrate</td><td>0.03 %</td></tr>
                <tr><td>Nitroglycerine</td><td>3.0 %</td></tr>
                <tr><td>Octanitrocubane</td><td>4.0 %</td></tr>
                <tr><td>Mercury Fulminate</td><td>2.5 %</td></tr>
                <tr><td>Perchlorate</td><td>0.02 %</td></tr>
                <tr><td>Lead Acetate</td><td>3.00 %</td></tr>
                <tr><td>RDX</td><td>0.02 %</td></tr>
                <tr><td>Pomegranate Extract</td><td>86.73 %</td></tr>
                </tbody></table>
                <p><small>Warning: this medication contains certain active ingredients which may cause side effects including: acne, vomiting, convulsions, violent death and explosion. </small></p>
                <p><small>Caution: Highly Inflammable.</small></p>'
                ],
                "lang" => "en",
                "background" => "stamp",
                "design" => "stamp",
                "chance" => "4",
            ],
            "bjhtcx_en" => [
                "title" => "Unlucky this time...",
                "author" => null,
                "content" => [
                    '<h1>Unlucky, you didn\'t win this time...</h1>
                <blockquote>
                <p></p><center>But maybe you will win the next time you purchase any other classic Bill Jerone products!<br>Coming soon: Bill Jerone Bikini Line Chainsaws and Hadron Colliders .</center><p></p>
                </blockquote>
                <small>When you\'re short on time and cash, sometimes you gotta Bill Jerone!</small>
                <small><strong>As always Bill accepts no responsibility for loss of life or limbs arising from use of this product</strong></small>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "5",
            ],
            "stval1" => [
                "title" => "Valentines Day Card",
                "author" => null,
                "content" => [
                    '<p></p><center><br><br>Roses are red,<br><br>
                Violets are blue,<br><br>
                I\'ve got 5 fingers<br><br>
                The middle one\'s for you!<br><br><br><br>
                Crappy Valentine\'s Day!</center>'
                ],
                "lang" => "en",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "3",
            ],
            "letann_es" => [
                "title" => "Cartas para Ana",
                "author" => "Desconocido",
                "content" => [
                    '<p>Ana,</p>
                <p>Siempre me dijiste de no correr riesgos, que no me separe de ti. Pero ya ves, terco como soy y queriéndome hacer siempre el valiente para nunca perderte. Te llevo conmigo, no temas. Ayuda en lo que puedas a los demás. Ahora debo esconderme de nuevo, una tormenta de arena azota el Ultramundo y la carpa no es tan fuerte como creí.</p>
                <p>Te amo.</p>',
                    '<p>Ana,</p>
                <p>Hoy no nos falta nada. Agua, comida y algunas armas improvisadas. Esperamos encontrar algo que nos permita volver al pueblo con materiales para reforzar nuestra casita.</p>
                <p>No olvides beber agua.</p>
                <p>Tu novio, el valiente.</p>',
                    '<p>Ana,</p>
                <p>Sal del pueblo de inmediato. Me dicen que dentro del pueblo alguien está contaminado con esa porquería de bacteria zombie. Huye. En esta inmensidad de arena no podríamos encontrarnos, solo corre.
                </p>
                <p>Seguiré luchando y al volver al pueblo te esperaré cada día en el portal... Rezo por ti.</p>
                <p>Pase lo que pase, piensa en los bonitos días de campo que compartíamos allá en el sur. Siempre te amaré. Siempre.</p>'
                ],
                "lang" => "es",
                "background" => "letter",
                "design" => "typed",
                "chance" => "3",
            ],
            "monsmc_es" => [
                "title" => "El Monstruo Manos de Cuchilla",
                "author" => "Caballero-KANON",
                "content" => [
                    '<p>Estoy atrapado, encerrado. Se me agotan las opciones... Y ese monstruo sigue buscándome allá afuera. </p>
                <p>No saldré vivo de aquí, lo sé, pero quien encuentre esto, debe saber que hay algo mas que solo zombies.. Mucho cuidado, pues no están solos. </p>
                <p>El monstruo tiene cara desfigurada, y sus manos son como cuchillas...</p>
                <p>Vuelve y refuerza las defensas de tu pueblo, corre, no queda mucho tiempo.</p>'
                ],
                "lang" => "es",
                "background" => "letter",
                "design" => "typed",
                "chance" => "2",
            ],
            "miamiz_es" => [
                "title" => "Mi amigo, el zombie",
                "author" => "Znarf",
                "content" => [
                    '<h1>Mi amigo, el zombie</h1>
                <p>Estábamos reunidos en una noche clara del Ultramundo, frotándonos las manos y los pies para no morir de frío.</p>
                <p>De pronto vimos una silueta acercarse a nosotros. Pensamos que era un habitante perdido, pero no.</p>
                <p>Era un zombie que resultó herido por las armas del pueblo. Sus sollozos eran tan profundos que no me dieron miedo, más bien sentí compasión.</p>
                <p>Mis compañeros, sin armas a la mano, quisieron atacarle con sus propias vestimentas. Yo les detuve y me abandonáron gritándome "loco" y "traidor".</p>',
                    '<p>Ya llevo 2 noches junto a este monstruo herido, sus ojos entristecidos me hacen pensar en tantos amigos que perdí.</p>
                <p>No sé hasta qué punto este compañero pueda ser inofensivo. Hasta ahora no me ha atacado y recibe sin quejarse las pocas migajas de comida que le doy.</p>
                <p>No sé realmente qué hacer, irme, matarlo, seguir con él...</p>'
                ],
                "lang" => "es",
                "background" => "letter",
                "design" => "written",
                "chance" => "3",
            ],
            "navid1_es" => [
                "title" => "Navidad en medio de este desastre",
                "author" => "Iterrance",
                "content" => [
                    '<p>Aún recuerdo las navidades cuando era menor, obviamente después de que estos come-cesos hayan aparecido es todo muy diferente.</p>
                <p>Antes podía ver el árbol navideño con sus luces resaltar en casa. Ahora sólo puedo ver el raro color de las raciones del pueblo.</p>
                <p>Antes podía ver la caza adornada y todas sus luces, ahora sólo puedo ver mi cuchitril de madera apolillada.</p>
                <p>Antes me contaban sobre que Santa se metería en la chimenea y dejaría regalos, pero ahora solo veo zombis metiéndose en las defensas y dejando más victimas. </p>
                <p>Lo único que puedo agradecer es que sigo vivo, quizá la navidad me de una muerte sin dolor.</p>'
                ],
                "lang" => "es",
                "background" => "grid",
                "design" => "written",
                "chance" => "2",
            ],
            "soled1_es" => [
                "title" => "Soledad",
                "author" => "Sercobra",
                "content" => [
                    '<p>Siete días han pasado desde que estoy en este pueblo con treinta y nueve personas más pero... ahora solo quedo yo. </p>
                <p>La muerte me acecha y los cuervos me revolotean, dejo lo que será mi último recuerdo como vivo... antes de dejarlo en manos de los zombies.</p>'
                ],
                "lang" => "es",
                "background" => "grid",
                "design" => "written",
                "chance" => "2",
            ],
            "navid3_es" => [
                "title" => "Viejos recuerdos",
                "author" => "1_Shadow_1",
                "content" => [
                    '<p>Mis recuerdos son borrosos, tanto tiempo vagando sin conocimiento. Pasando de un pueblo a otro… en cada pueblo, mis mejores amigos mueren. Mientras los vagos sin oficio solo esperan la muerte, recuerdo esta época hace tantos años atrás… donde todo era felicidad con la familia.</p>
                <p>Nos sentábamos a hablar a medianoche, con comida en la mesa, contar nuestros pequeños logros de ese año… reír a carcajadas cuando todos estábamos ebrios, y finalmente dormir esperando un día mejor… ahora, en este mundo tan corrompido por la oscuridad… se siente como si no fuéramos a ningún lado. </p>',
                    '<p>Aun así, seguimos moviéndonos. Mi muerte está tocando la puerta, aquel que encuentre esto… y recuerden esos momentos alegres. Feliz Navidad.</p>'
                ],
                "lang" => "es",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "wintk1_es" => [
                "title" => "El suertudo Juan",
                "author" => null,
                "content" => [
                    '<p>Mientras silbaba una canción de Bob Marley, el habitante llamado Juan encontró un papelito que decía:</p>
                <blockquote>
                <p>Mala hierba.</p>
                <p>Sólo hierba en mal lugar.</p>
                </blockquote>
                <p>Entonces entendió que no debería conformarse con solo huir. ¿Qué se sentirá ser un héroe? Apretó bien los dientes, corrió hacia la horda de zombies, y el resto es historia...</p>'
                ],
                "lang" => "es",
                "background" => "tinystamp",
                "design" => "classic",
                "chance" => "1",
            ],
            "herr_es" => [
                "title" => "Pueblo Herrero: Relato de Ryan",
                "author" => "RyanOliver",
                "content" => [
                    '<p>Hola querido lector:</p>
                <p>Si lees esto, tal vez encuentres mis restos a unos pocos metros de donde estás. Mi Nombre es Ryan. Y te contaré lo que pasa en ese pueblo. Empecemos en orden: </p>
                <h2>Día 1</h2>
                <p>En total somos 40 personas, la gran mayoría, incluyéndome, estamos asustados desde que avisaron que una infección se había propagado y está convirtiendo a la gente que es mordida en caníbales sin razonamiento...</p>
                <p>El de la torre nos dice que hay 35 zombies en la zona.</p>',
                    '<p>Están construyendo una especie de muro. Iré a sacar agua y ayudaré con la construcción.</p>
                <p>Acaban de cerrar el portón pero veo a nuestra gente aún afuera, ¿será mi imaginacion?</p>
                <h2>Día 2</h2>
                <p>Fue terrible. Había 7 personas llorando y tocando el portón. Les quería abrir pero me detuvieron los otros habitantes. Me dijeron que los zombies entrarían si lo hacía. Fue muy duro ver que los zombies los devoraran. Alguien agonizando, gritando de dolor nos dijo: ¡Acuerd...ense de mi, si me convierto en eso... los ma...taré... a todos¡</p>
                <p>Después de lo que pasó, mucha gente entró en las casas de los fallecidos y robaron sus cosas.</p>',
                    '<p>Hice un amigo en el pueblo, su nombre es Oliver. Me dice: "Sé fuerte o terminarás entre las mandíbulas zombies".</p>
                <p>El de la torre nos dice que ahora ve a 50 zombies acercándose. Las construcciones continúan.</p>
                <p>Alguien trajo carne. Se ve rara y Oliver me dice que no la toque, mejor, comeremos estas galletas secas... </p>
                <p>Continuará</p>'
                ],
                "lang" => "es",
                "background" => "stamp",
                "design" => "typed",
                "chance" => "1",
            ],
            "wstal1" => [
                "title" => "Workshop's Tale - Part 1",
                "author" => "R3dd3r",
                "content" => [
                    '<h1>Workshop\'s Tale - Part 1</h1>
                <p>Our hero was born into the world without much fuss and came to rest in the Nasty Hideout of Losers, a town whose name he would not let be a stain on his life.
                Of a young age he worked tirelessly in the construction yard, diligently making sure his town would survive the undead horde, who come nightfall attacked without mercy.</p>
                <p>Each night when the shambling masses came and all his fellow citizens were huddling under their covers our Hero was in the workshop busily pulling apart broken electronic devices and tinkering with the odd flat pack furniture to keep his mind sharp.
                All this gave our hero nimble fingers and quick as a flash anything not nailed down would be his, torn apart and remade into something to defend the town.</p>',
                    '<br>
                <br>
                <p>Our Hero could not relax, consumed with worry, night after night when all his fellow citizens where around the camp fire swapping stories our hero would be counting out the correct number of nuts and bolts needed for the next day’s defence against the ever swelling horde.
                He was no coward and though he lived in the workshop he also went forth through the gates many times.</p> <p>In the wasteland our hero was ever searching for that glint of sheet metal or an old crate lid he could drag back to town and assemble to protect his friends.</p>',
                    '<br>
                <br>
                <br>
                <p>Some found our hero hard to relate to, often cursing him and calling him rude, however our hero knew they had just been in the wasteland too long that it was just the alcohol, dehydration and drugs talking that was the problem, they would be ok in the morning he thought.
                But in the morning our hero was in for a surprise for a great mass of murderous zombies had been growing outside and now stretched as far as the towns scouts could see from the top of the watchtower.</p>
                <p>They cried "There is no hope"</p>',
                    '<br>
                <br>
                <p>Suppressing his Fears our hero stood strong. Hammering, sawing, fusing and combining all he could find into weapons and defences.</p>
                <p>"I can save this town" he muttered almost mantra like as he worked day and night.</p>
                <br>
                <br>
                <br>
                <p>To be continued...</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "typed",
                "chance" => "4",
            ],
            "wstal2" => [
                "title" => "Workshop's Tale - Part 2",
                "author" => "R3dd3r",
                "content" => [
                    '<h1>Workshop\'s Tale - Part 2</h1>
                <p>It was no use though for our hero was slowly forced to watch his friends die, night after night and he new that soon his end was near.
                On the last day when all around him where dead and all seemed lost, he collected his belongings and grabbed a water pistol, pushing open the gates he turned one last time to look at the town he loved, now just a shadow of its glory days he again turned and set off into the wasteland.
                The sun beat down, the heat almost unbearable our hero marched on into the endless rolling sand dunes.
                Fighting off a damn zombie ambush whilst searching for a drink at an old hydraulic pump he found.</p>',
                    '<br>
                <br>
                <p>Our hero thought his end was near. Sunburnt, no food, no water just an empty super-soaker and a case of the wasteland blues he sat on a rock to watch the sunset.
                Looking one last time at the horizon our hero gave up hope, lay on sand and went to sleep.
                His dreams were of his friends, of his time in the workshop and of the hate he had of those damn undead.
                pain, Pain, PAIN!</p>
                <p>Our hero awoke his leg now swarmed by Rats chewing and gnawing him, our hero freaked jumped up and flailing like a fool went running from the viscous chewing rats, he ran,
                he ran,</p>',
                    '<br>
                <p>he ran until he collapsed pain still shooting up his leg he lay there in the sand and cried dry tears until he could hear the moans around him getting louder and closer.
                He was no coward our hero so he stood ready to face death ready to go down swinging.
                As his eyes scanned the ever darkening gloom he spotted a low wall, a camp fire and a gate, the dehydration had finely taken his mind, at least the zombies wouldn\'t get it.
                But no.</p>
                <p>There where voices a low muttering and intermittent laughs, he was saved it was another town.
                Summoning all his might he headed toward the gate.</p>',
                    '<br>
                <br>
                <p>"WHO GOES THERE? If you moan I\'ll freak-in shoot, SPEAK!" the voice rang out in the darkens.
                Our hero called out "I\'m, I\'m Workshop. Where am I?"</p>
                <p>A guardian stepped out from behind the gate, "Welcome, you’re in the Palisades of Winter."</p>'
                ],
                "lang" => "en",
                "background" => "white",
                "design" => "typed",
                "chance" => "3",
            ],
            "morse2_en" => [
                "title" => "Communication in morse code (dated 31 August)",
                "author" => null,
                "content" => [
                    '<small>31 August, ETA: 23:30</small>
                <small>[Start of transmission]</small>
                <p>. - .- - / -- .- .--- --- .-. / / - .-. .- -. -.-. .... . / -. .---- ..--- / / .-. .- ...- .. - .- .. .-.. .-.. . -- . -. - / -.-. --- ..- .--. --..-- / .--. .- ... ... .- --. . / - . -. ..- / .--. .- .-. / .-.. .----. . -. -. . -- .. .-.-.- / .. -- .--. --- ... ... .. -... .-.. . / -.. . / .-. . .--. .-. . -. -.. .-. . / .-.. . / ... . -.-. - . ..- .-. .-.-.- / - . -. . --.. / .--. --- ... .. - .. --- -. / .-.. . / .--. .-.. ..- ... / .-.. --- -. --. - . -- .--. ... / .--. --- ... ... .. -... .-.. . .-.-.- / -.. .. . ..- / ...- --- ..- ... / --. .- .-. -.. . </p>
                <small>[End of transmission]</small>'
                ],
                "lang" => "en",
                "background" => "blood",
                "design" => "typed",
                "chance" => "3",
            ],
        ]);
    }
}