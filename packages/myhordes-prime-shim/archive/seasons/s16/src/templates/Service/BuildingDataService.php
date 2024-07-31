<?php

namespace MyHordes\Prime\Service;

use MyHordes\Fixtures\DTO\Buildings\BuildingPrototypeDataContainer;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class BuildingDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $container = new BuildingPrototypeDataContainer($data);

        //Verstärkte Stadtmauer, todo: build this construction in small town
        $container->modify('small_wallimprove_#00')->description('Mauern um die Stadt zu errichten, darüber müsste man nachdenken! Dies ist der Anfang der Befestigungsanlagen.')->resource('wood2_#00', 10)->commit();

        //Großer Graben
        $container->modify('small_gather_#00')->ap(70)->health(70)->upgradeTexts([
            'Der Verteidigungsbonus des Grabens steigt dauerhaft um 20.',
            'Der Verteidigungsbonus des Grabens steigt dauerhaft um 25.',
            'Der Verteidigungsbonus des Grabens steigt dauerhaft um 30.',
            'Der Verteidigungsbonus des Grabens steigt dauerhaft um 35.',
            'Der Verteidigungsbonus des Grabens steigt dauerhaft um 40.',
        ])->commit();
        // Wassergraben
        $container->modify('small_waterhole_#00')->parentBuilding('small_gather_#00')->defense(60)->ap(60)->health(60)->blueprintLevel(0)->commit();
        // Pfahlgraben
        $container->modify('small_spears_#00')->parentBuilding('small_gather_#00')->description('Ein guter Weg, um die Große Grube zu füllen, mit scharfen Pfählen, um zu sehen, wie sich die Zombies dort aufspießen.')->defense(45)->ap(35)->health(35)->blueprintLevel(0)->resources(["metal_#00" => 2,"wood_beam_#00" => 8,])->orderBy(1)->commit();
        // Fallgruben
        $container->modify('small_gather_#01')->parentBuilding('small_gather_#00')->parentBuilding('small_gather_#00')->description('Mit tieferen Löchern und darüber ausgelegten Planen, einfach abwarten und zusehen, wie etwas (oder jemand?) hineinfällt!')->ap(25)->health(25)->resources(["metal_beam_#00" => 1, "plate_#00" => 2,])->orderBy(2)->commit();
        //Buddelgruben, todo effect
        $container->add()->parentBuilding('small_gather_#00')
            ->icon('item_shovel')->label('Buddelgruben')->description('Seitengänge, die Buddler direkt vom Großen Grube aus in die Wüste treiben, um noch nie zuvor ausgebeutete Bereiche zu erkunden. Hoffentlich finden sie dort einige interessante Dinge!')
            ->isTemporary(0)->defense(0)->ap(25)->health(25)->blueprintLevel(3)->resources(["explo_#00" => 1,])->orderBy(3)->commit();

        //Weiterentwickelte Stadtmauer
        $container->modify('small_wallimprove_#01')->defense(20)->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(3)->commit();
        //Zweite Schicht
        $container->modify('item_plate_#04')->parentBuilding('small_wallimprove_#01')->defense(70)->ap(60)->health(60)->blueprintLevel(2)->orderBy(0)->commit();
        //Dritte Schicht
        $container->modify('item_plate_#05')->parentBuilding('item_plate_#04')->description('Nach der zweiten Wand dachten wir, warum nicht eine dritte? Die Isolierung wird einfach besser sein!')->ap(60)->health(60)->blueprintLevel(3)->resources(["metal_#00" => 35,"plate_#00" => 3,"metal_beam_#00" => 5,])->orderBy(0)->commit();
        //Groooße Mauer
        $container->modify('item_plate_#03')->parentBuilding('small_wallimprove_#01')->description('Eine Mauer ist gut, eine große Mauer ist besser.')->blueprintLevel(1)->resources(["wood2_#00" => 10, "metal_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 10,"metal_beam_#00" => 10,])->orderBy(1)->commit();
        //Entwicklungsfähige Stadtmauer
        $container->modify('item_home_def_#00')->parentBuilding('small_wallimprove_#01')->resource('metal_#00', 15)->resource('concrete_wall_#00', 2)->commit();
        //Verstärkende Balken
        $container->modify('item_plate_#01')->parentBuilding('small_wallimprove_#01')->description('Eine verstärkte Struktur für die schwächeren Teile der Mauer.')->defense(35)->ap(15)->health(15)->blueprintLevel(1)->resource('wood_beam_#00', 5)->orderBy(3)->commit();
        //Zackenmauer
        $container->modify('item_plate_#02')->parentBuilding('small_wallimprove_#01')->orderBy(4)->commit();

        //Einseifer
        $container->modify('small_wallimprove_#03')->parentBuilding('small_wallimprove_#00')->description('Warum ist das nicht schon früher jemandem eingefallen? Anstatt sich um die persönliche Hygiene zu kümmern, benutzen wir Seife, um die Wälle der Stadt rutschig zu machen.')->ap(35)->health(35)->blueprintLevel(0)->resources(["metal_#00" => 10, "water_#00" => 10,"tube_#00" => 1,"plate_#00" => 2,"pharma_#00" => 2,])->orderBy(4)->commit();

        //Rasierklingenmauer
        $container->modify('item_plate_#00')->parentBuilding('small_wallimprove_#03')->orderBy(0)->commit();
        //Zombiereibe
        $container->modify('small_grater_#00')->parentBuilding('small_wallimprove_#03')->description('Bedecken wir einen großen Teil der Mauer mit einer Vielzahl von scharfen Metallstücken, dann haben wir die größte Käsereibe der Welt. Man kann zusehen, wie die Zombies hineinrutschen. Das einzige Problem ist der Lärm.')->ap(40)->health(40)->resources(["meca_parts_#00" => 3,"metal_beam_#00" => 5,"metal_#00" => 15,"plate_#00" => 1,"wire_#00" => 1,])->orderBy(1)->commit();
        //Kreischende Sägen
        $container->modify('small_saw_#00')->parentBuilding('small_wallimprove_#03')->description('Kreissägen, die am Fuße der Mauer durch ein geschicktes elastisches System aktiviert werden. Das Geräusch, das beim Drehen der Sägen entsteht, erinnert seltsamerweise an einen menschlichen Schrei...')->defense(55)->ap(45)->health(45)->blueprintLevel(2)->resources(["meca_parts_#00" => 1,"metal_#00" => 10,"rustine_#00" => 2,"metal_beam_#00" => 5,"plate_#00" => 2,"wire_#00" => 1,])->orderBy(2)->commit();

        //Zaun
        $container->modify('small_fence_#00')->parentBuilding('small_wallimprove_#00')->description('Holzzäune, die vor der Mauer errichtet wurden, um die auf die Stadt zustürmenden Zombies zu verlangsamen (oder es zumindest zu versuchen).')->defense(40)->ap(60)->health(60)->resources(["wood2_#00" => 15,"wood_beam_#00" => 5,])->orderBy(5)->commit();
        // Holzzaun
        $container->modify('small_fence_#01')->parentBuilding('small_fence_#00')->description('Verstärken wir die Barrieren und erhöhen sie ein wenig, um ihre Wirkung auf die Verteidigung der Stadt zu verbessern.')->defense(60)->resources(["wood2_#00" => 10,"wood_beam_#00" => 8,"metal_beam_#00" => 2,"plate_#00" => 1,])->orderBy(0)->commit();

        // Stacheldraht
        $container->modify('small_barbed_#00')->parentBuilding('small_wallimprove_#00')->description('Bedecken wir die Mauern mit Stacheldraht, so dass ein paar kleine Stücke an der Passage hängen bleiben.')->defense(20)->ap(10)->health(10)->resources(["metal_#00" => 1,"wire_#00" => 2,])->orderBy(6)->commit();
        // Köder
        $container->modify('small_meatbarbed_#00')->defense(30)->blueprintLevel(0)->resource('bone_meat_#00', 2)->commit();

        // Sperrholz
        $container->modify('item_plate_#09')->parentBuilding('small_wallimprove_#00')->defense(15)->resources(["wood2_#00" => 2,"metal_#00" => 2,])->orderBy(7)->commit();

        // Betonschicht
        $container->modify('small_wallimprove_#02')->parentBuilding('item_plate_#09')->description('Mit den Betonblöcken, die wir gefunden haben, können wir die Sperrholzplatten verstärken, damit sie endlich für etwas nützlich sind.')->defense(80)->ap(40)->health(40)->blueprintLevel(2)->resources(["wood2_#00" => 5,"concrete_wall_#00" => 5,"metal_beam_#00" => 10,])->orderBy(0)->commit();

        // Rüstungsplatten
        $container->modify('item_plate_#06')->defense(30)->orderBy(8)->commit();
        // Rüstungsplatten 2.0
        $container->modify('item_plate_#07')->description('Es ist nicht sehr fortschrittlich oder gut durchdacht, aber es erfüllt seinen Zweck ... es verzögert unseren Tod. Ein wenig.')->defense(30)->orderBy(9)->commit();
        // Rüstungsplatten 3.0
        $container->modify('item_plate_#08')->defense(45)->ap(30)->health(30)->orderBy(10)->resources(["wood2_#00" => 8,"metal_#00" => 8,])->commit();
        // Extramauer
        $container->modify('item_plate_#10')->description('Schützt das Herz der Stadt mit einer zusätzlichen Mauer. Man muss keine helle Leuchte sein, um auf diese Idee zu kommen, aber es kann auch nicht schaden.')->defense(50)->ap(25)->health(25)->orderBy(11)->commit();

        // Portal
        $container->modify('small_door_closed_#00')->parentBuilding('small_wallimprove_#00')->defense(5)->ap(15)->health(15)->resource('plate_#00', 1)->orderBy(12)->commit();
        // Kolbenschließmechanismus
        $container->modify('small_door_closed_#01')->ap(45)->health(45)->resources(["meca_parts_#00" => 1,"metal_#00" => 10,"tube_#00" => 2,"metal_beam_#00" => 2, "diode_#00" => 1,])->commit();
        // Automatiktür
        $container->modify('small_door_closed_#02')->resource('diode_#00', 1)->commit();
        // Torpanzerung
        $container->modify('item_plate_#11')->defense(25)->commit();
        // Ventilationssystem
        $container->modify('small_ventilation_#00')->ap(45)->health(45)->blueprintLevel(1)->resources(["meca_parts_#00" => 2,"metal_beam_#00" => 2,"metal_#00" => 10,])->orderBy(2)->commit();
        // Holzbalkendrehkreuz
        $container->modify('item_wood_beam_#00')->parentBuilding('small_door_closed_#00')->description('Große Balken um eine Achse direkt vor dem Eingang befestigt. Und es dreht sich. Sehr schnell.')->defense(20)->ap(25)->health(25)->blueprintLevel(1)->resources(["wood_beam_#00" => 4,"rustine_#00" => 2,])->orderBy(3)->commit();
        // Kreischender Rotor
        $container->modify('small_grinder_#00')->parentBuilding('small_door_closed_#00')->orderBy(4)->commit();

        // Notfallkonstruktion
        $container->modify('status_terror_#00')->parentBuilding('small_wallimprove_#00')->description('Um mit bestimmten unvorhergesehenen Ereignissen fertig zu werden, ist es manchmal notwendig, ein paar Verteidigungsanlagen für den Notfall zu bauen, ohne sich Sorgen zu machen, dass sie länger als eine Nacht halten werden. Achten wir sollten darauf achten, nicht zu viele Ressourcen und Energie für dieses Provisorium ausgeben!')->resources(["wood2_#00" => 5,"metal_#00" => 5,])->orderBy(13)->commit();
        // Notfallabstützung
        $container->modify('item_wood_plate_#00')->description('Wir verstärken alles, was wir können, mit ein paar Holzbrettern und drücken die Daumen, dass es in der Nacht hält.')->ap(20)->resources(["wood2_#00" => 6,])->orderBy(0)->commit();
        // Guerilla
        $container->modify('small_trap_#01')->defense(60)->ap(30)->blueprintLevel(0)->resources(["wood_beam_#00" => 3,"metal_beam_#00" => 3,"metal_#00" => 5,"rustine_#00" => 1,"wire_#00" => 1,])->orderBy(1)->commit();
        // Abfallberg
        $container->modify('small_dig_#01')->orderBy(2)->commit();
        // Trümmerberg
        $container->modify('small_dig_#02')->parentBuilding('small_dig_#01')->commit();
        // Verteidigungspfähle
        $container->modify('small_trap_#00')->parentBuilding('small_dig_#01')->description('Indem wir schnell ein paar scharfe Holzpfähle in die Mitte des Haufens pflanzen, wird uns das hoffentlich über die Nacht retten.')->defense(30)->ap(15)->resource('wood2_#00', 5)->commit();
        // Wolfsfalle
        $container->modify('small_trap_#02')->parentBuilding('small_dig_#01')->description('Das Hinzufügen von Metall auf Bodenhöhe wird die Zombies nicht aufhalten, aber es könnte sie verlangsamen.')->defense(30)->ap(15)->health(15)->resources(["metal_#00" => 5,"hmeat_#00" => 1,])->orderBy(2)->commit();
        // Sprengfalle
        $container->modify('small_tnt_#00')->ap(30)->blueprintLevel(1)->resource('explo_#00', 2)->orderBy(3)->commit();
        // Nackte Panik
        $container->modify('status_terror_#01')->defense(70)->blueprintLevel(1)->resource('water_#00', 2)->resource('meca_parts_#00', 1)->orderBy(4)->commit();
        // Dollhouse
        $container->modify('small_bamba_#00')->defense(50)->ap(20)->blueprintLevel(1)->resources(["wood2_#00" => 3,"diode_#00" => 1,"radio_on_#00" => 3,"guitar_#00" => 1,])->orderBy(5)->commit();

        //Pumpe, now gives 15 water
        $container->modify('small_water_#00')->description('Die Pumpe ermöglicht alle Konstruktionen, die mit Wasser zu tun haben. Indem man kräftig pumpt, ist es möglich, die Wassermenge im Brunnen leicht zu erhöhen.')->baseVoteText('Der Brunnen wird einmalig mit 15 Rationen Wasser befüllt.')->commit();
        // Wasserfänger, gives 2 water
        $container->modify('item_tube_#01')->ap(10)->resources(["metal_#00" => 2,])->orderBy(0)->commit();
        // Brunnenbohrer, now gives 50 water
        $container->modify('small_water_#01')->ap(55)->orderBy(1)->commit();
        //Tunnelratte, now gives 100 water
        $container->modify('small_derrick_#01')->parentBuilding('small_water_#01')->ap(150)->resources(["tube_#00" => 2, "oilcan_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 15,])->orderBy(0)->commit();
        //Wünschelrakete, gives 60 water
        $container->modify('small_rocketperf_#00')->parentBuilding('small_water_#01')->ap(80)->blueprintLevel(2)->resources(["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(1)->commit();
        //Projekt Eden, now gives 50 water
        $container->modify('small_eden_#00')->parentBuilding('small_water_#00')->ap(50)->blueprintLevel(2)->resources(["wood2_#00" => 10,"explo_#00" => 2,"deto_#00" => 1,"metal_beam_#00" => 5,])->orderBy(2)->commit();
        //Bohrturm, now gives 75 water
        $container->modify('small_derrick_#00')->parentBuilding('small_eden_#00')
            ->description('Auch der Bohrturm ist eine absurde Konstruktion. Mit ihm können selbst tiefste wasserführende Schichten angezapft werden! Er fügt +75 Rationen an Wasser dem Brunnen hinzu.')
            ->ap(86)->resources(["wood2_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 15,"tube_#00" => 1,])->orderBy(0)
            ->commit();
        //Wünschelrute, gives 100 water
        $container->modify('small_waterdetect_#00')->resources(["electro_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"tube_#00" => 1,"diode_#00" => 2,])->orderBy(3)->commit();
        // Wasserreiniger
        $container->modify('item_jerrycan_#00')->resources(["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,"tube_#00" => 2,"oilcan_#00" => 2,])->orderBy(4)->commit();
        // Wasserfilter
        $container->modify('item_jerrycan_#01')->parentBuilding('item_jerrycan_#00')->ap(50)->health(50)->resources(["metal_#00" => 10,"electro_#00" => 2,"wire_#00" => 1,"oilcan_#00" => 1,"fence_#00" => 1,])->orderBy(0)->commit();

        //Zerstäuber
        $container->modify('small_waterspray_#00')->parentBuilding('small_water_#00')->blueprintLevel(0)->resources(["meca_parts_#00" => 2,"metal_#00" => 8,"tube_#00" => 2,"metal_beam_#00" => 2,"wire_#00" => 2,"deto_#00" => 1,])->orderBy(5)->commit();
        //Kärcher
        $container->modify('small_waterspray_#01')->parentBuilding('small_waterspray_#00')->defense(60)->ap(40)->health(40)->blueprintLevel(1)->resources(["water_#00" => 10,"tube_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 5,"oilcan_#00" => 1,])->orderBy(0)->commit();
        //Säurespray
        $container->modify('small_acidspray_#00')->description('Das Hinzufügen einiger Chemikalien zum verwendeten Wasser wird das hübsche Gesicht der Zombies vor der Stadt definitiv nicht verschönern.')->ap(25)->resources(["water_#00" => 3,"pharma_#00" => 2,])->orderBy(1)->commit();
        //Spraykanone, todo hidden effect : +10% terror chance in Watch
        $container->modify('small_gazspray_#00')->description('Oft wird vergessen, dass Zombies ein Gehirn haben. Manchmal sogar zwei, wenn sie Glück haben. Trifft sich gut: Das mit dieser Kanone geschossene Konzentrat hat die erstaunliche Fähigkeit, Gehirne in Matsch zu verwandeln. Es könnte allerdings sein, dass sie auf eure Wächter herunterfällt... aber wo gehobelt wird, da fallen Späne.')->defense(140)->ap(60)->health(60)->blueprintLevel(1)->resources(["metal_beam_#00" => 5,"water_#00" => 5,"meca_parts_#00" => 1,"tube_#00" => 1,"pharma_#00" => 2,"poison_part_#00" => 1,])->orderBy(2)->commit();
        //Sprinkleranlage, todo hidden effect : +4% death chance in Watch
        $container->modify('small_sprinkler_#00')->description('Wie jeder weiß, wird eine Sprinkleranlage für gewöhnlich im Garten eingesetzt. Die wenigsten wissen jedoch, dass sie sich auch hervorragend gegen Zombiehorden eignet. Einziger Wermutstropfen: Die Anlage verbraucht relativ viel Wasser und die Mauer wird etwas rutschiger. Immer vorsichtig laufen!')->parentBuilding('small_waterspray_#00')->defense(185)->resource('diode_#00', 1)->orderBy(3)->commit();
        //Wasserkanone
        $container->modify('small_watercanon_#00')->parentBuilding('small_waterspray_#00')->orderBy(4)->commit();

        // Wasserturm
        $container->modify('item_tube_#00')->ap(50)->health(50)->resources(["water_#00" => 25,"tube_#00" => 6,"metal_beam_#00" => 10,])->orderBy(6)
            ->upgradeTexts([
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 252.',//todo edit effect; now +252
                               'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 336.',//todo edit effect; now +336
                           ])
            ->commit();
        //Schießstand
        $container->modify('small_tourello_#00')->parentBuilding('small_water_#00')
            ->description('Ein Geschützturm, der Wasserbomben abfeuert. Unhandlich, aber mit gutem Flächenschaden, jeder wird ihn von der Stadtmauer abreißen wollen!')
            ->defense(60)->ap(30)->health(30)->blueprintLevel(1)->resources(["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 2,"metal_beam_#00" => 2,"meca_parts_#00" => 1,"rustine_#00" => 2,"deto_#00" => 1,])->orderBy(7)
            ->commit();

        // Wasserleitungsnetz, now gives 5 water
        $container->modify('item_firework_tube_#00')->description('Indem du die ganze Stadt mit einem ganzen Netz von Rohren verbindest, kannst du starke wasserbasierte Verteidigungsanlagen in der Stadt aufbauen... Und wer weiß, vielleicht verbessern Sie nebenbei auch noch die Körperhygiene?')
            ->resources(["meca_parts_#00" => 2,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,"rustine_#00" => 1,])->orderBy(8)
            ->commit();
        // Wasserhahn
        $container->modify('small_valve_#00')->parentBuilding('item_firework_tube_#00')->resource('oilcan_#00', 3)->orderBy(0)->commit();
        // Wasserfall
        $container->modify('small_shower_#02')->parentBuilding('item_firework_tube_#00')->orderBy(1)->commit();
        // Schleuse
        $container->modify('small_shower_#01')->parentBuilding('item_firework_tube_#00')->resources(["water_#00" => 15,"wood2_#00" => 8,"tube_#00" => 1,])->orderBy(2)->commit();
        // Dusche, todo effect
        $container->modify('small_shower_#00')->blueprintLevel(1)->resource('oilcan_#00', 1)->commit();
        //Naturbereich der Überlebenskünstler, todo effect
        $container->add()->parentBuilding('item_firework_tube_#00')
            ->icon('item_surv_book')->label('Naturbereich der Überlebenskünstler')->description('Anstatt die Überlebenskünstler überall in der Stadt urinieren zu lassen, habt ihr ihnen ein kleines Stück Paradies gebaut, nur für sie! Der Vorteil ist, dass es direkt über einem Gemüsegarten liegt und wir so täglich die Wasserreserven aufstocken können. Nun, zumindest wenn ihr bereit sind, das zu trinken...')
            ->isTemporary(0)->defense(0)->ap(30)->blueprintLevel(3)->resources(["ryebag_#00" => 2,"wood2_#00" => 5,"radio_on_#00" => 1,"oilcan_#00" => 2,])->orderBy(4)->commit();

        // Gemüsebeet
        $container->modify('item_vegetable_tasty_#00')->blueprintLevel(0)->orderBy(9)->commit();
        // Dünger
        $container->modify('item_digger_#00')->description('Erhöht den Ertrag des Gemüsegartens und aller umliegenden Gärten erheblich.')->resource('ryebag_#00', 3)->orderBy(0)->commit();
        // Granatapfel
        $container->modify('item_bgrenade_#01')->ap(40)->health(40)->resource('oilcan_#00', 1)->orderBy(1)->commit();
        //Granatwerfer, todo: nightly effect similar than Water Turrets but with explosive grapefruits
        $container->add()->parentBuilding('item_bgrenade_#01')
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
        //Vitaminen
        $container->add()->parentBuilding('item_bgrenade_#01')
            ->icon('item_boomfruit')->label('Vitaminen')->description('Wenn wir ein paar explosive Pampelmusen in der Nähe der Stadtmauer in den Boden stecken, sollten wir heute Abend ein schönes Leichenfeuerwerk sehen. Aber morgen müssen wir wieder ganz von vorne anfangen...')
            ->isTemporary(1)->defense(100)->ap(40)->blueprintLevel(3)->resources(["metal_beam_#00" => 2,"wire_#00" => 1,"deto_#00" => 1,"boomfruit_#00" => 5,])->orderBy(1)->commit();
        // Apfelbaum
        $container->modify('small_appletree_#00')->parentBuilding('item_vegetable_tasty_#00')->orderBy(2)->commit();
        //Wüste Kürbisse, todo effect : provide 1-2 pumpkins / day (1-3 pumpkins if Fertiliser is built)
        $container->add()->parentBuilding('item_vegetable_tasty_#00')
            ->icon('item_pumpkin_raw')->label('Wüste Kürbisse')->description('Ein düsterer Ort, den man nur ungern zu betreten wagt, der aber seltsamerweise schöne Kürbisse hervorbringt... Man muss sie nur transportieren können.')
            ->isTemporary(0)->defense(0)->ap(45)->health(45)->blueprintLevel(2)->resources(["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 2,"ryebag_#00" => 3,"drug_#00" => 1,])->orderBy(3)->commit($item_pumpkin_raw);
        // Vogelscheuche, todo effect : improves pummpkins production
        $container->modify('small_scarecrow_#00')->parentBuilding($item_pumpkin_raw)
            ->description('Um Tiere (und vor allem diese verdammten Raben) von deiner Plantage fernzuhalten, hast du beschlossen, ein paar alte Holzbretter mit dem Outfit deines alten Nachbarn zu verkleiden. In der Hoffnung, dass er es dir nicht übel nehmen wird!')
            ->defense(15)->ap(40)->health(40)->blueprintLevel(3)->resources(["wood2_#00" => 5,"wood_beam_#00" => 3,"rustine_#00" => 3,])->orderBy(0)
            ->commit();
        // Minen
        $container->modify('item_bgrenade_#00')->parentBuilding('item_vegetable_tasty_#00')->blueprintLevel(1)->orderBy(10)->commit();

        //Werkstatt
        $container->modify('small_refine_#00')->orderBy(2)
            ->upgradeTexts([
                               'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                               'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt. Erhöht die Effektivität von Reparaturen um einen Punkt.',//todo edit effect: now -25% for AP-cost
                               'Die AP-Kosten aller Bauprojekte werden um 35% gesenkt. Erhöht die Effektivität von Reparaturen um zwei Punkte.',//todo edit effect: now -35% for AP-cost
                           ])
            ->commit();
        // Manufaktur
        $container->modify('small_factory_#00')->description('Indem wir in der Werkstatt ein wenig aufräumen und die Ausstattung verbessern, senken wir zwangsläufig die Produktionskosten für alle durchzuführenden Umbauten!')->orderBy(0)->commit();
        //Baustellenbuch
            $container->modify('item_rp_book2_#00')->parentBuilding('small_refine_#00')->resources(["table_#00" => 1,"chair_basic_#00" => 1,])->orderBy(1)->commit();
        //Defensivanpassung, replace the Pentagon construction (new name)
            $container->modify('item_shield_#00')->parentBuilding('item_rp_book2_#00')->label('Defensivanpassung')->description('Eine umfassende Umstrukturierung unserer Verteidigung, um das Beste daraus zu machen.')
            ->defense(0)->ap(60)->blueprintLevel(2)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 10,"meca_parts_#00" => 2,"wire_#00" => 1,])->orderBy(0)
            ->upgradeTexts([
                               'Die Verteidigung der Stadt wird um 11% erhöht.',//todo: now 11%
                               'Die Verteidigung der Stadt wird um 13% erhöht.',//todo: now 13%
                               'Die Verteidigung der Stadt wird um 15% erhöht.'//todo: new level 15%
                           ])
            ->commit();
        //Bürgergericht
        $container->modify('small_court_#00')->parentBuilding('item_rp_book2_#00')->blueprintLevel(3)->resources(["wood2_#00" => 10,"metal_#00" => 10,"table_#00" => 1,"wire_#00" => 1,"radio_on_#00" => 2,])->orderBy(1)->commit();
        //Techniker-Werkstatt, todo : effect
        $container->add()->parentBuilding('item_rp_book2_#00')
            ->icon('item_keymol')->label('Techniker-Werkstatt')->description('Mit einem eigenen Arbeitsplatz sind Techniker in der Lage, McGyver zu spielen und aus allem, was herumliegt, nützliches Zeug zu bauen.')
            ->isTemporary(0)->defense(0)->ap(60)->health(60)->blueprintLevel(3)->resources(["wood_beam_#00" => 5,"metal_beam_#00" => 10,"plate_#00" => 1,"wire_#00" => 2,"ryebag_#00" => 1,"lens_#00" => 1,"coffee_machine_#00" => 1,"table_#00" => 1,])->orderBy(2)->commit($item_keymol);
        //Ministerium für Sklaverei
        $container->modify('small_slave_#00')->parentBuilding('item_rp_book2_#00')->ap(15)->health(15)->resource('table_#00', 1)->orderBy(3)->commit();
        //Altar
        $container->modify('small_redemption_#00')->parentBuilding('item_rp_book2_#00')->blueprintLevel(1)->orderBy(4)->commit();

        // Metzgerei
        $container->modify('item_meat_#00')->parentBuilding('small_refine_#00')->commit();
        //Schlachthof
        $container->modify('small_slaughterhouse_#00')->parentBuilding('item_meat_#00')->defense(45)->ap(35)->health(35)->resources(["wood_beam_#00" => 1,"plate_#00" => 2,"metal_beam_#00" => 8,"hmeat_#00" => 1,])->orderBy(0)->commit();
        //Kremato-Cue
        $container->modify('item_hmeat_#00')->ap(40)->health(40)->resource('wood_beam_#00', 6)->orderBy(1)->commit();
        //Schweinestall, todo effect : add between 2-4 good steak (7AP) per day
        $container->add()->parentBuilding('item_meat_#00')
            ->icon('item_pet_pig')->label('Schweinestall')->description('Seit ihr erfolgreich mit der Schweinezucht begonnen habt, kommt jeden Morgen frisches Fleisch in der Bank an. Ihr solltet nur über die Arbeitshygiene niemals nachdenken... ')
            ->isTemporary(0)->defense(0)->ap(35)->health(35)->blueprintLevel(3)->resources(["wood2_#00" => 8,"wood_beam_#00" => 4,"meca_parts_#00" => 1,"ryebag_#00" => 3,"pet_pig_#00" => 3,])->orderBy(2)->commit();

        // Hühnerstall
        $container->modify('small_chicken_#00')->blueprintLevel(2)->resource('ryebag_#00', 1)->orderBy(3)->commit();
        // Kantine
        $container->modify('small_cafet_#01')->description('Durch die Zentralisierung der Produktion sind persönliche Küchen viel effizienter.')->resources(["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,"ryebag_#00" => 1,"machine_2_#00" => 1,])->orderBy(4)->commit();
        // Kleines Cafe
        $container->modify('small_cafet_#00')->ap(5)->blueprintLevel(1)->resources(["water_#00" => 1,"wood2_#00" => 3,])->orderBy(5)->commit();
        // Labor
        $container->modify('item_acid_#00')->description('Das Kollektiv, es gibt nichts Vergleichbares. Was für eine Freude, alles zu teilen! Persönliche Labore werden effizienter.')
            ->ap(20)->health(20)->resources(["meca_parts_#00" => 1,"pharma_#00" => 4,"wood_beam_#00" => 5,"metal_beam_#00" => 5,"ryebag_#00" => 2,"lens_#00" => 1,"machine_1_#00" => 1,])->orderBy(6)->commit();
        //Die Höhle der Aufklärer, todo: new effect, replace the old Krähennest (small_watchmen_#01)
            $container->modify('small_watchmen_#01')->parentBuilding('item_acid_#00')->icon('item_vest_on')->label('Die Höhle der Aufklärer')->description('Die Aufklärer haben dich schon immer fasziniert und nie verraten, was sie unter ihrer Haube verbergen... Aber vielleicht ist es das Beste, sie in Ruhe zu lassen. Mit diesem speziellen Keller können sie sich endlich an die Arbeit machen, schließlich mögen alle ihre Kräuter.')
            ->defense(0)->ap(25)->blueprintLevel(3)->resources(["tube_#00" => 2,"metal_beam_#00" => 3,"plate_#00" => 1,"ryebag_#00" => 3,"machine_1_#00" => 1,])->orderBy(0)->commit();
        // Galgen
        $container->modify('r_dhang_#00')->orderBy(7)->commit();
        // Schokoladenkreuz
        $container->modify('small_eastercross_#00')->orderBy(7)->commit();
        //Fleischkäfig
        $container->modify('small_fleshcage_#00')->parentBuilding('small_refine_#00')->resources(["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"metal_beam_#00" => 1,])->orderBy(8)->commit();

        // Wachturm
        $container->modify('item_tagger_#00')->description('Dieser Turm, der sich in der Nähe des Eingangs befindet, bietet einen perfekten Überblick über die Umgebung und ermöglicht es, den nächtlichen Angriff abzuschätzen und sich somit besser vorzubereiten.')
            ->voteLevel(0)->defense(10)->ap(15)->health(15)->resources(["wood2_#00" => 3,"wood_beam_#00" => 1,"metal_#00" => 1,])->orderBy(3)->commit();
        //Aussichtsplattform, todo: now this constructions has the Wachturm evolutions, and has a lvl0 which allows to elect a Guide
        $container->add()->parentBuilding('item_tagger_#00')
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
        // Scanner
        $container->modify('item_tagger_#01')->parentBuilding($item_scope)->blueprintLevel(1)->resources(["metal_#00" => 5,"pile_#00" => 2,"diode_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,])->orderBy(0)->commit();
        // Verbesserte Karte
        $container->modify('item_electro_#00')->parentBuilding($item_scope)->ap(25)->health(25)->resources(["pile_#00" => 2,"metal_#00" => 5,"plate_#00" => 1,"diode_#00" => 1,"radio_on_#00" => 2,])->orderBy(1)->commit();
        // Rechenmaschine
        $container->modify('item_tagger_#02')->parentBuilding('item_tagger_#00')->orderBy(1)->commit();
        // Leuchtturm
        $container->modify('small_lighthouse_#00')->parentBuilding('item_tagger_#00')->resources(["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,"pile_#00" => 1,"diode_#00" => 1,])->orderBy(2)->commit();
        // Brustwehr, todo : this constructions allows players to Watch once built. 10 watchers max at lvl0. Votable, PLEASE NOTE, WITHOUT ARMOURY (small_armor_#00), THE ITEMS (WHATEVER THE ITEM) ARE NOT COUNTED IN THE BAGS.
        $container->modify('small_round_path_#00')->parentBuilding('item_tagger_#00')->ap(25)->health(25)->resources(["wood2_#00" => 6,"wood_beam_#00" => 2,"metal_beam_#00" => 2,"meca_parts_#00" => 1,])->orderBy(3)
            ->voteLevel(3)->baseVoteText('Diese Brustwehr um die Stadt erlaubt es einigen Wächtern, die Stadt von der Mauer aus zu schützen.')
            ->upgradeTexts([
                               'Indem man die Zinnen etwas erweitert, können mehr Wächter während des Angriffs kämpfen.',
                               'Der Brustwehr ist so breit, dass die ganze Stadt dort übernachten kann.',
                               'Durch den Ausbau der Brustwehr können sich die Wächter dort etwas sicherer fühlen.',
                           ])
            ->commit();
        // Wächter-Turm, todo: edit effect ; remove the passive defense gain obtained by guardians
        $container->modify('small_watchmen_#00')->parentBuilding('small_round_path_#00')
            ->description('Die Installation eines großen Turms, der den Wächtern gewidmet ist, in der Mitte der Festungsmauern, um ihre Effizienz zu verbessern. Von nun an werden die heldenhaften Wächter in der Lage sein, dort während ihrer Ruhezeiten  die Verteidigung der Stadt gegen ein wenig von ihrer Energie zu verbessern.')
            ->defense(0)->ap(35)->health(35)->blueprintLevel(3)->orderBy(0)
            ->commit();
        // Wachstube, todo effect: +5% survival chance in Watch
        $container->add()->parentBuilding('small_round_path_#00')
            ->icon('small_watchmen')->label('Wachstube')->description('Ein alter Raum, der nach Kaffee und Tabak riecht, in dem man sich aber viel besser auf die langen Nächte vorbereiten kann, die die Bürger auf der Stadtmauer erwarten.')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(3)->resources(["wood_beam_#00" => 5,"metal_#00" => 10,"meca_parts_#00" => 2,"metal_beam_#00" => 5,"rustine_#00" => 1,"ryebag_#00" => 2,"oilcan_#00" => 1,"lights_#00" => 1,"coffee_machine_#00" => 1,"cigs_#00" => 1,"trestle_#00" => 1,"chair_basic_#00" => 2,])->orderBy(1)->commit();
        // Kleine Waffenschmiede, todo: edit effect ; don't provide Watch bonus but now items in bag are counted in Watch 
        $container->modify('small_armor_#00')->parentBuilding('small_round_path_#00')
            ->description('Nach dem harten Kampf mit Fäusten und Füßen ist es an der Zeit, zu etwas Ernsthafterem überzugehen. Mit einem Waffenvorrat in der Nähe der Stadtmauer wirst du nicht mehr mit leeren Händen auf die Wache zugehen.')
            ->ap(40)->health(40)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,"metal_#00" => 8,"plate_#00" => 2,"rustine_#00" => 2,])->orderBy(2)
            ->commit();
        // Schwedische Schreinerei, now provides nw_ikea bonus (30%)
        $container->modify('small_ikea_#00')->parentBuilding('small_armor_#00')->description('Dieser kleine Laden verbessert die Effektivität jedes Möbelstücks, das auf der Wache benutzt wird um 30%. Hach ja, die Schweden... Nie gab es bessere Billigmöbel!')->blueprintLevel(3)->resources(["meca_parts_#00" => 2,"wood2_#00" => 10,"metal_#00" => 10,"plate_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"radio_on_#00" => 1,])->orderBy(0)->commit();
        // Waffenschmiede, now provides nw_armory bonus
        $container->add()->parentBuilding('small_armor_#00')
            ->icon('small_blacksmith')->label('Waffenschmiede')->description('Indem man jede Klinge vor Einbruch der Nacht gewissenhaft schärft, kann die Wacht nun noch effektiver werden. Jede scharfe Waffe hat einen 20%igen Wächterbonus.')
            ->isTemporary(0)->defense(0)->ap(50)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 10,"metal_beam_#00" => 5,"plate_#00" => 2,"concrete_wall_#00" => 2,])->orderBy(1)->commit();
        // Tierhandlung, replace Trebuchet functionning (provide nw_trebuchet bonus)
        $container->add()->parentBuilding('small_armor_#00')
            ->icon('small_animfence')->label('Tierhandlung')->description('Indem du deine Tiere direkt auf der Stadtmauer hälst, ist es viel effizienter, sie in die Schlacht zu führen. Und ihr könnt sicher sein, dass sie nicht wieder entkommen! Erhöht die Effizienz von Haustieren während der Wache um 40%.')
            ->isTemporary(0)->defense(0)->ap(40)->blueprintLevel(3)->resources(["wood2_#00" => 5,"wood_beam_#00" => 4,"metal_beam_#00" => 2,"ryebag_#00" => 2,"oilcan_#00" => 1,"fence_#00" => 1,])->orderBy(3)->commit();
        // Dompteur-Spa, todo effect
        $container->add()->parentBuilding('small_round_path_#00')
            ->icon('item_tamed_pet')->label('Dompteur-Spa')->description('Eure Hunde verdienen nur das Beste, und wenn sie euch auf die Wacht begleiten, heißt es, ihre Zähne zu schärfen und ihre Locken zu bürsten. Alles, damit eure Schuckel die schönsten auf der Stadtmauer sind! Außerdem soll es ihre Effektivität verbessern und Ihre Überlebenschancen in der Nacht erhöhen.')
            ->isTemporary(0)->defense(0)->ap(40)->blueprintLevel(3)->resources(["wood2_#00" => 4,"water_#00" => 10,"meca_parts_#00" => 1,"drug_#00" => 1,])->orderBy(3)->commit();
        //Verschmutzte Rinnen, todo: edit has_shooting_gallery functions for using Verschmutzte Rinnen
        $container->add()->parentBuilding('small_round_path_#00')
            ->icon('small_sewers')->label('Verschmutzte Rinnen')->description('Wenn wir die Abwässer der Stadt mit Hilfe eines Pumpensystems direkt an die Spitze der Stadtmauer leiten, können usere Wasserwaffen 20% effizienter eingesetzt werden. Könnte aber ein bisschen stinken...')
            ->isTemporary(0)->defense(0)->ap(35)->blueprintLevel(3)->resources(["wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,"tube_#00" => 3,"concrete_wall_#00" => 1,"plate_#00" => 1,"oilcan_#00" => 1,])->orderBy(4)->commit();

        // Katapult
        $container->modify('item_courroie_#00')->orderBy(4)->commit();
        // Verbesserter Katapult
        $container->modify('item_courroie_#01')->blueprintLevel(1)->resources(["tube_#00" => 1,"courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 1,"lens_#00" => 1,])->commit();
        // Kleiner Tribok, this no longer provide nw_trebuchet (now it's Tierhandlung who provide the bonus). Todo effect: this construction allows to throw Animals without killing them in the Catapult
        $container->modify('small_catapult3_#00')->parentBuilding('item_courroie_#00')
            ->description('Ein paar spezielle Riemen für deine tierischen Freunde, und es ist an der Zeit, sie zu den Zombies zu schicken. Du wirst sehen, es macht wirklich Spaß, ihnen zuzusehen.')
            ->defense(60)->resources(["metal_#00" => 2,"wood_beam_#00" => 3,"meca_parts_#00" => 1,"ryebag_#00" => 1,"pet_pig_#00" => 1,])
            ->commit();

        // Forschungsturm
        $container->modify('small_gather_#02')->blueprintLevel(2)->resource('scope_#00', 1)->resource('diode_#00', 2)->orderBy(5)->commit();

        // Kanonenhügel
        $container->modify('small_dig_#00')->parentBuilding('item_tagger_#00')->ap(60)->health(60)->resources(["concrete_wall_#00" => 1,"wood_beam_#00" => 8,"metal_beam_#00" => 1,"meca_parts_#00" => 1,])->orderBy(6)->commit();
        // Steinkanone
        $container->modify('small_canon_#00')->ap(40)->health(40)->resources(["tube_#00" => 1,"electro_#00" => 1,"concrete_wall_#00" => 3,"wood_beam_#00" => 3,"metal_beam_#00" => 5,])->commit();
        // Selbstgebaute Railgun
        $container->modify('small_canon_#01')->ap(35)->health(35)->blueprintLevel(2)->resources(["wood_beam_#00" => 2,"metal_#00" => 10,"meca_parts_#00" => 2,"plate_#00" => 1,"metal_beam_#00" => 2,])->commit();
        // Blechplattenwerfer
        $container->modify('small_canon_#02')->resources(["meca_parts_#00" => 2,"plate_#00" => 3,"explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 1,])->commit();
        // Brutale Kanone
        $container->modify('small_canon_#03')->blueprintLevel(1)->commit();

        // Straßenbeleuchtung
        $container->modify('small_novlamps_#00')->resources(["meca_parts_#00" => 1,"deto_#00" => 1,"lens_#00" => 2,"diode_#00" => 2,"metal_beam_#00" => 8,"wire_#00" => 1, "pile_#00" => 4])->orderBy(7)
            ->voteLevel(3)
            ->upgradeTexts([
                'Die Verringerung der Fundchancen bei Nacht wird im Umkreis von 6km um die Stadt negiert, pro Tag wird 1 Batterie verbraucht.',
                'Die Verringerung der Fundchancen bei Nacht wird auf der gesamten Karte negiert, pro Tag werden 2 Batterien verbraucht.',
                'Bietet nur nachts einen leichten Suchbonus, pro Tag werden 3 Batterien verbraucht.',//todo: new lvl effect of Public lights, provide +10% search bonus during night only (the +10% is hidden for the regular user)
            ])->commit();

        // Fundament
        $container->modify('small_building_#00')->resources(["wood2_#00" => 8,"metal_#00" => 8,"concrete_wall_#00" => 2,])->orderBy(4)->commit();
        //Großer Umbau (no edit)
        // Falsche Stadt
        $container->modify('small_falsecity_#00')->orderBy(1)->commit();
        //Unterirdische Stadt
        $container->add()->parentBuilding('small_building_#00')
            ->icon('small_underground')->label('Unterirdische Stadt')->description('Indem wir einen großen Teil der Stadt unter der Erde vergraben, schaffen wir neuen Platz für die Verteidigungsanlagen über unseren Köpfen. Sehr es positiv: wir sind dann vor der Sonne geschützt.')
            ->isTemporary(0)->defense(400)->ap(500)->blueprintLevel(3)->resources(["meca_parts_#00" => 3,"explo_#00" => 5,"metal_#00" => 10,"wood_beam_#00" => 20,"metal_beam_#00" => 20,])->orderBy(2)->commit();
        // Labyrinth
        $container->modify('small_labyrinth_#00')->orderBy(3)->commit();
        // Müllhalde, todo: new functionning of Dump sites (deposit / taking objects)
        $container->modify('small_trash_#00')->ap(80)->health(80)->blueprintLevel(1)->resources(["wood2_#00" => 5,"wood_beam_#00" => 15,"metal_#00" => 10,"metal_beam_#00" => 15,"meca_parts_#00" => 1,"concrete_wall_#00" => 3,])->orderBy(4)
            ->voteLevel(3)->baseVoteText('Ermöglicht das Zerstören von Gegenständen für 1 Verteidigungspunkt.')//todo: instead multiple dumps construction, this constructions has evolutions which has same effects than previous dumps
            ->upgradeTexts([
                               'Ermöglicht die fachgerechte Entsorgung von Waffen und Nahrung auf der Müllhalde.',//alows weapons & food dump
                               'Ermöglicht das fachgerechte Massakrieren unschuldiger Tiere und Steigert die Ausbeute jedes auf den Müll geworfenen Verteidigungs-Gegenstandes.',//increase defense provided by defense objects & allows animal dump
                               'Ermöglicht die fachgerechte Entsorgung von Holz und Metall auf der Müllhalde.',//alows wood & metal dump
                           ])
            ->commit();
        // Müll für Alle, todo: adjust effect : must reduce from 1AP the dump cost, not 0AP anymore
        $container->modify('small_trashclean_#00')->description('Durch besseres Sortieren und Klassifizieren von weggeworfenen Gegenständen wird wertvolle Zeit in der Abfallwirtschaft eingespart. Reduziert jede Aktion auf der Müllhalde um 1 AP.')
            ->ap(20)->health(20)->resource('metal_beam_#00', 5)->orderBy(0)->commit();
        // Verbesserte Müllhalde
        $container->modify('small_trash_#06')->description('Wenn alle Gegenstände vor dem Wegwerfen eingeweicht werden, scheint sich ihre Wirksamkeit nach Einbruch der Dunkelheit zu verzehnfachen.')
            ->defense(65)->ap(150)->health(150)->resource('concrete_wall_#00', 5)->resource('poison_part_#00', 1)->resource('meca_parts_#00', 1)->orderBy(1)->commit();

        // Previous sub-constructions, their effects will be included into Müllhalde evolutions
        $container->delete('small_trash_#01');
        $container->delete('small_trash_#02');
        $container->delete('small_trash_#03');
        $container->delete('small_trash_#04');
        $container->delete('small_trash_#05');
        $container->delete('small_howlingbait_#00');

        // Alles oder Nichts, now give +2 def / item
        $container->modify('small_lastchance_#00')->description('Nicht mehr als ein Akt der Verzweiflung! Alle Gegenstände in der Bank werden zerstört und bringen jeweils +2 vorübergehende Verteidigung.')
            ->defense(50)->ap(200)->health(200)->blueprintLevel(0)->resources(["meca_parts_#00" => 1,"wood2_#00" => 10,"metal_#00" => 15,])->orderBy(5)->commit();
        // Befestigungen
        $container->modify('small_city_up_#00')->blueprintLevel(2)->resource('metal_#00', 5)->orderBy(6)->commit();
        // Bollwerk
        $container->modify('small_strategy_#00')->parentBuilding('small_city_up_#00')->orderBy(0)->commit();
        // Stadtplan, todo: this constructions allows to build personal defense above level 4, todo: edit the constructions which allows it (must be small_strategy_#02)
        $container->add()->parentBuilding('small_city_up_#00')
            ->icon('small_urban')->label('Stadtplan')->description('Mit genau definierten Grundstücken für jede Behausung können wir endlich sehen, wie viel man noch ausbauen kann! Es ist an der Zeit, etwas Neues zu versuchen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(4)->resources(["meca_parts_#00" => 1,"wood_beam_#00" => 1,"wood2_#00" => 5,"rustine_#00" => 3,"wire_#00" => 5,"diode_#00" => 4,])->orderBy(1)->commit();
        //Verteidigungsanlage
        $container->modify('item_meca_parts_#00')->parentBuilding('small_building_#00')
            ->voteLevel(3)->resources(["meca_parts_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 8,])->orderBy(7)
            ->upgradeTexts([//todo: only 3 levels now + todo: delete the 500 OD limit (even if this construction is not built)
                'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
            ])
            ->commit();
        // Kino
        $container->modify('small_cinema_#00')->resources(["electro_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 5,"lens_#00" => 1,"cinema_#00" => 1,])->orderBy(8)->commit();
        // Luftschlag
        $container->modify('small_rocket_#00')->resources(["water_#00" => 10,"meca_parts_#00" => 1,"diode_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,])->orderBy(9)->commit();
        // Feuerwerk
        $container->modify('small_fireworks_#00')->orderBy(10)->commit();
        // Leuchtfeuer
        $container->modify('small_score_#00')->orderBy(11)->commit();
        // Heißluftballon
        $container->modify('small_balloon_#00')->resources(["wood2_#00" => 2,"meca_parts_#00" => 1,"lights_#00" => 1,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,])->orderBy(12)->commit();
        // Riesiger KVF
        $container->modify('small_pmvbig_#00')->orderBy(13)->commit();
        // Riesenrad, todo: add in the description "It's a beautiful and great achievement, designed by a madman from a distant land, it drained a good part of our resources but you are very proud of it."
        $container->modify('small_wheel_#00')->orderBy(14)->commit();
        // Riesige Sandburg
        $container->modify('small_castle_#00')->orderBy(15)->commit();
        //new wonder : Blue Gold Thermal baths
        // Reaktor
        $container->modify('small_arma_#00')->orderBy(16)->commit();


        // Sanktuarium, todo: edit effect : this construction allows now the town to elect a Shaman
        $container->add()
            ->icon('small_spa4souls')->label('Sanktuarium')->description('Auch wenn dir das Spirituelle ein wenig über den Kopf wächst - ein Raum, der dem Wohlbefinden und der Entspannung gewidmet ist, hilft dir, dich weniger um die kommenden Tage zu sorgen. Nun... wenn du denn Zeit hättest, es zu besuchen.')
            ->isTemporary(0)->defense(0)->ap(20)->health(20)->blueprintLevel(0)->resources(["wood2_#00" => 2,"wood_beam_#00" => 3,"ryebag_#00" => 1,])->orderBy(5)
            ->commit($small_spa4souls);
        //Seelenreinigungsquelle, this construction replace the old Hammam (small_spa4souls_#00) for purifying souls
        $container->add()->parentBuilding($small_spa4souls)
            ->icon('item_soul_blue_static')->label('Seelenreinigungsquelle')->description('Ein Ort der Entspannung, der sich hervorragend für die Überführung von Seelen in die ewige Ruhe eignet.')
            ->isTemporary(0)->defense(20)->ap(30)->health(30)->blueprintLevel(0)->resources(["metal_#00" => 1,"rustine_#00" => 1,"ryebag_#00" => 2,"lens_#00" => 1,"oilcan_#00" => 1,])->orderBy(0)
            ->voteLevel(3)->baseVoteText('Du kannst jetzt die Seelen deiner verstorbenen Mitbürger reinigen, um ein wenig zusätzliche Verteidigung zu erhalten.')
            ->upgradeTexts([//todo: now it has evolutions level
                               'Jede gereinigte Seele bringt der Stadt ein wenig mehr Verteidigung.',
                               'Jede gereinigte Seele bringt Verteidigungspunkte und jede gequälte Seele hat weniger Auswinkungen auf den Angriff.',//todo: at this level each tortured soul has 2% impact instead 4%
                               'Jede gereinigte Seele bringt Verteidigungspunkte und 2 Rangpunkte, jede gequälte Seele hat weniger Auswinkungen auf den Angriff.',//todo: at this level each tortured soul has 2% impact instead 4% + each purified soul provide 2 ranking points (brings back the old Hordes soul purification system)
                           ])
            ->commit($item_soul_blue_static);
        // Hammam, todo edit effect: once built Souls are limited to 11km from the city, any souls that were further away at the time of construction move within the 11km zone.
        $container->modify('small_spa4souls_#00')->parentBuilding($item_soul_blue_static)
            ->description('Mit einer solchen Erweiterung der Seelenreinigungsquelle haben es die Seelen noch eiliger, ihn zu erreichen. Ihr könnt sicher sein, dass sie von nun an näher bei euch auftauchen werden.')
            ->defense(20)->blueprintLevel(2)->orderBy(0)
            ->commit();
        // Pool, todo effect: allow to take a bath at Home (provide +1% watch survival chance, cumulative, limited to once a day, until the next watch performed, once watch done, the bonus is resets)
        $container->add()->parentBuilding($small_spa4souls)
            ->icon('small_pool')->label('Pool')->description('Ein großer Pool, der nur für euer Wohlbefinden eingerichtet ist. Wenn man nicht auf seine Mitbürger achtet, die panisch um einen herumlaufen, könnte man den Angriff heute Abend glatt vergessen.')
            ->isTemporary(0)->defense(0)->ap(150)->blueprintLevel(4)->resources(["wood2_#00" => 18,"plate_#00" => 2,"metal_beam_#00" => 1,"water_#00" => 20,"meca_parts_#00" => 2,"tube_#00" => 1,"ryebag_#00" => 2,])->orderBy(1)
            ->commit();
        // Kleiner Friedhof
        $container->modify('small_cemetery_#00')->parentBuilding($small_spa4souls)->ap(42)->health(42)->blueprintLevel(2)->orderBy(2)->commit();
        //Sarg-Katapult
        $container->modify('small_coffin_#00')->ap(85)->health(85)->blueprintLevel(3)->resources(["courroie_#00" => 1,"concrete_wall_#00" => 2,"wire_#00" => 2,"meca_parts_#00" => 3,"wood2_#00" => 5,"metal_#00" => 15,])->commit();
        // Krankenstation
        $container->modify('small_infirmary_#00')->parentBuilding($small_spa4souls)->orderBy(3)->commit();
        // Bauhaus, todo: edit evolutions, now this construction don't has level but its children (Baumarkt) has them. This construction only provide 1 common blueprint / day
        $container->modify('small_refine_#01')->parentBuilding($small_spa4souls)->voteLevel(0)->ap(50)->health(50)->resources(["drug_#00" => 1,"vodka_#00" => 1,"wood_beam_#00" => 5,"ryebag_#00" => 2,])->orderBy(4)->commit();
        // Baumarkt, todo: now this constructions has Bauhaus evolutions levels
        $container->modify('small_strategy_#01')->parentBuilding('small_refine_#01')->description('Wenn alle Bürger an den Diskussionstisch eingeladen werden, entstehen neue Ideen. Die Eliten der Stadt brauchen sie dann nur zu stehlen und als ihre eigenen auszugeben.')
            ->ap(40)->health(40)->blueprintLevel(2)->resources(["wood2_#00" => 10,"water_#00" => 5,"wood_beam_#00" => 2,"metal_beam_#00" => 8,"meca_parts_#00" => 1,"plate_#00" => 2,"ryebag_#00" => 1,"drug_hero_#00" => 1,"vodka_#00" => 1,"coffee_machine_#00" => 1,"cigs_#00" => 1,"trestle_#00" => 2,"chair_basic_#00" => 1,])->orderBy(0)
            ->voteLevel(3)->baseVoteText('Die Stadt erhält nach dem nächsten Angriff einmalig 1 ungewöhnliche Baupläne.')//todo edit effect: provide 1 uncommon blueprint per day at lvl0
            ->upgradeTexts([
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 1 gewöhnliche und 1 ungewöhnliche Baupläne sowie.',//todo edit effect: provide 1 common & 1 uncommon per day at lvl1
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 1 gewöhnliche und 1 seltene Baupläne sowie.',//todo edit effect: provide 1 uncommon & 1 rare blueprints per day at lvl2
                               'Die Stadt erhält nach dem nächsten Angriff einmalig 1 seltene Baupläne - möglicherweise - eine nette Überraschung.',//todo edit effect: provide 1 rare blueprint per day & 10% chance to get an epic blueprint per day at lvl3
                           ])
            ->commit();
        // Krähenstatue
        $container->modify('small_crow_#00')->parentBuilding($small_spa4souls)->orderBy(5)->commit();
        // Voodoo-Puppe
        $container->modify('small_vaudoudoll_#00')->parentBuilding($small_spa4souls)->orderBy(6)->commit();
        // Bokors Guillotine
        $container->modify('small_bokorsword_#00')->parentBuilding($small_spa4souls)->orderBy(7)->commit();
        // Spirituelles Wunder
        $container->modify('small_spiritmirage_#00')->parentBuilding($small_spa4souls)->orderBy(8)->commit();
        // Heiliger Regen
        $container->modify('small_holyrain_#00')->parentBuilding($small_spa4souls)->orderBy(9)->commit();

        $data = $container->toArray();
    }
}