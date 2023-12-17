<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Fixtures\DTO\Buildings\BuildingPrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class BuildingDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        // \["name"\s*=>\s*["'](.*?)["']\s*,\s*'desc'\s*=>\s*["'](.*?)["'],\s*"temporary"\s*=>\s*(\d+),"img"\s*=>\s*["'](.*?)["'],"vp"\s*=>\s*(\d+),"ap"\s*=>\s*(\d+),\s*"hp"\s*=>\s*(\d+),"bp"\s*=>\s*(\d+),"rsc"\s*=>\s*\[(.*?)\],\s*"orderby"\s*=>\s*(\d+)
        $container = new BuildingPrototypeDataContainer($data);

        $container->add()
            ->icon('small_wallimprove')->label('Verstärkte Stadtmauer')->description('Verbessert die Stadtverteidigung erheblich.')
            ->isTemporary(0)->defense(30)->ap(30)->blueprintLevel(0)->resources(["wood2_#00" => 15,"metal_#00" => 5,])->orderBy(0)->commit( $small_wallimprove );

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_gather')->label('Großer Graben')->description('Der Große Graben ist eine sehr wirkungsvolle Verteidigungsmaßnahme, die sich insbesondere auf lange Sicht auszahlt. Der Graben lässt sich mit allerhand Dingen füllen.')
            ->isTemporary(0)->defense(10)->ap(80)->blueprintLevel(0)->orderBy(0)
            ->voteLevel(5)->baseVoteText('Der Verteidigungswert der Stadt steigt mit dem Großen Graben auf 10 Punkte.')
            ->upgradeTexts([
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 13.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 21.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 32.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 33.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 51.',
                           ])
            ->commit( $small_gather );

        $container->add()->parentBuilding($small_gather)
            ->icon('small_waterhole')->label('Wassergraben')->description('Eine verbesserte Version des Großen Grabens. Muss mit Wasser gefüllt werden...')
            ->isTemporary(0)->defense(65)->ap(50)->health(50)->blueprintLevel(1)->resources(["water_#00" => 20,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rasierklingenmauer')->description('Die Rasierklingenmauer folgt einem ganz einfachen Prinzip: Man nehme allerlei Eisenstücke, schärfe und spitze sie an und verteile sie anschließend über die ganze Stadtmauer. Die Mauer verwandelt sich so in eine überdimensionale Zombiefeile.')
            ->isTemporary(0)->defense(50)->ap(40)->health(40)->blueprintLevel(1)->resources(["metal_#00" => 15,"meca_parts_#00" => 2,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_spears')->label('Pfahlgraben')->description('Diese verbesserte Variante des Großen Grabens besteht aus einer großen Anzahl zugespitzter Holzpfähle.')
            ->isTemporary(0)->defense(40)->ap(40)->health(40)->blueprintLevel(1)->resources(["wood2_#00" => 8,"wood_beam_#00" => 4,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_barbed')->label('Stacheldraht')->description('Na ja, dieser Stachel"draht" ist noch simpler als der normale... Der Grund: Der Draht fehlt.')
            ->isTemporary(0)->defense(10)->ap(20)->health(20)->blueprintLevel(0)->resources(["metal_#00" => 2,])->orderBy(3)->commit($small_barbed);

        $container->add()->parentBuilding($small_barbed)
            ->icon('small_meatbarbed')->label('Köder')->description('Mit diesem an einem Stacheldraht befestigtem Stück Fleisch kann man ein paar Zombies \'ne Zeit lang beschäftigen')
            ->isTemporary(1)->defense(80)->ap(10)->health(0)->blueprintLevel(1)->resources(["bone_meat_#00" => 3,])->orderBy(0)->commit();


        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_wallimprove')->label('Weiterentwickelte Stadtmauer')->description('Auf die Verteidigungsvorichtungen müssen wir heute Nacht verzichten, aber diese intelligent gebaute und ausbaufähige Stadtmauer hat mehr drauf, als man denkt.')
            ->isTemporary(0)->defense(50)->ap(40)->health(40)->blueprintLevel(1)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 9,"metal_beam_#00" => 6,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_wallimprove)
           ->icon('item_plate')->label('Verstärkende Balken')->description('Mit diesen Metallbalken können die schwächeren Stellen der Stadtmauer verstärkt werden.')
           ->isTemporary(0)->defense(25)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood_beam_#00" => 1,"metal_beam_#00" => 3,])->orderBy(5)->commit($item_plate);

        $container->add()->parentBuilding($item_plate)
            ->icon('item_plate')->label('Zackenmauer')->description('Diese Mauer ist mit einer großen Anzahl an Metallspitzen gespickt, damit die Stadtbewohner beim Angriff um Mitternacht ein paar nette Spieße Zombieschaschlik herstellen können.')
            ->isTemporary(0)->defense(45)->ap(35)->health(35)->blueprintLevel(1)->resources(["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,])->orderBy(0)->commit($item_plate_2);

        $container->add()->parentBuilding($item_plate_2)
            ->icon('item_plate')->label('Groooße Mauer')->description('Was ist besser als eine Mauer?... eine groooße Mauer.')
            ->isTemporary(0)->defense(80)->ap(50)->health(50)->blueprintLevel(2)->resources(["wood2_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,])->orderBy(0)->commit();

        $container->add()->parentBuilding($item_plate)
            ->icon('item_plate')->label('Zweite Schicht')->description('Damit selbst hartnäckige Zombies draußen bleiben, bekommt die gesamte Stadtmauer eine zusätzliche Schicht verpasst.')
            ->isTemporary(0)->defense(75)->ap(65)->health(65)->blueprintLevel(1)->resources(["wood2_#00" => 35,"metal_beam_#00" => 5,])->orderBy(1)->commit($item_plate_3);

        $container->add()->parentBuilding($item_plate_3)
            ->icon('item_plate')->label('Dritte Schicht')->description('Eine dritte Schicht über der bestehenden Mauer bietet noch besseren Schutz gegen untote Eindringlinge.')
            ->isTemporary(0)->defense(100)->ap(65)->health(65)->blueprintLevel(2)->resources(["metal_#00" => 30,"plate_#00" => 5,"metal_beam_#00" => 5,])->orderBy(0)->commit();

        $container->add()->parentBuilding($item_plate)
            ->icon('item_home_def')->label('Entwicklungsfähige Stadtmauer')->description('Die Stadtmauer wird mit einem Eisengestell verstärkt und kann ab sofort jeden Tag ganz leicht um ein Stück erweitert werden!')
            ->isTemporary(0)->defense(55)->ap(65)->health(65)->blueprintLevel(3)->resources(["wood2_#00" => 5,"metal_#00" => 20,"concrete_wall_#00" => 1])->orderBy(2)
            ->voteLevel(5)->baseVoteText('Die entwicklungsfähige Stadtmauer bringt der Stadt 55 Verteidigungspunkte.')
            ->upgradeTexts([
                               'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 30.',
                               'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 35.',
                               'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 50.',
                               'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 65.',
                               'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 80.',
                           ])
            ->commit();

        $container->add()->parentBuilding($item_plate)
            ->icon('small_wallimprove')->label('Betonschicht')->description('Zu guter Letzt bekommt die Mauer noch eine Schicht aus Beton verpasst. Das sollte die Zombiehorden nun aber wirklich draußen halten.')
            ->isTemporary(0)->defense(50)->ap(60)->health(60)->blueprintLevel(1)->resources(["concrete_wall_#00" => 6,"metal_beam_#00" => 2,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_grater')->label('Zombiereibe')->description('Man bedecke eine große Bodenfläche mit einem Meer von zugespitzten und geschärften Metallstücken und schon erhält man die größte Käsereibe der Welt.')
            ->isTemporary(0)->defense(55)->ap(60)->health(60)->blueprintLevel(1)->resources(["meca_parts_#00" => 3,"metal_#00" => 20,"plate_#00" => 3,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_gather')->label('Fallgruben')->description('Ihr legt eine große Anzahl von verdeckten Fallgruben rund um die Stadt an und wartet bis irgendwas reinfällt. So einfach.')
            ->isTemporary(0)->defense(35)->ap(50)->health(50)->blueprintLevel(0)->resources(["wood2_#00" => 10,])->orderBy(7)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_fence')->label('Zaun (Baustellen)')->description('Die Stadt baut einen Holzzaun, der - zumindest theoretisch- die Bestien ausreichend verlangsamen sollte.')
            ->isTemporary(0)->defense(30)->ap(50)->health(50)->blueprintLevel(0)->resources(["wood_beam_#00" => 5,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_fence')->label('Holzzaun')->description('Verbessert die Stadtverteidigung erheblich.')
            ->isTemporary(0)->defense(45)->ap(50)->health(50)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"wood2_#00" => 20,"wood_beam_#00" => 5,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_wallimprove')->label('Einseifer')->description('Warum ist da vorher noch niemand drauf gekommen? Anstatt Zeit mit Körperpflege zu verschwenden, benutzt eure Seife lieber dazu, die Stadtmauer schön glitschig zu machen. Vor allem im Zusammenspiel mit der Zombiereibe eine "saubere Lösung". Wen stören da schon die Geräusche?')
            ->isTemporary(0)->defense(60)->ap(40)->health(40)->blueprintLevel(1)->resources(["water_#00" => 10,"pharma_#00" => 5,"concrete_wall_#00" => 1,])->orderBy(10)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_waterspray')->label('Zerstäuber')->description('Ein handliches, hydraulisch betriebenes Gerät, das Wasserdampf versprühen kann (und weitere amüsante Chemikalien).')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"metal_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 2,])->orderBy(11)->commit($small_waterspray);

        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_acidspray')->label('Säurespray')->description('Das wird die hübschen Antlitze der Zombies vor der Stadt sicher auch nicht verbessern.')
            ->isTemporary(1)->defense(40)->ap(30)->blueprintLevel(1)->resources(["water_#00" => 2,"pharma_#00" => 3,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_gazspray')->label('Spraykanone')->description('Oft wird vergessen, dass Zombies ein Gehirn haben. Manchmal sogar zwei, wenn sie Glück haben. Trifft sich gut: Das mit dieser Kanone geschossene Konzentrat hat die erstaunliche Fähigkeit, Gehirne in Matsch zu verwandeln.')
            ->isTemporary(1)->defense(150)->ap(40)->blueprintLevel(2)->resources(["water_#00" => 2,"pharma_#00" => 5,"drug_#00" => 1,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten')->description('Ein simpler Verteidigungsgegenstand, aber du wirst ihn zu schätzen wissen, wenn dein Ex-Nachbar Kevo versuchen sollte, an deinem Gehirn rumzuknabbern..')
            ->isTemporary(0)->defense(25)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 10,])->orderBy(12)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten 2.0')->description('Diese Verbesserung ist nicht der ganz große Wurf, aber sie erfüllt ihren Zweck: Sie verhindert, dass du zu schnell stirbst.')
            ->isTemporary(0)->defense(25)->ap(30)->health(30)->blueprintLevel(0)->resources(["metal_#00" => 10,])->orderBy(13)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten 3.0')->description('Simpel aber stabil: Was will man mehr?')
            ->isTemporary(0)->defense(40)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood2_#00" => 10,"metal_#00" => 10,])->orderBy(14)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Sperrholz')->description('Sperrholz. Du hast es nur genommen, weil du wirklich nichts besseres zu tun hattest. Dir war klar, dass es unnütz sein würde, aber das hat dich trotzdem nicht davon abgehalten. Na dann mal los...')
            ->isTemporary(0)->defense(25)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 5,"metal_#00" => 5,])->orderBy(15)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Extramauer')->description('Es war sicher kein Geniestreich dieses Bauwerk zu beginnen. Aber gut, letztlich haben alle zugestimmt und eine weitere große Mauer ist sicher keine schlechte Idee.')
            ->isTemporary(0)->defense(45)->ap(30)->health(30)->blueprintLevel(1)->resources(["wood2_#00" => 15,"metal_#00" => 15,])->orderBy(16)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_round_path')->label('Brustwehr')->description('Hast du es satt, über die abgetrennten Gliedmaßen der Zombies zu stolpern, die du erledigt hast? Vielleicht ist es dann an der Zeit, in der Apokalypse aufzusteigen and und die Zombiehorden von oben zu beobachten. Mithilfe der Brustwehr kannst du des Nachts über die Stadt wachen und dem Himmel ein Stück näher zu kommen.')
            ->isTemporary(0)->defense(0)->ap(20)->blueprintLevel(0)->resources(["wood2_#00" => 6,"metal_#00" => 2,"meca_parts_#00" => 1,])->orderBy(17)->commit();

        $container->add()
            ->icon('small_water')->label('Pumpe')->description('Die Pumpe ist die Grundvoraussetzung für alle auf Wasser basierenden Konstruktionen! Darüber hinaus steigert sie die Wasserergiebigkeit des Brunnens um ein Vielfaches.')
            ->isTemporary(0)->isImpervious(true)->defense(0)->ap(25)->blueprintLevel(0)->resources(["metal_#00" => 8,"tube_#00" => 1,])->orderBy(1)
            ->voteLevel(5)->baseVoteText('Der Brunnen wird einmalig mit 5 Rationen Wasser befüllt.')
            ->upgradeTexts([
                               'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                               'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                               'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                               'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                               'Der Brunnen der Stadt wird einmalig um 40 Rationen Wasser aufgefüllt',
                           ])
            ->commit($small_water);

        $container->add()->parentBuilding($small_water)
            ->icon('item_jerrycan')->label('Wasserreiniger')->description('Verwandelt in der Wüste gefundenes Kanisterwasser in Trinkwasser.')
            ->isTemporary(0)->defense(0)->ap(75)->health(75)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 6,"tube_#00" => 3,])->orderBy(0)->commit($item_jerrycan);

        $container->add()->parentBuilding($item_jerrycan)
            ->icon('item_bgrenade')->label('Minen')->description('Raketenpulver, Zünder und reines Wasser: Das sind die Zutaten für einen saftigen Brei aus vermodertem Fleisch diese Nacht. Eine mächtige Verteidigung, leider kann sie nur einmal verwendet werden.')
            ->isTemporary(1)->defense(115)->ap(50)->health(0)->blueprintLevel(2)->resources(["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_jerrycan)
            ->icon('item_jerrycan')->label('Wasserfilter')->description('Verbessert die Ausbeute des Wasserreinigers erheblich (hoher Wirkungsgrad).')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(3)->resources(["metal_#00" => 10,"electro_#00" => 2,"fence_#00" => 1,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_vegetable_tasty')->label('Gemüsebeet')->description('Mit einem Gemüsebeet könnt ihr leckere Früchte und nicht allzu verschimmeltes Gemüse anbauen. Ist zwar kein Bio, macht aber satt.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(1)->resources(["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,])->orderBy(1)->commit($item_vegetable_tasty);

        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('item_bgrenade')->label('Granatapfel')->description('Ein gewaltiger wissenschaftlicher Durchbruch: Durch die Aussaat von Dynamitstangen und gaaanz vorsichtiges Gießen, könnt ihr Granatäpfel anbauen!')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('item_digger')->label('Dünger')->description('Verbessert die Ausbeute des Gemüsebeets erheblich.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('small_water')->label('Brunnenbohrer')->description('Mit diesem selbstgebauten Bohrer kann die Stadt ihre Wasserreserven beträchtlich vergrößern.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(60)->health(0)->blueprintLevel(0)->resources(["wood_beam_#00" => 7,"metal_beam_#00" => 2,])->orderBy(2)->commit($small_water_2);

        $container->add()->parentBuilding($small_water_2)
            ->icon('small_eden')->label('Projekt Eden')->description('Eine radikale Lösung, wenn mal das Wasser ausgehen sollte: Mit ein paar gezielten Sprengungen können tiefergelegene Wasserschichten erschlossen und das Wasserreservoir vergrößert werden.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(65)->health(0)->blueprintLevel(3)->resources(["explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 8,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_firework_tube')->label('Wasserleitungsnetz')->description('Nach der Pumpe könnt ihr mit dem Wasserleitungsnetz die auf Wasser basierenden Verteidigungsmechanismen angehen! Zusätzlich vergrößert das Netz auch die Wasserreserven des Brunnens.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(40)->health(0)->blueprintLevel(0)->resources(["meca_parts_#00" => 3,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,])->orderBy(3)->commit($item_firework_tube);

        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_waterspray')->label('Kärcher')->description('Dieser leistungsstarke Dampfstrahlreiniger versprüht feinen, siedend heißen Wasserdampf. Deine muffigen Freunde werden beim Anblick dieses Geräts wortwörtlich dahinschmelzen.')
            ->isTemporary(0)->defense(50)->ap(50)->health(50)->blueprintLevel(0)->resources(["water_#00" => 10,"meca_parts_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 7,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_grinder')->label('Kreischender Rotor')->description('Es handelt sich um ein einfallsreiches und SEHR effektives System! Zwei schnell kreisende und mit geschliffenen Eisenstangen bestückte Drehscheiben, die von einem Kolbenmechanismus angetrieben werden, zerfetzen alles und jeden, der sich im Toreingang befindet!')
            ->isTemporary(0)->defense(50)->ap(55)->health(55)->blueprintLevel(1)->resources(["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,])->orderBy(1)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_sprinkler')->label('Sprinkleranlage')->description('Wie jeder weiß, wird eine Sprinkleranlage für gewöhnlich im Garten eingesetzt. Die wenigsten wissen jedoch, dass sie sich auch hervorragend gegen Zombiehorden eignet. Einziger Wermutstropfen: Die Anlage verbraucht relativ viel Wasser.')
            ->isTemporary(0)->defense(150)->ap(85)->health(85)->blueprintLevel(3)->resources(["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,])->orderBy(2)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_shower')->label('Dusche')->description('Nein, ganz ehrlich, dieser... dieser... Geruch ist einfach nicht auszuhalten: Nimm eine Dusche. Sofort!')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(2)->resources(["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_tube')->label('Wasserturm')->description('Mit dieser revolutionären Verteidigungsanlage ist die Stadt imstande, große Wasserdampfwolken zu erzeugen. Ein wohlig-warmes Dampfbad wird den vor den Stadtmauern herumlungernden Zombies gut tun und sie grundlegend "reinigen". Die Leistung kann mit ein wenig Feintuning noch gesteigert werden.')
            ->isTemporary(0)->defense(70)->ap(60)->blueprintLevel(3)->resources(["water_#00" => 40,"tube_#00" => 7,"metal_beam_#00" => 10,])->orderBy(4)
            ->voteLevel(5)->baseVoteText('Der Wasserwerfer gibt 70 zusätzliche Verteidigungspunkte.')
            ->upgradeTexts([
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 224.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 280.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_tube')->label('Wasserfänger')->description('Wenn es um Wasser geht, zählt jeder Tropfen. Dieses Bauwerk fügt dem Brunnen +2 Rationen Wasser hinzu und kann jeden Tag gebaut werden.')
            ->isTemporary(1)->defense(0)->ap(12)->health(0)->blueprintLevel(1)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(5)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_watercanon')->label('Wasserkanone')->description('Ein hübscher kleiner Wasserstrahl, um die wachsende Zombiemeute beim Stadttor zu sprengen.')
            ->isTemporary(0)->defense(80)->ap(40)->health(40)->blueprintLevel(2)->resources(["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_appletree')->label('Apfelbaum')->description('Dieser Apfelbaum erinnert eher an einen verkümmerten und halbtoten Busch, aber er trägt wunderschöne blaue Äpfel. Äh, Moment mal,... wie bitte?')
            ->isTemporary(0)->defense(0)->ap(30)->health(0)->blueprintLevel(3)->resources(["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"wood_beam_#00" => 1,])->orderBy(7)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_shower')->label('Schleuse')->description('Selbst das Abwasser der Stadt kann noch genutzt werden: Wir müssen bloß alle Toiletten der Stadt über ein ausgeklügeltes System aus Rohren und Schlitten miteinander verbinden und dann um Mitternacht die Schleusen öffnen. Hat auch jeder sein Zelt korrekt aufgebaut?')
            ->isTemporary(0)->defense(60)->ap(50)->health(50)->blueprintLevel(1)->resources(["water_#00" => 15,"wood2_#00" => 10,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_shower')->label('Wasserfall')->description('Anfangs war es nur zur Dekontaminierung gedacht. Aber dann stellte es sich als äußerst effizientes Mittel gegen unsere pestilenten Freunde heraus. Man gebe noch einen Spritzer Kokosnuss-Duschgel hinzu und siehe da: Die meterhohen Leichenstapel, für die DU verantwortlich bist, verströmen ein betörendes Aroma.')
            ->isTemporary(0)->defense(35)->ap(20)->health(20)->blueprintLevel(1)->resources(["water_#00" => 10,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_rocketperf')->label('Wünschelrakete')->description('Ein selbstgebauer Raketenwerfer feuert in den Boden: Denk mal drüber nach! Der Legende nach wollte der geistige Vater dieses Bauprojekts eigentlich den "Rocket Jump" erfinden. Egal, +60 Rationen Wasser werden so zum Brunnen hinzugefügt.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(90)->health(0)->blueprintLevel(3)->resources(["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(10)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_waterdetect')->label('Wünschelrute')->description('Dass "Hightech" nicht nur auf die Dezimierung von Zombiehorden beschränkt ist, beweist dieses Gebäude... Es fügt +100 Rationen Wasser zum Brunnen hinzu.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(130)->health(0)->blueprintLevel(4)->resources(["electro_#00" => 5,"wood_beam_#00" => 5,"metal_beam_#00" => 10,])->orderBy(11)->commit();

        $container->add()
            ->icon('item_meat')->label('Metzgerei')->description('In der Metzgerei könnt ihr eure kleinen treuen Begleiter (Hunde, Katzen, Schlangen ...) in Lebensmittel verwandeln. Da gibt es doch tatsächlich noch Leute, die Vegetarier sind...')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood2_#00" => 9,"metal_#00" => 4,])->orderBy(2)->commit($item_meat);

        $container->add()->parentBuilding($item_meat)
            ->icon('item_hmeat')->label('Kremato-Cue')->description('Jeder weiß, was ein Krematorium ist, richtig? Und jeder weiß, wozu man einen Barbecuegrill verwendet? Dann einfach eins und eins zusammenzählen, dann wisst ihr auch wie ein "Kremato-Cue" funktioniert. Die Zeiten des Hungerns sind jedenfalls vorbei...')
            ->isTemporary(0)->defense(0)->ap(45)->health(45)->blueprintLevel(2)->resources(["wood_beam_#00" => 8,"metal_beam_#00" => 1,])->orderBy(0)->commit();

        $container->add()
            ->icon('small_refine')->label('Werkstatt')->description('Die Entwicklung einer jeden Stadt hängt vom Bau einer verdreckten Werkstatt ab. Sie ist die Voraussetzung für alle weiter entwickelten Gebäude.')
            ->isTemporary(0)->defense(0)->ap(25)->blueprintLevel(0)->resources(["wood2_#00" => 10,"metal_#00" => 8,])->orderBy(3)
            ->voteLevel(5)->baseVoteText('Ermöglicht die Umwandlung von Objekten, mit etwas Anstrengung.')
            ->upgradeTexts([
                               'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 20% gesenkt. Erhöht die Effektivität von Reparaturen um einen Punkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt. Erhöht die Effektivität von Reparaturen um zwei Punkte.',
                           ])
            ->commit($small_refine);

        $container->add()->parentBuilding($small_refine)
            ->icon('item_meca_parts')->label('Verteidigungsanlage')->description('Für diese raffiniert durchdachte Anlage können alle Arten von Platten (z.B. Blech) verwendet werden. Jeder in der Bank abgelegte Verteidigungsgegenstand steuert zusätzliche Verteidigungspunkte bei!')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(0)->resources(["meca_parts_#00" => 4,"wood_beam_#00" => 8,"metal_beam_#00" => 8,])->orderBy(0)
            ->voteLevel(5)->baseVoteText('Jeder in der Bank abgelegte Verteidigungsgegenstand bringt der Stadt 1.5 Verteidigungspunkte zusätzlich ein.')
            ->upgradeTexts([
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 250%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 300%.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('small_dig')->label('Kanonenhügel')->description('Mehrere Erdhügel, die durch Holzbalken verstärkt wurden, bilden die Grundlage für diesen mächtigen Verteidigungsturm.')
            ->isTemporary(0)->defense(30)->ap(50)->health(50)->blueprintLevel(0)->resources(["concrete_wall_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 1,])->orderBy(1)->commit($small_dig);

        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Steinkanone')->description('Dieser automatisierte Wachturm verschießt um Mitternacht minutenlang Felsen mit hoher Geschwindigkeit in Richtung Stadttor. Solltest du vorgehabt haben zu schlafen, kannst du das hiermit vergessen!')
            ->isTemporary(0)->defense(50)->ap(60)->health(60)->blueprintLevel(1)->resources(["tube_#00" => 1,"electro_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Selbstgebaute Railgun')->description('Diese improvisierte Railgun funktioniert mit Luftdruck. Sie ist in der Lage, mehrere Ladungen Metallsplitter (verbogene Nägel und rostiges Metall) mit enormer Geschwindigkeit zu verschießen und faustgroße Löcher zu reißen.')
            ->isTemporary(0)->defense(50)->ap(40)->health(40)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"tube_#00" => 1,"electro_#00" => 1,"metal_beam_#00" => 10,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Blechplattenwerfer')->description('Der Blechplattenwerfer schleudert schwere Blechplatten aufs Schlachtfeld. Die angerichtete Schweinerei willst du garantiert kein zweites Mal sehen...')
            ->isTemporary(0)->defense(60)->ap(50)->health(50)->blueprintLevel(1)->resources(["meca_parts_#00" => 3,"plate_#00" => 3,"explo_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Brutale Kanone')->description('Für diese Maschine möchte man sterben. ...im übertragenen Sinne. Ihr müsst sie allerdings jeden Tag nachladen.')
            ->isTemporary(1)->defense(50)->ap(25)->health(0)->blueprintLevel(0)->resources(["plate_#00" => 1,"metal_beam_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('item_wood_beam')->label('Holzbalkendrehkreuz')->description('Schwere Holzbalken werden auf einem Drehkreuz befestigt und das Ganze wird dann in Bewegung gesetzt. Es dreht sich schnell... sehr schnell.')
            ->isTemporary(0)->defense(10)->ap(15)->health(15)->blueprintLevel(0)->resources(["wood_beam_#00" => 2,"metal_beam_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_factory')->label('Manufaktur')->description('Die Manufaktur ist eine verbesserte Werkstatt. Sie senkt die Verarbeitungskosten aller darin erledigten Arbeiten um 1 AP.')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_saw')->label('Kreischende Sägen')->description('Ein paar geschickt hergestelle Kreissägen, die durch ein ausgeklügeltes Gummizugsystem bewegt werden. Der schrille Rotationslärm erinnert seltsamerweise an einen menschlichen Schrei...')
            ->isTemporary(0)->defense(45)->ap(65)->health(65)->blueprintLevel(0)->resources(["meca_parts_#00" => 3,"metal_#00" => 5,"rustine_#00" => 3,"metal_beam_#00" => 2,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('item_rp_book2')->label('Baustellenbuch')->description('Mit diesem Register erhältst du eine bessere Übersicht zu allen aktuellen Konstruktionen samt der dafür benötigten Materialien.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(15)->health(0)->blueprintLevel(0)->resources(["table_#00" => 1,])->orderBy(5)->commit($item_rp_book2);

        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_refine')->label('Bauhaus')->description('Wenn sich intelligente Leute abends beim Feuer unterhalten, können großartige Erfindungen dabei herauskommen. Zumindest wenn sie vorher ein Bauhaus errichtet haben. Dieses Bauwerk gibt der Stadt täglich einen (gewöhnlichen) Bauplan.')
            ->isTemporary(0)->defense(0)->ap(75)->blueprintLevel(0)->resources(["drug_#00" => 1,"vodka_#00" => 1,"wood_beam_#00" => 10,])->orderBy(0)
            ->voteLevel(3)->baseVoteText('Die Stadt erhält nach dem nächsten Angriff einmalig 1 gewöhnliche Baupläne.')
            ->upgradeTexts([
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 4 gewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 2 gewöhnliche und 2 ungewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 2 ungewöhnliche und 2 seltene Baupläne sowie - möglicherweise - eine nette Überraschung.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('r_dhang')->label('Galgen')->description('An diesem prächtigen Galgen könnt ihr unliebsame (oder lästige) Mitbürger loswerden. Ist mal was "anderes" als die klassische Verbannung...')
            ->isTemporary(0)->defense(0)->ap(13)->health(0)->blueprintLevel(0)->resources(["wood_beam_#00" => 1,"chain_#00" => 1,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_eastercross')->label('Schokoladenkreuz')->description('Ein wunderschönes Kreuz aus Schokolade, an dem unliebsame (oder lästige) Mitbürger platziert werden können. Ist mal was "anderes" als die klassische Verbannung...')
            ->isTemporary(0)->defense(0)->ap(13)->health(0)->blueprintLevel(5)->resources(["wood_beam_#00" => 1,"chain_#00" => 1,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_slaughterhouse')->label('Schlachthof')->description('Ein Schlachthof, der direkt vor dem Stadttor errichtet wird und dessen Eingang zur Außenwelt zeigt. Schwierig ist eigentlich nur, jede Nacht einen Freiwilligen zu finden, der sich hineinstellt und so die Zombies anlockt.')
            ->isTemporary(0)->defense(35)->ap(40)->health(40)->blueprintLevel(1)->resources(["concrete_wall_#00" => 2,"metal_beam_#00" => 10,])->orderBy(7)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('item_shield')->label('Pentagon')->description('Eine großangelegte Neuausrichtung aller Verteidigungsanlagen, um wirklich das Optimum herauszuholen (die Gesamtverteidigung der Stadt erhöht sich um 10%).')
            ->isTemporary(0)->defense(8)->ap(55)->blueprintLevel(3)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 10,])->orderBy(8)
            ->voteLevel(2)->baseVoteText('Die Verteidigung der Stadt wird um 10% erhöht.')
            ->upgradeTexts([
                               'Die Verteidigung der Stadt wird um 12% erhöht.',
                               'Die Verteidigung der Stadt wird um 14% erhöht.'
                           ])
            ->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_cafet')->label('Kleines Cafe')->description('Das Mittagessen liegt schon lange zurück... Was gibt\'s da besseres als eine solide Holzplanke und altbackenes Brot.')
            ->isTemporary(1)->defense(0)->ap(6)->health(0)->blueprintLevel(0)->resources(["water_#00" => 1,"wood2_#00" => 2,"pharma_#00" => 1,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_cemetery')->label('Kleiner Friedhof')->description('Bringt eure Toten! Denn diesmal werden sie sich noch als nützlich erweisen. Macht das beste aus ihnen und verbessert damit gemeinsam eure Verteidigung. Jeder zum Friedhof gebrachte tote Mitbürger bringt +10 Verteidigungspunkte für die Gesamtverteidigung der Stadt. Hinweis: Es spielt keine Rolle, wo und woran ein Mitbürger verstarb.')
            ->isTemporary(0)->defense(0)->ap(36)->health(36)->blueprintLevel(1)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,])->orderBy(10)->commit($small_cemetery);

        $container->add()->parentBuilding($small_cemetery)
            ->icon('small_coffin')->label('Sarg-Katapult')->description('Von 2 Toten hat derjenige, der sich bewegt, die besten Chancen, dich zu verspeisen. Trickst eure Feinde aus, indem ihr eure Leichen in die herankommende Zombiehorde schleudert. Jeder Tote bringt +20 anstelle von +10 Verteidigungspunkten.')
            ->isTemporary(0)->defense(0)->ap(100)->health(100)->blueprintLevel(4)->resources(["courroie_#00" => 1,"meca_parts_#00" => 5,"wood2_#00" => 5,"metal_#00" => 15,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('small_cafet')->label('Kantine')->description('Die Kantine verbessert die Produktion in den Küchen, die die Helden in eurer Stadt gebaut haben.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,])->orderBy(11)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('item_acid')->label('Labor')->description('Das Labor verbessert die Produktion in den Hobbylabors, die die Helden in eurer Stadt gebaut haben.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(1)->resources(["meca_parts_#00" => 3,"pharma_#00" => 5,"wood_beam_#00" => 3,"metal_beam_#00" => 10,])->orderBy(12)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_chicken')->label('Hühnerstall')->description('Falls du schon vor langer Zeit vergessen hast, wie köstlich ein Omelett mit gebratenen Chamignons, Kräutern und Speck ist, dürfte dieses Gebäude wohl eher unnütz für dich sein. Aber zumindest liefert es dir einige Eier. Was die Pilze betrifft, so musst du dich wohl an die Zombies draußen halten.')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(3)->resources(["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,])->orderBy(13)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_infirmary')->label('Krankenstation')->description('Egal ob kleines Wehwehchen oder irreparables Trauma - die Krankenstation empfängt dich mit offenen Armen. Zumindst solange du noch imstande bist, dich selbst zu verarzten, denn diese Einrichtung kommt ganz ohne medizinisches Personal daher.')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(3)->resources(["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(14)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_strategy')->label('Bollwerk')->description('Dieses ambitionierte Stadtbauprojekt hat zum Ziel, die Verteidigung der Bürgerbehausungen besser in die Stadtverteidigung zu integrieren. Dank dieses Bauwerks bringen Bürgerbehausungen fortan 80% statt 40% ihres Verteidigungswertes in die Stadtverteidigung ein.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(16)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_strategy')->label('Baumarkt')->description('Dank diesem wahrlich großen Bauwerk können Bürger ihre Häuser noch aufwändiger ausbauen.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(4)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 10,])->orderBy(15)->commit();

        $container->add()
            ->icon('item_tagger')->label('Wachturm')->description('Mit dem Wachturm ist es möglich den nächtlichen Angriff abzuschätzen. Die Stadtbürger können dann die entsprechenden Gegenmaßnahmen vorbereiten (oder auch nicht...). Nach seiner Fertigstellung können Notfallkonstruktionen aller Art gebaut werden.')
            ->isTemporary(0)->defense(0)->ap(12)->blueprintLevel(0)->resources(["wood2_#00" => 3,"metal_#00" => 2,])->orderBy(4)
            ->voteLevel(5)
            ->upgradeTexts([
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 3km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 6km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 1km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 2km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                           ])
            ->commit($item_tagger);

        $container->add()->parentBuilding($item_tagger)
            ->icon('item_courroie')->label('Katapult')->description('Das Katapult ist ein äußerst mächtiges Werkzeug, mit dem die Stadt jede Art von Gegenstand in die Wüste schießen kann. Das ist sehr nützlich, wenn man weit entfernte Bürger versorgen möchte (Lebensmittel, Wasser, Waffen etc...).')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(1)->resources(["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,])->orderBy(0)->commit($item_courroie);

        $container->add()->parentBuilding($item_courroie)
            ->icon('item_courroie')->label('Verbesserter Katapult')->description('Dieses erheblich verbesserte Katapult ist einfacher zu bedienen und benötigt weniger AP, um mit einem Gegenstand beladen zu werden!')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 2,])->orderBy(0)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('item_tagger')->label('Scanner')->description('Dieser selbstgebaute Zonenscanner erleichtert die Abschätzung des nächtlichen Angriffs erheblich. Wenn er richtig eingesetzt wird, sind nur halb so viele Bürger notwendig, um eine gute Schätzung zu bekommen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(2)->resources(["pile_#00" => 1,"meca_parts_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,])->orderBy(1)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('item_electro')->label('Verbesserte Karte')->description('Diese simple elektronische Konstruktion erleichtert das Lesen der Außenweltkarte. Konkret: Du erfährst die genaue Zombieanzahl jeder Zone und musst somit nicht mehr planlos in der Wüste rumlaufen...')
            ->isTemporary(0)->defense(0)->ap(15)->health(15)->blueprintLevel(1)->resources(["pile_#00" => 2,"metal_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,])->orderBy(2)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('item_tagger')->label('Rechenmaschine')->description('Die Rechenmaschine ist ein etwas rustikaler Taschenrechner, mit dem man die Angriffsstärke des MORGIGEN Tages berechnen kann!')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["rustine_#00" => 1,"electro_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('small_gather')->label('Forschungsturm')->description('Mit dem Forschungsturm können in bereits "abgesuchten" Wüstenzonen jeden Tag neue Gegenstände gefunden werden! Der Forschungsturm versetzt dich in die Lage, jene anormalen meteorologischen Phänomene aufzuzeichnen und auszuwerten, die sich nachts in der Wüste abspielen. Die entsprechenden Fundstellen werden anschließend in der Zeitung veröffentlicht.')
            ->isTemporary(0)->defense(0)->ap(30)->blueprintLevel(1)->resources(["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,])->orderBy(4)
            ->voteLevel(5)->baseVoteText('Zeigt die Windrichtung in der Zeitung an.')
            ->upgradeTexts([
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 37%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 49%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 61%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 73%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 85%.',
                           ])
            ->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('status_terror')->label('Notfallkonstruktion')->description('Die Stadt muss sich auch auf unvorhergesehene Ereignisse einstellen. Für diese Zwecke wurde diese Notfallkonstruktion entworfen: Sie hält in der Regel nur eine Nacht lang. Achtung! Es sollte darauf geachtet werden, nicht zu viele Rohstoffe für den Bau temporärer Konstruktionen zu verwenden!')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood2_#00" => 5,"metal_#00" => 7,])->orderBy(5)->commit($status_terror);

        $container->add()->parentBuilding($status_terror)
            ->icon('item_wood_plate')->label('Notfallabstützung')->description('Wenn es brenzlig wird, ist es oft überlebenswichtig, hier und dort mit ein paar Holzplanken nachzuhelfen und alles nochmal dicht zu machen. Das bringt dir oft noch eine weitere Nacht.')
            ->isTemporary(1)->defense(40)->ap(30)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 8,])->orderBy(0)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_trap')->label('Verteidigungspfähle')->description('Der gesamte Außenbereich der Stadt wird mit angespitzten Holzpfählen gespickt. Diese kostengünstige Verteidigungsmaßnahme verwandelt die Stadt in eine wahre Trutzburg und kann an manchen Abenden den Unterschied zwischen Leben und Tod ausmachen.')
            ->isTemporary(1)->defense(25)->ap(12)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 6,])->orderBy(1)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_trap')->label('Guerilla')->description('Dieses Arsenal an einfallsreichen Guerillafallen ermöglicht dir, die Zombiereihen zu lichten und die Last des Angriffs entscheidend zu senken.')
            ->isTemporary(1)->defense(50)->ap(24)->health(0)->blueprintLevel(2)->resources(["meca_parts_#00" => 2,"wood2_#00" => 2,"metal_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_dig')->label('Abfallberg')->description('Wenn wirklich gar nichts mehr geht, sammelst du alles ein, was du findest und formst daraus einen großen Abfallhaufen... jetzt heißt es Daumen drücken und hoffen, dass das die Horde irgendwie aufhält... Ach ja, wenn du möchtest, kannst du diesen Abfallberg auch mit Fallen spicken.')
            ->isTemporary(1)->defense(5)->ap(10)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(3)->commit($small_dig_2);

        $container->add()->parentBuilding($small_dig_2)
            ->icon('small_dig')->label('Trümmerberg')->description('Hast du erst mal einen großen Haufen Müll aufgeschüttet, kannst du ihn einfach noch mit Stacheln versehen, die ebenso rostig wie tödlich sind!')
            ->isTemporary(1)->defense(60)->ap(40)->health(0)->blueprintLevel(1)->resources(["metal_#00" => 2,])->orderBy(0)->commit();

        $container->add()->parentBuilding($status_terror)
            ->icon('small_trap')->label('Wolfsfalle')->description('Das wird den Zombies Beine machen - oder besser: ausreißen!')
            ->isTemporary(1)->defense(40)->ap(20)->health(0)->blueprintLevel(0)->resources(["metal_#00" => 2,"hmeat_#00" => 3,])->orderBy(4)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_tnt')->label('Sprengfalle')->description('Dynamit, Zombies, Blut.')
            ->isTemporary(1)->defense(35)->ap(20)->health(0)->blueprintLevel(0)->resources(["explo_#00" => 3,])->orderBy(5)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('status_terror')->label('Nackte Panik')->description('Falls die Lage wirklich verzweifelt ist, könnt ihr beschließen loszuschreien und in Panik zu verfallen. Falls alle Überlebenden mitmachen, wird es die Zombies verwirren (denn sie können mit dieser Art Stress nicht umgehen) und euch einige virtuelle Verteidigungespuntke einbringen... Genau, das ist natürlich Unsinn.')
            ->isTemporary(1)->defense(50)->ap(25)->health(25)->blueprintLevel(0)->resources(["water_#00" => 4,"wood2_#00" => 5,"metal_#00" => 5,])->orderBy(6)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_bamba')->label('Dollhouse')->description('Feiern bis zum Abwinken ist immer noch die beste Art, all die schrecklichen Dinge der Außenwelt zu vergessen. Glücklicherweise sorgen die Zombies schon dafür, dass die Dinge nicht zu sehr ausschweifen.')
            ->isTemporary(1)->defense(75)->ap(50)->health(0)->blueprintLevel(2)->resources(["wood2_#00" => 5,"metal_#00" => 5,"radio_on_#00" => 3,])->orderBy(7)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_vaudoudoll')->label('Voodoo-Puppe')->description('Ein über 2 Meter hoher, schimmliger Wollballen, über und über mit Stricken und Nadeln bedeckt. In den mächtigen Händen des Schamanen wird dieses *Ding* zu einem XXL-Püppchen, das etliche Zombies niederstreckt, ehe es wieder eine unförmige, unbewegliche Masse wird.')
            ->isTemporary(0)->defense(65)->ap(40)->health(40)->blueprintLevel(0)->resources(["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_yellow_#00" => 2,])->orderBy(8)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_bokorsword')->label('Bokors Guillotine')->description('Mit Hilfe einer teuflischen Guillotine und einer provisorischen Schaufensterpuppe, kann der Schamane aus der Entfernung den Kopf eines Zombierudel-Führers abschlagen. Die Zombies die ihm folgen, werden daraufhin abdrehen und wieder in die Wüste wandern.')
            ->isTemporary(0)->defense(100)->ap(60)->health(60)->blueprintLevel(0)->resources(["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_yellow_#00" => 3,])->orderBy(9)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_spiritmirage')->label('Spirituelles Wunder')->description('Dieser Zauber des Schamanen erschafft ein Trugbild der Stadt. Als Folge verliert sich eine beträchtliche Anzahl Zombies heillos in der Wüste.')
            ->isTemporary(0)->defense(80)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_yellow_#00" => 2,])->orderBy(10)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_holyrain')->label('Heiliger Regen')->description('Nur der Schamane kennt das Geheimnis dieses rituellen Feuertanzes. Richtig ausgeführt, steigt eine kleine Wolke in den Himmel und bewirkt, dass ein Schauer heiligen Wassers auf die Zombiehorde niedergeht.')
            ->isTemporary(1)->defense(200)->ap(40)->health(0)->blueprintLevel(0)->resources(["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_yellow_#00" => 4,])->orderBy(11)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('small_watchmen')->label('Wächter-Turm')->description('Der Bau eines prächtigen Wachturms mit Kontrollgang macht die Wächter unter den Helden glücklich. Dieses Bauwerk gibt pro Wächter in der Stadt +5 Verteidigungspunkte und erlaubt ihnen, ihre AP in Verteidigungspunkte umzuwandeln und so noch mehr für die Verteidung zu tun.')
            ->isTemporary(0)->defense(15)->ap(24)->health(24)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,])->orderBy(6)->commit($small_watchmen);

        $container->add()->parentBuilding($small_watchmen)
            ->icon('small_tourello')->label('Schießstand')->description('Ein Schießstand mit Wasserpistolen, der wohl aus den Überresten eines Rummels geborgen wurde. Er zaubert eurer Nachtwache ein Lächeln ins Gesicht - damit schickt ihr sicher einen netten Schauer die Mauer runter! Verleiht jeder Wasserwaffe, die auf der Wacht genutzt wird, einen 20% Bonus.')
            ->isTemporary(0)->defense(50)->ap(25)->health(25)->blueprintLevel(2)->resources(["water_#00" => 30,"tube_#00" => 2,"wood_beam_#00" => 1,"metal_beam_#00" => 2,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_watchmen)
            ->icon('small_catapult3')->label('Kleiner Tribok')->description('Mit diesem Katapult kannst du deinen tierischen Freunden ein wenig Starthilfe geben, damit sie noch mehr Schaden in der angreifenden Horde anrichten. Zudem besitzt die Geräuschkulisse von Tieren, die mit hoher Geschwindigkeit durch die Luft fliegen, einen gewissen Unterhaltungswert. 20% auf die Angriffskraft jedes Tieres, das mit auf die Wacht genommen wird.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["wood_beam_#00" => 2,"metal_beam_#00" => 4,"meca_parts_#00" => 2,"plate_#00" => 2,"tube_#00" => 1,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_watchmen)
            ->icon('small_armor')->label('Kleine Waffenschmiede')->description('Diese Waffenwerkstatt ist euer Trumpf für die Nachtwache. Wird sie gebaut, wird jede Waffe, die während der Wacht zum Einsatz kommt um 20% verstärkt.')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(2)->resources(["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 15,"plate_#00" => 2,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_watchmen)
            ->icon('small_ikea')->label('Schwedische Schreinerei')->description('Dieser kleine Laden verbessert die Effektivität jedes Möbelstücks, das auf der Wache benutzt wird um 20%. Hach ja, die Schweden... Nie gab es bessere Billigmöbel!')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(2)->resources(["meca_parts_#00" => 3,"wood2_#00" => 15,"metal_#00" => 10,"plate_#00" => 4,"concrete_wall_#00" => 2,"wood_beam_#00" => 5,])->orderBy(3)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('small_watchmen')->label('Krähennest')->description('Weniger ein Turm als ein seeeeehr hoher Mast, der fast bis in die Wolken reicht. Aufklärer können ihn erklimmen und so Gebäude in der Außenwelt erspähen (1x pro Tag und Held).')
            ->isTemporary(0)->defense(10)->ap(36)->health(36)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,])->orderBy(7)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('small_novlamps')->label('Straßenbeleuchtung')->description('Selbst in der tiefsten Nacht erlaubt dir der fahle Schein der Laternenmasten, deine Ausgrabungen in der Wüste fortzusetzen. Keine Ausreden mehr, um früh ins Bett zu gehen.')
            ->isTemporary(0)->defense(0)->ap(25)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"lens_#00" => 2,"diode_#00" => 2,"metal_beam_#00" => 10,"wire_#00" => 1, "pile_#00" => 5])->orderBy(16)
            ->voteLevel(2)->baseVoteText('Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 2km um die Stadt negiert.')
            ->upgradeTexts([
                               /* 'Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 2km um die Stadt negiert, pro Tag wird 1 Batterie verbraucht.', */
                               'Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 6km um die Stadt negiert, pro Tag wird 1 Batterie verbraucht.',
                               'Die Verringerung der Fundchancen bei Nacht wird auf der gesamten Karte negiert, pro Tag werden 2 Batterien verbraucht.',
                               /* 'Bei Nacht erhöhen sich die Fundchancen im Umkreis von 10km um die Stadt um 20%, pro Tag werden 2 Batterien verbraucht.', */
                           ])
            ->commit();

        $container->add()
            ->icon('small_building')->label('Fundament')->description('Das Fundament ist die Grundvoraussetzung für "Absurde Projekte" (das sind langwierige und anstrengende Bauten, die jedoch für die Stadt mehr als nützlich sind).')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 10,"metal_#00" => 8,])->orderBy(5)->commit($small_building);

        $container->add()->parentBuilding($small_building)
            ->icon('small_moving')->label('Großer Umbau')->description('Dieses absurde Projekt hat den kompletten Umbau der Stadt zum Ziel. Jeder Stein, jedes Brett und jedes Gebäude wird gemäß eines neuen Verteidigungsplans neu ausgerichtet. Hierzu werden u.a. Häuser näher zusammengerückt, Gassen und selten benutzte Straßen versperrt und Wassergeschütztürme auf die Dächer der Stadt montiert. Ein richtig großer Umbau!')
            ->isTemporary(0)->defense(300)->ap(300)->health(300)->blueprintLevel(3)->resources(["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_derrick')->label('Bohrturm')->description('Auch der Bohrturm ist eine absurde Konstruktion. Mit ihm können selbst tiefste wasserführende Schichten angezapft werden! Er fügt +50 Rationen an Wasser dem Brunnen hinzu.')
            ->isTemporary(0)->defense(0)->ap(70)->health(0)->blueprintLevel(3)->resources(["wood_beam_#00" => 10,"metal_beam_#00" => 15,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_falsecity')->label('Falsche Stadt')->description('Es ist weithin bekannt, dass die Zombies nicht so ganz helle sind... Wenn ihr es schafft, eine Stadt nachzubauen, könntet ihr den überwiegenden Großteil des Angriffs auf diesen Nachbau umlenken...')
            ->isTemporary(0)->defense(400)->ap(400)->health(400)->blueprintLevel(3)->resources(["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_valve')->label('Wasserhahn')->description('Dank dieses kleinen, am Brunnen angebrachten Wasserhahns, kannst Du nun die Wassermengen abschöpfen, die ansonten durch das Filtersystem verschwendet werden (es braucht kein zusätzliches Brunnen-Wasser). Du kannst mit diesem Wasser alle auf Wasser basierenden Waffen KOSTENLOS auffüllen (Wasserbombe, Wasserkanone,...)!')
            ->isTemporary(0)->defense(0)->ap(130)->health(130)->blueprintLevel(3)->resources(["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_scarecrow')->label('Vogelscheuche')->description('Dieses Feld voller Vogelscheuchen würde noch viel besser mit der Kleidung der Stadtbewohner funktionieren. Allerdings würde das bedeuten, dass sich die Stadt in ein Nudisten-Camp verwandeln würde.')
            ->isTemporary(0)->defense(25)->ap(35)->health(35)->blueprintLevel(0)->resources(["wood2_#00" => 10,"rustine_#00" => 2,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_trash')->label('Müllhalde')->description('Der Eckpfeiler einer jeden großen Stadt: eine riesige, stinkende Müllhalde, die die ganze Stadt umgibt. Zugegeben, das ist nicht gerade ästhetisch, aber immerhin könnt ihr so Alltagsgegenstände in eine effektive Verteidigung verwandeln (nur eine Nacht haltbar).')
            ->isTemporary(0)->defense(0)->ap(70)->health(70)->blueprintLevel(0)->resources(["concrete_wall_#00" => 5,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(5)->commit($small_trash);

        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Holzabfall')->description('Ermöglicht die fachgerechte Entsorgung von Holz auf der Müllhalde.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Metallabfall')->description('Ermöglicht die fachgerechte Entsorgung von Metall auf der Müllhalde.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["wood2_#00" => 5,"metal_#00" => 5,])->orderBy(5)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Waffenabfall')->description('Ermöglicht die fachgerechte Entsorgung von Waffen auf der Müllhalde.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"metal_#00" => 8,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Biomüll')->description('Ermöglicht die fachgerechte Entsorgung von Nahrung auf der Müllhalde.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(2)->resources(["wood2_#00" => 15,])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Rüstungsabfall')->description('Steigert die Ausbeute jedes auf den Müll geworfenen Verteidigungs-Gegenstandes.')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(2)->resources(["metal_beam_#00" => 3,"metal_#00" => 5,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Verbesserte Müllhalde')->description('Steigert die Ausbeute jedes auf den Müll geworfenen Gegenstandes um +1 Verteidigungspunkt.')
            ->isTemporary(0)->defense(75)->ap(120)->health(120)->blueprintLevel(4)->resources(["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_howlingbait')->label('Tierabfälle')->description('Ermöglicht das fachgerechte Massakrieren unschuldiger Tiere auf der Müllhalde.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(2)->resources(["wood_beam_#00" => 10,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trashclean')->label('Müll für Alle')->description('Senkt die Kosten der Nutzung der Müllhalde auf 0 AP. Achtung: Falls ihr Halunken in eurer Stadt habt, solltet ihr euch den Bau gut überlegen...')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"trestle_#00" => 2,])->orderBy(7)->commit();

        $container->add()->parentBuilding($small_building)
            ->icon('small_fleshcage')->label('Fleischkäfig')->description('Moderne Justiz in all seiner Pracht! Wird ein Mitbürger verbannt, kann er in den Fleischkäfig gesteckt werden, welcher sich wohlweislich direkt vor dem Stadttor befindet. Seine Schreie und Tränen dürften einen exzellenten Köder um Mitternacht abgeben. Jeder verbannte und in den Fleischkäfig gesteckte Mitbürger bringt der Stadt vorübergehende Verteidigungspuntke ein.')
            ->isTemporary(0)->defense(0)->ap(40)->health(0)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"wood_beam_#00" => 1,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_lighthouse')->label('Leuchtturm')->description('Dieser schöne, hohe Leuchtturm wird Licht in lange Winternächte bringen (hat er im Sommer eigentlich irgendeinen Nutzen?). Alle Stadtbewohner auf Camping-Ausflug haben eine höhere Überlebens-Chance.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(7)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_city_up')->label('Befestigungen')->description('Da Bürger normalerweise nicht direkt von Verteidigungsbauten profitieren, wollen wir uns mal nicht beschweren. Alle Bürgerbehausungen erhalten +4 Verteidigung.')
            ->isTemporary(0)->defense(0)->ap(50)->health(0)->blueprintLevel(3)->resources(["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_score')->label('Leuchtfeuer')->description('Ein großes Leuchtfeuer, irgendwo weit abseits von der Stadt entzündet, soll die Zombies von unseren Häusern weglocken.')
            ->isTemporary(1)->defense(30)->ap(15)->health(15)->blueprintLevel(2)->resources(["lights_#00" => 1,"wood2_#00" => 5,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_court')->label('Bürgergericht')->description('Wie jeder weiß, haben Helden immer recht. Um diesen Fakt weiter zu zementieren, zählen alle von Helden gegen andere Bürger ausgesprochenen Beschwerden doppelt.')
            ->isTemporary(0)->defense(0)->ap(12)->health(12)->blueprintLevel(2)->resources(["wood2_#00" => 6,"metal_beam_#00" => 15,"table_#00" => 1,])->orderBy(10)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_slave')->label('Ministerium für Sklaverei')->description('Das Ministerium für Sklaverei hat beschlossen, dass Verbannte auf den Baustellen arbeiten dürfen. Außerdem erhält jeder von ihnen in ein Bauprojekt investierte AP einen 50%-Bonus (z.B. aus 6 AP werden so 9 AP, die in das Bauprojekt fließen).')
            ->isTemporary(0)->defense(0)->ap(45)->health(45)->blueprintLevel(4)->resources(["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,])->orderBy(11)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_derrick')->label('Tunnelratte')->description('Da selbst der Bohrer des Bohrturms nicht durch jede Schicht durchkommt, muss man hin und wieder kleine und mit Dynamit bestückte Tiere in die Tiefe schicken. Dieses Projekt fügt den städtischen Wasserreserven +150 Rationen hinzu.')
            ->isTemporary(0)->defense(0)->ap(170)->health(0)->blueprintLevel(4)->resources(["concrete_wall_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(12)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_cinema')->label('Kino')->description('Sie zeigen Dawn of the Dead... zum 636. Mal. Bisher war dir die überzeugende Darstellung eines Nebendarstellers noch nie so richtig aufgefallen. Es gibt tatsächlich noch etwas Neues zu entdecken. Und wer weiß? Mit ein bisschen Glück bringt dich der Film ja sogar zum Lachen.')
            ->isTemporary(0)->defense(0)->ap(75)->health(75)->blueprintLevel(4)->resources(["electro_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 5,"machine_1_#00" => 1,"machine_2_#00" => 1,])->orderBy(13)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_balloon')->label('Heißluftballon')->description('Ein großer, runder Ballon steigt hinauf in den Himmel. Aber nur solange, wie der "Freiwillige" in der Gondel braucht, um alles rund um die Stadt zu erfassen. Das Bauwerk ermöglicht es Dir, die gesamte Außenwelt zu entdecken.')
            ->isTemporary(0)->defense(0)->ap(80)->health(80)->blueprintLevel(4)->resources(["meca_parts_#00" => 6,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(14)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_labyrinth')->label('Labyrinth')->description('Zombies sind bekanntermaßen einfach gestrickt. Warum ihnen dann nicht einfach ein kleines Labyrinth vor die Nase (das Stadttor) setzen und dabei zusehen, wie ihr Angriff an Schwung verliert. Das Ganze ist äußerst effektiv. Doch jeder Bürger, der die Stadt betreten will, muss dann 1 AP aufbringen.')
            ->isTemporary(0)->defense(150)->ap(200)->health(200)->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,])->orderBy(15)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_lastchance')->label('Alles oder nichts')->description('Nicht mehr als ein Akt der Verzweiflung! Alle Gegenstände in der Bank werden zerstört und bringen jeweils +1 vorübergehende Verteidigung.')
            ->isImpervious(true)->isTemporary(0)->defense(55)->ap(150)->blueprintLevel(3)->resources(["meca_parts_#00" => 4,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(16)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_rocket')->label('Luftschlag')->description('Vier feine Raketen werden gestartet und auf vier strategische Ziele rund um die Stadt (Norden, Süden, Osten, Westen) abgefeuert. Auf ihrem Weg töten sie jeden Zombie.')
            ->isTemporary(1)->defense(0)->ap(50)->health(0)->blueprintLevel(3)->resources(["water_#00" => 10,"meca_parts_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,])->orderBy(17)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_fireworks')->label('Feuerwerk')->description('Es gibt nichts Besseres, um die Tristesse langer Wüstennächte zu vertreiben, als ein schönes, großes Feuerwerk. Diese spezielle Variante geht so: Man feuert die Raketen in die Bereiche rund um die Stadt ab und zündet sie dann um Punkt Mitternacht inmitten der Zombiehorden.')
            ->isTemporary(0)->defense(100)->ap(90)->health(90)->blueprintLevel(0)->resources(["firework_powder_#00" => 1,"firework_tube_#00" => 1,"firework_box_#00" => 2])->orderBy(18)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_redemption')->label('Altar')->description('Weil der Rabe gut und gerecht ist, befreit dieser zu seinen Ehren errichtete Schrein alle Bürger, die aus der Stadt verbannt wurden.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(24)->blueprintLevel(2)->resources(["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,])->orderBy(19)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_pmvbig')->label('Riesiger KVF')->description('Ein wirklich riesiger KVF, auf dem die Namen aller Bürger der Stadt eingraviert sind, erhebt sich stolz in den Himmel... äh. Genau, ein KVF. Niemand weiß warum, aber jemand hat am Fuße des Bauwerks "Eigentum der tiefsinnigen Nacht" eingraviert. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["meca_parts_#00" => 2,"metal_#00" => 30,])->orderBy(20)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_crow')->label('Krähenstatue')->description('Huldigt den Raben! Gelobt sei deine Milde und deine erhabene Austrahlung! Befreie uns vom Spam und vergib uns unsere Trollenbeiträge so wie auch wir vergeben anderen Trollen. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["hmeat_#00" => 3,"wood_beam_#00" => 35,])->orderBy(21)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_wheel')->label('Riesenrad')->description('Es ist wirklich eine enorme und beeindruckente Konstruktion. Ihr habt eure kostbarsten Materialien an dieses verdammte Ding verschwendet, und denoch seid ihr irgendwie stolz darauf. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,])->orderBy(22)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_castle')->label('Riesige Sandburg')->description('Wenn es eines gibt, woran hier wahrlich kein Mangel herrscht, dann ist es Sand. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,])->orderBy(23)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_arma')->label('Reaktor')->description('Dieses furchterregende Konstrukt stammt aus einem sowjetischen U-Boot und sendet gleißende Blitze knisternder Elektrizität rund um die Stadt aus. Einziger Haken an der Sache: Es muss jeden Tag repariert werden. Falls es zerstört wird, würde die Stadt mitsamt der gesamten Umgebung augenblicklich ausradiert werden (inklusive euch). Das Schild am Reaktor besagt: sowjetische Bauweise, hergestellt in « вшивый ».')
            ->isImpervious(true)->isTemporary(0)->defense(500)->ap(100)->health(250)->blueprintLevel(4)->resources(["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,])->orderBy(24)->commit();

        $container->add()
            ->icon('small_door_closed')->label('Portal')->description('Eine rustikal anmutende Konstruktion, mit der die Öffnung des Stadttors nach 23:40 erfolgreich verhindert werden kann (es dürfte äußerst selten vorkommen, dass das Tor danach nochmal geöffnet werden muss). Das Stadttor muss nichtsdestotrotz zusätzlich noch per Hand geschlossen werden.')
            ->isTemporary(0)->defense(0)->ap(16)->health(16)->blueprintLevel(0)->resources(["metal_#00" => 2,])->orderBy(6)->commit($small_door_closed);

        $container->add()->parentBuilding($small_door_closed)
            ->icon('small_door_closed')->label('Kolbenschließmechanismus')->description('Dieser äußerst leistungsstarke Kolbenmotor schließt und verriegelt das Stadttor eine halbe Stunde vor Mitternacht. Nach der Schließung kann das Tor nicht mehr geöffnet werden.')
            ->isTemporary(0)->defense(30)->ap(24)->health(24)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,])->orderBy(0)->commit($small_door_closed_2);

        $container->add()->parentBuilding($small_door_closed_2)
            ->icon('small_door_closed')->label('Automatiktür')->description('Das Stadttor schließt sich selbsttätig um 23:59 anstatt 23:30.')
            ->isTemporary(0)->defense(0)->ap(10)->health(10)->blueprintLevel(1)->resources([])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_door_closed)
            ->icon('item_plate')->label('Torpanzerung')->description('Ein paar improvisierte Panzerplatten werden direkt auf das Stadttor geschraubt und verbessern so die Widerstandskraft desselben.')
            ->isTemporary(0)->defense(20)->ap(35)->health(35)->blueprintLevel(0)->resources(["wood2_#00" => 3,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_door_closed)
            ->icon('small_ventilation')->label('Ventilationssystem')->description('Dieser Geheimgang erlaubt es Helden, ein- und auszugehen, ohne das Stadttor zu benutzen!')
            ->isTemporary(0)->defense(20)->ap(24)->health(24)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"metal_#00" => 8,])->orderBy(2)->commit();

        $container->add()
            ->icon('small_spa4souls')->label('Hammam')->description('Ein Ort der Entspannung und der Meditation, perfekt geeignet um eine Seele auf die Andere Seite zu geleiten.')
            ->isTemporary(0)->defense(30)->ap(20)->health(20)->blueprintLevel(0)->resources(["wood2_#00" => 2,"plate_#00" => 2,])->orderBy(7)->commit();

        $data = $container->toArray();

        /*
        $data = array_replace_recursive($data, [
            ["name" => "Verstärkte Stadtmauer",'desc' => 'Verbessert die Stadtverteidigung erheblich.', "temporary" => 0,"img" => "small_wallimprove","vp" => 30,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 15,"metal_#00" => 5,], "orderby" => 0, "children" => [
                ["name" => "Großer Graben",'desc' => 'Der Große Graben ist eine sehr wirkungsvolle Verteidigungsmaßnahme, die sich insbesondere auf lange Sicht auszahlt. Der Graben lässt sich mit allerhand Dingen füllen.', "maxLevel" => 5,"temporary" => 0,"img" => "small_gather","vp" => 10,"ap" => 80, "hp" => 80,"bp" => 0,"rsc" => [], "orderby" => 0,
                    "lv0text" => 'Der Verteidigungswert der Stadt steigt mit dem Großen Graben auf 10 Punkte.',
                    "upgradeTexts" => [
                        'Der Verteidigungsbonus des Grabens steigt dauerhaft um 13.',
                        'Der Verteidigungsbonus des Grabens steigt dauerhaft um 21.',
                        'Der Verteidigungsbonus des Grabens steigt dauerhaft um 32.',
                        'Der Verteidigungsbonus des Grabens steigt dauerhaft um 33.',
                        'Der Verteidigungsbonus des Grabens steigt dauerhaft um 51.',
                    ], "children" => [
                    ["name" => "Wassergraben",'desc' => 'Eine verbesserte Version des Großen Grabens. Muss mit Wasser gefüllt werden...', "temporary" => 0,"img" => "small_waterhole","vp" => 65,"ap" => 50, "hp" => 50,"bp" => 1,"rsc" => ["water_#00" => 20,], "orderby" => 0, ],
                ]],
                ["name" => "Rasierklingenmauer",'desc' => 'Die Rasierklingenmauer folgt einem ganz einfachen Prinzip: Man nehme allerlei Eisenstücke, schärfe und spitze sie an und verteile sie anschließend über die ganze Stadtmauer. Die Mauer verwandelt sich so in eine überdimensionale Zombiefeile.', "temporary" => 0,"img" => "item_plate","vp" => 50,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["metal_#00" => 15,"meca_parts_#00" => 2,], "orderby" => 1],
                ["name" => "Pfahlgraben",'desc' => 'Diese verbesserte Variante des Großen Grabens besteht aus einer großen Anzahl zugespitzter Holzpfähle.', "temporary" => 0,"img" => "small_spears","vp" => 40,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 8,"wood_beam_#00" => 4,], "orderby" => 2],
                ["name" => "Stacheldraht",'desc' => 'Na ja, dieser Stachel"draht" ist noch simpler als der normale... Der Grund: Der Draht fehlt.', "temporary" => 0,"img" => "small_barbed","vp" => 10,"ap" => 20, "hp" => 20,"bp" => 0,"rsc" => ["metal_#00" => 2,], "orderby" => 3, "children" => [
                    ["name" => "Köder",'desc' => 'Mit diesem an einem Stacheldraht befestigtem Stück Fleisch kann man ein paar Zombies \'ne Zeit lang beschäftigen', "temporary" => 1,"img" => "small_meatbarbed","vp" => 80,"ap" => 10, "hp" => 0,"bp" => 1,"rsc" => ["bone_meat_#00" => 3,], "orderby" => 0],
                ]],
                ["name" => "Weiterentwickelte Stadtmauer",'desc' => 'Auf die Verteidigungsvorichtungen müssen wir heute Nacht verzichten, aber diese intelligent gebaute und ausbaufähige Stadtmauer hat mehr drauf, als man denkt.', "temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 9,"metal_beam_#00" => 6,], "orderby" => 4],
                ["name" => "Verstärkende Balken",'desc' => 'Mit diesen Metallbalken können die schwächeren Stellen der Stadtmauer verstärkt werden.', "temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"metal_beam_#00" => 3,], "orderby" => 5, "children" => [
                    ["name" => "Zackenmauer",'desc' => 'Diese Mauer ist mit einer großen Anzahl an Metallspitzen gespickt, damit die Stadtbewohner beim Angriff um Mitternacht ein paar nette Spieße Zombieschaschlik herstellen können.', "temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 35, "hp" => 35,"bp" => 1,"rsc" => ["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,], "orderby" => 0, "children" => [
                        ["name" => "Groooße Mauer", 'desc' => 'Was ist besser als eine Mauer?... eine groooße Mauer.',"temporary" => 0,"img" => "item_plate","vp" => 80,"ap" => 50, "hp" => 50,"bp" => 2,"rsc" => ["wood2_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 0],
                    ]],
                    ["name" => "Zweite Schicht",'desc' => 'Damit selbst hartnäckige Zombies draußen bleiben, bekommt die gesamte Stadtmauer eine zusätzliche Schicht verpasst.', "temporary" => 0,"img" => "item_plate","vp" => 75,"ap" => 65, "hp" => 65,"bp" => 1,"rsc" => ["wood2_#00" => 35,"metal_beam_#00" => 5,], "orderby" => 1, "children" => [
                        ["name" => "Dritte Schicht", 'desc' => 'Eine dritte Schicht über der bestehenden Mauer bietet noch besseren Schutz gegen untote Eindringlinge.',"temporary" => 0,"img" => "item_plate","vp" => 100,"ap" => 65, "hp" => 65,"bp" => 2,"rsc" => ["metal_#00" => 30,"plate_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 0],
                    ]],
                    ["name" => "Entwicklungsfähige Stadtmauer", 'desc' => 'Die Stadtmauer wird mit einem Eisengestell verstärkt und kann ab sofort jeden Tag ganz leicht um ein Stück erweitert werden!',"maxLevel" => 5,"temporary" => 0,"img" => "item_home_def","vp" => 55,"ap" => 65, "hp" => 65,"bp" => 3,"rsc" => ["wood2_#00" => 5,"metal_#00" => 20,"concrete_wall_#00" => 1,], "orderby" => 2,
                        'lv0text' => "Die entwicklungsfähige Stadtmauer bringt der Stadt 55 Verteidigungspunkte.",
                        "upgradeTexts" => [
                            'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 30.',
                            'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 35.',
                            'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 50.',
                            'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 65.',
                            'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 80.',
                        ]],
                    ["name" => "Betonschicht",'desc' => 'Zu guter Letzt bekommt die Mauer noch eine Schicht aus Beton verpasst. Das sollte die Zombiehorden nun aber wirklich draußen halten.', "temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 60, "hp" => 60,"bp" => 1,"rsc" => ["concrete_wall_#00" => 6,"metal_beam_#00" => 2,], "orderby" => 3],
                ]],
                ["name" => "Zombiereibe",'desc' => 'Man bedecke eine große Bodenfläche mit einem Meer von zugespitzten und geschärften Metallstücken und schon erhält man die größte Käsereibe der Welt.', "temporary" => 0,"img" => "small_grater","vp" => 55,"ap" => 60, "hp" => 60,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 20,"plate_#00" => 3,], "orderby" => 6],
                ["name" => "Fallgruben",'desc' => 'Ihr legt eine große Anzahl von verdeckten Fallgruben rund um die Stadt an und wartet bis irgendwas reinfällt. So einfach.', "temporary" => 0,"img" => "small_gather","vp" => 35,"ap" => 50, "hp" => 50,"bp" => 0,"rsc" => ["wood2_#00" => 10,], "orderby" => 7],
                ["name" => "Zaun (Baustellen)", 'desc' => 'Die Stadt baut einen Holzzaun, der - zumindest theoretisch- die Bestien ausreichend verlangsamen sollte.',"temporary" => 0,"img" => "small_fence","vp" => 30,"ap" => 50, "hp" => 50,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,], "orderby" => 8],
                ["name" => "Holzzaun", 'desc' => 'Verbessert die Stadtverteidigung erheblich.',"temporary" => 0,"img" => "small_fence","vp" => 45,"ap" => 50, "hp" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"wood_beam_#00" => 5,], "orderby" => 9],
                ["name" => "Einseifer", 'desc' => 'Warum ist da vorher noch niemand drauf gekommen? Anstatt Zeit mit Körperpflege zu verschwenden, benutzt eure Seife lieber dazu, die Stadtmauer schön glitschig zu machen. Vor allem im Zusammenspiel mit der Zombiereibe eine "saubere Lösung". Wen stören da schon die Geräusche?', "temporary" => 0,"img" => "small_wallimprove","vp" => 60,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 5,"concrete_wall_#00" => 1,], "orderby" => 10],
                ["name" => "Zerstäuber", 'desc' => 'Ein handliches, hydraulisch betriebenes Gerät, das Wasserdampf versprühen kann (und weitere amüsante Chemikalien).',"temporary" => 0,"img" => "small_waterspray","vp" => 0,"ap" => 50, "hp" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 2,], "orderby" => 11, "children" => [
                    ["name" => "Säurespray",'desc' => 'Das wird die hübschen Antlitze der Zombies vor der Stadt sicher auch nicht verbessern.', "temporary" => 1,"img" => "small_acidspray","vp" => 40,"ap" => 30, "hp" => 0,"bp" => 1,"rsc" => ["water_#00" => 2,"pharma_#00" => 3,], "orderby" => 0],
                    ["name" => "Spraykanone", 'desc' => 'Oft wird vergessen, dass Zombies ein Gehirn haben. Manchmal sogar zwei, wenn sie Glück haben. Trifft sich gut: Das mit dieser Kanone geschossene Konzentrat hat die erstaunliche Fähigkeit, Gehirne in Matsch zu verwandeln.', "temporary" => 1,"img" => "small_gazspray","vp" => 150,"ap" => 40, "hp" => 0,"bp" => 2,"rsc" => ["water_#00" => 2,"pharma_#00" => 5,"drug_#00" => 1,], "orderby" => 1],
                ]],
                ["name" => "Rüstungsplatten", 'desc' => 'Ein simpler Verteidigungsgegenstand, aber du wirst ihn zu schätzen wissen, wenn dein Ex-Nachbar Kevo versuchen sollte, an deinem Gehirn rumzuknabbern..',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,], "orderby" => 12],
                ["name" => "Rüstungsplatten 2.0", 'desc' => 'Diese Verbesserung ist nicht der ganz große Wurf, aber sie erfüllt ihren Zweck: Sie verhindert, dass du zu schnell stirbst.',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["metal_#00" => 10,], "orderby" => 13],
                ["name" => "Rüstungsplatten 3.0", 'desc' => 'Simpel aber stabil: Was will man mehr?',"temporary" => 0,"img" => "item_plate","vp" => 40,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 10,], "orderby" => 14],
                ["name" => "Sperrholz", 'desc' => 'Sperrholz. Du hast es nur genommen, weil du wirklich nichts besseres zu tun hattest. Dir war klar, dass es unnütz sein würde, aber das hat dich trotzdem nicht davon abgehalten. Na dann mal los...',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 15],
                ["name" => "Extramauer", 'desc' => 'Es war sicher kein Geniestreich dieses Bauwerk zu beginnen. Aber gut, letztlich haben alle zugestimmt und eine weitere große Mauer ist sicher keine schlechte Idee.',"temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 30, "hp" => 30,"bp" => 1,"rsc" => ["wood2_#00" => 15,"metal_#00" => 15,], "orderby" => 16],
                ["name" => "Brustwehr", 'desc' => 'Hast du es satt, über die abgetrennten Gliedmaßen der Zombies zu stolpern, die du erledigt hast? Vielleicht ist es dann an der Zeit, in der Apokalypse aufzusteigen and und die Zombiehorden von oben zu beobachten. Mithilfe der Brustwehr kannst du des Nachts über die Stadt wachen und dem Himmel ein Stück näher zu kommen.',"temporary" => 0,"img" => "small_round_path","vp" => 0,"ap" => 20, "hp" => 0,"bp" => 0,"rsc" => ["wood2_#00" => 6,"metal_#00" => 2,"meca_parts_#00" => 1,], "orderby" => 17],
            ]],

            ["name" => "Pumpe",'desc' => 'Die Pumpe ist die Grundvoraussetzung für alle auf Wasser basierenden Konstruktionen! Darüber hinaus steigert sie die Wasserergiebigkeit des Brunnens um ein Vielfaches.', "maxLevel" => 5,"temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 25, "hp" => 0,"bp" => 0,"rsc" => ["metal_#00" => 8,"tube_#00" => 1,], "orderby" => 1, "impervious" => true,
                "lv0text" => 'Der Brunnen wird einmalig mit 5 Rationen Wasser befüllt.',
                "upgradeTexts" => [
                    'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                    'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                    'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                    'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                    'Der Brunnen der Stadt wird einmalig um 40 Rationen Wasser aufgefüllt',
                ], "children" => [
                ["name" => "Wasserreiniger", 'desc' => 'Verwandelt in der Wüste gefundenes Kanisterwasser in Trinkwasser.',"temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 75, "hp" => 75,"bp" => 0,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 6,"tube_#00" => 3,], "orderby" => 0, "children" => [
                    ["name" => "Minen", 'desc' => 'Raketenpulver, Zünder und reines Wasser: Das sind die Zutaten für einen saftigen Brei aus vermodertem Fleisch diese Nacht. Eine mächtige Verteidigung, leider kann sie nur einmal verwendet werden.',"temporary" => 1,"img" => "item_bgrenade","vp" => 115,"ap" => 50, "hp" => 0,"bp" => 2,"rsc" => ["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,], "orderby" => 0],
                    ["name" => "Wasserfilter", 'desc' => 'Verbessert die Ausbeute des Wasserreinigers erheblich (hoher Wirkungsgrad).',"temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 60, "hp" => 60,"bp" => 3,"rsc" => ["metal_#00" => 10,"electro_#00" => 2,"fence_#00" => 1,], "orderby" => 1],
                ]],
                ["name" => "Gemüsebeet", 'desc' => 'Mit einem Gemüsebeet könnt ihr leckere Früchte und nicht allzu verschimmeltes Gemüse anbauen. Ist zwar kein Bio, macht aber satt.',"temporary" => 0,"img" => "item_vegetable_tasty","vp" => 0,"ap" => 60, "hp" => 60,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,], "orderby" => 1, "children" => [
                    ["name" => "Granatapfel", 'desc' => 'Ein gewaltiger wissenschaftlicher Durchbruch: Durch die Aussaat von Dynamitstangen und gaaanz vorsichtiges Gießen, könnt ihr Granatäpfel anbauen!',"temporary" => 0,"img" => "item_bgrenade","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,], "orderby" => 0],
                    ["name" => "Dünger", 'desc' => 'Verbessert die Ausbeute des Gemüsebeets erheblich.',"temporary" => 0,"img" => "item_digger","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 3,"rsc" => ["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,], "orderby" => 1],
                ]],
                ["name" => "Brunnenbohrer", 'desc' => 'Mit diesem selbstgebauten Bohrer kann die Stadt ihre Wasserreserven beträchtlich vergrößern.',"temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 60, "hp" => 0,"bp" => 0,"rsc" => ["wood_beam_#00" => 7,"metal_beam_#00" => 2,], "orderby" => 2, "impervious" => true, "children" => [
                    ["name" => "Projekt Eden",'desc' => 'Eine radikale Lösung, wenn mal das Wasser ausgehen sollte: Mit ein paar gezielten Sprengungen können tiefergelegene Wasserschichten erschlossen und das Wasserreservoir vergrößert werden.', "temporary" => 0,"img" => "small_eden","vp" => 0,"ap" => 65, "hp" => 0,"bp" => 3,"rsc" => ["explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 8,], "orderby" => 0, "impervious" => true],
                ]],
                ["name" => "Wasserleitungsnetz",'desc' => 'Nach der Pumpe könnt ihr mit dem Wasserleitungsnetz die auf Wasser basierenden Verteidigungsmechanismen angehen! Zusätzlich vergrößert das Netz auch die Wasserreserven des Brunnens.', "temporary" => 0,"img" => "item_firework_tube","vp" => 0,"ap" => 40, "hp" => 0,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,], "orderby" => 3, "impervious" => true, "children" => [
                    ["name" => "Kärcher",'desc' => 'Dieser leistungsstarke Dampfstrahlreiniger versprüht feinen, siedend heißen Wasserdampf. Deine muffigen Freunde werden beim Anblick dieses Geräts wortwörtlich dahinschmelzen.', "temporary" => 0,"img" => "small_waterspray","vp" => 50,"ap" => 50, "hp" => 50,"bp" => 0,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 7,], "orderby" => 0],
                    ["name" => "Kreischender Rotor",'desc' => 'Es handelt sich um ein einfallsreiches und SEHR effektives System! Zwei schnell kreisende und mit geschliffenen Eisenstangen bestückte Drehscheiben, die von einem Kolbenmechanismus angetrieben werden, zerfetzen alles und jeden, der sich im Toreingang befindet!', "temporary" => 0,"img" => "small_grinder","vp" => 50,"ap" => 55, "hp" => 55,"bp" => 1,"rsc" => ["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,], "orderby" => 1],
                    ["name" => "Sprinkleranlage",'desc' => 'Wie jeder weiß, wird eine Sprinkleranlage für gewöhnlich im Garten eingesetzt. Die wenigsten wissen jedoch, dass sie sich auch hervorragend gegen Zombiehorden eignet. Einziger Wermutstropfen: Die Anlage verbraucht relativ viel Wasser.', "temporary" => 0,"img" => "small_sprinkler","vp" => 150,"ap" => 85, "hp" => 85,"bp" => 3,"rsc" => ["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,], "orderby" => 2],
                    ["name" => "Dusche",'desc' => 'Nein, ganz ehrlich, dieser... dieser... Geruch ist einfach nicht auszuhalten: Nimm eine Dusche. Sofort!', "temporary" => 0,"img" => "small_shower","vp" => 0,"ap" => 25, "hp" => 25,"bp" => 2,"rsc" => ["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,], "orderby" => 3],
                ]],
                ["name" => "Wasserturm","maxLevel" => 5,'desc' => 'Mit dieser revolutionären Verteidigungsanlage ist die Stadt imstande, große Wasserdampfwolken zu erzeugen. Ein wohlig-warmes Dampfbad wird den vor den Stadtmauern herumlungernden Zombies gut tun und sie grundlegend "reinigen". Die Leistung kann mit ein wenig Feintuning noch gesteigert werden.', "temporary" => 0,"img" => "item_tube","vp" => 70,"ap" => 60, "hp" => 60,"bp" => 3,"rsc" => ["water_#00" => 40,"tube_#00" => 7,"metal_beam_#00" => 10,], "orderby" => 4,
                    "lv0text" => "Der Wasserwerfer gibt 70 zusätzliche Verteidigungspunkte.",
                    "upgradeTexts" => [
                        'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                        'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                        'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                        'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 224.',
                        'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 280.',
                    ]],
                ["name" => "Wasserfänger",'desc' => 'Wenn es um Wasser geht, zählt jeder Tropfen. Dieses Bauwerk fügt dem Brunnen +2 Rationen Wasser hinzu und kann jeden Tag gebaut werden.', "temporary" => 1,"img" => "item_tube","vp" => 0,"ap" => 12, "hp" => 0,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,], "orderby" => 5],
                ["name" => "Wasserkanone",'desc' => 'Ein hübscher kleiner Wasserstrahl, um die wachsende Zombiemeute beim Stadttor zu sprengen.', "temporary" => 0,"img" => "small_watercanon","vp" => 80,"ap" => 40, "hp" => 40,"bp" => 2,"rsc" => ["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 6],
                ["name" => "Apfelbaum",'desc' => 'Dieser Apfelbaum erinnert eher an einen verkümmerten und halbtoten Busch, aber er trägt wunderschöne blaue Äpfel. Äh, Moment mal,... wie bitte?', "temporary" => 0,"img" => "small_appletree","vp" => 0,"ap" => 30, "hp" => 0,"bp" => 3,"rsc" => ["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"wood_beam_#00" => 1,], "orderby" => 7],
                ["name" => "Schleuse",'desc' => 'Selbst das Abwasser der Stadt kann noch genutzt werden: Wir müssen bloß alle Toiletten der Stadt über ein ausgeklügeltes System aus Rohren und Schlitten miteinander verbinden und dann um Mitternacht die Schleusen öffnen. Hat auch jeder sein Zelt korrekt aufgebaut?', "temporary" => 0,"img" => "small_shower","vp" => 60,"ap" => 50, "hp" => 50,"bp" => 1,"rsc" => ["water_#00" => 15,"wood2_#00" => 10,], "orderby" => 8],
                ["name" => "Wasserfall",'desc' => 'Anfangs war es nur zur Dekontaminierung gedacht. Aber dann stellte es sich als äußerst effizientes Mittel gegen unsere pestilenten Freunde heraus. Man gebe noch einen Spritzer Kokosnuss-Duschgel hinzu und siehe da: Die meterhohen Leichenstapel, für die DU verantwortlich bist, verströmen ein betörendes Aroma.', "temporary" => 0,"img" => "small_shower","vp" => 35,"ap" => 20, "hp" => 20,"bp" => 1,"rsc" => ["water_#00" => 10,], "orderby" => 9],
                ["name" => "Wünschelrakete",'desc' => 'Ein selbstgebauer Raketenwerfer feuert in den Boden: Denk mal drüber nach! Der Legende nach wollte der geistige Vater dieses Bauprojekts eigentlich den "Rocket Jump" erfinden. Egal, +60 Rationen Wasser werden so zum Brunnen hinzugefügt.', "temporary" => 0,"img" => "small_rocketperf","vp" => 0,"ap" => 90, "hp" => 0,"bp" => 3,"rsc" => ["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 10, "impervious" => true],
                ["name" => "Wünschelrute",'desc' => 'Dass "Hightech" nicht nur auf die Dezimierung von Zombiehorden beschränkt ist, beweist dieses Gebäude... Es fügt +100 Rationen Wasser zum Brunnen hinzu.', "temporary" => 0,"img" => "small_waterdetect","vp" => 0,"ap" => 130, "hp" => 0,"bp" => 4,"rsc" => ["electro_#00" => 5,"wood_beam_#00" => 5,"metal_beam_#00" => 10,], "orderby" => 11, "impervious" => true],
            ]],

            ["name" => "Metzgerei",'desc' => 'In der Metzgerei könnt ihr eure kleinen treuen Begleiter (Hunde, Katzen, Schlangen ...) in Lebensmittel verwandeln. Da gibt es doch tatsächlich noch Leute, die Vegetarier sind...', "temporary" => 0,"img" => "item_meat","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 9,"metal_#00" => 4,], "orderby" => 2, "children" => [
                ["name" => "Kremato-Cue",'desc' => 'Jeder weiß, was ein Krematorium ist, richtig? Und jeder weiß, wozu man einen Barbecuegrill verwendet? Dann einfach eins und eins zusammenzählen, dann wisst ihr auch wie ein "Kremato-Cue" funktioniert. Die Zeiten des Hungerns sind jedenfalls vorbei...', "temporary" => 0,"img" => "item_hmeat","vp" => 0,"ap" => 45, "hp" => 45,"bp" => 2,"rsc" => ["wood_beam_#00" => 8,"metal_beam_#00" => 1,], "orderby" => 0],
            ]],

            ["name" => "Werkstatt","maxLevel" => 5,'desc' => 'Die Entwicklung einer jeden Stadt hängt vom Bau einer verdreckten Werkstatt ab. Sie ist die Voraussetzung für alle weiter entwickelten Gebäude.', "temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 25, "hp" => 25,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 3,
                "upgradeTexts" => [
                    'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                    'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                    'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                    'Die AP-Kosten aller Bauprojekte werden um 20% gesenkt. Erhöht die Effektivität von Reparaturen um einen Punkt.',
                    'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt. Erhöht die Effektivität von Reparaturen um zwei Punkte.',
                ], "children" => [
                ["name" => "Verteidigungsanlage","maxLevel" => 5,'desc' => 'Für diese raffiniert durchdachte Anlage können alle Arten von Platten (z.B. Blech) verwendet werden. Jeder in der Bank abgelegte Verteidigungsgegenstand steuert zusätzliche Verteidigungspunkte bei!', "temporary" => 0,"img" => "item_meca_parts","vp" => 0,"ap" => 50, "hp" => 50,"bp" => 0,"rsc" => ["meca_parts_#00" => 4,"wood_beam_#00" => 8,"metal_beam_#00" => 8,], "orderby" => 0,
                    "lv0text" => 'Jeder in der Bank abgelegte Verteidigungsgegenstand bringt der Stadt 1.5 Verteidigungspunkte zusätzlich ein.',
                    "upgradeTexts" => [
                        'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                        'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                        'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
                        'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 250%.',
                        'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 300%.',
                    ]],
                ["name" => "Kanonenhügel",'desc' => 'Mehrere Erdhügel, die durch Holzbalken verstärkt wurden, bilden die Grundlage für diesen mächtigen Verteidigungsturm.', "temporary" => 0,"img" => "small_dig","vp" => 30,"ap" => 50, "hp" => 50,"bp" => 0,"rsc" => ["concrete_wall_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 1,], "orderby" => 1, "children" => [
                    ["name" => "Steinkanone",'desc' => 'Dieser automatisierte Wachturm verschießt um Mitternacht minutenlang Felsen mit hoher Geschwindigkeit in Richtung Stadttor. Solltest du vorgehabt haben zu schlafen, kannst du das hiermit vergessen!', "temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 60, "hp" => 60,"bp" => 1,"rsc" => ["tube_#00" => 1,"electro_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 0],
                    ["name" => "Selbstgebaute Railgun",'desc' => 'Diese improvisierte Railgun funktioniert mit Luftdruck. Sie ist in der Lage, mehrere Ladungen Metallsplitter (verbogene Nägel und rostiges Metall) mit enormer Geschwindigkeit zu verschießen und faustgroße Löcher zu reißen.', "temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"tube_#00" => 1,"electro_#00" => 1,"metal_beam_#00" => 10,], "orderby" => 1],
                    ["name" => "Blechplattenwerfer",'desc' => 'Der Blechplattenwerfer schleudert schwere Blechplatten aufs Schlachtfeld. Die angerichtete Schweinerei willst du garantiert kein zweites Mal sehen...', "temporary" => 0,"img" => "small_canon","vp" => 60,"ap" => 50, "hp" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"plate_#00" => 3,"explo_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 1,], "orderby" => 2],
                    ["name" => "Brutale Kanone",'desc' => 'Für diese Maschine möchte man sterben. ...im übertragenen Sinne. Ihr müsst sie allerdings jeden Tag nachladen.', "temporary" => 1,"img" => "small_canon","vp" => 50,"ap" => 25, "hp" => 0,"bp" => 0,"rsc" => ["plate_#00" => 1,"metal_beam_#00" => 1,], "orderby" => 3],
                ]],
                ["name" => "Holzbalkendrehkreuz",'desc' => 'Schwere Holzbalken werden auf einem Drehkreuz befestigt und das Ganze wird dann in Bewegung gesetzt. Es dreht sich schnell... sehr schnell.', "temporary" => 0,"img" => "item_wood_beam","vp" => 10,"ap" => 15, "hp" => 15,"bp" => 0,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 1,], "orderby" => 2],
                ["name" => "Manufaktur",'desc' => 'Die Manufaktur ist eine verbesserte Werkstatt. Sie senkt die Verarbeitungskosten aller darin erledigten Arbeiten um 1 AP.', "temporary" => 0,"img" => "small_factory","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,], "orderby" => 3],
                ["name" => "Kreischende Sägen",'desc' => 'Ein paar geschickt hergestelle Kreissägen, die durch ein ausgeklügeltes Gummizugsystem bewegt werden. Der schrille Rotationslärm erinnert seltsamerweise an einen menschlichen Schrei...', "temporary" => 0,"img" => "small_saw","vp" => 45,"ap" => 65, "hp" => 65,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"rustine_#00" => 3,"metal_beam_#00" => 2,], "orderby" => 4],
                ["name" => "Baustellenbuch",'desc' => 'Mit diesem Register erhältst du eine bessere Übersicht zu allen aktuellen Konstruktionen samt der dafür benötigten Materialien.', "temporary" => 0,"img" => "item_rp_book2","vp" => 0,"ap" => 15, "hp" => 0,"bp" => 0,"rsc" => ["table_#00" => 1,], "orderby" => 5, "impervious" => true, "children" => [
                    ["name" => "Bauhaus","maxLevel" => 3,'desc' => 'Wenn sich intelligente Leute abends beim Feuer unterhalten, können großartige Erfindungen dabei herauskommen. Zumindest wenn sie vorher ein Bauhaus errichtet haben. Dieses Bauwerk gibt der Stadt täglich einen (gewöhnlichen) Bauplan.', "temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 75, "hp" => 0,"bp" => 0,"rsc" => ["drug_#00" => 1,"vodka_#00" => 1,"wood_beam_#00" => 10,],
                        "lv0text" => "Die Stadt erhält nach dem nächsten Angriff einmalig 1 gewöhnliche Baupläne.",
                        "upgradeTexts" => [
                            'Die Stadt erhält nach dem nächsten Angriff einmalig 4 gewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                            'Die Stadt erhält nach dem nächsten Angriff einmalig 2 gewöhnliche und 2 ungewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                            'Die Stadt erhält nach dem nächsten Angriff einmalig 2 ungewöhnliche und 2 seltene Baupläne sowie - möglicherweise - eine nette Überraschung.',
                        ], "orderby" => 0],
                ]],
                ["name" => "Galgen",'desc' => 'An diesem prächtigen Galgen könnt ihr unliebsame (oder lästige) Mitbürger loswerden. Ist mal was "anderes" als die klassische Verbannung...', "temporary" => 0,"img" => "r_dhang","vp" => 0,"ap" => 13, "hp" => 0,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"chain_#00" => 1,], "orderby" => 6],
                ["name" => "Schokoladenkreuz",'desc' => 'Ein wunderschönes Kreuz aus Schokolade, an dem unliebsame (oder lästige) Mitbürger platziert werden können. Ist mal was "anderes" als die klassische Verbannung...', "temporary" => 0,"img" => "small_eastercross","vp" => 0,"ap" => 13, "hp" => 0,"bp" => 5,"rsc" => ["wood_beam_#00" => 1,"chain_#00" => 1,], "orderby" => 6],
                ["name" => "Schlachthof",'desc' => 'Ein Schlachthof, der direkt vor dem Stadttor errichtet wird und dessen Eingang zur Außenwelt zeigt. Schwierig ist eigentlich nur, jede Nacht einen Freiwilligen zu finden, der sich hineinstellt und so die Zombies anlockt.', "temporary" => 0,"img" => "small_slaughterhouse","vp" => 35,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["concrete_wall_#00" => 2,"metal_beam_#00" => 10,], "orderby" => 7],
                ["name" => "Pentagon",'maxLevel' => 2, 'desc' => 'Eine großangelegte Neuausrichtung aller Verteidigungsanlagen, um wirklich das Optimum herauszuholen (die Gesamtverteidigung der Stadt erhöht sich um 10%).', "temporary" => 0,"img" => "item_shield","vp" => 8,"ap" => 55, "hp" => 55,"bp" => 3,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 10,], "orderby" => 8,
                    "lv0text" => 'Die Verteidigung der Stadt wird um 10% erhöht.',
                    "upgradeTexts" => [
                        'Die Verteidigung der Stadt wird um 12% erhöht.',
                        'Die Verteidigung der Stadt wird um 14% erhöht.'
                    ]],
                ["name" => "Kleines Cafe",'desc' => 'Das Mittagessen liegt schon lange zurück... Was gibt\'s da besseres als eine solide Holzplanke und altbackenes Brot.', "temporary" => 1,"img" => "small_cafet","vp" => 0,"ap" => 6, "hp" => 0,"bp" => 0,"rsc" => ["water_#00" => 1,"wood2_#00" => 2,"pharma_#00" => 1,], "orderby" => 9],
                ["name" => "Kleiner Friedhof",'desc' => 'Bringt eure Toten! Denn diesmal werden sie sich noch als nützlich erweisen. Macht das beste aus ihnen und verbessert damit gemeinsam eure Verteidigung. Jeder zum Friedhof gebrachte tote Mitbürger bringt +10 Verteidigungspunkte für die Gesamtverteidigung der Stadt. Hinweis: Es spielt keine Rolle, wo und woran ein Mitbürger verstarb.', "temporary" => 0,"img" => "small_cemetery","vp" => 0,"ap" => 36, "hp" => 36,"bp" => 1,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 10,], "orderby" => 10, "children" => [
                    ["name" => "Sarg-Katapult",'desc' => 'Von 2 Toten hat derjenige, der sich bewegt, die besten Chancen, dich zu verspeisen. Trickst eure Feinde aus, indem ihr eure Leichen in die herankommende Zombiehorde schleudert. Jeder Tote bringt +20 anstelle von +10 Verteidigungspunkten.', "temporary" => 0,"img" => "small_coffin","vp" => 0,"ap" => 100, "hp" => 100,"bp" => 4,"rsc" => ["courroie_#00" => 1,"meca_parts_#00" => 5,"wood2_#00" => 5,"metal_#00" => 15,], "orderby" => 0],
                ]],
                ["name" => "Kantine",'desc' => 'Die Kantine verbessert die Produktion in den Küchen, die die Helden in eurer Stadt gebaut haben.', "temporary" => 0,"img" => "small_cafet","vp" => 0,"ap" => 20, "hp" => 20,"bp" => 1,"rsc" => ["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,], "orderby" => 11],
                ["name" => "Labor",'desc' => 'Das Labor verbessert die Produktion in den Hobbylabors, die die Helden in eurer Stadt gebaut haben.', "temporary" => 0,"img" => "item_acid","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"pharma_#00" => 5,"wood_beam_#00" => 3,"metal_beam_#00" => 10,], "orderby" => 12],
                ["name" => "Hühnerstall",'desc' => 'Falls du schon vor langer Zeit vergessen hast, wie köstlich ein Omelett mit gebratenen Chamignons, Kräutern und Speck ist, dürfte dieses Gebäude wohl eher unnütz für dich sein. Aber zumindest liefert es dir einige Eier. Was die Pilze betrifft, so musst du dich wohl an die Zombies draußen halten.', "temporary" => 0,"img" => "small_chicken","vp" => 0,"ap" => 25, "hp" => 25,"bp" => 3,"rsc" => ["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,], "orderby" => 13],
                ["name" => "Krankenstation",'desc' => 'Egal ob kleines Wehwehchen oder irreparables Trauma - die Krankenstation empfängt dich mit offenen Armen. Zumindst solange du noch imstande bist, dich selbst zu verarzten, denn diese Einrichtung kommt ganz ohne medizinisches Personal daher.', "temporary" => 0,"img" => "small_infirmary","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 3,"rsc" => ["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 14],
                ["name" => "Bollwerk",'desc' => 'Dieses ambitionierte Stadtbauprojekt hat zum Ziel, die Verteidigung der Bürgerbehausungen besser in die Stadtverteidigung zu integrieren. Dank dieses Bauwerks bringen Bürgerbehausungen fortan 80% statt 40% ihres Verteidigungswertes in die Stadtverteidigung ein.', "temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 60, "hp" => 60,"bp" => 3,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 16],
                ["name" => "Baumarkt",'desc' => 'Dank diesem wahrlich großen Bauwerk können Bürger ihre Häuser noch aufwändiger ausbauen.', "temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 4,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 10,], "orderby" => 15],
            ]],

            ["name" => "Wachturm","maxLevel" => 5, 'desc' => 'Mit dem Wachturm ist es möglich den nächtlichen Angriff abzuschätzen. Die Stadtbürger können dann die entsprechenden Gegenmaßnahmen vorbereiten (oder auch nicht...). Nach seiner Fertigstellung können Notfallkonstruktionen aller Art gebaut werden.', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 12, "hp" => 12,"bp" => 0,"rsc" => ["wood2_#00" => 3,"metal_#00" => 2,], "orderby" => 4,
                "upgradeTexts" => [
                    'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 3km um die Stadt aufhalten.',
                    'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 6km um die Stadt aufhalten.',
                    'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten.',
                    'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 1km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                    'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 2km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                ], "children" => [
                ["name" => "Katapult",'desc' => 'Das Katapult ist ein äußerst mächtiges Werkzeug, mit dem die Stadt jede Art von Gegenstand in die Wüste schießen kann. Das ist sehr nützlich, wenn man weit entfernte Bürger versorgen möchte (Lebensmittel, Wasser, Waffen etc...).', "temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,], "orderby" => 0, "children" => [
                    ["name" => "Verbesserter Katapult",'desc' => 'Dieses erheblich verbesserte Katapult ist einfacher zu bedienen und benötigt weniger AP, um mit einem Gegenstand beladen zu werden!', "temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 2,], "orderby" => 0],
                ]],
                ["name" => "Scanner",'desc' => 'Dieser selbstgebaute Zonenscanner erleichtert die Abschätzung des nächtlichen Angriffs erheblich. Wenn er richtig eingesetzt wird, sind nur halb so viele Bürger notwendig, um eine gute Schätzung zu bekommen.', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20, "hp" => 20,"bp" => 2,"rsc" => ["pile_#00" => 1,"meca_parts_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,], "orderby" => 1],
                ["name" => "Verbesserte Karte",'desc' => 'Diese simple elektronische Konstruktion erleichtert das Lesen der Außenweltkarte. Konkret: Du erfährst die genaue Zombieanzahl jeder Zone und musst somit nicht mehr planlos in der Wüste rumlaufen...', "temporary" => 0,"img" => "item_electro","vp" => 0,"ap" => 15, "hp" => 15,"bp" => 1,"rsc" => ["pile_#00" => 2,"metal_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,], "orderby" => 2],
                ["name" => "Rechenmaschine",'desc' => 'Die Rechenmaschine ist ein etwas rustikaler Taschenrechner, mit dem man die Angriffsstärke des MORGIGEN Tages berechnen kann!', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20, "hp" => 20,"bp" => 1,"rsc" => ["rustine_#00" => 1,"electro_#00" => 1,], "orderby" => 3],
                ["name" => "Forschungsturm","maxLevel" => 5,'desc' => 'Mit dem Forschungsturm können in bereits "abgesuchten" Wüstenzonen jeden Tag neue Gegenstände gefunden werden! Der Forschungsturm versetzt dich in die Lage, jene anormalen meteorologischen Phänomene aufzuzeichnen und auszuwerten, die sich nachts in der Wüste abspielen. Die entsprechenden Fundstellen werden anschließend in der Zeitung veröffentlicht.', "temporary" => 0,"img" => "small_gather","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 1,"rsc" => ["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,], "orderby" => 4,
                    "upgradeTexts" => [
                        'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 37%.',
                        'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 49%.',
                        'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 61%.',
                        'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 73%.',
                        'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 85%.',
                    ]],
                ["name" => "Notfallkonstruktion",'desc' => 'Die Stadt muss sich auch auf unvorhergesehene Ereignisse einstellen. Für diese Zwecke wurde diese Notfallkonstruktion entworfen: Sie hält in der Regel nur eine Nacht lang. Achtung! Es sollte darauf geachtet werden, nicht zu viele Rohstoffe für den Bau temporärer Konstruktionen zu verwenden!', "temporary" => 0,"img" => "status_terror","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 7,], "orderby" => 5, "children" => [
                    ["name" => "Notfallabstützung",'desc' => 'Wenn es brenzlig wird, ist es oft überlebenswichtig, hier und dort mit ein paar Holzplanken nachzuhelfen und alles nochmal dicht zu machen. Das bringt dir oft noch eine weitere Nacht.', "temporary" => 1,"img" => "item_wood_plate","vp" => 40,"ap" => 30, "hp" => 0,"bp" => 0,"rsc" => ["wood2_#00" => 8,], "orderby" => 0],
                    ["name" => "Verteidigungspfähle",'desc' => 'Der gesamte Außenbereich der Stadt wird mit angespitzten Holzpfählen gespickt. Diese kostengünstige Verteidigungsmaßnahme verwandelt die Stadt in eine wahre Trutzburg und kann an manchen Abenden den Unterschied zwischen Leben und Tod ausmachen.', "temporary" => 1,"img" => "small_trap","vp" => 25,"ap" => 12, "hp" => 0,"bp" => 0,"rsc" => ["wood2_#00" => 6,], "orderby" => 1],
                    ["name" => "Guerilla",'desc' => 'Dieses Arsenal an einfallsreichen Guerillafallen ermöglicht dir, die Zombiereihen zu lichten und die Last des Angriffs entscheidend zu senken.', "temporary" => 1,"img" => "small_trap","vp" => 50,"ap" => 24, "hp" => 0,"bp" => 2,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 2,"metal_#00" => 1,], "orderby" => 2],
                    ["name" => "Abfallberg",'desc' => 'Wenn wirklich gar nichts mehr geht, sammelst du alles ein, was du findest und formst daraus einen großen Abfallhaufen... jetzt heißt es Daumen drücken und hoffen, dass das die Horde irgendwie aufhält... Ach ja, wenn du möchtest, kannst du diesen Abfallberg auch mit Fallen spicken.', "temporary" => 1,"img" => "small_dig","vp" => 5,"ap" => 10, "hp" => 0,"bp" => 0,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,], "orderby" => 3, "children" => [
                        ["name" => "Trümmerberg",'desc' => 'Hast du erst mal einen großen Haufen Müll aufgeschüttet, kannst du ihn einfach noch mit Stacheln versehen, die ebenso rostig wie tödlich sind!', "temporary" => 1,"img" => "small_dig","vp" => 60,"ap" => 40, "hp" => 0,"bp" => 1,"rsc" => ["metal_#00" => 2,], "orderby" => 0],
                    ]],
                    ["name" => "Wolfsfalle",'desc' => 'Das wird den Zombies Beine machen - oder besser: ausreißen!', "temporary" => 1,"img" => "small_trap","vp" => 40,"ap" => 20, "hp" => 0,"bp" => 0,"rsc" => ["metal_#00" => 2,"hmeat_#00" => 3,], "orderby" => 4],
                    ["name" => "Sprengfalle",'desc' => 'Dynamit, Zombies, Blut.', "temporary" => 1,"img" => "small_tnt","vp" => 35,"ap" => 20, "hp" => 0,"bp" => 0,"rsc" => ["explo_#00" => 3,], "orderby" => 5],
                    ["name" => "Nackte Panik",'desc' => 'Falls die Lage wirklich verzweifelt ist, könnt ihr beschließen loszuschreien und in Panik zu verfallen. Falls alle Überlebenden mitmachen, wird es die Zombies verwirren (denn sie können mit dieser Art Stress nicht umgehen) und euch einige virtuelle Verteidigungespuntke einbringen... Genau, das ist natürlich Unsinn.', "temporary" => 1,"img" => "status_terror","vp" => 50,"ap" => 25, "hp" => 25,"bp" => 0,"rsc" => ["water_#00" => 4,"wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 6],
                    ["name" => "Dollhouse",'desc' => 'Feiern bis zum Abwinken ist immer noch die beste Art, all die schrecklichen Dinge der Außenwelt zu vergessen. Glücklicherweise sorgen die Zombies schon dafür, dass die Dinge nicht zu sehr ausschweifen.', "temporary" => 1,"img" => "small_bamba","vp" => 75,"ap" => 50, "hp" => 0,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,"radio_on_#00" => 3,], "orderby" => 7],
                    ["name" => "Voodoo-Puppe",'desc' => 'Ein über 2 Meter hoher, schimmliger Wollballen, über und über mit Stricken und Nadeln bedeckt. In den mächtigen Händen des Schamanen wird dieses *Ding* zu einem XXL-Püppchen, das etliche Zombies niederstreckt, ehe es wieder eine unförmige, unbewegliche Masse wird.', "temporary" => 0,"img" => "small_vaudoudoll","vp" => 65,"ap" => 40, "hp" => 40,"bp" => 0,"rsc" => ["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_yellow_#00" => 2,], "orderby" => 8],
                    ["name" => "Bokors Guillotine",'desc' => 'Mit Hilfe einer teuflischen Guillotine und einer provisorischen Schaufensterpuppe, kann der Schamane aus der Entfernung den Kopf eines Zombierudel-Führers abschlagen. Die Zombies die ihm folgen, werden daraufhin abdrehen und wieder in die Wüste wandern.', "temporary" => 0,"img" => "small_bokorsword","vp" => 100,"ap" => 60, "hp" => 60,"bp" => 0,"rsc" => ["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_yellow_#00" => 3,], "orderby" => 9],
                    ["name" => "Spirituelles Wunder",'desc' => 'Dieser Zauber des Schamanen erschafft ein Trugbild der Stadt. Als Folge verliert sich eine beträchtliche Anzahl Zombies heillos in der Wüste.', "temporary" => 0,"img" => "small_spiritmirage","vp" => 80,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_yellow_#00" => 2,], "orderby" => 10],
                    ["name" => "Heiliger Regen",'desc' => 'Nur der Schamane kennt das Geheimnis dieses rituellen Feuertanzes. Richtig ausgeführt, steigt eine kleine Wolke in den Himmel und bewirkt, dass ein Schauer heiligen Wassers auf die Zombiehorde niedergeht.', "temporary" => 1,"img" => "small_holyrain","vp" => 200,"ap" => 40, "hp" => 0,"bp" => 0,"rsc" => ["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_yellow_#00" => 4,], "orderby" => 11],
                ]],
                ["name" => "Wächter-Turm",'desc' => 'Der Bau eines prächtigen Wachturms mit Kontrollgang macht die Wächter unter den Helden glücklich. Dieses Bauwerk gibt pro Wächter in der Stadt +5 Verteidigungspunkte und erlaubt ihnen, ihre AP in Verteidigungspunkte umzuwandeln und so noch mehr für die Verteidung zu tun.', "temporary" => 0,"img" => "small_watchmen","vp" => 15,"ap" => 24, "hp" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,], "orderby" => 6, "children" => [
                    ["name" => "Schießstand",'desc' => 'Ein Schießstand mit Wasserpistolen, der wohl aus den Überresten eines Rummels geborgen wurde. Er zaubert eurer Nachtwache ein Lächeln ins Gesicht - damit schickt ihr sicher einen netten Schauer die Mauer runter! Verleiht jeder Wasserwaffe, die auf der Wacht genutzt wird, einen 20% Bonus.', "temporary" => 0,"img" => "small_tourello","vp" => 50,"ap" => 25, "hp" => 25,"bp" => 2,"rsc" => ["water_#00" => 30,"tube_#00" => 2,"wood_beam_#00" => 1,"metal_beam_#00" => 2,], "orderby" => 0],
                    ["name" => "Kleiner Tribok",'desc' => 'Mit diesem Katapult kannst du deinen tierischen Freunden ein wenig Starthilfe geben, damit sie noch mehr Schaden in der angreifenden Horde anrichten. Zudem besitzt die Geräuschkulisse von Tieren, die mit hoher Geschwindigkeit durch die Luft fliegen, einen gewissen Unterhaltungswert. 20% auf die Angriffskraft jedes Tieres, das mit auf die Wacht genommen wird.', "temporary" => 0,"img" => "small_catapult3","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 4,"meca_parts_#00" => 2,"plate_#00" => 2,"tube_#00" => 1,], "orderby" => 1],
                    ["name" => "Kleine Waffenschmiede",'desc' => 'Diese Waffenwerkstatt ist euer Trumpf für die Nachtwache. Wird sie gebaut, wird jede Waffe, die während der Wacht zum Einsatz kommt um 20% verstärkt.', "temporary" => 0,"img" => "small_armor","vp" => 0,"ap" => 50, "hp" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 15,"plate_#00" => 2,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,], "orderby" => 2],
                    ["name" => "Schwedische Schreinerei",'desc' => 'Dieser kleine Laden verbessert die Effektivität jedes Möbelstücks, das auf der Wache benutzt wird um 20%. Hach ja, die Schweden... Nie gab es bessere Billigmöbel!', "temporary" => 0,"img" => "small_ikea","vp" => 0,"ap" => 50, "hp" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 15,"metal_#00" => 10,"plate_#00" => 4,"concrete_wall_#00" => 2,"wood_beam_#00" => 5,], "orderby" => 3],
                ]],
                ["name" => "Krähennest",'desc' => 'Weniger ein Turm als ein seeeeehr hoher Mast, der fast bis in die Wolken reicht. Aufklärer können ihn erklimmen und so Gebäude in der Außenwelt erspähen (1x pro Tag und Held).', "temporary" => 0,"img" => "small_watchmen","vp" => 10,"ap" => 36, "hp" => 36,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,], "orderby" => 7],
                ["name" => "Straßenbeleuchtung","maxLevel" => 2,'desc' => 'Selbst in der tiefsten Nacht erlaubt dir der fahle Schein der Laternenmasten, deine Ausgrabungen in der Wüste fortzusetzen. Keine Ausreden mehr, um früh ins Bett zu gehen.', "temporary" => 0,"img" => "small_novlamps","vp" => 0,"ap" => 25, "hp" => 25,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"lens_#00" => 2,"diode_#00" => 2,"metal_beam_#00" => 10,"wire_#00" => 1, "pile_#00" => 5], "orderby" => 16,
                    "lv0text" => 'Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 2km um die Stadt negiert.',
                    "upgradeTexts" => [
                        'Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 6km um die Stadt negiert, pro Tag wird 1 Batterie verbraucht.',
                        'Die Verringerung der Fundchancen bei Nacht wird auf der gesamten Karte negiert, pro Tag werden 2 Batterien verbraucht.',
                    ]],
            ]],

            ["name" => "Fundament",'desc' => 'Das Fundament ist die Grundvoraussetzung für "Absurde Projekte" (das sind langwierige und anstrengende Bauten, die jedoch für die Stadt mehr als nützlich sind).', "temporary" => 0,"img" => "small_building","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 5, "children" => [
                ["name" => "Großer Umbau",'desc' => 'Dieses absurde Projekt hat den kompletten Umbau der Stadt zum Ziel. Jeder Stein, jedes Brett und jedes Gebäude wird gemäß eines neuen Verteidigungsplans neu ausgerichtet. Hierzu werden u.a. Häuser näher zusammengerückt, Gassen und selten benutzte Straßen versperrt und Wassergeschütztürme auf die Dächer der Stadt montiert. Ein richtig großer Umbau!', "temporary" => 0,"img" => "small_moving","vp" => 300,"ap" => 300, "hp" => 300,"bp" => 3,"rsc" => ["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,], "orderby" => 0],
                ["name" => "Bohrturm",'desc' => 'Auch der Bohrturm ist eine absurde Konstruktion. Mit ihm können selbst tiefste wasserführende Schichten angezapft werden! Er fügt +50 Rationen an Wasser dem Brunnen hinzu.', "temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 70, "hp" => 0,"bp" => 3,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 15,], "orderby" => 1],
                ["name" => "Falsche Stadt",'desc' => 'Es ist weithin bekannt, dass die Zombies nicht so ganz helle sind... Wenn ihr es schafft, eine Stadt nachzubauen, könntet ihr den überwiegenden Großteil des Angriffs auf diesen Nachbau umlenken...', "temporary" => 0,"img" => "small_falsecity","vp" => 400,"ap" => 400, "hp" => 400,"bp" => 3,"rsc" => ["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,], "orderby" => 2],
                ["name" => "Wasserhahn",'desc' => 'Dank dieses kleinen, am Brunnen angebrachten Wasserhahns, kannst Du nun die Wassermengen abschöpfen, die ansonten durch das Filtersystem verschwendet werden (es braucht kein zusätzliches Brunnen-Wasser). Du kannst mit diesem Wasser alle auf Wasser basierenden Waffen KOSTENLOS auffüllen (Wasserbombe, Wasserkanone,...)!', "temporary" => 0,"img" => "small_valve","vp" => 0,"ap" => 130, "hp" => 130,"bp" => 3,"rsc" => ["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,], "orderby" => 3],
                ["name" => "Vogelscheuche",'desc' => 'Dieses Feld voller Vogelscheuchen würde noch viel besser mit der Kleidung der Stadtbewohner funktionieren. Allerdings würde das bedeuten, dass sich die Stadt in ein Nudisten-Camp verwandeln würde.', "temporary" => 0,"img" => "small_scarecrow","vp" => 25,"ap" => 35, "hp" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 10,"rustine_#00" => 2,], "orderby" => 4],
                ["name" => "Müllhalde",'desc' => 'Der Eckpfeiler einer jeden großen Stadt: eine riesige, stinkende Müllhalde, die die ganze Stadt umgibt. Zugegeben, das ist nicht gerade ästhetisch, aber immerhin könnt ihr so Alltagsgegenstände in eine effektive Verteidigung verwandeln (nur eine Nacht haltbar).', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 70, "hp" => 70,"bp" => 0,"rsc" => ["concrete_wall_#00" => 5,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 5, "children" => [
                    ["name" => "Holzabfall",'desc' => 'Ermöglicht die fachgerechte Entsorgung von Holz auf der Müllhalde.', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 4],
                    ["name" => "Metallabfall",'desc' => 'Ermöglicht die fachgerechte Entsorgung von Metall auf der Müllhalde.', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 5],
                    ["name" => "Waffenabfall",'desc' => 'Ermöglicht die fachgerechte Entsorgung von Waffen auf der Müllhalde.', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20, "hp" => 20,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,], "orderby" => 2],
                    ["name" => "Biomüll",'desc' => 'Ermöglicht die fachgerechte Entsorgung von Nahrung auf der Müllhalde.', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20, "hp" => 20,"bp" => 2,"rsc" => ["wood2_#00" => 15,], "orderby" => 3],
                    ["name" => "Rüstungsabfall",'desc' => 'Steigert die Ausbeute jedes auf den Müll geworfenen Verteidigungs-Gegenstandes.', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 40, "hp" => 40,"bp" => 2,"rsc" => ["metal_beam_#00" => 3,"metal_#00" => 5,], "orderby" => 1],
                    ["name" => "Verbesserte Müllhalde",'desc' => 'Steigert die Ausbeute jedes auf den Müll geworfenen Gegenstandes um +1 Verteidigungspunkt.', "temporary" => 0,"img" => "small_trash","vp" => 75,"ap" => 120, "hp" => 120,"bp" => 4,"rsc" => ["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 0],
                    ["name" => "Tierabfälle",'desc' => 'Ermöglicht das fachgerechte Massakrieren unschuldiger Tiere auf der Müllhalde.', "temporary" => 0,"img" => "small_howlingbait","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 10,], "orderby" => 6],
                    ["name" => "Müll für Alle",'desc' => 'Senkt die Kosten der Nutzung der Müllhalde auf 0 AP. Achtung: Falls ihr Halunken in eurer Stadt habt, solltet ihr euch den Bau gut überlegen...', "temporary" => 0,"img" => "small_trashclean","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"trestle_#00" => 2,], "orderby" => 7],
                ]],
                ["name" => "Fleischkäfig",'desc' => 'Moderne Justiz in all seiner Pracht! Wird ein Mitbürger verbannt, kann er in den Fleischkäfig gesteckt werden, welcher sich wohlweislich direkt vor dem Stadttor befindet. Seine Schreie und Tränen dürften einen exzellenten Köder um Mitternacht abgeben. Jeder verbannte und in den Fleischkäfig gesteckte Mitbürger bringt der Stadt vorübergehende Verteidigungspuntke ein.', "temporary" => 0,"img" => "small_fleshcage","vp" => 0,"ap" => 40, "hp" => 0,"bp" => 0,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"wood_beam_#00" => 1,], "orderby" => 6],
                ["name" => "Leuchtturm",'desc' => 'Dieser schöne, hohe Leuchtturm wird Licht in lange Winternächte bringen (hat er im Sommer eigentlich irgendeinen Nutzen?). Alle Stadtbewohner auf Camping-Ausflug haben eine höhere Überlebens-Chance.', "temporary" => 0,"img" => "small_lighthouse","vp" => 0,"ap" => 30, "hp" => 30,"bp" => 3,"rsc" => ["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 7],
                ["name" => "Befestigungen",'desc' => 'Da Bürger normalerweise nicht direkt von Verteidigungsbauten profitieren, wollen wir uns mal nicht beschweren. Alle Bürgerbehausungen erhalten +4 Verteidigung.', "temporary" => 0,"img" => "small_city_up","vp" => 0,"ap" => 50, "hp" => 0,"bp" => 3,"rsc" => ["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 8],
                ["name" => "Leuchtfeuer",'desc' => 'Ein großes Leuchtfeuer, irgendwo weit abseits von der Stadt entzündet, soll die Zombies von unseren Häusern weglocken.', "temporary" => 1,"img" => "small_score","vp" => 30,"ap" => 15, "hp" => 15,"bp" => 2,"rsc" => ["lights_#00" => 1,"wood2_#00" => 5,], "orderby" => 9],
                ["name" => "Bürgergericht",'desc' => 'Wie jeder weiß, haben Helden immer recht. Um diesen Fakt weiter zu zementieren, zählen alle von Helden gegen andere Bürger ausgesprochenen Beschwerden doppelt.', "temporary" => 0,"img" => "small_court","vp" => 0,"ap" => 12, "hp" => 12,"bp" => 2,"rsc" => ["wood2_#00" => 6,"metal_beam_#00" => 15,"table_#00" => 1,], "orderby" => 10],
                ["name" => "Ministerium für Sklaverei",'desc' => 'Das Ministerium für Sklaverei hat beschlossen, dass Verbannte auf den Baustellen arbeiten dürfen. Außerdem erhält jeder von ihnen in ein Bauprojekt investierte AP einen 50%-Bonus (z.B. aus 6 AP werden so 9 AP, die in das Bauprojekt fließen).', "temporary" => 0,"img" => "small_slave","vp" => 0,"ap" => 45, "hp" => 45,"bp" => 4,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,], "orderby" => 11],
                ["name" => "Tunnelratte",'desc' => 'Da selbst der Bohrer des Bohrturms nicht durch jede Schicht durchkommt, muss man hin und wieder kleine und mit Dynamit bestückte Tiere in die Tiefe schicken. Dieses Projekt fügt den städtischen Wasserreserven +150 Rationen hinzu.', "temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 170, "hp" => 0,"bp" => 4,"rsc" => ["concrete_wall_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 12],
                ["name" => "Kino",'desc' => 'Sie zeigen Dawn of the Dead... zum 636. Mal. Bisher war dir die überzeugende Darstellung eines Nebendarstellers noch nie so richtig aufgefallen. Es gibt tatsächlich noch etwas Neues zu entdecken. Und wer weiß? Mit ein bisschen Glück bringt dich der Film ja sogar zum Lachen.', "temporary" => 0,"img" => "small_cinema","vp" => 0,"ap" => 75, "hp" => 75,"bp" => 4,"rsc" => ["electro_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 5,"machine_1_#00" => 1,"machine_2_#00" => 1,], "orderby" => 13],
                ["name" => "Heißluftballon",'desc' => 'Ein großer, runder Ballon steigt hinauf in den Himmel. Aber nur solange, wie der "Freiwillige" in der Gondel braucht, um alles rund um die Stadt zu erfassen. Das Bauwerk ermöglicht es Dir, die gesamte Außenwelt zu entdecken.', "temporary" => 0,"img" => "small_balloon","vp" => 0,"ap" => 80, "hp" => 80,"bp" => 4,"rsc" => ["meca_parts_#00" => 6,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 14],
                ["name" => "Labyrinth",'desc' => 'Zombies sind bekanntermaßen einfach gestrickt. Warum ihnen dann nicht einfach ein kleines Labyrinth vor die Nase (das Stadttor) setzen und dabei zusehen, wie ihr Angriff an Schwung verliert. Das Ganze ist äußerst effektiv. Doch jeder Bürger, der die Stadt betreten will, muss dann 1 AP aufbringen.', "temporary" => 0,"img" => "small_labyrinth","vp" => 150,"ap" => 200, "hp" => 200,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,], "orderby" => 15],
                ["name" => "Alles oder nichts",'desc' => 'Nicht mehr als ein Akt der Verzweiflung! Alle Gegenstände in der Bank werden zerstört und bringen jeweils +1 vorübergehende Verteidigung.', "temporary" => 0,"img" => "small_lastchance","vp" => 55,"ap" => 150, "hp" => 0,"bp" => 3,"rsc" => ["meca_parts_#00" => 4,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "impervious" => true, "orderby" => 16],
                ["name" => "Luftschlag",'desc' => 'Vier feine Raketen werden gestartet und auf vier strategische Ziele rund um die Stadt (Norden, Süden, Osten, Westen) abgefeuert. Auf ihrem Weg töten sie jeden Zombie.', "temporary" => 1,"img" => "small_rocket","vp" => 0,"ap" => 50, "hp" => 0,"bp" => 3,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,], "orderby" => 17],
                ["name" => "Feuerwerk",'desc' => 'Es gibt nichts Besseres, um die Tristesse langer Wüstennächte zu vertreiben, als ein schönes, großes Feuerwerk. Diese spezielle Variante geht so: Man feuert die Raketen in die Bereiche rund um die Stadt ab und zündet sie dann um Punkt Mitternacht inmitten der Zombiehorden.', "temporary" => 0,"img" => "small_fireworks","vp" => 100,"ap" => 90, "hp" => 90,"bp" => 0,"rsc" => ["firework_powder_#00" => 1,"firework_tube_#00" => 1,"firework_box_#00" => 2], "orderby" => 18],
                ["name" => "Altar",'desc' => 'Weil der Rabe gut und gerecht ist, befreit dieser zu seinen Ehren errichtete Schrein alle Bürger, die aus der Stadt verbannt wurden.', "temporary" => 0,"img" => "small_redemption","vp" => 0,"ap" => 24, "hp" => 0,"bp" => 2,"rsc" => ["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,], "impervious" => true, "orderby" => 19],
                ["name" => "Riesiger KVF",'desc' => 'Ein wirklich riesiger KVF, auf dem die Namen aller Bürger der Stadt eingraviert sind, erhebt sich stolz in den Himmel... äh. Genau, ein KVF. Niemand weiß warum, aber jemand hat am Fuße des Bauwerks "Eigentum der tiefsinnigen Nacht" eingraviert. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.', "temporary" => 0,"img" => "small_pmvbig","vp" => 0,"ap" => 300, "hp" => 0,"bp" => 4,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 30,], "orderby" => 20],
                ["name" => "Krähenstatue",'desc' => 'Huldigt den Raben! Gelobt sei deine Milde und deine erhabene Austrahlung! Befreie uns vom Spam und vergib uns unsere Trollenbeiträge so wie auch wir vergeben anderen Trollen. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.', "temporary" => 0,"img" => "small_crow","vp" => 0,"ap" => 300, "hp" => 0,"bp" => 4,"rsc" => ["hmeat_#00" => 3,"wood_beam_#00" => 35,], "orderby" => 21],
                ["name" => "Riesenrad",'desc' => 'Es ist wirklich eine enorme und beeindruckente Konstruktion. Ihr habt eure kostbarsten Materialien an dieses verdammte Ding verschwendet, und denoch seid ihr irgendwie stolz darauf. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.', "temporary" => 0,"img" => "small_wheel","vp" => 0,"ap" => 300, "hp" => 0,"bp" => 4,"rsc" => ["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,], "orderby" => 22],
                ["name" => "Riesige Sandburg",'desc' => 'Wenn es eines gibt, woran hier wahrlich kein Mangel herrscht, dann ist es Sand. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.', "temporary" => 0,"img" => "small_castle","vp" => 0,"ap" => 300, "hp" => 0,"bp" => 4,"rsc" => ["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 23],
                ["name" => "Reaktor",'desc' => 'Dieses furchterregende Konstrukt stammt aus einem sowjetischen U-Boot und sendet gleißende Blitze knisternder Elektrizität rund um die Stadt aus. Einziger Haken an der Sache: Es muss jeden Tag repariert werden. Falls es zerstört wird, würde die Stadt mitsamt der gesamten Umgebung augenblicklich ausradiert werden (inklusive euch). Das Schild am Reaktor besagt: sowjetische Bauweise, hergestellt in « вшивый ».', "temporary" => 0,"img" => "small_arma","vp" => 500,"ap" => 100, "hp" => 250,"bp" => 4, "impervious" => true, "rsc" => ["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,], "orderby" => 24],
            ]],

            ["name" => "Portal",'desc' => 'Eine rustikal anmutende Konstruktion, mit der die Öffnung des Stadttors nach 23:40 erfolgreich verhindert werden kann (es dürfte äußerst selten vorkommen, dass das Tor danach nochmal geöffnet werden muss). Das Stadttor muss nichtsdestotrotz zusätzlich noch per Hand geschlossen werden.', "temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 16, "hp" => 16,"bp" => 0,"rsc" => ["metal_#00" => 2,], "orderby" => 6, "children" => [
                ["name" => "Kolbenschließmechanismus",'desc' => 'Dieser äußerst leistungsstarke Kolbenmotor schließt und verriegelt das Stadttor eine halbe Stunde vor Mitternacht. Nach der Schließung kann das Tor nicht mehr geöffnet werden.', "temporary" => 0,"img" => "small_door_closed","vp" => 30,"ap" => 24, "hp" => 24,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,], "orderby" => 0, "children" => [
                    ["name" => "Automatiktür",'desc' => 'Das Stadttor schließt sich selbsttätig um 23:59 anstatt 23:30.', "temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 10, "hp" => 10,"bp" => 1,"rsc" => [], "orderby" => 0, ],
                ]],
                ["name" => "Torpanzerung",'desc' => 'Ein paar improvisierte Panzerplatten werden direkt auf das Stadttor geschraubt und verbessern so die Widerstandskraft desselben.', "temporary" => 0,"img" => "item_plate","vp" => 20,"ap" => 35, "hp" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 3,], "orderby" => 1,],
                ["name" => "Ventilationssystem",'desc' => 'Dieser Geheimgang erlaubt es Helden, ein- und auszugehen, ohne das Stadttor zu benutzen!', "temporary" => 0,"img" => "small_ventilation","vp" => 20,"ap" => 24, "hp" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,], "orderby" => 2,],
            ]],

            ["name" => "Hammam",'desc' => 'Ein Ort der Entspannung und der Meditation, perfekt geeignet um eine Seele auf die Andere Seite zu geleiten.', "temporary" => 0,"img" => "small_spa4souls","vp" => 30,"ap" => 20, "hp" => 20,"bp" => 0,"rsc" => ["wood2_#00" => 2,"plate_#00" => 2,], "orderby" => 7],
        ]);*/
    }
}