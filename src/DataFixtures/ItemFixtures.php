<?php

namespace App\DataFixtures;

use App\Entity\ItemCategory;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ItemFixtures extends Fixture
{
    public static $item_category_data = [

        ["name" => "Rsc", "label" => "Baustoffe", "parent" => null, "ordering" => 0],
        ["name" => "Furniture", "label" => "Einrichtungen", "parent" => null, "ordering" => 1],
        ["name" => "Weapon", "label" => "Waffenarsenal", "parent" => null, "ordering" => 2],
        ["name" => "Box", "label" => "Taschen und Behälter", "parent" => null, "ordering" => 3],
        ["name" => "Armor", "label" => "Verteidigung", "parent" => null, "ordering" => 4],
        ["name" => "Drug", "label" => "Apotheke und Labor", "parent" => null, "ordering" => 5],
        ["name" => "Food", "label" => "Grundnahrungsmittel", "parent" => null, "ordering" => 6],
        ["name" => "Misc", "label" => "Sonstiges", "parent" => null, "ordering" => 7],
    ];

    public static $item_prototype_data = [
	
		['label' => 'Drahtspule', 'icon' => 'wire', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                  // -- ---
        ['label' => 'Ölkännchen', 'icon' => 'oilcan', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                // -- ---
        ['label' => 'Konvexlinse', 'icon' => 'lens', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                 // -- ---
		['label' => 'Riemen', 'icon' => 'courroie', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                  // -- ---
        ['label' => 'Sprengstoff', 'icon' => 'explo', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                // -- ---
        ['label' => 'Klebeband', 'icon' => 'rustine', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                // -- ---
		['label' => 'Teleskop', 'icon' => 'scope', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                   // -- ---
        ['label' => 'Kupferrohr', 'icon' => 'tube', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                  // -- ---
        ['label' => 'Elektronisches Bauteil', 'icon' => 'electro', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                   // -- ---
        ['label' => 'Zünder', 'icon' => 'deto', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                      // -- ---
        ['label' => 'Defektes Elektrogerät', 'icon' => 'electro_box', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // -- ---
        ['label' => 'Getriebe', 'icon' => 'mecanism', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Laserdiode', 'icon' => 'diode', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                 // -- ---
        ['label' => 'Trockene Kräuter', 'icon' => 'ryebag', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // -- ---
        ['label' => 'Ohrstöpsel', 'icon' => 'bquies', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                // -- ---
        ['label' => 'Kaputter Stock', 'icon' => 'staff2', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // -- ---
		
		['label' => 'Handvoll Schrauben und Muttern', 'icon' => 'meca_parts', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                      // -- ---
		['label' => 'Batterie', 'icon' => 'pile', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
		['label' => 'Krummes Holzbrett', 'icon' => 'wood2', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // -- ---
		['label' => 'Alteisen', 'icon' => 'metal', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                 // -- ---
		['label' => 'Zusammengeschusterter Holzbalken', 'icon' => 'wood_beam', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                      // -- ---
        ['label' => 'Metallstruktur', 'icon' => 'metal_beam', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                       // -- ---
        ['label' => 'Metalltrümmer', 'icon' => 'metal_bad', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // -- ---
        ['label' => 'Verrotteter Baumstumpf', 'icon' => 'wood_bad', 'category' => 'Rsc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // -- ---
		
		
		['label' => 'Gut erhaltener Holzscheit', 'icon' => 'wood_log', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>true, 'watchpoint' => 0],                              // -- ---
		['label' => 'Minibar', 'icon' => 'machine_3', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>true, 'watchpoint' => 19],                                                 // -- Out
		['label' => 'MagLite Kinderlampe (aus)', 'icon' => 'maglite_off', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 0],                      // In Out
        ['label' => 'MagLite Kinderlampe (1 Ladung)', 'icon' => 'maglite_1', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 0],                       // -- ---
        ['label' => 'MagLite Kinderlampe (2 Ladungen)', 'icon' => 'maglite_2', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 0],                     // -- ---
		['label' => 'Krebserregender Ofen', 'icon' => 'machine_2', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>true, 'watchpoint' => 15],                                    // -- Out
		['label' => 'Alte Waschmaschine', 'icon' => 'machine_1', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>true, 'watchpoint' => 19],                                      // -- Out
		['label' => 'Wütende Mieze (halb verdaut)', 'icon' => 'angryc', 'category' => 'Furniture', 'deco' =>1, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                           // --
		['label' => 'Kassettenradio', 'icon' => 'radio_on', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>false, 'watchpoint' => -10],                                    // -- ---
        ['label' => 'Schaukelstuhl', 'icon' => 'chair', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>true, 'watchpoint' => 15],                                         // -- ---
        ['label' => 'Ausgeschaltete Nachttischlampe', 'icon' => 'lamp', 'category' => 'Furniture', 'deco' =>1, 'heavy' =>false, 'watchpoint' => 4],                        // In Out
        ['label' => 'Perser', 'icon' => 'carpet', 'category' => 'Furniture', 'deco' =>10, 'heavy' =>false, 'watchpoint' => 8],                                             // -- ---
        ['label' => 'Mini Hi-Fi Anlage (defekt)', 'icon' => 'music_part', 'category' => 'Furniture', 'deco' =>1, 'heavy' =>true, 'watchpoint' => 0],                       // -- ---
        ['label' => 'Kette + Vorhängeschloss', 'icon' => 'lock', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // -- ---
        ['label' => 'Fußabstreifer', 'icon' => 'door_carpet', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 0],                                  // -- ---
        ['label' => 'Videoprojektor', 'icon' => 'cinema', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                       // -- ---
        ['label' => 'Nachttischlampe (an)', 'icon' => 'lamp_on', 'category' => 'Furniture', 'deco' =>3, 'heavy' =>false, 'watchpoint' => 10],                               // -- ---
        ['label' => 'Mini Hi-Fi Anlage (an)', 'icon' => 'music', 'category' => 'Furniture', 'deco' =>10, 'heavy' =>true, 'watchpoint' => -20],                               // -- ---
        ['label' => 'Ektorp-Gluten Stuhl', 'icon' => 'chair_basic', 'category' => 'Furniture', 'deco' =>2, 'heavy' =>true, 'watchpoint' => 8],                             // -- Out
        ['label' => 'Revolver (entladen)', 'icon' => 'gun', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 0],                                    // -- ---
        ['label' => 'Sturmgewehr (entladen)', 'icon' => 'machine_gun', 'category' => 'Furniture', 'deco' =>15, 'heavy' =>false, 'watchpoint' => 0],                        // -- ---
        ['label' => 'Teddybär', 'icon' => 'teddy', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Geldbündel', 'icon' => 'money', 'category' => 'Furniture', 'deco' =>7, 'heavy' =>false, 'watchpoint' => 0],                                           // -- ---
        ['label' => 'Schrankkoffer', 'icon' => 'home_box_xl', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 8],                                   // In ---
        ['label' => 'Kartons', 'icon' => 'home_box', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                           // In ---
        ['label' => 'Nagelbare Barrikade', 'icon' => 'home_def', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                // In ---
        ['label' => 'Maschendrahtzaunstück', 'icon' => 'fence', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                      // -- ---
        ['label' => 'Verfluchter Teddybär', 'icon' => 'teddy', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Schnellgebauter Tisch', 'icon' => 'bureau', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 23],                                // -- ---
        ['label' => 'Leerer Automat', 'icon' => 'distri', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 30],                                       // -- ---
        ['label' => 'Unpersönliche Explodierende Fußmatte', 'icon' => 'trapma', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'hideInForeignChest' => true],                // -- ---
        ['label' => 'Chuck-Figur', 'icon' => 'chudol', 'category' => 'Furniture', 'deco' =>15, 'heavy' =>false, 'watchpoint' => 0],                                        // -- ---
        ['label' => 'Verfluchte HiFi', 'icon' => 'hifiev', 'category' => 'Furniture', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                      // -- ---
        ['label' => 'Phil Collins CD', 'icon' => 'cdphil', 'category' => 'Furniture', 'deco' =>1, 'heavy' =>false, 'watchpoint' => 0],                                     // -- ---
        ['label' => 'Britney Spears CD', 'icon' => 'cdbrit', 'category' => 'Furniture', 'deco' =>3, 'heavy' =>false, 'watchpoint' => 0],                                   // -- ---
        ['label' => 'Best of The King CD', 'icon' => 'cdelvi', 'category' => 'Furniture', 'deco' =>7, 'heavy' =>false, 'watchpoint' => 0],                                 // -- ---
        ['label' => 'Rock n Roll HiFi', 'icon' => 'dfhifi', 'category' => 'Furniture', 'deco' =>10, 'heavy' =>true, 'watchpoint' => 0],                                     // -- ---
        ['label' => 'Verteidigende HiFi', 'icon' => 'dfhifi', 'category' => 'Furniture', 'deco' =>10, 'heavy' =>true, 'watchpoint' => 0],                                   // -- ---
		['label' => 'Großer knuddeliger Kater', 'icon' => 'pet_cat', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>false, 'watchpoint' => 10, 'fragile' => true],                              // -- Out
		['label' => 'Kaffeekocher', 'icon' => 'coffee_machine', 'category' => 'Furniture', 'deco' =>5, 'heavy' =>true, 'watchpoint' => 0],                                       // -- ---
		['label' => 'PC-Gehäuse', 'icon' => 'pc', 'category' => 'Furniture', 'deco' =>3, 'heavy' =>true, 'watchpoint' => 11],                                                  // -- Out
		['label' => 'Selbstgebaute Gitarre', 'icon' => 'guitar', 'category' => 'Furniture', 'deco' =>6, 'heavy' =>false, 'watchpoint' => 19],                                     // In ---
		
	
        ['label' => 'Matratze', 'icon' => 'bed', 'category' => 'Armor', 'deco' =>3, 'heavy' =>true, 'watchpoint' => 25],                                                    // -- ---
        ['label' => 'Bissiger Hund', 'icon' => 'pet_dog', 'category' => 'Armor', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                          // -- Out
        ['label' => 'Blechplatte', 'icon' => 'plate', 'category' => 'Armor', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Alte Tür', 'icon' => 'door', 'category' => 'Armor', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 10],                                                   // -- ---
        ['label' => 'Unförmige Zementblöcke', 'icon' => 'concrete_wall', 'category' => 'Armor', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                            // -- Out
        ['label' => 'Holzbock', 'icon' => 'trestle', 'category' => 'Armor', 'deco' =>1, 'heavy' =>true, 'watchpoint' => 4],                                                // -- ---
        ['label' => 'Järpen-Tisch', 'icon' => 'table', 'category' => 'Armor', 'deco' =>3, 'heavy' =>true, 'watchpoint' => 15],                                              // -- ---
        ['label' => 'Solide Holzplatte', 'icon' => 'wood_plate', 'category' => 'Armor', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                    // -- ---
        ['label' => 'Autotür', 'icon' => 'car_door', 'category' => 'Armor', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 40],                                                // -- ---
        ['label' => 'Fackel', 'icon' => 'torch', 'category' => 'Armor', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 15],                                                   // -- Out
		    ['label' => 'Dackel', 'icon' => 'tekel', 'category' => 'Armor', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                  // -- Out
		
		
        ['label' => 'Aqua-Splash (leer)', 'icon' => 'watergun_opt_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // In Out
        ['label' => 'Kanisterpumpe (leer)', 'icon' => 'jerrygun_off', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                              // In Out
        ['label' => 'Plastiktüte', 'icon' => 'grenade_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                      // In Out
        ['label' => 'Plastiktüte mit Sprengstoff', 'icon' => 'bgrenade_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                     // -- ---
        ['label' => 'Wasserpistole (leer)', 'icon' => 'watergun_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In Out
        ['label' => 'Handmixer (ohne Strom)', 'icon' => 'mixergun_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // In Out
        ['label' => 'Kettensäge (ohne Strom)', 'icon' => 'chainsaw_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                          // In Out
        ['label' => 'Batteriewerfer 1-PDTG (entladen)', 'icon' => 'pilegun_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                 // In Out
        ['label' => 'Taser (ohne Strom)', 'icon' => 'taser_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Zerstörer (entladen)', 'icon' => 'big_pgun_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In Out
        ['label' => 'Batteriewerfer Mark II (leer)', 'icon' => 'pilegun_up_empty', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                 // In Out
		['label' => 'Große rostige Kette', 'icon' => 'chain', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                                        // -- Out
        ['label' => 'ANZAC Badge', 'icon' => 'badge', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 40],                                              // -- ---
		
		
		['label' => 'Batteriewerfer 1-PDTG (geladen)', 'icon' => 'pilegun', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                       // -- Out
		['label' => 'Starker Laserpointer (4 Schuss)', 'icon' => 'lpoint4', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       // -- Out
        ['label' => 'Starker Laserpointer (3 Schuss)', 'icon' => 'lpoint3', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       // -- Out
        ['label' => 'Starker Laserpointer (2 Schuss)', 'icon' => 'lpoint2', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       // -- Out
        ['label' => 'Starker Laserpointer (1 Schuss)', 'icon' => 'lpoint1', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       // -- Out
        ['label' => 'Starker Laserpointer (Leer)', 'icon' => 'lpoint', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In Out
        ['label' => 'Taser', 'icon' => 'taser', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                                   // -- Out
        ['label' => 'Handmixer (geladen)', 'icon' => 'mixergun', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 9],                                  // -- Out
        ['label' => 'Kettensäge (geladen)', 'icon' => 'chainsaw', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 30],                                  // -- Out
        ['label' => 'Rasenmäher', 'icon' => 'lawn', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 15],                                                // -- Out
        ['label' => 'Verstellbarer Schraubenschlüssel', 'icon' => 'wrench', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 2],                       // -- Out
        ['label' => 'Schraubenzieher', 'icon' => 'screw', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 5],                                         // -- Out
        ['label' => 'Großer trockener Stock', 'icon' => 'staff', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                  // -- Out
        ['label' => 'Jagdmesser', 'icon' => 'knife', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                                              // -- Out
        ['label' => 'Machete', 'icon' => 'cutcut', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 5],                                                // -- Out
        ['label' => 'Lächerliches Taschenmesser', 'icon' => 'small_knife', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 5],                        // -- Out
        ['label' => 'Schweizer Taschenmesser', 'icon' => 'swiss_knife', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                           // -- Out
        ['label' => 'Teppichmesser', 'icon' => 'cutter', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                                          // -- Out
        ['label' => 'Dosenöffner', 'icon' => 'can_opener', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                        // -- Out
        ['label' => 'Wasserbombe', 'icon' => 'grenade', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8, 'fragile' => true],                                           // -- Out
        ['label' => 'Explodierende Wasserbombe', 'icon' => 'bgrenade', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 20, 'fragile' => true],                            // -- Out
        ['label' => 'Aqua-Splash (3 Ladungen)', 'icon' => 'watergun_opt_3', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                       // -- Out
        ['label' => 'Aqua-Splash (2 Ladungen)', 'icon' => 'watergun_opt_2', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                       // -- Out
        ['label' => 'Aqua-Splash (1 Ladung)', 'icon' => 'watergun_opt_1', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                         // -- Out
        ['label' => 'Zerstörer (geladen)', 'icon' => 'big_pgun', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                                  // -- Out
        ['label' => 'Kanisterpumpe (einsatzbereit)', 'icon' => 'jerrygun', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                        // -- Out
        ['label' => 'Angeknackster menschlicher Knochen', 'icon' => 'bone', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                       // -- Out
        ['label' => 'Wasserpistole (3 Ladungen)', 'icon' => 'watergun_3', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 9],                         // -- Out
        ['label' => 'Wasserpistole (2 Ladungen)', 'icon' => 'watergun_2', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 9],                         // -- Out
        ['label' => 'Wasserpistole (1 Ladung)', 'icon' => 'watergun_1', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 9],                           // -- Out
        ['label' => 'Aqua-Splash (5 Ladungen)', 'icon' => 'watergun_opt_5', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                       // -- Out
        ['label' => 'Aqua-Splash (4 Ladungen)', 'icon' => 'watergun_opt_4', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                       // -- Out
        ['label' => 'Batteriewerfer Mark II (geladen)', 'icon' => 'pilegun_up', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 11],                   // -- Out
        ['label' => 'Verbrauchte Fackel', 'icon' => 'torch_off', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                  // -- Out
        ['label' => 'Mobiltelefon', 'icon' => 'iphone', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8, 'fragile' => true],                                           // -- Out
        ['label' => 'Explosive Pampelmuse', 'icon' => 'boomfruit', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                                // -- Out
        ['label' => 'Pfahlwerfer', 'icon' => 'rlaunc', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8],                                            // --
        ['label' => 'Kalaschni-Splash', 'icon' => 'kalach', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 11],                                        // -- ---
        ['label' => 'Kalaschni-Splash (leer)', 'icon' => 'kalach', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In Out
        ['label' => 'Tretmine', 'icon' => 'claymo', 'category' => 'Weapon', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 50],                                               // -- ---
		
		
		
		['label' => 'Einkaufswagen', 'icon' => 'cart', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20],                                             // -- ---
        ['label' => 'Extra Tasche', 'icon' => 'bag', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Superpraktischer Rucksack', 'icon' => 'bagxl', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // -- ---
        ['label' => 'Gürtel mit Tasche', 'icon' => 'pocket_belt', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                  // -- ---
		
		
        ['label' => 'Metallkiste', 'icon' => 'chest', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 8],                                                 // In Out
        ['label' => 'Großer Metallkoffer', 'icon' => 'chest_xl', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 10],                                      // In Out
        ['label' => 'Werkzeugkiste', 'icon' => 'chest_tools', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 5],                                         // In Out
        ['label' => 'Habseligkeiten eines Bürgers', 'icon' => 'chest_citizen', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                        // In Out
        ['label' => 'Ein Paket', 'icon' => 'book_gen_box', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                           // In Out
        ['label' => 'Kiste mit Materialien (3)', 'icon' => 'rsc_pack_3', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                              // In Out
        ['label' => 'Kiste mit Materialien (2)', 'icon' => 'rsc_pack_2', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                              // In Out
        ['label' => 'Kiste mit Materialien (1)', 'icon' => 'rsc_pack_1', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                              // In Out
        ['label' => 'Vorräte eines umsichtigen Bürgers', 'icon' => 'chest_hero', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                      // In Out
        ['label' => 'Postpaket', 'icon' => 'postal_box', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Safe', 'icon' => 'safe', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                                         // In Out
        ['label' => 'Architektenkoffer', 'icon' => 'bplan_box', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                       // In Out
        ['label' => 'Versiegelter Architektenkoffer', 'icon' => 'bplan_box_e', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                        // In Out
        ['label' => 'Schrödingers Box', 'icon' => 'catbox', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                          // In Out
		['label' => 'Ein Briefumschlag', 'icon' => 'book_gen_letter', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In Out
		['label' => 'Überraschungskiste (3 Geschenke)', 'icon' => 'chest_christmas_3', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],               // In Out
        ['label' => 'Überraschungskiste (2 Geschenke)', 'icon' => 'chest_christmas_2', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],               // In Out
        ['label' => 'Überraschungskiste (1 Geschenk)', 'icon' => 'chest_christmas_1', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                // In Out
		['label' => 'Lunch-Box', 'icon' => 'food_armag', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // In Out
		['label' => 'Gesellschaftsspiel', 'icon' => 'game_box', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                      // In Out
        ['label' => 'Geschenkpaket', 'icon' => 'postal_box', 'category' => 'Box', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                // In Out
        ['label' => 'Großes Geschenkpaket', 'icon' => 'postal_box_xl', 'category' => 'Box', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                // In Out

        ['label' => 'Zeltplane', 'icon' => 'sheet', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                                                 // -- ---
        ['label' => 'Sperrholzstück', 'icon' => 'out_def', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // -- ---
        ['label' => 'Ekliger Hautfetzen', 'icon' => 'smelly_meat', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                  // -- ---
        ['label' => 'Bandage', 'icon' => 'bandage', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                 // In Out
        ['label' => 'Pharmazeutische Substanz', 'icon' => 'pharma', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // -- ---
        ['label' => 'Zyanid', 'icon' => 'cyanure', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                  // In Out
        ['label' => 'Micropur Brausetablette', 'icon' => 'water_cleaner', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // -- ---
        ['label' => 'Dickflüssige Substanz', 'icon' => 'pharma_part', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // -- ---
        ['label' => 'Blutdurchtränkter Verband', 'icon' => 'infect_poison_part', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                    // -- ---
        ['label' => 'LSD', 'icon' => 'lsd', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                         //
		['label' => 'Kleine Zen-Fibel', 'icon' => 'lilboo', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // In Out
        ['label' => 'Beruhigungsspritze', 'icon' => 'xanax', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                      // In Out
        ['label' => 'Anaboles Steroid', 'icon' => 'drug', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                         // In Out
		
        ['label' => 'Twinoid 500mg', 'icon' => 'drug_hero', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                       // In Out
        ['label' => 'Hydraton 100mg', 'icon' => 'drug_water', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // In Out
        ['label' => 'Etikettenloses Medikament', 'icon' => 'drug_random', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                         // In Out
        ['label' => 'Paracetoid 7g', 'icon' => 'disinfect', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                       // In Out
        ['label' => 'Abgelaufene Betapropin-Tablette 5mg', 'icon' => 'beta_drug_bad', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],             // In Out
        ['label' => 'Betapropin-Tablette 5mg', 'icon' => 'beta_drug', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                             // In Out
        ['label' => 'Ghul-Serum', 'icon' => 'vagoul', 'category' => 'Drug', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             //
		
		
        ['label' =>"Zonenmarker 'Radius'", 'icon' => 'tagger', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                    // -- Out
        ['label' => 'Unkrautbekämpfungsmittel Ness-Quick', 'icon' => 'digger', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                     // -- Out
        ['label' => 'Schießpulverbombe', 'icon' => 'flash', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // -- Out
        ['label' => 'Radius Mark II (entladen)', 'icon' => 'radius_mk2_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                      // -- ---
        ['label' => 'Radius Mark II', 'icon' => 'radius_mk2', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                      // -- Out
        ['label' => 'Wasserspender (leer)', 'icon' => 'water_can_empty', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                            // -- ---
        ['label' => 'Makabre Bombe', 'icon' => 'flesh', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            // -- Out
        ['label' => 'Notizzettel eines Verbannten', 'icon' => 'banned_note', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       //
        ['label' => 'Magnet-Schlüssel', 'icon' => 'magneticKey', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                   // --
        ['label' => 'Schlagschlüssel', 'icon' => 'bumpKey', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // --
        ['label' => 'Flaschenöffner', 'icon' => 'classicKey', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                             // --
        ['label' => 'Abdruck vom Magnet-Schlüssel', 'icon' => 'prints', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            //
        ['label' => 'Abdruck vom Schlagschlüssel', 'icon' => 'prints', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                             //
        ['label' => 'Abdruck vom Flaschenöffner', 'icon' => 'prints', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 //
		
        ['label' => 'Ration Wasser', 'icon' => 'water', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Offene Konservendose', 'icon' => 'can_open', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 2],                                   // In Out
        ['label' => 'Undefinierbares Fleisch', 'icon' => 'undef', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 2],                                   // In Out
        ['label' => 'Heißer Kaffee', 'icon' => 'coffee', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                            // In Out
        ['label' => 'Verdächtiges Gemüse', 'icon' => 'vegetable', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                   // In Out
        ['label' => 'Doggybag', 'icon' => 'food_bag', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // In Out
        ['label' => 'Tüte mit labbrigen Chips', 'icon' => 'food_bar1', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                              // In Out
        ['label' => 'Verschimmelte Waffeln', 'icon' => 'food_bar2', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Trockene Kaugummis', 'icon' => 'food_bar3', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                    // In Out
        ['label' => 'Ranzige Butterkekse', 'icon' => 'food_biscuit', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In Out
        ['label' => 'Angebissene Hähnchenflügel', 'icon' => 'food_chick', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // In Out
        ['label' =>"Abgelaufene Pim's Kekse", 'icon' => 'food_pims', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // In Out
        ['label' => 'Fades Gebäck', 'icon' => 'food_tarte', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                         // In Out
        ['label' => 'Verschimmelte Stulle', 'icon' => 'food_sandw', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Chinesische Nudeln', 'icon' => 'food_noodles', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Verdächtige Speise', 'icon' => 'dish', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                         // In Out
        ['label' => 'Wasserspender (1 Ration)', 'icon' => 'water_can_1', 'category' => 'Food', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20],                             // In Out
        ['label' => 'Wasserspender (2 Rationen)', 'icon' => 'water_can_2', 'category' => 'Food', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20],                           // In Out
        ['label' => 'Wasserspender (3 Rationen)', 'icon' => 'water_can_3', 'category' => 'Food', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20],                           // In Out
        ['label' => 'Aasbeerenbrei', 'icon' => 'fruit', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Gereinigtes modriges Wasser', 'icon' => 'water_cup', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // In Out
        ['label' => 'Brezel', 'icon' => 'bretz', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                    // In Out
        ['label' => 'Mutterkorn', 'icon' => 'fungus', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Korn-Bräu', 'icon' => 'hmbrew', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                                // In Out
        ['label' => 'Verdächtiger Traubensaft', 'icon' => 'omg_this_will_kill_you', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                 //
		['label' => 'Nahrungsmittelkiste', 'icon' => 'chest_food', 'category' => 'Food', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                    // In Out
		['label' => 'Weihnachts-Süßigkeiten', 'icon' => 'christmas_candy', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                    // In Out

        ['label' => 'Leckeres Steak', 'icon' => 'meat', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4],                                           // In Out
        ['label' => 'Gewürzte chinesische Nudeln', 'icon' => 'food_noodles_hot', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                  // In Out
        ['label' => 'Darmmelone', 'icon' => 'vegetable_tasty', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                    // In Out
        ['label' => 'Leckere Speise', 'icon' => 'dish_tasty', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 6],                                     // In Out
        ['label' => 'Eine Handvoll Bonbons', 'icon' => 'food_candies', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In Out
        ['label' => 'Geröstete Marshmallows', 'icon' => 'chama_tasty', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In Out
        ['label' => 'Sägemehlsteak', 'icon' => 'woodsteak', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                       // In Out
        ['label' => 'Ei', 'icon' => 'egg', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                        // In Out
        ['label' => 'Apfel', 'icon' => 'apple', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                                   // In Out
		
		
        ['label' => 'Grüne Bierflasche', 'icon' => 'vodka_de', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                    // In Out
        ['label' => 'Vodka Marinostov', 'icon' => 'vodka', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                    // In Out
        ['label' =>"'Wake The Dead'", 'icon' => 'rhum', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                          // In Out
        ['label' => 'Bierkrug', 'icon' => 'fest', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0, 'fragile' => true],                                                 // In Out
		
        ['label' => 'Menschenfleisch', 'icon' => 'hmeat', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 20],                                         // In Out
        ['label' => 'Knochen mit Fleisch', 'icon' => 'bone_meat', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 10],                                 // In Out
        ['label' => 'Leiche eines Reisenden', 'icon' => 'cadaver', 'category' => 'Food', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                 // In Out
		
        ['label' => 'Konservendose', 'icon' => 'can', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Getrocknete Marshmallows', 'icon' => 'chama', 'category' => 'Food', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // -- ---
     	
		
        ['label' => 'Staubiges Buch', 'icon' => 'rp_book', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                           // In Out
        ['label' => 'Ein paar Würfel', 'icon' => 'dice', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In Out
        ['label' => 'Motor', 'icon' => 'engine', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 40],                                                      // -- ---
        ['label' => 'Vibrator (geladen)', 'icon' => 'vibr', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => -5],                                          // In ---
        ['label' => 'Wackliger Einkaufswagen', 'icon' => 'cart_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                 // -- ---
        ['label' => 'Unvollständiger Kaffeekocher', 'icon' => 'coffee_machine_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                  // -- ---
        ['label' => 'Reparaturset (kaputt)', 'icon' => 'repair_kit_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // -- ---
        ['label' => 'Reparaturset', 'icon' => 'repair_kit', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // In Out
        ['label' => 'Elektrischer Bauchmuskeltrainer (ohne Strom)', 'icon' => 'sport_elec_empty', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],    // In Out
        ['label' => 'Elektrischer Bauchmuskeltrainer (geladen)', 'icon' => 'sport_elec', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],             // In Out
        ['label' => 'Zementsack', 'icon' => 'concrete', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Unvollständiges Kartenspiel', 'icon' => 'cards', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In Out
        ['label' => 'Vibrator (entladen)', 'icon' => 'vibr_empty', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                   // In Out
        ['label' => 'Metallsäge', 'icon' => 'saw_tool', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                              // -- ---
        ['label' => 'Beschädigte Metallsäge', 'icon' => 'saw_tool_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                             // -- ---
        ['label' => 'Reparatur Fix', 'icon' => 'repair_one', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                         // In Out
        ['label' => 'Unvollständiger Motor', 'icon' => 'engine_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                 // -- ---
        ['label' => 'Aufgewelltes Blatt', 'icon' => 'rp_scroll', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // In Out
        ['label' => 'Unleserliches Notizbuch', 'icon' => 'rp_book2', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Blätterstapel', 'icon' => 'rp_sheets', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // In Out
        ['label' => 'Giftfläschchen', 'icon' => 'poison', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            // In Out
        ['label' => 'Ätzmittel', 'icon' => 'poison_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            // -- ---
        ['label' => 'Normaler Bauplan (gewöhnlich)', 'icon' => 'bplan_c', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In ---
        ['label' => 'Normaler Bauplan (ungewöhnlich)', 'icon' => 'bplan_u', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // In ---
        ['label' => 'Normaler Bauplan (selten)', 'icon' => 'bplan_r', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In ---
        ['label' => 'Normaler Bauplan (sehr selten!)', 'icon' => 'bplan_e', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // In ---
        ['label' => 'Hotel-Bauplan (ungewöhnlich)', 'icon' => 'hbplan_u', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In ---
        ['label' => 'Hotel-Bauplan (selten)', 'icon' => 'hbplan_r', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                  // In ---
        ['label' => 'Hotel-Bauplan (sehr selten!)', 'icon' => 'hbplan_e', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // In ---
        ['label' => 'Bunker-Bauplan (ungewöhnlich)', 'icon' => 'bbplan_u', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // In ---
        ['label' => 'Bunker-Bauplan (selten)', 'icon' => 'bbplan_r', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // In ---
        ['label' => 'Bunker-Bauplan (sehr selten!)', 'icon' => 'bbplan_e', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // In ---
        ['label' => 'Hospital-Bauplan (ungewöhnlich)', 'icon' => 'mbplan_u', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                         // In ---
        ['label' => 'Hospital-Bauplan (selten)', 'icon' => 'mbplan_r', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // In ---
        ['label' => 'Hospital-Bauplan (sehr selten!)', 'icon' => 'mbplan_e', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                         // In ---
        ['label' => 'Verirrte Seele', 'icon' => 'soul_blue', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                         //
        ['label' => 'Gequälte Seele', 'icon' => 'soul_red', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            //
        ['label' => 'Starke Seele', 'icon' => 'soul_yellow', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            //
        ['label' => 'Schwache Seele', 'icon' => 'soul_blue', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                         //
        ['label' => 'Ein Etikett', 'icon' => 'rp_scroll', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                            // In Out
		
		
		['label' => 'Kassettenradio (ohne Strom)', 'icon' => 'radio_off', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                      // In Out
        ['label' => 'Streichholzschachtel', 'icon' => 'lights', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // -- ---
        ['label' => 'Leuchtrakete', 'icon' => 'flare', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => -10],                                              // --
        ['label' => 'Starke Gewürze', 'icon' => 'spices', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                           // -- ---
        ['label' => 'Raketenpulver', 'icon' => 'powder', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // -- ---
        ['label' => 'Loses Werkzeug', 'icon' => 'repair_kit_part_raw', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                              // -- ---
        ['label' => 'Ein Brief ohne Adresse', 'icon' => 'rp_letter', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                // In Out
        ['label' => 'Betriebsanleitung', 'icon' => 'rp_manual', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // In Out
        ['label' => 'Fotoalbum', 'icon' => 'rp_book', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // In Out
        ['label' => 'Angefangene Zigarettenschachtel', 'icon' => 'cigs', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            //
        ['label' => 'Druckregler PDTT Mark II', 'icon' => 'pilegun_upkit', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // -- ---
        ['label' => 'Zerquetschte Batterie', 'icon' => 'pile_broken', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // -- ---
        ['label' => 'Eine Enzyklopädie', 'icon' => 'rp_twin', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                       // In Out
        ['label' => 'Aasbeeren', 'icon' => 'fruit_sub_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // -- ---
        ['label' => 'Schleimige Kugel', 'icon' => 'fruit_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // -- ---
        ['label' => 'Fleischfetzen', 'icon' => 'flesh_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                        // -- ---
        ['label' => 'Abgetragene rote Jacke', 'icon' => 'christmas_suit_1', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                         // -- ---
        ['label' => 'Zerrissene rote Hose', 'icon' => 'christmas_suit_2', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                           // -- ---
        ['label' => 'Schweißtriefende rote Mütze', 'icon' => 'christmas_suit_3', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                    // -- ---
        ['label' => 'Übelriechender Anzug aus einer anderen Zeit', 'icon' => 'christmas_suit_full', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0], //
        ['label' => 'Angenagte Leiche', 'icon' => 'cadaver_remains', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                 // -- ---
        ['label' =>"Rauchgranate 'Tannenduft'", 'icon' => 'smoke_bomb', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                            // --
        ['label' => 'Sandball', 'icon' => 'sand_ball', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                              //
        ['label' => 'Santas Rentier', 'icon' => 'renne', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 30],                                             // -- ---
        ['label' => 'Osterei', 'icon' => 'paques', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 38],                                                  // -- ---
        ['label' => 'Geistiger Beistand', 'icon' => 'chkspk', 'category' => 'Misc', 'deco' => 0, 'heavy' => false, 'watchpoint' => 0],                                       // -- ---
        ['label' => 'Fette Python', 'icon' => 'pet_snake2', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0, 'fragile' => true],                                          // -- ---
        ['label' => 'Bürgerbekleidung', 'icon' => 'basic_suit', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                     // -- ---
        ['label' => 'Dreckige Bürgerbekleidung', 'icon' => 'basic_suit_dirt', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                       // -- ---
        ['label' => 'Tarnanzug', 'icon' => 'vest_on', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                               // -- ---
        ['label' => 'Tarnanzug (abgelegt)', 'icon' => 'vest_off', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                   //
        ['label' => 'Kleine Schaufel', 'icon' => 'pelle', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                           // -- ---
        ['label' => 'Dreibeiniger Malteser', 'icon' => 'tamed_pet', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // --
        ['label' => 'Dreibeiniger Malteser (gedopt)', 'icon' => 'tamed_pet_drug', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                   // --
        ['label' => 'Dreibeiniger Malteser (erschöpft)', 'icon' => 'tamed_pet_off', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                 // -- ---
        ['label' => 'Survivalbuch', 'icon' => 'surv_book', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                          // --
        ['label' => 'Schraubenschlüssel', 'icon' => 'keymol', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                       // -- ---
        ['label' => 'Schutzschild', 'icon' => 'shield', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // -- ---
        ['label' => 'Voodoo-Maske', 'icon' => 'shaman', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
        ['label' => 'Kamera aus Vorkriegs-Tagen', 'icon' => 'photo_3', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
        ['label' => 'Kamera aus Vorkriegs-Tagen', 'icon' => 'photo_2', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
        ['label' => 'Kamera aus Vorkriegs-Tagen', 'icon' => 'photo_1', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
        ['label' => 'Kamera aus Vorkriegs-Tagen', 'icon' => 'photo_off', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
		['label' => 'Huhn', 'icon' => 'pet_chick', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 8, 'fragile' => true],                                                // -- Out
        ['label' => 'Übelriechendes Schwein', 'icon' => 'pet_pig', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20, 'fragile' => true],                                 // -- Out
        ['label' => 'Riesige Ratte', 'icon' => 'pet_rat', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 4, 'fragile' => true],                                         // -- Out
        ['label' => 'Zwei-Meter Schlange', 'icon' => 'pet_snake', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 20, 'fragile' => true],                                  // -- Out
		['label' => 'Holzkistendeckel', 'icon' => 'wood_plate_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                                // -- ---
		['label' => 'Unverarbeitete Blechplatten', 'icon' => 'plate_raw', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                           // -- ---
		['label' => 'Kanisterpumpe (zerlegt)', 'icon' => 'jerrygun_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                          // -- ---
		['label' => 'Unvollständige Kettensäge', 'icon' => 'chainsaw_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                         // -- ---
		['label' => 'Unvollständiger Handmixer', 'icon' => 'mixergun_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                        // -- ---
		['label' => 'Zerlegter Rasenmäher', 'icon' => 'lawn_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                 // -- ---
		['label' => 'Unvollständiger Zerstörer', 'icon' => 'big_pgun_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                        // -- ---
		['label' => 'Aqua-Splash (zerlegt)', 'icon' => 'watergun_opt_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                        // -- ---
		['label' => 'Beschädigte Autotür', 'icon' => 'car_door_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 0],                               // -- ---
		['label' => 'Kanister', 'icon' => 'jerrycan', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                                             // In ---
		['label' => 'Eisengefäß mit modrigem Wasser', 'icon' => 'water_cup_part', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                 // In Out
		['label' => 'Möbelpackung', 'icon' => 'deco_box', 'category' => 'Misc', 'deco' =>0, 'heavy' =>true, 'watchpoint' => 8],                                             // -- ---
		['label' => 'Abgenutzte Kuriertasche', 'icon' => 'bplan_drop', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],                               // In Out
		['label' => 'Unidentifizierbare Trümmerstücke', 'icon' => 'broken', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],
		['label' => 'Munitionsgriff', 'icon' => 'bullets', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0],

		
        ['label' => 'Super-Flaum-Pulver', 'icon' => 'firework_powder', 'category' => 'Furniture', 'deco' => 5, 'heavy' => false, 'watchpoint' => 0],                       // -- ---
        ['label' => 'Flush-Abschussrohr', 'icon' => 'firework_tube', 'category' => 'Furniture', 'deco' => 2, 'heavy' => true, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Feuerwerkskiste', 'icon' => 'firework_box', 'category' => 'Furniture', 'deco' => 3, 'heavy' => true, 'watchpoint' => 0],                                 // In Out
        ['label' => 'Kürbis ausgestorben', 'icon' => 'pumpkin_off', 'category' => 'Furniture', 'deco' => 5, 'heavy' => true, 'watchpoint' => 0],                              // -- ---
        ['label' => 'Beleuchteter Kürbis', 'icon' => 'pumpkin_on', 'category' => 'Armor', 'deco' => 15, 'heavy' => true, 'watchpoint' => 0],                                   // -- ---
        ['label' => 'Krähengirlande', 'icon' => 'xmas_gift', 'category' => 'Furniture', 'deco' => 2, 'heavy' => false, 'watchpoint' => 0],                              // -- Out
        ['label' => 'Mystischer Trank', 'icon' => 'potion', 'category' => 'Food', 'deco' => 0, 'heavy' => false, 'watchpoint' => 0],                              // -- Out
		['label' => 'Krähenelfe', 'icon' => 'food_xmas', 'category' => 'Food', 'deco' => 0, 'heavy' => false, 'watchpoint' => 0],                              // -- Out
        ['label' => 'Logbuch Schokolade', 'icon' => 'wood_xmas', 'category' => 'Food', 'deco' => 8, 'heavy' => true, 'watchpoint' => 0],                              // -- Out
        ['label' => 'Grüner Kobold-Anzug', 'icon' => 'leprechaun_suit', 'category' => 'Misc', 'deco' =>0, 'heavy' =>false, 'watchpoint' => 0], //
    ];
    
    public static $item_desc_data = [
        'water_#00' => 'Das Wasser gibt dir einmal pro Tag deine Aktionpunkte zurück oder löscht alternativ deinen Durst (unbegrenzt).', // Ration Wasser
        'pile_#00' => 'Die Batterie ist für vieles nützlich. Allerdings ist sie auch ziemlich schnell leer ...', // Batterie
        'can_#00' => 'Auf dem vergilbtem Etikettenbild erkennst du ein leckeres Bohnengericht! Allerdings brauchst du ein Werkzeug, um die Dose öffnen zu können...', // Konservendose
        'can_open_#00' => 'Jetzt mach mal kein Gezeter, schließ dein Augen, mach deinen Mund ganz weit auf und runter damit! Du wirst sehen, dass es dir danach besser geht.', // Offene Konservendose
        'pilegun_#00' => 'Der Batteriewerfer 1-PDTG (geladen) wurde äußerst einfallsreich zusammengeschraubt. Dank einer virtuosen Recyclingprozedur ist er in der Lage, harmlose Batterien in mächtige Geschosse zu verwandeln.', // Batteriewerfer 1-PDTG (geladen)
        'taser_#00' => 'Der Taser ist eine kleine technische Spielerei, mit der man grässliche Elektrostöße in dem Körper seines Opfers jagen kann. Allerdings ist er nicht sehr effektiv, wenn man bedennkt dass den Zombies Schmerzen nichts ausmachen. Vielleicht gelingt es dir ja mit einem Stromstoß ein wichtiges Körperglied oder Organ zum Platzen zu bringen?', // Taser
        'watergun_opt_empty_#00' => 'Eine furchterregende Waffe, die nur noch etwas reines Wasser benötigt.', // Aqua-Splash (leer)
        'mixergun_#00' => 'Rührt elektrisch und sehr schnell - was gibt\'s sonst noch zu sagen? Er hat den Vorteil, dass er nur eine Batterie braucht, die sich mit 40% Wahrscheinlichkeit entlädt.', // Handmixer (geladen)
        'chainsaw_#00' => 'Damit richtest du ein wahres Massaker an. Ob du das aber zu 100% unversehrt überstehst ist eine andere Frage... Diese umgebaute Höllenmaschine funktioniert - man glaubt es kaum - mit einer gewöhnlichen Batterie!', // Kettensäge (geladen)
        'lawn_#00' => 'Du magst es wenn es sauber ist und frisch nach Gras duftet? Vergiss das mal lieber ganz schnell. Wenn du ihn verwendest, wird es ganz anders riechen...', // Rasenmäher
        'wrench_#00' => 'Mit einem verstellbaren Schraubenschlüssel kannst du wahlweise an einem Gegenstand herumbasteln oder etwas zerstören (muss nicht unbedingt ein Gegenstand sein...)', // Verstellbarer Schraubenschlüssel
        'screw_#00' => 'Um Schrauben festzudrehen oder zu lösen. Du kannst mit ihm etwas reparieren und zur Not auch eine Konservendose aufmachen. Wenn du ihn mit voller Wucht in einen Kopf rammst, kann er dir in Extremsituationen auch mal das Leben retten... ', // Schraubenzieher
        'staff_#00' => 'Ein ziemlich großer trockener Stock, mit dem man etwas aufspießen kann. Allerdings sieht er etwas zerbrechlich aus... bete, dass er den Stoß übersteht und nicht bricht.', // Großer trockener Stock
        'knife_#00' => 'Skinner, Buck, Bowie ... , völlig egal, Hauptsache es schneidet.', // Jagdmesser
        'cutcut_#00' => 'Wird für die Entfernung von Unkraut und Pflanzengeflecht benutzt. Das ist auch hier der Fall. Allerdings ist die "Vegetation" hier ziemlich rachsüchtig und laut...', // Machete
        'small_knife_#00' => 'Taschenmesser [Substantiv, n]: Ein Taschenmesser ist ein Messer, dessen Klinge zum gefahrlosen Transport in den Griff eingeklappt werden kann. Wenn du schlau bist, lässt du es bei diesem Messer besser nicht drauf ankommen...', // Lächerliches Taschenmesser
        'swiss_knife_#00' => 'Das Schweizer Taschenmesser ist für viele kleinere Arbeiten nützlich. Für eine Horde lebender Untoter reicht es allerdings nicht ganz aus.', // Schweizer Taschenmesser
        'cutter_#00' => 'Dieses kleine kükengelbe Messer mit ausfahrbarer Klinge passt hervorragend zu der Eingeweidenfarbe deiner untoten Freunde. Vorausgesetzt du kommst so weit.', // Teppichmesser
        'cart_#00' => 'Man nennt ihn auch "Caddy". Niemand weiß so recht, wozu er gut ist, außer um ein paar Gegenstände herumzufahren. Im Sand rollt er leider nicht ganz so gut.', // Einkaufswagen
        'can_opener_#00' => 'Wie der Name schon sagt: Bestens dafür geeignet, um eine Konservendose aufzumachen. Leider ist er etwas zu klein, um auch deine zweibeinigen Kameraden in der Wüste korrekt zu "öffnen".', // Dosenöffner
        'bag_#00' => 'Diese Tasche eignet sich bestens für ausgedehnte Wanderungen unter Freunden, denn sie erlaubt dir, noch mehr Souvenirstücke jeglicher Art (biologische, nukleare, usw..) mitzunehmen.', // Extra Tasche
        'lights_#00' => 'Gib es ruhig zu: Die Zombies sind für dich schon immer Feuer und Flamme gewesen.', // Streichholzschachtel
        'xanax_#00' => 'Entspann dich... das wird nicht die letzte sein, die du brauchst.', // Beruhigungsspritze
        'chair_#00' => 'ZZzzzz... Knarr knarr... ZZzzzz... Knarr knarr...', // Schaukelstuhl
        'rp_book_#00' => 'Ein altes Buch, dessen Seiten größtenteils zerrissen oder verklebt sind. Ein paar sind jedoch lesbar...', // Staubiges Buch
        'bed_#00' => 'ZZzzzz ... Quietsch ... ZZzzzz ... Quietsch', // Matratze
        'lamp_#00' => 'Das ist eine hübsche Nachttischlampe. Allerdings brauchst du eine Batterie, um sie benutzen zu können...', // Ausgeschaltete Nachttischlampe
        'carpet_#00' => 'Leg ihn dir ins Wohnzimmer dann fallen dir all die grässlichen Dinge, die überall am Boden herum liegen nicht mehr so auf...', // Perser
        'music_part_#00' => 'Eine kleine Hi-Fi Anlage vom Discounter nebenan. Schade, dass keine Batterien mehr drin sind, aber vielleicht kannst du ja welche auftreiben...', // Mini Hi-Fi Anlage (defekt)
        'lock_#00' => 'Eine schwere Kette mit passendem Vorhängeschloss. Sie gibt dir ein beruhigendes Gefühl, wenn du mal nicht daheim bist. Für das Stadttor ist sie leider viel zu klein.', // Kette + Vorhängeschloss
        'door_carpet_#00' => 'Du trittst mal ausnahmsweise nicht auf Leichen rum. Ein gutes Gefühl.', // Fußabstreifer
        'dice_#00' => 'Spiele ne Runde mit den anderen Losern.', // Ein paar Würfel
        'engine_#00' => 'Es handelt sich um einen kleinen Motor, der mit Ölspuren und undefinierbaren Brennresten verschmutzt ist und der schwer wie Blei ist.', // Motor
        'courroie_#00' => 'Ein Riemen... Der ist bestimmt für irgendetwas nützlich.', // Riemen
        'meca_parts_#00' => 'Ein paar Teile sind verrostet, andere von einem moosartigen Flaum überzogen... aber sie funktionieren noch halbwegs.', // Handvoll Schrauben und Muttern
        'pet_chick_#00' => 'Sehr schön! Jetzt musst du nur noch einen Metzger finden. Nebenbei bemerkt: Du kannst es auch auf einen Zombie werfen...', // Huhn
        'pet_pig_#00' => 'Sehr schön! Jetzt musst du nur noch einen Metzger finden. Nebenbei bemerkt: Du kannst es auch auf einen Zombie werfen ...', // Übelriechendes Schwein
        'pet_rat_#00' => 'Sehr schön! Jetzt musst du nur noch einen Metzger finden. Nebenbei bemerkt: Du kannst sie auch auf einen Zombie werfen...', // Riesige Ratte
        'pet_dog_#00' => 'Der beste Freund des Menschen. Du kannst ihn wahlweise dein Haus bewachen lassen, ihn auf Zombies hetzen oder dir ein paar leckere Steaks braten...', // Bissiger Hund
        'pet_cat_#00' => 'Ist der knuddelig! Ein wahres Prachtstück für daheim, das dich im Notfall auch gegen Zombies verteidigt. Und wenn du mal Hunger hast ...', // Großer knuddeliger Kater
        'pet_snake_#00' => 'Sie ist grün, beißt und zischt. Du brauchst nur einen Metzger, dann kannst du dir ein paar Reptiliensteaks machen. Auf einen Zombie kannst du sie auch werfen...', // Zwei-Meter Schlange
        'vibr_#00' => 'Du machst es dir daheim gemütlich und entspannst dich... doch dann erlebst du ein böse Überraschung: Dieses Ding ist unglaublich schmerzhaft! Du versuchst es weiter bis du Stück für Stück Gefallen daran findest. Die nach wenige Minuten einsetzende Wirkung ist berauschend! Du schwitzt und zitterst und ein wohlig-warmes Gefühl breitet sich in dir aus...Die Batterie ist komplett leer.', // Vibrator (geladen)
        'drug_#00' => 'Mit dieser Droge siehst Du alles rosa: Du spürst keine Müdigkeit mehr und strotzt nur so vor Kraft! Das bisschen Herzrasen und der verstärkt auftretende Speichelfluss - geschenkt! Vorschicht: Bei übermäßiger Einnahme droht eine Abhängigkeit! Diese wundersamen Pillen füllen eure Aktivitätspunkte von 0 auf 6 wieder auf! Ein Teufelszeug! Leider kann man von den Pillen abhängig werden...', // Anaboles Steroid
        'meat_#00' => 'Es ist labbrig wie weiches Gummi und es riecht nach Fisch ... Was das wohl für ein Fleisch ist? Jedenfalls hat es bestimmt viele Vitamine und Proteine (und sonst hoffentlich nichts).', // Leckeres Steak
        'undef_#00' => 'Du bist dir so gut wie sicher, dass man dieses weiche und glibbrige Etwas essen kann. Zumindest riecht es nicht sooo schlecht und es bewegt sich auch nicht.', // Undefinierbares Fleisch
        'sheet_#00' => 'Eine große, solide Zeltplane, die auch noch wasserdicht ist. Sie eignet sich bestens dazu die Löcher in deinem Hausdach zu schließen. ANMERKUNG: Dieser Gegenstand ist für die Verbesserung deines Hauses zwingend vorgeschrieben.', // Zeltplane
        'bagxl_#00' => 'Dein idealer Begleiter für unterwegs! Massage im Schulter- und Lendenbereich, zweistufig regulierbar und mit großen Seitentaschen. Ein hübsches Logo aus Chrom mit der Auffschrift "Desert Addict" verziert ihn zusätzlich.', // Superpraktischer Rucksack
        'jerrycan_#00' => 'Der "Jerrycan" kann mehrere Rationen nicht-trinkbares Wasser aufnehmen. Sobald die Stadt über eine passende Filteranlage verfügt, kann dieses Wasser trinkbar gemacht werden.', // Kanister
        'wood2_#00' => 'Ein total verschimmeltes Holzbrett, das noch einigermaßen stabil ist...', // Krummes Holzbrett
        'metal_#00' => 'Stinknormales Metall, das zwar schwer ist, aber immer verwendet werden kann!', // Alteisen
        'grenade_#00' => 'Gebrauchsanweisung: Auf einen Zombie werfen und beten, dass sie platzt. Anschließend warten bis es "Splatsch!!" macht.', // Wasserbombe
        'plate_#00' => 'Fast nichts ist besser als eine rostige Blechplatte, um die Zombies am Eindringen zu hindern... Anmerkung: Verteidigungsgegenstände zählen mehr Punkte, wenn sie in der Bank aufbewahrt werden anstatt daheim.', // Blechplatte
        'jerrygun_part_#00' => 'Früher konntest du einem solchen Gegenstand gerade mal ein fades Lächeln abgewinnen. er war so sinnlos wie ein Kropf. Inzwischen hat sich diese Pumpe in eine gefürchtete Waffe verwandelt, um nicht gereinigtes Wasser auf Zombies zu versprühen (das Ansatzstück passt auf einen Kanister).', // Kanisterpumpe (zerlegt)
        'bandage_#00' => 'Sie riecht schlecht und weist ein paar Schimmelspuren auf, aber jetzt mal ganz ehrlich: In dieser Lage kannst du nicht rumnörgeln. Mit der Bandage kannst du eine Wunde verbinden.', // Bandage
        'vodka_de_#00' => 'Das Glas dieser gut erhaltenen Bierflasche ist ziemlich verfärbt. Du vermutest, dass es früher mal grün war. Ah, da, das Etikett! Roter Rand..."Welcome to the Feck\'s experience"... okay, ein Pils. Na dann lass uns doch mal das "Experiment" wagen... .(Mit einem kräftigen Schluck kippst du dir den Saft hinter die Binde und lässt kurz darauf einen Riesenrülpser los!) - "Prost Kameraden!"', // Grüne Bierflasche
        'vodka_#00' => 'Nimm einen kräftigen Schluck aus der Pulle und deine Lebenskräfte werden wieder geweckt (Du erhältst alle deine AP zurück!). Da nimmt man die lose darin herum schwimmenden organischen Substanzen doch gerne in Kauf. Prost Kameraden!', // Vodka Marinostov
        'jerrygun_off_#00' => 'Früher konntest du einem solchen Gegenstand gerade mal ein fades Lächeln abgewinnen. er war so sinnlos wie ein Kropf. Inzwischen hat sich diese Pumpe in eine gefürchtete Waffe verwandelt, um nicht gereinigtes Wasser auf Zombies zu versprühen (das Ansatzstück passt auf einen Kanister).', // Kanisterpumpe (leer)
        'cinema_#00' => 'Dieser defekte Videoprojektor ist zu nichts mehr zu gebrauchen. Die Linse sieht allerdings noch halbwegs intakt aus....nur hast du nichts, um sie zu entfernen...', // Videoprojektor
        'explo_#00' => 'Booom! ', // Sprengstoff
        'hmeat_#00' => 'Denk am besten gar nicht daran, was du soeben geschluckt hast und mach einfach weiter...', // Menschenfleisch
        'grenade_empty_#00' => 'Eine alte Plastiktüte aus dem Supermarkt. Wenn du sie mit Wasser füllst, verwandelt sie sich in eine gefürchtete Waffe...', // Plastiktüte
        'bgrenade_#00' => 'Aus einer Plastiktüte, Wasser und etwas Sprengstoff bastelst du dir eine Massenvernichtungswaffe, die du nur noch in eine Zombiehorde werfen brauchst. Gibt ein nettes Massaker.', // Explodierende Wasserbombe
        'bgrenade_empty_#00' => 'Eine Ladung Sprengstoff, die an eine Plastiktüte befestigt wurde - simpel und effektiv. Jetzt brauchst du nur noch Wasser...', // Plastiktüte mit Sprengstoff
        'chainsaw_part_#00' => 'Das ist ein richtig lustiges Spielzeug. Allerdings brauchst du noch einen Riemen und noch ein paar andere Kleinigkeiten, um sie zum Laufen zu bringen...', // Unvollständige Kettensäge
        'mixergun_part_#00' => 'Das könnte eine gute Waffe ergeben, wenn du auch die fehlenden Teile auftreibst....', // Unvollständiger Handmixer
        'rustine_#00' => 'Immer nützlich, wenn man etwas kleben möchte. ', // Klebeband
        'lawn_part_#00' => 'DAS klassische Gerät für die Gartenarbeit. Es funktioniert ohne Strom und ist in seine Einzelteile zerlegt...Du musst ihn zusammenbauen.', // Zerlegter Rasenmäher
        'tube_#00' => 'Ein schmutziges Rohr aus Kupfer, für das dir noch keine Verwendung einfällt...', // Kupferrohr
        'cart_part_#00' => 'Eine Art Einkaufswagen aus Metall. Leider fehlt ihm eine Radachse. Du müsstest sie durch irgendetwas ersetzen...', // Wackliger Einkaufswagen
        'pocket_belt_#00' => 'Mit diesem Gürtel kannst du einen weiteren Platz in deinem Rucksack freimachen. Außerdem ist der Gürtel äußerst ergonomisch entworfen worden, denn er gestattet dir, später noch eine weitere Tasche tragen zu können.', // Gürtel mit Tasche
        'drug_hero_#00' => 'Eine Mischung aus konzentriertem Koffein, verschiedenen chemischen Drogen und zermahlenen Stierhoden. Die ideale Droge für den kleinen Energieschub am Abend, wenn du vor einer Zombiemeute flüchten musst, die es auf deine Leber abgesehen hat.', // Twinoid 500mg
        'chest_#00' => 'Eine komplett zerbeulte, alte rostige Kiste. Was könnte da wohl drin sein?', // Metallkiste
        'chest_xl_#00' => 'Der Koffer ist wirklich schwer und enthält bestimmt etwas Wertvolles....Jetzt musst du nur noch das passende Werkzeug finden, denn mit deinen Zähnen bekommst du das nicht auf.', // Großer Metallkoffer
        'chest_tools_#00' => 'Diese alte Holzkiste enthält sicher ein paar brauchbare Kleinigkeiten...', // Werkzeugkiste
        'lamp_on_#00' => 'Mit einer Nachttischlampe hast du nachts weniger Angst und kannst so besser schlafen.', // Nachttischlampe (an)
        'music_#00' => 'Gönn dir etwas Entspannung und höre dir ein bisschen Musik an. Hmmm... Ne Maxi-CD mit "Zombie Eaters" von Faith No More befindet sich noch im CD-Fach.', // Mini Hi-Fi Anlage (an)
        'pharma_#00' => 'Ein paar geheimnisvolle Substanzen und ein kleines Fläschchen. Alles ist in Plastik eingepackt. Kein Plan, wozu du das gebrauchen kannst... Vielleicht kann man einige der Substanzen mit anderen Produkten mischen?', // Pharmazeutische Substanz
        'plate_raw_#00' => 'Ein paar Blechplatten, die nur darauf warten, von dir daheim verbaut zu werden... Du musst sie nur noch korrekt zusammensetzen.', // Unverarbeitete Blechplatten
        'rhum_#00' => 'Ein Cocktail, der Tote weckt und der von Chuck erfunden wurde (wenn man dem Etikett glaubt...). Man nehme einen guten Schuss Rum, etwas roten Chili, Whisky und einen marinierten Finger, der in einer Flüssigkeit schwimmt...', // 'Wake The Dead'
        'coffee_#00' => 'Wie konnte diese Thermoskanne solange heiss bleiben?Das spielt jetzt keine Rolle,es ist jedenfalls starker Kaffee,SEHR starker Kaffe,der auf irgendeine Weise von jemandem gebrüht wurde.Nichts für kleine Mädchen!', // Heißer Kaffee
        'coffee_machine_#00' => 'Das ultimative Gerät für den Abenteuerer, der in dir schlummert. Sein einzigartiges Filtersystem stellt den besten Kaffee her, den man mit den hier erhältlichen Zutaten brühen kann. Frag besser nicht nach....', // Kaffeekocher
        'coffee_machine_part_#00' => 'Diese Kaffeemaschine könnte sehr nützlich sein, nur ist sie komplett zerlegt.', // Unvollständiger Kaffeekocher
        'electro_#00' => 'Eine alte elektronische Platine, die wahrscheinlich aus einem Radioempfänger oder aus einem sonstigen Gerät stammt.', // Elektronisches Bauteil
        'chest_citizen_#00' => 'Diese kleine Box enthält alles, was man so als neuer Bürger braucht! Sie ist wie eine Wundertüte oder eine Pralinenschachtel: Man weiß nie was man bekommt. Aber eigentlich ist sie\'n Witz...', // Habseligkeiten eines Bürgers
        'drug_water_#00' => 'Eine äußerst praktische Tablette, die du überall einstecken kannst. Kurz nach ihrer Einnahme verschwindet dein Durst. Trotzdem kann sie WEDER deine Erschöpfung lindern, NOCH gibt sie dir Aktionspunkte zurück.', // Hydraton 100mg
        'radio_off_#00' => 'Ein altes Transistorradio mit Kassettenfach. Es ist ein Blues-Klasette drin. Mit dem Radio wirst du nicht viel anfangen können, da du hier sowieso nichts empfängst, aber du kannst ein bisschen Musik hören. Das übertönt das Geschrei deiner Nachbarn... Jetzt brauchst du nur noch eine Batterie.', // Kassettenradio (ohne Strom)
        'radio_on_#00' => 'Ein altes Transistorradio mit Kassettenfach. Es ist ein Blues-Klasette drin. Mit dem Radio wirst du nicht viel anfangen können, da du hier sowieso nichts empfängst, aber du kannst ein bisschen Musik hören. Das übertönt das Geschrei deiner Nachbarn...', // Kassettenradio
        'cyanure_#00' => ' Wenn wirklich gar nichts mehr geht, schluckst du diese Kapsel mit einem großen Glas Wasser. Danach einfach warten und sich mit einem zufriedenen Grinsen von dieser Welt verabschieden.', // Zyanid
        'door_#00' => 'Eine alte Holztür. Das klassische Modell in weiß. Sie ist zwar total verdreckt, aber wer weiß, wozu du sie noch gebrauchen kannst...', // Alte Tür
        'vegetable_#00' => 'Sieht wie Gemüse aus, allerdings wunderst du dich, wie es so krumm wachsen konnte. Wichtig ist, dass du es essen kannst – oder besser nicht?', // Verdächtiges Gemüse
        'repair_kit_part_#00' => 'Dieses Reparaturset ist kaputt. Du müsstest es in der Werkstatt reparieren, bevor du es wieder benutzen kannst.', // Reparaturset (kaputt)
        'repair_kit_#00' => 'Diese Eisenkiste enthält alle wichtigen Werkzeuge und Materialien um so ziemlich alles zur reparieren... außer vielleicht deinen toten Nachbarn.', // Reparaturset
        'watergun_empty_#00' => 'Eine Wasserpistole ist gegen Zombies äußerst wirksam! Es sei denn sie ist leer.', // Wasserpistole (leer)
        'watergun_opt_3_#00' => 'Die Aqua-Splash-Kanone ist die Lieblingswaffe aller Wüstenwanderer! Nicht geeignet für Kinder unter 8 Jahren.', // Aqua-Splash (3 Ladungen)
        'watergun_opt_2_#00' => 'Die Aqua-Splash-Kanone ist die Lieblingswaffe aller Wüstenwanderer! Nicht geeignet für Kinder unter 8 Jahren.', // Aqua-Splash (2 Ladungen)
        'watergun_opt_1_#00' => 'Die Aqua-Splash-Kanone ist die Lieblingswaffe aller Wüstenwanderer! Nicht geeignet für Kinder unter 8 Jahren.', // Aqua-Splash (1 Ladung)
        'mixergun_empty_#00' => 'Rührt elektrisch und sehr schnell - was gibt\'s sonst noch zu sagen? Er hat den Vorteil, dass er nur eine Batterie braucht, die sich mit 40% Wahrscheinlichkeit entlädt.', // Handmixer (ohne Strom)
        'chainsaw_empty_#00' => 'Damit richtest du ein wahres Massaker an. Ob du das aber zu 100% unversehrt überstehst ist eine andere Frage... Diese umgebaute Höllenmaschine funktioniert - man glaubt es kaum - mit einer gewöhnlichen Batterie!', // Kettensäge (ohne Strom)
        'pilegun_empty_#00' => 'Der Batteriewerfer 1-PDTG wurde äußerst einfallsreich zusammengeschraubt. Dank einer virtuosen Recyclingprozedur ist er in der Lage, harmlose Batterien in mächtige Geschosse zu verwandeln.', // Batteriewerfer 1-PDTG (entladen)
        'taser_empty_#00' => 'Der Taser ist eine kleine technische Spielerei, mit der man grässliche Elektrostöße in dem Körper seines Opfers jagen kann. Allerdings ist er nicht sehr effektiv, wenn man bedennkt dass den Zombies Schmerzen nichts ausmachen. Vielleicht gelingt es dir ja mit einem Stromstoß ein wichtiges Körperglied oder Organ zum Platzen zu bringen?', // Taser (ohne Strom)
        'sport_elec_empty_#00' => 'Die militärische Ausführung eines \'body shapers\', der dazu dient, seinen Körper mittels schwacher Stromstöße zu stählen. Er regeneriert deine Erschöpfung und gibt dir deine Aktionspunkte wieder. Allerdings verursacht er auch schwere Verletzungen und du kannst unter Umständen sogar qualvoll sterben...', // Elektrischer Bauchmuskeltrainer (ohne Strom)
        'sport_elec_#00' => 'Die militärische Ausführung eines \'body shapers\', der dazu dient, seinen Körper mittels schwacher Stromstöße zu stählen. Er regeneriert deine Erschöpfung und gibt dir deine Aktionspunkte wieder. Allerdings verursacht er auch schwere Verletzungen und du kannst unter Umständen sogar qualvoll sterben...', // Elektrischer Bauchmuskeltrainer (geladen)
        'big_pgun_empty_#00' => 'Die militärische Ausführung des Batteriewerfers 1-PDTG. Sein übermächtiger Kolben ist in der Lage, eine Batterie derart schnell zu beschleunigen, dass sie fast jedes Material durchstößt. Das Gerät reißt faustgroße Löcher in bis zu zwei Zombies, vorausgesetzt sie stehen hintereinander.', // Zerstörer (entladen)
        'big_pgun_#00' => 'Die militärische Ausführung des Batteriewerfers 1-PDGT. Sein übermächtiger Kolben ist in der Lage ein Batterie derart schnell zu beschleunigen, dass sie fast jedes Material durchstößt. Das Gerät reißt faustgroße Löcher in bis zu zwei Zombies, vorausgesetzt sie stehen hintereinander.', // Zerstörer (geladen)
        'big_pgun_part_#00' => 'Die militärische Ausführung des Batteriewerfers 1-PDTG. Sein übermächtiger Kolben ist in der Lage, eine Batterie derart schnell zu beschleunigen, dass sie fast jedes Material durchstößt. Das Gerät reißt faustgroße Löcher in bis zu zwei Zombies, vorausgesetzt sie stehen hintereinander.', // Unvollständiger Zerstörer
        'tagger_#00' => 'Der Zonenmarker \'Radius\' zeigt dir auf der Karte alle Zonen an, die an deine aktuelle Position angrenzen.', // Zonenmarker 'Radius'
        'flare_#00' => 'Die Leuchtrakete ermöglicht dir Informationen über weit entfernte Zonen zu bekommen. Sobald die Rakete am Boden aufschlägt, wird ein kleiner Sender freigesetzt, der Informationen über die Umgebung sendet.', // Leuchtrakete
        'jerrygun_#00' => 'Früher konntest du einem solchen Gegenstand gerade mal ein fades Lächeln abgewinnen. er war so sinnlos wie ein Kropf. Inzwischen hat sich diese Pumpe in eine gefürchtete Waffe verwandelt, um nicht gereinigtes Wasser auf Zombies zu versprühen (das Ansatzstück passt auf einen Kanister).', // Kanisterpumpe (einsatzbereit)
        'chair_basic_#00' => 'Ein schlichter Stuhl mit lustigem Namen aus schwedischer Herstellung. Man kann damit Leute vermöbeln, aber besser du stellst ihn dir ins Wohnzimmer...', // Ektorp-Gluten Stuhl
        'gun_#00' => 'Eine Faustfeuerwaffe vom Modelltyp P-22. Sie ist für ihre Treffgenauigkeit und Zuverlässigkeit berühmt. Ohne Munition kannst du damit aber überhaupt nichts anfangen...', // Revolver (entladen)
        'machine_gun_#00' => 'Eine umgebaute Version des AK-47. Dieses Gewehr ist kompakter und wahrscheinlich für den "zivilen" Einsatz entworfen worden... Manche sagen man könne damit sehr gut \'Problembären\' kalt machen.', // Sturmgewehr (entladen)
        'deto_#00' => 'Ein kleiner, kompakter Zünder, mit dem man eine kleine Sprengladung explodieren lassen kann.', // Zünder
        'concrete_#00' => 'Ein großer Zementsack von mittelmäßiger Qualität... Wenn du den Zement in Wasser auflöst trocknet er ziemlich schnell und du erhälst einen besonders festen Beton, den du für allerhand Konstruktionen verwenden kannst!', // Zementsack
        'concrete_wall_#00' => 'Sie ähneln in keinster Weise Bauziegeln, aber es handelt sich um Stahlbeton, welchen du sicherlich für die Verbesserung der Verteidigung deines Hauses verwenden kannst... Schlimmstenfalls kannst du sie auch auf ein paar Zombies werfen, so wie bei einer Demo.', // Unförmige Zementblöcke
        'drug_random_#00' => 'Eine Medikamentenschachtel, dessen Etikett abgerissen wurde... Die darin eingepackten Pillen sind größtenteils verschimmelt. Allerdings findest du welche, die noch einigermaßen "genießbar" sind. Um das zu schlucken, muss es dir schon wirklich schlecht gehen...', // Etikettenloses Medikament
        'disinfect_#00' => 'Ein starkes Antibiotikum, das jede Infektion platt macht. Auf dem Beipackzettel steht: "Dieses Medikament kann manchmal unerwünschte und unberechenbare Nebenwirkugen hervorrufen (Akne, Erbrechen, Krämpfe und Herzstillstand)".', // Paracetoid 7g
        'digger_#00' => 'Das auf den Sack geklebte Etikett besagt, dass das Produkt genießbar sei wenn man es in Milch auflöst. Ein leichter Zweifel befällt dich...', // Unkrautbekämpfungsmittel Ness-Quick
        'chest_food_#00' => 'Ein unerträglicher Verwesungsgestank kitzelt deine Nase als du die Kiste anfasst. Du bist dir fast sicher, dass sie etwas Essbares enthält. Du hoffst es zumindest.', // Nahrungsmittelkiste
        'food_bag_#00' => 'Eine fettverschmierte alte Papiertüte, in der wohl irgendetwas zum Essen drin ist... Sie riecht aber schlecht.', // Doggybag
        'food_bar1_#00' => 'Die Kartoffelchips sind total weich und schmecken nach trockenem Papier. Du hast aber keine Wahl: Essen ist Essen.', // Tüte mit labbrigen Chips
        'food_bar2_#00' => 'Ein leckeres in Alupapier eingepacktes Gebäck. Als Geschmack wird "Vanille-Schoko" angegeben. Wenn du es schüttelst hört es sich an, als ob da auch eine Flüssigkeit drin wäre...', // Verschimmelte Waffeln
        'food_bar3_#00' => 'Eine handvoll staubtrockener Kaugummis. Normalerweise werden sie gekaut und nicht geschluckt, aber du solltest besser nicht rumnörgeln ... viel anderes gibt es nicht.', // Trockene Kaugummis
        'food_biscuit_#00' => 'Sie sehen trocken aus. Was würdest du nicht alles geben, um diese Kekse nicht schmecken zu müssen...', // Ranzige Butterkekse
        'food_chick_#00' => 'Zwei Hähnchenflügel, die schon jemand vor dir angebissen hat, bevor er sie weggeworfen hat... Hmmm...', // Angebissene Hähnchenflügel
        'food_pims_#00' => 'Eine Handvoll Kekse, die mit ... schwer zu sagen, was es ist... gefüllt sind.', // Abgelaufene Pim's Kekse
        'food_tarte_#00' => 'Ein streng riechendes Törtchen... Denk einfach an etwas Leckeres.', // Fades Gebäck
        'food_sandw_#00' => 'Der Rest eines Schinkenbrötchens. Die grünlich-weiß verschimmelten Schinkenecken lassen sich kurzerhand unter dem Brot verstecken und dann nichts wie runter damit...', // Verschimmelte Stulle
        'food_noodles_#00' => 'Trotz all der Jahre, die sie wahrscheinlich schon in dieser Wüste liegen, haben diese Nudeln ihr Aussehen und ihren Geschmack behalten. Erstaunlich.', // Chinesische Nudeln
        'spices_#00' => 'Ein Säckchen mit starken Gewürzen ...', // Starke Gewürze
        'food_noodles_hot_#00' => 'Richtig gut gewürzte chinesische Nudeln! Das ist mal\'ne Abwechslung von dem sonstigen verschimmelten Fraß.', // Gewürzte chinesische Nudeln
        'cards_#00' => 'Ein Kartenset mit 54 abgegriffenen Karten, die ein paar Eselsohren haben... Du solltest gleich mal eine Runde zocken, denn oft wirst du hier nicht zum Spielen kommen.', // Unvollständiges Kartenspiel
        'game_box_#00' => 'Ein altes Spiel welches du noch nicht kennst. Wow!', // Gesellschaftsspiel
        'watergun_opt_part_#00' => 'Eine furchterregende Waffe, der nur ein paar Teile fehlen...', // Aqua-Splash (zerlegt)
        'vibr_empty_#00' => 'Er vibriert und kitzelt dich am ganzen Körper und lässt dich für einen Augenblick all deine Sorgen vergessen (benötigt 1 Batterie).', // Vibrator (entladen)
        'bone_meat_#00' => 'Ein Knochen, an dem noch Fleisch ist... Komisch, er riecht sogar noch frisch. Vielleicht stammt er von einem Tier, das letzte Nacht getötet wurde?', // Knochen mit Fleisch
        'bone_#00' => 'Ein trockener Knochen (wahrscheinlich ein Schienbein ), den jemand vor Kurzem abgenagt hat... Den kannst du bestimmt für etwas gebrauchen.', // Angeknackster menschlicher Knochen
        'wood_beam_#00' => 'Notdürftig zusammengebundene Holzstücke, die einen relativ stabilen Stützbalken abgeben.', // Zusammengeschusterter Holzbalken
        'metal_beam_#00' => 'Eine schnell zusammengebaute Metallstruktur, die ein gutes stützendes Bauteil abgibt.', // Metallstruktur
        'metal_bad_#00' => 'Ein paar verrostete Metallstangen, ein Gitter, ein paar Schrauben... Nichts, was du sofort benutzen könntest. Du musst es erst in der Stadt ein wenig bearbeiten.', // Metalltrümmer
        'wood_bad_#00' => 'Ein großer Baumstumpf, der von schleimigen Pilzen bedeckt ist. Damit kannst du nichts anfangen, es sei denn, du schneidest ihn dir in der Stadt zurecht.', // Verrotteter Baumstumpf
        'saw_tool_#00' => 'Eine Säge, dessen Zacken größtenteils verbogen sind. Du kannst mit ihr aber noch verschiedene Sachen zerteilen (Die Säge verringert die Verarbeitungskosten in der Werkstatt um 1 AP. Dafür musst du sie in deinem Rucksack haben.', // Metallsäge
        'wood_log_#00' => 'Ein schöner Holzscheit, den du als Hocker für deine Einrichtung verwenden kannst. Was willst du mehr? Ist doch besser als nichts.', // Gut erhaltener Holzscheit
        'electro_box_#00' => 'Ein uraltes Haushaltsgerät, von dem du aber nicht weißt, für welchen Zweck es bestimmt war. Vielleicht erhälst du ein paar nützliche Sachen wenn du es auseinanderbaust?', // Defektes Elektrogerät
        'deco_box_#00' => 'Ein Möbelstück, das in seine Einzelteile zerlegt ist. Schwer zu sagen, was es ist...', // Möbelpackung
        'saw_tool_part_#00' => 'Diese Säge könnte nützlich sein, wenn du es schaffst, sie zu reparieren.', // Beschädigte Metallsäge
        'mecanism_#00' => 'Dieses Getriebe war wohl mal Teil einer größeren Maschine oder eines Fahrzeugs... In dieser Form kannst du aber nichts mehr damit anfangen.', // Getriebe
        'trestle_#00' => 'Ein relativ solider Holzbock, den du zweifelsohne für deine persönliche Verteidigung oder für die Stadtverteidigung gebrauchen kannst.', // Holzbock
        'table_#00' => 'Der Tisch lässt sich ganz leicht mit Gummizügen und Holzstiften zusammenbauen. In deinem Wohnzimmer wird er ein richtiger Hingucker sein. Und wenn du ihn senkrecht stellst, kann er dir eines Nachts vielleicht sogar das Leben retten.', // Järpen-Tisch
        'water_cleaner_#00' => 'Eine desinfizierende Brausetablette, mit der man Wasser reinigen kann. Eigentlich handelt es sich um ein Reinigungstab für Toiletten. Den Geschmack kannst du dir ja ausmalen... (ACHTUNG: Die Ausbeute dieses Produkts ist im Vergleich zum industriellen Reiniger wesentlich geringer.)', // Micropur Brausetablette
        'vegetable_tasty_#00' => 'Normalerweise "wächst" die Darmmelone im Grimmdarm von Kadavern, doch es sieht so aus, als ob dein Gemüsebeet dir ein paar hübsche Exemplare beschert hätte. Kann es vielleicht sein, dass dein Gemüsebeet auf einem alten Indianerfriedhof liegt?', // Darmmelone
        'powder_#00' => 'Dieses Feuerwerkraketenpulver könnte als Ablenkung dienen, wenn du es korrekt verwendest ...', // Raketenpulver
        'flash_#00' => 'Diese improvisierte Granate ermöglicht dir, die Zombies in einer Zone abzulenken. Nutze die sich bietende Gelegenheit um dich zu verdrücken.', // Schießpulverbombe
        'teddy_#00' => 'Ein kleines Stoffkuscheltier, das einem Kind vor langer Zeit viel Freude bereitet hat...', // Teddybär
        'wood_plate_part_#00' => 'Eine lose Ansammlung von Brettern, die früher wahrscheinlich einen Holzdeckel formten. Du könntest ihn gut für die Verteidigung gebrauchen, allerdings brauchst du etwas, um die Bretter zu fixieren.', // Holzkistendeckel
        'wood_plate_#00' => 'Dieser große Kistendeckel könnte dir gute Dienste leisten, um ein Fenster oder eine Tür bei dir daheim zu verriegeln...', // Solide Holzplatte
        'money_#00' => 'Ein bedrucktes Bündel Papierscheine und ein paar Kupferstücke. Beides diente früher mal als Währung, doch hier ist es wertlos. Alles, was du nicht essen oder dir ins Blut spritzen kannst ist uninteressant...', // Geldbündel
        'repair_kit_part_raw_#00' => 'Eine Tasche mit verschiedenen Werkzeugen, denen hier und da ein Griff oder eine Schraube fehlt. Du müsstest es reparieren, wenn du es ernsthaft gebrauchen willst.', // Loses Werkzeug
        'radius_mk2_part_#00' => 'Der Radar-Marker \'Radius Mark II\' ist ein fantasievoll zusammengeschraubtes Gerät, das dir ermöglicht, Lebewesen in deiner Umgebung ausfindig zu machen. Es wird behauptet, dass er Krebs verursache und Gehirnzellen irreparabel schädige, doch das kann dir egal sein, da du eh nicht lang genug leben wirst... und überhaupt: Um ihn zu benutzen brauchst du erstmal eine Batterie.', // Radius Mark II (entladen)
        'radius_mk2_#00' => 'Der Radar-Marker \'Radius Mark II\' ist ein fantasievoll zusammengeschraubtes Gerät, das dir ermöglicht, Lebewesen in deiner Umgebung ausfindig zu machen. Es wird behauptet, dass er Krebs verursache und Gehirnzellen irreparabel schädige, doch das kann dir egal sein, da du eh nicht lang genug leben wirst... und überhaupt: Um ihn zu benutzen brauchst du erstmal eine Batterie.', // Radius Mark II
        'repair_one_#00' => '\'Reparatur Fix\' ist ein kleines Reparaturkit für den einmaligen Gebrauch, mit dem du fast alles richten kannst. Es enthält das übliche Grundwerkzeug, ein paar Plastikersatzteile und eine Do-it-yourself-Anleitung in sieben Sprachen.', // Reparatur Fix
        'engine_part_#00' => 'Es handelt sich um einen kleinen Motor, der mit Ölspuren und undefinierbaren Brennresten verschmutzt ist und der schwer wie Blei ist.', // Unvollständiger Motor
        'machine_1_#00' => 'Diese uralte Waschmaschine ist das Relikt einer Ära, in der die Menschen noch Wert auf Sauberkeit legten. Heute kannst du nur noch darüber lachen...', // Alte Waschmaschine
        'machine_2_#00' => 'Legenden erzählen, dass die Mikrowellentechnologie einer der Gründe für den Untergang der menschlichen Zivilisation waren. Manche behaupten sogar, dass Mikrowellenöfen für das Auftauchen der Untoten verantwortlich seien...', // Krebserregender Ofen
        'machine_3_#00' => 'Leider enthält dieser kleine Kühlschrank keinen Alkohol mehr. Zudem funktioniert er nicht mehr. Allerdings könnte er ein gutes Wurfgeschoss abgeben.', // Minibar
        'rp_letter_#00' => 'Ein blutbefleckter Briefumschlag, auf dem die Empfängeradresse nicht mehr lesbar ist... Der Umschlag ist noch zu.', // Ein Brief ohne Adresse
        'rp_scroll_#00' => 'Ein aufgewelltes, sprödes Blatt Papier. Du hast nicht den blassesten Schimmer, was es sein könnte. Versuche es vorsichtig zu reinigen, vielleicht kannst du ja was eintziffern.', // Aufgewelltes Blatt
        'rp_manual_#00' => 'Eine Gebrauchsanweisung mit komplizierten Diagrammen, die detailliert beschreiben, wie man eine obskure Maschine zusammensetzt. Es scheint schlecht aus einer asiatischen Sprache übersetzt zu sein, aber auf der Rückseite ist eine interessantere Handschrift von jemandem zu finden.', // Betriebsanleitung
        'rp_book2_#00' => 'Wer weiß, was in diesem Notizbuch stehen könnte? Die dunkelsten Geheimnisse von jemandem? Eine Schatzkarte? Wenn du es nur lesen könntest... ', // Unleserliches Notizbuch
        'rp_book_#01' => 'Sieht wie ein alter Schuljahresbericht aus. Die Fotos sind stinklangweilig. Beim Durchblättern fällt dir ein dazwischengeschobenes Blatt in die Hände...', // Fotoalbum
        'rp_sheets_#00' => 'Ein Haufen Papierkram, das aus einer Verwaltung stammt.Nichts Interessantes, wobei manche Blätter Handnotizen erkennen lassen...', // Blätterstapel
        'chain_#00' => 'Diese alte Eisenkette ist total krumm und ist nicht mehr von großem Nutzen... Aber vielleicht kannst du jemanden damit erwürgen?', // Große rostige Kette
        'dish_#00' => 'Eine nicht ganz durchgegarte wilde Mischung verschiedenster Zutaten, fein abgeschmeckt mit Wüstensand. Hhhmmm...', // Verdächtige Speise
        'dish_tasty_#00' => 'Auf den ersten Blick sieht es kaum besser aus als das, was du sonst zu dir nimmst. In Wirklichkeit schmeckt es jedoch sehr sehr gut und ist sättigend!', // Leckere Speise
        'home_box_xl_#00' => 'Dieser Schrankkoffer ist zwar schwer, aber äußerst praktisch, um daheim mal aufzuräumen.', // Schrankkoffer
        'home_box_#00' => 'Ein paar Haushaltsgerätekartons, die stark nach Schimmel riechen. Du kannst sie aber immer noch verwenden - als Möbel zum Beispiel.', // Kartons
        'home_def_#00' => 'Dieser lustige Blech- und Holzverbund hat wohl einem anderen Einwohner als Schutz gedient. Den Beulen und Blutflecken nach zu urteilen, hat sie wohl nicht ganz gereicht...', // Nagelbare Barrikade
        'book_gen_letter_#00' => 'Ein großer, versiegelter Briefumschlag ... Dem Tasten nach zu urteilen enthält er Papier oder doch etwas anderes?', // Ein Briefumschlag
        'book_gen_box_#00' => 'Die Adresse ist nicht mehr lesbar. Beim Schütteln hörst du ein raschelndes Geräusch...', // Ein Paket
        'fence_#00' => 'Ein kleines, verrostetes Stück Maschendrahtzaun. ', // Maschendrahtzaunstück
        'watergun_3_#00' => 'Die Wasserpistole erinnert dich irgendwie an Räuber und Gendarmen... Nur mit sauberem Wasser benutzen(zum Beispiel aus einer Trinkflasche).', // Wasserpistole (3 Ladungen)
        'watergun_2_#00' => 'Die Wasserpistole erinnert dich irgendwie an Räuber und Gendarmen... Nur mit sauberem Wasser benutzen(zum Beispiel aus einer Trinkflasche).', // Wasserpistole (2 Ladungen)
        'watergun_1_#00' => 'Die Wasserpistole erinnert dich irgendwie an Räuber und Gendarmen... Nur mit sauberem Wasser benutzen(zum Beispiel aus einer Trinkflasche).', // Wasserpistole (1 Ladung)
        'watergun_opt_5_#00' => 'Die Aqua-Splash-Kanone ist die Lieblingswaffe aller Wüstenwanderer! Nicht geeignet für Kinder unter 8 Jahren.', // Aqua-Splash (5 Ladungen)
        'watergun_opt_4_#00' => 'Die Aqua-Splash-Kanone ist die Lieblingswaffe aller Wüstenwanderer! Nicht geeignet für Kinder unter 8 Jahren.', // Aqua-Splash (4 Ladungen)
        'cigs_#00' => 'Eine gammelige alte Zigarettenschachtel, die nach Aas riecht. Du kannst dich richtig glücklich schätzen, sowas findet man heutzutage nur noch sehr selten. Auf der Packung ist ein Warnhinweis: "Rauchen ist tödlich".', // Angefangene Zigarettenschachtel
        'pilegun_upkit_#00' => 'Mit diesem veralteten Druckregler kannst du den Druck in der Schusskammer des Batteriewerfer 1-PDTG regeln.', // Druckregler PDTT Mark II
        'pilegun_up_empty_#00' => 'Bei dieser verbesserten Version des normalen Batteriewerfers 1-PDTG ist der PDTT Mark II-Druckregler schon eingebaut. Durch Feinjustierung der Schusskraft ist es manchmal sogar möglich die verschossene Batterie wieder einzusammeln.', // Batteriewerfer Mark II (leer)
        'pilegun_up_#00' => 'Bei dieser verbesserten Version des normalen Batteriewerfers 1-PDTG ist der PDTT Mark II-Druckregler schon eingebaut. Durch Feinjustierung der Schusskraft ist es manchmal sogar möglich die verschossene Batterie wieder einzusammeln.', // Batteriewerfer Mark II (geladen)
        'pile_broken_#00' => 'Das war mal eine Batterie. Jetzt ist es nur noch ein komplett zerdrücktes Stück Metall...', // Zerquetschte Batterie
        'rsc_pack_3_#00' => 'Diese große Kiste ist in Zellophan eingeschweißt und mit bedruckten Etiketten nur so zugepflastert. Sie enthält wahrscheinlich Baumaterialien.', // Kiste mit Materialien (3)
        'rsc_pack_2_#00' => 'Diese große Kiste ist in Zellophan eingeschweißt und mit bedruckten Etiketten nur so zugepflastert. Sie enthält wahrscheinlich Baumaterialien.', // Kiste mit Materialien (2)
        'rsc_pack_1_#00' => 'Diese große Kiste ist in Zellophan eingeschweißt und mit bedruckten Etiketten nur so zugepflastert. Sie enthält wahrscheinlich Baumaterialien.', // Kiste mit Materialien (1)
        'car_door_#00' => 'Diese Autotür kann dir als Schutzschild dienen, wenn du eine Reihe hungriger Kadaver durchqueren musst. Die haben nämlich nur eines im Sinn: Dein köstliches Gehirn zu verschlingen.', // Autotür
        'car_door_part_#00' => 'Eine Autotür, bei der ein entscheidendes Teil fehlt: Der Türgriff. Schlecht...', // Beschädigte Autotür
        'poison_#00' => 'Diese winzig kleine Dosis Gift reicht aus, um jeden deiner Mitbürger umzubringen. Wenn du es mit etwas Essen oder einer Droge vermischt, oder wenn du es in eine Trinkflasche kippst, ist dieses Gift eine tödliche Waffe.', // Giftfläschchen
        'poison_part_#00' => 'Mit den richtigen Zutaten kannst du dir ein schönes Gift mischen. Musst du denn unbedingt jemanden umbringen? Das willst du doch nicht wirklich... oder doch?', // Ätzmittel
        'chest_hero_#00' => 'Sobald du in dieser Welt ein wenig Erfahrung gesammelt hast, weißt du, dass es wichtig ist, ein paar Gegenstände bei sich zu behalten. Denn eines Tages könnten sie dir das Leben retten.', // Vorräte eines umsichtigen Bürgers
        'postal_box_#00' => 'Die Adresse ist nicht mehr lesbar. Es scheint etwas drin zu sein... Das ist ja besser als Weihnachten!', // Postpaket
        'postal_box_#01' => 'Die Adresse ist nicht mehr lesbar. Es scheint etwas drin zu sein... Frohe Weihnachten!', // Postpaket (xmas variant)
        'postal_box_xl_#00' => 'Die Adresse ist nicht mehr lesbar. Es scheint etwas drin zu sein... Frohe Weihnachten!', // Großes Postpaket (xmas)
        'food_armag_#00' => 'Da Du hier kein richtiges Dinner mit Freunden erleben kannst, hast du dir ein paar leckere Happen beiseite gelegt und in dieser Lunch-Box aufbewahrt. Ab und zu muss man sich mal was gönnen ...', // Lunch-Box
        'food_candies_#00' => 'Beim Anblick dieser Bonbons kommen dir die Tränen...', // Eine Handvoll Bonbons
        'out_def_#00' => 'Dieses alte Sperrholzstück stammt aus irgendeinem alten Gebäude. Mit ein wenig Einfallsreichtum könntest du es für eine neue Konstruktion wiederverwerten.', // Sperrholzstück
        'torch_#00' => 'Die brennt bestimmt noch ein paar Stunden... Selbst damit kannst du dir die Zombies vom Leib halten.', // Fackel
        'torch_off_#00' => 'Mit diesem alten verbranntem Stück Holz kannst du wahrscheinlich nicht mehr viel anfangen...', // Verbrauchte Fackel
        'chama_#00' => 'Eine Handvoll steinharte Marshmallows, die nach Rattengift riechen. So wie sie jetzt sind, kannst du sie nicht essen...', // Getrocknete Marshmallows
        'chama_tasty_#00' => 'Eine Handvoll steinharter Marshmallows, die nach Rattengift riechen. Jetzt, da sie komplett geröstet sind, kannst du sie essen, und wenn du es dir genau überlegst, sind sie sogar richtig lecker...', // Geröstete Marshmallows
        'pc_#00' => 'Diese alte Blechkiste war mal mit verschiedenen elektronischen Bauteilen gefüllt (CPU, Hauptplatine, Hardwareschnittstellen etc ...). Was willst du jetzt noch damit anfangen?', // PC-Gehäuse
        'safe_#00' => 'Toll... und wie findest du jetzt raus, was da drin ist? Ohne die Geheimkombination dürfte das etwas schwierig werden.', // Safe
        'rp_twin_#00' => 'Dieser große staubige Schinken gehört zu einer Lexikonausgabe mit dem Titel "Twinpedia". Die Seiten sind mit Anmerkungen unterschiedlicher Handschriften vollgeschrieben.Es scheint als ob mehrere Personen daran und darin gearbeitet hätten.', // Eine Enzyklopädie
        'water_can_empty_#00' => 'Ein typischer Wasserspender, wie man ihn in jedem Großraumbüro antreffen kann. Wie durch ein Wunder ist er dir in die Hände gefallen. So ein Ding könnte hier gut nützlich sein...', // Wasserspender (leer)
        'water_can_1_#00' => 'Ein typischer Wasserspender, wie man ihn in jedem Großraumbüro antreffen kann. Wie durch ein Wunder ist er dir in die Hände gefallen. So ein Ding könnte hier gut nützlich sein...', // Wasserspender (1 Ration)
        'water_can_2_#00' => 'Ein typischer Wasserspender, wie man ihn in jedem Großraumbüro antreffen kann. Wie durch ein Wunder ist er dir in die Hände gefallen. So ein Ding könnte hier gut nützlich sein...', // Wasserspender (2 Rationen)
        'water_can_3_#00' => 'Ein typischer Wasserspender, wie man ihn in jedem Großraumbüro antreffen kann. Wie durch ein Wunder ist er dir in die Hände gefallen. So ein Ding könnte hier gut nützlich sein...', // Wasserspender (3 Rationen)
        'beta_drug_bad_#00' => 'Die Wirkung dieser Tablette ist höchst zweifelhaft... Auf der Schachtel steht: "Für BETA-Tester unter 18 Jahren nicht geeignet". Seltsam...', // Abgelaufene Betapropin-Tablette 5mg
        'beta_drug_#00' => 'Auf der Schachtel steht: "Für BETA-Tester unter 18 Jahren nicht geeignet". Seltsam...', // Betapropin-Tablette 5mg
        'fruit_sub_part_#00' => 'Aasbeeren sind Beeren, die auf verwesten Leichen wachsen. Man findet sie oft auf Zombies, aber erst recht auf Tierkadavern...Willst du sie wirklich essen?', // Aasbeeren
        'fruit_part_#00' => 'Eine glitschige Kugel aus zusammengeklebten "Aasbeeren" "Aasbeeren" wachsen hauptsächtlich auf Kadavern.', // Schleimige Kugel
        'flesh_part_#00' => 'Ein altes Hautstück oder irgend etwas anderes, das mal zu einem Lebewesen gehörte. Was willst du damit anstellen?', // Fleischfetzen
        'flesh_#00' => 'Das tolle an dieser unförmigen Fleischkugel ist, dass sie auf 10 Meter nach Aas stinkt. Damit kannst du die Zombies \'ne Zeit lang beschäftigen.', // Makabre Bombe
        'pharma_part_#00' => 'Dieses kleine Fläschen enthält eine fluoresziernde Flüssigkeit. Erstaunlich! Vielleicht kannst du ja was Nützliches brauen, wenn du die Substanz mit etwas anderem mischst?', // Dickflüssige Substanz
        'fruit_#00' => 'Dieser Brei stinkt wie die Pest, da die dafür verarbeiteten Beeren für gewöhnlich auf Kadavern wachsen. Wenn du vor einer akuten Infektion keine Angst hast könntest du ihn ja mal vorsichtig probieren. Vielleicht macht er ja satt?', // Aasbeerenbrei
        'water_cup_part_#00' => 'Dieser total verformte Eisenbehälter hat eine kleine Menge Wasser aufgefangen.Das Problem ist, das du es nicht trinken kannst, da es schmutzig ist. ', // Eisengefäß mit modrigem Wasser
        'water_cup_#00' => 'Dieser total verformte Eisenbehälter enthält eine kleine Menge gereinigtes Wasser. Es ist nicht viel, aber besser als nichts.)', // Gereinigtes modriges Wasser
        'banned_note_#00' => 'Dieser Notizzettel gehörte einem *Verbannten*. Mal sehen, was er geschrieben hat...', // Notizzettel eines Verbannten
        'infect_poison_part_#00' => 'Dieser blutdurchtränkte Verband wurde vormals von einem infizierten Bürger getragen...', // Blutdurchtränkter Verband
        'teddy_#01' => 'Das alleinige Ansehen des Teddys macht dich verrückt.Vielleicht solltest du ihn aus dem Fenster werfen? Immerhin befindest du dich in einem verlassenen Hotel...', // Verfluchter Teddybär
        'woodsteak_#00' => 'Man nehme eine Scheibe Fleisch, paniere das Ganze mit Sägemehl und streue anschließend einige Holzstücke darüber... et Voilà! Im Holz scheinen auch einige Holzwürmer gewesen zu sein, denn das Ding hört nicht auf sich zu bewegen.', // Sägemehlsteak
        'christmas_suit_1_#00' => 'Ein wenig zu gross für dich. Sieht so aus als waren seine Vorbesitzer ein wenig beleibter und hatten schlechten Geschmack...was man ohne Zweifel erkennen kann.', // Abgetragene rote Jacke
        'christmas_suit_2_#00' => 'Diese alten Fetzen riechen nach Urin und wurden mehrmals geflickt. Besser aussehen würden sie....ohh, sie passt Dir perfekt.', // Zerrissene rote Hose
        'christmas_suit_3_#00' => 'Viele seltsame Gestalten haben diese stinkende Mütze schon getragen, und trotzdem geht dein Herz auf, als du sie aufsetzt.', // Schweißtriefende rote Mütze
        'christmas_suit_full_#00' => 'Als du diesen Anzug anziehst, verspürst du den Drang, deinen Nachbarn eine Freude zu machen und seltsame Eintrittswege zu wählen. Nutze die Chance und bestehle deine Nachbarn nach Herzenslust.', // Übelriechender Anzug aus einer anderen Zeit
        'iphone_#00' => 'Dieses uralte Modell, war mal ein ziemlich angesagtes Handy. Eines Tages jedoch fingen die Dinger urplötzlich zu explodieren an... Da das Handy weit verbreitet war, sind Millionen Menschen bei diesen Explosionen gestorben. Ganze Städte wurden von der Landkarte ausgelöscht. Hhmmm... Das gibt bestimmt eine gute Granate ab.', // Mobiltelefon
        'smelly_meat_#00' => 'Dieser übelriechende Hautfetzen gehörte früher mal einem deiner Mitbürger. Jetzt gehört er den Maden... Mit seinem unerträglichen Gestank kannst du deinen eigenen Körpergeruch überdecken, so dass dich die Untoten nicht mehr so gut riechen können. Benutze diesen Gegenstand, um deine Überlebenschancen beim Campen zu verbessern (Dazu musst du den Hautfetzen bei Dir tragen).', // Ekliger Hautfetzen
        'maglite_off_#00' => 'Diese große Taschenlampe ist wirklich nützlich für die Suche in der Wüste, vor allem Nachts. Möglicherweise kan man noch eine andere Verwendung für sie finden, wenn sie angehen würde....', // MagLite Kinderlampe (aus)
        'maglite_1_#00' => 'Diese große Taschenlampe ist wirklich nützlich für die Suche in der Wüste, vor allem Nachts. Möglicherweise kann man noch eine andere Verwendung für sie finden.', // MagLite Kinderlampe (1 Ladung)
        'maglite_2_#00' => 'Diese große Taschenlampe ist wirklich nützlich für die Suche in der Wüste, vor allem Nachts. Möglicherweise kann man noch eine andere Verwendung für sie finden.', // MagLite Kinderlampe (2 Ladungen)
        'cadaver_#00' => 'Diese Leiche liegt hier schon ein bisschen länger rum: Fliegenmaden haben sich schon an ihm zu schaffen gemacht und den größten Teil gegessen... Bist du sicher, dass du DIESES DING schultern möchtest?', // Leiche eines Reisenden
        'cadaver_remains_#00' => 'Loses herumliegendes Gebein. Sieht aus, als ob es von einem \'wilden Tier\' angenagt worden wäre... Die Bissspuren sehen aber menschlich aus. Irgendetwas stimmt hier nicht...', // Angenagte Leiche
        'smoke_bomb_#00' => 'Es handelt sich um eine bekannte Rauchgranatenmarke, die herzlich frisch nach Tannenzapfen riecht. BEACHTE: Dieser Gegenstand löscht drei Minuten lange alle Registereinträge. Die letzte Bewegungsaktion wird gelöscht, wenn sie spätestens eine Minute nach Benutzung der Rauchgranate erfolgt. ACHTUNG: Die Rauchgranate bitte erst NACH deiner Verheimlichungsaktion werfen.', // Rauchgranate 'Tannenduft'
        'sand_ball_#00' => 'Du hältst eine schlichte Sandkugel in der Hand, die du vorher mit ein paar fiesen Kieselsteinchen angereicherst hast. HeHe! Irgendwie verspürst du gerade eine unglaubliche Lust diesen Ball jemanden ins Gesicht zu werfen. Wenn schon kein Schnee, dann wenigstens \'ne Sandballschlacht!', // Sandball
        'bplan_c_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Normaler Bauplan (gewöhnlich)
        'bplan_u_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Normaler Bauplan (ungewöhnlich)
        'bplan_r_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Normaler Bauplan (selten)
        'bplan_e_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Normaler Bauplan (sehr selten!)
        'bplan_box_#00' => 'Dieser Koffer enthält mehrere Dokumente und obskure Gegenstände (Bleistift, Kompass, Plastiklineale,...). Nichts was dir irgendwie helfen würde, in der Wüste zu überleben. ...zumindest auf den ersten Blick.', // Architektenkoffer
        'bplan_box_e_#00' => 'Dieser Koffer enthält höchstwahrscheinlich ein sehr seltenes und kostbares Dokument...', // Versiegelter Architektenkoffer
        'egg_#00' => 'Hier in dieser Einöde ein Ei zu finden, sollte dich eigentlich glücklich machen. Aber eine Frage lässt dir einfach keine Ruhe: Wo zum Teufel steckt das verdammte Huhn...', // Ei
        'apple_#00' => 'Als einer davon auf Sir Newton\'s Kopf fiel dachte er mit Sicherheit nicht zuerst an das erste universelle Gesetz der Schwerkraft... aber es sollten immerhin ein paar Vitamine enthalten sein.', // Apfel
        'boomfruit_#00' => 'Sie ist sehr groß, fruchtig und macht "tick-tack".', // Explosive Pampelmuse
        'bplan_drop_#00' => 'Diese kleine Ledertasche scheint Unterlagen zu enthalten. Du betest inständig, dass es sich dabei um ein Männermagazin handelt, oder den Bauplan für ein neues, mächiges Gebäude. ...und nicht um die verblichenen Kopien einer Umsatzschätzung der Buchhaltung.', // Abgenutzte Kuriertasche
        'magneticKey_#00' => 'Mit diesem HighTech-Schlüssel lassen sich Türen der Sicherheitsklasse 6.2 AOC öffnen. Nur Angehörige der Elite können sich damit brüsten, so einen Schlüssel zu besitzen. Leider ist der Schlüssel in einem so schlechten Zustand, dass er nur eine einzige Tür öffnet.', // Magnet-Schlüssel
        'bumpKey_#00' => 'Dieser Schlüssel ist der Liebling aller Einbrecher und öffnet Türen so rasend schnell, dass man sich glatt fragt, warum man überhaupt noch normale Schlüssel benutzt. Leider ist der Schlüssel in einem so schlechten Zustand, dass er nur eine einzige Tür öffnet.', // Schlagschlüssel
        'classicKey_#00' => 'Einige der Zimmer sind wahre Flaschenhälse. Darum ist das das perfekte Werkzeug, um sich Zutritt zu verschaffen und den einen oder anderen nützlichen Gegenstand herauszuholen! Leider ist der Flaschenöffner in einem so schlechten Zustand, dass er nur eine einzige Tür öffnet.', // Flaschenöffner
        'prints_#00' => 'Mit diesem Abdruck des HighTech-Schlüssels lassen sich Türen der Sicherheitsklasse 6.2 AOC öffnen.', // Abdruck vom Magnet-Schlüssel
        'prints_#01' => 'Einige der Zimmer sind wahre Flaschenhälse. Darum ist das das perfekte Werkzeug, um sich Zutritt zu verschaffen und den einen oder anderen nützlichen Gegenstand herauszuholen!', // Abdruck vom Schlagschlüssel
        'prints_#02' => 'Einige der Zimmer sind wahre Flaschenhälse. Darum ist das das perfekte Werkzeug, um sich Zutritt zu verschaffen und den einen oder anderen nützlichen Gegenstand herauszuholen!', // Abdruck vom Flaschenöffner
        'vagoul_#00' => 'Dieses extrem seltene Serum wurde durch der Untersuchung der DNA eines fast immunen Wesens gewonnen. Es hatte zuvor 3 Tage überlebt, obwohl es von einem Infizierten gebissen wurde. Mit dem Serum kann man einen Ghul wieder in einen normalen Menschen verwandeln.', // Ghul-Serum
        'hbplan_u_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hotel-Bauplan (ungewöhnlich)
        'hbplan_r_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hotel-Bauplan (selten)
        'hbplan_e_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hotel-Bauplan (sehr selten!)
        'bbplan_u_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Bunker-Bauplan (ungewöhnlich)
        'bbplan_r_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Bunker-Bauplan (selten)
        'bbplan_e_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Bunker-Bauplan (sehr selten!)
        'mbplan_u_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hospital-Bauplan (ungewöhnlich)
        'mbplan_r_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hospital-Bauplan (selten)
        'mbplan_e_#00' => 'Dieser Bauplan ermöglicht es, ein neues Gebäude in der Stadt zu bauen.', // Hospital-Bauplan (sehr selten!)
        'soul_blue_#00' => 'Eine Schwache Seele. Einmal in der Bank abgelegt, kann ein Schamane sie nehmen und in eine starke Seele umwandeln. Sie kann einen Schamanen noch in vielen anderen Situationen nützen.', // Verirrte Seele
        'soul_yellow_#00' => 'Eine Starke Seele. Wurde von einem Schamanen aus einer Schwachen Seele hergestellt.', // Starke Seele
        'soul_red_#00' => 'Die Seele dieses Bürgers wurde schon zu lange ohne Rücksicht auf Rücksicht verlassen. Heute kommt dieses von Hass erfüllte Wesen, um die schlechten Vibes der Überseewelt zu nähren, ihr solltet euch schnell darum kümmern!', // Gefolterte Seele
        'soul_blue_#01' => 'Eine Schwache Seele. Einmal in der Bank abgelegt, kann ein Schamane sie nehmen und in eine starke Seele umwandeln. Sie kann einen Schamanen noch in vielen anderen Situationen nützen.', // Schwache Seele
        'fest_#00' => 'In Wehmut versunken starrst du diesen überdimensionalen Bierkrug an... Sicher warm und geschmacklos, aber das Beste, das du seit der Apokalypse getrunken hast. Du musst wieder an all die verrückten Tage in München denken...', // Bierkrug
        'bretz_#00' => 'Ein leckerer, essbarer Snack... zumindest war es das einmal. Auf dieser Brezel wurde herumgetreten, sie riecht, als würde sie in Benzin getränkt (leider zu wenig, um sie für den Panzer im Südwesten der Stadt zu verwenden - schade) und es scheint, als wäre sie als Hammer oder als Zuhause für einen Holzwurm verwendet zu sein. Auf jedenfall erinnert sie dich jetzt stark an Prinzessin Beatrices Hochzeitshut.', // Brezel
        'tekel_#00' => 'Der einzige in diesem gottverlassenen Ort, der dir wirklich Liebe schenken kann, ohne dir danach deine Pillen zu stehlen. Soweit du dich mit Hunden auskennst, sieht er ziemlich seltsam aus, aber er ist treu, hat scharfe Zähne und kann wunderbar knurren. Und sollte es doch mal zum schlimmsten Fall kommen, wird sich der Metzger sicher freuen, sich um ihn zu "kümmern"...', // Dackel
        'rlaunc_#00' => 'Der Pfahlwerfer kann dazu verwendet werden, Barrikaden zu verstärken oder jede Art von Gegner abzuwehren. Leider ist nur noch ein Pfahl übrig...', // Pfahlwerfer
        'kalach_#00' => 'Eine mächtige Wasserwaffe, dessen defeker Mechanismus nur noch für einen Schuss reicht.', // Kalaschni-Splash
        'bureau_#00' => 'Dieser wackelige Tisch wurde offensichtlich in Eile gebaut. Doch könnte er, von den Barrikaden geworfen, bestimmt gut einige Zombies zerquetschen.', // Schnellgebauter Tisch
        'distri_#00' => 'Ich weiß was du denkst: "Kann dieser Automat nicht 20 Rationen Wasser und 50 Tüten Chips enthalten?!?" Dummerweise sieht das nicht so aus...aber immerhin ist er sehr klotzig und enthält ein paar Knochen.', // Leerer Automat
        'renne_#00' => 'Es gab eine Zeit, da waren diese edlen Tiere legendär. Naja...diese Zeiten sind zwar vorbei, aber edel sind sie trotzdem noch.', // Santas Rentier
        'paques_#00' => 'Ein weitgehend verschimmeltes Osterei. Sein fauliger Gestank erinnert ein wenig an Schießpulver. Hat der Schimmel etwa seine physikalischen Eigenschaften verändert? Könnte sich lohnen, auf einen Zombie zu werfen.', // Osterei
        'badge_#00' => 'Du siehst ein glitzernden Gegenstand. Beim näheren Betrachten fällt dir auf, dass es ein Abzeichen ist. Das Abzeichen gibt dir die Kraft von 10 Männern. Du fühlst, dass dieser legendäre Gegenstand was besonderes ist.', // ANZAC Badge
        'kalach_#01' => 'Eine Wasserwaffe ist immer sehr effektiv gegen Zombies.Das zählt allerdings nicht für leere...', // Kalaschni-Splash (leer)
        'wire_#00' => 'Pass auf, dich nicht in den Drähten zu verstricken - wir würden dir nur ungern die Finger amputieren müssen, um dich zu befreien...', // Drahtspule
        'oilcan_#00' => ' Ab und an finden wir diese Behälter leer in der Wüste - meistens an der Seite einer Leiche. Wann lernen die Leute endlich, dass man Motoröl nicht trinken kann?', // Ölkännchen
        'lens_#00' => 'Diese fast makellose Linse wartet scheinbar nur darauf, von deiner Kreativität einem höheren Zweck zugeführt zu werden.', // Konvexlinse
        'angryc_#00' => 'Das Kätzchen sieht ein wenig angefressen aus. Du solltest es besser nicht noch mehr reizen, es faucht ja jetzt schon.', // Wütende Mieze (halb verdaut)
        'claymo_#00' => 'Irgendwer hat sie mal in der Wüste vergraben, vermutlich um die Zombies aufzuhalten… Bisher haben sie sich nur als effektiv gegen unvorsichtige Mitbürger erwiesen.', // Tretmine
        'diode_#00' => 'Ein elektronisches Bauteil aus alter Zeit. Du hast nicht viel Ahnung von dem Zeug, aber es scheint noch zu funktionieren.', // Laserdiode
        'guitar_#00' => 'Früher konnte man damit die Damenwelt beeindrucken... Diese Zeiten sind vorbei, doch beliebt ist die Gitarre immer noch. Sie kommt zwar nicht an eine echte Les Paul \'58 heran, aber die Stadt kann ein wenig Auflockerung gebrauchen. Wer hätte denn keinen Spaß an ein bisschen Musik?', // Selbstgebaute Gitarre
        'lsd_#00' => 'Keine Ahnung, wo dieses kleine, pinke Stück Papier herkommt, aber wenn Du es in den Mund nimmst kribbelt dein Gehirn!', // LSD
        'lpoint4_#00' => 'In der alten Welt war er verboten.In den Jahren wurde er weiter verbessert und die Strahlen gebündelt. Heute eignet er sich gut, um durch verrottetes Fleisch zu schneiden.', // Starker Laserpointer (4 Schuss)
        'lpoint3_#00' => 'In der alten Welt war er verboten.In den Jahren wurde er weiter verbessert und die Strahlen gebündelt. Heute eignet er sich gut, um durch verrottetes Fleisch zu schneiden.', // Starker Laserpointer (3 Schuss)
        'lpoint2_#00' => 'In der alten Welt war er verboten.In den Jahren wurde er weiter verbessert und die Strahlen gebündelt. Heute eignet er sich gut, um durch verrottetes Fleisch zu schneiden.', // Starker Laserpointer (2 Schuss)
        'lpoint1_#00' => 'In der alten Welt war er verboten.In den Jahren wurde er weiter verbessert und die Strahlen gebündelt. Heute eignet er sich gut, um durch verrottetes Fleisch zu schneiden.', // Starker Laserpointer (1 Schuss)
        'lpoint_#00' => 'In der alten Welt war er verboten.In den Jahren wurde er weiter verbessert und die Strahlen gebündelt. Heute eignet er sich gut, um durch verrottetes Fleisch zu schneiden. Jetzt brauchst du nur noch eine Batterie...', // Starker Laserpointer (Leer)
        'scope_#00' => 'Vor langer Zeit hat man mit diesem Werkzeug die Sterne beobachtet. Heute beobachten wir Zombies. Damit braucht die Angriffsabschätzung nur noch halb so viele Bürger.', // Teleskop
        'trapma_#00' => 'Nichts sagt "Willkommen zuhause" mehr als eine spontane Fußamputation.', // Unpersönliche Explodierende Fußmatte
        'chudol_#00' => 'Der Geist von Chuck lebt in dieser Figur. Nichts kann ihr schaden und sie wird noch lange nach deinem Tod hier sein, stets unerschrocken im Angesicht der Zombies.', // Chuck-Figur
        'lilboo_#00' => 'Dieses Buch wurde in vielen Sprachen veröffentlicht. Es bietet Rat für aussichtslose Situationen und schützt so vor Angststarren.', // Kleine Zen-Fibel
        'ryebag_#00' => 'Scheint nicht viel besser als Heu zu sein, aber es wird sicher für irgendwas verwendbar sein. Außer ziemlich enttäuschenden Zigaretten.', // Trockene Kräuter
        'fungus_#00' => 'Manchmal finden wir Pilze auf Leichen nach deren Genuss man die merkwürdigsten Sachen von sich gibt. Es heißt, die Schäden am Gehirn seien irreparabel und dein aktueller Zustand könnte das sogar bestätigen...', // Mutterkorn
        'hmbrew_#00' => 'Gesöff der Krieger, Trank der Könige, Cocktail der Götter! Zweifelst Du an der Stärke dieses Getränkes, dann lass ruhig erst einmal Deine Nachbarn probieren.', // Korn-Bräu
        'hifiev_#00' => 'Wenn kombiniert mit einer CD, diese HiFi Anlage hat das Potential zu einer Massenvernichtungswaffe zu werden.', // Verfluchte HiFi
        'cdphil_#00' => 'Nichts ist besser, als mit ein wenig 80er Jahre Retro-Musik den Abend zu beleben! Dreh den Regler hoch und mach das Fenster auf!', // Phil Collins CD
        'bquies_#00' => 'Hast du genug von dem Gequassel vor deiner Tür, den Streitereien nach zu viel Korn-Bräu oder Bürgern, die verzweifelt um ihr Leben schreien? Diese Ohrstöpsel sind genau das Richtige für dich!', // Ohrstöpsel
        'staff2_#00' => 'Ein zerbrochener Stock. Leider zu kurz, um eine vernünftige Waffe zu sein, aber er ist nicht völlig nutzlos...', // Kaputter Stock
        'cdbrit_#00' => 'Trotz dass Du glaubst, dies ist Musik für kleine Mädchen , ist das eine ernsthafte Verteidigungsmaßnahme . Sobald die ersten Töne von der Mini Hi-Fi Anlage erklingen, wird kein normaler Mensch in der Lage sein, sich deinem Haus zu nähern. Vorrausgesetzt unsere Zombies waren mal normal und kennen Facebook ....', // Britney Spears CD
        'cdelvi_#00' => 'Bereit deine Hüften zu ein wenig Rock \'n\' Roll zu schwingen? Diese Sammlung der besten Songs des King of Rock wird Stimmung in jede Party bringen.', // Best of The King CD
        'dfhifi_#00' => 'Beflügelt durch die Hits des Kings, bist du motivierter als jemals zuvor!', // Rock n Roll HiFi
        'dfhifi_#01' => 'Mit der richtigen CD und ein paar guten Ohrstöpseln wird diese HiFi zu einer gefährlichen Waffe, die dich und deine Mitbürger sicher schlafen lässt (zumindest in einiger Distanz)', // Verteidigende HiFi
        'catbox_#00' => 'Sie ist verdammt schwer und du bist dir fast sicher, dass da etwas nützliches drin ist... Aber erstmal brauchst du etwas um sie zu öffnen - zumindest etwas besseres als deine Zähne.', // Schrödingers Box
        'chkspk_#00' => 'Keine Ahnung, ob es am LSD liegt oder daran, dass Chuck Norris so hart ist, er trinkt Napalm gegen Sodbrennen - aber diese Rede hat definitiv einen positiven Einfluss auf unsere Nachtwächter!', // Geistiger Beistand
        'pet_snake2_#00' => 'Diese riesige Schlange scheint Verdauungsprobleme zu haben... SO kannst du jedenfalls nichts mehr mit ihr anfangen.', // Fette Python
        'chest_christmas_3_#00' => 'Sieht so aus als hätte dein Nachbar dir ein Geschenkt gemacht! Dein Name steht drauf! Was mag sie wohl enthalten?.', // Überraschungskiste (3 Geschenke)
        'chest_christmas_2_#00' => 'Sieht so aus als hätte dein Nachbar dir ein Geschenkt gemacht! Dein Name steht drauf! Was mag sie wohl enthalten?.', // Überraschungskiste (2 Geschenke)
        'chest_christmas_1_#00' => 'Sieht so aus als hätte dein Nachbar dir ein Geschenkt gemacht! Dein Name steht drauf! Was mag sie wohl enthalten?.', // Überraschungskiste (1 Geschenk)
        'omg_this_will_kill_you_#00' => 'Dieses gut versiegelte Fläschen enthält Traubensaft. Das ist wirklich komisch... Warum sollte jemand Traubensaft versiegeln? Zumal ein Etikett angebracht wurde: \'Bei versehentlichen Verschlucken, bitte schnellstens die nächste Ambulanz aufsuchen\'. Das willste doch nicht trinken? Oder etwa doch?!', // Verdächtiger Traubensaft
        'rp_scroll_#01' => 'Ein Flaschenetikett... Auf der Rückseite ist es sogar beschrieben!', // Ein Etikett

        'basic_suit_#00' => 'Die Klamotten, die du schon seit ein paar Jahren trägst. Sie sind abgetragen, erfüllen aber ihren Zweck: Sie sind bequem. In diesen Kleidern wirst du auch sterben, soviel steht schon einmal fest.', // Bürgerbekleidung
        'basic_suit_dirt_#00' => 'Schau dich nur an! Deine Kleidung ist vollkommen verdreckt und blutbesprengt! Du solltest sie reinigen, sobald du daheim bist!', // Dreckige Bürgerbekleidung
        'vest_on_#00' => 'Mit dieser Kleidung kannst du dich in der Wüste unerkannt fortbewegen. Dennoch ist Vorsicht geboten: Wenn sich in einer Zone zu viele Zombies aufhalten, kannst du erkannt werden.', // Tarnanzug
        'vest_off_#00' => 'Mit dieser Kleidung kannst du dich in der Wüste unerkannt fortbewegen. Dennoch ist Vorsicht geboten: Wenn sich in einer Zone zu viele Zombies aufhalten, kannst du erkannt werden.', // Tarnanzug (abgelegt)
        'pelle_#00' => 'Mit der kleinen Schaufel musst du in der Wüste nicht so lange graben (automatisch aktiviert). Die Wahrscheinlichkeit einen Gegenstand zu finden ist ebenfalls größer.', // Kleine Schaufel
        'tamed_pet_#00' => 'Der kleine kläffende Malteser stinkt nach nassem Fell, humpelt und sabbert ohne Ende. Einmal pro Tag kannst du ihn mit deinem Rucksackinhalt in die Stadt schicken. Dabei spielt es keine Rolle, wo du dich gerade befindest... Dein treuer Begleiter schlägt sich.', // Dreibeiniger Malteser
        'tamed_pet_drug_#00' => 'Der kleine kläffende Malteser stinkt nach nassem Fell, humpelt und sabbert ohne Ende. Einmal pro Tag kannst du ihn mit deinem Rucksackinhalt in die Stadt schicken. Dabei spielt es keine Rolle, wo du dich gerade befindest... Dein treuer Begleiter schlägt sich.', // Dreibeiniger Malteser (gedopt)
        'tamed_pet_off_#00' => 'Der kleine kläffende Malteser stinkt nach nassem Fell, humpelt und sabbert ohne Ende. Einmal pro Tag kannst du ihn mit deinem Rucksackinhalt in die Stadt schicken. Dabei spielt es keine Rolle, wo du dich gerade befindest... Dein treuer Begleiter schlägt sich.', // Dreibeiniger Malteser (erschöpft)
        'surv_book_#00' => 'Auch wenn der Titel anderes vermuten lässt: "Tick, Trick und Tracks Schlaues Buch" ist von unschätzbarem Wert. Es enthält zahlreiche Tipps und Tricks, wie man in der Natur am besten überleben und Nahrung finden kann.', // Survivalbuch
        'keymol_#00' => 'Unverzichtbar, um auf den Baustellen der Stadt herumzuwuseln, kann der Schraubenschlüssel auch dazu verwendet werden, verschlossene Türen ganz einfach zu öffnen.', // Schraubenschlüssel
        'shield_#00' => ' Ein großer Schutzschild, der keine Wünsche offen lässt, ermöglicht es dir, in der Wüste bis zu 2 zusätzliche Zombies auf Distanz zu halten.', // Schutzschild
        'shaman_#00' => 'Diese uralte Maske ist erfüllt vom Wissen und der Macht tausender Voodoo-Priester. Mit ihr kann der Schamane ausfindig machen, wo in der Außenwelt die Seelen frisch verstorbenen Bürger herumwandeln.', // Voodoo-Maske
        'xmas_gift_#00' => 'Eine Girlande einer scheinbar uralten Tradition. Es wird empfohlen, es zu Hause als Dekoration aufzuhängen, es könnte gut für die Moral der Stadt sein.',
        'pumpkin_on_#00' => 'Hier ist eine verrückte Idee: Dieses große, seltsame Gemüse wurde von seinem Fleisch befreit, geformt und es gibt eine brennende Kerze darin... ',
        'pumpkin_off_#00' => 'Eine Art großes, stinkendes Orangengemüse, wie Sie es noch nie zuvor gesehen haben. Ein grimassierendes Gesicht ist in sein Fleisch geritzt: Was für eine barbarische Tradition steckt hinter diesem Ritual?',
        'firework_box_#00' => 'Diese Schachtel enthält eine ganze Reihe von hochgiftigen Chemikalien mit aufrüttelnden Namen wie: Natrium-Fuzz, Carbopotassium Bling Bling oder Rainbow Lithium Cyanurized.',
        'firework_tube_#00' => 'Mehrere lange Plastikschläuche ohne großes Interesse.',
        'firework_powder_#00' => 'Je nach Epoche wurde dieses Pulver nacheinander als Zünder für verschiedene Artilleriegeschütze, dann als billige Droge für bedürftige Bürger und schließlich als Hauptbestandteil von Feuerwerkskörpern verwendet. In allen drei Fällen haben viele Menschen nicht überlebt.',
        'potion_#00' => 'Diese besondere Ration Wasser, oder genauer gesagt "Weihwasser", sollte es dir ermöglichen, unbeschadet mit gequälten Seelen in Kontakt zu treten... Hoffentlich...',
        'photo_3_#00' => 'Diese nostalgische Knipse aus dem letzten Jahrhundert wirkt, als hätte sie schon Aberhunderten Leuten die Netzhaut verbrannt. Ihr schwacher Blitz könnte dich aus brenzligen Situationen retten, wenn du Zombies damit blendest!',
        'photo_2_#00' => 'Diese nostalgische Knipse aus dem letzten Jahrhundert wirkt, als hätte sie schon Aberhunderten Leuten die Netzhaut verbrannt. Ihr schwacher Blitz könnte dich aus brenzligen Situationen retten, wenn du Zombies damit blendest!',
        'photo_1_#00' => 'Diese nostalgische Knipse aus dem letzten Jahrhundert wirkt, als hätte sie schon Aberhunderten Leuten die Netzhaut verbrannt. Ihr schwacher Blitz könnte dich aus brenzligen Situationen retten, wenn du Zombies damit blendest!',
        'photo_off_#00' => 'Diese nostalgische Knipse aus dem letzten Jahrhundert wirkt, als hätte sie schon Aberhunderten Leuten die Netzhaut verbrannt. Ihr schwacher Blitz könnte dich aus brenzligen Situationen retten, wenn du Zombies damit blendest!',
        'food_xmas_#00' => 'Das ist eine seltsam aussehende kleine Bestie... Scheint einer Krähe zu ähneln... Aber es ist grün und trägt einen komischen Hut...',
        'wood_xmas_#00' => 'Entweder ein verschrumpelter alter Weihnachtskuchen oder etwas weniger Schmackhaftes, das dennoch am Weihnachtstag gebacken wird! Genießen Sie auf jeden Fall diesen Kuchen... Ding...',
        'leprechaun_suit_#00' => 'In dieser Aufmachung sind Sie so auffällig, dass Sie niemand bemerkt oder glaubt, sich das eingebildet zu haben! Sie würden es nicht missbrauchen, oder?',
        'broken_#00' => 'Diese Trümmerstücke waren mal Teil eines Gegenstandes, den du nicht mehr identifizieren kannst. Die Verformung der Teile lassen vermuten, dass dieser Gegenstand mit hoher Geschwindigkeit am Boden aufgeprallt ist...',
        'bullets_#00' => 'Eine Handvoll Munition. Aber was hat das für einen Sinn?',
        'christmas_candy_#00' => 'Es sieht aus wie eine Art Schokoladenbonbon mit alkoholischem Likör darin. Oder eine andere Füllung...'
    ];
    
    public static $item_prototype_properties = [
        'saw_tool_#00'               => [ 'impoundable', 'can_opener', 'box_opener' ],
        'can_opener_#00'             => [ 'impoundable', 'weapon', 'can_opener', 'box_opener', 'nw_armory' ],
        'screw_#00'                  => [ 'impoundable', 'weapon', 'can_opener', 'box_opener', 'nw_armory' ],
        'swiss_knife_#00'            => [ 'impoundable', 'weapon', 'can_opener', 'box_opener', 'nw_armory' ],
        'wrench_#00'                 => [ 'impoundable', 'weapon', 'box_opener', 'nw_armory' ],
        'cutter_#00'                 => [ 'impoundable', 'weapon', 'box_opener', 'nw_armory' ],
        'small_knife_#00'            => [ 'impoundable', 'weapon', 'box_opener', 'nw_armory' ],
        'bone_#00'                   => [ 'impoundable', 'weapon', 'box_opener', 'nw_armory' ],
        'cutcut_#00'                 => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory' ],
        'chair_basic_#00'            => [ 'box_opener', 'nw_ikea', 'nw_armory' ],
        'chair_#00'                  => [ 'nw_armory' ],
        'staff_#00'                  => [ 'impoundable', 'weapon', 'box_opener', 'nw_armory' ],
        'chain_#00'                  => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory' ],
        'pc_#00'                     => [ 'box_opener', 'nw_ikea', 'nw_armory' ],
        'door_#00'                   => [ 'impoundable', 'defence' ],
        'car_door_#00'               => [ 'impoundable', 'defence' ],
        'pet_dog_#00'                => [ 'impoundable', 'defence', 'pet', 'esc_fixed' ],
        'plate_#00'                  => [ 'impoundable', 'defence' ],
        'torch_#00'                  => [ 'impoundable', 'defence', 'weapon', 'nw_ikea', 'nw_armory', 'prevent_night' ],
        'tekel_#00'                  => [ 'impoundable', 'defence', 'lock', 'pet' ],
        'trestle_#00'                => [ 'impoundable', 'defence' ],
        'table_#00'                  => [ 'impoundable', 'defence' ],
        'bed_#00'                    => [ 'impoundable', 'defence' ],
        'wood_plate_#00'             => [ 'impoundable', 'defence' ],
        'concrete_wall_#00'          => [ 'impoundable', 'defence' ],
        'wood_bad_#00'               => [ 'impoundable', 'ressource' ],
        'metal_bad_#00'              => [ 'impoundable', 'ressource' ],
        'wood2_#00'                  => [ 'impoundable', 'ressource', 'hero_find' ],
        'metal_#00'                  => [ 'impoundable', 'ressource', 'hero_find' ],
        'wood_beam_#00'              => [ 'impoundable', 'ressource' ],
        'metal_beam_#00'             => [ 'impoundable', 'ressource' ],
        'courroie_#00'               => [ 'impoundable', 'ressource' ],
        'deto_#00'                   => [ 'impoundable', 'ressource' ],
        'tube_#00'                   => [ 'impoundable', 'ressource' ],
        'rustine_#00'                => [ 'impoundable', 'ressource' ],
        'electro_#00'                => [ 'impoundable', 'ressource' ],
        'meca_parts_#00'             => [ 'impoundable', 'ressource' ],
        'explo_#00'                  => [ 'impoundable', 'ressource' ],
        'mecanism_#00'               => [ 'impoundable', 'ressource', 'hero_find_lucky' ],
        'grenade_#00'                => [ 'impoundable', 'weapon', 'hero_find', 'esc_fixed', 'nw_armory', 'hero_find_lucky' ],
        'bgrenade_#00'               => [ 'impoundable', 'weapon', 'nw_armory' ],
        'boomfruit_#00'              => [ 'impoundable', 'weapon', 'nw_armory' ],
        'pilegun_#00'                => [ 'impoundable', 'weapon', 'nw_armory' ],
        'pilegun_up_#00'             => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'big_pgun_#00'               => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'big_pgun_empty_#00'         => [ 'esc_fixed' ],
        'mixergun_#00'               => [ 'impoundable', 'weapon', 'nw_armory' ],
        'chainsaw_#00'               => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory' ],
        'taser_#00'                  => [ 'impoundable', 'weapon', 'nw_armory' ],
        'lpoint4_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'lpoint3_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'lpoint2_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'lpoint1_#00'                => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_armory' ],
        'watergun_opt_5_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
        'watergun_opt_4_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
        'watergun_opt_3_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
        'watergun_opt_2_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
        'watergun_opt_1_#00'         => [ 'impoundable', 'weapon', 'esc_fixed', 'nw_shooting', 'nw_armory' ],
        'kalach_#00'                 => [ 'nw_shooting', 'nw_armory' ],
        'watergun_3_#00'             => [ 'impoundable', 'weapon', 'nw_shooting', 'nw_armory' ],
        'watergun_2_#00'             => [ 'impoundable', 'weapon', 'nw_shooting', 'nw_armory' ],
        'watergun_1_#00'             => [ 'impoundable', 'weapon', 'nw_shooting', 'nw_armory' ],
        'jerrygun_#00'               => [ 'impoundable', 'weapon', 'esc_fixed' ],
        'jerrycan_#00'               => [ 'hero_find_lucky' ],
        'knife_#00'                  => [ 'impoundable', 'weapon', 'box_opener', 'esc_fixed', 'nw_armory' ],
        'lawn_#00'                   => [ 'impoundable', 'weapon', 'nw_armory' ],
        'torch_off_#00'              => [ 'impoundable', 'weapon', 'nw_armory' ],
        'iphone_#00'                 => [ 'impoundable', 'weapon', 'nw_armory' ],
        'machine_1_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory' ],
        'machine_2_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory' ],
        'machine_3_#00'              => [ 'esc_fixed', 'nw_ikea', 'nw_armory' ],
        'disinfect_#00'              => [ 'impoundable', 'drug' ],
        'drug_#00'                   => [ 'can_poison', 'impoundable', 'drug' ],
        'drug_hero_#00'              => [ 'impoundable', 'drug', 'esc_fixed' ],
        'drug_random_#00'            => [ 'impoundable', 'drug' ],
        'beta_drug_bad_#00'          => [ 'impoundable', 'drug' ],
        'beta_drug_#00'              => [ 'impoundable', 'drug' ],
        'xanax_#00'                  => [ 'impoundable', 'drug', 'hero_find_lucky' ],
        'drug_water_#00'             => [ 'impoundable', 'drug' ],
        'bandage_#00'                => [ 'impoundable', 'drug' ],
        'pharma_#00'                 => [ 'impoundable', 'drug' ],
        'pharma_part_#00'            => [ 'impoundable', 'drug' ],
        'lsd_#00'                    => [ 'impoundable', 'drug' ],
        'radio_on_#00'               => [ 'impoundable', 'nw_ikea' ],
        'water_#00'                  => [ 'can_poison', 'hero_find', 'esc_fixed', 'hero_find_lucky' ],
        'can_open_#00'               => [ 'can_poison', 'food', 'can_cook' ],
        'vegetable_#00'              => [ 'can_poison', 'food', 'can_cook' ],
        'fruit_#00'                  => [ 'can_poison', 'food', 'can_cook' ],
        'water_can_3_#00'            => [ 'can_poison', 'esc_fixed' ],
        'water_can_2_#00'            => [ 'can_poison', 'esc_fixed' ],
        'water_can_1_#00'            => [ 'can_poison', 'esc_fixed' ],
        'lock_#00'                   => [ 'lock' ],
        'dfhifi_#01'                 => [ 'lock' ],
        'pile_#00'                   => [ 'hero_find', 'hero_find_lucky' ],
        'food_bag_#00'               => [ 'hero_find' ],
        'rsc_pack_3_#00'             => [ 'hero_find_lucky' ],
        'rsc_pack_2_#00'             => [ 'hero_find' ],
        'bretz_#00'                  => [ 'food', 'esc_fixed', 'can_cook' ],
        'undef_#00'                  => [ 'food', 'can_cook' ],
        'dish_#00'                   => [ 'food' ],
        'chama_#00'                  => [ 'can_cook' ],
        'food_bar1_#00'              => [ 'food', 'can_cook' ],
        'food_bar2_#00'              => [ 'food', 'can_cook' ],
        'food_bar3_#00'              => [ 'food', 'can_cook' ],
        'food_biscuit_#00'           => [ 'food', 'can_cook' ],
        'food_chick_#00'             => [ 'food', 'can_cook' ],
        'food_pims_#00'              => [ 'food', 'can_cook' ],
        'food_tarte_#00'             => [ 'food', 'can_cook' ],
        'food_sandw_#00'             => [ 'food', 'can_cook' ],
        'food_noodles_#00'           => [ 'food', 'can_cook' ],
        'hmeat_#00'                  => [ 'food', 'can_cook' ],
        'bone_meat_#00'              => [ 'food', 'can_cook' ],
        'cadaver_#00'                => [ 'food', 'can_cook' ],
        'food_noodles_hot_#00'       => [ 'food', 'esc_fixed' ],
        'meat_#00'                   => [ 'food', 'esc_fixed', 'hero_find_lucky' ],
        'vegetable_tasty_#00'        => [ 'food', 'esc_fixed' ],
        'dish_tasty_#00'             => [ 'food', 'esc_fixed' ],
        'food_candies_#00'           => [ 'food', 'esc_fixed' ],
        'chama_tasty_#00'            => [ 'food', 'esc_fixed' ],
        'woodsteak_#00'              => [ 'food', 'esc_fixed' ],
        'egg_#00'                    => [ 'food', 'esc_fixed' ],
        'apple_#00'                  => [ 'food', 'esc_fixed' ],
        'angryc_#00'                 => [ 'pet', 'weapon' ],
        'pet_cat_#00'                => [ 'pet', 'esc_fixed', 'nw_trebuchet', 'nw_ikea' ],
        'pet_chick_#00'              => [ 'pet', 'nw_trebuchet' ],
        'pet_pig_#00'                => [ 'pet', 'nw_trebuchet' ],
        'pet_rat_#00'                => [ 'pet', 'nw_trebuchet' ],
        'pet_snake_#00'              => [ 'pet', 'nw_trebuchet' ],
        'renne_#00'                  => [ 'nw_trebuchet' ],
        'book_gen_letter_#00'        => [ 'esc_fixed' ],
        'book_gen_box_#00'           => [ 'esc_fixed' ],
        'postal_box_#00'             => [ 'esc_fixed' ],
        'postal_box_#01'             => [ 'esc_fixed' ],
        'pocket_belt_#00'            => [ 'esc_fixed' ],
        'bag_#00'                    => [ 'esc_fixed' ],
        'bagxl_#00'                  => [ 'esc_fixed' ],
        'radius_mk2_#00'             => [ 'esc_fixed' ],
        'rp_book_#00'                => [ 'esc_fixed' ],
        'rp_book_#01'                => [ 'esc_fixed' ],
        'rp_book2_#00'               => [ 'esc_fixed' ],
        'rp_scroll_#00'              => [ 'esc_fixed' ],
        'rp_scroll_#01'              => [ 'esc_fixed' ],
        'rp_sheets_#00'              => [ 'esc_fixed' ],
        'rp_letter_#00'              => [ 'esc_fixed' ],
        'rp_manual_#00'              => [ 'esc_fixed' ],
        'lilboo_#00'                 => [ 'esc_fixed', 'prevent_terror' ],
        'rp_twin_#00'                => [ 'esc_fixed' ],
        'home_box_#00'               => [ 'nw_ikea' ],
        'lamp_#00'                   => [ 'nw_ikea' ],
        'lamp_on_#00'                => [ 'nw_ikea', 'prevent_night' ],
        'music_#00'                  => [ 'nw_ikea' ],
        'distri_#00'                 => [ 'nw_ikea' ],
        'guitar_#00'                 => [ 'nw_ikea' ],
        'bureau_#00'                 => [ 'nw_ikea' ],
        'rlaunc_#00'                 => [ 'nw_armory' ],
        'repair_one_#00'             => [ 'hero_find_lucky' ],
        'electro_box_#00'            => [ 'hero_find_lucky' ],
        'christmas_candy_#00'        => [ 'can_cook' ],
        'omg_this_will_kill_you_#00' => [ 'can_cook' ],
        'chidol_#00'                 => [ 'prevent_terror' ],
        'maglite_1_#00'              => [ 'prevent_night' ],
        'maglite_2_#00'              => [ 'prevent_night' ],
        'wood_xmas_#00'              => [ 'food' ],
    ];

    public static $item_groups = [
        'empty_dig' => array(
            array('item' => 'wood_bad_#00', 'count' => '41306'),
            array('item' => 'metal_bad_#00', 'count' => '22856'),
        ),
        'base_dig' => array(
            array('item' => 'wood2_#00', 'count' => '16764'),
            array('item' => 'metal_#00', 'count' => '10124'),
            array('item' => 'grenade_empty_#00', 'count' => '6915'),
            array('item' => 'food_bag_#00', 'count' => '4845'),
            array('item' => 'pile_#00', 'count' => '4766'),
            array('item' => 'pharma_#00', 'count' => '3935'),
            array('item' => 'rustine_#00', 'count' => '2578'),
            array('item' => 'can_#00', 'count' => '2445'),
            array('item' => 'concrete_#00', 'count' => '1689'),
            array('item' => 'wood_plate_part_#00', 'count' => '1529'),
            array('item' => 'jerrycan_#00', 'count' => '1456'),
            array('item' => 'chest_tools_#00', 'count' => '1390'),
            array('item' => 'deco_box_#00', 'count' => '1309'),
            array('item' => 'bplan_drop_#00', 'count' => '1232'),
            array('item' => 'digger_#00', 'count' => '1231'),
            array('item' => 'tube_#00', 'count' => '1184'),
            array('item' => 'wood_beam_#00', 'count' => '1176'),
            array('item' => 'powder_#00', 'count' => '1159'),
            array('item' => 'staff_#00', 'count' => '1138'),
            array('item' => 'oilcan_#00', 'count' => '1100'),
            array('item' => 'mecanism_#00', 'count' => '1063'),
            array('item' => 'drug_#00', 'count' => '965'),
            array('item' => 'fest_#00', 'count' => '964'),
            array('item' => 'meca_parts_#00', 'count' => '964'),
            array('item' => 'plate_raw_#00', 'count' => '963'),
            array('item' => 'watergun_empty_#00', 'count' => '953'),
            array('item' => 'deto_#00', 'count' => '935'),
            array('item' => 'repair_one_#00', 'count' => '907'),
            array('item' => 'pet_snake_#00', 'count' => '877'),
            array('item' => 'electro_box_#00', 'count' => '854'),
            array('item' => 'tekel_#00', 'count' => '845'),
            array('item' => 'door_#00', 'count' => '841'),
            array('item' => 'drug_random_#00', 'count' => '818'),
            array('item' => 'smoke_bomb_#00', 'count' => '814'),
            array('item' => 'bag_#00', 'count' => '808'),
            array('item' => 'water_cleaner_#00', 'count' => '803'),
            array('item' => 'tagger_#00', 'count' => '794'),
            array('item' => 'machine_3_#00', 'count' => '794'),
            array('item' => 'trestle_#00', 'count' => '790'),
            array('item' => 'pilegun_empty_#00', 'count' => '784'),
            array('item' => 'machine_1_#00', 'count' => '777'),
            array('item' => 'bretz_#00', 'count' => '775'),
            array('item' => 'explo_#00', 'count' => '771'),
            array('item' => 'food_noodles_#00', 'count' => '763'),
            array('item' => 'chest_#00', 'count' => '734'),
            array('item' => 'machine_2_#00', 'count' => '729'),
            array('item' => 'pet_rat_#00', 'count' => '710'),
            array('item' => 'wire_#00', 'count' => '691'),
            array('item' => 'drug_hero_#00', 'count' => '634'),
            array('item' => 'metal_beam_#00', 'count' => '592'),
            array('item' => 'ryebag_#00', 'count' => '541'),
            array('item' => 'spices_#00', 'count' => '508'),
            array('item' => 'small_knife_#00', 'count' => '501'),
            array('item' => 'bed_#00', 'count' => '501'),
            array('item' => 'rhum_#00', 'count' => '499'),
            array('item' => 'chain_#00', 'count' => '493'),
            array('item' => 'pet_chick_#00', 'count' => '491'),
            array('item' => 'cutter_#00', 'count' => '476'),
            array('item' => 'pet_pig_#00', 'count' => '474'),
            array('item' => 'meat_#00', 'count' => '474'),
            array('item' => 'can_opener_#00', 'count' => '469'),
            array('item' => 'lilboo_#00', 'count' => '458'),
            array('item' => 'electro_#00', 'count' => '427'),
            array('item' => 'chair_basic_#00', 'count' => '423'),
            array('item' => 'xanax_#00', 'count' => '418'),
            array('item' => 'diode_#00', 'count' => '416'),
            array('item' => 'pet_cat_#00', 'count' => '396'),
            array('item' => 'lights_#00', 'count' => '381'),
            array('item' => 'sport_elec_empty_#00', 'count' => '376'),
            array('item' => 'lock_#00', 'count' => '369'),
            array('item' => 'lens_#00', 'count' => '366'),
            array('item' => 'chest_food_#00', 'count' => '364'),
            array('item' => 'chudol_#00', 'count' => '347'),
            array('item' => 'angryc_#00', 'count' => '342'),
            array('item' => 'home_box_#00', 'count' => '302'),
            array('item' => 'home_def_#00', 'count' => '294'),
            array('item' => 'fence_#00', 'count' => '293'),
            array('item' => 'repair_kit_part_raw_#00', 'count' => '293'),
            array('item' => 'rsc_pack_2_#00', 'count' => '290'),
            array('item' => 'lamp_#00', 'count' => '288'),
            array('item' => 'disinfect_#00', 'count' => '288'),
            array('item' => 'book_gen_letter_#00', 'count' => '286'),
            array('item' => 'bandage_#00', 'count' => '284'),
            array('item' => 'plate_#00', 'count' => '283'),
            array('item' => 'chest_citizen_#00', 'count' => '282'),
            array('item' => 'cart_part_#00', 'count' => '274'),
            array('item' => 'chair_#00', 'count' => '273'),
            array('item' => 'bquies_#00', 'count' => '257'),
            array('item' => 'screw_#00', 'count' => '240'),
            array('item' => 'badge_#00', 'count' => '238'),
            array('item' => 'pc_#00', 'count' => '215'),
            array('item' => 'music_part_#00', 'count' => '210'),
            array('item' => 'book_gen_box_#00', 'count' => '209'),
            array('item' => 'cyanure_#00', 'count' => '206'),
            array('item' => 'hmeat_#00', 'count' => '195'),
            array('item' => 'knife_#00', 'count' => '194'),
            array('item' => 'engine_part_#00', 'count' => '193'),
            array('item' => 'game_box_#00', 'count' => '192'),
            array('item' => 'wood_log_#00', 'count' => '190'),
            array('item' => 'vibr_empty_#00', 'count' => '189'),
            array('item' => 'cigs_#00', 'count' => '181'),
            array('item' => 'out_def_#00', 'count' => '180'),
            array('item' => 'courroie_#00', 'count' => '179'),
            array('item' => 'catbox_#00', 'count' => '170'),
            array('item' => 'sheet_#00', 'count' => '115'),
            array('item' => 'iphone_#00', 'count' => '112'),
            array('item' => 'money_#00', 'count' => '110'),
            array('item' => 'home_box_xl_#00', 'count' => '109'),
            array('item' => 'coffee_machine_part_#00', 'count' => '103'),
            array('item' => 'cadaver_#00', 'count' => '102'),
            array('item' => 'smelly_meat_#00', 'count' => '102'),
            array('item' => 'maglite_off_#00', 'count' => '100'),
            array('item' => 'vodka_#00', 'count' => '100'),
            array('item' => 'postal_box_#00', 'count' => '98'),
            array('item' => 'big_pgun_part_#00', 'count' => '95'),
            array('item' => 'car_door_part_#00', 'count' => '95'),
            array('item' => 'chama_#00', 'count' => '94'),
            array('item' => 'water_can_empty_#00', 'count' => '93'),
            array('item' => 'cdbrit_#00', 'count' => '92'),
            array('item' => 'pilegun_upkit_#00', 'count' => '90'),
            array('item' => 'saw_tool_part_#00', 'count' => '90'),
            array('item' => 'beta_drug_bad_#00', 'count' => '90'),
            array('item' => 'rp_book_#00', 'count' => '90'),
            array('item' => 'chest_xl_#00', 'count' => '90'),
            array('item' => 'poison_part_#00', 'count' => '90'),
            array('item' => 'gun_#00', 'count' => '90'),
            array('item' => 'rsc_pack_3_#00', 'count' => '89'),
            array('item' => 'safe_#00', 'count' => '88'),
            array('item' => 'rp_twin_#00', 'count' => '87'),
            array('item' => 'food_armag_#00', 'count' => '87'),
            array('item' => 'cdelvi_#00', 'count' => '82'),
            array('item' => 'cdphil_#00', 'count' => '67'),
            array('item' => 'cinema_#00', 'count' => '53'),
            //array('item' => 'vodka_de_#00', 'count' => '6'),
        ),
        'christmas_dig' => [
            array('item' => 'renne_#00', 'count' => '8'),
            array('item' => 'sand_ball_#00', 'count' => '14'),
            array('item' => 'christmas_suit_3_#00', 'count' => '4'),
            array('item' => 'christmas_suit_1_#00', 'count' => '3'),
            array('item' => 'christmas_suit_2_#00', 'count' => '1'),
            array('item' => 'food_xmas_#00', 'count' => '2'),
        ],
        'christmas_dig_post' => [
            array('item' => 'postal_box_#01', 'count' => '3'),
            array('item' => 'postal_box_xl_#00', 'count' => '1'),
        ],
        'easter_dig' => [
            array('item' => 'paques_#00', 'count' => '207'),
        ],
        'trash_good' => [
            array('item' => 'fence_#00', 'count' => '33'),
            array('item' => 'chest_#00', 'count' => '6'),
            array('item' => 'wood2_#00', 'count' => '15'),
            array('item' => 'metal_#00', 'count' => '2'),
            array('item' => 'repair_one_#00', 'count' => '7'),
            array('item' => 'home_def_#00', 'count' => '9'),
            array('item' => 'home_box_#00', 'count' => '4'),
        ],
        'trash_bad' => [
            array('item' => 'fruit_sub_part_#00', 'count' => '205'),
            array('item' => 'fruit_part_#00', 'count' => '54'),
            array('item' => 'pharma_part_#00', 'count' => '148'),
            array('item' => 'flesh_part_#00', 'count' => '311'),
            array('item' => 'water_cup_part_#00', 'count' => '16'),
        ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_item_categories(ObjectManager $manager, ConsoleOutputInterface $out) {
        // Mark all entries as "not imported"
        $changed = true;
        $missing_data = static::$item_category_data;
        $out->writeln( '<comment>Item categories: ' . count(static::$item_category_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$item_category_data) );

        // As long as the last query performed any changes and we still have not-imported data , continue
        while ($changed && !empty($missing_data)) {
            // Set the current data, and clear state
            $current = $missing_data;
            $missing_data = [];
            $changed = false;

            // For each missing entry
            foreach ($current as $entry) {

                // Check if this entry has a parent, and attempt to fetch the parent from the database
                $parent = null;
                if ($entry['parent'] !== null) {
                    $parent = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['parent']] );
                    // If the entry has a parent, but that parent is missing from the database,
                    // defer the current entry for the next run
                    if ($parent === null) {
                        $missing_data[] = $entry;
                        continue;
                    }
                }

                // Attempt to fetch the current entry from the database; if the entry does not exist, create a new one
                $entity = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['name']] );
                if (!$entity) $entity = new ItemCategory();

                // Set properties
                $entity->setName( $entry['name'] );
                $entity->setLabel( $entry['label'] );
                $entity->setOrdering( $entry['ordering'] );
                $entity->setParent( $entry['parent'] === null ? null :
                    $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['parent']] )
                );

                // Persist entry
                $manager->persist( $entity );
                $progress->advance();
                $changed = true;
            }

            // Flush
            $manager->flush();
            $progress->finish();
        }

        if (!empty($missing_data)) {
            $out->writeln('<error>Unable to insert all fixtures. The following entries are missing:</error>');
            $table2 = new Table( $out->section() );
            $table2->setHeaders( ['Name', 'Label', 'Parent', 'Ordering'] );
            foreach ($missing_data as $entry)
                $table2->addRow( [ $entry['name'], $entry['label'], $entry['parent'], $entry['ordering'] ] );
            $table2->render();
        }
    }

    protected function insert_item_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {

        $out->writeln( '<comment>Item prototypes: ' . count(static::$item_prototype_data) . ' fixture entries available.</comment>' );

        // Get misc category
        $misc_category = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => 'Misc'] );
        $cache = [];

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$item_prototype_data) );

        $properties = [];

        // Iterate over all entries
        foreach (static::$item_prototype_data as $entry) {
            // Set up the icon cache
            if (!isset($cache[$entry['icon']])) $cache[$entry['icon']] = 0;
            else $cache[$entry['icon']]++;

            // Generate unique ID
            $entry_unique_id = $entry['icon'] . '_#' . str_pad($cache[$entry['icon']],2, '0',STR_PAD_LEFT);

            // Check the category
            $category = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['category']] );
            if ($category === null) {
                $category = $misc_category;
                $out->writeln('<error>Unable to locate category \'' . $entry['category'] . '\' for item \'' .
                    $entry_unique_id . '\'. Assigning MISC category instead.</error>');
            }
            if ($category === null) {
                $out->writeln('<error>Unable to locate category \'' . $entry['category'] . '\' for item \'' .
                    $entry_unique_id . '\'. MISC category could not be located, entry will be skipped!</error>');
                continue;
            }

            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $entry_unique_id] );
            if ($entity === null) $entity = new ItemPrototype();

            // Set property
            $entity->setName( $entry_unique_id )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setDeco( $entry['deco'] )
                ->setHeavy( $entry['heavy'] )
                ->setCategory( $category )
                ->setDescription( static::$item_desc_data[ $entry_unique_id ] ?? "" )
                ->setHideInForeignChest( $entry['hideInForeignChest'] ?? false )
                ->getProperties()->clear();

            $entity
                ->setWatchpoint($entry['watchpoint'] ?? 0)
                ->setFragile( $entry['fragile'] ?? false );

            if (isset(static::$item_prototype_properties[$entry_unique_id]))
                foreach (static::$item_prototype_properties[$entry_unique_id] as $property) {
                    if (!isset($properties[$property])) {
                        $properties[$property] = $manager->getRepository(ItemProperty::class)->findOneBy( ['name' => $property] );
                        if (!$properties[$property]) {
                            $p = new ItemProperty();
                            $p->setName( $property );
                            $properties[$property] = $p;
                            $manager->persist( $properties[$property] );
                        }
                    }
                    $entity->addProperty( $properties[$property] );
                }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        // Flush
        $manager->flush();
        $progress->finish();
    }

    public function insert_default_item_groups( ObjectManager $manager, ConsoleOutputInterface $out ) {
        $out->writeln( '<comment>Default item groups: ' . count(static::$item_groups) . ' fixture entries available.</comment>' );

        foreach (static::$item_groups as $name => $group)
            $manager->persist( FixtureHelper::createItemGroup( $manager, $name, $group ) );

        $manager->flush();

        $out->writeln('<info>Done!</info>');
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Item Database</info>' );
        $output->writeln("");

        $this->insert_item_categories( $manager, $output );
        $output->writeln("");
        $this->insert_item_prototypes( $manager, $output );
        $output->writeln("");
        $this->insert_default_item_groups( $manager, $output );
        $output->writeln("");
    }
}
