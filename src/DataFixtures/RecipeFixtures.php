<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use App\Entity\BuildingPrototype;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    protected static $building_data = [
        ["name" => "Verstärkte Stadtmauer",'desc' => 'Verbessert die Stadtverteidigung erheblich.', "temporary" => 0,"img" => "small_wallimprove","vp" => 30,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 15,"metal_#00" => 5,], "orderby" => 0, "children" => [
            ["name" => "Großer Graben",'desc' => 'Der Große Graben ist eine sehr wirkungsvolle Verteidigungsmaßnahme, die sich insbesondere auf lange Sicht auszahlt. Der Graben lässt sich mit allerhand Dingen füllen.', "maxLevel" => 5,"temporary" => 0,"img" => "small_gather","vp" => 10,"ap" => 80,"bp" => 0,"rsc" => [], "orderby" => 0,
                "upgradeTexts" => [
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 13.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 21.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 32.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 33.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 51.',
                ], "children" => [
                ["name" => "Wassergraben",'desc' => 'Eine verbesserte Version des Großen Grabens. Muss mit Wasser gefüllt werden...', "temporary" => 0,"img" => "small_waterhole","vp" => 65,"ap" => 50,"bp" => 1,"rsc" => ["water_#00" => 20,], "orderby" => 0, ],
            ]],
            ["name" => "Rasierklingenmauer",'desc' => 'Die Rasierklingenmauer folgt einem ganz einfachen Prinzip: Man nehme allerlei Eisenstücke, schärfe und spitze sie an und verteile sie anschließend über die ganze Stadtmauer. Die Mauer verwandelt sich so in eine überdimensionale Zombiefeile.', "temporary" => 0,"img" => "item_plate","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["metal_#00" => 15,"meca_parts_#00" => 2,], "orderby" => 1],
            ["name" => "Pfahlgraben",'desc' => 'Diese verbesserte Variante des Großen Grabens besteht aus einer großen Anzahl zugespitzter Holzpfähle.', "temporary" => 0,"img" => "small_spears","vp" => 40,"ap" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 8,"wood_beam_#00" => 4,], "orderby" => 2],
            ["name" => "Stacheldraht",'desc' => 'Na ja, dieser Stachel"draht" ist noch simpler als der normale... Der Grund: Der Draht fehlt.', "temporary" => 0,"img" => "small_barbed","vp" => 10,"ap" => 20,"bp" => 0,"rsc" => ["metal_#00" => 2,], "orderby" => 3, "children" => [
                ["name" => "Köder",'desc' => 'Mit diesem an einem Stacheldraht befestigtem Stück Fleisch kann man ein paar Zombies \'ne Zeit lang beschäftigen', "temporary" => 1,"img" => "small_meatbarbed","vp" => 80,"ap" => 10,"bp" => 1,"rsc" => ["bone_meat_#00" => 3,], "orderby" => 0],
            ]],
            ["name" => "Weiterentwickelte Stadtmauer",'desc' => 'Auf die Verteidigungsvorichtungen müssen wir heute Nacht verzichten, aber diese intelligent gebaute und ausbaufähige Stadtmauer hat mehr drauf, als man denkt.', "temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 9,"metal_beam_#00" => 6,], "orderby" => 4],
            ["name" => "Verstärkende Balken",'desc' => 'Mit diesen Metallbalken können die schwächeren Stellen der Stadtmauer verstärkt werden.', "temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"metal_beam_#00" => 3,], "orderby" => 5, "children" => [
                ["name" => "Zackenmauer",'desc' => 'Diese Mauer ist mit einer großen Anzahl an Metallspitzen gespickt, damit die Stadtbewohner beim Angriff um Mitternacht ein paar nette Spieße Zombieschaschlik herstellen können.', "temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 35,"bp" => 1,"rsc" => ["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,], "orderby" => 0, "children" => [
                    ["name" => "Groooße Mauer", 'desc' => 'Was ist besser als eine Mauer?... eine groooße Mauer.',"temporary" => 0,"img" => "item_plate","vp" => 80,"ap" => 50,"bp" => 2,"rsc" => ["wood2_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 0],
                ]],
                ["name" => "Zweite Schicht",'desc' => 'Damit selbst hartnäckige Zombies draußen bleiben, bekommt die gesamte Stadtmauer eine zusätzliche Schicht verpasst.', "temporary" => 0,"img" => "item_plate","vp" => 75,"ap" => 65,"bp" => 1,"rsc" => ["wood2_#00" => 35,"metal_beam_#00" => 5,], "orderby" => 1, "children" => [
                    ["name" => "Dritte Schicht", 'desc' => 'Eine dritte Schicht über der bestehenden Mauer bietet noch besseren Schutz gegen untote Eindringlinge.',"temporary" => 0,"img" => "item_plate","vp" => 95,"ap" => 65,"bp" => 2,"rsc" => ["metal_#00" => 30,"plate_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 0],
                ]],
                ["name" => "Entwicklungsfähige Stadtmauer", 'desc' => 'Die Stadtmauer wird mit einem Eisengestell verstärkt und kann ab sofort jeden Tag ganz leicht um ein Stück erweitert werden!',"maxLevel" => 5,"temporary" => 0,"img" => "item_home_def","vp" => 55,"ap" => 65,"bp" => 3,"rsc" => ["wood2_#00" => 5,"metal_#00" => 20,"concrete_wall_#00" => 1,], "orderby" => 2,
                    "upgradeTexts" => [
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 30.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 35.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 50.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 65.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 80.',
                    ]],
                ["name" => "Betonschicht",'desc' => 'Zu guter Letzt bekommt die Mauer noch eine Schicht aus Beton verpasst. Das sollte die Zombiehorden nun aber wirklich draußen halten.', "temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 60,"bp" => 1,"rsc" => ["concrete_wall_#00" => 6,"metal_beam_#00" => 2,], "orderby" => 3],
            ]],
            ["name" => "Zombiereibe",'desc' => 'Man bedecke eine große Bodenfläche mit einem Meer von zugespitzten und geschärften Metallstücken und schon erhält man die größte Käsereibe der Welt.', "temporary" => 0,"img" => "small_grater","vp" => 55,"ap" => 60,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 20,"plate_#00" => 3,], "orderby" => 6],
            ["name" => "Fallgruben",'desc' => 'Ihr legt eine große Anzahl von verdeckten Fallgruben rund um die Stadt an und wartet bis irgendwas reinfällt. So einfach.', "temporary" => 0,"img" => "small_gather","vp" => 35,"ap" => 50,"bp" => 0,"rsc" => ["wood2_#00" => 10,], "orderby" => 7],
            ["name" => "Zaun", 'desc' => 'Die Stadt baut einen Holzzaun, der - zumindest theoretisch- die Bestien ausreichend verlangsamen sollte.',"temporary" => 0,"img" => "small_fence","vp" => 30,"ap" => 50,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,], "orderby" => 8],
            ["name" => "Holzzaun", 'desc' => 'Verbessert die Stadtverteidigung erheblich.',"temporary" => 0,"img" => "small_fence","vp" => 45,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"wood_beam_#00" => 5,], "orderby" => 9],
            ["name" => "Einseifer", 'desc' => 'Warum ist da vorher noch niemand drauf gekommen? Anstatt Zeit mit Körperpflege zu verschwenden, benutzt eure Seife lieber dazu, die Stadtmauer schön glitschig zu machen. Vor allem im Zusammenspiel mit der Zombiereibe eine "saubere Lösung". Wen stören da schon die Geräusche?', "temporary" => 0,"img" => "small_wallimprove","vp" => 60,"ap" => 40,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 5,"concrete_wall_#00" => 1,], "orderby" => 10],
            ["name" => "Zerstäuber", 'desc' => 'Ein handliches, hydraulisch betriebenes Gerät, das Wasserdampf versprühen kann (und weitere amüsante Chemikalien).',"temporary" => 0,"img" => "small_waterspray","vp" => 0,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 2,], "orderby" => 11, "children" => [
                ["name" => "Säurespray",'desc' => 'Das wird die hübschen Antlitze der Zombies vor der Stadt sicher auch nicht verbessern.', "temporary" => 1,"img" => "small_acidspray","vp" => 40,"ap" => 30,"bp" => 1,"rsc" => ["water_#00" => 2,"pharma_#00" => 3,], "orderby" => 0],
                ["name" => "Spraykanone", 'desc' => 'Oft wird vergessen, dass Zombies ein Gehirn haben. Manchmal sogar zwei, wenn sie Glück haben. Trifft sich gut: Das mit dieser Kanone geschossene Konzentrat hat die erstaunliche Fähigkeit, Gehirne in Matsch zu verwandeln.', "temporary" => 1,"img" => "small_gazspray","vp" => 150,"ap" => 40,"bp" => 2,"rsc" => ["water_#00" => 2,"pharma_#00" => 5,"drug_#00" => 1,], "orderby" => 1],
            ]],
            ["name" => "Rüstungsplatten", 'desc' => 'Ein simpler Verteidigungsgegenstand, aber du wirst ihn zu schätzen wissen, wenn dein Ex-Nachbar Kevo versuchen sollte, an deinem Gehirn rumzuknabbern..',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,], "orderby" => 12],
            ["name" => "Rüstungsplatten 2.0", 'desc' => 'Diese Verbesserung ist nicht der ganz große Wurf, aber sie erfüllt ihren Zweck: Sie verhindert, dass du zu schnell stirbst.',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["metal_#00" => 10,], "orderby" => 13],
            ["name" => "Rüstungsplatten 3.0", 'desc' => 'Simpel aber stabil: Was will man mehr?',"temporary" => 0,"img" => "item_plate","vp" => 40,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 10,], "orderby" => 14],
            ["name" => "Sperrholz", 'desc' => 'Sperrholz. Du hast es nur genommen, weil du wirklich nichts besseres zu tun hattest. Dir war klar, dass es unnütz sein würde, aber das hat dich trotzdem nicht davon abgehalten. Na dann mal los...',"temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 15],
            ["name" => "Extramauer", 'desc' => 'Es war sicher kein Geniestreich dieses Bauwerk zu beginnen. Aber gut, letztlich haben alle zugestimmt und eine weitere große Mauer ist sicher keine schlechte Idee.',"temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 30,"bp" => 1,"rsc" => ["wood2_#00" => 15,"metal_#00" => 15,], "orderby" => 16],
            // TODO: Night watch action
            ["name" => "Brustwehr", 'desc' => '',"temporary" => 0,"img" => "small_round_path","vp" => 0,"ap" => 20,"bp" => 0,"rsc" => ["wood2_#00" => 6,"metal_#00" => 2,"meca_parts_#00" => 1,], "orderby" => 17],
        ]],

        ["name" => "Pumpe",'desc' => 'Die Pumpe ist die Grundvoraussetzung für alle auf Wasser basierenden Konstruktionen! Darüber hinaus steigert sie die Wasserergiebigkeit des Brunnens um ein Vielfaches.', "maxLevel" => 5,"temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 25,"bp" => 0,"rsc" => ["metal_#00" => 8,"tube_#00" => 1,], "orderby" => 1,
            "upgradeTexts" => [
                'Der Brunnen der Stadt wird einmalig um 5 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 40 Rationen Wasser aufgefüllt',
            ], "children" => [
            ["name" => "Wasserreiniger", 'desc' => 'Verwandelt in der Wüste gefundenes Kanisterwasser in Trinkwasser.',"temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 75,"bp" => 0,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 6,"tube_#00" => 3,], "orderby" => 0, "children" => [
                ["name" => "Minen", 'desc' => 'Raketenpulver, Zünder und reines Wasser: Das sind die Zutaten für einen saftigen Brei aus vermodertem Fleisch diese Nacht. Eine mächtige Verteidigung, leider kann sie nur einmal verwendet werden.',"temporary" => 1,"img" => "item_bgrenade","vp" => 115,"ap" => 50,"bp" => 2,"rsc" => ["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,], "orderby" => 0],
                ["name" => "Wasserfilter", 'desc' => 'Verbessert die Ausbeute des Wasserreinigers erheblich (hoher Wirkungsgrad).',"temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 60,"bp" => 3,"rsc" => ["metal_#00" => 10,"electro_#00" => 2,"fence_#00" => 1,], "orderby" => 1],
            ]],
            ["name" => "Gemüsebeet", 'desc' => 'Mit einem Gemüsebeet könnt ihr leckere Früchte und nicht allzu verschimmeltes Gemüse anbauen. Ist zwar kein Bio, macht aber satt.',"temporary" => 0,"img" => "item_vegetable_tasty","vp" => 0,"ap" => 60,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,], "orderby" => 1, "children" => [
                ["name" => "Granatapfel", 'desc' => 'Ein gewaltiger wissenschaftlicher Durchbruch: Durch die Aussaat von Dynamitstangen und gaaanz vorsichtiges Gießen, könnt ihr Granatäpfel anbauen!',"temporary" => 0,"img" => "item_bgrenade","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,], "orderby" => 0],
                ["name" => "Dünger", 'desc' => 'Verbessert die Ausbeute des Gemüsebeets erheblich.',"temporary" => 0,"img" => "item_digger","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,], "orderby" => 1],
            ]],
            ["name" => "Brunnenbohrer", 'desc' => 'Mit diesem selbstgebauten Bohrer kann die Stadt ihre Wasserreserven beträchtlich vergrößern.',"temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 60,"bp" => 0,"rsc" => ["wood_beam_#00" => 7,"metal_beam_#00" => 2,], "orderby" => 2, "children" => [
                ["name" => "Projekt Eden",'desc' => 'Eine radikale Lösung, wenn mal das Wasser ausgehen sollte: Mit ein paar gezielten Sprengungen können tiefergelegene Wasserschichten erschlossen und das Wasserreservoir vergrößert werden.', "temporary" => 0,"img" => "small_eden","vp" => 0,"ap" => 65,"bp" => 3,"rsc" => ["explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 8,], "orderby" => 0],
            ]],
            ["name" => "Wasserleitungsnetz",'desc' => 'Nach der Pumpe könnt ihr mit dem Wasserleitungsnetz die auf Wasser basierenden Verteidigungsmechanismen angehen! Zusätzlich vergrößert das Netz auch die Wasserreserven des Brunnens.', "temporary" => 0,"img" => "item_firework_tube","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,], "orderby" => 3, "children" => [
                ["name" => "Kärcher",'desc' => 'Dieser leistungsstarke Dampfstrahlreiniger versprüht feinen, siedend heißen Wasserdampf. Deine muffigen Freunde werden beim Anblick dieses Geräts wortwörtlich dahinschmelzen.', "temporary" => 0,"img" => "small_waterspray","vp" => 50,"ap" => 50,"bp" => 0,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 7,], "orderby" => 0],
                ["name" => "Kreischender Rotor",'desc' => 'Es handelt sich um ein einfallsreiches und SEHR effektives System! Zwei schnell kreisende und mit geschliffenen Eisenstangen bestückte Drehscheiben, die von einem Kolbenmechanismus angetrieben werden, zerfetzen alles und jeden, der sich im Toreingang befindet!', "temporary" => 0,"img" => "small_grinder","vp" => 50,"ap" => 55,"bp" => 1,"rsc" => ["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,], "orderby" => 1],
                ["name" => "Sprinkleranlage",'desc' => 'Wie jeder weiß, wird eine Sprinkleranlage für gewöhnlich im Garten eingesetzt. Die wenigsten wissen jedoch, dass sie sich auch hervorragend gegen Zombiehorden eignet. Einziger Wermutstropfen: Die Anlage verbraucht relativ viel Wasser.', "temporary" => 0,"img" => "small_sprinkler","vp" => 150,"ap" => 85,"bp" => 3,"rsc" => ["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,], "orderby" => 2],
                ["name" => "Dusche",'desc' => 'Nein, ganz ehrlich, dieser... dieser... Geruch ist einfach nicht auszuhalten: Nimm eine Dusche. Sofort!', "temporary" => 0,"img" => "small_shower","vp" => 0,"ap" => 25,"bp" => 2,"rsc" => ["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,], "orderby" => 3],
            ]],
            ["name" => "Wasserturm","maxLevel" => 5,'desc' => 'Mit dieser revolutionären Verteidigungsanlage ist die Stadt imstande, große Wasserdampfwolken zu erzeugen. Ein wohlig-warmes Dampfbad wird den vor den Stadtmauern herumlungernden Zombies gut tun und sie grundlegend "reinigen". Die Leistung kann mit ein wenig Feintuning noch gesteigert werden.', "temporary" => 0,"img" => "item_tube","vp" => 70,"ap" => 60,"bp" => 3,"rsc" => ["water_#00" => 40,"tube_#00" => 7,"metal_beam_#00" => 10,], "orderby" => 4,
                "upgradeTexts" => [
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 224.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 280.',
                ]],
            ["name" => "Wasserfänger",'desc' => 'Wenn es um Wasser geht, zählt jeder Tropfen. Dieses Bauwerk fügt dem Brunnen +2 Rationen Wasser hinzu und kann jeden Tag gebaut werden.', "temporary" => 1,"img" => "item_tube","vp" => 0,"ap" => 12,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,], "orderby" => 5],
            ["name" => "Wasserkanone",'desc' => 'Ein hübscher kleiner Wasserstrahl, um die wachsende Zombiemeute beim Stadttor zu sprengen.', "temporary" => 0,"img" => "small_watercanon","vp" => 80,"ap" => 40,"bp" => 2,"rsc" => ["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 6],
            ["name" => "Apfelbaum",'desc' => 'Dieser Apfelbaum erinnert eher an einen verkümmerten und halbtoten Busch, aber er trägt wunderschöne blaue Äpfel. Äh, Moment mal,... wie bitte?', "temporary" => 0,"img" => "small_appletree","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"wood_beam_#00" => 1,], "orderby" => 7],
            ["name" => "Schleuse",'desc' => 'Selbst das Abwasser der Stadt kann noch genutzt werden: Wir müssen bloß alle Toiletten der Stadt über ein ausgeklügeltes System aus Rohren und Schlitten miteinander verbinden und dann um Mitternacht die Schleusen öffnen. Hat auch jeder sein Zelt korrekt aufgebaut?', "temporary" => 0,"img" => "small_shower","vp" => 60,"ap" => 50,"bp" => 1,"rsc" => ["water_#00" => 15,"wood2_#00" => 10,], "orderby" => 8],
            ["name" => "Wasserfall",'desc' => 'Anfangs war es nur zur Dekontaminierung gedacht. Aber dann stellte es sich als äußerst effizientes Mittel gegen unsere pestilenten Freunde heraus. Man gebe noch einen Spritzer Kokosnuss-Duschgel hinzu und siehe da: Die meterhohen Leichenstapel, für die DU verantwortlich bist, verströmen ein betörendes Aroma.', "temporary" => 0,"img" => "small_shower","vp" => 35,"ap" => 20,"bp" => 1,"rsc" => ["water_#00" => 10,], "orderby" => 9],
            ["name" => "Wünschelrakete",'desc' => 'Ein selbstgebauer Raketenwerfer feuert in den Boden: Denk mal drüber nach! Der Legende nach wollte der geistige Vater dieses Bauprojekts eigentlich den "Rocket Jump" erfinden. Egal, +60 Rationen Wasser werden so zum Brunnen hinzugefügt.', "temporary" => 0,"img" => "small_rocketperf","vp" => 0,"ap" => 90,"bp" => 3,"rsc" => ["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 10],
            ["name" => "Wünschelrute",'desc' => 'Dass "Hightech" nicht nur auf die Dezimierung von Zombiehorden beschränkt ist, beweist dieses Gebäude... Es fügt +100 Rationen Wasser zum Brunnen hinzu.', "temporary" => 0,"img" => "small_waterdetect","vp" => 0,"ap" => 130,"bp" => 4,"rsc" => ["electro_#00" => 5,"wood_beam_#00" => 5,"metal_beam_#00" => 10,], "orderby" => 11],
        ]],

        ["name" => "Metzgerei",'desc' => 'In der Metzgerei könnt ihr eure kleinen treuen Begleiter (Hunde, Katzen, Schlangen ...) in Lebensmittel verwandeln. Da gibt es doch tatsächlich noch Leute, die Vegetarier sind...', "temporary" => 0,"img" => "item_meat","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 9,"metal_#00" => 4,], "orderby" => 2, "children" => [
            ["name" => "Kremato-Cue",'desc' => 'Jeder weiß, was ein Krematorium ist, richtig? Und jeder weiß, wozu man einen Barbecuegrill verwendet? Dann einfach eins und eins zusammenzählen, dann wisst ihr auch wie ein "Kremato-Cue" funktioniert. Die Zeiten des Hungerns sind jedenfalls vorbei...', "temporary" => 0,"img" => "item_hmeat","vp" => 0,"ap" => 45,"bp" => 2,"rsc" => ["wood_beam_#00" => 8,"metal_beam_#00" => 1,], "orderby" => 0],
        ]],

        // TODO: Upgrade effect
        ["name" => "Werkstatt","maxLevel" => 5,'desc' => '', "temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 25,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 3,
            "upgradeTexts" => [
                'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 20% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt.',
            ], "children" => [
            ["name" => "Verteidigungsanlage","maxLevel" => 5,'desc' => '', "temporary" => 0,"img" => "item_meca_parts","vp" => 0,"ap" => 50,"bp" => 0,"rsc" => ["meca_parts_#00" => 4,"wood_beam_#00" => 8,"metal_beam_#00" => 8,], "orderby" => 0,
                "upgradeTexts" => [
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 250%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 300%.',
                ]],
            ["name" => "Kanonenhügel",'desc' => '', "temporary" => 0,"img" => "small_dig","vp" => 30,"ap" => 50,"bp" => 0,"rsc" => ["concrete_wall_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 1,], "orderby" => 1, "children" => [
                ["name" => "Steinkanone",'desc' => '', "temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 60,"bp" => 1,"rsc" => ["tube_#00" => 1,"electro_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 0],
                ["name" => "Selbstgebaute Railgun",'desc' => '', "temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"tube_#00" => 1,"electro_#00" => 1,"metal_beam_#00" => 10,], "orderby" => 1],
                ["name" => "Blechplattenwerfer",'desc' => '', "temporary" => 0,"img" => "small_canon","vp" => 60,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"plate_#00" => 3,"explo_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 1,], "orderby" => 2],
                ["name" => "Brutale Kanone",'desc' => '', "temporary" => 1,"img" => "small_canon","vp" => 50,"ap" => 25,"bp" => 0,"rsc" => ["plate_#00" => 1,"metal_beam_#00" => 1,], "orderby" => 3],
            ]],
            ["name" => "Holzbalkendrehkreuz",'desc' => '', "temporary" => 0,"img" => "item_wood_beam","vp" => 10,"ap" => 15,"bp" => 0,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 1,], "orderby" => 2],
            ["name" => "Manufaktur",'desc' => '', "temporary" => 0,"img" => "small_factory","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,], "orderby" => 3],
            ["name" => "Kreischende Sägen",'desc' => '', "temporary" => 0,"img" => "small_saw","vp" => 45,"ap" => 65,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"rustine_#00" => 3,"metal_beam_#00" => 2,], "orderby" => 4],
            ["name" => "Baustellenbuch",'desc' => '', "temporary" => 0,"img" => "item_rp_book2","vp" => 0,"ap" => 15,"bp" => 0,"rsc" => ["table_#00" => 1,], "orderby" => 5, "children" => [
                ["name" => "Bauhaus","maxLevel" => 3,'desc' => '', "temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 75,"bp" => 0,"rsc" => ["drug_#00" => 1,"vodka_#00" => 1,"wood_beam_#00" => 10,],
                "upgradeTexts" => [
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 4 gewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 2 gewöhnliche und 2 ungewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 2 ungewöhnliche und 2 seltene Baupläne sowie - möglicherweise - eine nette Überraschung.',
                ], "orderby" => 0],
            ]],
            ["name" => "Galgen",'desc' => '', "temporary" => 0,"img" => "r_dhang","vp" => 0,"ap" => 13,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"chain_#00" => 1,], "orderby" => 6],
            ["name" => "Schlachthof",'desc' => '', "temporary" => 0,"img" => "small_slaughterhouse","vp" => 35,"ap" => 40,"bp" => 1,"rsc" => ["concrete_wall_#00" => 2,"metal_beam_#00" => 10,], "orderby" => 7],
            ["name" => "Pentagon",'desc' => '', "temporary" => 0,"img" => "item_shield","vp" => 8,"ap" => 55,"bp" => 3,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 10,], "orderby" => 8,
                "upgradeTexts" => [
                    'Die Verteidigung der Stadt wird um 12% erhöht.',
                    'Die Verteidigung der Stadt wird um 14% erhöht.'
                ]],
            ["name" => "Kleines Cafe",'desc' => '', "temporary" => 1,"img" => "small_cafet","vp" => 0,"ap" => 6,"bp" => 0,"rsc" => ["water_#00" => 1,"wood2_#00" => 2,"pharma_#00" => 1,], "orderby" => 9],
            ["name" => "Kleiner Friedhof",'desc' => '', "temporary" => 0,"img" => "small_cemetery","vp" => 60,"ap" => 36,"bp" => 1,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 10,], "orderby" => 10, "children" => [
                ["name" => "Sarg-Katapult",'desc' => '', "temporary" => 0,"img" => "small_coffin","vp" => 60,"ap" => 100,"bp" => 4,"rsc" => ["courroie_#00" => 1,"meca_parts_#00" => 5,"wood2_#00" => 5,"metal_#00" => 15,], "orderby" => 0],
            ]],
            ["name" => "Kantine",'desc' => '', "temporary" => 0,"img" => "small_cafet","vp" => 0,"ap" => 20,"bp" => 1,"rsc" => ["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,], "orderby" => 11],
            ["name" => "Labor",'desc' => '', "temporary" => 0,"img" => "item_acid","vp" => 0,"ap" => 30,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"pharma_#00" => 5,"wood_beam_#00" => 3,"metal_beam_#00" => 10,], "orderby" => 12],
            ["name" => "Hühnerstall",'desc' => '', "temporary" => 0,"img" => "small_chicken","vp" => 0,"ap" => 25,"bp" => 3,"rsc" => ["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,], "orderby" => 13],
            ["name" => "Krankenstation",'desc' => '', "temporary" => 0,"img" => "small_infirmary","vp" => 0,"ap" => 40,"bp" => 3,"rsc" => ["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 14],
            ["name" => "Baumarkt",'desc' => '', "temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 30,"bp" => 4,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 10,], "orderby" => 15],
            ["name" => "Bollwerk",'desc' => '', "temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 60,"bp" => 3,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 16],
        ]],

        ["name" => "Wachturm","maxLevel" => 5, 'desc' => '', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 12,"bp" => 0,"rsc" => ["wood2_#00" => 3,"metal_#00" => 2,], "orderby" => 4,
            "upgradeTexts" => [
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 3km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 6km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 1km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 2km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
            ], "children" => [
            // TODO: UI
            ["name" => "Katapult",'desc' => '', "temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,], "orderby" => 0, "children" => [
                ["name" => "Verbesserter Katapult",'desc' => '', "temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 2,], "orderby" => 0],
            ]],
            ["name" => "Scanner",'desc' => '', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["pile_#00" => 1,"meca_parts_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,], "orderby" => 1],
            // TODO: Unveil zombie count
            ["name" => "Verbesserte Karte",'desc' => '', "temporary" => 0,"img" => "item_electro","vp" => 0,"ap" => 15,"bp" => 1,"rsc" => ["pile_#00" => 2,"metal_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,], "orderby" => 2],
            ["name" => "Rechenmaschine",'desc' => '', "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20,"bp" => 1,"rsc" => ["rustine_#00" => 1,"electro_#00" => 1,], "orderby" => 3],
            ["name" => "Forschungsturm","maxLevel" => 5,'desc' => '', "temporary" => 0,"img" => "small_gather","vp" => 0,"ap" => 30,"bp" => 1,"rsc" => ["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,], "orderby" => 4,
                "upgradeTexts" => [
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 37%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 49%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 61%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 73%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 85%.',
                ]],
            ["name" => "Notfallkonstruktion",'desc' => '', "temporary" => 0,"img" => "status_terror","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 7,], "orderby" => 5, "children" => [
                ["name" => "Notfallabstützung",'desc' => '', "temporary" => 1,"img" => "item_wood_plate","vp" => 40,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 8,], "orderby" => 0],
                ["name" => "Verteidigungspfähle",'desc' => '', "temporary" => 1,"img" => "small_trap","vp" => 25,"ap" => 12,"bp" => 0,"rsc" => ["wood2_#00" => 6,], "orderby" => 1],
                ["name" => "Guerilla",'desc' => '', "temporary" => 1,"img" => "small_trap","vp" => 50,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 2,"metal_#00" => 1,], "orderby" => 2],
                ["name" => "Abfallberg",'desc' => '', "temporary" => 1,"img" => "small_dig","vp" => 5,"ap" => 10,"bp" => 0,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,], "orderby" => 3, "children" => [
                    ["name" => "Trümmerberg",'desc' => '', "temporary" => 1,"img" => "small_dig","vp" => 60,"ap" => 40,"bp" => 1,"rsc" => ["metal_#00" => 2,], "orderby" => 0],
                ]],
                ["name" => "Wolfsfalle",'desc' => '', "temporary" => 1,"img" => "small_trap","vp" => 40,"ap" => 20,"bp" => 0,"rsc" => ["metal_#00" => 2,"hmeat_#00" => 3,], "orderby" => 4],
                ["name" => "Sprengfalle",'desc' => '', "temporary" => 1,"img" => "small_tnt","vp" => 35,"ap" => 20,"bp" => 0,"rsc" => ["explo_#00" => 3,], "orderby" => 5],
                ["name" => "Nackte Panik",'desc' => '', "temporary" => 1,"img" => "status_terror","vp" => 50,"ap" => 25,"bp" => 0,"rsc" => ["water_#00" => 4,"wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 6],
                ["name" => "Dollhouse",'desc' => '', "temporary" => 1,"img" => "small_bamba","vp" => 75,"ap" => 50,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,"radio_on_#00" => 3,], "orderby" => 7],
                ["name" => "Voodoo-Puppe",'desc' => '', "temporary" => 0,"img" => "small_vaudoudoll","vp" => 65,"ap" => 40,"bp" => 0,"rsc" => ["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_red_#00" => 2,], "orderby" => 8],
                ["name" => "Bokors Guillotine",'desc' => '', "temporary" => 0,"img" => "small_bokorsword","vp" => 100,"ap" => 60,"bp" => 0,"rsc" => ["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_red_#00" => 3,], "orderby" => 9],
                ["name" => "Spirituelles Wunder",'desc' => '', "temporary" => 0,"img" => "small_spiritmirage","vp" => 80,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_red_#00" => 2,], "orderby" => 10],
                ["name" => "Heiliger Regen",'desc' => '', "temporary" => 1,"img" => "small_holyrain","vp" => 200,"ap" => 40,"bp" => 0,"rsc" => ["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_red_#00" => 4,], "orderby" => 11],
            ]],
            ["name" => "Wächter-Turm",'desc' => '', "temporary" => 0,"img" => "small_watchmen","vp" => 15,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,], "orderby" => 6, "children" => [
                // TODO: NW effect
                ["name" => "Schießstand",'desc' => '', "temporary" => 0,"img" => "small_tourello","vp" => 50,"ap" => 25,"bp" => 2,"rsc" => ["water_#00" => 30,"tube_#00" => 2,"wood_beam_#00" => 1,"metal_beam_#00" => 2,], "orderby" => 0],
                // TODO: NW effect
                ["name" => "Kleiner Tribok",'desc' => '', "temporary" => 0,"img" => "small_catapult3","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 4,"meca_parts_#00" => 2,"plate_#00" => 2,"tube_#00" => 1,], "orderby" => 1],
                // TODO: NW effect
                ["name" => "Kleine Waffenschmiede",'desc' => '', "temporary" => 0,"img" => "small_armor","vp" => 0,"ap" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 15,"plate_#00" => 2,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,], "orderby" => 2],
                // TODO: NW effect
                ["name" => "Schwedische Schreinerei",'desc' => '', "temporary" => 0,"img" => "small_ikea","vp" => 0,"ap" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 15,"metal_#00" => 10,"plate_#00" => 4,"concrete_wall_#00" => 2,"wood_beam_#00" => 5,], "orderby" => 3],
            ]],
            ["name" => "Krähennest",'desc' => '', "temporary" => 0,"img" => "small_watchmen","vp" => 10,"ap" => 36,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,], "orderby" => 7],
        ]],

        ["name" => "Fundament",'desc' => '', "temporary" => 0,"img" => "small_building","vp" => 0,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 5, "children" => [
            ["name" => "Großer Umbau",'desc' => '', "temporary" => 0,"img" => "small_moving","vp" => 300,"ap" => 300,"bp" => 3,"rsc" => ["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 10,"metal_beam_#00" => 10,], "orderby" => 0],
            ["name" => "Bohrturm",'desc' => '', "temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 70,"bp" => 3,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 15,], "orderby" => 1],
            ["name" => "Falsche Stadt",'desc' => '', "temporary" => 0,"img" => "small_falsecity","vp" => 400,"ap" => 400,"bp" => 3,"rsc" => ["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,], "orderby" => 2],
            ["name" => "Wasserhahn",'desc' => '', "temporary" => 0,"img" => "small_valve","vp" => 0,"ap" => 130,"bp" => 3,"rsc" => ["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,], "orderby" => 3],
            ["name" => "Vogelscheuche",'desc' => '', "temporary" => 0,"img" => "small_scarecrow","vp" => 25,"ap" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 10,"rustine_#00" => 2,], "orderby" => 4],
            ["name" => "Müllhalde",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 70,"bp" => 0,"rsc" => ["concrete_wall_#00" => 5,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 5, "children" => [
                ["name" => "Holzabfall",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 4],
                ["name" => "Metallabfall",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,], "orderby" => 5],
                ["name" => "Waffenabfall",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,], "orderby" => 2],
                ["name" => "Biomüll",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["wood2_#00" => 15,], "orderby" => 3],
                ["name" => "Rüstungsabfall",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 40,"bp" => 2,"rsc" => ["metal_beam_#00" => 3,"metal_#00" => 5,], "orderby" => 1],
                ["name" => "Verbesserte Müllhalde",'desc' => '', "temporary" => 0,"img" => "small_trash","vp" => 75,"ap" => 120,"bp" => 4,"rsc" => ["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 0],
                ["name" => "Tierabfälle",'desc' => '', "temporary" => 0,"img" => "small_howlingbait","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 10,], "orderby" => 6],
                ["name" => "Müll für Alle",'desc' => '', "temporary" => 0,"img" => "small_trashclean","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"trestle_#00" => 2,], "orderby" => 7],
            ]],
            ["name" => "Fleischkäfig",'desc' => '', "temporary" => 0,"img" => "small_fleshcage","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"wood_beam_#00" => 1,], "orderby" => 6],
            ["name" => "Leuchtturm",'desc' => '', "temporary" => 0,"img" => "small_lighthouse","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 7],
            ["name" => "Befestigungen",'desc' => '', "temporary" => 0,"img" => "small_city_up","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 8],
            ["name" => "Leuchtfeuer",'desc' => '', "temporary" => 1,"img" => "small_score","vp" => 30,"ap" => 15,"bp" => 2,"rsc" => ["lights_#00" => 1,"wood2_#00" => 5,], "orderby" => 9],
            ["name" => "Bürgergericht",'desc' => '', "temporary" => 0,"img" => "small_court","vp" => 0,"ap" => 12,"bp" => 2,"rsc" => ["wood2_#00" => 6,"metal_beam_#00" => 15,"table_#00" => 1,], "orderby" => 10],
            ["name" => "Ministerium für Sklaverei",'desc' => '', "temporary" => 0,"img" => "small_slave","vp" => 0,"ap" => 45,"bp" => 4,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,], "orderby" => 11],
            ["name" => "Tunnelratte",'desc' => '', "temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 170,"bp" => 4,"rsc" => ["concrete_wall_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 12],
            ["name" => "Kino",'desc' => '', "temporary" => 0,"img" => "small_cinema","vp" => 0,"ap" => 75,"bp" => 4,"rsc" => ["electro_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 5,"machine_1_#00" => 1,"machine_2_#00" => 1,], "orderby" => 13],
            ["name" => "Heißluftballon",'desc' => '', "temporary" => 0,"img" => "small_balloon","vp" => 0,"ap" => 80,"bp" => 4,"rsc" => ["meca_parts_#00" => 6,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,], "orderby" => 14],
            ["name" => "Labyrinth",'desc' => '', "temporary" => 0,"img" => "small_labyrinth","vp" => 150,"ap" => 200,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,], "orderby" => 15],
            // TODO: Temp Def
            ["name" => "Alles oder nichts",'desc' => '', "temporary" => 0,"img" => "small_lastchance","vp" => 55,"ap" => 150,"bp" => 3,"rsc" => ["meca_parts_#00" => 4,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "orderby" => 16],
            ["name" => "Luftschlag",'desc' => '', "temporary" => 1,"img" => "small_rocket","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,], "orderby" => 17],
            // TODO: Destroyable, infect half citizen, kill zombies around city
            ["name" => "Feuerwerk",'desc' => '', "temporary" => 0,"img" => "small_fireworks","vp" => 300,"ap" => 90,"bp" => 0,"rsc" => ["firework_powder_#00" => 1,"firework_tube_#00" => 1,"firework_box_#00" => 2], "orderby" => 18],
            ["name" => "Altar",'desc' => '', "temporary" => 0,"img" => "small_redemption","vp" => 0,"ap" => 24,"bp" => 2,"rsc" => ["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,], "orderby" => 19],
            ["name" => "Riesiger KVF",'desc' => '', "temporary" => 0,"img" => "small_pmvbig","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 30,], "orderby" => 20],
            ["name" => "Krähenstatue",'desc' => '', "temporary" => 0,"img" => "small_crow","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["hmeat_#00" => 3,"wood_beam_#00" => 35,], "orderby" => 21],
            ["name" => "Riesenrad",'desc' => '', "temporary" => 0,"img" => "small_wheel","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,], "orderby" => 22],
            ["name" => "Riesige Sandburg",'desc' => '', "temporary" => 0,"img" => "small_castle","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,], "orderby" => 23],
            // TODO: Destroyable, kill
            ["name" => "Reaktor",'desc' => '', "temporary" => 0,"img" => "small_arma","vp" => 500,"ap" => 100,"bp" => 4,"rsc" => ["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,], "orderby" => 24],
        ]],

        ["name" => "Portal",'desc' => '', "temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 16,"bp" => 0,"rsc" => ["metal_#00" => 2,], "orderby" => 6, "children" => [
            ["name" => "Kolbenschließmechanismus",'desc' => '', "temporary" => 0,"img" => "small_door_closed","vp" => 30,"ap" => 24,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,], "orderby" => 0, "children" => [
                ["name" => "Automatiktür",'desc' => '', "temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 10,"bp" => 1,"rsc" => [], "orderby" => 0, ],
            ]],
            ["name" => "Torpanzerung",'desc' => '', "temporary" => 0,"img" => "item_plate","vp" => 20,"ap" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 3,], "orderby" => 1,],
            ["name" => "Ventilationssystem",'desc' => '', "temporary" => 0,"img" => "small_ventilation","vp" => 20,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,], "orderby" => 2,],
        ]],
        
        ["name" => "Hammam",'desc' => '', "temporary" => 0,"img" => "small_spa4souls","vp" => 28,"ap" => 20,"bp" => 0,"rsc" => ["wood2_#00" => 2,"plate_#00" => 2,], "orderby" => 7],
    ];

    protected static $recipe_data = [
        'ws001' => ['type' => Recipe::WorkshopType, 'in' => 'repair_kit_part_#00', 'out' => 'repair_kit_#00', 'action' => 'Wandeln'],
        'ws002' => ['type' => Recipe::WorkshopType, 'in' => 'can_#00',             'out' => 'can_open_#00', 'action' => 'Öffnen'],
        'ws003' => ['type' => Recipe::WorkshopType, 'in' => 'plate_raw_#00',       'out' => 'plate_#00', 'action' => 'Wandeln'],
        'ws004' => ['type' => Recipe::WorkshopType, 'in' => 'wood_log_#00',        'out' => 'wood2_#00', 'action' => 'Wandeln'],
        'ws005' => ['type' => Recipe::WorkshopType, 'in' => 'wood_bad_#00',        'out' => 'wood2_#00', 'action' => 'Wandeln'],
        'ws006' => ['type' => Recipe::WorkshopType, 'in' => 'wood2_#00',           'out' => 'wood_beam_#00', 'action' => 'Wandeln'],
        'ws007' => ['type' => Recipe::WorkshopType, 'in' => 'wood_beam_#00',       'out' => 'wood2_#00', 'action' => 'Wandeln'],
        'ws008' => ['type' => Recipe::WorkshopType, 'in' => 'metal_bad_#00',       'out' => 'metal_#00', 'action' => 'Wandeln'],
        'ws009' => ['type' => Recipe::WorkshopType, 'in' => 'metal_#00',           'out' => 'metal_beam_#00', 'action' => 'Wandeln'],
        'ws010' => ['type' => Recipe::WorkshopType, 'in' => 'metal_beam_#00',      'out' => 'metal_#00', 'action' => 'Wandeln'],
        'ws011' => ['type' => Recipe::WorkshopType, 'in' => 'electro_box_#00',     'out' => [ 'pile_#00', 'pilegun_empty_#00', 'electro_#00', 'meca_parts_#00', 'tagger_#00', 'deto_#00' ], 'action' => 'Zerlegen' ],
        'ws012' => ['type' => Recipe::WorkshopType, 'in' => 'mecanism_#00',        'out' => [ 'metal_#00', 'tube_#00', 'metal_bad_#00', 'meca_parts_#00' ], 'action' => 'Zerlegen' ],
        'ws013' => ['type' => Recipe::WorkshopType, 'in' => 'chest_#00',           'out' => [ 'drug_#00', 'bandage_#00', 'pile_#00', 'pilegun_empty_#00', 'vodka_de_#00', 'vodka_#00', 'pharma_#00', 'explo_#00', 'lights_#00', 'drug_hero_#00', 'rhum_#00' ], 'action' => 'Öffnen' ],
        'ws014' => ['type' => Recipe::WorkshopType, 'in' => 'chest_xl_#00',        'out' => [ 'watergun_opt_part_#00', 'pilegun_upkit_#00', 'pocket_belt_#00', 'cutcut_#00', 'chainsaw_part_#00', 'mixergun_part_#00', 'big_pgun_part_#00', 'lawn_part_#00' ], 'action' => 'Öffnen' ],
        'ws015' => ['type' => Recipe::WorkshopType, 'in' => 'chest_tools_#00',     'out' => [ 'pile_#00', 'meca_parts_#00', 'rustine_#00', 'tube_#00', 'pharma_#00', 'explo_#00', 'lights_#00' ], 'action' => 'Öffnen' ],
        'ws016' => ['type' => Recipe::WorkshopType, 'in' => 'chest_food_#00',      'out' => [ 'food_bag_#00', 'can_#00', 'meat_#00', 'hmeat_#00', 'vegetable_#00' ], 'action' => 'Öffnen' ],
        'ws017' => ['type' => Recipe::WorkshopType, 'in' => 'deco_box_#00',        'out' => [ 'door_#00', 'chair_basic_#00', 'trestle_#00', 'table_#00', 'chair_#00' ], 'action' => 'Öffnen' ],

        'com001' => ['type' => Recipe::ManualAnywhere, 'out' => 'coffee_machine_#00',     'provoking' => 'coffee_machine_part_#00','in' => ['coffee_machine_part_#00', 'cyanure_#00', 'electro_#00', 'meca_parts_#00', 'rustine_#00', 'metal_#00', 'tube_#00' ] ],
        'com002' => ['type' => Recipe::ManualAnywhere, 'out' => 'music_#00',              'provoking' => 'music_part_#00',         'in' => ['music_part_#00', 'pile_#00', 'electro_#00'] ],
        'com003' => ['type' => Recipe::ManualAnywhere, 'out' => 'guitar_#00',             'provoking' => ['wire_#00','oilcan_#00'],'in' => ['wire_#00', 'oilcan_#00', 'staff2_#00'] ],
        'com004' => ['type' => Recipe::ManualAnywhere, 'out' => 'car_door_#00',           'provoking' => 'car_door_part_#00',      'in' => ['car_door_part_#00', 'meca_parts_#00', 'rustine_#00', 'metal_#00'] ],
        'com005' => ['type' => Recipe::ManualAnywhere, 'out' => 'torch_#00',              'provoking' => 'lights_#00',             'in' => ['lights_#00', 'wood_bad_#00'] ],
        'com006' => ['type' => Recipe::ManualAnywhere, 'out' => 'wood_plate_#00',         'provoking' => 'wood_plate_part_#00',    'in' => ['wood_plate_part_#00', 'wood2_#00'] ],
        'com007' => ['type' => Recipe::ManualAnywhere, 'out' => 'concrete_wall_#00',      'provoking' => 'concrete_#00',           'in' => ['concrete_#00', 'water_#00'] ],
        'com008' => ['type' => Recipe::ManualAnywhere, 'out' => 'chama_tasty_#00',        'provoking' => 'torch_#00',              'in' => ['chama_#00'] ],
        'com009' => ['type' => Recipe::ManualAnywhere, 'out' => 'food_noodles_hot_#00',   'provoking' => 'food_noodles_#00',       'in' => ['food_noodles_#00', 'spices_#00', 'water_#00'] ],
        'com010' => ['type' => Recipe::ManualAnywhere, 'out' => 'coffee_#00',             'provoking' => 'coffee_machine_#00',     'in' => ['pile_#00', 'pharma_#00', 'wood_bad_#00'] ],

        'com011' => ['type' => Recipe::ManualAnywhere, 'out' => 'watergun_opt_empty_#00', 'provoking' => 'watergun_opt_part_#00',  'in' => ['watergun_opt_part_#00', 'tube_#00', 'deto_#00', 'grenade_empty_#00', 'rustine_#00' ], "picto"=> "r_watgun_#00"],
        'com012' => ['type' => Recipe::ManualAnywhere, 'out' => 'pilegun_up_empty_#00',  'provoking' => 'pilegun_upkit_#00',      'in' => ['pilegun_upkit_#00', 'pilegun_empty_#00', 'meca_parts_#00', 'electro_#00', 'rustine_#00' ], 'picto' => 'r_batgun_#00' ],
        'com013' => ['type' => Recipe::ManualAnywhere, 'out' => 'mixergun_empty_#00',     'provoking' => 'mixergun_part_#00',      'in' => ['mixergun_part_#00', 'meca_parts_#00', 'electro_#00', 'rustine_#00' ] ],
        'com014' => ['type' => Recipe::ManualAnywhere, 'out' => 'jerrygun_#00',           'provoking' => 'jerrygun_part_#00',      'in' => ['jerrygun_part_#00', 'jerrycan_#00', 'rustine_#00' ], "picto"=> "r_watgun_#00" ],
        'com015' => ['type' => Recipe::ManualAnywhere, 'out' => 'chainsaw_empty_#00',     'provoking' => 'chainsaw_part_#00',      'in' => ['chainsaw_part_#00', 'engine_#00', 'meca_parts_#00', 'courroie_#00', 'rustine_#00' ] ],
        'com016' => ['type' => Recipe::ManualAnywhere, 'out' => 'bgrenade_empty_#00',     'provoking' => ['explo_#00','deto_#00'], 'in' => ['explo_#00', 'grenade_empty_#00', 'deto_#00', 'rustine_#00' ] ],
        'com017' => ['type' => Recipe::ManualAnywhere, 'out' => 'lawn_#00',               'provoking' => 'lawn_part_#00',          'in' => ['lawn_part_#00', 'meca_parts_#00', 'metal_#00', 'rustine_#00' ] ],
        'com018' => ['type' => Recipe::ManualAnywhere, 'out' => 'flash_#00',              'provoking' => 'powder_#00',             'in' => ['powder_#00', 'grenade_empty_#00', 'rustine_#00' ] ],
        'com019' => ['type' => Recipe::ManualAnywhere, 'out' => 'big_pgun_empty_#00',     'provoking' => 'big_pgun_part_#00',      'in' => ['big_pgun_part_#00', 'meca_parts_#00', 'courroie_#00' ], 'picto' => 'r_batgun_#00' ],

        'com020' => ['type' => Recipe::ManualAnywhere, 'out' => 'cart_#00',               'provoking' => 'cart_part_#00',          'in' => ['cart_part_#00', 'rustine_#00', 'metal_#00', 'tube_#00' ] ],
        'com021' => ['type' => Recipe::ManualAnywhere, 'out' => 'poison_#00',             'provoking' => 'poison_part_#00',        'in' => ['poison_part_#00', 'pile_#00', 'pharma_#00' ] ],
        'com022' => ['type' => Recipe::ManualAnywhere, 'out' => 'flesh_#00',              'provoking' => 'flesh_part_#00',         'in' => ['flesh_part_#00', 'flesh_part_#00' ] ],
        'com023' => ['type' => Recipe::ManualAnywhere, 'out' => 'saw_tool_#00',           'provoking' => 'saw_tool_part_#00',      'in' => ['saw_tool_part_#00', 'rustine_#00', 'meca_parts_#00' ] ],
        'com024' => ['type' => Recipe::ManualAnywhere, 'out' => 'engine_#00',             'provoking' => 'engine_part_#00',        'in' => ['engine_part_#00', 'rustine_#00', 'meca_parts_#00', 'metal_#00', 'deto_#00', 'bone_#00' ] ],
        'com025' => ['type' => Recipe::ManualAnywhere, 'out' => 'repair_kit_#00',         'provoking' => 'repair_kit_part_raw_#00','in' => ['repair_kit_part_raw_#00', 'rustine_#00', 'meca_parts_#00', 'wood2_#00' ] ],
        'com026' => ['type' => Recipe::ManualAnywhere, 'out' => 'fruit_part_#00',         'provoking' => 'fruit_sub_part_#00',     'in' => ['fruit_sub_part_#00', 'fruit_sub_part_#00' ] ],

        'com027' => ['type' => Recipe::ManualAnywhere, 'out' => ['drug_#00', 'xanax_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00', 'disinfect_#00', 'drug_hero_#00'], 'provoking' => 'pharma_#00', 'in' => ['pharma_#00', 'pharma_#00' ] ],
        'com028' => ['type' => Recipe::ManualAnywhere, 'out' => ['drug_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00', 'pharma_#00'], 'provoking' => 'pharma_part_#00', 'in' => ['pharma_part_#00', 'pharma_part_#00' ] ],

        'com029' => ['type' => Recipe::ManualAnywhere, 'out' => 'trapma_#00',     'provoking' => ['claymo_#00','door_carpet_#00'],'in' => ['claymo_#00','door_carpet_#00'] ],
        'com030' => ['type' => Recipe::ManualAnywhere, 'out' => 'claymo_#00',     'provoking' => ['wire_#00','explo_#00'],'in' => ['wire_#00','explo_#00', 'meca_parts_#00', 'rustine_#00'] ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @param ObjectManager $manager
     * @param array $data
     * @param array $cache
     * @return BuildingPrototype
     * @throws Exception
     */
    public function create_building(ObjectManager &$manager, array $data, array &$cache): BuildingPrototype {
        // Set up the icon cache
        if (!isset($cache[$data['img']])) $cache[$data['img']] = 0;
        else $cache[$data['img']]++;

        // Generate unique ID
        $entry_unique_id = $data['img'] . '_#' . str_pad($cache[$data['img']],2,'0',STR_PAD_LEFT);

        $object = $manager->getRepository(BuildingPrototype::class)->findOneByName( $entry_unique_id, false );
        if ($object) {
            if (!empty($object->getResources())) $manager->remove($object->getResources());
        } else $object = (new BuildingPrototype())->setName( $entry_unique_id );

        $object
            ->setLabel( $data['name'] )
            ->setTemp( $data['temporary'] > 0 )
            ->setAp( $data['ap'] )
            ->setBlueprint( $data['bp'] )
            ->setDefense( $data['vp'] )
            ->setIcon( $data['img'] );

        if(isset($data['desc'])){
        	$object->setDescription($data['desc']);
        }

        if (isset($data['maxLevel'])) {
            $object->setMaxLevel( $data['maxLevel'] );
            if ($data['upgradeTexts']) $object->setUpgradeTexts( $data['upgradeTexts'] );
        }

        if(isset($data['orderby'])){
            $object->setOrderBy( $data['orderby'] );
        }

        if (!empty($data['rsc'])) {

            $group = (new ItemGroup())->setName( "{$entry_unique_id}_rsc" );
            foreach ($data['rsc'] as $item_name => $count) {

                $item = $manager->getRepository(ItemPrototype::class)->findOneByName( $item_name );
                if (!$item) throw new Exception( "Item class not found: " . $item_name );

                $group->addEntry( (new ItemGroupEntry())->setPrototype( $item )->setChance( $count ) );
            }

            $object->setResources( $group );
        }

        if (!empty($data['children']))
            foreach ($data['children'] as $child)
                $object->addChild( $this->create_building( $manager, $child, $cache ) );

        $manager->persist( $object );
        return $object;

    }

    public function insert_buildings(ObjectManager $manager, ConsoleOutputInterface $out) {
        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$building_data) );

        $cache = [];
        foreach (static::$building_data as $building)
            try {
                $this->create_building($manager, $building, $cache);
                $progress->advance();
            } catch (Exception $e) {
                $out->writeln("<error>{$e->getMessage()}</error>");
                return;
            }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    public function insert_recipes(ObjectManager $manager, ConsoleOutputInterface $out) {
        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$building_data) );

        $cache = [];
        foreach (static::$recipe_data as $name => $recipe_data) {
            $recipe = $manager->getRepository(Recipe::class)->findOneByName( $name );
            if ($recipe === null) $recipe = (new Recipe())->setName( $name );

            if ($recipe->getSource()) { $manager->remove( $recipe->getSource() ); $recipe->setSource( null ); }
            if ($recipe->getResult()) { $manager->remove( $recipe->getResult() ); $recipe->setResult( null ); }
            $recipe->getProvoking()->clear();

            $unpack = function( $data ): array {
                if (!is_array($data)) return [ $data => 1 ];
                $cache = [];
                foreach ( $data as $entry ) {
                    if (is_array($entry))
                        list($id,$count) = $entry;
                    else {
                        $id = $entry;
                        $count = 1;
                    }

                    if (!isset( $cache[$id] )) $cache[$id] = 0;
                    $cache[$id] += $count;
                }
                return $cache;
            };

            $in =  $unpack( $recipe_data['in']  );
            $out = $unpack( $recipe_data['out'] );

            $provoking = null;
            if (isset($recipe_data['provoking'])) $provoking = is_array( $recipe_data['provoking'] ) ? $recipe_data['provoking'] : [$recipe_data['provoking']];
            elseif ( count($in) === 1 ) $provoking = [ array_keys($in)[0] ];

            if ($provoking === null || empty($out) || empty($in))
                throw new Exception("Entry '$name' is incomplete!");

            $in_group = (new ItemGroup())->setName("rc_{$name}_in");
            foreach ( $in as $id => $count ) {
                $proto = $manager->getRepository(ItemPrototype::class)->findOneByName( $id );
                if (!$proto) throw new Exception("Item prototype not found: '$id'");
                $in_group->addEntry( (new ItemGroupEntry())->setChance( $count )->setPrototype( $proto ) );
            }
            $recipe->setSource($in_group);

            $out_group = (new ItemGroup())->setName("rc_{$name}_out");
            foreach ( $out as $id => $count ) {
                $proto = $manager->getRepository(ItemPrototype::class)->findOneByName( $id );
                if (!$proto) throw new Exception("Item prototype not found: '$id'");
                $out_group->addEntry( (new ItemGroupEntry())->setChance( $count )->setPrototype( $proto ) );
            }
            $recipe->setResult($out_group);

            foreach ($provoking as $item)
                $recipe->addProvoking( $manager->getRepository(ItemPrototype::class)->findOneByName( $item ) );

            $recipe->setType( $recipe_data['type'] );
            if (array_key_exists('action', $recipe_data)) {
              $recipe->setAction($recipe_data['action']);
            }

            if(isset($recipe_data['picto'])){
                $recipe->setPictoPrototype($manager->getRepository(PictoPrototype::class)->findOneByName($recipe_data['picto']));
            }
            $manager->persist($recipe);

            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Buildings</info>' );
        $output->writeln("");

        try {
            $this->insert_buildings( $manager, $output );
            $this->insert_recipes( $manager, $output );
        } catch (Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }



        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ ItemFixtures::class ];
    }
}
