<?php

namespace MyHordes\Fixtures\Service;

use MyHordes\Fixtures\DTO\Buildings\BuildingPrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class BuildingDataService implements FixtureProcessorInterface {

    public function process(array &$data, ?string $tag = null): void
    {
        // \["name"\s*=>\s*["'](.*?)["']\s*,\s*'desc'\s*=>\s*["'](.*?)["'],\s*"temporary"\s*=>\s*(\d+),"img"\s*=>\s*["'](.*?)["'],"vp"\s*=>\s*(\d+),"ap"\s*=>\s*(\d+),\s*"hp"\s*=>\s*(\d+),"bp"\s*=>\s*(\d+),"rsc"\s*=>\s*\[(.*?)\],\s*"orderby"\s*=>\s*(\d+)
        $container = new BuildingPrototypeDataContainer($data);

        $container->add()
            ->icon('small_wallimprove')->label('Verstärkte Stadtmauer')->description('Mauern um die Stadt zu errichten, darüber müsste man nachdenken! Dies ist der Anfang der Befestigungsanlagen.')
            ->isTemporary(0)->defense(30)->ap(25)->blueprintLevel(0)->resources(["wood2_#00" => 8,"metal_#00" => 4])->orderBy(0)->commit( $small_wallimprove );

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_gather')->label('Großer Graben')->description('Der Große Graben ist eine sehr wirkungsvolle Verteidigungsmaßnahme, die sich insbesondere auf lange Sicht auszahlt. Der Graben lässt sich mit allerhand Dingen füllen.')
            ->isTemporary(0)->defense(10)->ap(70)->health(70)->blueprintLevel(0)->orderBy(0)
            ->voteLevel(5)->baseVoteText('Der Verteidigungswert der Stadt steigt mit dem Großen Graben auf 10 Punkte.')
            ->upgradeTexts([
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 20.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 25.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 30.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 35.',
                               'Der Verteidigungsbonus des Grabens steigt dauerhaft um 40.',
                           ])
            ->commit( $small_gather );

        $container->add()->parentBuilding($small_gather)
            ->icon('small_waterhole')->label('Wassergraben')->description('Eine verbesserte Version des Großen Grabens. Muss mit Wasser gefüllt werden...')
            ->isTemporary(0)->defense(60)->ap(60)->health(60)->blueprintLevel(0)->resources(["water_#00" => 20,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rasierklingenmauer')->description('Die Rasierklingenmauer folgt einem ganz einfachen Prinzip: Man nehme allerlei Eisenstücke, schärfe und spitze sie an und verteile sie anschließend über die ganze Stadtmauer. Die Mauer verwandelt sich so in eine überdimensionale Zombiefeile.')
            ->isTemporary(0)->defense(50)->ap(40)->health(40)->blueprintLevel(1)->resources(["metal_#00" => 15,"meca_parts_#00" => 2,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_gather)
            ->icon('small_spears')->label('Pfahlgraben')->description('Ein guter Weg, um die Große Grube zu füllen, mit scharfen Pfählen, um zu sehen, wie sich die Zombies dort aufspießen.')
            ->isTemporary(0)->defense(45)->ap(35)->health(35)->blueprintLevel(0)->resources(["metal_#00" => 2,"wood_beam_#00" => 8,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_barbed')->label('Stacheldraht')->description('Bedecken wir die Mauern mit Stacheldraht, so dass ein paar kleine Stücke an der Passage hängen bleiben.')
            ->isTemporary(0)->defense(20)->ap(10)->health(10)->blueprintLevel(0)->resources(["metal_#00" => 1,"wire_#00" => 2,])->orderBy(6)->commit($small_barbed);

        $container->add()->parentBuilding($small_barbed)
            ->icon('small_meatbarbed')->label('Köder')->description('Mit diesem an einem Stacheldraht befestigtem Stück Fleisch kann man ein paar Zombies \'ne Zeit lang beschäftigen')
            ->isTemporary(1)->defense(30)->ap(10)->health(0)->blueprintLevel(0)->resources(["bone_meat_#00" => 2,])->orderBy(0)->commit();


        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_wallimprove')->label('Weiterentwickelte Stadtmauer')->description('Auf die Verteidigungsvorichtungen müssen wir heute Nacht verzichten, aber diese intelligent gebaute und ausbaufähige Stadtmauer hat mehr drauf, als man denkt.')
            ->isTemporary(0)->defense(20)->ap(40)->health(40)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(3)->commit($small_wallimprove1);
        $container->add()->parentBuilding($small_wallimprove1)
           ->icon('item_plate')->label('Verstärkende Balken')->description('Eine verstärkte Struktur für die schwächeren Teile der Mauer.')
           ->isTemporary(0)->defense(35)->ap(15)->health(15)->blueprintLevel(1)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 3,])->orderBy(3)->commit($item_plate);

        $container->add()->parentBuilding($small_wallimprove1)
            ->icon('item_plate')->label('Zackenmauer')->description('Diese Mauer ist mit einer großen Anzahl an Metallspitzen gespickt, damit die Stadtbewohner beim Angriff um Mitternacht ein paar nette Spieße Zombieschaschlik herstellen können.')
            ->isTemporary(0)->defense(45)->ap(35)->health(35)->blueprintLevel(1)->resources(["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,])->orderBy(4)->commit($item_plate_2);

        $container->add()->parentBuilding($small_wallimprove1)
            ->icon('item_plate')->label('Groooße Mauer')->description('Eine Mauer ist gut, eine große Mauer ist besser.')
            ->isTemporary(0)->defense(80)->ap(50)->health(50)->blueprintLevel(1)->resources(["wood2_#00" => 10, "metal_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 10,"metal_beam_#00" => 10,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_wallimprove1)
            ->icon('item_plate')->label('Zweite Schicht')->description('Damit selbst hartnäckige Zombies draußen bleiben, bekommt die gesamte Stadtmauer eine zusätzliche Schicht verpasst.')
            ->isTemporary(0)->defense(70)->ap(60)->health(60)->blueprintLevel(2)->resources(["wood2_#00" => 35,"metal_beam_#00" => 5,])->orderBy(0)->commit($item_plate_3);

        $container->add()->parentBuilding($item_plate_3)
            ->icon('item_plate')->label('Dritte Schicht')->description('Nach der zweiten Wand dachten wir, warum nicht eine dritte? Die Isolierung wird einfach besser sein!')
            ->isTemporary(0)->defense(100)->ap(60)->health(60)->blueprintLevel(3)->resources(["metal_#00" => 35,"plate_#00" => 3,"metal_beam_#00" => 5,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_wallimprove1)
            ->icon('item_home_def')->label('Entwicklungsfähige Stadtmauer')->description('Die Stadtmauer wird mit einem Eisengestell verstärkt und kann ab sofort jeden Tag ganz leicht um ein Stück erweitert werden!')
            ->isTemporary(0)->defense(55)->ap(65)->health(65)->blueprintLevel(3)->resources(["wood2_#00" => 5,"metal_#00" => 15,"concrete_wall_#00" => 2])->orderBy(2)
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
            ->icon('small_wallimprove')->label('Betonschicht')->description('Mit den Betonblöcken, die wir gefunden haben, können wir die Sperrholzplatten verstärken, damit sie endlich für etwas nützlich sind.')
            ->isTemporary(0)->defense(80)->ap(40)->health(40)->blueprintLevel(2)->resources(["wood2_#00" => 5,"concrete_wall_#00" => 5,"metal_beam_#00" => 10,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_grater')->label('Zombiereibe')->description('Bedecken wir einen großen Teil der Mauer mit einer Vielzahl von scharfen Metallstücken, dann haben wir die größte Käsereibe der Welt. Man kann zusehen, wie die Zombies hineinrutschen. Das einzige Problem ist der Lärm.')
            ->isTemporary(0)->defense(55)->ap(40)->health(40)->blueprintLevel(1)->resources(["meca_parts_#00" => 3,"metal_beam_#00" => 5,"metal_#00" => 15,"plate_#00" => 1,"wire_#00" => 1,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_gather)
            ->icon('small_gather')->label('Fallgruben')->description('Mit tieferen Löchern und darüber ausgelegten Planen, einfach abwarten und zusehen, wie etwas (oder jemand?) hineinfällt!')
            ->isTemporary(0)->defense(35)->ap(25)->health(25)->blueprintLevel(0)->resources(["metal_beam_#00" => 1, "plate_#00" => 2,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_fence')->label('Zaun (Baustellen)')->description('Holzzäune, die vor der Mauer errichtet wurden, um die auf die Stadt zustürmenden Zombies zu verlangsamen (oder es zumindest zu versuchen).')
            ->isTemporary(0)->defense(40)->ap(60)->health(60)->blueprintLevel(0)->resources(["wood2_#00" => 15,"wood_beam_#00" => 5,])->orderBy(5)->commit($small_fence);
        $container->add()->parentBuilding($small_fence)
            ->icon('small_fence')->label('Holzzaun')->description('Verstärken wir die Barrieren und erhöhen sie ein wenig, um ihre Wirkung auf die Verteidigung der Stadt zu verbessern.')
            ->isTemporary(0)->defense(60)->ap(50)->health(50)->blueprintLevel(1)->resources(["wood2_#00" => 10,"wood_beam_#00" => 8,"metal_beam_#00" => 2,"plate_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_wallimprove')->label('Einseifer')->description('Warum ist das nicht schon früher jemandem eingefallen? Anstatt sich um die persönliche Hygiene zu kümmern, benutzen wir Seife, um die Wälle der Stadt rutschig zu machen.')
            ->isTemporary(0)->defense(60)->ap(35)->health(35)->blueprintLevel(0)->resources(["metal_#00" => 10, "water_#00" => 10,"tube_#00" => 1,"plate_#00" => 2,"pharma_#00" => 2,])->orderBy(4)->commit($small_wallimprove3);
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_waterspray')->label('Zerstäuber')->description('Ein handliches, hydraulisch betriebenes Gerät, das Wasserdampf versprühen kann (und weitere amüsante Chemikalien).')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"metal_#00" => 8,"tube_#00" => 2,"metal_beam_#00" => 2,"wire_#00" => 2,"deto_#00" => 1,])->orderBy(5)->commit($small_waterspray);

        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_acidspray')->label('Säurespray')->description('Das Hinzufügen einiger Chemikalien zum verwendeten Wasser wird das hübsche Gesicht der Zombies vor der Stadt definitiv nicht verschönern.')
            ->isTemporary(1)->defense(40)->ap(25)->blueprintLevel(1)->resources(["water_#00" => 3,"pharma_#00" => 2,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_gazspray')->label('Spraykanone')->description('Oft wird vergessen, dass Zombies ein Gehirn haben. Manchmal sogar zwei, wenn sie Glück haben. Trifft sich gut: Das mit dieser Kanone geschossene Konzentrat hat die erstaunliche Fähigkeit, Gehirne in Matsch zu verwandeln. Es könnte allerdings sein, dass sie auf eure Wächter herunterfällt... aber wo gehobelt wird, da fallen Späne.')
            ->isTemporary(false)->defense(140)->ap(60)->health(60)->blueprintLevel(1)->resources(["metal_beam_#00" => 5,"water_#00" => 5,"meca_parts_#00" => 1,"tube_#00" => 1,"pharma_#00" => 2,"poison_part_#00" => 1,])->orderBy(2)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten')->description('Ein simpler Verteidigungsgegenstand, aber du wirst ihn zu schätzen wissen, wenn dein Ex-Nachbar Kevo versuchen sollte, an deinem Gehirn rumzuknabbern..')
            ->isTemporary(0)->defense(30)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 10,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten 2.0')->description('Es ist nicht sehr fortschrittlich oder gut durchdacht, aber es erfüllt seinen Zweck ... es verzögert unseren Tod. Ein wenig.')
            ->isTemporary(0)->defense(30)->ap(30)->health(30)->blueprintLevel(0)->resources(["metal_#00" => 10,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Rüstungsplatten 3.0')->description('Simpel aber stabil: Was will man mehr?')
            ->isTemporary(0)->defense(45)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 8,"metal_#00" => 8,])->orderBy(10)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Sperrholz')->description('Sperrholz. Du hast es nur genommen, weil du wirklich nichts besseres zu tun hattest. Dir war klar, dass es unnütz sein würde, aber das hat dich trotzdem nicht davon abgehalten. Na dann mal los...')
            ->isTemporary(0)->defense(15)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(7)->commit($item_plate9);
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('item_plate')->label('Extramauer')->description('Schützt das Herz der Stadt mit einer zusätzlichen Mauer. Man muss keine helle Leuchte sein, um auf diese Idee zu kommen, aber es kann auch nicht schaden.')
            ->isTemporary(0)->defense(50)->ap(25)->health(25)->blueprintLevel(1)->resources(["wood2_#00" => 15,"metal_#00" => 15,])->orderBy(11)->commit();
        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_round_path')->label('Brustwehr')->description('Wenn die Bürger die Nacht auf der Spitze des Wachturms tanzen, sollte dies die Aufmerksamkeit einiger Zombies auf sich ziehen. Wir wünschen den Freiwilligen viel Glück.')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(0)->resources(["wood2_#00" => 6,"wood_beam_#00" => 2,"metal_beam_#00" => 2,"meca_parts_#00" => 1,])->orderBy(3)
            ->voteLevel(3)->baseVoteText('Diese Brustwehr um die Stadt erlaubt es einigen Wächtern, die Stadt von der Mauer aus zu schützen.')
            ->upgradeTexts([
                               'Indem man die Zinnen etwas erweitert, können mehr Wächter während des Angriffs kämpfen.',
                               'Der Brustwehr ist so breit, dass die ganze Stadt dort übernachten kann.',
                               'Durch den Ausbau der Brustwehr können sich die Wächter dort etwas sicherer fühlen.',
                           ])
            ->commit($small_round_path);

        $container->add()
            ->icon('small_water')->label('Pumpe')->description('Die Pumpe ermöglicht alle Konstruktionen, die mit Wasser zu tun haben. Indem man kräftig pumpt, ist es möglich, die Wassermenge im Brunnen leicht zu erhöhen.')
            ->isTemporary(0)->isImpervious(true)->defense(0)->ap(25)->blueprintLevel(0)->resources(["metal_#00" => 8,"tube_#00" => 1,])->orderBy(1)
            ->voteLevel(5)->baseVoteText('Der Brunnen wird einmalig mit 15 Rationen Wasser befüllt.')
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
            ->isTemporary(0)->defense(0)->ap(75)->health(75)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,"tube_#00" => 2,"oilcan_#00" => 2,])->orderBy(4)->commit($item_jerrycan);

        $container->add()->parentBuilding($item_jerrycan)
            ->icon('item_bgrenade')->label('Minen')->description('Raketenpulver, Zünder und reines Wasser: Das sind die Zutaten für einen saftigen Brei aus vermodertem Fleisch diese Nacht. Eine mächtige Verteidigung, leider kann sie nur einmal verwendet werden.')
            ->isTemporary(1)->defense(115)->ap(50)->health(0)->blueprintLevel(1)->resources(["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,])->orderBy(10)->commit();
        $container->add()->parentBuilding($item_jerrycan)
            ->icon('item_jerrycan')->label('Wasserfilter')->description('Verbessert die Ausbeute des Wasserreinigers erheblich (hoher Wirkungsgrad).')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(3)->resources(["metal_#00" => 10,"electro_#00" => 2,"wire_#00" => 1,"oilcan_#00" => 1,"fence_#00" => 1,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_vegetable_tasty')->label('Gemüsebeet')->description('Mit einem Gemüsebeet könnt ihr leckere Früchte und nicht allzu verschimmeltes Gemüse anbauen. Ist zwar kein Bio, macht aber satt.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(0)->resources(["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,])->orderBy(9)
            ->voteLevel(4)->baseVoteText('Produziert Verdächtiges Gemüse und Darmmelone.')
            ->upgradeTexts([
                               'Produziert täglich zusätzlich zu seiner ursprünglichen Produktion Stufe Mutterkorn und Trockene Kräuter.',
                               'Produziert täglich zusätzlich zu seiner Produktion aus den vorherigen Stufen Explosive Pampelmusen.',
                               'Produziert täglich zu seiner Produktion aus den vorherigen Stufen Apfel.',
                               'Produziert täglich zusätzlich zu seiner Produktion der vorherigen Stufen Toller Kürbis.',
                           ])
            ->commit($item_vegetable_tasty);

        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('item_bgrenade')->label('Granatapfel')->description('Ein gewaltiger wissenschaftlicher Durchbruch: Durch die Aussaat von Dynamitstangen und gaaanz vorsichtiges Gießen, könnt ihr Granatäpfel anbauen!')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(2)->resources(["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,'oilcan_#00' => 1])->orderBy(1)->commit($item_bgrenade);
        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('item_digger')->label('Dünger')->description('Erhöht den Ertrag des Gemüsegartens und aller umliegenden Gärten erheblich.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,'ryebag_#00' => 3])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('small_water')->label('Brunnenbohrer')->description('Mit diesem selbstgebauten Bohrer kann die Stadt ihre Wasserreserven beträchtlich vergrößern.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(55)->health(0)->blueprintLevel(0)->resources(["wood_beam_#00" => 7,"metal_beam_#00" => 2,])->orderBy(1)->commit($small_water_1);

        $container->add()->parentBuilding($small_water)
            ->icon('small_eden')->label('Projekt Eden')->description('Eine radikale Lösung, wenn mal das Wasser ausgehen sollte: Mit ein paar gezielten Sprengungen können tiefergelegene Wasserschichten erschlossen und das Wasserreservoir vergrößert werden.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(50)->health(0)->blueprintLevel(2)->resources(["wood2_#00" => 10,"explo_#00" => 2,"deto_#00" => 1,"metal_beam_#00" => 5,])->orderBy(2)->commit($small_eden);

        $container->add()->parentBuilding($small_water)
            ->icon('item_firework_tube')->label('Wasserleitungsnetz')->description('Indem du die ganze Stadt mit einem ganzen Netz von Rohren verbindest, kannst du starke wasserbasierte Verteidigungsanlagen in der Stadt aufbauen... Und wer weiß, vielleicht verbessern Sie nebenbei auch noch die Körperhygiene?')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(40)->health(0)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,"rustine_#00" => 1,])->orderBy(8)->commit($item_firework_tube);

        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_waterspray')->label('Kärcher')->description('Dieser leistungsstarke Dampfstrahlreiniger versprüht feinen, siedend heißen Wasserdampf. Deine muffigen Freunde werden beim Anblick dieses Geräts wortwörtlich dahinschmelzen.')
            ->isTemporary(0)->defense(60)->ap(40)->health(40)->blueprintLevel(1)->resources(["water_#00" => 10,"tube_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 5,"oilcan_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_grinder')->label('Kreischender Rotor')->description('Es handelt sich um ein einfallsreiches und SEHR effektives System! Zwei schnell kreisende und mit geschliffenen Eisenstangen bestückte Drehscheiben, die von einem Kolbenmechanismus angetrieben werden, zerfetzen alles und jeden, der sich im Toreingang befindet!')
            ->isTemporary(0)->defense(50)->ap(55)->health(55)->blueprintLevel(1)->resources(["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_sprinkler')->label('Sprinkleranlage')->description('Wie jeder weiß, wird eine Sprinkleranlage für gewöhnlich im Garten eingesetzt. Die wenigsten wissen jedoch, dass sie sich auch hervorragend gegen Zombiehorden eignet. Einziger Wermutstropfen: Die Anlage verbraucht relativ viel Wasser und die Mauer wird etwas rutschiger. Immer vorsichtig laufen!')
            ->isTemporary(0)->defense(185)->ap(85)->health(85)->blueprintLevel(3)->resources(["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,'diode_#00' => 1])->orderBy(3)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_shower')->label('Dusche')->description('Nein, ganz ehrlich, dieser... dieser... Geruch ist einfach nicht auszuhalten: Nimm eine Dusche. Sofort!')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(1)->resources(["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,'oilcan_#00' => 1])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_tube')->label('Wasserturm')->description('Mit dieser revolutionären Verteidigungsanlage ist die Stadt imstande, große Wasserdampfwolken zu erzeugen. Ein wohlig-warmes Dampfbad wird den vor den Stadtmauern herumlungernden Zombies gut tun und sie grundlegend "reinigen". Die Leistung kann mit ein wenig Feintuning noch gesteigert werden.')
            ->isTemporary(0)->defense(70)->ap(50)->health(50)->blueprintLevel(3)->resources(["water_#00" => 25,"tube_#00" => 6,"metal_beam_#00" => 10,])->orderBy(6)
            ->voteLevel(5)->baseVoteText('Der Wasserwerfer gibt 70 zusätzliche Verteidigungspunkte.')
            ->upgradeTexts([
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 252.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 336.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_water)
            ->icon('item_tube')->label('Wasserfänger')->description('Wenn es um Wasser geht, zählt jeder Tropfen. Dieses Bauwerk fügt dem Brunnen +2 Rationen Wasser hinzu und kann jeden Tag gebaut werden.')
            ->isTemporary(1)->defense(0)->ap(10)->health(0)->blueprintLevel(1)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_waterspray)
            ->icon('small_watercanon')->label('Wasserkanone')->description('Ein hübscher kleiner Wasserstrahl, um die wachsende Zombiemeute beim Stadttor zu sprengen.')
            ->isTemporary(0)->defense(80)->ap(40)->health(40)->blueprintLevel(2)->resources(["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,])->orderBy(4)->commit();
        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('small_appletree')->label('Apfelbaum')->description('Dieser Apfelbaum erinnert eher an einen verkümmerten und halbtoten Busch, aber er trägt wunderschöne blaue Äpfel. Äh, Moment mal,... wie bitte?')
            ->isTemporary(0)->defense(0)->ap(30)->health(0)->blueprintLevel(3)->resources(["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"wood_beam_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_shower')->label('Schleuse')->description('Selbst das Abwasser der Stadt kann noch genutzt werden: Wir müssen bloß alle Toiletten der Stadt über ein ausgeklügeltes System aus Rohren und Schlitten miteinander verbinden und dann um Mitternacht die Schleusen öffnen. Hat auch jeder sein Zelt korrekt aufgebaut?')
            ->isTemporary(0)->defense(60)->ap(50)->health(50)->blueprintLevel(1)->resources(["water_#00" => 15,"wood2_#00" => 8,"tube_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_shower')->label('Wasserfall')->description('Anfangs war es nur zur Dekontaminierung gedacht. Aber dann stellte es sich als äußerst effizientes Mittel gegen unsere pestilenten Freunde heraus. Man gebe noch einen Spritzer Kokosnuss-Duschgel hinzu und siehe da: Die meterhohen Leichenstapel, für die DU verantwortlich bist, verströmen ein betörendes Aroma.')
            ->isTemporary(0)->defense(35)->ap(20)->health(20)->blueprintLevel(1)->resources(["water_#00" => 10,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_water_1)
            ->icon('small_rocketperf')->label('Wünschelrakete')->description('Ein selbstgebauer Raketenwerfer feuert in den Boden: Denk mal drüber nach! Der Legende nach wollte der geistige Vater dieses Bauprojekts eigentlich den "Rocket Jump" erfinden. Egal, +60 Rationen Wasser werden so zum Brunnen hinzugefügt.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(80)->health(0)->blueprintLevel(2)->resources(["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_water)
            ->icon('small_waterdetect')->label('Wünschelrute')->description('Dass "Hightech" nicht nur auf die Dezimierung von Zombiehorden beschränkt ist, beweist dieses Gebäude... Es fügt +100 Rationen Wasser zum Brunnen hinzu.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(130)->health(0)->blueprintLevel(4)->resources(["electro_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"tube_#00" => 1,"diode_#00" => 2,])->orderBy(3)->commit();

        $container->add()
            ->icon('item_meat')->label('Metzgerei')->description('In der Metzgerei könnt ihr eure kleinen treuen Begleiter (Hunde, Katzen, Schlangen ...) in Lebensmittel verwandeln. Da gibt es doch tatsächlich noch Leute, die Vegetarier sind...')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood2_#00" => 9,"metal_#00" => 4,])->orderBy(2)->commit($item_meat);

        $container->add()->parentBuilding($item_meat)
            ->icon('item_hmeat')->label('Kremato-Cue')->description('Jeder weiß, was ein Krematorium ist, richtig? Und jeder weiß, wozu man einen Barbecuegrill verwendet? Dann einfach eins und eins zusammenzählen, dann wisst ihr auch wie ein "Kremato-Cue" funktioniert. Die Zeiten des Hungerns sind jedenfalls vorbei...')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(2)->resources(["wood_beam_#00" => 6,"metal_beam_#00" => 1,])->orderBy(1)->commit();

        $container->add()
            ->icon('small_refine')->label('Werkstatt')->description('Die Entwicklung einer jeden Stadt hängt vom Bau einer verdreckten Werkstatt ab. Sie ist die Voraussetzung für alle weiter entwickelten Gebäude.')
            ->isTemporary(0)->defense(0)->ap(25)->blueprintLevel(0)->resources(["wood2_#00" => 10,"metal_#00" => 8,])->orderBy(2)
            ->voteLevel(5)->baseVoteText('Ermöglicht die Umwandlung von Objekten, mit etwas Anstrengung.')
            ->upgradeTexts([
                               'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt. Erhöht die Effektivität von Reparaturen um einen Punkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 35% gesenkt. Erhöht die Effektivität von Reparaturen um zwei Punkte.',
                           ])
            ->commit($small_refine);

        $container->add()->parentBuilding($small_refine)
            ->icon('item_meca_parts')->label('Verteidigungsanlage')->description('Für diese raffiniert durchdachte Anlage können alle Arten von Platten (z.B. Blech) verwendet werden. Jeder in der Bank abgelegte Verteidigungsgegenstand steuert zusätzliche Verteidigungspunkte bei!')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(0)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 8,])->orderBy(7)
            ->voteLevel(3)->baseVoteText('Jeder in der Bank abgelegte Verteidigungsgegenstand bringt der Stadt 1.5 Verteidigungspunkte zusätzlich ein.')->upgradeTexts([
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                               'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('small_dig')->label('Kanonenhügel')->description('Mehrere Erdhügel, die durch Holzbalken verstärkt wurden, bilden die Grundlage für diesen mächtigen Verteidigungsturm.')
            ->isTemporary(0)->defense(30)->ap(60)->health(60)->blueprintLevel(0)->resources(["concrete_wall_#00" => 1,"wood_beam_#00" => 8,"metal_beam_#00" => 1,"meca_parts_#00" => 1,])->orderBy(6)->commit($small_dig);

        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Steinkanone')->description('Dieser automatisierte Wachturm verschießt um Mitternacht minutenlang Felsen mit hoher Geschwindigkeit in Richtung Stadttor. Solltest du vorgehabt haben zu schlafen, kannst du das hiermit vergessen!')
            ->isTemporary(0)->defense(50)->ap(40)->health(40)->blueprintLevel(1)->resources(["tube_#00" => 1,"electro_#00" => 1,"concrete_wall_#00" => 3,"wood_beam_#00" => 3,"metal_beam_#00" => 5,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Selbstgebaute Railgun')->description('Diese improvisierte Railgun funktioniert mit Luftdruck. Sie ist in der Lage, mehrere Ladungen Metallsplitter (verbogene Nägel und rostiges Metall) mit enormer Geschwindigkeit zu verschießen und faustgroße Löcher zu reißen.')
            ->isTemporary(0)->defense(50)->ap(35)->health(35)->blueprintLevel(2)->resources(["wood_beam_#00" => 2,"metal_#00" => 10,"meca_parts_#00" => 2,"plate_#00" => 1,"metal_beam_#00" => 2,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Blechplattenwerfer')->description('Der Blechplattenwerfer schleudert schwere Blechplatten aufs Schlachtfeld. Die angerichtete Schweinerei willst du garantiert kein zweites Mal sehen...')
            ->isTemporary(0)->defense(60)->ap(50)->health(50)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"plate_#00" => 3,"explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_dig)
            ->icon('small_canon')->label('Brutale Kanone')->description('Für diese Maschine möchte man sterben. ...im übertragenen Sinne. Ihr müsst sie allerdings jeden Tag nachladen.')
            ->isTemporary(1)->defense(50)->ap(25)->health(0)->blueprintLevel(1)->resources(["plate_#00" => 1,"metal_beam_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('item_wood_beam')->label('Holzbalkendrehkreuz')->description('Große Balken um eine Achse direkt vor dem Eingang befestigt. Und es dreht sich. Sehr schnell.')
            ->isTemporary(0)->defense(20)->ap(25)->health(25)->blueprintLevel(1)->resources(["wood_beam_#00" => 4,"rustine_#00" => 2,])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_factory')->label('Manufaktur')->description('Indem wir in der Werkstatt ein wenig aufräumen und die Ausstattung verbessern, senken wir zwangsläufig die Produktionskosten für alle durchzuführenden Umbauten!')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_wallimprove3)
            ->icon('small_saw')->label('Kreischende Sägen')->description('Kreissägen, die am Fuße der Mauer durch ein geschicktes elastisches System aktiviert werden. Das Geräusch, das beim Drehen der Sägen entsteht, erinnert seltsamerweise an einen menschlichen Schrei...')
            ->isTemporary(0)->defense(55)->ap(45)->health(45)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"metal_#00" => 10,"rustine_#00" => 2,"metal_beam_#00" => 5,"plate_#00" => 2,"wire_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('item_rp_book2')->label('Baustellenbuch')->description('Mit diesem Register erhältst du eine bessere Übersicht zu allen aktuellen Konstruktionen samt der dafür benötigten Materialien.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(15)->health(0)->blueprintLevel(0)->resources(["table_#00" => 1,"chair_basic_#00" => 1,])->orderBy(1)->commit($item_rp_book2);

        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_refine')->label('Bauhaus')->description('Wenn sich intelligente Leute abends beim Feuer unterhalten, können großartige Erfindungen dabei herauskommen. Zumindest wenn sie vorher ein Bauhaus errichtet haben. Dieses Bauwerk gibt der Stadt täglich einen (gewöhnlichen) Bauplan.')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(0)->resources(["drug_#00" => 1,"vodka_#00" => 1,"wood_beam_#00" => 5,"ryebag_#00" => 2,])->orderBy(4)->commit($small_refine_1);

        $container->add()->parentBuilding($small_refine)
            ->icon('r_dhang')->label('Galgen')->description('An diesem prächtigen Galgen könnt ihr unliebsame (oder lästige) Mitbürger loswerden. Ist mal was "anderes" als die klassische Verbannung...')
            ->isTemporary(0)->defense(0)->ap(13)->health(0)->blueprintLevel(0)->resources(["wood_beam_#00" => 1,"chain_#00" => 1,])->orderBy(7)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_eastercross')->label('Schokoladenkreuz')->description('Ein wunderschönes Kreuz aus Schokolade, an dem unliebsame (oder lästige) Mitbürger platziert werden können. Ist mal was "anderes" als die klassische Verbannung...')
            ->isTemporary(0)->defense(0)->ap(13)->health(0)->blueprintLevel(5)->resources(["wood_beam_#00" => 1,"chain_#00" => 1,])->orderBy(7)->commit();
        $container->add()->parentBuilding($item_meat)
            ->icon('small_slaughterhouse')->label('Schlachthof')->description('Ein Schlachthof, der direkt vor dem Stadttor errichtet wird und dessen Eingang zur Außenwelt zeigt. Schwierig ist eigentlich nur, jede Nacht einen Freiwilligen zu finden, der sich hineinstellt und so die Zombies anlockt.')
            ->isTemporary(0)->defense(45)->ap(35)->health(35)->blueprintLevel(1)->resources(["wood_beam_#00" => 1,"plate_#00" => 2,"metal_beam_#00" => 8,"hmeat_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_rp_book2)
            ->icon('item_shield')->label('Defensivanpassung')->description('Eine umfassende Umstrukturierung unserer Verteidigung, um das Beste daraus zu machen.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(2)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 10,"meca_parts_#00" => 2,"wire_#00" => 1,])->orderBy(0)
            ->voteLevel(3)->baseVoteText('Die Verteidigung der Stadt wird um 10% erhöht.')
            ->upgradeTexts([
                               'Die Verteidigung der Stadt wird um 11% erhöht.',
                               'Die Verteidigung der Stadt wird um 13% erhöht.',
                               'Die Verteidigung der Stadt wird um 15% erhöht.'
                           ])
            ->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_cafet')->label('Kleines Cafe')->description('Das Mittagessen liegt schon lange zurück... Was gibt\'s da besseres als eine solide Holzplanke und altbackenes Brot.')
            ->isTemporary(1)->defense(0)->ap(5)->health(0)->blueprintLevel(1)->resources(["water_#00" => 1,"wood2_#00" => 3,])->orderBy(5)->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('small_cafet')->label('Kantine')->description('Durch die Zentralisierung der Produktion sind persönliche Küchen viel effizienter.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,"ryebag_#00" => 1,"machine_2_#00" => 1,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('item_acid')->label('Labor')->description('Das Kollektiv, es gibt nichts Vergleichbares. Was für eine Freude, alles zu teilen! Persönliche Labore werden effizienter.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["meca_parts_#00" => 1,"pharma_#00" => 4,"wood_beam_#00" => 5,"metal_beam_#00" => 5,"ryebag_#00" => 2,"lens_#00" => 1,"machine_1_#00" => 1,])->orderBy(6)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_chicken')->label('Hühnerstall')->description('Falls du schon vor langer Zeit vergessen hast, wie köstlich ein Omelett mit gebratenen Chamignons, Kräutern und Speck ist, dürfte dieses Gebäude wohl eher unnütz für dich sein. Aber zumindest liefert es dir einige Eier. Was die Pilze betrifft, so musst du dich wohl an die Zombies draußen halten.')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(2)->resources(["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,'ryebag_#00' => 1])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_refine)
            ->icon('small_strategy')->label('Bollwerk')->description('Dieses ambitionierte Stadtbauprojekt hat zum Ziel, die Verteidigung der Bürgerbehausungen besser in die Stadtverteidigung zu integrieren. Dank dieses Bauwerks bringen Bürgerbehausungen fortan 80% statt 40% ihres Verteidigungswertes in die Stadtverteidigung ein.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_refine_1)
            ->icon('small_strategy')->label('Baumarkt')->description('Wenn alle Bürger an den Diskussionstisch eingeladen werden, entstehen neue Ideen. Die Eliten der Stadt brauchen sie dann nur zu stehlen und als ihre eigenen auszugeben.')
            ->isTemporary(0)->defense(0)->ap(45)->health(45)->blueprintLevel(1)->resources(["wood2_#00" => 10,"water_#00" => 5,"metal_beam_#00" => 8,"plate_#00" => 2,"drug_hero_#00" => 1,"vodka_#00" => 1,"table_#00" => 1,"cigs_#00" => 1,"trestle_#00" => 2,"chair_basic_#00" => 1,])->orderBy(0)
            ->voteLevel(4)->baseVoteText('Die Stadt erhält nach dem nächsten Angriff einmalig 1 ungewöhnliche Baupläne.')
            ->upgradeTexts([
                               'Die Stadt erhält nach jedem Angriff 1 gewöhnliche und 1 ungewöhnliche Baupläne sowie.',
                               'Die Stadt erhält nach jedem Angriff 1 ungewöhnliche und 1 seltene Baupläne sowie.',
                               'Die Stadt erhält nach jedem Angriff 2 seltene Baupläne.',
                               'Die Stadt erhält nach jedem Angriff 1 seltene Baupläne und - möglicherweise - eine nette Überraschung.',
                           ])
            ->commit();

        $container->add()
            ->icon('item_tagger')->label('Wachturm')->description('Dieser Turm, der sich in der Nähe des Eingangs befindet, bietet einen perfekten Überblick über die Umgebung und ermöglicht es, den nächtlichen Angriff abzuschätzen und sich somit besser vorzubereiten.')
            ->isTemporary(0)->defense(10)->ap(15)->health(15)->blueprintLevel(0)->resources(["wood2_#00" => 3,"wood_beam_#00" => 1,"metal_#00" => 1,])->orderBy(3)->commit($item_tagger);

        $container->add()->parentBuilding($item_tagger)
            ->icon('item_courroie')->label('Katapult')->description('Das Katapult ist ein äußerst mächtiges Werkzeug, mit dem die Stadt jede Art von Gegenstand in die Wüste schießen kann. Das ist sehr nützlich, wenn man weit entfernte Bürger versorgen möchte (Lebensmittel, Wasser, Waffen etc...).')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(1)->resources(["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,])->orderBy(4)->commit($item_courroie);

        $container->add()->parentBuilding($item_courroie)
            ->icon('item_courroie')->label('Verbesserter Katapult')->description('Dieses erheblich verbesserte Katapult ist einfacher zu bedienen und benötigt weniger AP, um mit einem Gegenstand beladen zu werden!')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(1)->resources(["tube_#00" => 1,"courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 1,"lens_#00" => 1,])->orderBy(0)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('item_tagger')->label('Scanner')->description('Dieser selbstgebaute Zonenscanner erleichtert die Abschätzung des nächtlichen Angriffs erheblich. Wenn er richtig eingesetzt wird, sind nur halb so viele Bürger notwendig, um eine gute Schätzung zu bekommen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["metal_#00" => 5,"pile_#00" => 2,"diode_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,])->orderBy(0)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('item_electro')->label('Verbesserte Karte')->description('Diese simple elektronische Konstruktion erleichtert das Lesen der Außenweltkarte. Konkret: Du erfährst die genaue Zombieanzahl jeder Zone und musst somit nicht mehr planlos in der Wüste rumlaufen...')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(1)->resources(["pile_#00" => 2,"metal_#00" => 5,"plate_#00" => 1,"diode_#00" => 1,"radio_on_#00" => 2,])->orderBy(1)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('item_tagger')->label('Rechenmaschine')->description('Die Rechenmaschine ist ein etwas rustikaler Taschenrechner, mit dem man die Angriffsstärke des MORGIGEN Tages berechnen kann!')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(1)->resources(["rustine_#00" => 1,"electro_#00" => 1,])->orderBy(1)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('small_gather')->label('Forschungsturm')->description('Mit dem Forschungsturm können in bereits "abgesuchten" Wüstenzonen jeden Tag neue Gegenstände gefunden werden! Der Forschungsturm versetzt dich in die Lage, jene anormalen meteorologischen Phänomene aufzuzeichnen und auszuwerten, die sich nachts in der Wüste abspielen. Die entsprechenden Fundstellen werden anschließend in der Zeitung veröffentlicht.')
            ->isTemporary(0)->defense(0)->ap(30)->blueprintLevel(0)->resources(["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,'scope_#00' => 1, 'diode_#00' => 2])->orderBy(5)
            ->voteLevel(5)->baseVoteText('Zeigt die Windrichtung in der Zeitung an.')
            ->upgradeTexts([
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 37%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 49%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 61%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 73%.',
                               'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 85%.',
                           ])
            ->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('status_terror')->label('Notfallkonstruktion')->description('Um mit bestimmten unvorhergesehenen Ereignissen fertig zu werden, ist es manchmal notwendig, ein paar Verteidigungsanlagen für den Notfall zu bauen, ohne sich Sorgen zu machen, dass sie länger als eine Nacht halten werden. Achten wir sollten darauf achten, nicht zu viele Ressourcen und Energie für dieses Provisorium ausgeben!')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["wood2_#00" => 5,"metal_#00" => 5,])->orderBy(13)->commit($status_terror);

        $container->add()->parentBuilding($status_terror)
            ->icon('item_wood_plate')->label('Notfallabstützung')->description('Wir verstärken alles, was wir können, mit ein paar Holzbrettern und drücken die Daumen, dass es in der Nacht hält.')
            ->isTemporary(1)->defense(40)->ap(20)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 6,])->orderBy(0)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_trap')->label('Verteidigungspfähle')->description('Indem wir schnell ein paar scharfe Holzpfähle in die Mitte des Haufens pflanzen, wird uns das hoffentlich über die Nacht retten.')
            ->isTemporary(1)->defense(30)->ap(15)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 5,])->orderBy(1)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_trap')->label('Guerilla')->description('Dieses Arsenal an einfallsreichen Guerillafallen ermöglicht dir, die Zombiereihen zu lichten und die Last des Angriffs entscheidend zu senken.')
            ->isTemporary(1)->defense(60)->ap(30)->blueprintLevel(0)->resources(["wood_beam_#00" => 3,"metal_beam_#00" => 3,"metal_#00" => 5,"rustine_#00" => 1,"wire_#00" => 1,])->orderBy(1)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_dig')->label('Abfallberg')->description('Wenn wirklich gar nichts mehr geht, sammelst du alles ein, was du findest und formst daraus einen großen Abfallhaufen... jetzt heißt es Daumen drücken und hoffen, dass das die Horde irgendwie aufhält... Ach ja, wenn du möchtest, kannst du diesen Abfallberg auch mit Fallen spicken.')
            ->isTemporary(1)->defense(5)->ap(10)->health(0)->blueprintLevel(0)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(2)->commit($small_dig_1);

        $container->add()->parentBuilding($small_dig_1)
            ->icon('small_dig')->label('Trümmerberg')->description('Hast du erst mal einen großen Haufen Müll aufgeschüttet, kannst du ihn einfach noch mit Stacheln versehen, die ebenso rostig wie tödlich sind!')
            ->isTemporary(1)->defense(60)->ap(40)->health(0)->blueprintLevel(1)->resources(["metal_#00" => 2,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_dig_1)
            ->icon('small_trap')->label('Wolfsfalle')->description('Das Hinzufügen von Metall auf Bodenhöhe wird die Zombies nicht aufhalten, aber es könnte sie verlangsamen.')
            ->isTemporary(1)->defense(30)->ap(15)->health(0)->blueprintLevel(0)->resources(["metal_#00" => 5,"hmeat_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_tnt')->label('Sprengfalle')->description('Dynamit, Zombies, Blut.')
            ->isTemporary(1)->defense(35)->ap(30)->health(0)->blueprintLevel(1)->resources(["explo_#00" => 2,])->orderBy(3)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('status_terror')->label('Nackte Panik')->description('Falls die Lage wirklich verzweifelt ist, könnt ihr beschließen loszuschreien und in Panik zu verfallen. Falls alle Überlebenden mitmachen, wird es die Zombies verwirren (denn sie können mit dieser Art Stress nicht umgehen) und euch einige virtuelle Verteidigungespuntke einbringen... Genau, das ist natürlich Unsinn.')
            ->isTemporary(1)->defense(70)->ap(25)->health(25)->blueprintLevel(1)->resources(["water_#00" => 2,"wood2_#00" => 5,"metal_#00" => 5, 'meca_parts_#00' => 1])->orderBy(4)->commit();
        $container->add()->parentBuilding($status_terror)
            ->icon('small_bamba')->label('Dollhouse')->description('Feiern bis zum Abwinken ist immer noch die beste Art, all die schrecklichen Dinge der Außenwelt zu vergessen. Glücklicherweise sorgen die Zombies schon dafür, dass die Dinge nicht zu sehr ausschweifen.')
            ->isTemporary(1)->defense(50)->ap(20)->health(0)->blueprintLevel(1)->resources(["wood2_#00" => 3,"diode_#00" => 1,"radio_on_#00" => 3,"guitar_#00" => 1,])->orderBy(5)->commit();

        $container->add()->parentBuilding($small_round_path)
            ->icon('small_watchmen')->label('Wächter-Turm')->description('Die Installation eines großen Turms, der den Wächtern gewidmet ist, in der Mitte der Festungsmauern, um ihre Effizienz zu verbessern. Von nun an werden die heldenhaften Wächter in der Lage sein, dort während ihrer Ruhezeiten  die Verteidigung der Stadt gegen ein wenig von ihrer Energie zu verbessern.')
            ->isTemporary(0)->defense(0)->ap(35)->health(35)->blueprintLevel(3)->resources(["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,])->orderBy(0)->commit($small_watchmen);

        $container->add()->parentBuilding($small_water)
            ->icon('small_tourello')->label('Schießstand')->description('Ein Geschützturm, der Wasserbomben abfeuert. Unhandlich, aber mit gutem Flächenschaden, jeder wird ihn von der Stadtmauer abreißen wollen!')
            ->isTemporary(0)->defense(60)->ap(30)->health(30)->blueprintLevel(1)->resources(["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 2,"metal_beam_#00" => 2,"meca_parts_#00" => 1,"rustine_#00" => 2,"deto_#00" => 1,])->orderBy(7)->commit();
        $container->add()->parentBuilding($item_courroie)
            ->icon('small_catapult3')->label('Kleiner Tribok')->description('Ein paar spezielle Riemen für deine tierischen Freunde, und es ist an der Zeit, sie zu den Zombies zu schicken. Du wirst sehen, es macht wirklich Spaß, ihnen zuzusehen.')
            ->isTemporary(0)->defense(60)->ap(30)->health(30)->blueprintLevel(2)->resources(["metal_#00" => 2,"wood_beam_#00" => 3,"meca_parts_#00" => 1,"ryebag_#00" => 1,"pet_pig_#00" => 1,])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_round_path)
            ->icon('small_armor')->label('Kleine Waffenschmiede')->description('Nach dem harten Kampf mit Fäusten und Füßen ist es an der Zeit, zu etwas Ernsthafterem überzugehen. Mit einem Waffenvorrat in der Nähe der Stadtmauer wirst du nicht mehr mit leeren Händen auf die Wache zugehen.')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,"metal_#00" => 8,"plate_#00" => 2,"rustine_#00" => 2,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_watchmen)
            ->icon('small_ikea')->label('Schwedische Schreinerei')->description('Dieser kleine Laden verbessert die Effektivität jedes Möbelstücks, das auf der Wache benutzt wird um 30%. Hach ja, die Schweden... Nie gab es bessere Billigmöbel!')
            ->isTemporary(0)->defense(0)->ap(50)->health(50)->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"wood2_#00" => 10,"metal_#00" => 10,"plate_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"radio_on_#00" => 1,])->orderBy(0)->commit($small_armor);

        $container->add()->parentBuilding($item_tagger)
            ->icon('small_watchmen')->label('Rückzugsort für Aufklärer')->description('Die Aufklärer haben dich schon immer fasziniert und nie verraten, was sie unter ihrer Haube verbergen... Aber vielleicht ist es das Beste, sie in Ruhe zu lassen. Mit diesem speziellen Rückzugsort können sie sich endlich an die Arbeit machen, ohne die Stadt verlassen zu müssen.')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(3)->resources(["tube_#00" => 1,"scope_#00" => 1,"metal_beam_#00" => 3,"wood2_#00" => 2,"plate_#00" => 1,"tagger_#00" => 2,"pile_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('small_novlamps')->label('Straßenbeleuchtung')->description('Selbst in der tiefsten Nacht erlaubt dir der fahle Schein der Laternenmasten, deine Ausgrabungen in der Wüste fortzusetzen. Keine Ausreden mehr, um früh ins Bett zu gehen.')
            ->isTemporary(0)->defense(0)->ap(25)->blueprintLevel(1)->resources(["meca_parts_#00" => 1,"deto_#00" => 1,"lens_#00" => 2,"diode_#00" => 2,"metal_beam_#00" => 8,"wire_#00" => 1, "pile_#00" => 4])->orderBy(7)
            ->voteLevel(1)->baseVoteText('Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 6km um die Stadt negiert.')
            ->upgradeTexts([
                               'Die Verringerung der Fundchancen bei Nacht wird auf der gesamten Karte negiert, pro Tag werden 2 Batterien verbraucht.',
                           ])
            ->commit();

        $container->add()
            ->icon('small_building')->label('Fundament')->description('Das Fundament ist die Grundvoraussetzung für "Absurde Projekte" (das sind langwierige und anstrengende Bauten, die jedoch für die Stadt mehr als nützlich sind).')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 8,"metal_#00" => 8,"concrete_wall_#00" => 2,])->orderBy(4)->commit($small_building);

        $container->add()->parentBuilding($small_building)
            ->icon('small_moving')->label('Großer Umbau')->description('Dieses absurde Projekt hat den kompletten Umbau der Stadt zum Ziel. Jeder Stein, jedes Brett und jedes Gebäude wird gemäß eines neuen Verteidigungsplans neu ausgerichtet. Hierzu werden u.a. Häuser näher zusammengerückt, Gassen und selten benutzte Straßen versperrt und Wassergeschütztürme auf die Dächer der Stadt montiert. Ein richtig großer Umbau!')
            ->isTemporary(0)->defense(300)->ap(300)->health(300)->blueprintLevel(3)->resources(["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_eden)
            ->icon('small_derrick')->label('Bohrturm')->description('Auch der Bohrturm ist eine absurde Konstruktion. Mit ihm können selbst tiefste wasserführende Schichten angezapft werden! Er fügt +75 Rationen an Wasser dem Brunnen hinzu.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(86)->health(0)->blueprintLevel(3)->resources(["wood2_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 15,"tube_#00" => 1,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_falsecity')->label('Falsche Stadt')->description('Es ist weithin bekannt, dass die Zombies nicht so ganz helle sind... Wenn ihr es schafft, eine Stadt nachzubauen, könntet ihr den überwiegenden Großteil des Angriffs auf diesen Nachbau umlenken...')
            ->isTemporary(0)->defense(400)->ap(400)->health(400)->blueprintLevel(3)->resources(["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,])->orderBy(1)->commit();
        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_valve')->label('Wasserhahn')->description('Dank dieses kleinen, am Brunnen angebrachten Wasserhahns, kannst Du nun die Wassermengen abschöpfen, die ansonten durch das Filtersystem verschwendet werden (es braucht kein zusätzliches Brunnen-Wasser). Du kannst mit diesem Wasser alle auf Wasser basierenden Waffen KOSTENLOS auffüllen (Wasserbombe, Wasserkanone,...)!')
            ->isTemporary(0)->defense(0)->ap(130)->health(130)->blueprintLevel(3)->resources(["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,'oilcan_#00' => 3])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_scarecrow')->label('Vogelscheuche')->description('Um Tiere (und vor allem diese verdammten Raben) von deiner Plantage fernzuhalten, hast du beschlossen, ein paar alte Holzbretter mit dem Outfit deines alten Nachbarn zu verkleiden. In der Hoffnung, dass er es dir nicht übel nehmen wird!')
            ->defense(15)->ap(40)->health(40)->blueprintLevel(3)->resources(["wood2_#00" => 5,"wood_beam_#00" => 3,"rustine_#00" => 3,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_trash')->label('Müllhalde')->description('Der Eckpfeiler einer jeden großen Stadt: eine riesige, stinkende Müllhalde, die die ganze Stadt umgibt. Zugegeben, das ist nicht gerade ästhetisch, aber immerhin könnt ihr so Alltagsgegenstände in eine effektive Verteidigung verwandeln (nur eine Nacht haltbar).')
            ->isTemporary(0)->defense(0)->ap(80)->health(80)->blueprintLevel(1)->resources(["wood2_#00" => 5,"wood_beam_#00" => 15,"metal_#00" => 10,"metal_beam_#00" => 15,"meca_parts_#00" => 1,"concrete_wall_#00" => 3,])->orderBy(4)
            ->voteLevel(2)->baseVoteText('Ermöglicht das Zerstören von Gegenständen für 1 Verteidigungspunkt.')
            ->upgradeTexts([
                               'Ermöglicht die fachgerechte Entsorgung von Waffen und Nahrung auf der Müllhalde.',
                               'Ermöglicht das fachgerechte Massakrieren unschuldiger Tiere und Steigert die Ausbeute jedes auf den Müll geworfenen Verteidigungs-Gegenstandes.',
                           ])
            ->commit($small_trash);

        // Stubs
        $container->add()->icon('small_trash')->commit();
        $container->add()->icon('small_trash')->commit();
        $container->add()->icon('small_trash')->commit();
        $container->add()->icon('small_trash')->commit();
        $container->add()->icon('small_trash')->commit();

        $container->add()->parentBuilding($small_trash)
            ->icon('small_trash')->label('Verbesserte Müllhalde')->description('Wenn alle Gegenstände vor dem Wegwerfen eingeweicht werden, scheint sich ihre Wirksamkeit nach Einbruch der Dunkelheit zu verzehnfachen.')
            ->isTemporary(0)->defense(65)->ap(150)->health(150)->blueprintLevel(4)->resources(["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,'poison_part_#00' => 1,'meca_parts_#00' => 1])->orderBy(1)->commit();
        $container->add()->parentBuilding($small_trash)
            ->icon('small_trashclean')->label('Organisierte Müll')->description('Durch besseres Sortieren und Klassifizieren von weggeworfenen Gegenständen wird wertvolle Zeit in der Abfallwirtschaft eingespart. Reduziert jede Aktion auf der Müllhalde um 1 AP.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 10,"trestle_#00" => 2,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_refine)
            ->icon('small_fleshcage')->label('Fleischkäfig')->description('Moderne Justiz in all seiner Pracht! Wird ein Mitbürger verbannt, kann er in den Fleischkäfig gesteckt werden, welcher sich wohlweislich direkt vor dem Stadttor befindet. Seine Schreie und Tränen dürften einen exzellenten Köder um Mitternacht abgeben. Jeder verbannte und in den Fleischkäfig gesteckte Mitbürger bringt der Stadt vorübergehende Verteidigungspuntke ein.')
            ->isTemporary(0)->defense(0)->ap(40)->health(0)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"metal_beam_#00" => 1,])->orderBy(8)->commit();
        $container->add()->parentBuilding($item_tagger)
            ->icon('small_lighthouse')->label('Leuchtturm')->description('Dieser schöne, hohe Leuchtturm wird Licht in lange Winternächte bringen (hat er im Sommer eigentlich irgendeinen Nutzen?). Alle Stadtbewohner auf Camping-Ausflug haben eine höhere Überlebens-Chance.')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,"pile_#00" => 1,"diode_#00" => 1,])->orderBy(2)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_city_up')->label('Befestigungen')->description('Da Bürger normalerweise nicht direkt von Verteidigungsbauten profitieren, wollen wir uns mal nicht beschweren. Alle Bürgerbehausungen erhalten +4 Verteidigung.')
            ->isTemporary(0)->defense(0)->ap(50)->health(0)->blueprintLevel(2)->resources(["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,'metal_#00' => 5])->orderBy(6)->commit($small_city_up);
        $container->add()->parentBuilding($small_building)
            ->icon('small_score')->label('Leuchtfeuer')->description('Ein großes Leuchtfeuer, irgendwo weit abseits von der Stadt entzündet, soll die Zombies von unseren Häusern weglocken.')
            ->isTemporary(1)->defense(30)->ap(15)->health(15)->blueprintLevel(2)->resources(["lights_#00" => 1,"wood2_#00" => 5,])->orderBy(11)->commit();
        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_court')->label('Bürgergericht')->description('Wie jeder weiß, haben Helden immer recht. Um diesen Fakt weiter zu zementieren, zählen alle von Helden gegen andere Bürger ausgesprochenen Beschwerden doppelt.')
            ->isTemporary(0)->defense(0)->ap(12)->health(12)->blueprintLevel(3)->resources(["wood2_#00" => 10,"metal_#00" => 10,"table_#00" => 1,"wire_#00" => 1,"radio_on_#00" => 2,])->orderBy(1)->commit();
        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_slave')->label('Ministerium für Sklaverei')->description('Das Ministerium für Sklaverei hat beschlossen, dass Verbannte auf den Baustellen arbeiten dürfen. Außerdem erhält jeder von ihnen in ein Bauprojekt investierte AP einen 50%-Bonus (z.B. aus 6 AP werden so 9 AP, die in das Bauprojekt fließen).')
            ->isTemporary(0)->defense(0)->ap(15)->health(15)->blueprintLevel(4)->resources(["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,'table_#00' => 1])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_water_1)
            ->icon('small_derrick')->label('Tunnelratte')->description('Da selbst der Bohrer des Bohrturms nicht durch jede Schicht durchkommt, muss man hin und wieder kleine und mit Dynamit bestückte Tiere in die Tiefe schicken. Dieses Projekt fügt den städtischen Wasserreserven +100 Rationen hinzu.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(150)->health(0)->blueprintLevel(4)->resources(["tube_#00" => 2, "oilcan_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(0)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_cinema')->label('Kino')->description('Sie zeigen Dawn of the Dead... zum 636. Mal. Bisher war dir die überzeugende Darstellung eines Nebendarstellers noch nie so richtig aufgefallen. Es gibt tatsächlich noch etwas Neues zu entdecken. Und wer weiß? Mit ein bisschen Glück bringt dich der Film ja sogar zum Lachen.')
            ->isTemporary(0)->defense(0)->ap(75)->health(75)->blueprintLevel(4)->resources(["electro_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 5,"lens_#00" => 1,"cinema_#00" => 1,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_balloon')->label('Heißluftballon')->description('Ein großer, runder Ballon steigt hinauf in den Himmel. Aber nur solange, wie der "Freiwillige" in der Gondel braucht, um alles rund um die Stadt zu erfassen. Das Bauwerk ermöglicht es Dir, die gesamte Außenwelt zu entdecken.')
            ->isTemporary(0)->defense(0)->ap(80)->health(80)->blueprintLevel(4)->resources(["wood2_#00" => 2,"meca_parts_#00" => 1,"lights_#00" => 1,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(12)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_labyrinth')->label('Labyrinth')->description('Zombies sind bekanntermaßen einfach gestrickt. Warum ihnen dann nicht einfach ein kleines Labyrinth vor die Nase (das Stadttor) setzen und dabei zusehen, wie ihr Angriff an Schwung verliert. Das Ganze ist äußerst effektiv. Doch jeder Bürger, der die Stadt betreten will, muss dann 1 AP aufbringen.')
            ->isTemporary(0)->defense(150)->ap(200)->health(200)->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,])->orderBy(3)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_lastchance')->label('Alles oder nichts')->description('Nicht mehr als ein Akt der Verzweiflung! Alle Gegenstände in der Bank werden zerstört und bringen jeweils +2 vorübergehende Verteidigung.')
            ->isImpervious(true)->isTemporary(0)->defense(50)->ap(200)->health(200)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,"metal_#00" => 15,])->orderBy(5)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_rocket')->label('Luftschlag')->description('Vier feine Raketen werden gestartet und auf vier strategische Ziele rund um die Stadt (Norden, Süden, Osten, Westen) abgefeuert. Auf ihrem Weg töten sie jeden Zombie.')
            ->isTemporary(1)->defense(0)->ap(50)->health(0)->blueprintLevel(3)->resources(["water_#00" => 10,"meca_parts_#00" => 1,"diode_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_fireworks')->label('Feuerwerk')->description('Es gibt nichts Besseres, um die Tristesse langer Wüstennächte zu vertreiben, als ein schönes, großes Feuerwerk. Diese spezielle Variante geht so: Man feuert die Raketen in die Bereiche rund um die Stadt ab und zündet sie dann um Punkt Mitternacht inmitten der Zombiehorden.')
            ->isTemporary(0)->defense(100)->ap(90)->health(90)->blueprintLevel(0)->resources(["firework_powder_#00" => 1,"firework_tube_#00" => 1,"firework_box_#00" => 2])->orderBy(10)->commit();
        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_redemption')->label('Altar')->description('Weil der Rabe gut und gerecht ist, befreit dieser zu seinen Ehren errichtete Schrein alle Bürger, die aus der Stadt verbannt wurden.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(24)->blueprintLevel(1)->resources(["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,])->orderBy(4)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_pmvbig')->label('Riesiger KVF')->description('Ein wirklich riesiger KVF, auf dem die Namen aller Bürger der Stadt eingraviert sind, erhebt sich stolz in den Himmel... äh. Genau, ein KVF. Niemand weiß warum, aber jemand hat am Fuße des Bauwerks "Eigentum der tiefsinnigen Nacht" eingraviert. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["meca_parts_#00" => 2,"metal_#00" => 30,])->orderBy(13)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_wheel')->label('Dayan Riesenrad')->description('Es ist wirklich eine enorme und beeindruckente Konstruktion, entworfen von einem Verrückten aus einem fernen Land. Ihr habt eure kostbarsten Materialien an dieses verdammte Ding verschwendet, und denoch seid ihr irgendwie stolz darauf. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,])->orderBy(14)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_castle')->label('Riesige Sandburg')->description('Wenn es eines gibt, woran hier wahrlich kein Mangel herrscht, dann ist es Sand. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,])->orderBy(15)->commit();
        $container->add()->parentBuilding($small_building)
            ->icon('small_arma')->label('Reaktor')->description('Dieses furchterregende Konstrukt stammt aus einem sowjetischen U-Boot und sendet gleißende Blitze knisternder Elektrizität rund um die Stadt aus. Einziger Haken an der Sache: Es muss jeden Tag repariert werden. Falls es zerstört wird, würde die Stadt mitsamt der gesamten Umgebung augenblicklich ausradiert werden (inklusive euch). Das Schild am Reaktor besagt: sowjetische Bauweise, hergestellt in « вшивый ».')
            ->isImpervious(true)->isTemporary(0)->defense(500)->ap(100)->health(250)->blueprintLevel(4)->resources(["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,])->orderBy(16)->commit();

        $container->add()->parentBuilding($small_wallimprove)
            ->icon('small_door_closed')->label('Portal')->description('Eine rustikal anmutende Konstruktion, mit der die Öffnung des Stadttors nach 23:40 erfolgreich verhindert werden kann (es dürfte äußerst selten vorkommen, dass das Tor danach nochmal geöffnet werden muss). Das Stadttor muss nichtsdestotrotz zusätzlich noch per Hand geschlossen werden.')
            ->isTemporary(0)->defense(5)->ap(15)->health(15)->blueprintLevel(0)->resources(["metal_#00" => 2,])->orderBy(12)
            ->voteLevel(3)->baseVoteText('Das Portal bringt 5 Verteidigungspunkte.')
            ->upgradeTexts([
                               'Das Portal bringt 20 Verteidigungspunkte.',
                               'Das Portal bringt 50 Verteidigungspunkte.',
                               'Das Portal bringt 80 Verteidigungspunkte und die Öffnung der Tür ist bis zum Mittag kostenlos.',
                           ])
            ->commit($small_door_closed);

        $container->add()->parentBuilding($small_door_closed)
            ->icon('small_door_closed')->label('Kolbenschließmechanismus')->description('Dieser äußerst leistungsstarke Kolbenmotor schließt und verriegelt das Stadttor eine halbe Stunde vor Mitternacht. Nach der Schließung kann das Tor nicht mehr geöffnet werden.')
            ->isTemporary(0)->defense(30)->ap(45)->health(45)->blueprintLevel(1)->resources(["meca_parts_#00" => 1,"metal_#00" => 10,"tube_#00" => 2,"metal_beam_#00" => 2, "diode_#00" => 1,])->orderBy(0)->commit($small_door_closed_2);

        $container->add()->parentBuilding($small_door_closed_2)
            ->icon('small_door_closed')->label('Automatiktür')->description('Das Stadttor schließt sich selbsttätig um 23:59 anstatt 23:30.')
            ->isTemporary(0)->defense(0)->ap(10)->health(10)->blueprintLevel(1)->resources(['diode_#00' => 1])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_door_closed)
            ->icon('item_plate')->label('Torpanzerung')->description('Ein paar improvisierte Panzerplatten werden direkt auf das Stadttor geschraubt und verbessern so die Widerstandskraft desselben.')
            ->isTemporary(0)->defense(25)->ap(35)->health(35)->blueprintLevel(0)->resources(["wood2_#00" => 3,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_door_closed)
            ->icon('small_ventilation')->label('Ventilationssystem')->description('Dieser Geheimgang erlaubt es Helden, ein- und auszugehen, ohne das Stadttor zu benutzen!')
            ->isTemporary(0)->defense(20)->ap(45)->health(45)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"metal_beam_#00" => 2,"metal_#00" => 10,])->orderBy(2)->commit();

        $container->add()
            ->icon('small_spa4souls')->label('Hammam')->description('Mit einer solchen Erweiterung der Seelenreinigungsquelle haben es die Seelen noch eiliger, ihn zu erreichen. Ihr könnt sicher sein, dass sie von nun an näher bei euch auftauchen werden.')
            ->isTemporary(0)->defense(20)->ap(20)->health(20)->blueprintLevel(2)->resources(["wood2_#00" => 2,"plate_#00" => 2,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_gather)
            ->icon('small_gallery')->label('Buddelgruben')->description('Seitengänge, die Buddler direkt vom Großen Grube aus in die Wüste treiben, um noch nie zuvor ausgebeutete Bereiche zu erkunden. Hoffentlich finden sie dort einige interessante Dinge!')
            ->isTemporary(0)->defense(0)->ap(30)->health(30)->blueprintLevel(3)->resources(["explo_#00" => 1, "wood_beam_#00" => 3, "deto_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($item_firework_tube)
            ->icon('small_survarea')->label('Naturbereich der Überlebenskünstler')->description('Ein kleines Stück Paradies, das aus ein paar Grashalmen im Schatten von Dächern und halbierten Dosen besteht, die senkrecht in den Himmel gehoben werden, so eine geheimnisvolle Theorie aus dem Survival-Handbuch der Einsiedler. Angeblich soll dies „die Brise einfangen“ ... ein Rätsel. Aber der Punkt ist, dass man dadurch ein wenig Wasser sammeln kann!')
            ->isTemporary(0)->defense(0)->ap(30)->blueprintLevel(3)->resources(["ryebag_#00" => 2,"wood2_#00" => 5,"radio_on_#00" => 1,"oilcan_#00" => 2,])->orderBy(4)->commit();

        $container->add()->parentBuilding($item_bgrenade)
            ->icon('item_boomfruit')->label('Granatwerfer')->description('Ein Mini-Katapult auf der Stadtmauer, garniert mit explosiven Pampelmusen. Alles, was ihr tun müsst, ist warten und schießen! Mit ein wenig extra Arbeit können wir ihn sogar automatisieren und seine Leistung verbessern.')
            ->isTemporary(0)->defense(40)->ap(60)->health(60)->blueprintLevel(3)->resources(["wood_beam_#00" => 7,"metal_beam_#00" => 2,"meca_parts_#00" => 2,"rustine_#00" => 2,"wire_#00" => 2,"lens_#00" => 1,"boomfruit_#00" => 4,])->orderBy(0)
            ->voteLevel(5)->baseVoteText('Der Granatwerfer gibt 40 zusätzliche Verteidigungspunkte.')
            ->upgradeTexts([
                               'Der Granatwerfer verbraucht beim nächtlichen Angriff 1 Explosive Pampelmuse und steigert seinen Verteidigungswert dafür um 30.',
                               'Der Granatwerfer verbraucht beim nächtlichen Angriff 2 Explosive Pampelmusen und steigert seinen Verteidigungswert dafür um 75.',
                               'Der Granatwerfer verbraucht beim nächtlichen Angriff 3 Explosive Pampelmusen und steigert seinen Verteidigungswert dafür um 150.',
                               'Der Granatwerfer verbraucht beim nächtlichen Angriff 4 Explosive Pampelmusen und steigert seinen Verteidigungswert dafür um 240.',
                               'Der Granatwerfer verbraucht beim nächtlichen Angriff 5 Explosive Pampelmusen und steigert seinen Verteidigungswert dafür um 340.',
                           ])->commit();


        $container->add()->parentBuilding($item_bgrenade)
            ->icon('item_boomfruit')->label('Vitaminen')->description('Wenn wir ein paar explosive Pampelmusen in der Nähe der Stadtmauer in den Boden stecken, sollten wir heute Abend ein schönes Leichenfeuerwerk sehen. Aber morgen müssen wir wieder ganz von vorne anfangen...')
            ->isTemporary(1)->defense(100)->ap(40)->blueprintLevel(3)->resources(["metal_beam_#00" => 2,"wire_#00" => 1,"deto_#00" => 1,"boomfruit_#00" => 5,])->orderBy(1)->commit();

        $container->add()->parentBuilding($item_vegetable_tasty)
            ->icon('item_pumpkin_raw')->label('Wüste Kürbisse')->description('Ein düsterer Ort, den man nur ungern zu betreten wagt, der aber seltsamerweise schöne Kürbisse hervorbringt... Man muss sie nur transportieren können.')
            ->isTemporary(0)->defense(0)->ap(45)->health(45)->blueprintLevel(2)->resources(["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 2,"ryebag_#00" => 3,"drug_#00" => 1,])->orderBy(3)->commit($item_pumpkin_raw);

        $container->add()->parentBuilding($item_rp_book2)
            ->icon('small_techtable')->label('Techniker-Werkstatt')->description('Mit einem eigenen Arbeitsplatz sind Techniker in der Lage, McGyver zu spielen und aus allem, was herumliegt, nützliches Zeug zu bauen.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(3)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 10,"plate_#00" => 1,"wire_#00" => 2,"ryebag_#00" => 1,"lens_#00" => 1,"coffee_machine_#00" => 1,])->orderBy(2)->commit($small_techtable);

        $container->add()->parentBuilding($item_meat)
            ->icon('item_pet_pig')->label('Schweinestall')->description('Seit ihr erfolgreich mit der Schweinezucht begonnen habt, kommt jeden Morgen frisches Fleisch in der Bank an. Ihr solltet nur über die Arbeitshygiene niemals nachdenken... ')
            ->isTemporary(0)->defense(0)->ap(35)->health(35)->blueprintLevel(3)->resources(["wood2_#00" => 8,"wood_beam_#00" => 4,"meca_parts_#00" => 1,"ryebag_#00" => 3,"pet_pig_#00" => 3,])->orderBy(2)->commit();

        $container->add()->parentBuilding($item_tagger)
            ->icon('item_scope')->label('Aussichtsplattform')->description( 'Wenn wir den Wachturm noch ein wenig vergrößern, hält die Außenwelt keine Geheimnisse mehr für uns. Dank seines soliden Fundaments können wir sogar noch ein paar Stockwerke hinzufügen, um noch weiter sehen zu können.')
            ->isTemporary(0)->defense(0)->ap(30)->blueprintLevel(0)->resources(["wood2_#00" => 5,"scope_#00" => 1,"lens_#00" => 1,])->orderBy(0)
            ->voteLevel(5)->baseVoteText('Die Aussichtsplattform ermöglicht es, den Bürger zu wählen, der am besten bei Expeditionen führen kann.')
            ->upgradeTexts([
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 3km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 6km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 1km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                               'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 2km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                           ])
            ->commit($item_scope);

        $container->add()->parentBuilding($small_round_path)
            ->icon('small_watchmen')->label('Wachstube')->description('Ein alter Raum, der nach Kaffee und Tabak riecht, in dem man sich aber viel besser auf die langen Nächte vorbereiten kann, die die Bürger auf der Stadtmauer erwarten.')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(3)->resources(["wood_beam_#00" => 6,"metal_#00" => 10,"meca_parts_#00" => 2,"metal_beam_#00" => 5,"ryebag_#00" => 2,"lights_#00" => 1,"coffee_machine_#00" => 1,"cigs_#00" => 1,"trestle_#00" => 1,"chair_basic_#00" => 2,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_armor)
            ->icon('small_grinder2')->label('Handschleifer')->description('Indem man jede Klinge vor Einbruch der Nacht gewissenhaft schärft, kann die Wacht nun noch effektiver werden. Jede scharfe Waffe hat einen 20%igen Wächterbonus.')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 10,"metal_beam_#00" => 5,"plate_#00" => 2,"concrete_wall_#00" => 2,])->orderBy(1)->commit();

        $container->add()->parentBuilding($small_armor)
            ->icon('small_animfence')->label('Tierhandlung')->description('Indem du deine Tiere direkt auf der Stadtmauer hälst, ist es viel effizienter, sie in die Schlacht zu führen. Und ihr könnt sicher sein, dass sie nicht wieder entkommen! Erhöht die Effizienz von Haustieren während der Wache um 40%.')
            ->isTemporary(0)->defense(0)->ap(40)->blueprintLevel(3)->resources(["wood2_#00" => 5,"wood_beam_#00" => 4,"metal_beam_#00" => 2,"ryebag_#00" => 2,"oilcan_#00" => 1,"fence_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_armor)
            ->icon('small_sewers')->label('Filtrierende Rinnen')->description('Ein ausgeklügeltes System zur Rückgewinnung der unvermeidlichen Spritzer reinen Wassers während der Wacht. Es filtert auch Gehirnspritzer. Wasserwaffen sind während der Wacht 30% effektiver.')
            ->isTemporary(0)->defense(0)->ap(35)->blueprintLevel(3)->resources(["wood2_#00" => 10,"metal_beam_#00" => 3,"tube_#00" => 3,"concrete_wall_#00" => 1,"plate_#00" => 1,"oilcan_#00" => 1,])->orderBy(4)->commit();

        $container->add()->parentBuilding($small_round_path)
            ->icon('small_pet')->label('Experimentelle Klinik der Dompteure')->description('Manche nennen es unschuldig eine "Tierklinik". Aber jeden Abend wecken die Schreie der Tiere das gesamte Südviertel auf. Auf jeden Fall funktioniert es: Unsere Haustiere sind verspielt, sauber, fröhlich und stürzen sich durch ihr Training routiniert auf Zombies, die dreißigmal so schwer sind wie sie.')
            ->isTemporary(0)->defense(0)->ap(40)->blueprintLevel(3)->resources(["wood2_#00" => 4,"water_#00" => 10,"meca_parts_#00" => 1,"drug_#00" => 1,])->orderBy(3)->commit();

        $container->add()->parentBuilding($small_building)
            ->icon('small_underground')->label('Unterirdische Stadt')->description('Indem wir einen großen Teil der Stadt unter der Erde vergraben, schaffen wir neuen Platz für die Verteidigungsanlagen über unseren Köpfen. Sehr es positiv: wir sind dann vor der Sonne geschützt.')
            ->isTemporary(0)->defense(400)->ap(500)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"explo_#00" => 5,"metal_#00" => 10,"wood_beam_#00" => 20,"metal_beam_#00" => 20,])->orderBy(2)->commit();

        $container->add()->parentBuilding($small_city_up)
            ->icon('small_urban')->label('Stadtplan')->description('Mit genau definierten Grundstücken für jede Behausung können wir endlich sehen, wie viel man noch ausbauen kann! Es ist an der Zeit, etwas Neues zu versuchen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"wood_beam_#00" => 1,"wood2_#00" => 5,"rustine_#00" => 3,"wire_#00" => 5,"diode_#00" => 4,])->orderBy(1)->commit();

        $container->add()
            ->icon('small_spa4souls')->label('Sanktuarium')->description('Auch wenn dir das Spirituelle ein wenig über den Kopf wächst - ein Raum, der dem Wohlbefinden und der Entspannung gewidmet ist, hilft dir, dich weniger um die kommenden Tage zu sorgen. Nun... wenn du denn Zeit hättest, es zu besuchen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(0)->resources(["wood2_#00" => 2,"wood_beam_#00" => 3,"ryebag_#00" => 1,])->orderBy(5)
            ->commit($small_spa4souls);

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_cemetery')->label('Kleiner Friedhof')->description('Bringt eure Toten! Denn diesmal werden sie sich noch als nützlich erweisen. Macht das beste aus ihnen und verbessert damit gemeinsam eure Verteidigung. Jeder zum Friedhof gebrachte tote Mitbürger bringt +10 Verteidigungspunkte für die Gesamtverteidigung der Stadt. Hinweis: Es spielt keine Rolle, wo und woran ein Mitbürger verstarb.')
            ->isTemporary(0)->defense(0)->ap(42)->health(42)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,])->orderBy(2)->commit($small_cemetery);

        $container->add()->parentBuilding($small_cemetery)
            ->icon('small_coffin')->label('Sarg-Katapult')->description('Von 2 Toten hat derjenige, der sich bewegt, die besten Chancen, dich zu verspeisen. Trickst eure Feinde aus, indem ihr eure Leichen in die herankommende Zombiehorde schleudert. Jeder Tote bringt +20 anstelle von +10 Verteidigungspunkten.')
            ->isTemporary(0)->defense(0)->ap(85)->health(85)->blueprintLevel(3)->resources(["courroie_#00" => 1,"concrete_wall_#00" => 2,"wire_#00" => 2,"meca_parts_#00" => 3,"wood2_#00" => 5,"metal_#00" => 15,])->orderBy(0)->commit();

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_infirmary')->label('Krankenstation')->description('Egal ob kleines Wehwehchen oder irreparables Trauma - die Krankenstation empfängt dich mit offenen Armen. Zumindst solange du noch imstande bist, dich selbst zu verarzten, denn diese Einrichtung kommt ganz ohne medizinisches Personal daher.')
            ->isTemporary(0)->defense(0)->ap(40)->health(40)->blueprintLevel(3)->resources(["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(3)->commit();


        $container->add()->parentBuilding($small_spa4souls)
            ->icon('item_soul_blue_static')->label('Seelenreinigungsquelle')->description('Ein Ort der Entspannung, der sich hervorragend für die Überführung von Seelen in die ewige Ruhe eignet.')
            ->isTemporary(0)->defense(20)->ap(30)->health(30)->blueprintLevel(0)->resources(["metal_#00" => 1,"rustine_#00" => 1,"ryebag_#00" => 2,"lens_#00" => 1,"oilcan_#00" => 1,])->orderBy(0)
            ->voteLevel(3)->baseVoteText('Du kannst jetzt die Seelen deiner verstorbenen Mitbürger reinigen, um ein wenig zusätzliche Verteidigung zu erhalten.')
            ->upgradeTexts([
                               'Jede gereinigte Seele bringt der Stadt etwas mehr Verteidigung.',
                               'Zusätzlich zum vorherigen Effekt sinkt der Einfluss gequälter Seelen auf den Angriff leicht ab.',
                               'Zusätzlich zum vorherigen Effekt wird der Verteidigungsbonus gereinigter Seelen weiter erhöht, und jede gereinigte Seele bringt zwei zusätzliche Ranking-Punkte für die Stadt ein.',
                           ])
            ->commit($item_soul_blue_static);

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_pool')->label('Pool')->description('Ein großer Pool, der nur für euer Wohlbefinden eingerichtet ist. Wenn man nicht auf seine Mitbürger achtet, die panisch um einen herumlaufen, könnte man den Angriff heute Abend glatt vergessen.')
            ->isTemporary(0)->defense(0)->ap(150)->blueprintLevel(4)->resources(["wood2_#00" => 18,"plate_#00" => 2,"metal_beam_#00" => 1,"water_#00" => 20,"meca_parts_#00" => 2,"tube_#00" => 1,"ryebag_#00" => 2,])->orderBy(1)
            ->commit();

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_thermal')->label('Blaugoldige Thermalbäder')->description('Die kolossale Menge an Wasser, die für den Bau dieses Gebäudes verbraucht wurde, kann nicht mehr konsumiert oder gegen Zombies eingesetzt werden. Während du dich fragst, was dich dazu gebracht hat, eine so kostbare Ressource zu verschwenden, bemerkst du die in goldenen Buchstaben eingravierte Inschrift auf dem Eingangsbogen: "Non est certamen". Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(300)->health(300)->blueprintLevel(4)->resources(["water_#00" => 100,"metal_beam_#00" => 8,"tube_#00" => 6,"wood_beam_#00" => 6,"concrete_wall_#00" => 2,"fence_#00" => 1,"water_cleaner_#00" => 4,])->orderBy(6)->commit();

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_crow')->label('Krähenstatue')->description('Huldigt den Raben! Gelobt sei deine Milde und deine erhabene Austrahlung! Befreie uns vom Spam und vergib uns unsere Trollenbeiträge so wie auch wir vergeben anderen Trollen. Dieses Wunderwerk strahlt im Glanze seiner Nutzlosigkeit: Seine Errichtung bringt allen Bürgern der Stadt eine seltene Auszeichnung ein.')
            ->isImpervious(true)->isTemporary(0)->defense(0)->ap(300)->health(0)->blueprintLevel(4)->resources(["hmeat_#00" => 3,"wood_beam_#00" => 35,])->orderBy(5)->commit();

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_vaudoudoll')->label('Voodoo-Puppe')->description('Ein über 2 Meter hoher, schimmliger Wollballen, über und über mit Stricken und Nadeln bedeckt. In den mächtigen Händen des Schamanen wird dieses *Ding* zu einem XXL-Püppchen, das etliche Zombies niederstreckt, ehe es wieder eine unförmige, unbewegliche Masse wird.')
            ->isTemporary(0)->defense(65)->ap(40)->health(40)->blueprintLevel(0)->resources(["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_yellow_#00" => 2,])->orderBy(7)->commit();

        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_bokorsword')->label('Bokors Guillotine')->description('Mit Hilfe einer teuflischen Guillotine und einer provisorischen Schaufensterpuppe, kann der Schamane aus der Entfernung den Kopf eines Zombierudel-Führers abschlagen. Die Zombies die ihm folgen, werden daraufhin abdrehen und wieder in die Wüste wandern.')
            ->isTemporary(0)->defense(100)->ap(60)->health(60)->blueprintLevel(0)->resources(["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_yellow_#00" => 3,])->orderBy(8)->commit();
        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_spiritmirage')->label('Spirituelles Wunder')->description('Dieser Zauber des Schamanen erschafft ein Trugbild der Stadt. Als Folge verliert sich eine beträchtliche Anzahl Zombies heillos in der Wüste.')
            ->isTemporary(0)->defense(80)->ap(30)->health(30)->blueprintLevel(0)->resources(["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_yellow_#00" => 2,])->orderBy(9)->commit();
        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_holyrain')->label('Heiliger Regen')->description('Nur der Schamane kennt das Geheimnis dieses rituellen Feuertanzes. Richtig ausgeführt, steigt eine kleine Wolke in den Himmel und bewirkt, dass ein Schauer heiligen Wassers auf die Zombiehorde niedergeht.')
            ->isTemporary(1)->defense(200)->ap(40)->health(0)->blueprintLevel(0)->resources(["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_yellow_#00" => 4,])->orderBy(10)->commit();


        $container
            // Bauhaus
            ->modify('small_refine_#01')->parentBuilding($small_spa4souls)->commit()
            //Weiterentwickelte Stadtmauer
            ->modify('small_wallimprove_#01')->icon('small_devwall')->commit()
            // Vogelscheuche
            ->modify('small_scarecrow_#00')->parentBuilding($item_pumpkin_raw)->commit()
            // Scanner
            ->modify('item_tagger_#01')->parentBuilding($item_scope)->commit()
            // Verbesserte Karte
            ->modify('item_electro_#00')->parentBuilding($item_scope)->commit()
            //Rückzugsort für Aufklärer
            ->modify('small_watchmen_#01')->parentBuilding($item_scope)->icon('small_lair')->commit()
            // Hammam
            ->modify('small_spa4souls_#00')->parentBuilding($item_soul_blue_static)->commit()
            //Zackenmauer
            ->modify('item_plate_#02')->icon('small_spikedwall')->commit()
            // Holzzaun
            ->modify('small_fence_#01')->icon('small_fence2')->commit()
            // Rüstungsplatten
            ->modify('item_plate_#06')->icon('small_woodwall')->commit()
            // Rüstungsplatten 2.0
            ->modify('item_plate_#07')->icon('small_metalwall')->commit()
            // Rüstungsplatten 3.0
            ->modify('item_plate_#08')->icon('small_thickwall')->commit()
            // Extramauer
            ->modify('item_plate_#10')->icon('small_bastion')->commit()
            // Sperrholz
            ->modify('item_plate_#09')->icon('small_plywood')->commit()
            // Holzbalkendrehkreuz
            ->modify('item_wood_beam_#00')->parentBuilding($small_door_closed)->commit()
            // Kreischender Rotor
            ->modify('small_grinder_#00')->parentBuilding($small_door_closed)->commit()
            //Einseifer
            ->modify('small_wallimprove_#03')->icon('small_soapwall')->commit()
            //Rasierklingenmauer
            ->modify('item_plate_#00')->parentBuilding($small_wallimprove3)->icon('small_shredwall')->commit()
            //Groooße Mauer
            ->modify('item_plate_#03')->icon('small_bigwall')->commit()
            //Zweite Schicht
            ->modify('item_plate_#04')->icon('small_secondlayer')->commit()
            //Dritte Schicht
            ->modify('item_plate_#05')->icon('small_thirdlayer')->commit()
            // Torpanzerung
            ->modify('item_plate_#11')->icon('small_reinfdoor')->commit()
            // Betonschicht
            ->modify('small_wallimprove_#02')->icon('small_concretewall')->commit()
            //Verstärkende Balken
            ->modify('item_plate_#01')->icon('small_reinfbeams')->commit()
            // Betonschicht
            ->modify('small_wallimprove_#02')->parentBuilding($item_plate9)->commit();
        $container
            //Zombiereibe
            ->modify('small_grater_#00')->parentBuilding($small_wallimprove3)->commit()
            //Kreischende Sägen
            ->modify('small_saw_#00')->parentBuilding($small_wallimprove3)->commit()
            // Verteidigungspfähle
            ->modify('small_trap_#00')->parentBuilding($small_dig_1)->commit()
            //Zerstäuber
            ->modify('small_waterspray_#00')->parentBuilding($small_water)->commit()
            // Minen
            ->modify('item_bgrenade_#00')->parentBuilding($item_vegetable_tasty)->commit()
            // Metzgerei
            ->modify('item_meat_#00')->parentBuilding($small_refine)->commit()
            // Brustwehr
            ->modify('small_round_path_#00')->parentBuilding($item_tagger)->commit()
            // Schwedische Schreinerei
            ->modify('small_ikea_#00')->parentBuilding($small_armor)->commit()
            // Kanonenhügel
            ->modify('small_dig_#00')->parentBuilding($item_tagger)->commit()
            // Bollwerk
            ->modify('small_strategy_#00')->parentBuilding($small_city_up)->commit()
            //Verteidigungsanlage
            ->modify('item_meca_parts_#00')->parentBuilding($small_building)->commit()
            // Baumarkt
            ->modify('small_strategy_#01')->parentBuilding($small_refine_1);

        // Delete stubs
        // Previous sub-constructions, their effects will be included into Müllhalde evolutions
        $container
            ->delete('small_trash_#01')
            ->delete('small_trash_#02')
            ->delete('small_trash_#03')
            ->delete('small_trash_#04')
            ->delete('small_trash_#05');

        $data = $container->toArray();
    }
}