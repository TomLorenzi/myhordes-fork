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
        ["name"=>"root_rsc" ,"label"=>"Baustoffe"               ,"parent"=>null,"ordering"=>0],
            ["name"=>"rsc_b","label"=>"Grundlegend","parent"=>"root_rsc","ordering"=>0],
            ["name"=>"rsc"  ,"label"=>"Sonstiges"  ,"parent"=>"root_rsc","ordering"=>1],
        ["name"=>"root_food","label"=>"Grundnahrungsmittel"     ,"parent"=>null,"ordering"=>1],
            ["name"=>"food"  ,"label"=>"Nahrung und Wasser","parent"=>"root_food","ordering"=>0],
            ["name"=>"food_7","label"=>"Leckeres Essen"    ,"parent"=>"root_food","ordering"=>1],
            ["name"=>"food_a","label"=>"Alkohol"           ,"parent"=>"root_food","ordering"=>2],
            ["name"=>"food_g","label"=>"Menschenfleisch"   ,"parent"=>"root_food","ordering"=>3],
            ["name"=>"food_n","label"=>"Nicht Essbar"      ,"parent"=>"root_food","ordering"=>4],
        ["name"=>"root_drug","label"=>"Apotheke und Labor"      ,"parent"=>null,"ordering"=>2],
            ["name"=>"drug_d","label"=>"Drogen"                 ,"parent"=>"root_drug","ordering"=>0],
            ["name"=>"drug"  ,"label"=>"Chemikalien und Zubehör","parent"=>"root_drug","ordering"=>1],
        ["name"=>"root_exp" ,"label"=>"Expedition"              ,"parent"=>null,"ordering"=>3],
            ["name"=>"exp_b","label"=>"Taschen und Behälter","parent"=>"root_exp","ordering"=>0],
            ["name"=>"exp_s","label"=>"Nützliches"          ,"parent"=>"root_exp","ordering"=>1],
            ["name"=>"camp" ,"label"=>"Camping"             ,"parent"=>"root_exp","ordering"=>2],
        ["name"=>"root_aud" ,"label"=>"Angriff und Verteidigung","parent"=>null,"ordering"=>4],
            ["name"=>"armor" ,"label"=>"Verteidigung"       ,"parent"=>"root_aud","ordering"=>0],
            ["name"=>"weapon","label"=>"Waffen"             ,"parent"=>"root_aud","ordering"=>1],
            ["name"=>"aud_n" ,"label"=>"Nicht Einsatzbereit","parent"=>"root_aud","ordering"=>2],
        ["name"=>"root_misc","label"=>"Sonstiges"               ,"parent"=>null,"ordering"=>5],
            ["name"=>"box"      ,"label"=>"Kisten und Behälter","parent"=>"root_misc","ordering"=>0],
            ["name"=>"imp"      ,"label"=>"Wichtige Items"     ,"parent"=>"root_misc","ordering"=>1],
            ["name"=>"furniture","label"=>"Dekoration"         ,"parent"=>"root_misc","ordering"=>2],
            ["name"=>"misc"     ,"label"=>"Sonstige Items"     ,"parent"=>"root_misc","ordering"=>3],
    ];

    public static $item_prototype_data = [
        ["label"=>"Ration Wasser","icon"=>"water","category"=>"food","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Batterie","icon"=>"pile","category"=>"misc","deco"=>0,"heavy"=>false],                                                   // In Out
        ["label"=>"Konservendose","icon"=>"can","category"=>"food_n","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Offene Konservendose","icon"=>"can_open","category"=>"food","deco"=>0,"heavy"=>false],                                   // In Out
        ["label"=>"Batteriewerfer 1-PDTG (geladen)","icon"=>"pilegun","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Taser","icon"=>"taser","category"=>"weapon","deco"=>0,"heavy"=>false],                                                   // -- Out
        ["label"=>"Aqua-Splash (leer)","icon"=>"watergun_opt_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                          // In Out
        ["label"=>"Handmixer (geladen)","icon"=>"mixergun","category"=>"weapon","deco"=>0,"heavy"=>false],                                  // -- Out
        ["label"=>"Kettensäge (geladen)","icon"=>"chainsaw","category"=>"weapon","deco"=>0,"heavy"=>true],                                  // -- Out
        ["label"=>"Rasenmäher","icon"=>"lawn","category"=>"weapon","deco"=>0,"heavy"=>true],                                                // -- Out
        ["label"=>"Verstellbarer Schraubenschlüssel","icon"=>"wrench","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Schraubenzieher","icon"=>"screw","category"=>"weapon","deco"=>0,"heavy"=>false],                                         // -- Out
        ["label"=>"Großer trockener Stock","icon"=>"staff","category"=>"weapon","deco"=>0,"heavy"=>false],                                  // -- Out
        ["label"=>"Jagdmesser","icon"=>"knife","category"=>"weapon","deco"=>0,"heavy"=>false],                                              // -- Out
        ["label"=>"Machete","icon"=>"cutcut","category"=>"weapon","deco"=>0,"heavy"=>false],                                                // -- Out
        ["label"=>"Lächerliches Taschenmesser","icon"=>"small_knife","category"=>"weapon","deco"=>0,"heavy"=>false],                        // -- Out
        ["label"=>"Schweizer Taschenmesser","icon"=>"swiss_knife","category"=>"weapon","deco"=>0,"heavy"=>false],                           // -- Out
        ["label"=>"Teppichmesser","icon"=>"cutter","category"=>"weapon","deco"=>0,"heavy"=>false],                                          // -- Out
        ["label"=>"Einkaufswagen","icon"=>"cart","category"=>"exp_b","deco"=>0,"heavy"=>false],                                             // -- ---
        ["label"=>"Dosenöffner","icon"=>"can_opener","category"=>"weapon","deco"=>0,"heavy"=>false],                                        // -- Out
        ["label"=>"Extra Tasche","icon"=>"bag","category"=>"exp_b","deco"=>0,"heavy"=>false],                                               // -- ---
        ["label"=>"Streichholzschachtel","icon"=>"lights","category"=>"misc","deco"=>0,"heavy"=>false],                                     // -- ---
        ["label"=>"Beruhigungsspritze","icon"=>"xanax","category"=>"drug_d","deco"=>0,"heavy"=>false],                                      // In Out
        ["label"=>"Schaukelstuhl","icon"=>"chair","category"=>"furniture","deco"=>5,"heavy"=>true],                                         // -- ---
        ["label"=>"Staubiges Buch","icon"=>"rp_book","category"=>"imp","deco"=>0,"heavy"=>false],                                           // In Out
        ["label"=>"Matratze","icon"=>"bed","category"=>"armor","deco"=>3,"heavy"=>true],                                                    // -- ---
        ["label"=>"Ausgeschaltete Nachttischlampe","icon"=>"lamp","category"=>"furniture","deco"=>1,"heavy"=>false],                        // In Out
        ["label"=>"Perser","icon"=>"carpet","category"=>"furniture","deco"=>10,"heavy"=>false],                                             // -- ---
        ["label"=>"Mini Hi-Fi Anlage (defekt)","icon"=>"music_part","category"=>"furniture","deco"=>1,"heavy"=>true],                       // -- ---
        ["label"=>"Kette + Vorhängeschloss","icon"=>"lock","category"=>"furniture","deco"=>0,"heavy"=>false],                               // -- ---
        ["label"=>"Fußabstreifer","icon"=>"door_carpet","category"=>"furniture","deco"=>5,"heavy"=>false],                                  // -- ---
        ["label"=>"Ein paar Würfel","icon"=>"dice","category"=>"imp","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Motor","icon"=>"engine","category"=>"imp","deco"=>0,"heavy"=>true],                                                      // -- ---
        ["label"=>"Riemen","icon"=>"courroie","category"=>"rsc","deco"=>0,"heavy"=>false],                                                  // -- ---
        ["label"=>"Handvoll Schrauben und Muttern","icon"=>"meca_parts","category"=>"rsc_b","deco"=>0,"heavy"=>false],                      // -- ---
        ["label"=>"Huhn","icon"=>"pet_chick","category"=>"food_n","deco"=>0,"heavy"=>false],                                                // -- Out
        ["label"=>"Übelriechendes Schwein","icon"=>"pet_pig","category"=>"food_n","deco"=>0,"heavy"=>true],                                 // -- Out
        ["label"=>"Riesige Ratte","icon"=>"pet_rat","category"=>"food_n","deco"=>0,"heavy"=>false],                                         // -- Out
        ["label"=>"Bissiger Hund","icon"=>"pet_dog","category"=>"armor","deco"=>0,"heavy"=>false],                                          // -- Out
        ["label"=>"Großer knuddeliger Kater","icon"=>"pet_cat","category"=>"weapon","deco"=>5,"heavy"=>false],                              // -- Out
        ["label"=>"Zwei-Meter Schlange","icon"=>"pet_snake","category"=>"food_n","deco"=>0,"heavy"=>true],                                  // -- Out
        ["label"=>"Vibrator (geladen)","icon"=>"vibr","category"=>"imp","deco"=>0,"heavy"=>false],                                          // In ---
        ["label"=>"Anaboles Steroid","icon"=>"drug","category"=>"drug_d","deco"=>0,"heavy"=>false],                                         // In Out
        ["label"=>"Leckeres Steak","icon"=>"meat","category"=>"food_7","deco"=>0,"heavy"=>false],                                           // In Out
        ["label"=>"Undefinierbares Fleisch","icon"=>"undef","category"=>"food","deco"=>0,"heavy"=>false],                                   // In Out
        ["label"=>"Zeltplane","icon"=>"sheet","category"=>"camp","deco"=>0,"heavy"=>false],                                                 // -- ---
        ["label"=>"Superpraktischer Rucksack","icon"=>"bagxl","category"=>"exp_b","deco"=>0,"heavy"=>false],                                // -- ---
        ["label"=>"Kanister","icon"=>"jerrycan","category"=>"food_n","deco"=>0,"heavy"=>false],                                             // In ---
        ["label"=>"Krummes Holzbrett","icon"=>"wood2","category"=>"rsc_b","deco"=>0,"heavy"=>false],                                        // -- ---
        ["label"=>"Alteisen","icon"=>"metal","category"=>"rsc_b","deco"=>0,"heavy"=>false],                                                 // -- ---
        ["label"=>"Wasserbombe","icon"=>"grenade","category"=>"weapon","deco"=>0,"heavy"=>false],                                           // -- Out
        ["label"=>"Blechplatte","icon"=>"plate","category"=>"armor","deco"=>0,"heavy"=>true],                                               // -- ---
        ["label"=>"Kanisterpumpe (zerlegt)","icon"=>"jerrygun_part","category"=>"aud_n","deco"=>0,"heavy"=>false],                          // -- ---
        ["label"=>"Bandage","icon"=>"bandage","category"=>"drug","deco"=>0,"heavy"=>false],                                                 // In Out
        ["label"=>"Grüne Bierflasche","icon"=>"vodka_de","category"=>"food_a","deco"=>0,"heavy"=>false],                                    // In Out
        ["label"=>"Kanisterpumpe (leer)","icon"=>"jerrygun_off","category"=>"aud_n","deco"=>0,"heavy"=>false],                              // In Out
        ["label"=>"Videoprojektor","icon"=>"cinema","category"=>"furniture","deco"=>0,"heavy"=>true],                                       // -- ---
        ["label"=>"Sprengstoff","icon"=>"explo","category"=>"rsc","deco"=>0,"heavy"=>false],                                                // -- ---
        ["label"=>"Menschenfleisch","icon"=>"hmeat","category"=>"food_g","deco"=>0,"heavy"=>false],                                         // In Out
        ["label"=>"Plastiktüte","icon"=>"grenade_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                                      // In Out
        ["label"=>"Explodierende Wasserbombe","icon"=>"bgrenade","category"=>"weapon","deco"=>0,"heavy"=>false],                            // -- Out
        ["label"=>"Plastiktüte mit Sprengstoff","icon"=>"bgrenade_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                     // -- ---
        ["label"=>"Unvollständige Kettensäge","icon"=>"chainsaw_part","category"=>"aud_n","deco"=>0,"heavy"=>true],                         // -- ---
        ["label"=>"Unvollständiger Handmixer","icon"=>"mixergun_part","category"=>"aud_n","deco"=>0,"heavy"=>false],                        // -- ---
        ["label"=>"Klebeband","icon"=>"rustine","category"=>"rsc","deco"=>0,"heavy"=>false],                                                // -- ---
        ["label"=>"Zerlegter Rasenmäher","icon"=>"lawn_part","category"=>"aud_n","deco"=>0,"heavy"=>false],                                 // -- ---
        ["label"=>"Kupferrohr","icon"=>"tube","category"=>"rsc","deco"=>0,"heavy"=>false],                                                  // -- ---
        ["label"=>"Wackliger Einkaufswagen","icon"=>"cart_part","category"=>"imp","deco"=>0,"heavy"=>true],                                 // -- ---
        ["label"=>"Gürtel mit Tasche","icon"=>"pocket_belt","category"=>"exp_b","deco"=>0,"heavy"=>false],                                  // -- ---
        ["label"=>"Twinoid 500mg","icon"=>"drug_hero","category"=>"drug_d","deco"=>0,"heavy"=>false],                                       // In Out
        ["label"=>"Metallkiste","icon"=>"chest","category"=>"box","deco"=>0,"heavy"=>true],                                                 // In Out
        ["label"=>"Großer Metallkoffer","icon"=>"chest_xl","category"=>"box","deco"=>0,"heavy"=>true],                                      // In Out
        ["label"=>"Werkzeugkiste","icon"=>"chest_tools","category"=>"box","deco"=>0,"heavy"=>true],                                         // In Out
        ["label"=>"Nachttischlampe (an)","icon"=>"lamp_on","category"=>"furniture","deco"=>3,"heavy"=>false],                               // -- ---
        ["label"=>"Mini Hi-Fi Anlage (an)","icon"=>"music","category"=>"furniture","deco"=>10,"heavy"=>true],                               // -- ---
        ["label"=>"Pharmazeutische Substanz","icon"=>"pharma","category"=>"drug","deco"=>0,"heavy"=>false],                                 // -- ---
        ["label"=>"Unverarbeitete Blechplatten","icon"=>"plate_raw","category"=>"aud_n","deco"=>0,"heavy"=>true],                           // -- ---
        ["label"=>"'Wake The Dead'","icon"=>"rhum","category"=>"food_a","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Heißer Kaffee","icon"=>"coffee","category"=>"food","deco"=>0,"heavy"=>false],                                            // In Out
        ["label"=>"Kaffeekocher","icon"=>"coffee_machine","category"=>"imp","deco"=>5,"heavy"=>true],                                       // -- ---
        ["label"=>"Unvollständiger Kaffeekocher","icon"=>"coffee_machine_part","category"=>"imp","deco"=>0,"heavy"=>true],                  // -- ---
        ["label"=>"Elektronisches Bauteil","icon"=>"electro","category"=>"rsc","deco"=>0,"heavy"=>false],                                   // -- ---
        ["label"=>"Habseligkeiten eines Bürgers","icon"=>"chest_citizen","category"=>"box","deco"=>0,"heavy"=>true],                        // In Out
        ["label"=>"Hydraton 100mg","icon"=>"drug_water","category"=>"drug_d","deco"=>0,"heavy"=>false],                                     // In Out
        ["label"=>"Kassettenradio (ohne Strom)","icon"=>"radio_off","category"=>"furniture","deco"=>0,"heavy"=>false],                      // In Out
        ["label"=>"Kassettenradio","icon"=>"radio_on","category"=>"furniture","deco"=>2,"heavy"=>false],                                    // -- ---
        ["label"=>"Zyanid","icon"=>"cyanure","category"=>"drug","deco"=>0,"heavy"=>false],                                                  // In Out
        ["label"=>"Alte Tür","icon"=>"door","category"=>"armor","deco"=>0,"heavy"=>true],                                                   // -- ---
        ["label"=>"Verdächtiges Gemüse","icon"=>"vegetable","category"=>"food","deco"=>0,"heavy"=>false],                                   // In Out
        ["label"=>"Reparaturset (kaputt)","icon"=>"repair_kit_part","category"=>"imp","deco"=>0,"heavy"=>false],                            // -- ---
        ["label"=>"Reparaturset","icon"=>"repair_kit","category"=>"imp","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Wasserpistole (leer)","icon"=>"watergun_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                            // In Out
        ["label"=>"Aqua-Splash (3 Ladungen)","icon"=>"watergun_opt_3","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Aqua-Splash (2 Ladungen)","icon"=>"watergun_opt_2","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Aqua-Splash (1 Ladung)","icon"=>"watergun_opt_1","category"=>"weapon","deco"=>0,"heavy"=>false],                         // -- Out
        ["label"=>"Handmixer (ohne Strom)","icon"=>"mixergun_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                          // In Out
        ["label"=>"Kettensäge (ohne Strom)","icon"=>"chainsaw_empty","category"=>"aud_n","deco"=>0,"heavy"=>true],                          // In Out
        ["label"=>"Batteriewerfer 1-PDTG (entladen)","icon"=>"pilegun_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                 // In Out
        ["label"=>"Taser (ohne Strom)","icon"=>"taser_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Elektrischer Bauchmuskeltrainer (ohne Strom)","icon"=>"sport_elec_empty","category"=>"imp","deco"=>0,"heavy"=>false],    // In Out
        ["label"=>"Elektrischer Bauchmuskeltrainer (geladen)","icon"=>"sport_elec","category"=>"imp","deco"=>0,"heavy"=>false],             // In Out
        ["label"=>"Zerstörer (entladen)","icon"=>"big_pgun_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                            // In Out
        ["label"=>"Zerstörer (geladen)","icon"=>"big_pgun","category"=>"weapon","deco"=>0,"heavy"=>false],                                  // -- Out
        ["label"=>"Unvollständiger Zerstörer","icon"=>"big_pgun_part","category"=>"aud_n","deco"=>0,"heavy"=>false],                        // -- ---
        ["label"=>"Zonenmarker 'Radius'","icon"=>"tagger","category"=>"exp_s","deco"=>0,"heavy"=>false],                                    // -- Out
        ["label"=>"Leuchtrakete","icon"=>"flare","category"=>"misc","deco"=>0,"heavy"=>false],                                              // --
        ["label"=>"Kanisterpumpe (einsatzbereit)","icon"=>"jerrygun","category"=>"weapon","deco"=>0,"heavy"=>false],                        // -- Out
        ["label"=>"Ektorp-Gluten Stuhl","icon"=>"chair_basic","category"=>"furniture","deco"=>2,"heavy"=>true],                             // -- Out
        ["label"=>"Revolver (entladen)","icon"=>"gun","category"=>"furniture","deco"=>5,"heavy"=>false],                                    // -- ---
        ["label"=>"Sturmgewehr (entladen)","icon"=>"machine_gun","category"=>"furniture","deco"=>15,"heavy"=>false],                        // -- ---
        ["label"=>"Zünder","icon"=>"deto","category"=>"rsc","deco"=>0,"heavy"=>false],                                                      // -- ---
        ["label"=>"Zementsack","icon"=>"concrete","category"=>"imp","deco"=>0,"heavy"=>true],                                               // -- ---
        ["label"=>"Unförmige Zementblöcke","icon"=>"concrete_wall","category"=>"armor","deco"=>0,"heavy"=>true],                            // -- Out
        ["label"=>"Etikettenloses Medikament","icon"=>"drug_random","category"=>"drug_d","deco"=>0,"heavy"=>false],                         // In Out
        ["label"=>"Paracetoid 7g","icon"=>"disinfect","category"=>"drug_d","deco"=>0,"heavy"=>false],                                       // In Out
        ["label"=>"Unkrautbekämpfungsmittel Ness-Quick","icon"=>"digger","category"=>"exp_s","deco"=>0,"heavy"=>false],                     // -- Out
        ["label"=>"Nahrungsmittelkiste","icon"=>"chest_food","category"=>"box","deco"=>0,"heavy"=>true],                                    // In Out
        ["label"=>"Doggybag","icon"=>"food_bag","category"=>"food","deco"=>0,"heavy"=>false],                                               // In Out
        ["label"=>"Tüte mit labbrigen Chips","icon"=>"food_bar1","category"=>"food","deco"=>0,"heavy"=>false],                              // In Out
        ["label"=>"Verschimmelte Waffeln","icon"=>"food_bar2","category"=>"food","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Trockene Kaugummis","icon"=>"food_bar3","category"=>"food","deco"=>0,"heavy"=>false],                                    // In Out
        ["label"=>"Ranzige Butterkekse","icon"=>"food_biscuit","category"=>"food","deco"=>0,"heavy"=>false],                                // In Out
        ["label"=>"Angebissene Hähnchenflügel","icon"=>"food_chick","category"=>"food","deco"=>0,"heavy"=>false],                           // In Out
        ["label"=>"Abgelaufene Pim's Kekse","icon"=>"food_pims","category"=>"food","deco"=>0,"heavy"=>false],                               // In Out
        ["label"=>"Fades Gebäck","icon"=>"food_tarte","category"=>"food","deco"=>0,"heavy"=>false],                                         // In Out
        ["label"=>"Verschimmelte Stulle","icon"=>"food_sandw","category"=>"food","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Chinesische Nudeln","icon"=>"food_noodles","category"=>"food","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Starke Gewürze","icon"=>"spices","category"=>"misc","deco"=>0,"heavy"=>false],                                           // -- ---
        ["label"=>"Gewürzte chinesische Nudeln","icon"=>"food_noodles_hot","category"=>"food_7","deco"=>0,"heavy"=>false],                  // In Out
        ["label"=>"Unvollständiges Kartenspiel","icon"=>"cards","category"=>"imp","deco"=>0,"heavy"=>false],                                // In Out
        ["label"=>"Gesellschaftsspiel","icon"=>"game_box","category"=>"imp","deco"=>0,"heavy"=>false],                                      // In Out
        ["label"=>"Aqua-Splash (zerlegt)","icon"=>"watergun_opt_part","category"=>"aud_n","deco"=>0,"heavy"=>false],                        // -- ---
        ["label"=>"Vibrator (entladen)","icon"=>"vibr_empty","category"=>"imp","deco"=>0,"heavy"=>false],                                   // In Out
        ["label"=>"Knochen mit Fleisch","icon"=>"bone_meat","category"=>"food_g","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Angeknackster menschlicher Knochen","icon"=>"bone","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Zusammengeschusterter Holzbalken","icon"=>"wood_beam","category"=>"rsc_b","deco"=>0,"heavy"=>true],                      // -- ---
        ["label"=>"Metallstruktur","icon"=>"metal_beam","category"=>"rsc_b","deco"=>0,"heavy"=>true],                                       // -- ---
        ["label"=>"Metalltrümmer","icon"=>"metal_bad","category"=>"rsc_b","deco"=>0,"heavy"=>false],                                        // -- ---
        ["label"=>"Verrotteter Baumstumpf","icon"=>"wood_bad","category"=>"rsc_b","deco"=>0,"heavy"=>false],                                // -- ---
        ["label"=>"Metallsäge","icon"=>"saw_tool","category"=>"imp","deco"=>0,"heavy"=>false],                                              // -- ---
        ["label"=>"Gut erhaltener Holzscheit","icon"=>"wood_log","category"=>"rsc_b","deco"=>2,"heavy"=>true],                              // -- ---
        ["label"=>"Defektes Elektrogerät","icon"=>"electro_box","category"=>"misc","deco"=>0,"heavy"=>false],                               // -- ---
        ["label"=>"Möbelpackung","icon"=>"deco_box","category"=>"box","deco"=>0,"heavy"=>true],                                             // -- ---
        ["label"=>"Beschädigte Metallsäge","icon"=>"saw_tool_part","category"=>"imp","deco"=>0,"heavy"=>false],                             // -- ---
        ["label"=>"Getriebe","icon"=>"mecanism","category"=>"misc","deco"=>0,"heavy"=>false],                                               // -- ---
        ["label"=>"Holzbock","icon"=>"trestle","category"=>"armor","deco"=>1,"heavy"=>true],                                                // -- ---
        ["label"=>"Järpen-Tisch","icon"=>"table","category"=>"armor","deco"=>3,"heavy"=>true],                                              // -- ---
        ["label"=>"Micropur Brausetablette","icon"=>"water_cleaner","category"=>"drug","deco"=>0,"heavy"=>false],                           // -- ---
        ["label"=>"Darmmelone","icon"=>"vegetable_tasty","category"=>"food_7","deco"=>0,"heavy"=>false],                                    // In Out
        ["label"=>"Raketenpulver","icon"=>"powder","category"=>"rsc","deco"=>0,"heavy"=>false],                                             // -- ---
        ["label"=>"Schießpulverbombe","icon"=>"flash","category"=>"exp_s","deco"=>0,"heavy"=>false],                                        // -- Out
        ["label"=>"Teddybär","icon"=>"teddy","category"=>"furniture","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Holzkistendeckel","icon"=>"wood_plate_part","category"=>"aud_n","deco"=>0,"heavy"=>true],                                // -- ---
        ["label"=>"Solide Holzplatte","icon"=>"wood_plate","category"=>"armor","deco"=>0,"heavy"=>true],                                    // -- ---
        ["label"=>"Geldbündel","icon"=>"money","category"=>"furniture","deco"=>7,"heavy"=>false],                                           // -- ---
        ["label"=>"Loses Werkzeug","icon"=>"repair_kit_part_raw","category"=>"misc","deco"=>0,"heavy"=>false],                              // -- ---
        ["label"=>"Radius Mark II (entladen)","icon"=>"radius_mk2_part","category"=>"exp_s","deco"=>0,"heavy"=>false],                      // -- ---
        ["label"=>"Radius Mark II","icon"=>"radius_mk2","category"=>"exp_s","deco"=>0,"heavy"=>false],                                      // -- Out
        ["label"=>"Reparatur Fix","icon"=>"repair_one","category"=>"imp","deco"=>0,"heavy"=>false],                                         // In Out
        ["label"=>"Unvollständiger Motor","icon"=>"engine_part","category"=>"imp","deco"=>0,"heavy"=>true],                                 // -- ---
        ["label"=>"Alte Waschmaschine","icon"=>"machine_1","category"=>"imp","deco"=>2,"heavy"=>true],                                      // -- Out
        ["label"=>"Krebserregender Ofen","icon"=>"machine_2","category"=>"imp","deco"=>2,"heavy"=>true],                                    // -- Out
        ["label"=>"Minibar","icon"=>"machine_3","category"=>"imp","deco"=>2,"heavy"=>true],                                                 // -- Out
        ["label"=>"Ein Brief ohne Adresse","icon"=>"rp_letter","category"=>"misc","deco"=>0,"heavy"=>false],                                // In Out
        ["label"=>"Aufgewelltes Blatt","icon"=>"rp_scroll","category"=>"imp","deco"=>0,"heavy"=>false],                                     // In Out
        ["label"=>"Betriebsanleitung","icon"=>"rp_manual","category"=>"misc","deco"=>0,"heavy"=>false],                                     // In Out
        ["label"=>"Unleserliches Notizbuch","icon"=>"rp_book2","category"=>"imp","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Fotoalbum","icon"=>"rp_book","category"=>"misc","deco"=>0,"heavy"=>false],                                               // In Out
        ["label"=>"Blätterstapel","icon"=>"rp_sheets","category"=>"imp","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Große rostige Kette","icon"=>"chain","category"=>"imp","deco"=>0,"heavy"=>false],                                        // -- Out
        ["label"=>"Verdächtige Speise","icon"=>"dish","category"=>"food","deco"=>0,"heavy"=>false],                                         // In Out
        ["label"=>"Leckere Speise","icon"=>"dish_tasty","category"=>"food_7","deco"=>0,"heavy"=>false],                                     // In Out
        ["label"=>"Schrankkoffer","icon"=>"home_box_xl","category"=>"furniture","deco"=>0,"heavy"=>true],                                   // In ---
        ["label"=>"Kartons","icon"=>"home_box","category"=>"furniture","deco"=>0,"heavy"=>false],                                           // In ---
        ["label"=>"Nagelbare Barrikade","icon"=>"home_def","category"=>"furniture","deco"=>0,"heavy"=>true],                                // In ---
        ["label"=>"Ein Briefumschlag","icon"=>"book_gen_letter","category"=>"imp","deco"=>0,"heavy"=>false],                                // In Out
        ["label"=>"Ein Paket","icon"=>"book_gen_box","category"=>"box","deco"=>0,"heavy"=>false],                                           // In Out
        ["label"=>"Maschendrahtzaunstück","icon"=>"fence","category"=>"rsc","deco"=>0,"heavy"=>false],                                      // -- ---
        ["label"=>"Wasserpistole (3 Ladungen)","icon"=>"watergun_3","category"=>"weapon","deco"=>0,"heavy"=>false],                         // -- Out
        ["label"=>"Wasserpistole (2 Ladungen)","icon"=>"watergun_2","category"=>"weapon","deco"=>0,"heavy"=>false],                         // -- Out
        ["label"=>"Wasserpistole (1 Ladung)","icon"=>"watergun_1","category"=>"weapon","deco"=>0,"heavy"=>false],                           // -- Out
        ["label"=>"Aqua-Splash (5 Ladungen)","icon"=>"watergun_opt_5","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Aqua-Splash (4 Ladungen)","icon"=>"watergun_opt_4","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Angefangene Zigarettenschachtel","icon"=>"cigs","category"=>"misc","deco"=>0,"heavy"=>false],                            //
        ["label"=>"Druckregler PDTT Mark II","icon"=>"pilegun_upkit","category"=>"misc","deco"=>0,"heavy"=>false],                          // -- ---
        ["label"=>"Batteriewerfer Mark II (leer)","icon"=>"pilegun_up_empty","category"=>"aud_n","deco"=>0,"heavy"=>false],                 // In Out
        ["label"=>"Batteriewerfer Mark II (geladen)","icon"=>"pilegun_up","category"=>"weapon","deco"=>0,"heavy"=>false],                   // -- Out
        ["label"=>"Zerquetschte Batterie","icon"=>"pile_broken","category"=>"misc","deco"=>0,"heavy"=>false],                               // -- ---
        ["label"=>"Kiste mit Materialien (3)","icon"=>"rsc_pack_3","category"=>"box","deco"=>0,"heavy"=>true],                              // In Out
        ["label"=>"Kiste mit Materialien (2)","icon"=>"rsc_pack_2","category"=>"box","deco"=>0,"heavy"=>true],                              // In Out
        ["label"=>"Kiste mit Materialien (1)","icon"=>"rsc_pack_1","category"=>"box","deco"=>0,"heavy"=>true],                              // In Out
        ["label"=>"Autotür","icon"=>"car_door","category"=>"armor","deco"=>0,"heavy"=>true],                                                // -- ---
        ["label"=>"Beschädigte Autotür","icon"=>"car_door_part","category"=>"aud_n","deco"=>0,"heavy"=>true],                               // -- ---
        ["label"=>"Giftfläschchen","icon"=>"poison","category"=>"imp","deco"=>0,"heavy"=>false],                                            // In Out
        ["label"=>"Ätzmittel","icon"=>"poison_part","category"=>"imp","deco"=>0,"heavy"=>false],                                            // -- ---
        ["label"=>"Vorräte eines umsichtigen Bürgers","icon"=>"chest_hero","category"=>"box","deco"=>0,"heavy"=>true],                      // In Out
        ["label"=>"Postpaket","icon"=>"postal_box","category"=>"box","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Lunch-Box","icon"=>"food_armag","category"=>"food_7","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Eine Handvoll Bonbons","icon"=>"food_candies","category"=>"food_7","deco"=>0,"heavy"=>false],                            // In Out
        ["label"=>"Sperrholzstück","icon"=>"out_def","category"=>"camp","deco"=>0,"heavy"=>false],                                          // -- ---
        ["label"=>"Fackel","icon"=>"torch","category"=>"armor","deco"=>0,"heavy"=>false],                                                   // -- Out
        ["label"=>"Verbrauchte Fackel","icon"=>"torch_off","category"=>"weapon","deco"=>0,"heavy"=>false],                                  // -- Out
        ["label"=>"Getrocknete Marshmallows","icon"=>"chama","category"=>"food_n","deco"=>0,"heavy"=>false],                                // -- ---
        ["label"=>"Geröstete Marshmallows","icon"=>"chama_tasty","category"=>"food_7","deco"=>0,"heavy"=>false],                            // In Out
        ["label"=>"PC-Gehäuse","icon"=>"pc","category"=>"weapon","deco"=>3,"heavy"=>true],                                                  // -- Out
        ["label"=>"Safe","icon"=>"safe","category"=>"box","deco"=>0,"heavy"=>true],                                                         // In Out
        ["label"=>"Eine Enzyklopädie","icon"=>"rp_twin","category"=>"misc","deco"=>0,"heavy"=>false],                                       // In Out
        ["label"=>"Wasserspender (leer)","icon"=>"water_can_empty","category"=>"exp_s","deco"=>0,"heavy"=>true],                            // -- ---
        ["label"=>"Wasserspender (1 Ration)","icon"=>"water_can_1","category"=>"food","deco"=>0,"heavy"=>true],                             // In Out
        ["label"=>"Wasserspender (2 Rationen)","icon"=>"water_can_2","category"=>"food","deco"=>0,"heavy"=>true],                           // In Out
        ["label"=>"Wasserspender (3 Rationen)","icon"=>"water_can_3","category"=>"food","deco"=>0,"heavy"=>true],                           // In Out
        ["label"=>"Abgelaufene Betapropin-Tablette 5mg","icon"=>"beta_drug_bad","category"=>"drug_d","deco"=>0,"heavy"=>false],             // In Out
        ["label"=>"Betapropin-Tablette 5mg","icon"=>"beta_drug","category"=>"drug_d","deco"=>0,"heavy"=>false],                             // In Out
        ["label"=>"Aasbeeren","icon"=>"fruit_sub_part","category"=>"misc","deco"=>0,"heavy"=>false],                                        // -- ---
        ["label"=>"Schleimige Kugel","icon"=>"fruit_part","category"=>"misc","deco"=>0,"heavy"=>false],                                     // -- ---
        ["label"=>"Fleischfetzen","icon"=>"flesh_part","category"=>"misc","deco"=>0,"heavy"=>false],                                        // -- ---
        ["label"=>"Makabre Bombe","icon"=>"flesh","category"=>"exp_s","deco"=>0,"heavy"=>false],                                            // -- Out
        ["label"=>"Dickflüssige Substanz","icon"=>"pharma_part","category"=>"drug","deco"=>0,"heavy"=>false],                               // -- ---
        ["label"=>"Aasbeerenbrei","icon"=>"fruit","category"=>"food","deco"=>0,"heavy"=>false],                                             // In Out
        ["label"=>"Eisengefäß mit modrigem wasser","icon"=>"water_cup_part","category"=>"food_n","deco"=>0,"heavy"=>false],                 // In Out
        ["label"=>"Gereinigtes modriges Wasser","icon"=>"water_cup","category"=>"food","deco"=>0,"heavy"=>false],                           // In Out
        ["label"=>"Notizzettel eines Verbannten","icon"=>"banned_note","category"=>"exp_s","deco"=>0,"heavy"=>false],                       //
        ["label"=>"Blutdurchtränkter Verband","icon"=>"infect_poison_part","category"=>"drug","deco"=>0,"heavy"=>false],                    // -- ---
        ["label"=>"Verfluchter Teddybär","icon"=>"teddy","category"=>"furniture","deco"=>0,"heavy"=>false],                                 // In Out
        ["label"=>"Sägemehlsteak","icon"=>"woodsteak","category"=>"food_7","deco"=>0,"heavy"=>false],                                       // In Out
        ["label"=>"Abgetragene rote Jacke","icon"=>"christmas_suit_1","category"=>"misc","deco"=>0,"heavy"=>false],                         // -- ---
        ["label"=>"Zerrissene rote Hose","icon"=>"christmas_suit_2","category"=>"misc","deco"=>0,"heavy"=>false],                           // -- ---
        ["label"=>"Schweißtriefende rote Mütze","icon"=>"christmas_suit_3","category"=>"misc","deco"=>0,"heavy"=>false],                    // -- ---
        ["label"=>"Übelriechender Anzug aus einer anderen Zeit","icon"=>"christmas_suit_full","category"=>"misc","deco"=>0,"heavy"=>false], //
        ["label"=>"Mobiltelefon","icon"=>"iphone","category"=>"weapon","deco"=>0,"heavy"=>false],                                           // -- Out
        ["label"=>"Ekliger Hautfetzen","icon"=>"smelly_meat","category"=>"camp","deco"=>0,"heavy"=>false],                                  // -- ---
        ["label"=>"MagLite Kinderlampe (aus)","icon"=>"maglite_off","category"=>"furniture","deco"=>5,"heavy"=>false],                      // In Out
        ["label"=>"MagLite Kinderlampe (1 Ladung)","icon"=>"maglite_1","category"=>"exp_s","deco"=>5,"heavy"=>false],                       // -- ---
        ["label"=>"MagLite Kinderlampe (2 Ladungen)","icon"=>"maglite_2","category"=>"exp_s","deco"=>5,"heavy"=>false],                     // -- ---
        ["label"=>"Leiche eines Reisenden","icon"=>"cadaver","category"=>"food_g","deco"=>0,"heavy"=>true],                                 // In Out
        ["label"=>"Angenagte Leiche","icon"=>"cadaver_remains","category"=>"misc","deco"=>0,"heavy"=>true],                                 // -- ---
        ["label"=>"Rauchgranate 'Tannenduft'","icon"=>"smoke_bomb","category"=>"misc","deco"=>0,"heavy"=>false],                            // --
        ["label"=>"Sandball","icon"=>"sand_ball","category"=>"misc","deco"=>0,"heavy"=>false],                                              //
        ["label"=>"Normaler Bauplan (gewöhnlich)","icon"=>"bplan_c","category"=>"imp","deco"=>0,"heavy"=>false],                            // In ---
        ["label"=>"Normaler Bauplan (ungewöhnlich)","icon"=>"bplan_u","category"=>"imp","deco"=>0,"heavy"=>false],                          // In ---
        ["label"=>"Normaler Bauplan (selten)","icon"=>"bplan_r","category"=>"imp","deco"=>0,"heavy"=>false],                                // In ---
        ["label"=>"Normaler Bauplan (sehr selten!)","icon"=>"bplan_e","category"=>"imp","deco"=>0,"heavy"=>false],                          // In ---
        ["label"=>"Architektenkoffer","icon"=>"bplan_box","category"=>"box","deco"=>0,"heavy"=>true],                                       // In Out
        ["label"=>"Versiegelter Architektenkoffer","icon"=>"bplan_box_e","category"=>"box","deco"=>0,"heavy"=>true],                        // In Out
        ["label"=>"Ei","icon"=>"egg","category"=>"food_7","deco"=>0,"heavy"=>false],                                                        // In Out
        ["label"=>"Apfel","icon"=>"apple","category"=>"food_7","deco"=>0,"heavy"=>false],                                                   // In Out
        ["label"=>"Explosive Pampelmuse","icon"=>"boomfruit","category"=>"weapon","deco"=>0,"heavy"=>false],                                // -- Out
        ["label"=>"Abgenutzte Kuriertasche","icon"=>"bplan_drop","category"=>"box","deco"=>0,"heavy"=>false],                               // In Out
        ["label"=>"Magnet-Schlüssel","icon"=>"magneticKey","category"=>"exp_s","deco"=>0,"heavy"=>false],                                   // --
        ["label"=>"Schlagschlüssel","icon"=>"bumpKey","category"=>"exp_s","deco"=>0,"heavy"=>false],                                        // --
        ["label"=>"Dosenöffner (Schlüssel)","icon"=>"classicKey","category"=>"exp_s","deco"=>0,"heavy"=>false],                             // --
        ["label"=>"Abdruck vom Magnet-Schlüssel","icon"=>"prints","category"=>"exp_s","deco"=>0,"heavy"=>false],                            //
        ["label"=>"Abdruck vom Schlagschlüssel","icon"=>"prints","category"=>"exp_s","deco"=>0,"heavy"=>false],                             //
        ["label"=>"Abdruck vom Dosenöffner","icon"=>"prints","category"=>"exp_s","deco"=>0,"heavy"=>false],                                 //
        ["label"=>"Ghul-Serum","icon"=>"vagoul","category"=>"drug_d","deco"=>0,"heavy"=>false],                                             //
        ["label"=>"Hotel-Bauplan (ungewöhnlich)","icon"=>"hbplan_u","category"=>"imp","deco"=>0,"heavy"=>false],                            // In ---
        ["label"=>"Hotel-Bauplan (selten)","icon"=>"hbplan_r","category"=>"imp","deco"=>0,"heavy"=>false],                                  // In ---
        ["label"=>"Hotel-Bauplan (sehr selten!)","icon"=>"hbplan_e","category"=>"imp","deco"=>0,"heavy"=>false],                            // In ---
        ["label"=>"Bunker-Bauplan (ungewöhnlich)","icon"=>"bbplan_u","category"=>"imp","deco"=>0,"heavy"=>false],                           // In ---
        ["label"=>"Bunker-Bauplan (selten)","icon"=>"bbplan_r","category"=>"imp","deco"=>0,"heavy"=>false],                                 // In ---
        ["label"=>"Bunker-Bauplan (sehr selten!)","icon"=>"bbplan_e","category"=>"imp","deco"=>0,"heavy"=>false],                           // In ---
        ["label"=>"Hospital-Bauplan (ungewöhnlich)","icon"=>"mbplan_u","category"=>"imp","deco"=>0,"heavy"=>false],                         // In ---
        ["label"=>"Hospital-Bauplan (selten)","icon"=>"mbplan_r","category"=>"imp","deco"=>0,"heavy"=>false],                               // In ---
        ["label"=>"Hospital-Bauplan (sehr selten!)","icon"=>"mbplan_e","category"=>"imp","deco"=>0,"heavy"=>false],                         // In ---
        ["label"=>"Verirrte Seele","icon"=>"soul_blue","category"=>"imp","deco"=>0,"heavy"=>false],                                         //
        ["label"=>"Starke Seele","icon"=>"soul_red","category"=>"imp","deco"=>0,"heavy"=>false],                                            //
        ["label"=>"Schwache Seele","icon"=>"soul_blue","category"=>"imp","deco"=>0,"heavy"=>false],                                         //
        ["label"=>"Bierkrug","icon"=>"fest","category"=>"food_a","deco"=>0,"heavy"=>false],                                                 // In Out
        ["label"=>"Brezel","icon"=>"bretz","category"=>"food","deco"=>0,"heavy"=>false],                                                    // In Out
        ["label"=>"Dackel","icon"=>"tekel","category"=>"weapon","deco"=>0,"heavy"=>false],                                                  // -- Out
        ["label"=>"Pfahlwerfer","icon"=>"rlaunc","category"=>"weapon","deco"=>0,"heavy"=>false],                                            // --
        ["label"=>"Kalaschni-Splash","icon"=>"kalach","category"=>"weapon","deco"=>0,"heavy"=>true],                                        // -- ---
        ["label"=>"Schnellgebauter Tisch","icon"=>"bureau","category"=>"furniture","deco"=>0,"heavy"=>true],                                // -- ---
        ["label"=>"Leerer Automat","icon"=>"distri","category"=>"furniture","deco"=>0,"heavy"=>true],                                       // -- ---
        ["label"=>"Santas Rentier","icon"=>"renne","category"=>"misc","deco"=>0,"heavy"=>true],                                             // -- ---
        ["label"=>"Osterei","icon"=>"paques","category"=>"misc","deco"=>0,"heavy"=>false],                                                  // -- ---
        ["label"=>"ANZAC Badge","icon"=>"badge","category"=>"armor","deco"=>0,"heavy"=>false],                                              // -- ---
        ["label"=>"Kalaschni-Splash (leer)","icon"=>"kalach","category"=>"weapon","deco"=>0,"heavy"=>false],                                // In Out
        ["label"=>"Drahtspule","icon"=>"wire","category"=>"rsc","deco"=>0,"heavy"=>false],                                                  // -- ---
        ["label"=>"Ölkännchen","icon"=>"oilcan","category"=>"rsc","deco"=>0,"heavy"=>false],                                                // -- ---
        ["label"=>"Konvexlinse","icon"=>"lens","category"=>"rsc","deco"=>0,"heavy"=>false],                                                 // -- ---
        ["label"=>"Wütende Mieze (halb verdaut)","icon"=>"angryc","category"=>"weapon","deco"=>0,"heavy"=>false],                           // --
        ["label"=>"Tretmine","icon"=>"claymo","category"=>"weapon","deco"=>0,"heavy"=>false],                                               // -- ---
        ["label"=>"Laserdiode","icon"=>"diode","category"=>"rsc","deco"=>0,"heavy"=>false],                                                 // -- ---
        ["label"=>"Selbstgebaute Gitarre","icon"=>"guitar","category"=>"imp","deco"=>0,"heavy"=>false],                                     // In ---
        ["label"=>"LSD","icon"=>"lsd","category"=>"drug","deco"=>0,"heavy"=>false],                                                         //
        ["label"=>"Starker Laserpointer (4 Schuss)","icon"=>"lpoint4","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Starker Laserpointer (3 Schuss)","icon"=>"lpoint3","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Starker Laserpointer (2 Schuss)","icon"=>"lpoint2","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Starker Laserpointer (1 Schuss)","icon"=>"lpoint1","category"=>"weapon","deco"=>0,"heavy"=>false],                       // -- Out
        ["label"=>"Starker Laserpointer (Leer)","icon"=>"lpoint","category"=>"weapon","deco"=>0,"heavy"=>false],                            // In Out
        ["label"=>"Teleskop","icon"=>"scope","category"=>"imp","deco"=>0,"heavy"=>false],                                                   // -- ---
        ["label"=>"Unpersönliche Explodierende Fußmatte","icon"=>"trapma","category"=>"furniture","deco"=>0,"heavy"=>false],                // -- ---
        ["label"=>"Chuck-Figur","icon"=>"chudol","category"=>"furniture","deco"=>15,"heavy"=>false],                                        // -- ---
        ["label"=>"Kleine Zen-Fibel","icon"=>"lilboo","category"=>"imp","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Trockene Kräuter","icon"=>"ryebag","category"=>"rsc","deco"=>0,"heavy"=>false],                                          // -- ---
        ["label"=>"Mutterkorn","icon"=>"fungus","category"=>"food","deco"=>0,"heavy"=>false],                                               // -- ---
        ["label"=>"Korn-Bräu","icon"=>"hmbrew","category"=>"food","deco"=>0,"heavy"=>false],                                                // In Out
        ["label"=>"Verfluchte HiFi","icon"=>"hifiev","category"=>"furniture","deco"=>0,"heavy"=>true],                                      // -- ---
        ["label"=>"Phil Collins CD","icon"=>"cdphil","category"=>"furniture","deco"=>1,"heavy"=>false],                                     // -- ---
        ["label"=>"Ohrstöpsel","icon"=>"bquies","category"=>"rsc","deco"=>0,"heavy"=>false],                                                // -- ---
        ["label"=>"Kaputter Stock","icon"=>"staff","category"=>"rsc","deco"=>0,"heavy"=>false],                                             // -- ---
        ["label"=>"Britney Spears CD","icon"=>"cdbrit","category"=>"furniture","deco"=>3,"heavy"=>false],                                   // -- ---
        ["label"=>"Best of The King CD","icon"=>"cdelvi","category"=>"furniture","deco"=>7,"heavy"=>false],                                 // -- ---
        ["label"=>"Rock n Roll HiFi","icon"=>"dfhifi","category"=>"furniture","deco"=>0,"heavy"=>true],                                     // -- ---
        ["label"=>"Verteidigende HiFi","icon"=>"dfhifi","category"=>"furniture","deco"=>0,"heavy"=>true],                                   // -- ---
        ["label"=>"Schrödingers Box","icon"=>"catbox","category"=>"box","deco"=>0,"heavy"=>false],                                          // In Out
        ["label"=>"Geistiger Beistand","icon"=>"chkspk","category"=>"misc","deco"=>0,"heavy"=>false],                                       // -- ---
        ["label"=>"Fette Python","icon"=>"pet_snake2","category"=>"misc","deco"=>0,"heavy"=>true],                                          // -- ---
        ["label"=>"Überraschungskiste (3 Geschenke)","icon"=>"chest_christmas_3","category"=>"misc","deco"=>0,"heavy"=>true],               // In Out
        ["label"=>"Überraschungskiste (2 Geschenke)","icon"=>"chest_christmas_2","category"=>"misc","deco"=>0,"heavy"=>true],               // In Out
        ["label"=>"Überraschungskiste (1 Geschenk)","icon"=>"chest_christmas_1","category"=>"misc","deco"=>0,"heavy"=>true],                // In Out
        ["label"=>"Verdächtiger Traubensaft","icon"=>"omg_this_will_kill_you","category"=>"food","deco"=>0,"heavy"=>false],                 //
        ["label"=>"Ein Etikett","icon"=>"rp_scroll","category"=>"imp","deco"=>0,"heavy"=>false],                                            // In Out


        ["label"=>"Bürgerbekleidung","icon"=>"basic_suit","category"=>"misc","deco"=>0,"heavy"=>false],                                     // -- ---
        ["label"=>"Dreckige Bürgerbekleidung","icon"=>"basic_suit_dirt","category"=>"misc","deco"=>0,"heavy"=>false],                       // -- ---
        ["label"=>"Tarnanzug","icon"=>"vest_on","category"=>"misc","deco"=>0,"heavy"=>false],                                               // -- ---
        ["label"=>"Tarnanzug (abgelegt)","icon"=>"vest_off","category"=>"misc","deco"=>0,"heavy"=>false],                                   //
        ["label"=>"Kleine Schaufel","icon"=>"pelle","category"=>"misc","deco"=>0,"heavy"=>false],                                           // -- ---
        ["label"=>"Dreibeiniger Malteser","icon"=>"tamed_pet","category"=>"misc","deco"=>0,"heavy"=>false],                                 // --
        ["label"=>"Dreibeiniger Malteser (gedopt)","icon"=>"tamed_pet_drug","category"=>"misc","deco"=>0,"heavy"=>false],                   // --
        ["label"=>"Dreibeiniger Malteser (erschöpft)","icon"=>"tamed_pet_off","category"=>"misc","deco"=>0,"heavy"=>false],                 // -- ---
        ["label"=>"Survivalbuch","icon"=>"surv_book","category"=>"misc","deco"=>0,"heavy"=>false],                                          // --
        ["label"=>"Schraubenschlüssel","icon"=>"keymol","category"=>"misc","deco"=>0,"heavy"=>false],                                       // -- ---
        ["label"=>"Schutzschild","icon"=>"shield","category"=>"misc","deco"=>0,"heavy"=>false],                                             // -- ---
        ["label"=>"Voodoo-Maske","icon"=>"shaman","category"=>"misc","deco"=>0,"heavy"=>false],                                             // -- ---
    ];
    public static $item_prototype_properties = [
        'saw_tool_#00'     => [ 'can_opener' ],
        'can_opener_#00'   => [ 'impoundable', 'weapon', 'can_opener', 'box_opener' ],
        'screw_#00'        => [ 'impoundable', 'weapon', 'can_opener', 'box_opener' ],
        'swiss_knife_#00'  => [ 'impoundable', 'weapon', 'can_opener', 'box_opener' ],
        'wrench_#00'       => [ 'impoundable', 'weapon', 'box_opener' ],
        'cutter_#00'       => [ 'impoundable', 'weapon', 'box_opener' ],
        'small_knife_#00'  => [ 'impoundable', 'weapon', 'box_opener' ],
        'bone_#00'         => [ 'impoundable', 'weapon', 'box_opener' ],
        'cutcut_#00'       => [ 'impoundable', 'weapon', 'box_opener' ],
        'chair_basic_#00'  => [ 'box_opener' ],
        'staff_#00'        => [ 'impoundable', 'weapon', 'box_opener' ],
        'chain_#00'        => [ 'impoundable', 'weapon', 'box_opener' ],
        'pc_#00'           => [ 'box_opener' ],
        'door_#00'         => [ 'impoundable', 'defence' ],
        'car_door_#00'     => [ 'impoundable', 'defence' ],
        'pet_dog_#00'      => [ 'impoundable', 'defence' ],
        'plate_#00'        => [ 'impoundable', 'defence' ],
        'tekel_#00'        => [ 'impoundable', 'defence', 'lock' ],
        'torch_#00'        => [ 'impoundable', 'defence', 'weapon' ],
        'trestle_#00'      => [ 'impoundable', 'defence' ],
        'table_#00'        => [ 'impoundable', 'defence' ],
        'bed_#00'          => [ 'impoundable', 'defence' ],
        'wood_plate_#00'   => [ 'impoundable', 'defence' ],
        'concrete_wall_#00'=> [ 'impoundable', 'defence' ],
        'wood_bad_#00'     => [ 'impoundable', 'ressource' ],
        'metal_bad_#00'    => [ 'impoundable', 'ressource' ],
        'wood2_#00'        => [ 'impoundable', 'ressource', 'hero_find' ],
        'metal_#00'        => [ 'impoundable', 'ressource', 'hero_find' ],
        'wood_beam_#00'    => [ 'impoundable', 'ressource' ],
        'metal_beam_#00'   => [ 'impoundable', 'ressource' ],
        'courroie_#00'     => [ 'impoundable', 'ressource' ],
        'deto_#00'         => [ 'impoundable', 'ressource' ],
        'tube_#00'         => [ 'impoundable', 'ressource' ],
        'rustine_#00'      => [ 'impoundable', 'ressource' ],
        'electro_#00'      => [ 'impoundable', 'ressource' ],
        'meca_parts_#00'   => [ 'impoundable', 'ressource' ],
        'explo_#00'        => [ 'impoundable', 'ressource' ],
        'mecanism_#00'     => [ 'impoundable', 'ressource' ],
        'grenade_#00'        => [ 'impoundable', 'weapon', 'hero_find' ],
        'bgrenade_#00'       => [ 'impoundable', 'weapon' ],
        'boomfruit_#00'      => [ 'impoundable', 'weapon' ],
        'pilegun_#00'        => [ 'impoundable', 'weapon' ],
        'pilegun_up_#00'     => [ 'impoundable', 'weapon' ],
        'big_pgun_#00'       => [ 'impoundable', 'weapon' ],
        'mixergun_#00'       => [ 'impoundable', 'weapon' ],
        'chainsaw_#00'       => [ 'impoundable', 'weapon' ],
        'taser_#00'          => [ 'impoundable', 'weapon' ],
        'lpoint4_#00'        => [ 'impoundable', 'weapon' ],
        'lpoint3_#00'        => [ 'impoundable', 'weapon' ],
        'lpoint2_#00'        => [ 'impoundable', 'weapon' ],
        'lpoint1_#00'        => [ 'impoundable', 'weapon' ],
        'watergun_opt_5_#00' => [ 'impoundable', 'weapon' ],
        'watergun_opt_4_#00' => [ 'impoundable', 'weapon' ],
        'watergun_opt_3_#00' => [ 'impoundable', 'weapon' ],
        'watergun_opt_2_#00' => [ 'impoundable', 'weapon' ],
        'watergun_opt_1_#00' => [ 'impoundable', 'weapon' ],
        'watergun_3_#00'     => [ 'impoundable', 'weapon' ],
        'watergun_2_#00'     => [ 'impoundable', 'weapon' ],
        'watergun_1_#00'     => [ 'impoundable', 'weapon' ],
        'knife_#00'          => [ 'impoundable', 'weapon' ],
        'lawn_#00'           => [ 'impoundable', 'weapon' ],
        'torch_off_#00'      => [ 'impoundable', 'weapon' ],
        'iphone_#00'         => [ 'impoundable', 'weapon' ],
        'disinfect_#00'       => [ 'impoundable', 'drug' ],
        'drug_#00'            => [ 'can_poison', 'impoundable', 'drug' ],
        'drug_hero_#00'       => [ 'impoundable', 'drug' ],
        'drug_random_#00'     => [ 'impoundable', 'drug' ],
        'beta_drug_bad_#00'   => [ 'impoundable', 'drug' ],
        'beta_drug_#00'       => [ 'impoundable', 'drug' ],
        'xanax_#00'           => [ 'impoundable', 'drug' ],
        'drug_water_#00'      => [ 'impoundable', 'drug' ],
        'bandage_#00'         => [ 'impoundable', 'drug' ],
        'pharma_#00'          => [ 'impoundable', 'drug' ],
        'pharma_part_#00'     => [ 'impoundable', 'drug' ],
        'lsd_#00'             => [ 'impoundable', 'drug' ],
        'radio_on_#00'        => [ 'impoundable' ],
        'water_#00'        => [ 'can_poison', 'hero_find' ],
        'can_open_#00'     => [ 'can_poison' ],
        'vegetable_#00'    => [ 'can_poison' ],
        'fruit_#00'        => [ 'can_poison' ],
        'water_can_3_#00'  => [ 'can_poison' ],
        'water_can_2_#00'  => [ 'can_poison' ],
        'water_can_1_#00'  => [ 'can_poison' ],
        'cyanure_#00'      => [ 'can_poison' ],
        'lock_#00'         => [ 'lock' ],
        'pile_#00'       => [ 'hero_find' ],
        'food_bag_#00'   => [ 'hero_find' ],
        'rsc_pack_2_#00' => [ 'hero_find' ],

    ];

    public static $item_groups = [
        'empty_dig' => array(
            array('item' => 'wood_bad_#00','count' => '41306'),
            array('item' => 'metal_bad_#00','count' => '22856'),
        ),
        'base_dig' => array(
            array('item' => 'wood2_#00','count' => '16764'),
            array('item' => 'metal_#00','count' => '10124'),
            array('item' => 'grenade_empty_#00','count' => '6915'),
            array('item' => 'food_bag_#00','count' => '4845'),
            array('item' => 'pile_#00','count' => '4766'),
            array('item' => 'pharma_#00','count' => '3935'),
            array('item' => 'rustine_#00','count' => '2578'),
            array('item' => 'can_#00','count' => '2445'),
            array('item' => 'concrete_#00','count' => '1689'),
            array('item' => 'wood_plate_part_#00','count' => '1529'),
            array('item' => 'jerrycan_#00','count' => '1456'),
            array('item' => 'chest_tools_#00','count' => '1390'),
            array('item' => 'deco_box_#00','count' => '1309'),
            array('item' => 'bplan_drop_#00','count' => '1232'),
            array('item' => 'digger_#00','count' => '1231'),
            array('item' => 'tube_#00','count' => '1184'),
            array('item' => 'wood_beam_#00','count' => '1176'),
            array('item' => 'powder_#00','count' => '1159'),
            array('item' => 'staff_#00','count' => '1138'),
            array('item' => 'oilcan_#00','count' => '1100'),
            array('item' => 'mecanism_#00','count' => '1063'),
            array('item' => 'drug_#00','count' => '965'),
            array('item' => 'fest_#00','count' => '964'),
            array('item' => 'meca_parts_#00','count' => '964'),
            array('item' => 'plate_raw_#00','count' => '963'),
            array('item' => 'watergun_empty_#00','count' => '953'),
            array('item' => 'deto_#00','count' => '935'),
            array('item' => 'repair_one_#00','count' => '907'),
            array('item' => 'pet_snake_#00','count' => '877'),
            array('item' => 'electro_box_#00','count' => '854'),
            array('item' => 'tekel_#00','count' => '845'),
            array('item' => 'door_#00','count' => '841'),
            array('item' => 'drug_random_#00','count' => '818'),
            array('item' => 'smoke_bomb_#00','count' => '814'),
            array('item' => 'bag_#00','count' => '808'),
            array('item' => 'water_cleaner_#00','count' => '803'),
            array('item' => 'tagger_#00','count' => '794'),
            array('item' => 'machine_3_#00','count' => '794'),
            array('item' => 'trestle_#00','count' => '790'),
            array('item' => 'pilegun_empty_#00','count' => '784'),
            array('item' => 'machine_1_#00','count' => '777'),
            array('item' => 'bretz_#00','count' => '775'),
            array('item' => 'explo_#00','count' => '771'),
            array('item' => 'food_noodles_#00','count' => '763'),
            array('item' => 'chest_#00','count' => '734'),
            array('item' => 'machine_2_#00','count' => '729'),
            array('item' => 'pet_rat_#00','count' => '710'),
            array('item' => 'wire_#00','count' => '691'),
            array('item' => 'drug_hero_#00','count' => '634'),
            array('item' => 'metal_beam_#00','count' => '592'),
            array('item' => 'ryebag_#00','count' => '541'),
            array('item' => 'spices_#00','count' => '508'),
            array('item' => 'small_knife_#00','count' => '501'),
            array('item' => 'bed_#00','count' => '501'),
            array('item' => 'rhum_#00','count' => '499'),
            array('item' => 'chain_#00','count' => '493'),
            array('item' => 'pet_chick_#00','count' => '491'),
            array('item' => 'cutter_#00','count' => '476'),
            array('item' => 'pet_pig_#00','count' => '474'),
            array('item' => 'meat_#00','count' => '474'),
            array('item' => 'can_opener_#00','count' => '469'),
            array('item' => 'lilboo_#00','count' => '458'),
            array('item' => 'electro_#00','count' => '427'),
            array('item' => 'chair_basic_#00','count' => '423'),
            array('item' => 'xanax_#00','count' => '418'),
            array('item' => 'diode_#00','count' => '416'),
            array('item' => 'pet_cat_#00','count' => '396'),
            array('item' => 'lights_#00','count' => '381'),
            array('item' => 'sport_elec_empty_#00','count' => '376'),
            array('item' => 'lock_#00','count' => '369'),
            array('item' => 'lens_#00','count' => '366'),
            array('item' => 'chest_food_#00','count' => '364'),
            array('item' => 'chudol_#00','count' => '347'),
            array('item' => 'angryc_#00','count' => '342'),
            array('item' => 'home_box_#00','count' => '302'),
            array('item' => 'home_def_#00','count' => '294'),
            array('item' => 'fence_#00','count' => '293'),
            array('item' => 'repair_kit_part_raw_#00','count' => '293'),
            array('item' => 'rsc_pack_2_#00','count' => '290'),
            array('item' => 'lamp_#00','count' => '288'),
            array('item' => 'disinfect_#00','count' => '288'),
            array('item' => 'book_gen_letter_#00','count' => '286'),
            array('item' => 'bandage_#00','count' => '284'),
            array('item' => 'plate_#00','count' => '283'),
            array('item' => 'chest_citizen_#00','count' => '282'),
            array('item' => 'cart_part_#00','count' => '274'),
            array('item' => 'chair_#00','count' => '273'),
            array('item' => 'bquies_#00','count' => '257'),
            array('item' => 'screw_#00','count' => '240'),
            array('item' => 'badge_#00','count' => '238'),
            array('item' => 'pc_#00','count' => '215'),
            array('item' => 'music_part_#00','count' => '210'),
            array('item' => 'book_gen_box_#00','count' => '209'),
            array('item' => 'paques_#00','count' => '207'),
            array('item' => 'cyanure_#00','count' => '206'),
            array('item' => 'hmeat_#00','count' => '195'),
            array('item' => 'knife_#00','count' => '194'),
            array('item' => 'engine_part_#00','count' => '193'),
            array('item' => 'game_box_#00','count' => '192'),
            array('item' => 'wood_log_#00','count' => '190'),
            array('item' => 'vibr_empty_#00','count' => '189'),
            array('item' => 'cigs_#00','count' => '181'),
            array('item' => 'out_def_#00','count' => '180'),
            array('item' => 'courroie_#00','count' => '179'),
            array('item' => 'catbox_#00','count' => '170'),
            array('item' => 'sheet_#00','count' => '115'),
            array('item' => 'iphone_#00','count' => '112'),
            array('item' => 'money_#00','count' => '110'),
            array('item' => 'home_box_xl_#00','count' => '109'),
            array('item' => 'coffee_machine_part_#00','count' => '103'),
            array('item' => 'cadaver_#00','count' => '102'),
            array('item' => 'smelly_meat_#00','count' => '102'),
            array('item' => 'postal_box_#00','count' => '98'),
            array('item' => 'big_pgun_part_#00','count' => '95'),
            array('item' => 'car_door_part_#00','count' => '95'),
            array('item' => 'chama_#00','count' => '94'),
            array('item' => 'water_can_empty_#00','count' => '93'),
            array('item' => 'cdbrit_#00','count' => '92'),
            array('item' => 'pilegun_upkit_#00','count' => '90'),
            array('item' => 'saw_tool_part_#00','count' => '90'),
            array('item' => 'beta_drug_bad_#00','count' => '90'),
            array('item' => 'rp_book_#00','count' => '90'),
            array('item' => 'chest_xl_#00','count' => '90'),
            array('item' => 'poison_part_#00','count' => '90'),
            array('item' => 'gun_#00','count' => '90'),
            array('item' => 'rsc_pack_3_#00','count' => '89'),
            array('item' => 'safe_#00','count' => '88'),
            array('item' => 'rp_twin_#00','count' => '87'),
            array('item' => 'food_armag_#00','count' => '87'),
            array('item' => 'cdelvi_#00','count' => '82'),
            array('item' => 'cdphil_#00','count' => '67'),
            array('item' => 'cinema_#00','count' => '53'),
            array('item' => 'maglite_off_#00','count' => '24'),
            array('item' => 'renne_#00','count' => '14'),
            array('item' => 'sand_ball_#00','count' => '8'),
            array('item' => 'vodka_de_#00','count' => '6'),
            array('item' => 'christmas_suit_3_#00','count' => '4'),
            array('item' => 'christmas_suit_1_#00','count' => '3'),
            array('item' => 'christmas_suit_2_#00','count' => '1'),
        ),
        'trash_good' => [
            array('item' => 'fence_#00','count' => '1'),
            array('item' => 'chest_#00','count' => '1'),
            array('item' => 'wood2_#00','count' => '1'),
            array('item' => 'metal_#00','count' => '1'),
            array('item' => 'repair_one_#00','count' => '1'),
            array('item' => 'home_def_#00','count' => '1'),
            array('item' => 'home_box_#00','count' => '1'),
        ],
        'trash_bad' => [
            array('item' => 'fruit_sub_part_#00','count' => '1'),
            array('item' => 'fruit_part_#00','count' => '1'),
            array('item' => 'pharma_part_#00','count' => '1'),
            array('item' => 'flesh_part_#00','count' => '1'),
            array('item' => 'water_cup_part_#00','count' => '1'),
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
                    $parent = $this->entityManager->getRepository(ItemCategory::class)->findOneByName( $entry['parent'] );
                    // If the entry has a parent, but that parent is missing from the database,
                    // defer the current entry for the next run
                    if ($parent === null) {
                        $missing_data[] = $entry;
                        continue;
                    }
                }

                // Attempt to fetch the current entry from the database; if the entry does not exist, create a new one
                $entity = $this->entityManager->getRepository(ItemCategory::class)->findOneByName( $entry['name'] );
                if (!$entity) $entity = new ItemCategory();

                // Set properties
                $entity->setName( $entry['name'] );
                $entity->setLabel( $entry['label'] );
                $entity->setOrdering( $entry['ordering'] );
                $entity->setParent( $entry['parent'] === null ? null :
                    $this->entityManager->getRepository(ItemCategory::class)->findOneByName( $entry['parent'] )
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
            $table2->setHeaders( ['Name','Label','Parent','Ordering'] );
            foreach ($missing_data as $entry)
                $table2->addRow( [ $entry['name'], $entry['label'], $entry['parent'], $entry['ordering'] ] );
            $table2->render();
        }
    }

    protected function insert_item_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {

        $out->writeln( '<comment>Item prototypes: ' . count(static::$item_prototype_data) . ' fixture entries available.</comment>' );

        // Get misc category
        $misc_category = $this->entityManager->getRepository(ItemCategory::class)->findOneByName( 'misc' );
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
            $entry_unique_id = $entry['icon'] . '_#' . str_pad($cache[$entry['icon']],2,'0',STR_PAD_LEFT);

            // Check the category
            $category = $this->entityManager->getRepository(ItemCategory::class)->findOneByName( $entry['category'] );
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
            $entity = $this->entityManager->getRepository(ItemPrototype::class)->findOneByName( $entry_unique_id );
            if ($entity === null) $entity = new ItemPrototype();

            // Set property
            $entity->setName( $entry_unique_id );
            $entity->setLabel( $entry['label'] );
            $entity->setIcon( $entry['icon'] );
            $entity->setDeco( $entry['deco'] );
            $entity->setHeavy( $entry['heavy'] );
            $entity->setCategory( $category );
            $entity->getProperties()->clear();

            if (isset(static::$item_prototype_properties[$entry_unique_id]))
                foreach (static::$item_prototype_properties[$entry_unique_id] as $property) {
                    if (!isset($properties[$property])) {
                        $properties[$property] = $manager->getRepository(ItemProperty::class)->findOneByName( $property );
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
