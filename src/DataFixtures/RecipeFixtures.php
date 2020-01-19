<?php

namespace App\DataFixtures;

use App\Entity\AffectAP;
use App\Entity\AffectItemConsume;
use App\Entity\AffectItemSpawn;
use App\Entity\AffectOriginalItem;
use App\Entity\AffectResultGroup;
use App\Entity\AffectResultGroupEntry;
use App\Entity\AffectStatus;
use App\Entity\AffectZombies;
use App\Entity\BuildingPrototype;
use App\Entity\CitizenStatus;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\RequireItem;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Entity\RequireStatus;
use App\Entity\RequireZombiePresence;
use App\Entity\Result;
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
        ["name"=>"Verstärkte Stadtmauer","temporary"=>0,"img"=>"small_wallimprove","vp"=>30,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 15,"metal_#00" => 5,], "children" => [
            ["name"=>"Großer Graben","temporary"=>0,"img"=>"small_gather","vp"=>10,"ap"=>80,"bp"=>0,"rsc"=>[], "children" => [
                ["name"=>"Wassergraben","temporary"=>0,"img"=>"small_waterhole","vp"=>65,"ap"=>50,"bp"=>1,"rsc"=>["water_#00" => 20,]],
            ]],
            ["name"=>"Rasierklingenmauer","temporary"=>0,"img"=>"item_plate","vp"=>50,"ap"=>40,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"metal_#00" => 15,]],
            ["name"=>"Pfahlgraben","temporary"=>0,"img"=>"small_spears","vp"=>40,"ap"=>40,"bp"=>1,"rsc"=>["wood2_#00" => 8,"wood_beam_#00" => 4,]],
            ["name"=>"Stacheldraht","temporary"=>0,"img"=>"small_barbed","vp"=>10,"ap"=>20,"bp"=>0,"rsc"=>["metal_#00" => 2,], "children" => [
                ["name"=>"Köder","temporary"=>1,"img"=>"small_meatbarbed","vp"=>80,"ap"=>10,"bp"=>1,"rsc"=>["bone_meat_#00" => 3,]],
            ]],
            ["name"=>"Weiterentwickelte Stadtmauer","temporary"=>0,"img"=>"small_wallimprove","vp"=>50,"ap"=>40,"bp"=>1,"rsc"=>["meca_parts_#00" => 3,"wood_beam_#00" => 9,"metal_beam_#00" => 6,]],
            ["name"=>"Verstärkende Balken","temporary"=>0,"img"=>"item_plate","vp"=>25,"ap"=>40,"bp"=>0,"rsc"=>["wood_beam_#00" => 1,"metal_beam_#00" => 3,], "children" => [
                ["name"=>"Zackenmauer","temporary"=>0,"img"=>"item_plate","vp"=>45,"ap"=>35,"bp"=>1,"rsc"=>["wood2_#00" => 5,"metal_#00" => 2,"concrete_wall_#00" => 1,], "children" => [
                    ["name"=>"Groooße Mauer","temporary"=>0,"img"=>"item_plate","vp"=>80,"ap"=>50,"bp"=>2,"rsc"=>["wood2_#00" => 10,"concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
                ]],
                ["name"=>"Zweite Schicht","temporary"=>0,"img"=>"item_plate","vp"=>75,"ap"=>65,"bp"=>1,"rsc"=>["wood2_#00" => 35,"metal_beam_#00" => 5,], "children" => [
                    ["name"=>"Dritte Schicht","temporary"=>0,"img"=>"item_plate","vp"=>100,"ap"=>65,"bp"=>2,"rsc"=>["metal_#00" => 30,"plate_#00" => 5,"metal_beam_#00" => 5,]],
                ]],
                ["name"=>"Betonschicht","temporary"=>0,"img"=>"small_wallimprove","vp"=>50,"ap"=>60,"bp"=>1,"rsc"=>["concrete_wall_#00" => 6,"metal_beam_#00" => 2,]],
                ["name"=>"Entwicklungsfähige Stadtmauer","temporary"=>0,"img"=>"item_home_def","vp"=>55,"ap"=>65,"bp"=>3,"rsc"=>["wood2_#00" => 5,"metal_#00" => 20,"concrete_wall_#00" => 1,]],
            ]],
            ["name"=>"Zombiereibe","temporary"=>0,"img"=>"small_grater","vp"=>55,"ap"=>60,"bp"=>1,"rsc"=>["meca_parts_#00" => 3,"metal_#00" => 20,"plate_#00" => 3,]],
            ["name"=>"Fallgruben","temporary"=>0,"img"=>"small_gather","vp"=>35,"ap"=>50,"bp"=>0,"rsc"=>["wood2_#00" => 10,]],
            ["name"=>"Zaun","temporary"=>0,"img"=>"small_fence","vp"=>30,"ap"=>50,"bp"=>0,"rsc"=>["wood_beam_#00" => 5,]],
            ["name"=>"Holzzaun","temporary"=>0,"img"=>"small_fence","vp"=>45,"ap"=>50,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"wood2_#00" => 20,"wood_beam_#00" => 5,]],
            ["name"=>"Sperrholz","temporary"=>0,"img"=>"item_plate","vp"=>25,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 5,"metal_#00" => 5,]],
            ["name"=>"Rüstungsplatten 3.0","temporary"=>0,"img"=>"item_plate","vp"=>40,"ap"=>40,"bp"=>0,"rsc"=>["wood2_#00" => 10,"metal_#00" => 10,]],
            ["name"=>"Extramauer","temporary"=>0,"img"=>"item_plate","vp"=>45,"ap"=>30,"bp"=>1,"rsc"=>["wood2_#00" => 15,"metal_#00" => 15,]],
            ["name"=>"Rüstungsplatten","temporary"=>0,"img"=>"item_plate","vp"=>25,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 10,]],
            ["name"=>"Rüstungsplatten 2.0","temporary"=>0,"img"=>"item_plate","vp"=>25,"ap"=>30,"bp"=>0,"rsc"=>["metal_#00" => 10,]],
            ["name"=>"Einseifer","temporary"=>0,"img"=>"small_wallimprove","vp"=>60,"ap"=>40,"bp"=>1,"rsc"=>["water_#00" => 10,"pharma_#00" => 6,"concrete_wall_#00" => 1,]],
            ["name"=>"Zerstäuber","temporary"=>0,"img"=>"small_waterspray","vp"=>0,"ap"=>50,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"metal_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 2,], "children" => [
                ["name"=>"Spraykanone","temporary"=>1,"img"=>"small_gazspray","vp"=>150,"ap"=>40,"bp"=>2,"rsc"=>["water_#00" => 2,"pharma_#00" => 7,"drug_#00" => 3,]],
                ["name"=>"Säurespray","temporary"=>1,"img"=>"small_acidspray","vp"=>35,"ap"=>30,"bp"=>1,"rsc"=>["water_#00" => 2,"pharma_#00" => 5,]],
            ]],
        ]],

        ["name"=>"Pumpe","temporary"=>0,"img"=>"small_water","vp"=>0,"ap"=>25,"bp"=>0,"rsc"=>["metal_#00" => 8,"tube_#00" => 1,], "children" => [
            ["name"=>"Wasserreiniger","temporary"=>0,"img"=>"item_jerrycan","vp"=>0,"ap"=>75,"bp"=>0,"rsc"=>["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 6,"tube_#00" => 3,], "children" => [
                ["name"=>"Minen","temporary"=>1,"img"=>"item_bgrenade","vp"=>115,"ap"=>50,"bp"=>2,"rsc"=>["water_#00" => 10,"metal_#00" => 3,"explo_#00" => 1,"deto_#00" => 1,]],
                ["name"=>"Wasserfilter","temporary"=>0,"img"=>"item_jerrycan","vp"=>0,"ap"=>50,"bp"=>3,"rsc"=>["metal_#00" => 10,"electro_#00" => 2,"fence_#00" => 1,]],
            ]],
            ["name"=>"Gemüsebeet","temporary"=>0,"img"=>"item_vegetable_tasty","vp"=>0,"ap"=>60,"bp"=>1,"rsc"=>["water_#00" => 10,"pharma_#00" => 1,"wood_beam_#00" => 10,], "children" => [
                ["name"=>"Dünger","temporary"=>0,"img"=>"item_digger","vp"=>0,"ap"=>30,"bp"=>3,"rsc"=>["water_#00" => 10,"drug_#00" => 2,"metal_#00" => 5,"pharma_#00" => 8,]],
                ["name"=>"Granatapfel","temporary"=>0,"img"=>"item_bgrenade","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["water_#00" => 10,"wood2_#00" => 5,"explo_#00" => 5,]],
            ]],
            ["name"=>"Brunnenbohrer","temporary"=>0,"img"=>"small_water","vp"=>0,"ap"=>60,"bp"=>0,"rsc"=>["wood_beam_#00" => 7,"metal_beam_#00" => 2,], "children" => [
                ["name"=>"Projekt Eden","temporary"=>0,"img"=>"small_eden","vp"=>0,"ap"=>65,"bp"=>3,"rsc"=>["explo_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 8,]],
            ]],
            ["name"=>"Wasserleitungsnetz","temporary"=>0,"img"=>"item_firework_tube","vp"=>0,"ap"=>40,"bp"=>0,"rsc"=>["meca_parts_#00" => 3,"metal_#00" => 5,"tube_#00" => 2,"metal_beam_#00" => 5,], "children" => [
                ["name"=>"Kärcher","temporary"=>0,"img"=>"small_waterspray","vp"=>50,"ap"=>50,"bp"=>0,"rsc"=>["water_#00" => 10,"meca_parts_#00" => 1,"wood2_#00" => 10,"metal_beam_#00" => 7,]],
                ["name"=>"Kreischender Rotor","temporary"=>0,"img"=>"small_grinder","vp"=>50,"ap"=>55,"bp"=>1,"rsc"=>["plate_#00" => 2,"tube_#00" => 2,"wood_beam_#00" => 4,"metal_beam_#00" => 10,]],
                ["name"=>"Sprinkleranlage","temporary"=>0,"img"=>"small_sprinkler","vp"=>150,"ap"=>85,"bp"=>3,"rsc"=>["water_#00" => 20,"tube_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 15,]],
                ["name"=>"Dusche","temporary"=>0,"img"=>"small_shower","vp"=>0,"ap"=>25,"bp"=>2,"rsc"=>["water_#00" => 5,"wood2_#00" => 4,"metal_#00" => 1,"tube_#00" => 1,]],
            ]],
            ["name"=>"Wasserturm","temporary"=>0,"img"=>"item_tube","vp"=>70,"ap"=>60,"bp"=>3,"rsc"=>["water_#00" => 40,"tube_#00" => 7,"metal_beam_#00" => 10,]],
            ["name"=>"Wasserfänger","temporary"=>1,"img"=>"item_tube","vp"=>0,"ap"=>12,"bp"=>1,"rsc"=>["wood2_#00" => 2,"metal_#00" => 2,]],
            ["name"=>"Wasserkanone","temporary"=>0,"img"=>"small_watercanon","vp"=>80,"ap"=>40,"bp"=>2,"rsc"=>["water_#00" => 15,"wood2_#00" => 5,"metal_#00" => 5,"metal_beam_#00" => 5,]],
            ["name"=>"Schleuse","temporary"=>0,"img"=>"small_shower","vp"=>60,"ap"=>50,"bp"=>1,"rsc"=>["water_#00" => 15,"wood2_#00" => 10,]],
            ["name"=>"Wasserfall","temporary"=>0,"img"=>"small_shower","vp"=>35,"ap"=>20,"bp"=>1,"rsc"=>["water_#00" => 10,]],
            ["name"=>"Wünschelrakete","temporary"=>0,"img"=>"small_rocketperf","vp"=>0,"ap"=>90,"bp"=>3,"rsc"=>["explo_#00" => 1,"tube_#00" => 1,"deto_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name"=>"Wünschelrute","temporary"=>0,"img"=>"small_waterdetect","vp"=>0,"ap"=>130,"bp"=>4,"rsc"=>["electro_#00" => 5,"wood_beam_#00" => 5,"metal_beam_#00" => 10,]],
            ["name"=>"Apfelbaum","temporary"=>0,"img"=>"small_appletree","vp"=>0,"ap"=>30,"bp"=>3,"rsc"=>["water_#00" => 10,"hmeat_#00" => 2,"pharma_#00" => 3,"metal_beam_#00" => 1,]],
        ]],

        ["name"=>"Werkstatt","temporary"=>0,"img"=>"small_refine","vp"=>0,"ap"=>25,"bp"=>0,"rsc"=>["wood2_#00" => 10,"metal_#00" => 8,], "children" => [
            ["name"=>"Metzgerei","temporary"=>0,"img"=>"item_meat","vp"=>0,"ap"=>40,"bp"=>2,"rsc"=>["wood2_#00" => 9,"metal_#00" => 4,], "children" => [
                ["name"=>"Kremato-Cue","temporary"=>0,"img"=>"item_hmeat","vp"=>0,"ap"=>45,"bp"=>2,"rsc"=>["wood_beam_#00" => 8,"metal_beam_#00" => 1,]],
            ]],
            ["name"=>"Verteidigungsanlage","temporary"=>0,"img"=>"item_meca_parts","vp"=>0,"ap"=>50,"bp"=>3,"rsc"=>["meca_parts_#00" => 3,"wood_beam_#00" => 7,"metal_beam_#00" => 8,]],
            ["name"=>"Kanonenhügel","temporary"=>0,"img"=>"small_dig","vp"=>30,"ap"=>50,"bp"=>0,"rsc"=>["concrete_wall_#00" => 1,"wood_beam_#00" => 7,"metal_beam_#00" => 1,], "children" => [
                ["name"=>"Steinkanone","temporary"=>0,"img"=>"small_canon","vp"=>50,"ap"=>60,"bp"=>1,"rsc"=>["tube_#00" => 1,"electro_#00" => 2,"concrete_wall_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
                ["name"=>"Selbstgebaute Railgun","temporary"=>0,"img"=>"small_canon","vp"=>50,"ap"=>40,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"tube_#00" => 1,"electro_#00" => 1,"metal_beam_#00" => 10,]],
                ["name"=>"Blechplattenwerfer","temporary"=>0,"img"=>"small_canon","vp"=>60,"ap"=>50,"bp"=>1,"rsc"=>["meca_parts_#00" => 3,"plate_#00" => 3,"explo_#00" => 3,"wood_beam_#00" => 5,"metal_beam_#00" => 1,]],
                ["name"=>"Brutale Kanone","temporary"=>1,"img"=>"small_canon","vp"=>50,"ap"=>25,"bp"=>0,"rsc"=>["plate_#00" => 1,"metal_beam_#00" => 1,]],
            ]],
            ["name"=>"Holzbalkendrehkreuz","temporary"=>0,"img"=>"item_wood_beam","vp"=>10,"ap"=>15,"bp"=>0,"rsc"=>["wood_beam_#00" => 2,"metal_beam_#00" => 1,]],
            ["name"=>"Manufaktur","temporary"=>0,"img"=>"small_factory","vp"=>0,"ap"=>40,"bp"=>0,"rsc"=>["wood_beam_#00" => 5,"metal_beam_#00" => 5,"table_#00" => 1,]],
            ["name"=>"Kreischende Sägen","temporary"=>0,"img"=>"small_saw","vp"=>45,"ap"=>65,"bp"=>0,"rsc"=>["meca_parts_#00" => 3,"metal_#00" => 5,"rustine_#00" => 3,"metal_beam_#00" => 2,]],
            ["name"=>"Baustellenbuch","temporary"=>0,"img"=>"item_rp_book2","vp"=>0,"ap"=>15,"bp"=>0,"rsc"=>["table_#00" => 1,], "children" => [
                ["name"=>"Bauhaus","temporary"=>0,"img"=>"small_refine","vp"=>0,"ap"=>75,"bp"=>0,"rsc"=>["drug_#00" => 1,"vodka_de_#00" => 1,"wood_beam_#00" => 10,]],
            ]],
            ["name"=>"Galgen","temporary"=>0,"img"=>"r_dhang","vp"=>0,"ap"=>13,"bp"=>0,"rsc"=>["wood_beam_#00" => 1,"chain_#00" => 1,]],
            ["name"=>"Kleines Cafe","temporary"=>1,"img"=>"small_cafet","vp"=>0,"ap"=>6,"bp"=>0,"rsc"=>["water_#00" => 1,"wood2_#00" => 2,"pharma_#00" => 1,]],
            ["name"=>"Kleiner Friedhof","temporary"=>0,"img"=>"small_cemetery","vp"=>0,"ap"=>36,"bp"=>1,"rsc"=>["meca_parts_#00" => 1,"wood2_#00" => 10,], "children" => [
                ["name"=>"Sarg-Katapult","temporary"=>0,"img"=>"small_coffin","vp"=>0,"ap"=>100,"bp"=>4,"rsc"=>["courroie_#00" => 1,"meca_parts_#00" => 5,"wood2_#00" => 5,"metal_#00" => 15,]],
            ]],
            ["name"=>"Hühnerstall","temporary"=>0,"img"=>"small_chicken","vp"=>0,"ap"=>25,"bp"=>3,"rsc"=>["pet_chick_#00" => 2,"wood2_#00" => 5,"wood_beam_#00" => 5,"fence_#00" => 2,]],
            ["name"=>"Schlachthof","temporary"=>0,"img"=>"small_slaughterhouse","vp"=>35,"ap"=>40,"bp"=>1,"rsc"=>["concrete_wall_#00" => 2,"metal_beam_#00" => 10,]],
            ["name"=>"Pentagon","temporary"=>0,"img"=>"item_shield","vp"=>8,"ap"=>55,"bp"=>3,"rsc"=>["wood_beam_#00" => 5,"metal_beam_#00" => 10,]],
            ["name"=>"Kantine","temporary"=>0,"img"=>"small_cafet","vp"=>0,"ap"=>20,"bp"=>1,"rsc"=>["pharma_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,"table_#00" => 1,]],
            ["name"=>"Bollwerk","temporary"=>0,"img"=>"small_strategy","vp"=>0,"ap"=>60,"bp"=>3,"rsc"=>["meca_parts_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ["name"=>"Baumarkt","temporary"=>0,"img"=>"small_strategy","vp"=>0,"ap"=>30,"bp"=>4,"rsc"=>["meca_parts_#00" => 3,"wood_beam_#00" => 10,"metal_beam_#00" => 10,]],
            ["name"=>"Krankenstation","temporary"=>0,"img"=>"small_infirmary","vp"=>0,"ap"=>40,"bp"=>3,"rsc"=>["pharma_#00" => 6,"disinfect_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name"=>"Labor","temporary"=>0,"img"=>"item_acid","vp"=>0,"ap"=>30,"bp"=>1,"rsc"=>["meca_parts_#00" => 3,"pharma_#00" => 5,"wood_beam_#00" => 3,"metal_beam_#00" => 10,]],
        ]],

        ["name"=>"Wachturm","temporary"=>0,"img"=>"item_tagger","vp"=>0,"ap"=>12,"bp"=>0,"rsc"=>["wood2_#00" => 3,"metal_#00" => 2,], "children" => [
            ["name"=>"Scanner","temporary"=>0,"img"=>"item_tagger","vp"=>0,"ap"=>20,"bp"=>2,"rsc"=>["pile_#00" => 2,"meca_parts_#00" => 1,"electro_#00" => 1,"cyanure_#00" => 2,]],
            ["name"=>"Verbesserte Karte","temporary"=>0,"img"=>"item_electro","vp"=>0,"ap"=>15,"bp"=>1,"rsc"=>["pile_#00" => 2,"metal_#00" => 1,"electro_#00" => 1,"cyanure_#00" => 2,]],
            ["name"=>"Rechenmaschine","temporary"=>0,"img"=>"item_tagger","vp"=>0,"ap"=>20,"bp"=>1,"rsc"=>["rustine_#00" => 1,"electro_#00" => 1,]],
            ["name"=>"Forschungsturm","temporary"=>0,"img"=>"small_gather","vp"=>0,"ap"=>30,"bp"=>1,"rsc"=>["electro_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 1,"table_#00" => 1,]],
            ["name"=>"Notfallkonstruktion","temporary"=>0,"img"=>"status_terror","vp"=>0,"ap"=>40,"bp"=>0,"rsc"=>["wood2_#00" => 5,"metal_#00" => 7,], "children" => [
                ["name"=>"Verteidigungspfähle","temporary"=>1,"img"=>"small_trap","vp"=>25,"ap"=>12,"bp"=>0,"rsc"=>["wood2_#00" => 6,]],
                ["name"=>"Notfallabstützung","temporary"=>1,"img"=>"item_wood_plate","vp"=>40,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 8,]],
                ["name"=>"Guerilla","temporary"=>1,"img"=>"small_trap","vp"=>50,"ap"=>24,"bp"=>2,"rsc"=>["meca_parts_#00" => 2,"wood2_#00" => 2,"metal_#00" => 1,]],
                ["name"=>"Abfallberg","temporary"=>1,"img"=>"small_dig","vp"=>5,"ap"=>10,"bp"=>0,"rsc"=>["wood2_#00" => 2,"metal_#00" => 2,], "children" => [
                    ["name"=>"Trümmerberg","temporary"=>1,"img"=>"small_dig","vp"=>60,"ap"=>40,"bp"=>1,"rsc"=>["metal_#00" => 2,]],
                ]],
                ["name"=>"Wolfsfalle","temporary"=>1,"img"=>"small_trap","vp"=>40,"ap"=>20,"bp"=>0,"rsc"=>["metal_#00" => 2,"hmeat_#00" => 3,]],
                ["name"=>"Sprengfalle","temporary"=>1,"img"=>"small_tnt","vp"=>35,"ap"=>20,"bp"=>0,"rsc"=>["explo_#00" => 3,]],
                ["name"=>"Nackte Panik","temporary"=>1,"img"=>"status_terror","vp"=>50,"ap"=>25,"bp"=>0,"rsc"=>["water_#00" => 4,"wood2_#00" => 5,"metal_#00" => 5,]],
                ["name"=>"Dollhouse","temporary"=>1,"img"=>"small_bamba","vp"=>75,"ap"=>50,"bp"=>2,"rsc"=>["wood2_#00" => 5,"metal_#00" => 5,"cyanure_#00" => 3,]],
                ["name"=>"Heiliger Regen","temporary"=>1,"img"=>"small_holyrain","vp"=>200,"ap"=>40,"bp"=>0,"rsc"=>["water_#00" => 5,"wood2_#00" => 5,"wood_beam_#00" => 9,"soul_red_#00" => 4,]],
                ["name"=>"Voodoo-Puppe","temporary"=>0,"img"=>"small_vaudoudoll","vp"=>65,"ap"=>40,"bp"=>0,"rsc"=>["water_#00" => 2,"meca_parts_#00" => 3,"metal_#00" => 2,"plate_#00" => 2,"soul_red_#00" => 2,]],
                ["name"=>"Spirituelles Wunder","temporary"=>0,"img"=>"small_spiritmirage","vp"=>80,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 6,"plate_#00" => 2,"wood_beam_#00" => 6,"soul_red_#00" => 2,]],
                ["name"=>"Bokors Guillotine","temporary"=>0,"img"=>"small_bokorsword","vp"=>100,"ap"=>60,"bp"=>0,"rsc"=>["plate_#00" => 3,"wood_beam_#00" => 8,"metal_beam_#00" => 5,"soul_red_#00" => 3,]],
            ]],
            ["name"=>"Katapult","temporary"=>0,"img"=>"item_courroie","vp"=>0,"ap"=>40,"bp"=>1,"rsc"=>["wood2_#00" => 2,"metal_#00" => 1,"wood_beam_#00" => 1,"metal_beam_#00" => 1,], "children" => [
                ["name"=>"Verbesserter Katapult","temporary"=>0,"img"=>"item_courroie","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["courroie_#00" => 1,"wood2_#00" => 2,"metal_#00" => 2,"electro_#00" => 2,]],
            ]],
            ["name"=>"Wächter-Turm","temporary"=>0,"img"=>"small_watchmen","vp"=>15,"ap"=>24,"bp"=>2,"rsc"=>["meca_parts_#00" => 1,"plate_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 2,], "children" => [
                ["name"=>"Kleine Waffenschmiede","temporary"=>0,"img"=>"small_armor","vp"=>0,"ap"=>50,"bp"=>2,"rsc"=>["meca_parts_#00" => 3,"wood2_#00" => 10,"metal_#00" => 15,"plate_#00" => 2,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,]],
                ["name"=>"Schwedische Schreinerei","temporary"=>0,"img"=>"small_ikea","vp"=>0,"ap"=>50,"bp"=>2,"rsc"=>["meca_parts_#00" => 3,"wood2_#00" => 15,"metal_#00" => 10,"plate_#00" => 4,"concrete_wall_#00" => 2,"wood_beam_#00" => 5,]],
                ["name"=>"Schießstand","temporary"=>0,"img"=>"small_tourello","vp"=>50,"ap"=>25,"bp"=>2,"rsc"=>["water_#00" => 30,"tube_#00" => 2,"wood_beam_#00" => 1,"metal_beam_#00" => 2,]],
                ["name"=>"Kleiner Tribok","temporary"=>0,"img"=>"small_catapult3","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["wood_beam_#00" => 2,"metal_beam_#00" => 4,"meca_parts_#00" => 2,"plate_#00" => 2,"tube_#00" => 1,]],
            ]],
            ["name"=>"Krähennest","temporary"=>0,"img"=>"small_watchmen","vp"=>10,"ap"=>36,"bp"=>2,"rsc"=>["meca_parts_#00" => 1,"wood_beam_#00" => 5,"metal_beam_#00" => 1,]],
        ]],

        ["name"=>"Fundament","temporary"=>0,"img"=>"small_building","vp"=>0,"ap"=>30,"bp"=>0,"rsc"=>["wood2_#00" => 10,"metal_#00" => 8,], "children" => [
            ["name"=>"Bohrturm","temporary"=>0,"img"=>"small_derrick","vp"=>0,"ap"=>70,"bp"=>3,"rsc"=>["wood_beam_#00" => 10,"metal_beam_#00" => 15,]],
            ["name"=>"Falsche Stadt","temporary"=>0,"img"=>"small_falsecity","vp"=>400,"ap"=>400,"bp"=>3,"rsc"=>["meca_parts_#00" => 15,"wood2_#00" => 20,"metal_#00" => 20,"wood_beam_#00" => 20,"metal_beam_#00" => 20,]],
            ["name"=>"Wasserhahn","temporary"=>0,"img"=>"small_valve","vp"=>0,"ap"=>130,"bp"=>3,"rsc"=>["engine_#00" => 1,"meca_parts_#00" => 4,"metal_#00" => 10,"wood_beam_#00" => 6,"metal_beam_#00" => 3,]],
            ["name"=>"Großer Umbau","temporary"=>0,"img"=>"small_moving","vp"=>300,"ap"=>300,"bp"=>3,"rsc"=>["wood2_#00" => 20,"metal_#00" => 20,"concrete_wall_#00" => 5,"wood_beam_#00" => 20,"metal_beam_#00" => 20,]],
            ["name"=>"Vogelscheuche","temporary"=>0,"img"=>"small_scarecrow","vp"=>25,"ap"=>35,"bp"=>0,"rsc"=>["wood2_#00" => 10,"rustine_#00" => 2,]],
            ["name"=>"Fleischkäfig","temporary"=>0,"img"=>"small_fleshcage","vp"=>0,"ap"=>40,"bp"=>0,"rsc"=>["meca_parts_#00" => 2,"metal_#00" => 8,"chair_basic_#00" => 1,"wood_beam_#00" => 1,]],
            ["name"=>"Bürgergericht","temporary"=>0,"img"=>"small_court","vp"=>0,"ap"=>12,"bp"=>2,"rsc"=>["wood2_#00" => 6,"metal_beam_#00" => 15,"table_#00" => 1,]],
            ["name"=>"Befestigungen","temporary"=>0,"img"=>"small_city_up","vp"=>0,"ap"=>50,"bp"=>3,"rsc"=>["concrete_wall_#00" => 2,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
            ["name"=>"Müllhalde","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>70,"bp"=>0,"rsc"=>["concrete_wall_#00" => 5,"wood_beam_#00" => 15,"metal_beam_#00" => 15,], "children" => [
                ["name"=>"Holzabfall","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["meca_parts_#00" => 1,"wood2_#00" => 5,"metal_#00" => 5,]],
                ["name"=>"Metallabfall","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["wood2_#00" => 5,"metal_#00" => 5,]],
                ["name"=>"Tierabfälle","temporary"=>0,"img"=>"small_howlingbait","vp"=>0,"ap"=>30,"bp"=>2,"rsc"=>["wood_beam_#00" => 10,]],
                ["name"=>"Müll für Alle","temporary"=>0,"img"=>"small_trashclean","vp"=>0,"ap"=>30,"bp"=>3,"rsc"=>["meca_parts_#00" => 2,"concrete_wall_#00" => 1,"wood_beam_#00" => 10,"metal_beam_#00" => 10,"trestle_#00" => 2,]],
                ["name"=>"Waffenabfall","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>20,"bp"=>2,"rsc"=>["meca_parts_#00" => 1,"metal_#00" => 8,]],
                ["name"=>"Biomüll","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>20,"bp"=>2,"rsc"=>["wood2_#00" => 15,]],
                ["name"=>"Rüstungsabfall","temporary"=>0,"img"=>"small_trash","vp"=>0,"ap"=>40,"bp"=>2,"rsc"=>["metal_beam_#00" => 3,"metal_#00" => 5,]],
                ["name"=>"Verbesserte Müllhalde","temporary"=>0,"img"=>"small_trash","vp"=>75,"ap"=>120,"bp"=>4,"rsc"=>["water_#00" => 20,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ]],
            ["name"=>"Leuchtturm","temporary"=>0,"img"=>"small_lighthouse","vp"=>0,"ap"=>30,"bp"=>3,"rsc"=>["electro_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name"=>"Altar","temporary"=>0,"img"=>"small_redemption","vp"=>0,"ap"=>24,"bp"=>2,"rsc"=>["pet_pig_#00" => 1,"wood_beam_#00" => 3,"metal_beam_#00" => 2,]],
            ["name"=>"Riesige Sandburg","temporary"=>0,"img"=>"small_castle","vp"=>0,"ap"=>300,"bp"=>4,"rsc"=>["water_#00" => 30,"wood_beam_#00" => 15,"metal_beam_#00" => 10,]],
            ["name"=>"Leuchtfeuer","temporary"=>1,"img"=>"small_score","vp"=>30,"ap"=>15,"bp"=>2,"rsc"=>["lights_#00" => 1,"wood2_#00" => 5,]],
            ["name"=>"Ministerium für Sklaverei","temporary"=>0,"img"=>"small_slave","vp"=>0,"ap"=>45,"bp"=>4,"rsc"=>["wood_beam_#00" => 10,"metal_beam_#00" => 5,"chain_#00" => 2,]],
            ["name"=>"Reaktor","temporary"=>0,"img"=>"small_arma","vp"=>500,"ap"=>100,"bp"=>4,"rsc"=>["pile_#00" => 10,"engine_#00" => 1,"electro_#00" => 4,"concrete_wall_#00" => 2,"metal_beam_#00" => 15,]],
            ["name"=>"Labyrinth","temporary"=>0,"img"=>"small_labyrinth","vp"=>150,"ap"=>200,"bp"=>3,"rsc"=>["meca_parts_#00" => 2,"wood2_#00" => 20,"metal_#00" => 10,"concrete_wall_#00" => 4,]],
            ["name"=>"Alles oder nichts","temporary"=>0,"img"=>"small_lastchance","vp"=>55,"ap"=>150,"bp"=>3,"rsc"=>["meca_parts_#00" => 4,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
            ["name"=>"Riesiger KVF","temporary"=>0,"img"=>"small_pmvbig","vp"=>0,"ap"=>300,"bp"=>4,"rsc"=>["meca_parts_#00" => 2,"metal_#00" => 30,]],
            ["name"=>"Riesenrad","temporary"=>0,"img"=>"small_wheel","vp"=>0,"ap"=>300,"bp"=>4,"rsc"=>["water_#00" => 20,"meca_parts_#00" => 5,"concrete_wall_#00" => 3,"metal_beam_#00" => 5,]],
            ["name"=>"Feuerwerk","temporary"=>0,"img"=>"small_fireworks","vp"=>0,"ap"=>50,"bp"=>4,"rsc"=>["meca_parts_#00" => 1,"explo_#00" => 4,"deto_#00" => 2,"wood_beam_#00" => 3,"metal_beam_#00" => 3,]],
            ["name"=>"Krähenstatue","temporary"=>0,"img"=>"small_crow","vp"=>0,"ap"=>300,"bp"=>4,"rsc"=>["hmeat_#00" => 3,"wood_beam_#00" => 35,]],
            ["name"=>"Kino","temporary"=>0,"img"=>"small_cinema","vp"=>0,"ap"=>100,"bp"=>4,"rsc"=>["electro_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 5,"machine_1_#00" => 1,"machine_2_#00" => 1,]],
            ["name"=>"Luftschlag","temporary"=>1,"img"=>"small_rocket","vp"=>0,"ap"=>50,"bp"=>3,"rsc"=>["water_#00" => 10,"meca_parts_#00" => 1,"metal_#00" => 5,"explo_#00" => 1,"deto_#00" => 2,]],
            ["name"=>"Heißluftballon","temporary"=>1,"img"=>"small_balloon","vp"=>0,"ap"=>100,"bp"=>4,"rsc"=>["meca_parts_#00" => 6,"sheet_#00" => 2,"wood_beam_#00" => 5,"metal_beam_#00" => 5,]],
            ["name"=>"Tunnelratte","temporary"=>0,"img"=>"small_derrick","vp"=>0,"ap"=>170,"bp"=>4,"rsc"=>["concrete_wall_#00" => 3,"wood_beam_#00" => 15,"metal_beam_#00" => 15,]],
        ]],

        ["name"=>"Portal","temporary"=>0,"img"=>"small_door_closed","vp"=>0,"ap"=>16,"bp"=>0,"rsc"=>["metal_#00" => 2,], "children" => [
            ["name"=>"Torpanzerung","temporary"=>0,"img"=>"item_plate","vp"=>20,"ap"=>35,"bp"=>0,"rsc"=>["wood2_#00" => 3,]],
            ["name"=>"Kolbenschließmechanismus","temporary"=>0,"img"=>"small_door_closed","vp"=>30,"ap"=>24,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"wood2_#00" => 10,"tube_#00" => 1,"metal_beam_#00" => 3,], "children" => [
                ["name"=>"Automatiktür","temporary"=>0,"img"=>"small_door_closed","vp"=>0,"ap"=>24,"bp"=>1,"rsc"=>["meca_parts_#00" => 2,"metal_#00" => 5,"electro_#00" => 2,]],
            ]],
            ["name"=>"Ventilationssystem","temporary"=>0,"img"=>"small_ventilation","vp"=>20,"ap"=>24,"bp"=>2,"rsc"=>["meca_parts_#00" => 1,"metal_#00" => 8,]],
        ]],
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
     * @param BuildingPrototype|null $parent
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

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Buildings</info>' );
        $output->writeln("");

        try {
            $this->insert_buildings( $manager, $output );
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
