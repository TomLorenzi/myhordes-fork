<?php

namespace MyHordes\Fixtures\Service;

use App\Enum\ArrayMergeDirective;
use MyHordes\Plugins\Interfaces\FixtureProcessorInterface;

class RuinDataService implements FixtureProcessorInterface {

    public function process(array &$data): void
    {
        $data = array_merge_recursive($data, [
            // id 20
            'home' => ["label" => "Abgelegenes Haus",'icon' => 'home',"camping" => 7,"min_dist" => 1, "max_dist" => 4, "chance" => 686, "empty" => 0.25, "drops"=> [
                'can_#00' => 237,
                'chest_citizen_#00' => 128,
                'electro_box_#00' => 25,
                'chair_basic_#00' => 18,
                'lock_#00' => 8,
            ], 'desc' => 'Hier wohnte ein Bürger, der  sich außerhalb der Stadt niedergelassen hat, weil er den Streitigkeiten und dem Verrat, die das Stadtleben mit sich bringt, entfliehen wollte. Die Hälfte seiner Leiche liegt immer noch im Wohnzimmer.'],

            // id 41
            'albi' => ["label" => "Albi Supermarkt",'icon' => 'albi',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 686, "empty" => 0.05, "drops" => [
                'drug_hero_#00' => 91,
                'meat_#00' => 91,
                'food_noodles_hot_#00' => 83,
                'vegetable_tasty_#00' => 82,
                'electro_box_#00' => 32,
                'door_carpet_#00' => 27,
                'food_bag_#00' => 23,
                'powder_#00' => 22,
                'lights_#00' => 14,
            ], 'desc' => 'Einer der vielen Albi Supermarkt, die um das Jahr 2010 herum aus dem Boden schossen und später dann verschwanden... spezialisiert darauf, Dinge so billig wie möglich zu verscherbeln. Hier findest du alles finden, was du brauchst - egal ob du einfach pleite bist oder eisern auf ein neues Stück Seife sparst, kaufe bei ALBI ein!'],

            // id 57
            'cave' => ["label" => "Alte Höhle",'icon' => 'cave',"camping" => 7,"min_dist" => 16, "max_dist" => 19, "chance" => 184, "empty" => 0.10, "drops" => [
                'money_#00' => 106,
                'machine_1_#00' => 26,
                'machine_3_#00' => 25,
                'chair_basic_#00' => 25,
                'machine_2_#00' => 22,
                'flash_#00' => 21,
                'coffee_#00' => 20,
                'table_#00' => 9,
                'teddy_#00' => 4,
                'rp_sheets_#00' => 2,
                'rp_letter_#00' => 1,
                'radius_mk2_part_#00' => 1,
            ], 'desc' => 'Manche Fehler enden zwangsläufig tödlich. Nehmt als Beispiel diese Höhle. Stellt euch vor, ihr werdet von einer Zombiemeute verfolgt und eilt überstürzt in eine Höhle, um Schutz zu suchen. Ihr werdet dann folgendes Problem haben: Wie kommt ihr heil wieder raus, wenn die Biester euch gefolgt sind? Der zerfledderte Typ an der Wand dort hinten scheint dies nicht bedacht zu haben...'],

            // id 3
            'pump' => ["label" => "Alte Hydraulikpumpe",'icon' => 'pump',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 401, "empty" => 0.10, "drops" => [
                'jerrycan_#00' => 331,
                'oilcan_#00' => 23,
                'metal_beam_#00' => 20,
                'tube_#00' => 18,
                'jerrygun_part_#00' => 8,
                'electro_#00' => 8,
            ], 'desc' => 'Eine alte Pumpe, die zwar vor sich hin rostet, aber dennoch in der Lage ist, in der Wüste Wasser zu schöpfen... Das einzige Problem ist, dass das Wasser, selbst wenn Sie es zum Funktionieren bringen, im Grunde genommen ungenießbar ist und in der Stadt mit den entsprechenden Geräten gereinigt werden muss.'],

            // id 24
            'bike' => ["label" => "Alter Fahrradverleih",'icon' => 'bike',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 159, "empty" => 0.25, "drops" => [
                'pocket_belt_#00' => 27,
                'tube_#00' => 19,
                'courroie_#00' => 19,
                'radio_off_#00' => 7,
                'meca_parts_#00' => 6,
            ], 'desc' => 'Ein altes Fahrradverleihlager, das mit Metallstücken, Werkzeugen und allen Arten von Schutt übersät ist.'],

            // id 53
            'freight' => ["label" => "Alter Rangierbahnhof",'icon' => 'freight',"camping" => 7,"min_dist" => 10, "max_dist" => 13, "chance" => 464, "empty" => 0.10, "drops" => [
                'metal_#00' => 114,
                'wood2_#00' => 113,
                'chain_#00' => 52,
                'metal_beam_#00' => 36,
                'wood_beam_#00' => 33,
                'wrench_#00' => 20,
                'courroie_#00' => 12,
                'coffee_#00' => 12,
            ], 'desc' => 'Dieser Rangierbahnhof war einmal das zentrale Drehkreuz des Landes. Waren aus aller Herren Länder wurden hier rund um die Uhr umgeladen und in alle Himmelsrichtungen versendet. Das \'weitverzweigte Netzt\' ist heute noch ungefähr 150 Meter lang, vorausgesetzt man zählt die Gleisüberbleibsel da hinten noch mit.'],

            // id 54
            'hospital' => ["label" => "Altes Feldkrankenhaus",'icon' => 'hospital',"camping" => 7,"min_dist" => 16, "max_dist" => 19, "chance" => 205, "empty" => 0.10, "drops" => [
                'drug_random_#00' => 67,
                'pharma_#00' => 39,
                'beta_drug_bad_#00' => 33,
                'disinfect_#00' => 26,
                'cyanure_#00' => 19,
                'drug_water_#00' => 18,
                'drug_hero_#00' => 14,
                'xanax_#00' => 12,
                'drug_#00' => 12,
                'fungus_#00' => 3,
                'vodka_#00' => 2,
            ], 'desc' => 'Die menschlichen Überreste, die in der Auffahrt liegen gehören den ehemaligen Patienten dieses improvisierten Krankenhauses. Schwer zu sagen, wie viele Menschen hier beim abendlichen Angriff gestorben sind... Wenn du die Anzahl der Arme durch zwei teilst, vielleicht bekommst du dann eine grobe Schätzung?'],

            // id 48
            'aerodrome' => ["label" => "Altes Flugfeld",'icon' => 'aerodrome',"camping" => 7,"min_dist" => 12, "max_dist" => 15, "chance" => 129, "empty" => 0.10, "drops" => [
                'metal_beam_#00' => 62,
                'electro_box_#00' => 28,
                'meca_parts_#00' => 24,
                'repair_one_#00' => 21,
                'jerrycan_#00' => 4,
                'courroie_#00' => 3,
                'fence_#00' => 2,
                'wire_#00' => 2,
                'oilcan_#00' => 2,
                'rp_manual_#00' => 1,
                'plate_raw_#00' => 1,
                'tube_#00' => 1,
                'engine_part_#00' => 1,
            ], 'desc' => 'Das Einzige, was auf diesem bröckelnden Flugplatz startet oder landet, sind die Fliegen. Vielleicht finden Sie etwas Nützliches, wenn Sie in den Lagerhallen herumstöbern. Zum Beispiel einen A380 in funktionstüchtigem Zustand.'],

            // id 8
            'police' => ["label" => "Altes Polizeipräsidium",'icon' => 'police',"camping" => 11,"min_dist" => 6, "max_dist" => 9, "chance" => 640, "empty" => 0.10, "drops" => [
                'drug_hero_#00' => 58,
                'taser_empty_#00' => 53,
                'repair_kit_#00' => 49,
                'watergun_empty_#00' => 46,
                'watergun_opt_part_#00' => 38,
                'deto_#00' => 37,
                'tagger_#00' => 36,
                'knife_#00' => 35,
                'gun_#00' => 35,
                'bed_#00' => 34,
                'cutcut_#00' => 34,
                'big_pgun_part_#00' => 34,
                'bag_#00' => 33,
                'pilegun_empty_#00' => 28,
                'bandage_#00' => 24,
                'chair_basic_#00' => 21,
                'machine_gun_#00' => 18,
                'chest_xl_#00' => 10,
                'wire_#00' => 6,
                'bagxl_#00' => 5,
            ], 'desc' => 'Dieses beeindruckende Gebäude erstreckt sich auf mehrere Hundert Meter. Es enthält zahlreiche Räume, die größtenteils eingestürzt sind. Die große Anzahl an Einschusslöchern in den Wänden und die improvisierten Barrikaden lassen vermuten, dass das Gebäude vor einiger Zeit Schauplatz heftiger Gefechte gewesen ist.'],

            // id 10
            'bunker' => ["label" => "Atombunker",'icon' => 'bunker',"camping" => 15,"min_dist" => 10, "max_dist" => 13, "chance" => 499, "empty" => 0.10, "drops" => [
                'drug_hero_#00' => 127,
                'tagger_#00' => 66,
                'chest_#00' => 60,
                'repair_kit_#00' => 54,
                'electro_#00' => 51,
                'taser_empty_#00' => 39,
                'pharma_#00' => 34,
                'jerrygun_part_#00' => 34,
                'jerrycan_#00' => 32,
                'mixergun_part_#00' => 31,
                'can_#00' => 29,
                'big_pgun_part_#00' => 26,
                'plate_raw_#00' => 24,
                'machine_gun_#00' => 16,
                'radius_mk2_part_#00' => 15,
                'chainsaw_part_#00' => 10,
                'chest_xl_#00' => 5,
            ], 'desc' => 'Die Farbe der am Bunkereingang gepinselten Zahl ist fast vollständig abgeblättert, aber es handelt sich wahrscheinlich um den Bunker 14. Im Inneren liegen überall verweste Leichen herum. Scheint so, als ob der Schließmechanismus versagt hätte. Das kommt vor.'],

            // id 38
            'cafe' => ["label" => "Atomic Cafe",'icon' => 'cafe',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 320, "empty" => 0.10, "drops" => [
                'coffee_#00' => 55,
                'food_chick_#00' => 55,
                'pet_rat_#00' => 30,
                'rhum_#00' => 27,
                'pharma_#00' => 17,
                'drug_#00' => 7,
                'vodka_#00' => 4,
                'coffee_machine_part_#00' => 1,
            ], 'desc' => 'Das Atomic Cafe ist (oder war) der Ort, an dem man sein sollte: Ein verblichenes Plakat lädt Sie zum Sommerfest am 2. Mai 2010 ein: Hawaiianisches Thema, Preis für den bestangezogenen (halbnackten Mädchen + Jungs) DJ Dave ab 13.00 Uhr, kostenloses BBQ, Biergarten mit verbessertem Look, Partyspiele, Live-Fußball, Cocktails, £2 Flaschenbier, £2 Alcopop, £1 Tequila... Beteiligen Sie sich!'],

            // id 61
            'autobahn' => ["label" => "Autobahnraststätte",'icon' => 'autobahn',"camping" => 7,"min_dist" => 8, "max_dist" => 11, "chance" => 460, "empty" => 0.33, "drops" => [
                'pet_rat_#00' => 32,
                'food_bar2_#00' => 25,
                'food_tarte_#00' => 23,
                'food_bar1_#00' => 22,
                'food_bar3_#00' => 22,
                'food_biscuit_#00' => 22,
                'food_chick_#00' => 17,
                'food_pims_#00' => 16,
                'rhum_#00' => 13,
                'radio_off_#00' => 6,
                'coffee_#00' => 4,
                'table_#00' => 2,
            ], 'desc' => 'Früher wäre dies sicherlich einer der trendigsten Joints auf der M25 gewesen, mit verwässerten Getränken, dem Aroma von abgestandener Pisse und toten Ratten auf der Bar. Sie müssen seit Jahren der erste Mensch sein, der hier einen Fuß hinein gesetzt hat.'],

            // id 7
            'cars' => ["label" => "Autowracks",'icon' => 'cars',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 304, "empty" => 0.10, "drops" => [
                'metal_#00' => 112,
                'plate_raw_#00' => 24,
                'chest_#00' => 21,
                'tube_#00' => 21,
                'meca_parts_#00' => 12,
                'courroie_#00' => 9,
                'oilcan_#00' => 6,
                'repair_one_#00' => 5,
                'jerrycan_#00' => 4,
                'vodka_#00' => 4,
                'engine_part_#00' => 2,
                'rhum_#00' => 1,
            ], 'desc' => 'Ein Kombi, der sich in einen Kleintransporter verkeilt hat. Der großen Anzahl an verkohlten Leichen nach zu urteilen, hat hier ein Unfall eine richtig große Karambolage verursacht.'],

            // id 62
            'bar2' => ["label" => "Bar der verlorenen Hoffnungen",'icon' => 'bar2',"camping" => 9,"min_dist" => 21, "max_dist" => 28, "chance" => 41, "empty" => 0.10, "drops" => [
                'pet_dog_#00' => 10,
                'rhum_#00' => 9,
                'rp_book_#00' => 7,
                'rp_sheets_#00' => 4,
                'rp_book2_#00' => 4,
                'rp_manual_#00' => 4,
                'cigs_#00' => 3,
                'rp_scroll_#00' => 2,
            ], 'desc' => 'Diese Bar ist hinter einem kleinen Hügel an einer solchen Stelle versteckt, dass man leicht direkt daran vorbeigehen könnte, ohne es zu merken. Der Innenraum ist mit unzähligen Schwarzweiß-Portraits und Fotos geschmückt. Auf den Bildern ist oft ein Typ in gestreifter Pyjama-Kleidung zu sehen, der neben verschiedenen anderen Personen steht.'],

            // id 19
            'obi' => ["label" => "Baumarkt",'icon' => 'obi',"camping" => 7,"min_dist" => 5, "max_dist" => 8, "chance" => 409, "empty" => 0.10, "drops" => [
                'repair_kit_#00' => 74,
                'chest_#00' => 36,
                'chest_tools_#00' => 33,
                'plate_raw_#00' => 31,
                'concrete_#00' => 27,
                'electro_box_#00' => 23,
                'trestle_#00' => 22,
                'digger_#00' => 22,
                'swiss_knife_#00' => 21,
                'meca_parts_#00' => 18,
                'wrench_#00' => 10,
                'explo_#00' => 10,
                'lock_#00' => 10,
                'wire_#00' => 8,
                'oilcan_#00' => 8,
                'pile_#00' => 5,
                'pocket_belt_#00' => 4,
                'lights_#00' => 4,
                'saw_tool_part_#00' => 4,
                'tube_#00' => 4,
                'chest_xl_#00' => 2,
            ], 'desc' => 'Der Baumarkt ist das zweite Zuhause eines jeden Handwerkers. In dieser Welt avanciert er jedoch zu einem wahren Paradies! Gegenstände von unschätzbarem Wert warten nur darauf von dir entdeckt zu werden... Der Werbespruch auf dem Dach hat zudem nichts von seiner Aktualität eingebüßt: \'Plündern Sie uns bevor es andere tun!\''],

            // id 43
            'container' => ["label" => "Baustellencontainer",'icon' => 'container',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 475, "empty" => 0.05, "drops" => [
                'mecanism_#00' => 31,
                'trestle_#00' => 26,
                'jerrycan_#00' => 25,
                'chain_#00' => 24,
                'concrete_#00' => 21,
                'meca_parts_#00' => 21,
                'home_box_#00' => 19,
                'wrench_#00' => 19,
                'home_def_#00' => 18,
                'screw_#00' => 18,
                'door_#00' => 18,
                'metal_beam_#00' => 16,
                'rsc_pack_2_#00' => 14,
                'repair_kit_part_raw_#00' => 13,
                'saw_tool_part_#00' => 8,
                'oilcan_#00' => 2,
                'rsc_pack_3_#00' => 1,
            ], 'desc' => 'Dieser riesige gelbe Metallcontainer macht einen verlorenen Eindruck. Weit und breit keine Baustelle. Der Gemeinschaftsraum im Inneren ist mit leeren Bierflaschen übersät'],

            // id 23
            'doner' => ["label" => "Dönerbude Utsel-Brutzel",'icon' => 'doner',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 181, "empty" => 0.10, "drops" => [
                'meat_#00' => 15,
                'vegetable_#00' => 10,
                'jerrycan_#00' => 6,
                'chest_food_#00' => 3,
                'vodka_#00' => 1,
                //'vodka_de_#00' => 1,
                'knife_#00' => 3,
                'mixergun_part_#00' => 2,
                'pet_rat_#00' => 1,
            ], 'desc' => 'Von wegen Döner macht schöner. Scheint so als hätte der Besitzer dieser Bude das mit den Dönern und den Spießen missverstanden. Wer hier reingeht kommt garantiert nicht mehr raus.'],

            // id 24
            'duke' => ["label" => "Dukes Villa",'icon' => 'duke',"camping" => 7,"min_dist" => 12, "max_dist" => 15, "chance" => 148, "empty" => 0.10, "drops" => [
                'drug_hero_#00' => 40,
                'rhum_#00' => 27,
                'vibr_empty_#00' => 24,
                'bgrenade_empty_#00' => 16,
                'pile_#00' => 16,
                'big_pgun_part_#00' => 13,
                'sport_elec_empty_#00' => 13,
                'radius_mk2_part_#00' => 9,
                'vodka_#00' => 5,
                'chest_xl_#00' => 1,
            ], 'desc' => 'Das Heim eines gewissen Duke R. Cooke, und wenn man der Gedenktafel an der Tür glauben darf... ein Heim für Helden... dieser Ort ist viel größer als eine Villa, es ist eine voll ausgestattete Festung !'],

            // id 13
            'woods' => ["label" => "Dunkler Hain",'icon' => 'woods',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 70, "empty" => 0.00, "drops" => [
                'wood_bad_#00' => 28,
                'hmeat_#00' => 3,
                'pet_rat_#00' => 2,
                'vegetable_#00' => 2,
                'pet_chick_#00' => 2,
                'ryebag_#00' => 1,
                'plate_raw_#00' => 1,
                'saw_tool_part_#00' => 1,
                'grenade_empty_#00' => 1,
            ], 'desc' => 'Die verbrannten Überreste eines kleinen Waldes. Es war wahrscheinlich vorher eine schöne Gegend... Jetzt hoffen Sie nur noch, dass Sie hier nicht übernachten müssen.'],

            // id 52
            'mine' => ["label" => "Eingestürzte Mine",'icon' => 'mine',"camping" => 7,"min_dist" => 12, "max_dist" => 15, "chance" => 341, "empty" => 0.10, "drops" => [
                'powder_#00' => 191,
                'explo_#00' => 39,
                'deto_#00' => 37,
                'mecanism_#00' => 30,
                'concrete_wall_#00' => 11,
            ], 'desc' => 'Diese alte Mine hat es nicht vermocht den Wetterwidrigkeiten Stand zu halten. Nur Gott weiß, was die Menschen damals angetrieben hat, so tief zu graben, um der Erde nützliche Rohstoffe zu entreißen. Dabei reicht es mit den Füßen leicht am Boden zu kratzen und schon kommt eine leckere Kakerlake vorbeigehuscht. Du denkst dir: \'Lecker, die esse ich doch mal gleich\''],

            // id 30
            'quarry' => ["label" => "Eingestürzter Steinbruch",'icon' => 'quarry',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 71, "empty" => 0.30, "drops" => [
                'concrete_#00' => 9,
                'chest_tools_#00' => 9,
                'plate_raw_#00' => 7,
                'metal_beam_#00' => 6,
                'chest_#00' => 4,
                'hmeat_#00' => 3,
            ], 'desc' => 'Diese Mineralienabbauzone trägt alle Merkmale eines schrecklichen Unglücks : der Hang scheint auf die Arbeiter, Maschinen und Gebäude darunter eingestürzt zu sein.'],

            // id 59
            'ufo' => ["label" => "Ein seltsames kreisförmiges Gerät",'icon' => 'ufo',"camping" => 7,"min_dist" => 21, "max_dist" => 28, "chance" => 15, "empty" => 0.10, "drops" => [
                'metal_bad_#00' => 6,
                'plate_raw_#00' => 2,
                'iphone_#00' => 1,
            ], 'desc' => 'Das Ganze sieht wie eine komische runde Metallscheibe aus, die mal zu einen Flugzeugcockpit gehörte. Aber du bist dir nicht ganz sicher, denn es könnte sich auch um ein Mähdrescherteil handeln...'],

            // id 40
            'ekea' => ["label" => "E-KEA",'icon' => 'ekea',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 242, "empty" => 0.10, "drops" => [
                'deco_box_#00' => 49,
                'wood_plate_part_#00' => 28,
                'screw_#00' => 16,
                'table_#00' => 14,
                'trestle_#00' => 11,
                'chair_basic_#00' => 10,
                'door_#00' => 10,
                'cutter_#00' => 9,
                'bed_#00' => 8,
                'meca_parts_#00' => 6,
                'wood2_#00' => 2,
                'saw_tool_part_#00' => 1,
            ], 'desc' => 'E-KEA : Diese riesigen Geschäfte gab es früher in jeder Stadt (immer ziemlich ärgerlich am Stadtrand gelegen). Sie spezialisierten sich auf die Herstellung und den Verkauf von Billigmöbeln, denen meist ein Bolzen / Schraube / Verbindungselement fehlte. Es wird gesagt, dass die schlechte Qualität ihrer Produkte einer der Gründe für den Niedergang der Gesellschaft war...'],

            // id 28
            'tomb' => ["label" => "Familiengrab",'icon' => 'tomb',"camping" => 2,"min_dist" => 3, "max_dist" => 6, "chance" => 68, "empty" => 0.10, "drops" => [
                'hmeat_#00' => 24,
                'gun_#00' => 17,
                'machine_gun_#00' => 5,
                'pet_rat_#00' => 4,
                'digger_#00' => 3,
            ], 'desc' => 'Eine verfallene Familiengruft. Man kann den Eingang gerade noch erkennen, da er fast vollständig von verrottender Vegetation verdeckt ist. Anscheinend sind die Leichen vor einiger Zeit aufgestanden und gegangen...'],

            // id 18
            'mczombie' => ["label" => "Fast Food Restaurant",'icon' => 'mczombie',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 710, "empty" => 0.10, "drops" => [
                'coffee_#00' => 178,
                'meat_#00' => 94,
                'pharma_#00' => 28,
                'hmeat_#00' => 27,
                'food_bag_#00' => 25,
                'can_#00' => 25,
                'vegetable_#00' => 19,
                'digger_#00' => 13,
                'chest_food_#00' => 6,
                'coffee_machine_part_#00' => 2,
            ], 'desc' => 'Aus diesem Gebäude strömt ein entsetzlicher Gestank von verwesenden Leichen : Die Fleischvorräte haben sich in ekelerregende Hügel aus schimmeligem, weißem Fleisch verwandelt, aus denen eine dicke, scharfe Flüssigkeit austritt, die nun den Boden bedeckt und sogar begonnen hat, aus der Tür zu laufen...'],

            // id 2
            'plane' => ["label" => "Flugzeugwrack",'icon' => 'plane',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 155, "empty" => 0.10, "drops" => [
                'tube_#00' => 13,
                'chest_#00' => 13,
                'metal_beam_#00' => 10,
                'plate_raw_#00' => 9,
                'chest_tools_#00' => 7,
                'electro_box_#00' => 7,
                'courroie_#00' => 6,
                'metal_#00' => 6,
                'screw_#00' => 6,
                'vibr_empty_#00' => 5,
                'meca_parts_#00' => 2,
                'wire_#00' => 2,
                'tagger_#00' => 2,
                'chudol_#00' => 2,
                'radius_mk2_part_#00' => 1,
                'repair_one_#00' => 1,
            ], 'desc' => 'Dieser Langstreckenflieger ist mitten im nirgendwo abgestürzt... Da der Wüstensand das Wrack fast vollkommen eingegraben hat und sich der Zahn der Zeit in das Material gefressen hat, lässt sich nicht mehr sagen, was das Flugzeug transportierte. Du lässt deinen Blick schweifen, es sind jedoch weit und breit keine Leichen erkennbar...'],

            // id 44
            'shed' => ["label" => "Gartenhaus",'icon' => 'shed',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 624, "empty" => 0.05, "drops" => [
                'digger_#00' => 136,
                'electro_box_#00' => 62,
                'vegetable_tasty_#00' => 51,
                'jerrycan_#00' => 49,
                'chest_tools_#00' => 45,
                'lights_#00' => 25,
                'wood_log_#00' => 16,
                'rsc_pack_3_#00' => 15,
                'lawn_part_#00' => 11,
                'ryebag_#00' => 11,
                'jerrygun_part_#00' => 10,
                'concrete_#00' => 9,
                'chainsaw_part_#00' => 6,
                'angryc_#00' => 4,
                'staff_#00' => 4,
            ], 'desc' => 'Mitten auf einem völlig verfallenen Platz befindet sich ein großer Gartenschuppen. Die Tür gibt leicht nach und gibt den Blick frei auf einen riesigen Raum voller Regale und allerlei Werkzeug.'],

            // id 5
            'supermarket' => ["label" => "Geplünderte Mall",'icon' => 'supermarket',"camping" => 5,"min_dist" => 4, "max_dist" => 7, "chance" => 466, "empty" => 0.10, "drops" => [
                'cart_part_#00' => 54,
                'meat_#00' => 48,
                'grenade_empty_#00' => 47,
                'money_#00' => 22,
                'rustine_#00' => 22,
                'pile_#00' => 19,
                'repair_kit_#00' => 19,
                'water_#00' => 16,
                'can_opener_#00' => 13,
                'jerrycan_#00' => 13,
                'digger_#00' => 11,
                'drug_hero_#00' => 10,
                'chama_#00' => 9,
                'meca_parts_#00' => 8,
                'electro_box_#00' => 8,
                'rhum_#00' => 7,
                'jerrygun_part_#00' => 6,
                'mixergun_part_#00' => 5,
                'drug_random_#00' => 5,
                'bed_#00' => 3,
                'chainsaw_part_#00' => 3,
                'vodka_#00' => 3,
                'saw_tool_part_#00' => 3,
            ], 'desc' => 'Dieser riesige Haufen aus Schutt und Metall war früher mal ein hell erleuchtetes Einkaufszentrum, das vor Menschen nur so wimmelte. Das Einzige, was hier noch herumwimmelt, sind Würmer und anderes Gekreuch und Gefleuch... Du bist jedoch zuversichtlich, hier allerhand nützliche Gegenstände zu finden.'],

            // id 27
            'cave2' => ["label" => "Höhle",'icon' => 'cave2',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 73, "empty" => 0.10, "drops" => [
                'hmeat_#00' => 13,
                'chest_#00' => 13,
                'chest_tools_#00' => 9,
                'chest_citizen_#00' => 8,
                'pet_rat_#00' => 7,
                'tagger_#00' => 2,
                'pet_snake_#00' => 1,
            ], 'desc' => 'Eine Art Steinhöhle, die früher als Grabstätte oder Unterschlupf gedient haben muss... Schauen Sie sich das an. Im Inneren ist es absolut stockfinster, die Luft ist eisig und es riecht stark nach verfaulendem Fleisch...'],

            // id 37
            'cemetary' => ["label" => "Indianerfriedhof",'icon' => 'cemetary',"camping" => -5,"min_dist" => 3, "max_dist" => 6, "chance" => 181, "empty" => 0.20, "drops" => [
                'bone_#00' => 115,
                'bone_meat_#00' => 13,
                'hmeat_#00' => 7,
                'pet_rat_#00' => 3,
                'bag_#00' => 3,
                'chest_xl_#00' => 1,
            ], 'desc' => 'Ein altes indianisches Gräberfeld, das fast vollständig mit Sand und verrottender Vegetation bedeckt ist. Im Vergleich zum Rest der Welt fühlt man sich hier seltsam wohl...'],

            // id 11
            'fair' => ["label" => "Jahrmarktstand",'icon' => 'fair',"camping" => 7,"min_dist" => 5, "max_dist" => 8, "chance" => 215, "empty" => 0.10, "drops" => [
                'grenade_empty_#00' => 53,
                'watergun_empty_#00' => 18,
                'chama_#00' => 17,
                'pile_#00' => 14,
                'big_pgun_part_#00' => 10,
                'vibr_empty_#00' => 9,
                'game_box_#00' => 9,
                'watergun_opt_part_#00' => 6,
                'pilegun_empty_#00' => 6,
                'music_part_#00' => 5,
                'food_candies_#00' => 3,
                'chudol_#00' => 1,
                'cdbrit_#00' => 1,
            ], 'desc' => 'Orte wie dieser sind heutzutage ein Geschenk des Himmels... Hier gibt es garantiert alles an Plastikspielzeug, was man sich wünschen kann... und vielleicht noch ein paar andere nützliche Gadgets.'],

            // id 12
            'house' => ["label" => "Kleines Haus",'icon' => 'house',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 381, "empty" => 0.10, "drops" => [
                'pharma_#00' => 50,
                'water_#00' => 35,
                'rustine_#00' => 31,
                'food_bag_#00' => 29,
                'table_#00' => 28,
                'pet_rat_#00' => 20,
                'jerrycan_#00' => 16,
                'vegetable_#00' => 15,
                'door_carpet_#00' => 13,
                'chair_basic_#00' => 11,
                'electro_box_#00' => 11,
                'bed_#00' => 7,
                'lamp_#00' => 6,
                'chair_#00' => 3,
                'carpet_#00' => 2,
            ], 'desc' => 'Eine alte Hütte, die seit Jahren unbewohnt ist. Fast vollständig im Sand begraben, aber man hört immer noch einige beunruhigende Stöhngeräusche aus dem, was der Keller sein muss...'],

            // id 21
            'water' => ["label" => "Kleinwasserkraftwerk",'icon' => 'water',"camping" => 7,"min_dist" => 5, "max_dist" => 8, "chance" => 472, "empty" => 0.10, "drops" => [
                'jerrycan_#00' => 300,
                'water_#00' => 21,
                'jerrygun_part_#00' => 15,
                'plate_raw_#00' => 13,
            ], 'desc' => 'Das Kraftwerk sammelt das benachbarte Grundwasser in einem Stauraum. Die Energie der Bewegung des fließenden Wassers wird auf eine Turbine übertragen, wodurch dieses in Drehbewegung mit hohem Drehmoment versetzt wird. Das Filtersystem scheint kaputt zu sein, aber das schmutzige Wasser kann trotzdem eingesammelt werden.'],

            // id 6
            'lab' => ["label" => "Kosmetiklabor",'icon' => 'lab',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 180, "empty" => 0.10, "drops" => [
                'pharma_#00' => 30,
                'pet_rat_#00' => 27,
                'meat_#00' => 17,
                'pet_cat_#00' => 7,
                'pet_pig_#00' => 7,
                'sport_elec_empty_#00' => 6,
                'pet_chick_#00' => 5,
                'drug_hero_#00' => 4,
                'xanax_#00' => 4,
                'disinfect_#00' => 4,
                'drug_random_#00' => 3,
                'pet_snake_#00' => 2,
                'angryc_#00' => 2,
            ], 'desc' => 'Dieses bedrückende Gebäude diente einst als Einrichtung für Tierversuche (Kaninchen in Zwischenprüfungen etc...). Es riecht nach Kampfer, Äther und verrottenden Kadavern. Und Sie sind noch nicht einmal hineingegangen...'],

            // id 36
            'ambulance' => ["label" => "Krankenwagen",'icon' => 'ambulance',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 183, "empty" => 0.10, "drops" => [
                'drug_random_#00' => 57,
                'pharma_#00' => 46,
                'bandage_#00' => 17,
                'radius_mk2_part_#00' => 5,
                'lilboo_#00' => 4,
                'cutcut_#00' => 1,
                'saw_tool_part_#00' => 1,
            ], 'desc' => 'Dieser Krankenwagen ist mitten auf der Straße stehen geblieben. Er hat keine Reifen mehr und auch der Motor fehlt... Außerdem finden sich keinerlei Anzeichen für einen Kampf oder Unfall... Höchst seltsam...'],

            // id 47
            'warehouse' => ["label" => "Lagerhalle",'icon' => 'warehouse',"camping" => 7,"min_dist" => 15, "max_dist" => 18, "chance" => 219, "empty" => 0.10, "drops" => [
                'rsc_pack_1_#00' => 86,
                'chest_food_#00' => 84,
                'chest_tools_#00' => 67,
                'home_box_#00' => 25,
                'rsc_pack_2_#00' => 23,
                'wood_plate_part_#00' => 23,
                'book_gen_box_#00' => 16,
                'rsc_pack_3_#00' => 3,
            ], 'desc' => 'Die letzte Inventur hat hier schon vor einiger Zeit stattgefunden... Die 30 Leichen, die in Halle 2 hängen, lassen darauf vermuten, dass mit den Bilanzen etwas nicht stimmte. Dem Umfang ihrer Bäuche nach zu urteilen, handelt es sich wahrscheinlich um den Verwaltungsrat. War es ein kollektiver Selbstmord? Ihr gefesselten Hände sprechen nicht dafür.'],

            // id 14
            'carpark' => ["label" => "Leeres Parkhaus",'icon' => 'carpark',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 335, "empty" => 0.10, "drops" => [
                'metal_beam_#00' => 119,
                'repair_one_#00' => 38,
                'trestle_#00' => 33,
                'chest_#00' => 32,
                'tube_#00' => 25,
                'plate_raw_#00' => 22,
                'chest_tools_#00' => 15,
                'meca_parts_#00' => 14,
                'concrete_#00' => 13,
                'courroie_#00' => 9,
                'jerrycan_#00' => 6,
                'engine_part_#00' => 5,
            ], 'desc' => 'Ein unterirdisches Parkhaus, das fast vollständig vom Sand begraben wurde - der ideale Ort, um alleine zu sterben. Niemand wird dich hören...'],

            // id 58
            'tank' => ["label" => "Liegengebliebener Kampfpanzer",'icon' => 'tank',"camping" => 9,"min_dist" => 21, "max_dist" => 28, "chance" => 83, "empty" => 0.10, "drops" => [
                'chain_#00' => 20,
                'home_def_#00' => 16,
                'mecanism_#00' => 14,
                'powder_#00' => 9,
                'electro_box_#00' => 7,
                'tagger_#00' => 5,
                'gun_#00' => 4,
                'explo_#00' => 2,
                'deto_#00' => 2,
                'repair_kit_part_raw_#00' => 1,
                'home_box_xl_#00' => 1,
            ], 'desc' => 'Dieses militärische Vehikel ist wie die metaphorische Konservendose. Der Soldat ist drinnen und spielt die Rolle einer Sardine, und hundert Zombies draußen spielen den hungrigen Bürger. Der Bürger gewinnt...'],

            // id 51
            'motel' => ["label" => "Motel 'Dusk'",'icon' => 'motel',"camping" => 7,"min_dist" => 12, "max_dist" => 15, "chance" => 292, "empty" => 0.10, "drops" => [
                'door_carpet_#00' => 8,
                'chest_#00' => 5,
                'carpet_#00' => 4,
                'chest_food_#00' => 2,
                'lawn_part_#00' => 2,
                'coffee_#00' => 1,
                'bed_#00' => 1,
                'mecanism_#00' => 2,
                'pet_snake_#00' => 2,
            ], 'desc' => 'Beim Anblick des Gebäudes stellst du dir die Frage, wer in diesem schäbigen Motel früher übernachtet hat. Bilder und Szenen verschiedener Roadmovies schießen dir durch den Kopf: Thelma&Louise, Natural Born Killers... Du denkst dir: \'Vielleicht sollte ich als Erstes Zimmer 215 kontrollieren. Man weiß ja nie...\'.'],

            // id 55
            'army' => ["label" => "Militärischer Wachposten",'icon' => 'army',"camping" => 9,"min_dist" => 16, "max_dist" => 19, "chance" => 212, "empty" => 0.10, "drops" => [
                'coffee_#00' => 68,
                'machine_gun_#00' => 62,
                'gun_#00' => 57,
                'chest_food_#00' => 56,
                'fence_#00' => 49,
                'rsc_pack_3_#00' => 11,
                'wire_#00' => 9,
            ], 'desc' => 'Die hier stationierten Soldaten waren auf alles vorbereitet: Waffen, Vorräte und eine 150 m lange Sicherheitszone. Auf alles, außer darauf, dass ihr Leutnant sie während der Nacht verspeiste. Spaß beiseite, mit einer soliden Mauer und einer gesunden Diktatur gibt es (unter dem Gesichtspunkt des Überlebens) nichts Vergleichbares !'],

            // id 46
            'post' => ["label" => "Postfiliale",'icon' => 'post',"camping" => 7,"min_dist" => 8, "max_dist" => 11, "chance" => 177, "empty" => 0.15, "drops" => [
                'rp_letter_#00' => 41,
                'postal_box_#00' => 39,
                'book_gen_letter_#00' => 34,
                'book_gen_box_#00' => 22,
                'chair_basic_#00' => 5,
                'money_#00' => 3,
                'table_#00' => 2,
                'cards_#00' => 2,
            ], 'desc' => 'Dieses Gebäude scheint von den turbulenten Ereignissen der Vergangenheit verschont worden zu sein. Es ist noch vollkommen intakt und erinnert an ein klassisches Postbüro mit doppelten Schalterfenstern und durchsiebtem Sprechfenster. Hier wirst du kaum etwas Nützliches finden außer etwas zum Lesen...'],

            // id 33
            'cave3' => ["label" => "Räuberhöhle",'icon' => 'cave3',"camping" => 8,"min_dist" => 2, "max_dist" => 5, "chance" => 196, "empty" => 0.25, "drops" => [
                'chest_citizen_#00' => 52,
                'chest_tools_#00' => 33,
                'chest_#00' => 19,
                'money_#00' => 2,
                'chest_xl_#00' => 2,
                'chest_hero_#00' => 1,
            ], 'desc' => 'Der Zugang zu dieser Höhle ist ein notdürftig abgedecktes Loch in der Erde. Er führt in eine übergroße feuchte Grotte, die mit allerlei Trümmern und Gerümpel gefüllt ist. Höchstwahrscheinlich handelt es sich um Beutegut, das bei der Plünderung einer benachbarten Stadt eingesackt wurde. Vielleicht wurde deine Stadt mit diesem Raubgut errichtet? Und wer weiß: Womöglich haben die ersten Einwohner deiner Stadt an diesen Raubzügen teilgenommen...'],

            // id 32
            'trench' => ["label" => "Schützengraben",'icon' => 'trench',"camping" => 9,"min_dist" => 5, "max_dist" => 8, "chance" => 216, "empty" => 0.10, "drops" => [
                'concrete_#00' => 104,
                'bgrenade_empty_#00' => 33,
                'gun_#00' => 9,
                'machine_gun_#00' => 3,
            ], 'desc' => 'Dieser von Einschusskratern und schwarzen getrockneten Blutlachen übersäte Schützengraben lässt erahnen, was sich hier abgespielt hat. Der größte Teil des Grabens ist in sich zusammengestürzt, doch hier und dort erblickst du noch ein paar begehbare Stellen, die sich nach nutzbaren Gegenständen absuchen lassen.'],

            // id 45
            'dll' => ["label" => "Stadtbücherei",'icon' => 'dll',"camping" => 7,"min_dist" => 6, "max_dist" => 9, "chance" => 204, "empty" => 0.05, "drops" => [
                'deco_box_#00' => 77,
                'rp_scroll_#00' => 16,
                'rp_book_#00' => 13,
                'rp_sheets_#00' => 11,
                'chair_basic_#00' => 9,
                'rp_book2_#00' => 8,
                'rp_manual_#00' => 5,
                'pet_rat_#00' => 4,
                'cigs_#00' => 1,
                'lamp_#00' => 1,
                'lens_#00' => 1,
            ], 'desc' => 'Was einst die örtliche Bibliothek war, ist heute eine Ansammlung von mehreren kleinen Häusern. Heute sind die Bücher größtenteils zerrissen oder verbrannt, der Boden ist mit zerrissenen Seiten übersät und die Regale sind umgestoßen worden.'],

            // id 42
            'emma' => ["label" => "Tante Emma Laden",'icon' => 'emma',"camping" => 7,"min_dist" => 8, "max_dist" => 11, "chance" => 913, "empty" => 0.05, "drops" => [
                'cigs_#00' => 77,
                'jerrycan_#00' => 75,
                'can_#00' => 69,
                'drug_#00' => 69,
                'money_#00' => 69,
                'lights_#00' => 65,
                'food_bar1_#00' => 63,
                'food_noodles_#00' => 51,
                'spices_#00' => 26,
                'diode_#00' => 15,
                'carpet_#00' => 15,
                'poison_part_#00' => 12,
                'food_candies_#00' => 11,
                'chama_#00' => 9,
            ], 'desc' => 'In diesem Geschäft konnte man früher allerlei Produkte des täglichen Bedarfs kaufen: Lebensmittel, Getränke, Reinigungsmittel... An der Tür steht: Rund um die Uhr geöffnet (auch am Wochenende). In der Tat, das klaffenden Loch in der Mauer bestätigt dies.'],

            // id 56
            'mayor' => ["label" => "Truck 'Rathaus auf Rädern'",'icon' => 'mayor',"camping" => 7,"min_dist" => 16, "max_dist" => 19, "chance" => 81, "empty" => 0.10, "drops" => [
                'book_gen_letter_#00' => 4,
                'mecanism_#00' => 4,
                'rp_letter_#00' => 8,
                'rp_book2_#00' => 4,
                'rp_manual_#00' => 1,
            ], 'desc' => 'Ihr Vertreter vor Ihrer Haustür. Die Zombies stimmten diesem Konzept voll und ganz zu, wenn man die Krallenspuren auf den Polstern der Kabine und die überall versprühten menschlichen Überreste bemerkt.'],

            // id 35
            'lkw' => ["label" => "Umgekippter Laster",'icon' => 'lkw',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 177, "empty" => 0.00, "drops" => [
                'chest_food_#00' => 58,
                'chest_tools_#00' => 22,
                'wrench_#00' => 8,
                'radius_mk2_part_#00' => 7,
                'plate_raw_#00' => 6,
                'radio_off_#00' => 6,
                'rhum_#00' => 5,
                'jerrycan_#00' => 4,
                'game_box_#00' => 3,
                'mecanism_#00' => 2,
                'wire_#00' => 2,
            ], 'desc' => 'Es handelt sich um einen Transportlaster der sowjetischen Firma Transtwinï. Die Fahrerkabine hat sich komplett in einem Baum verkeilt. Der aufgeschlitzte Fahrersitz, sowie die großflächigen Blutspuren an den Wänden, lassen darauf schließen, dass der Unfall nicht die Todesursache war...'],

            // id 16
            'school' => ["label" => "Verbrannte Grundschule",'icon' => 'school',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 165, "empty" => 0.10, "drops" => [
                'hmeat_#00' => 42,
                'watergun_empty_#00' => 21,
                'pile_#00' => 13,
                'game_box_#00' => 12,
                'bandage_#00' => 5,
                'cyanure_#00' => 5,
                'watergun_opt_part_#00' => 1,
            ], 'desc' => 'Die fröhlichen Kinderzeichnungen an den Wänden stehen im starken Kontrast zu den nicht identifizierbaren menschlichen Überresten am Boden. Du hast das Gefühl, ein dunkles Kichern aus dem Bauschutt zu hören.'],

            // id 49
            'office' => ["label" => "Verfallenes Bürogebäude",'icon' => 'office',"camping" => 7,"min_dist" => 10, "max_dist" => 13, "chance" => 519, "empty" => 0.10, "drops" => [
                'mecanism_#00' => 82,
                'chair_basic_#00' => 74,
                'electro_box_#00' => 72,
                'money_#00' => 39,
                'door_#00' => 31,
                'machine_3_#00' => 13,
                'iphone_#00' => 10,
                'rp_manual_#00' => 8,
                'machine_1_#00' => 8,
                'machine_2_#00' => 8,
                'rp_sheets_#00' => 7,
                'water_can_empty_#00' => 6,
                'safe_#00' => 4,
                'food_armag_#00' => 4,
                'cigs_#00' => 1,
            ], 'desc' => 'In dieses schöne Gebäude gingen die Menschen früher zur Arbeit. Pünktlichkeit und Dresscode waren Pflicht. Die tägliche Routine bestand darin, mit einer Gruppe unbekannter Kollegen Zielvorgaben zu erreichen und um sein eigenes Überleben zu kämpfen... Hhmmm, wenn du so drüber nachdenkst: So viel hat sich gar nicht geändert - bis auf den Dresscode vielleicht.'],

            // id 4
            'villa' => ["label" => "Verfallene Villa",'icon' => 'villa',"camping" => 7,"min_dist" => 3, "max_dist" => 6, "chance" => 338, "empty" => 0.10, "drops" => [
                'can_#00' => 63,
                'pile_#00' => 32,
                'chest_citizen_#00' => 23,
                'screw_#00' => 16,
                'lock_#00' => 12,
                'table_#00' => 11,
                'door_carpet_#00' => 11,
                'pharma_#00' => 11,
                'can_opener_#00' => 8,
                'repair_kit_#00' => 8,
                'sport_elec_empty_#00' => 7,
                'chair_basic_#00' => 7,
                'chair_#00' => 7,
                'bed_#00' => 6,
                'lamp_#00' => 6,
                'carpet_#00' => 4,
                'vodka_#00' => 3,
                'rhum_#00' => 2,
                'pet_dog_#00' => 2,
            ], 'desc' => 'Jemand hat hier vor langer Zeit gelebt. Vielleicht jemand, der von einer Familie umgeben war, die ihn liebte und mit der er viele glückliche Stunden zusammen verbrachte ? Heute ist alles, was bleibt, ein wenig Staub und völlige Verwüstung... und gelegentlich eine Leiche, die mit den Zähnen knirschend auf einen zustürmt.'],

            // id 15
            'construction' => ["label" => "Verlassene Baustelle",'icon' => 'construction',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 481, "empty" => 0.10, "drops" => [
                'metal_beam_#00' => 103,
                'repair_kit_#00' => 64,
                'plate_raw_#00' => 51,
                'concrete_#00' => 50,
                'chest_#00' => 23,
                'trestle_#00' => 17,
                'screw_#00' => 13,
                'wrench_#00' => 11,
                'fence_#00' => 9,
                'radio_off_#00' => 6,
                'electro_box_#00' => 6,
                'lock_#00' => 6,
                'pocket_belt_#00' => 2,
                'chest_xl_#00' => 2,
            ], 'desc' => 'Soll das eine Schule, ein Parkhaus oder vielleicht ein Kaufhaus sein? Du kannst es nicht erkennen... Das einzige, was von diesem geheimnisvollen Projekt noch übrig ist, sind ein paar verrostete Metallstrukturen.'],

            // id 60
            'well' => ["label" => "Verlassener Brunnen",'icon' => 'well',"camping" => 1,"min_dist" => 17, "max_dist" => 20, "chance" => 221, "empty" => 0.33, "drops" => [
                'water_#00' => 121,
                'water_cup_part_#00' => 38,
                'jerrycan_#00' => 11,
            ], 'desc' => 'Wow - das ist ein verdammtes Geschenk des Himmels! Ein Brunnen, der immer noch funktioniert ! Völlig verloren in der Mitte von Nirgendwo gibt es hier niemanden mit seinem Regelwerk, der Ihnen sagt: \'Tun Sie dies nicht, tun Sie das nicht, nehmen Sie nicht zu viel Wasser, bla bla bla bla...\'. Na los, nimm einen Drink, es wird unser kleines Geheimnis sein...'],

            // id 39
            'silo' => ["label" => "Verlassene Silos",'icon' => 'silo',"camping" => 7,"min_dist" => 8, "max_dist" => 11, "chance" => 482, "empty" => 0.08, "drops" => [
                'jerrycan_#00' => 321,
            ], 'desc' => 'Ursprünglich zur Lagerung von Getreide konstruiert, aber als die Zeit verging und das Getreide knapp wurde, füllten sich die Tanks mit Regenwasser (und einer toten Ratte). Sie brauchen allerdings den richtigen Bausatz, um sie zu benutzen...'],

            // id 31
            'street' => ["label" => "Versperrte Straße",'icon' => 'street',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 42, "empty" => 0.20, "drops" => [
                'concrete_wall_#00' => 18,
                'plate_raw_#00' => 9,
                'tube_#00' => 5,
                'chest_#00' => 3,
                'trestle_#00' => 2,
                'meca_parts_#00' => 1,
                'courroie_#00' => 1,
                'repair_one_#00' => 1,
            ], 'desc' => 'Was hier passiert ist erschließt sich dir nicht so ganz... Ein riesiger Felsen ist mit voller Wucht auf die Straße geschleudert worden - doch woher kam er? Rings um dich ist nichts als Wüste...'],

            // id 29
            'park' => ["label" => "Verwilderter Park",'icon' => 'park',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 102, "empty" => 0.20, "drops" => [
                'watergun_empty_#00' => 12,
                'vegetable_#00' => 11,
                'pet_snake_#00' => 5,
                'lawn_part_#00' => 5,
                'digger_#00' => 5,
                'cutcut_#00' => 4,
                'chair_basic_#00' => 4,
                'wood2_#00' => 3,
                'game_box_#00' => 3,
                'ryebag_#00' => 1,
                'watergun_opt_part_#00' => 1,
                'pet_pig_#00' => 1,
            ], 'desc' => 'Ein Ort des Friedens und der Gelassenheit... Wenn Sie bewaffnet und bereit sind, um Ihr Leben zu kämpfen. Die umgebende Vegetation ist unheimlich und riecht stark nach Tod, unidentifizierte Kreaturen lauern im Schatten... Sie haben das überwältigende Gefühl, dass eine Kreatur aus einer Hecke ausbrechen und Sie angreifen wird.'],

            // id 22
            'guns' => ["label" => "Waffengeschäft Guns'n'Zombies",'icon' => 'guns',"camping" => 7,"min_dist" => 5, "max_dist" => 8, "chance" => 121, "empty" => 0.25, "drops" => [
                'gun_#00' => 35,
                'cutcut_#00' => 22,
                'pilegun_empty_#00' => 18,
                'knife_#00' => 15,
                'machine_gun_#00' => 11,
                'watergun_empty_#00' => 11,
                'watergun_opt_part_#00' => 8,
                'deto_#00' => 4,
                'big_pgun_part_#00' => 4,
                'chainsaw_part_#00' => 1,
            ], 'desc' => 'Wenn Sie drohen, verstümmeln oder morden wollen, haben Sie hier die Hauptader getroffen... Die in den Wänden steckenden Schrapnelle, Einschusslöcher und Trümmer ringsum geben Ihnen eine gute Vorstellung davon, welche Art von \'Ereignissen\' sich hier abgespielt haben...'],

            // id 34
            'warehouse2' => ["label" => "Warenlager",'icon' => 'warehouse2',"camping" => 7,"min_dist" => 2, "max_dist" => 5, "chance" => 181, "empty" => 0.20, "drops" => [
                'chest_food_#00' => 43,
                'chest_citizen_#00' => 34,
                'chest_tools_#00' => 31,
            ], 'desc' => 'Das Schiebetor dieses Supermarktlagers hat allen Plünderungsversuchen erfolgreich getrotzt. Durch einen etwas versteckten Seiteneingang gelangst du ins Innere und machst dich sofort auf die Suche nach Dingen, die du noch gebrauchen kannst...'],

            // id 50
            'tent' => ["label" => "Zelt eines Bürgers",'icon' => 'tent',"camping" => 11,"min_dist" => 12, "max_dist" => 15, "chance" => 202, "empty" => 0.05, "drops" => [
                'chest_hero_#00' => 72,
                'lamp_#00' => 36,
                'banned_note_#00' => 36,
                'chest_food_#00' => 33,
                'rhum_#00' => 24,
                'chest_#00' => 19,
                'home_box_#00' => 18,
                'lights_#00' => 17,
                'coffee_#00' => 15,
                'rp_letter_#00' => 9,
                'xanax_#00' => 8,
                'bandage_#00' => 8,
                'chest_citizen_#00' => 6,
                'watergun_opt_part_#00' => 3,
                'door_carpet_#00' => 3,
                'vodka_#00' => 3,
                'chama_tasty_#00' => 2,
                'bagxl_#00' => 2,
            ], 'desc' => 'Dieses Zelt macht einen wirklich soliden Eindruck und war bestimmt mal ein gutes Versteck. Derjenige, der es aufgestellt hat, wusste wie man sich vor Zombies schützt. Das Zelt verfügt über ein farblich abgestimmtes Tarnnetz, mehrere Ein- und Ausgänge, sowie über ein unterirdisches Notversteck für brenzlige Situation. Bei näherem Hinsehen entdeckst du auf der Zeltplane einen eingestickten Namen: \'Shenji\''],

            // id 17
            'pharma' => ["label" => "Zerstörte Apotheke",'icon' => 'pharma',"camping" => 7,"min_dist" => 4, "max_dist" => 7, "chance" => 458, "empty" => 0.10, "drops" => [
                'pharma_#00' => 316,
                'cyanure_#00' => 37,
                'xanax_#00' => 30,
                'drug_#00' => 28,
                'disinfect_#00' => 21,
                'digger_#00' => 19,
                'drug_hero_#00' => 16,
                'drug_random_#00' => 14,
                'bquies_#00' => 2,
            ], 'desc' => 'Mitten in der Wüste entdeckst du eine kleine Stadtviertelapotheke – grotesk! Ein unbeschreibbarer Gestank liegt in der Luft und es riecht nach allem möglichen, außer nach Gesundheit.'],

            // id 9
            'bar' => ["label" => "ZomBIER Bar",'icon' => 'bar',"camping" => 7,"min_dist" => 5, "max_dist" => 8, "chance" => 432, "empty" => 0.20, "drops" => [
                'rhum_#00' => 76,
                'meat_#00' => 60,
                'food_bag_#00' => 26,
                'pet_rat_#00' => 22,
                'chair_basic_#00' => 20,
                'drug_#00' => 17,
                'jerrycan_#00' => 16,
                'can_opener_#00' => 13,
                'vodka_#00' => 10,
            ], 'desc' => 'Es sieht eigentlich nicht mehr wie eine Bar aus, aber das halb im Sand vergrabene Schild und das Vorhandensein einiger zerbrochener Optiken lassen keinen großen Zweifel aufkommen. Die meisten Flaschen sind zerbrochen, aber Sie können hier mit ziemlicher Sicherheit etwas Nützliches finden...'],

            // Explorable Ruins.
            // id 100
            'deserted_bunker' => ["label" => "Verlassener Bunker",'icon' => 'deserted_bunker',"camping" => 1,"min_dist" => 5, "max_dist" => 100, "chance" => 0, "explorable" => true,
                "explorable_skin" => 'bunker', "explorable_desc" => null, "empty" => 1,
                "drops" => [
                    'bbplan_u_#00' => 12,
                    'bbplan_r_#00' => 7,
                    'bbplan_e_#00' => 6,
                    'water_#00' => 4,
                    'concrete_wall_#00' => 21,
                    'wood_bad_#00' => 4,
                    'kalach_#01' => 2,
                    'meca_parts_#00' => 11,
                    'wood2_#00' => 6,
                    'metal_#00' => 8,
                    'deto_#00' => 1,
                    'magneticKey_#00' => 9,
                    'money_#00' => 1,
                    'pile_#00' => 2,
                    'big_pgun_empty_#00' => 3,
                    'gun_#00' => 5,
                    'wood_log_#00' => 8,
                    'water_cup_part_#00' => 3,
                    'metal_bad_#00' => 2,
                    'metal_beam_#00' => 3,
                    'electro_box_#00' => 1,
                    'machine_gun_#00' => 1,
                    'flare_#00' => 1,
                    'wood_plate_#00' => 1,
                    'concrete_#00' => 4,
                    'rsc_pack_1_#00' => 4,
                    'rsc_pack_2_#00' => 2,
                    'rsc_pack_3_#00' => 1,
                    'wood_beam_#00' => 1,
                    'rlaunc_#00' => 1,
                    'tagger_#00' => 1,
                    'big_pgun_#00' => 1,
                    'big_pgun_part_#00' => 4,
                    'bumpKey_#00' => 3,
                    'repair_kit_part_raw_#00' => 2,
                    'classicKey_#00' => 3,
                ], 'namedDrops' => [
                    'with-toxin' => [ 'operator' => ArrayMergeDirective::Append, 'drops' => [
                        'infect_poison_part_#00' => 10
                    ] ]
                ], 'desc' => 'Diese heruntergekommene Gebäude scheint einmal ein Bunker gewesen zu sein. Du entdeckst einen Einstieg ins Gebäude, modriger Gestank schlägt dir entgegen. Du verziehst das Gesicht, aber hier könntest du mit ziemlicher Sicherheit etwas Nützliches finden...'],

            // id 101
            'deserted_hotel' => ["label" => "Verlassenes Hotel",'icon' => 'deserted_hotel',"camping" => 1,"min_dist" => 5, "max_dist" => 100, "chance" => 0, "explorable" => true,
                "explorable_skin" => 'hotel', "explorable_desc" => null, "empty" => 1,
                "drops" => [
                    'hbplan_u_#00' => 6000,
                    'hbplan_r_#00' => 4000,
                    'hbplan_e_#00' => 2000,
                    'water_#00' => 9000,
                    'bumpKey_#00' => 3000,
                    'classicKey_#00' => 3000,
                    'can_#00' => 6000,
                    'food_bag_#00' => 6000,
                    'chair_basic_#00' => 3000,
                    'table_#00' => 2000,
                    'food_bar2_#00' => 4000,
                    'spices_#00' => 3000,
                    'bed_#00' => 2000,
                    'chest_food_#00' => 3000,
                    'concrete_wall_#00' => 3000,
                    'bag_#00' => 3000,
                    'food_noodles_#00' => 4000,
                    'food_pims_#00' => 3000,
                    'food_bar1_#00' => 3000,
                    'food_bar3_#00' => 3000,
                    'food_chick_#00' => 3000,
                    'distri_#00' => 2000,
                    'rlaunc_#00' => 2000,
                    'dish_#00' => 3000,
                    'food_sandw_#00' => 3000,
                    'bureau_#00' => 2000,
                    'deco_box_#00' => 4000,
                    'lamp_#00' => 4000,
                    'teddy_#01' => 2,
                    'teddy_#00' => 2000,
                    'carpet_#00' => 2000,
                    'game_box_#00' => 2000,
                ], 'namedDrops' => [
                    'with-toxin' => [ 'operator' => ArrayMergeDirective::Append, 'drops' => [
                        'infect_poison_part_#00' => 5
                    ] ]
                ], 'desc' => 'Diese heruntergekommene Gebäude scheint einmal ein Hotel gewesen zu sein. Du entdeckst einen Einstieg ins Gebäude, modriger Gestank schlägt dir entgegen. Du verziehst das Gesicht, aber hier könntest du mit ziemlicher Sicherheit etwas Nützliches finden...'],

            // id 102
            'deserted_hospital' => ["label" => "Verlassenes Hospital",'icon' => 'deserted_hospital',"camping" => 1,"min_dist" => 5, "max_dist" => 100, "chance" => 0, "explorable" => true,
                "explorable_skin" => 'hospital', "explorable_desc" => null, "empty" => 1,
                "drops" => [
                    'mbplan_u_#00' => 20,
                    'mbplan_r_#00' => 12,
                    'mbplan_e_#00' => 5,
                    'water_#00' => 9,
                    'drug_random_#00' => 10,
                    'out_def_#00' => 14,
                    'drug_#00' => 3,
                    'xanax_#00' => 16,
                    'water_can_empty_#00' => 4,
                    'magneticKey_#00' => 9,
                    'pc_#00' => 2,
                    'drug_water_#00' => 3,
                    'distri_#00' => 2,
                    'disinfect_#00' => 6,
                    'pharma_#00' => 3,
                    'chainsaw_empty_#00' => 2,
                    'bureau_#00' => 1,
                    'cyanure_#00' => 4,
                    'classicKey_#00' => 3,
                    'water_can_3_#00' => 2,
                    'water_can_2_#00' => 2,
                    'water_can_1_#00' => 3,
                    'bed_#00' => 2,
                    'bumpKey_#00' => 3,
                    'vagoul_#00' => 3,
                ], 'namedDrops' => [
                    'with-toxin' => [ 'operator' => ArrayMergeDirective::Append, 'drops' => [
                        'infect_poison_part_#00' => 15
                    ] ]
                ], 'desc' => 'Diese heruntergekommene Gebäude scheint einmal ein Hospital gewesen zu sein. Du entdeckst einen Einstieg ins Gebäude, modriger Gestank schlägt dir entgegen. Du verziehst das Gesicht, aber hier könntest du mit ziemlicher Sicherheit etwas Nützliches finden...'],
        ]);
    }
}