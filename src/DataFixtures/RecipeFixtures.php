<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use App\Entity\BuildingPrototype;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
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
        ["name" => "Verstärkte Stadtmauer","temporary" => 0,"img" => "small_wallimprove","vp" => 30,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 15,"metal_#00" => 5,], "orderby" => 0, "children" => [
            ["name" => "Großer Graben","maxLevel" => 5,"temporary" => 0,"img" => "small_gather","vp" => 10,"ap" => 80,"bp" => 0,"rsc" => [],
                "upgradeTexts" => [
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 13.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 21.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 32.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 33.',
                    'Der Verteidigungsbonus des Grabens steigt dauerhaft um 51.',
                ], "children" => [
                ["name" => "Wassergraben","temporary" => 0,"img" => "small_waterhole","vp" => 65,"ap" => 50,"bp" => 1,"rsc" => ["water_#00" => 20,]],
            ]],
            ["name" => "Rasierklingenmauer","temporary" => 0,"img" => "item_plate","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 15,]],
            ["name" => "Pfahlgraben","temporary" => 0,"img" => "small_spears","vp" => 40,"ap" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 8,"wood_beam_#00" => 4,]],
            ["name" => "Stacheldraht","temporary" => 0,"img" => "small_barbed","vp" => 10,"ap" => 20,"bp" => 0,"rsc" => ["metal_#00" => 2,], "children" => [
                ["name" => "Köder","temporary" => 1,"img" => "small_meatbarbed","vp" => 80,"ap" => 10,"bp" => 1,"rsc" => ["bone_meat_#00" => 3,]],
            ]],
            ["name" => "Weiterentwickelte Stadtmauer","temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 9,"metal_beam_#00" => 6,]],
            ["name" => "Verstärkende Balken","temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"metal_beam_#00" => 3,], "children" => [
                ["name" => "Zackenmauer","temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 35,"bp" => 1,"rsc" => ["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,], "children" => [
                    ["name" => "Groooße Mauer","temporary" => 0,"img" => "item_plate","vp" => 80,"ap" => 50,"bp" => 2,"rsc" => ["wood2_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
                ]],
                ["name" => "Zweite Schicht","temporary" => 0,"img" => "item_plate","vp" => 75,"ap" => 65,"bp" => 1,"rsc" => ["wood2_#00" => 35,"metal_beam_#00" => 5,], "children" => [
                    ["name" => "Dritte Schicht","temporary" => 0,"img" => "item_plate","vp" => 100,"ap" => 65,"bp" => 2,"rsc" => ["metal_#00" => 30,"plate_#00" => 5,"metal_beam_#00" => 5,]],
                ]],
                ["name" => "Betonschicht","temporary" => 0,"img" => "small_wallimprove","vp" => 50,"ap" => 60,"bp" => 1,"rsc" => ["concrete_wall_#00" => 6,"metal_beam_#00" => 2,]],
                ["name" => "Entwicklungsfähige Stadtmauer","maxLevel" => 5,"temporary" => 0,"img" => "item_home_def","vp" => 55,"ap" => 65,"bp" => 3,"rsc" => ["wood2_#00" => 5,"metal_#00" => 20,"concrete_wall_#00" => 1,],
                    "upgradeTexts" => [
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 30.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 35.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 50.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 65.',
                        'Der Verteidigungsbonus der Stadtmauer steigt dauerhaft um 80.',
                    ]],
            ]],
            ["name" => "Zombiereibe","temporary" => 0,"img" => "small_grater","vp" => 55,"ap" => 60,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 20,"plate_#00" => 3,]],
            ["name" => "Fallgruben","temporary" => 0,"img" => "small_gather","vp" => 35,"ap" => 50,"bp" => 0,"rsc" => ["wood2_#00" => 10,]],
            ["name" => "Zaun","temporary" => 0,"img" => "small_fence","vp" => 30,"ap" => 50,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,]],
            ["name" => "Holzzaun","temporary" => 0,"img" => "small_fence","vp" => 45,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"wood_beam_#00" => 5,]],
            ["name" => "Sperrholz","temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,]],
            ["name" => "Rüstungsplatten 3.0","temporary" => 0,"img" => "item_plate","vp" => 40,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 10,]],
            ["name" => "Extramauer","temporary" => 0,"img" => "item_plate","vp" => 45,"ap" => 30,"bp" => 1,"rsc" => ["wood2_#00" => 15,"metal_#00" => 15,]],
            ["name" => "Rüstungsplatten","temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,]],
            ["name" => "Rüstungsplatten 2.0","temporary" => 0,"img" => "item_plate","vp" => 25,"ap" => 30,"bp" => 0,"rsc" => ["metal_#00" => 10,]],
            ["name" => "Einseifer","temporary" => 0,"img" => "small_wallimprove","vp" => 60,"ap" => 40,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 5,"concrete_wall_#00" => 1,]],
            ["name" => "Zerstäuber","temporary" => 0,"img" => "small_waterspray","vp" => 0,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 2,], "children" => [
                ["name" => "Spraykanone","temporary" => 1,"img" => "small_gazspray","vp" => 150,"ap" => 40,"bp" => 2,"rsc" => ["water_#00" => 2,"pharma_#00" => 7,"drug_#00" => 3,]],
                ["name" => "Säurespray","temporary" => 1,"img" => "small_acidspray","vp" => 35,"ap" => 30,"bp" => 1,"rsc" => ["water_#00" => 2,"pharma_#00" => 5,]],
            ]],
            // TODO: Night watch action
            ["name" => "Brustwehr","temporary" => 0,"img" => "small_round_path","vp" => 0,"ap" => 20,"bp" => 0,"rsc" => ["wood2_#00" => 6,"metal_#00" => 2,"meca_parts_#00" => 1,]],
        ]],

        ["name" => "Pumpe","maxLevel" => 5,"temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 25,"bp" => 0,"rsc" => ["metal_#00" => 8,"tube_#00" => 1,], "orderby" => 1,
            "upgradeTexts" => [
                'Der Brunnen der Stadt wird einmalig um 5 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 20 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 30 Rationen Wasser aufgefüllt',
                'Der Brunnen der Stadt wird einmalig um 40 Rationen Wasser aufgefüllt',
            ], "children" => [
            ["name" => "Wasserreiniger","temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 75,"bp" => 0,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 6,"tube_#00" => 3,], "children" => [
                ["name" => "Minen","temporary" => 1,"img" => "item_bgrenade","vp" => 115,"ap" => 50,"bp" => 2,"rsc" => ["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,]],
                ["name" => "Wasserfilter","temporary" => 0,"img" => "item_jerrycan","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["metal_#00" => 10,"electro_#00" => 2,"fence_#00" => 1,]],
            ]],
            ["name" => "Gemüsebeet","temporary" => 0,"img" => "item_vegetable_tasty","vp" => 0,"ap" => 60,"bp" => 1,"rsc" => ["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,], "children" => [
                ["name" => "Dünger","temporary" => 0,"img" => "item_digger","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,]],
                ["name" => "Granatapfel","temporary" => 0,"img" => "item_bgrenade","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,]],
            ]],
            ["name" => "Brunnenbohrer","temporary" => 0,"img" => "small_water","vp" => 0,"ap" => 60,"bp" => 0,"rsc" => ["wood_beam_#00" => 7,"metal_beam_#00" => 2,], "children" => [
                ["name" => "Projekt Eden","temporary" => 0,"img" => "small_eden","vp" => 0,"ap" => 65,"bp" => 3,"rsc" => ["explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 8,]],
            ]],
            ["name" => "Wasserleitungsnetz","temporary" => 0,"img" => "item_firework_tube","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,], "children" => [
                ["name" => "Kärcher","temporary" => 0,"img" => "small_waterspray","vp" => 50,"ap" => 50,"bp" => 0,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 7,]],
                ["name" => "Kreischender Rotor","temporary" => 0,"img" => "small_grinder","vp" => 50,"ap" => 55,"bp" => 1,"rsc" => ["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,]],
                ["name" => "Sprinkleranlage","temporary" => 0,"img" => "small_sprinkler","vp" => 150,"ap" => 85,"bp" => 3,"rsc" => ["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,]],
                // TODO: Special Action
                ["name" => "Dusche","temporary" => 0,"img" => "small_shower","vp" => 0,"ap" => 25,"bp" => 2,"rsc" => ["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,]],
            ]],
            ["name" => "Wasserturm","maxLevel" => 5,"temporary" => 0,"img" => "item_tube","vp" => 70,"ap" => 60,"bp" => 3,"rsc" => ["water_#00" => 40,"tube_#00" => 7,"metal_beam_#00" => 10,],
                "upgradeTexts" => [
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 2 Rationen Wasser und steigert seinen Verteidigungswert dafür um 56.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 4 Rationen Wasser und steigert seinen Verteidigungswert dafür um 112.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 6 Rationen Wasser und steigert seinen Verteidigungswert dafür um 168.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 9 Rationen Wasser und steigert seinen Verteidigungswert dafür um 224.',
                    'Der Wasserturm verbraucht beim nächtlichen Angriff 12 Rationen Wasser und steigert seinen Verteidigungswert dafür um 280.',
                ]],
            ["name" => "Wasserfänger","temporary" => 1,"img" => "item_tube","vp" => 0,"ap" => 12,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,]],
            ["name" => "Wasserkanone","temporary" => 0,"img" => "small_watercanon","vp" => 80,"ap" => 40,"bp" => 2,"rsc" => ["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,]],
            ["name" => "Schleuse","temporary" => 0,"img" => "small_shower","vp" => 60,"ap" => 50,"bp" => 1,"rsc" => ["water_#00" => 15,"wood2_#00" => 10,]],
            ["name" => "Wasserfall","temporary" => 0,"img" => "small_shower","vp" => 35,"ap" => 20,"bp" => 1,"rsc" => ["water_#00" => 10,]],
            ["name" => "Wünschelrakete","temporary" => 0,"img" => "small_rocketperf","vp" => 0,"ap" => 90,"bp" => 3,"rsc" => ["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name" => "Wünschelrute","temporary" => 0,"img" => "small_waterdetect","vp" => 0,"ap" => 130,"bp" => 4,"rsc" => ["electro_#00" => 5,"wood_beam_#00" => 5,"metal_beam_#00" => 10,]],
            ["name" => "Apfelbaum","temporary" => 0,"img" => "small_appletree","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"metal_beam_#00" => 1,]],
        ]],

        // TODO: Convert animals
        ["name" => "Metzgerei","temporary" => 0,"img" => "item_meat","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 9,"metal_#00" => 4,], "orderby" => 2, "children" => [
            ["name" => "Kremato-Cue","temporary" => 0,"img" => "item_hmeat","vp" => 0,"ap" => 45,"bp" => 2,"rsc" => ["wood_beam_#00" => 8,"metal_beam_#00" => 1,]],
        ]],

        // TODO: Upgrade effect
        ["name" => "Werkstatt","maxLevel" => 5,"temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 25,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 3,
            "upgradeTexts" => [
                'Die AP-Kosten aller Bauprojekte werden um 5% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 10% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 15% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 20% gesenkt.',
                'Die AP-Kosten aller Bauprojekte werden um 25% gesenkt.',
            ], "children" => [
            ["name" => "Verteidigungsanlage","maxLevel" => 5,"temporary" => 0,"img" => "item_meca_parts","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 7,"metal_beam_#00" => 8,],
                "upgradeTexts" => [
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 100%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 150%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 200%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 250%.',
                    'Der Verteidigungsbonus von Gegenständen in der Bank steigt um 300%.',
                ]],
            ["name" => "Kanonenhügel","temporary" => 0,"img" => "small_dig","vp" => 30,"ap" => 50,"bp" => 0,"rsc" => ["concrete_wall_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 1,], "children" => [
                ["name" => "Steinkanone","temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 60,"bp" => 1,"rsc" => ["tube_#00" => 1,"electro_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
                ["name" => "Selbstgebaute Railgun","temporary" => 0,"img" => "small_canon","vp" => 50,"ap" => 40,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"tube_#00" => 1,"electro_#00" => 1,"metal_beam_#00" => 10,]],
                ["name" => "Blechplattenwerfer","temporary" => 0,"img" => "small_canon","vp" => 60,"ap" => 50,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"plate_#00" => 3,"explo_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 1,]],
                ["name" => "Brutale Kanone","temporary" => 1,"img" => "small_canon","vp" => 50,"ap" => 25,"bp" => 0,"rsc" => ["plate_#00" => 1,"metal_beam_#00" => 1,]],
            ]],
            ["name" => "Holzbalkendrehkreuz","temporary" => 0,"img" => "item_wood_beam","vp" => 10,"ap" => 15,"bp" => 0,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 1,]],
            ["name" => "Manufaktur","temporary" => 0,"img" => "small_factory","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,]],
            ["name" => "Kreischende Sägen","temporary" => 0,"img" => "small_saw","vp" => 45,"ap" => 65,"bp" => 0,"rsc" => ["meca_parts_#00" => 3,"metal_#00" => 5,"rustine_#00" => 3,"metal_beam_#00" => 2,]],
            ["name" => "Baustellenbuch","temporary" => 0,"img" => "item_rp_book2","vp" => 0,"ap" => 15,"bp" => 0,"rsc" => ["table_#00" => 1,], "children" => [
                ["name" => "Bauhaus","maxLevel" => 5,"temporary" => 0,"img" => "small_refine","vp" => 0,"ap" => 75,"bp" => 0,"rsc" => ["drug_#00" => 1,"vodka_de_#00" => 1,"wood_beam_#00" => 10,],
                "upgradeTexts" => [
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 4 gewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 2 gewöhnliche und 2 ungewöhnliche Baupläne sowie - möglicherweise - eine nette Überraschung.',
                    'Die Stadt erhält nach dem nächsten Angriff einmalig 2 ungewöhnliche und 2 seltene Baupläne sowie - möglicherweise - eine nette Überraschung.',
                ]],
            ]],
            ["name" => "Galgen","temporary" => 0,"img" => "r_dhang","vp" => 0,"ap" => 13,"bp" => 0,"rsc" => ["wood_beam_#00" => 1,"chain_#00" => 1,]],
            ["name" => "Kleines Cafe","temporary" => 1,"img" => "small_cafet","vp" => 0,"ap" => 6,"bp" => 0,"rsc" => ["water_#00" => 1,"wood2_#00" => 2,"pharma_#00" => 1,]],
            ["name" => "Kleiner Friedhof","temporary" => 0,"img" => "small_cemetery","vp" => 0,"ap" => 36,"bp" => 1,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 10,], "children" => [
                ["name" => "Sarg-Katapult","temporary" => 0,"img" => "small_coffin","vp" => 0,"ap" => 100,"bp" => 4,"rsc" => ["courroie_#00" => 1,"meca_parts_#00" => 5,"wood2_#00" => 5,"metal_#00" => 15,]],
            ]],
            ["name" => "Hühnerstall","temporary" => 0,"img" => "small_chicken","vp" => 0,"ap" => 25,"bp" => 3,"rsc" => ["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,]],
            ["name" => "Schlachthof","temporary" => 0,"img" => "small_slaughterhouse","vp" => 35,"ap" => 40,"bp" => 1,"rsc" => ["concrete_wall_#00" => 2,"metal_beam_#00" => 10,]],
            ["name" => "Pentagon","temporary" => 0,"img" => "item_shield","vp" => 8,"ap" => 55,"bp" => 3,"rsc" => ["wood_beam_#00" => 5,"metal_beam_#00" => 10,],
                "upgradeTexts" => [
                    'Die Verteidigung der Stadt wird um 12% erhöht.',
                    'Die Verteidigung der Stadt wird um 14% erhöht.'
                ]],
            // TODO: Home upgrade
            ["name" => "Kantine","temporary" => 0,"img" => "small_cafet","vp" => 0,"ap" => 20,"bp" => 1,"rsc" => ["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,]],
            ["name" => "Bollwerk","temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 60,"bp" => 3,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ["name" => "Baumarkt","temporary" => 0,"img" => "small_strategy","vp" => 0,"ap" => 30,"bp" => 4,"rsc" => ["meca_parts_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 10,]],
            // TODO: Special Action
            ["name" => "Krankenstation","temporary" => 0,"img" => "small_infirmary","vp" => 0,"ap" => 40,"bp" => 3,"rsc" => ["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            // TODO: Home upgrade
            ["name" => "Labor","temporary" => 0,"img" => "item_acid","vp" => 0,"ap" => 30,"bp" => 1,"rsc" => ["meca_parts_#00" => 3,"pharma_#00" => 5,"wood_beam_#00" => 3,"metal_beam_#00" => 10,]],
        ]],

        ["name" => "Wachturm","maxLevel" => 5, "temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 12,"bp" => 0,"rsc" => ["wood2_#00" => 3,"metal_#00" => 2,], "orderby" => 4,
            "upgradeTexts" => [
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 3km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 6km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 1km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
                'Erspäht jeden Morgen alle Zombies, die sich im Umkreis von 10km um die Stadt aufhalten. Bürger im Umkreis von 2km um die Stadt können ohne AP-Verbrauch die Stadt betreten.',
            ], "children" => [
            ["name" => "Scanner","temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["pile_#00" => 2,"meca_parts_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,]],
            // TODO: Unveil zombie count
            ["name" => "Verbesserte Karte","temporary" => 0,"img" => "item_electro","vp" => 0,"ap" => 15,"bp" => 1,"rsc" => ["pile_#00" => 2,"metal_#00" => 1,"electro_#00" => 1,"radio_on_#00" => 2,]],
            ["name" => "Rechenmaschine","temporary" => 0,"img" => "item_tagger","vp" => 0,"ap" => 20,"bp" => 1,"rsc" => ["rustine_#00" => 1,"electro_#00" => 1,]],
            ["name" => "Forschungsturm","maxLevel" => 5,"temporary" => 0,"img" => "small_gather","vp" => 0,"ap" => 30,"bp" => 1,"rsc" => ["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,],
                "upgradeTexts" => [
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 37%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 49%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 61%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 73%.',
                    'Die Chance, dass sich die Zonen in Windrichtung regenerieren steigt auf 85%.',
                ]],
            ["name" => "Notfallkonstruktion","temporary" => 0,"img" => "status_terror","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["wood2_#00" => 5,"metal_#00" => 7,], "children" => [
                ["name" => "Verteidigungspfähle","temporary" => 1,"img" => "small_trap","vp" => 25,"ap" => 12,"bp" => 0,"rsc" => ["wood2_#00" => 6,]],
                ["name" => "Notfallabstützung","temporary" => 1,"img" => "item_wood_plate","vp" => 40,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 8,]],
                ["name" => "Guerilla","temporary" => 1,"img" => "small_trap","vp" => 50,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 2,"metal_#00" => 1,]],
                ["name" => "Abfallberg","temporary" => 1,"img" => "small_dig","vp" => 5,"ap" => 10,"bp" => 0,"rsc" => ["wood2_#00" => 2,"metal_#00" => 2,], "children" => [
                    ["name" => "Trümmerberg","temporary" => 1,"img" => "small_dig","vp" => 60,"ap" => 40,"bp" => 1,"rsc" => ["metal_#00" => 2,]],
                ]],
                ["name" => "Wolfsfalle","temporary" => 1,"img" => "small_trap","vp" => 40,"ap" => 20,"bp" => 0,"rsc" => ["metal_#00" => 2,"hmeat_#00" => 3,]],
                ["name" => "Sprengfalle","temporary" => 1,"img" => "small_tnt","vp" => 35,"ap" => 20,"bp" => 0,"rsc" => ["explo_#00" => 3,]],
                ["name" => "Nackte Panik","temporary" => 1,"img" => "status_terror","vp" => 50,"ap" => 25,"bp" => 0,"rsc" => ["water_#00" => 4,"wood2_#00" => 5,"metal_#00" => 5,]],
                ["name" => "Dollhouse","temporary" => 1,"img" => "small_bamba","vp" => 75,"ap" => 50,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,"radio_on_#00" => 3,]],
                ["name" => "Heiliger Regen","temporary" => 1,"img" => "small_holyrain","vp" => 200,"ap" => 40,"bp" => 0,"rsc" => ["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_red_#00" => 4,]],
                ["name" => "Voodoo-Puppe","temporary" => 0,"img" => "small_vaudoudoll","vp" => 65,"ap" => 40,"bp" => 0,"rsc" => ["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_red_#00" => 2,]],
                ["name" => "Spirituelles Wunder","temporary" => 0,"img" => "small_spiritmirage","vp" => 80,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_red_#00" => 2,]],
                ["name" => "Bokors Guillotine","temporary" => 0,"img" => "small_bokorsword","vp" => 100,"ap" => 60,"bp" => 0,"rsc" => ["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_red_#00" => 3,]],
            ]],
            // TODO: UI
            ["name" => "Katapult","temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 40,"bp" => 1,"rsc" => ["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,], "children" => [
                ["name" => "Verbesserter Katapult","temporary" => 0,"img" => "item_courroie","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 2,]],
            ]],
            // TODO: Special Action
            ["name" => "Wächter-Turm","temporary" => 0,"img" => "small_watchmen","vp" => 15,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,], "children" => [
                // TODO: NW effect
                ["name" => "Kleine Waffenschmiede","temporary" => 0,"img" => "small_armor","vp" => 0,"ap" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 15,"plate_#00" => 2,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,]],
                // TODO: NW effect
                ["name" => "Schwedische Schreinerei","temporary" => 0,"img" => "small_ikea","vp" => 0,"ap" => 50,"bp" => 2,"rsc" => ["meca_parts_#00" => 3,"wood2_#00" => 15,"metal_#00" => 10,"plate_#00" => 4,"concrete_wall_#00" => 2,"wood_beam_#00" => 5,]],
                // TODO: NW effect
                ["name" => "Schießstand","temporary" => 0,"img" => "small_tourello","vp" => 50,"ap" => 25,"bp" => 2,"rsc" => ["water_#00" => 30,"tube_#00" => 2,"wood_beam_#00" => 1,"metal_beam_#00" => 2,]],
                // TODO: NW effect
                ["name" => "Kleiner Tribok","temporary" => 0,"img" => "small_catapult3","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 2,"metal_beam_#00" => 4,"meca_parts_#00" => 2,"plate_#00" => 2,"tube_#00" => 1,]],
            ]],
            // TODO: Special Action
            ["name" => "Krähennest","temporary" => 0,"img" => "small_watchmen","vp" => 10,"ap" => 36,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,]],
        ]],

        ["name" => "Fundament","temporary" => 0,"img" => "small_building","vp" => 0,"ap" => 30,"bp" => 0,"rsc" => ["wood2_#00" => 10,"metal_#00" => 8,], "orderby" => 5, "children" => [
            ["name" => "Bohrturm","temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 70,"bp" => 3,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 15,]],
            ["name" => "Falsche Stadt","temporary" => 0,"img" => "small_falsecity","vp" => 400,"ap" => 400,"bp" => 3,"rsc" => ["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,]],
            // TODO: Special Action
            ["name" => "Wasserhahn","temporary" => 0,"img" => "small_valve","vp" => 0,"ap" => 130,"bp" => 3,"rsc" => ["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,]],
            ["name" => "Großer Umbau","temporary" => 0,"img" => "small_moving","vp" => 300,"ap" => 300,"bp" => 3,"rsc" => ["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 20,"metal_beam_#00" => 20,]],
            ["name" => "Vogelscheuche","temporary" => 0,"img" => "small_scarecrow","vp" => 25,"ap" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 10,"rustine_#00" => 2,]],
            ["name" => "Fleischkäfig","temporary" => 0,"img" => "small_fleshcage","vp" => 0,"ap" => 40,"bp" => 0,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"wood_beam_#00" => 1,]],
            ["name" => "Bürgergericht","temporary" => 0,"img" => "small_court","vp" => 0,"ap" => 12,"bp" => 2,"rsc" => ["wood2_#00" => 6,"metal_beam_#00" => 15,"table_#00" => 1,]],
            ["name" => "Befestigungen","temporary" => 0,"img" => "small_city_up","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
            // TODO: UI
            ["name" => "Müllhalde","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 70,"bp" => 0,"rsc" => ["concrete_wall_#00" => 5,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "children" => [
                // TODO: UI
                ["name" => "Holzabfall","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,]],
                // TODO: UI
                ["name" => "Metallabfall","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood2_#00" => 5,"metal_#00" => 5,]],
                // TODO: UI
                ["name" => "Tierabfälle","temporary" => 0,"img" => "small_howlingbait","vp" => 0,"ap" => 30,"bp" => 2,"rsc" => ["wood_beam_#00" => 10,]],
                // TODO: UI
                ["name" => "Müll für Alle","temporary" => 0,"img" => "small_trashclean","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"trestle_#00" => 2,]],
                // TODO: UI
                ["name" => "Waffenabfall","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,]],
                // TODO: UI
                ["name" => "Biomüll","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 20,"bp" => 2,"rsc" => ["wood2_#00" => 15,]],
                // TODO: UI
                ["name" => "Rüstungsabfall","temporary" => 0,"img" => "small_trash","vp" => 0,"ap" => 40,"bp" => 2,"rsc" => ["metal_beam_#00" => 3,"metal_#00" => 5,]],
                // TODO: UI
                ["name" => "Verbesserte Müllhalde","temporary" => 0,"img" => "small_trash","vp" => 75,"ap" => 120,"bp" => 4,"rsc" => ["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ]],
            // TODO: Camping
            ["name" => "Leuchtturm","temporary" => 0,"img" => "small_lighthouse","vp" => 0,"ap" => 30,"bp" => 3,"rsc" => ["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name" => "Altar","temporary" => 0,"img" => "small_redemption","vp" => 0,"ap" => 24,"bp" => 2,"rsc" => ["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,]],
            ["name" => "Riesige Sandburg","temporary" => 0,"img" => "small_castle","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
            ["name" => "Leuchtfeuer","temporary" => 1,"img" => "small_score","vp" => 30,"ap" => 15,"bp" => 2,"rsc" => ["lights_#00" => 1,"wood2_#00" => 5,]],
            ["name" => "Ministerium für Sklaverei","temporary" => 0,"img" => "small_slave","vp" => 0,"ap" => 45,"bp" => 4,"rsc" => ["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,]],
            // TODO: Destroyable, kill
            ["name" => "Reaktor","temporary" => 0,"img" => "small_arma","vp" => 500,"ap" => 100,"bp" => 4,"rsc" => ["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,]],
            ["name" => "Labyrinth","temporary" => 0,"img" => "small_labyrinth","vp" => 150,"ap" => 200,"bp" => 3,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,]],
            // TODO: Temp Def
            ["name" => "Alles oder nichts","temporary" => 0,"img" => "small_lastchance","vp" => 55,"ap" => 150,"bp" => 3,"rsc" => ["meca_parts_#00" => 4,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ["name" => "Riesiger KVF","temporary" => 0,"img" => "small_pmvbig","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["meca_parts_#00" => 2,"metal_#00" => 30,]],
            ["name" => "Riesenrad","temporary" => 0,"img" => "small_wheel","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,]],
            ["name" => "Feuerwerk","temporary" => 0,"img" => "small_fireworks","vp" => 0,"ap" => 50,"bp" => 4,"rsc" => ["meca_parts_#00" => 1,"explo_#00" => 4,"deto_#00" => 2,"wood_beam_#00" => 3,"metal_beam_#00" => 3,]],
            ["name" => "Krähenstatue","temporary" => 0,"img" => "small_crow","vp" => 0,"ap" => 300,"bp" => 4,"rsc" => ["hmeat_#00" => 3,"wood_beam_#00" => 35,]],
            // TODO: Special Action
            ["name" => "Kino","temporary" => 0,"img" => "small_cinema","vp" => 0,"ap" => 100,"bp" => 4,"rsc" => ["electro_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 5,"machine_1_#00" => 1,"machine_2_#00" => 1,]],
            ["name" => "Luftschlag","temporary" => 1,"img" => "small_rocket","vp" => 0,"ap" => 50,"bp" => 3,"rsc" => ["water_#00" => 10,"meca_parts_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,]],
            ["name" => "Heißluftballon","temporary" => 1,"img" => "small_balloon","vp" => 0,"ap" => 100,"bp" => 4,"rsc" => ["meca_parts_#00" => 6,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name" => "Tunnelratte","temporary" => 0,"img" => "small_derrick","vp" => 0,"ap" => 170,"bp" => 4,"rsc" => ["concrete_wall_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
        ]],

        ["name" => "Portal","temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 16,"bp" => 0,"rsc" => ["metal_#00" => 2,], "orderby" => 6, "children" => [
            ["name" => "Torpanzerung","temporary" => 0,"img" => "item_plate","vp" => 20,"ap" => 35,"bp" => 0,"rsc" => ["wood2_#00" => 3,]],
            ["name" => "Kolbenschließmechanismus","temporary" => 0,"img" => "small_door_closed","vp" => 30,"ap" => 24,"bp" => 1,"rsc" => ["meca_parts_#00" => 2,"wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,], "children" => [
                ["name" => "Automatiktür","temporary" => 0,"img" => "small_door_closed","vp" => 0,"ap" => 10,"bp" => 1,"rsc" => []],
            ]],
            ["name" => "Ventilationssystem","temporary" => 0,"img" => "small_ventilation","vp" => 20,"ap" => 24,"bp" => 2,"rsc" => ["meca_parts_#00" => 1,"metal_#00" => 8,]],
        ]],
        
        // TODO: Action in house
        ["name" => "Hammam","temporary" => 0,"img" => "small_spa4souls","vp" => 28,"ap" => 20,"bp" => 0,"rsc" => ["wood2_#00" => 2,"metal_beam_#00" => 2,], "orderby" => 7],
    ];

    protected static $recipe_data = [
        'ws001' => ['type' => Recipe::WorkshopType, 'in' => 'repair_kit_part_#00', 'out' => 'repair_kit_#00'],
        'ws002' => ['type' => Recipe::WorkshopType, 'in' => 'can_#00',             'out' => 'can_open_#00'],
        'ws003' => ['type' => Recipe::WorkshopType, 'in' => 'plate_raw_#00',       'out' => 'plate_#00'],
        'ws004' => ['type' => Recipe::WorkshopType, 'in' => 'wood_log_#00',        'out' => 'wood2_#00'],
        'ws005' => ['type' => Recipe::WorkshopType, 'in' => 'wood_bad_#00',        'out' => 'wood2_#00'],
        'ws006' => ['type' => Recipe::WorkshopType, 'in' => 'wood2_#00',           'out' => 'wood_beam_#00'],
        'ws007' => ['type' => Recipe::WorkshopType, 'in' => 'wood_beam_#00',       'out' => 'wood2_#00'],
        'ws008' => ['type' => Recipe::WorkshopType, 'in' => 'metal_bad_#00',       'out' => 'metal_#00'],
        'ws009' => ['type' => Recipe::WorkshopType, 'in' => 'metal_#00',           'out' => 'metal_beam_#00'],
        'ws010' => ['type' => Recipe::WorkshopType, 'in' => 'metal_beam_#00',      'out' => 'metal_#00'],
        'ws011' => ['type' => Recipe::WorkshopType, 'in' => 'electro_box_#00',     'out' => [ 'pile_#00', 'pilegun_empty_#00', 'electro_#00', 'meca_parts_#00', 'tagger_#00', 'deto_#00' ] ],
        'ws012' => ['type' => Recipe::WorkshopType, 'in' => 'mecanism_#00',        'out' => [ 'metal_#00', 'tube_#00', 'metal_bad_#00', 'meca_parts_#00' ] ],
        'ws013' => ['type' => Recipe::WorkshopType, 'in' => 'chest_#00',           'out' => [ 'drug_#00', 'bandage_#00', 'pile_#00', 'pilegun_empty_#00', 'vodka_de_#00', 'pharma_#00', 'explo_#00', 'lights_#00', 'drug_hero_#00', 'rhum_#00' ] ],
        'ws014' => ['type' => Recipe::WorkshopType, 'in' => 'chest_xl_#00',        'out' => [ 'watergun_opt_part_#00', 'pilegun_upkit_#00', 'pocket_belt_#00', 'cutcut_#00', 'chainsaw_part_#00', 'mixergun_part_#00', 'big_pgun_part_#00', 'lawn_part_#00' ] ],
        'ws015' => ['type' => Recipe::WorkshopType, 'in' => 'chest_tools_#00',     'out' => [ 'pile_#00', 'meca_parts_#00', 'rustine_#00', 'tube_#00', 'pharma_#00', 'explo_#00', 'lights_#00' ] ],
        'ws016' => ['type' => Recipe::WorkshopType, 'in' => 'chest_food_#00',      'out' => [ 'food_bag_#00', 'can_#00', 'meat_#00', 'hmeat_#00', 'vegetable_#00' ] ],
        'ws017' => ['type' => Recipe::WorkshopType, 'in' => 'deco_box_#00',        'out' => [ 'door_#00', 'chair_basic_#00', 'trestle_#00', 'table_#00', 'chair_#00' ] ],

        'com001' => ['type' => Recipe::ManualAnywhere, 'out' => 'coffee_machine_#00',     'provoking' => 'coffee_machine_part_#00','in' => ['coffee_machine_part_#00', 'cyanure_#00', 'electro_#00', 'meca_parts_#00', 'rustine_#00', 'metal_#00', 'tube_#00' ] ],
        'com002' => ['type' => Recipe::ManualAnywhere, 'out' => 'music_#00',              'provoking' => 'music_part_#00',         'in' => ['music_part_#00', 'pile_#00', 'electro_#00'] ],
        'com003' => ['type' => Recipe::ManualAnywhere, 'out' => 'guitar_#00',             'provoking' => ['wire_#00','oilcan_#00'],'in' => ['wire_#00', 'oilcan_#00', 'staff_#01'] ],
        'com004' => ['type' => Recipe::ManualAnywhere, 'out' => 'car_door_#00',           'provoking' => 'car_door_part_#00',      'in' => ['car_door_part_#00', 'meca_parts_#00', 'rustine_#00', 'metal_#00'] ],
        'com005' => ['type' => Recipe::ManualAnywhere, 'out' => 'torch_#00',              'provoking' => 'lights_#00',             'in' => ['lights_#00', 'wood_bad_#00'] ],
        'com006' => ['type' => Recipe::ManualAnywhere, 'out' => 'wood_plate_#00',         'provoking' => 'wood_plate_part_#00',    'in' => ['wood_plate_part_#00', 'wood2_#00'] ],
        'com007' => ['type' => Recipe::ManualAnywhere, 'out' => 'concrete_wall_#00',      'provoking' => 'concrete_#00',           'in' => ['concrete_#00', 'water_#00'] ],
        'com008' => ['type' => Recipe::ManualAnywhere, 'out' => 'chama_tasty_#00',        'provoking' => 'torch_#00',              'in' => ['chama_#00'] ],
        'com009' => ['type' => Recipe::ManualAnywhere, 'out' => 'food_noodles_hot_#00',   'provoking' => 'food_noodles_#00',       'in' => ['food_noodles_#00', 'spices_#00', 'water_#00'] ],
        'com010' => ['type' => Recipe::ManualAnywhere, 'out' => 'coffee_#00',             'provoking' => 'coffee_machine_#00',     'in' => ['pile_#00', 'pharma_#00', 'wood_bad_#00'] ],

        'com011' => ['type' => Recipe::ManualAnywhere, 'out' => 'watergun_opt_empty_#00', 'provoking' => 'watergun_opt_part_#00',  'in' => ['watergun_opt_part_#00', 'tube_#00', 'deto_#00', 'grenade_empty_#00', 'rustine_#00' ] ],
        'com012' => ['type' => Recipe::ManualAnywhere, 'out' => 'pilegun_up_empty_#00',  'provoking' => 'pilegun_upkit_#00',      'in' => ['pilegun_upkit_#00', 'pilegun_empty_#00', 'meca_parts_#00', 'electro_#00', 'rustine_#00' ] ],
        'com013' => ['type' => Recipe::ManualAnywhere, 'out' => 'mixergun_empty_#00',     'provoking' => 'mixergun_part_#00',      'in' => ['mixergun_part_#00', 'meca_parts_#00', 'electro_#00', 'rustine_#00' ] ],
        'com014' => ['type' => Recipe::ManualAnywhere, 'out' => 'jerrygun_#00',           'provoking' => 'jerrygun_part_#00',      'in' => ['jerrygun_part_#00', 'jerrycan_#00', 'rustine_#00' ] ],
        'com015' => ['type' => Recipe::ManualAnywhere, 'out' => 'chainsaw_empty_#00',     'provoking' => 'chainsaw_part_#00',      'in' => ['chainsaw_part_#00', 'engine_#00', 'meca_parts_#00', 'courroie_#00', 'rustine_#00' ] ],
        'com016' => ['type' => Recipe::ManualAnywhere, 'out' => 'bgrenade_empty_#00',     'provoking' => ['explo_#00','deto_#00'], 'in' => ['explo_#00', 'grenade_empty_#00', 'deto_#00', 'rustine_#00' ] ],
        'com017' => ['type' => Recipe::ManualAnywhere, 'out' => 'lawn_#00',               'provoking' => 'lawn_part_#00',          'in' => ['lawn_part_#00', 'meca_parts_#00', 'metal_#00', 'rustine_#00' ] ],
        'com018' => ['type' => Recipe::ManualAnywhere, 'out' => 'flash_#00',              'provoking' => 'powder_#00',             'in' => ['powder_#00', 'grenade_empty_#00', 'rustine_#00' ] ],
        'com019' => ['type' => Recipe::ManualAnywhere, 'out' => 'big_pgun_empty_#00',     'provoking' => 'big_pgun_part_#00',      'in' => ['big_pgun_part_#00', 'meca_parts_#00', 'courroie_#00' ] ],

        'com020' => ['type' => Recipe::ManualAnywhere, 'out' => 'cart_#00',               'provoking' => 'cart_part_#00',          'in' => ['cart_part_#00', 'rustine_#00', 'metal_#00', 'tube_#00' ] ],
        'com021' => ['type' => Recipe::ManualAnywhere, 'out' => 'poison_#00',             'provoking' => 'poison_part_#00',        'in' => ['poison_part_#00', 'pile_#00', 'pharma_#00' ] ],
        'com022' => ['type' => Recipe::ManualAnywhere, 'out' => 'flesh_#00',              'provoking' => 'flesh_part_#00',         'in' => ['flesh_part_#00', 'flesh_part_#00' ] ],
        'com023' => ['type' => Recipe::ManualAnywhere, 'out' => 'saw_tool_#00',           'provoking' => 'saw_tool_part_#00',      'in' => ['saw_tool_part_#00', 'rustine_#00', 'meca_parts_#00' ] ],
        'com024' => ['type' => Recipe::ManualAnywhere, 'out' => 'engine_#00',             'provoking' => 'engine_part_#00',        'in' => ['engine_part_#00', 'rustine_#00', 'meca_parts_#00', 'metal_#00', 'deto_#00', 'bone_#00' ] ],
        'com025' => ['type' => Recipe::ManualAnywhere, 'out' => 'repair_kit_#00',         'provoking' => 'repair_kit_part_raw_#00','in' => ['repair_kit_part_raw_#00', 'rustine_#00', 'meca_parts_#00', 'wood2_#00' ] ],
        'com026' => ['type' => Recipe::ManualAnywhere, 'out' => 'fruit_part_#00',         'provoking' => 'fruit_sub_part_#00',     'in' => ['fruit_sub_part_#00', 'fruit_sub_part_#00' ] ],

        'com027' => ['type' => Recipe::ManualAnywhere, 'out' => ['drug_#00', 'xanax_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00', 'disinfect_#00', 'drug_hero_#00'], 'provoking' => 'pharma_#00', 'in' => ['pharma_#00', 'pharma_#00' ] ],
        'com028' => ['type' => Recipe::ManualAnywhere, 'out' => ['drug_#00', 'drug_random_#00', 'drug_water_#00', 'water_cleaner_#00', 'pharma_#00'], 'provoking' => 'pharma_part_#00', 'in' => ['pharma_part_#00', 'pharma_part_#00' ] ],
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

        $object = $manager->getRepository(BuildingPrototype::class)->findOneByName( $entry_unique_id );
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
