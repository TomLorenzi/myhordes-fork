<?php

namespace App\DataFixtures;

use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class BeyondFixtures extends Fixture implements DependentFixtureInterface
{
    public static $zone_class_data = [
        ["label" => "Abgelegenes Haus",'icon' => 'home',"camping" => 7,"min_dist" => 1, "max_dist" => 6, "chance" => 686, "drops"=> [
            array('item' => 'can_#00','count' => 237),
            array('item' => 'chest_citizen_#00','count' => 128),
            array('item' => 'electro_box_#00','count' => 25),
            array('item' => 'chair_basic_#00','count' => 18),
            array('item' => 'lock_#00','count' => 8),
        ], 'desc' => 'Hier wohnte ein Bürger, der  sich außerhalb der Stadt niedergelassen hat, weil er den Streitigkeiten und dem Verrat, die das Stadtleben mit sich bringt, entfliehen wollte. Die Hälfte seiner Leiche liegt immer noch im Wohnzimmer.'],
        ["label" => "Albi Supermarkt",'icon' => 'albi',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 686, "drops" => [
            array('item' => 'drug_hero_#00','count' => 91),
            array('item' => 'meat_#00','count' => 91),
            array('item' => 'food_noodles_hot_#00','count' => 83),
            array('item' => 'vegetable_tasty_#00','count' => 82),
            array('item' => 'electro_box_#00','count' => 32),
            array('item' => 'door_carpet_#00','count' => 27),
            array('item' => 'food_bag_#00','count' => 23),
            array('item' => 'powder_#00','count' => 22),
            array('item' => 'lights_#00','count' => 14),
        ], 'desc' => 'Einer der vielen Albi Supermarkt, die um das Jahr 2010 herum aus dem Boden schossen und später dann verschwanden... spezialisiert darauf, Dinge so billig wie möglich zu verscherbeln. Hier findest du alles finden, was du brauchst - egal ob du einfach pleite bist oder eisern auf ein neues Stück Seife sparst, kaufe bei ALBI ein!'],
        ["label" => "Alte Höhle",'icon' => 'cave',"camping" => 7,"min_dist" => 16, "max_dist" => 27, "chance" => 184, "drops" => [
            array('item' => 'money_#00','count' => 106),
            array('item' => 'machine_1_#00','count' => 26),
            array('item' => 'machine_3_#00','count' => 25),
            array('item' => 'chair_basic_#00','count' => 25),
            array('item' => 'machine_2_#00','count' => 22),
            array('item' => 'flash_#00','count' => 21),
            array('item' => 'coffee_#00','count' => 20),
            array('item' => 'table_#00','count' => 9),
            array('item' => 'teddy_#00','count' => 4),
            array('item' => 'rp_sheets_#00','count' => 2),
            array('item' => 'rp_letter_#00','count' => 1),
            array('item' => 'radius_mk2_part_#00','count' => 1),
        ], 'desc' => 'Manche Fehler enden zwangsläufig tödlich. Nehmt als Beispiel diese Höhle. Stellt euch vor, ihr werdet von einer Zombiemeute verfolgt und eilt überstürzt in eine Höhle, um Schutz zu suchen. Ihr werdet dann folgendes Problem haben: Wie kommt ihr heil wieder raus, wenn die Biester euch gefolgt sind? Der zerfledderte Typ an der Wand dort hinten scheint dies nicht bedacht zu haben...'],
        ["label" => "Alte Hydraulikpumpe",'icon' => 'pump',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 401, "drops" => [
            array('item' => 'jerrycan_#00','count' => 331),
            array('item' => 'oilcan_#00','count' => 23),
            array('item' => 'metal_beam_#00','count' => 20),
            array('item' => 'tube_#00','count' => 18),
            array('item' => 'jerrygun_part_#00','count' => 8),
            array('item' => 'electro_#00','count' => 8),
        ], 'desc' => 'Eine alte Pumpe, die zwar vor sich hin rostet, aber dennoch in der Lage ist, in der Wüste Wasser zu schöpfen... Das einzige Problem ist, dass das Wasser, selbst wenn Sie es zum Funktionieren bringen, im Grunde genommen ungenießbar ist und in der Stadt mit den entsprechenden Geräten gereinigt werden muss.'],
        ["label" => "Alter Fahrradverleih",'icon' => 'bike',"camping" => 7,"min_dist" => 4, "max_dist" => 10, "chance" => 159, "drops" => [
            array('item' => 'pocket_belt_#00','count' => 27),
            array('item' => 'tube_#00','count' => 19),
            array('item' => 'courroie_#00','count' => 19),
            array('item' => 'radio_off_#00','count' => 7),
            array('item' => 'meca_parts_#00','count' => 6),
        ], 'desc' => 'Ein altes Fahrradverleihlager, das mit Metallstücken, Werkzeugen und allen Arten von Schutt übersät ist.'],
        ["label" => "Alter Rangierbahnhof",'icon' => 'freight',"camping" => 7,"min_dist" => 10, "max_dist" => 19, "chance" => 464, "drops" => [
            array('item' => 'metal_#00','count' => 114),
            array('item' => 'wood2_#00','count' => 113),
            array('item' => 'chain_#00','count' => 52),
            array('item' => 'metal_beam_#00','count' => 36),
            array('item' => 'wood_beam_#00','count' => 33),
            array('item' => 'wrench_#00','count' => 20),
            array('item' => 'courroie_#00','count' => 12),
            array('item' => 'coffee_#00','count' => 12),
        ], 'desc' => 'Dieser Rangierbahnhof war einmal das zentrale Drehkreuz des Landes. Waren aus aller Herren Länder wurden hier rund um die Uhr umgeladen und in alle Himmelsrichtungen versendet. Das \'weitverzweigte Netzt\' ist heute noch ungefähr 150 Meter lang, vorausgesetzt man zählt die Gleisüberbleibsel da hinten noch mit.'],
        ["label" => "Altes Feldkrankenhaus",'icon' => 'hospital',"camping" => 7,"min_dist" => 16, "max_dist" => 27, "chance" => 205, "drops" => [
            array('item' => 'drug_random_#00','count' => 67),
            array('item' => 'pharma_#00','count' => 39),
            array('item' => 'beta_drug_bad_#00','count' => 33),
            array('item' => 'disinfect_#00','count' => 26),
            array('item' => 'cyanure_#00','count' => 19),
            array('item' => 'drug_water_#00','count' => 18),
            array('item' => 'drug_hero_#00','count' => 14),
            array('item' => 'xanax_#00','count' => 12),
            array('item' => 'drug_#00','count' => 12),
            array('item' => 'fungus_#00','count' => 3),
            array('item' => 'vodka_de_#00','count' => 2),
        ], 'desc' => 'Die menschlichen Überreste, die in der Auffahrt liegen gehören den ehemaligen Patienten dieses improvisierten Krankenhauses. Schwer zu sagen, wie viele Menschen hier beim abendlichen Angriff gestorben sind... Wenn du die Anzahl der Arme durch zwei teilst, vielleicht bekommst du dann eine grobe Schätzung?'],
        ["label" => "Altes Flugfeld",'icon' => 'aerodrome',"camping" => 7,"min_dist" => 12, "max_dist" => 21, "chance" => 129, "drops" => [
            array('item' => 'metal_beam_#00','count' => 62),
            array('item' => 'electro_box_#00','count' => 28),
            array('item' => 'meca_parts_#00','count' => 24),
            array('item' => 'repair_one_#00','count' => 21),
            array('item' => 'jerrycan_#00','count' => 4),
            array('item' => 'courroie_#00','count' => 3),
            array('item' => 'fence_#00','count' => 2),
            array('item' => 'wire_#00','count' => 2),
            array('item' => 'oilcan_#00','count' => 2),
            array('item' => 'rp_manual_#00','count' => 1),
            array('item' => 'plate_raw_#00','count' => 1),
            array('item' => 'tube_#00','count' => 1),
            array('item' => 'engine_part_#00','count' => 1),
        ], 'desc' => 'Das Einzige, was auf diesem bröckelnden Flugplatz startet oder landet, sind die Fliegen. Vielleicht finden Sie etwas Nützliches, wenn Sie in den Lagerhallen herumstöbern. Zum Beispiel einen A380 in funktionstüchtigem Zustand.'],
        ["label" => "Altes Polizeipräsidium",'icon' => 'police',"camping" => 11,"min_dist" => 6, "max_dist" => 13, "chance" => 640, "drops" => [
            array('item' => 'drug_hero_#00','count' => 58),
            array('item' => 'taser_empty_#00','count' => 53),
            array('item' => 'repair_kit_#00','count' => 49),
            array('item' => 'watergun_empty_#00','count' => 46),
            array('item' => 'watergun_opt_part_#00','count' => 38),
            array('item' => 'deto_#00','count' => 37),
            array('item' => 'tagger_#00','count' => 36),
            array('item' => 'knife_#00','count' => 35),
            array('item' => 'gun_#00','count' => 35),
            array('item' => 'bed_#00','count' => 34),
            array('item' => 'cutcut_#00','count' => 34),
            array('item' => 'big_pgun_part_#00','count' => 34),
            array('item' => 'bag_#00','count' => 33),
            array('item' => 'pilegun_empty_#00','count' => 28),
            array('item' => 'bandage_#00','count' => 24),
            array('item' => 'chair_basic_#00','count' => 21),
            array('item' => 'machine_gun_#00','count' => 18),
            array('item' => 'chest_xl_#00','count' => 10),
            array('item' => 'wire_#00','count' => 6),
            array('item' => 'bagxl_#00','count' => 5),
        ], 'desc' => 'Dieses beeindruckende Gebäude erstreckt sich auf mehrere Hundert Meter. Es enthält zahlreiche Räume, die größtenteils eingestürzt sind. Die große Anzahl an Einschusslöchern in den Wänden und die improvisierten Barrikaden lassen vermuten, dass das Gebäude vor einiger Zeit Schauplatz heftiger Gefechte gewesen ist.'],
        ["label" => "Atombunker",'icon' => 'bunker',"camping" => 15,"min_dist" => 10, "max_dist" => 19, "chance" => 499, "drops" => [
            array('item' => 'drug_hero_#00','count' => 127),
            array('item' => 'tagger_#00','count' => 66),
            array('item' => 'chest_#00','count' => 60),
            array('item' => 'repair_kit_#00','count' => 54),
            array('item' => 'electro_#00','count' => 51),
            array('item' => 'taser_empty_#00','count' => 39),
            array('item' => 'pharma_#00','count' => 34),
            array('item' => 'jerrygun_part_#00','count' => 34),
            array('item' => 'jerrycan_#00','count' => 32),
            array('item' => 'mixergun_part_#00','count' => 31),
            array('item' => 'can_#00','count' => 29),
            array('item' => 'big_pgun_part_#00','count' => 26),
            array('item' => 'plate_raw_#00','count' => 24),
            array('item' => 'machine_gun_#00','count' => 16),
            array('item' => 'radius_mk2_part_#00','count' => 15),
            array('item' => 'chainsaw_part_#00','count' => 10),
            array('item' => 'chest_xl_#00','count' => 5),
        ], 'desc' => 'Die Farbe der am Bunkereingang gepinselten Zahl ist fast vollständig abgeblättert, aber es handelt sich wahrscheinlich um den Bunker 14. Im Inneren liegen überall verweste Leichen herum. Scheint so, als ob der Schließmechanismus versagt hätte. Das kommt vor.'],
        ["label" => "Atomic Cafe",'icon' => 'cafe',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 320, "drops" => [
            array('item' => 'coffee_#00','count' => 55),
            array('item' => 'food_chick_#00','count' => 55),
            array('item' => 'pet_rat_#00','count' => 30),
            array('item' => 'rhum_#00','count' => 27),
            array('item' => 'pharma_#00','count' => 17),
            array('item' => 'drug_#00','count' => 7),
            array('item' => 'vodka_de_#00','count' => 4),
            array('item' => 'coffee_machine_part_#00','count' => 1),
        ], 'desc' => 'Das Atomic Cafe ist (oder war) der Ort, an dem man sein sollte: Ein verblichenes Plakat lädt Sie zum Sommerfest am 2. Mai 2010 ein: Hawaiianisches Thema, Preis für den bestangezogenen (halbnackten Mädchen + Jungs) DJ Dave ab 13.00 Uhr, kostenloses BBQ, Biergarten mit verbessertem Look, Partyspiele, Live-Fußball, Cocktails, £2 Flaschenbier, £2 Alcopop, £1 Tequila... Beteiligen Sie sich!'],
        ["label" => "Autobahnraststätte",'icon' => 'autobahn',"camping" => 7,"min_dist" => 8, "max_dist" => 16, "chance" => 460, "drops" => [
            array('item' => 'pet_rat_#00','count' => 32),
            array('item' => 'food_bar2_#00','count' => 25),
            array('item' => 'food_tarte_#00','count' => 23),
            array('item' => 'food_bar1_#00','count' => 22),
            array('item' => 'food_bar3_#00','count' => 22),
            array('item' => 'food_biscuit_#00','count' => 22),
            array('item' => 'food_chick_#00','count' => 17),
            array('item' => 'food_pims_#00','count' => 16),
            array('item' => 'rhum_#00','count' => 13),
            array('item' => 'radio_off_#00','count' => 6),
            array('item' => 'coffee_#00','count' => 4),
            array('item' => 'table_#00','count' => 2),
        ], 'desc' => 'Früher wäre dies sicherlich einer der trendigsten Joints auf der M25 gewesen, mit verwässerten Getränken, dem Aroma von abgestandener Pisse und toten Ratten auf der Bar. Sie müssen seit Jahren der erste Mensch sein, der hier einen Fuß hinein gesetzt hat.'],
        ["label" => "Autowracks",'icon' => 'cars',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 304, "drops" => [
            array('item' => 'metal_#00','count' => 112),
            array('item' => 'plate_raw_#00','count' => 24),
            array('item' => 'chest_#00','count' => 21),
            array('item' => 'tube_#00','count' => 21),
            array('item' => 'meca_parts_#00','count' => 12),
            array('item' => 'courroie_#00','count' => 9),
            array('item' => 'oilcan_#00','count' => 6),
            array('item' => 'repair_one_#00','count' => 5),
            array('item' => 'jerrycan_#00','count' => 4),
            array('item' => 'vodka_de_#00','count' => 4),
            array('item' => 'engine_part_#00','count' => 2),
            array('item' => 'rhum_#00','count' => 1),
        ], 'desc' => 'Ein Kombi, der sich in einen Kleintransporter verkeilt hat. Der großen Anzahl an verkohlten Leichen nach zu urteilen, hat hier ein Unfall eine richtig große Karambolage verursacht.'],
        ["label" => "Bar der verlorenen Hoffnungen",'icon' => 'bar2',"camping" => 9,"min_dist" => 28, "max_dist" => 100, "chance" => 41, "drops" => [
            array('item' => 'pet_dog_#00','count' => 10),
            array('item' => 'rhum_#00','count' => 9),
            array('item' => 'rp_book_#00','count' => 7),
            array('item' => 'rp_sheets_#00','count' => 4),
            array('item' => 'rp_book2_#00','count' => 4),
            array('item' => 'rp_manual_#00','count' => 4),
            array('item' => 'cigs_#00','count' => 3),
            array('item' => 'rp_scroll_#00','count' => 2),
        ], 'desc' => 'Diese Bar ist hinter einem kleinen Hügel an einer solchen Stelle versteckt, dass man leicht direkt daran vorbeigehen könnte, ohne es zu merken. Der Innenraum ist mit unzähligen Schwarzweiß-Portraits und Fotos geschmückt. Auf den Bildern ist oft ein Typ in gestreifter Pyjama-Kleidung zu sehen, der neben verschiedenen anderen Personen steht.'],
        ["label" => "Baumarkt",'icon' => 'obi',"camping" => 7,"min_dist" => 5, "max_dist" => 12, "chance" => 409, "drops" => [
            array('item' => 'repair_kit_#00','count' => 74),
            array('item' => 'chest_#00','count' => 36),
            array('item' => 'chest_tools_#00','count' => 33),
            array('item' => 'plate_raw_#00','count' => 31),
            array('item' => 'concrete_#00','count' => 27),
            array('item' => 'electro_box_#00','count' => 23),
            array('item' => 'trestle_#00','count' => 22),
            array('item' => 'digger_#00','count' => 22),
            array('item' => 'swiss_knife_#00','count' => 21),
            array('item' => 'meca_parts_#00','count' => 18),
            array('item' => 'wrench_#00','count' => 10),
            array('item' => 'explo_#00','count' => 10),
            array('item' => 'lock_#00','count' => 10),
            array('item' => 'wire_#00','count' => 8),
            array('item' => 'oilcan_#00','count' => 8),
            array('item' => 'pile_#00','count' => 5),
            array('item' => 'pocket_belt_#00','count' => 4),
            array('item' => 'lights_#00','count' => 4),
            array('item' => 'saw_tool_part_#00','count' => 4),
            array('item' => 'tube_#00','count' => 4),
            array('item' => 'chest_xl_#00','count' => 2),
        ], 'desc' => 'Der Baumarkt ist das zweite Zuhause eines jeden Handwerkers. In dieser Welt avanciert er jedoch zu einem wahren Paradies! Gegenstände von unschätzbarem Wert warten nur darauf von dir entdeckt zu werden... Der Werbespruch auf dem Dach hat zudem nichts von seiner Aktualität eingebüßt: \'Plündern Sie uns bevor es andere tun!\''],
        ["label" => "Baustellencontainer",'icon' => 'container',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 475, "drops" => [
            array('item' => 'mecanism_#00','count' => 31),
            array('item' => 'trestle_#00','count' => 26),
            array('item' => 'jerrycan_#00','count' => 25),
            array('item' => 'chain_#00','count' => 24),
            array('item' => 'concrete_#00','count' => 21),
            array('item' => 'meca_parts_#00','count' => 21),
            array('item' => 'home_box_#00','count' => 19),
            array('item' => 'wrench_#00','count' => 19),
            array('item' => 'home_def_#00','count' => 18),
            array('item' => 'screw_#00','count' => 18),
            array('item' => 'door_#00','count' => 18),
            array('item' => 'metal_beam_#00','count' => 16),
            array('item' => 'rsc_pack_2_#00','count' => 14),
            array('item' => 'repair_kit_part_raw_#00','count' => 13),
            array('item' => 'saw_tool_part_#00','count' => 8),
            array('item' => 'oilcan_#00','count' => 2),
            array('item' => 'rsc_pack_3_#00','count' => 1),
        ], 'desc' => 'Dieser riesige gelbe Metallcontainer macht einen verlorenen Eindruck. Weit und breit keine Baustelle. Der Gemeinschaftsraum im Inneren ist mit leeren Bierflaschen übersät '],
        ["label" => "Dönerbude Utsel-Brutzel",'icon' => 'doner',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 181, "drops" => [
            //TODO
        ], 'desc' => 'Von wegen Döner macht schöner. Scheint so als hätte der Besitzer dieser Bude das mit den Dönern und den Spießen missverstanden. Wer hier reingeht kommt garantiert nicht mehr raus. '],
        ["label" => "Dukes Villa",'icon' => 'duke',"camping" => 7,"min_dist" => 12, "max_dist" => 21, "chance" => 148, "drops" => [
            array('item' => 'drug_hero_#00','count' => 40),
            array('item' => 'rhum_#00','count' => 27),
            array('item' => 'vibr_empty_#00','count' => 24),
            array('item' => 'bgrenade_empty_#00','count' => 16),
            array('item' => 'pile_#00','count' => 16),
            array('item' => 'big_pgun_part_#00','count' => 13),
            array('item' => 'sport_elec_empty_#00','count' => 13),
            array('item' => 'radius_mk2_part_#00','count' => 9),
            array('item' => 'vodka_de_#00','count' => 5),
            array('item' => 'chest_xl_#00','count' => 1),
        ], 'desc' => 'Das Heim eines gewissen Duke R. Cooke, und wenn man der Gedenktafel an der Tür glauben darf... ein Heim für Helden... dieser Ort ist viel größer als eine Villa, es ist eine voll ausgestattete Festung !'],
        ["label" => "Dunkler Hain",'icon' => 'woods',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 70, "drops" => [
            array('item' => 'wood_bad_#00','count' => 28),
            array('item' => 'hmeat_#00','count' => 3),
            array('item' => 'pet_rat_#00','count' => 2),
            array('item' => 'vegetable_#00','count' => 2),
            array('item' => 'pet_chick_#00','count' => 2),
            array('item' => 'ryebag_#00','count' => 1),
            array('item' => 'plate_raw_#00','count' => 1),
            array('item' => 'saw_tool_part_#00','count' => 1),
            array('item' => 'grenade_empty_#00','count' => 1),
        ], 'desc' => 'Die verbrannten Überreste eines kleinen Waldes. Es war wahrscheinlich vorher eine schöne Gegend... Jetzt hoffen Sie nur noch, dass Sie hier nicht übernachten müssen.'],
        ["label" => "Eingestürzte Mine",'icon' => 'mine',"camping" => 7,"min_dist" => 12, "max_dist" => 21, "chance" => 341, "drops" => [
            array('item' => 'powder_#00','count' => 191),
            array('item' => 'explo_#00','count' => 39),
            array('item' => 'deto_#00','count' => 37),
            array('item' => 'mecanism_#00','count' => 30),
            array('item' => 'concrete_wall_#00','count' => 11),
        ], 'desc' => 'Diese alte Mine hat es nicht vermocht den Wetterwidrigkeiten Stand zu halten. Nur Gott weiß, was die Menschen damals angetrieben hat, so tief zu graben, um der Erde nützliche Rohstoffe zu entreißen. Dabei reicht es mit den Füßen leicht am Boden zu kratzen und schon kommt eine leckere Kakerlake vorbeigehuscht. Du denkst dir: \'Lecker, die esse ich doch mal gleich\''],
        ["label" => "Eingestürzter Steinbruch",'icon' => 'quarry',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 71, "drops" => [
            array('item' => 'concrete_#00','count' => 9),
            array('item' => 'chest_tools_#00','count' => 9),
            array('item' => 'plate_raw_#00','count' => 7),
            array('item' => 'metal_beam_#00','count' => 6),
            array('item' => 'chest_#00','count' => 4),
            array('item' => 'hmeat_#00','count' => 3),
        ], 'desc' => 'Diese Mineralienabbauzone trägt alle Merkmale eines schrecklichen Unglücks : der Hang scheint auf die Arbeiter, Maschinen und Gebäude darunter eingestürzt zu sein.'],
        ["label" => "Ein seltsames kreisförmiges Gerät",'icon' => 'ufo',"camping" => 15,"min_dist" => 25, "max_dist" => 100, "chance" => 15, "drops" => [
            array('item' => 'metal_bad_#00','count' => 6),
            array('item' => 'plate_raw_#00','count' => 2),
            array('item' => 'iphone_#00','count' => 1),
        ], 'desc' => 'Das Ganze sieht wie eine komische runde Metallscheibe aus, die mal zu einen Flugzeugcockpit gehörte. Aber du bist dir nicht ganz sicher, denn es könnte sich auch um ein Mähdrescherteil handeln...'],
        ["label" => "E-KEA",'icon' => 'ekea',"camping" => 7,"min_dist" => 4, "max_dist" => 10, "chance" => 242, "drops" => [
            array('item' => 'deco_box_#00','count' => 49),
            array('item' => 'wood_plate_part_#00','count' => 28),
            array('item' => 'screw_#00','count' => 16),
            array('item' => 'table_#00','count' => 14),
            array('item' => 'trestle_#00','count' => 11),
            array('item' => 'chair_basic_#00','count' => 10),
            array('item' => 'door_#00','count' => 10),
            array('item' => 'cutter_#00','count' => 9),
            array('item' => 'bed_#00','count' => 8),
            array('item' => 'meca_parts_#00','count' => 6),
            array('item' => 'wood2_#00','count' => 2),
            array('item' => 'saw_tool_part_#00','count' => 1),
        ], 'desc' => 'E-KEA : Diese riesigen Geschäfte gab es früher in jeder Stadt (immer ziemlich ärgerlich am Stadtrand gelegen). Sie spezialisierten sich auf die Herstellung und den Verkauf von Billigmöbeln, denen meist ein Bolzen / Schraube / Verbindungselement fehlte. Es wird gesagt, dass die schlechte Qualität ihrer Produkte einer der Gründe für den Niedergang der Gesellschaft war...'],
        ["label" => "Familiengrab",'icon' => 'tomb',"camping" => 0,"min_dist" => 3, "max_dist" => 9, "chance" => 68, "drops" => [
            array('item' => 'hmeat_#00','count' => 24),
            array('item' => 'gun_#00','count' => 17),
            array('item' => 'machine_gun_#00','count' => 5),
            array('item' => 'pet_rat_#00','count' => 4),
            array('item' => 'digger_#00','count' => 3),
        ], 'desc' => 'Eine verfallene Familiengruft. Man kann den Eingang gerade noch erkennen, da er fast vollständig von verrottender Vegetation verdeckt ist. Anscheinend sind die Leichen vor einiger Zeit aufgestanden und gegangen...'],
        ["label" => "Fast Food Restaurant",'icon' => 'mczombie',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 710, "drops" => [
            array('item' => 'coffee_#00','count' => 178),
            array('item' => 'meat_#00','count' => 94),
            array('item' => 'pharma_#00','count' => 28),
            array('item' => 'hmeat_#00','count' => 27),
            array('item' => 'food_bag_#00','count' => 25),
            array('item' => 'can_#00','count' => 25),
            array('item' => 'vegetable_#00','count' => 19),
            array('item' => 'digger_#00','count' => 13),
            array('item' => 'chest_food_#00','count' => 6),
            array('item' => 'coffee_machine_part_#00','count' => 2),
        ], 'desc' => 'Aus diesem Gebäude strömt ein entsetzlicher Gestank von verwesenden Leichen : Die Fleischvorräte haben sich in ekelerregende Hügel aus schimmeligem, weißem Fleisch verwandelt, aus denen eine dicke, scharfe Flüssigkeit austritt, die nun den Boden bedeckt und sogar begonnen hat, aus der Tür zu laufen...'],
        ["label" => "Flugzeugwrack",'icon' => 'plane',"camping" => 9,"min_dist" => 4, "max_dist" => 10, "chance" => 155, "drops" => [
            array('item' => 'tube_#00','count' => 13),
            array('item' => 'chest_#00','count' => 13),
            array('item' => 'metal_beam_#00','count' => 10),
            array('item' => 'plate_raw_#00','count' => 9),
            array('item' => 'chest_tools_#00','count' => 7),
            array('item' => 'electro_box_#00','count' => 7),
            array('item' => 'courroie_#00','count' => 6),
            array('item' => 'metal_#00','count' => 6),
            array('item' => 'screw_#00','count' => 6),
            array('item' => 'vibr_empty_#00','count' => 5),
            array('item' => 'meca_parts_#00','count' => 2),
            array('item' => 'wire_#00','count' => 2),
            array('item' => 'tagger_#00','count' => 2),
            array('item' => 'chudol_#00','count' => 2),
            array('item' => 'radius_mk2_part_#00','count' => 1),
            array('item' => 'repair_one_#00','count' => 1),
        ], 'desc' => 'Dieser Langstreckenflieger ist mitten im nirgendwo abgestürzt... Da der Wüstensand das Wrack fast vollkommen eingegraben hat und sich der Zahn der Zeit in das Material gefressen hat, lässt sich nicht mehr sagen, was das Flugzeug transportierte. Du lässt deinen Blick schweifen, es sind jedoch weit und breit keine Leichen erkennbar...'],
        ["label" => "Gartenhaus",'icon' => 'shed',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 624, "drops" => [
            array('item' => 'digger_#00','count' => 136),
            array('item' => 'electro_box_#00','count' => 62),
            array('item' => 'vegetable_tasty_#00','count' => 51),
            array('item' => 'jerrycan_#00','count' => 49),
            array('item' => 'chest_tools_#00','count' => 45),
            array('item' => 'lights_#00','count' => 25),
            array('item' => 'wood_log_#00','count' => 16),
            array('item' => 'rsc_pack_3_#00','count' => 15),
            array('item' => 'lawn_part_#00','count' => 11),
            array('item' => 'ryebag_#00','count' => 11),
            array('item' => 'jerrygun_part_#00','count' => 10),
            array('item' => 'concrete_#00','count' => 9),
            array('item' => 'chainsaw_part_#00','count' => 6),
            array('item' => 'angryc_#00','count' => 4),
            array('item' => 'staff_#00','count' => 4),
        ], 'desc' => 'Mitten auf einem völlig verfallenen Platz befindet sich ein großer Gartenschuppen. Die Tür gibt leicht nach und gibt den Blick frei auf einen riesigen Raum voller Regale und allerlei Werkzeug.'],
        ["label" => "Geplünderte Mall",'icon' => 'supermarket',"camping" => 5,"min_dist" => 4, "max_dist" => 10, "chance" => 466, "drops" => [
            array('item' => 'cart_part_#00','count' => 54),
            array('item' => 'meat_#00','count' => 48),
            array('item' => 'grenade_empty_#00','count' => 47),
            array('item' => 'money_#00','count' => 22),
            array('item' => 'rustine_#00','count' => 22),
            array('item' => 'pile_#00','count' => 19),
            array('item' => 'repair_kit_#00','count' => 19),
            array('item' => 'water_#00','count' => 16),
            array('item' => 'can_opener_#00','count' => 13),
            array('item' => 'jerrycan_#00','count' => 13),
            array('item' => 'digger_#00','count' => 11),
            array('item' => 'drug_hero_#00','count' => 10),
            array('item' => 'chama_#00','count' => 9),
            array('item' => 'meca_parts_#00','count' => 8),
            array('item' => 'electro_box_#00','count' => 8),
            array('item' => 'rhum_#00','count' => 7),
            array('item' => 'jerrygun_part_#00','count' => 6),
            array('item' => 'mixergun_part_#00','count' => 5),
            array('item' => 'drug_random_#00','count' => 5),
            array('item' => 'bed_#00','count' => 3),
            array('item' => 'chainsaw_part_#00','count' => 3),
            array('item' => 'vodka_de_#00','count' => 3),
            array('item' => 'saw_tool_part_#00','count' => 3),
        ], 'desc' => 'Dieser riesige Haufen aus Schutt und Metall war früher mal ein hell erleuchtetes Einkaufszentrum, das vor Menschen nur so wimmelte. Das Einzige, was hier noch herumwimmelt, sind Würmer und anderes Gekreuch und Gefleuch... Du bist jedoch zuversichtlich, hier allerhand nützliche Gegenstände zu finden.'],
        ["label" => "Höhle",'icon' => 'cave2',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 73, "drops" => [
            array('item' => 'hmeat_#00','count' => 13),
            array('item' => 'chest_#00','count' => 13),
            array('item' => 'chest_tools_#00','count' => 9),
            array('item' => 'chest_citizen_#00','count' => 8),
            array('item' => 'pet_rat_#00','count' => 7),
            array('item' => 'tagger_#00','count' => 2),
            array('item' => 'pet_snake_#00','count' => 1),
        ], 'desc' => 'Eine Art Steinhöhle, die früher als Grabstätte oder Unterschlupf gedient haben muss... Schauen Sie sich das an. Im Inneren ist es absolut stockfinster, die Luft ist eisig und es riecht stark nach verfaulendem Fleisch...'],
        ["label" => "Indianerfriedhof",'icon' => 'cemetary',"camping" => -5,"min_dist" => 3, "max_dist" => 9, "chance" => 181, "drops" => [
            array('item' => 'bone_#00','count' => 115),
            array('item' => 'bone_meat_#00','count' => 13),
            array('item' => 'hmeat_#00','count' => 7),
            array('item' => 'pet_rat_#00','count' => 3),
            array('item' => 'bag_#00','count' => 3),
            array('item' => 'chest_xl_#00','count' => 1),
        ], 'desc' => 'Ein altes indianisches Gräberfeld, das fast vollständig mit Sand und verrottender Vegetation bedeckt ist. Im Vergleich zum Rest der Welt fühlt man sich hier seltsam wohl...'],
        ["label" => "Jahrmarktstand",'icon' => 'fair',"camping" => 7,"min_dist" => 5, "max_dist" => 12, "chance" => 215, "drops" => [
            array('item' => 'grenade_empty_#00','count' => 53),
            array('item' => 'watergun_empty_#00','count' => 18),
            array('item' => 'chama_#00','count' => 17),
            array('item' => 'pile_#00','count' => 14),
            array('item' => 'big_pgun_part_#00','count' => 10),
            array('item' => 'vibr_empty_#00','count' => 9),
            array('item' => 'game_box_#00','count' => 9),
            array('item' => 'watergun_opt_part_#00','count' => 6),
            array('item' => 'pilegun_empty_#00','count' => 6),
            array('item' => 'music_part_#00','count' => 5),
            array('item' => 'food_candies_#00','count' => 3),
            array('item' => 'chudol_#00','count' => 1),
            array('item' => 'cdbrit_#00','count' => 1),
        ], 'desc' => 'Orte wie dieser sind heutzutage ein Geschenk des Himmels... Hier gibt es garantiert alles an Plastikspielzeug, was man sich wünschen kann... und vielleicht noch ein paar andere nützliche Gadgets.'],
        ["label" => "Kleines Haus",'icon' => 'house',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 381, "drops" => [
            array('item' => 'pharma_#00','count' => 50),
            array('item' => 'water_#00','count' => 35),
            array('item' => 'rustine_#00','count' => 31),
            array('item' => 'food_bag_#00','count' => 29),
            array('item' => 'table_#00','count' => 28),
            array('item' => 'pet_rat_#00','count' => 20),
            array('item' => 'jerrycan_#00','count' => 16),
            array('item' => 'vegetable_#00','count' => 15),
            array('item' => 'door_carpet_#00','count' => 13),
            array('item' => 'chair_basic_#00','count' => 11),
            array('item' => 'electro_box_#00','count' => 11),
            array('item' => 'bed_#00','count' => 7),
            array('item' => 'lamp_#00','count' => 6),
            array('item' => 'chair_#00','count' => 3),
            array('item' => 'carpet_#00','count' => 2),
        ], 'desc' => 'Eine alte Hütte, die seit Jahren unbewohnt ist. Fast vollständig im Sand begraben, aber man hört immer noch einige beunruhigende Stöhngeräusche aus dem, was der Keller sein muss...'],
        ["label" => "Kleinwasserkraftwerk",'icon' => 'water',"camping" => 7,"min_dist" => 5, "max_dist" => 12, "chance" => 472, "drops" => [
            array('item' => 'jerrycan_#00','count' => 300),
            array('item' => 'water_#00','count' => 21),
            array('item' => 'jerrygun_part_#00','count' => 15),
            array('item' => 'plate_raw_#00','count' => 13),
        ], 'desc' => 'Das Kraftwerk sammelt das benachbarte Grundwasser in einem Stauraum. Die Energie der Bewegung des fließenden Wassers wird auf eine Turbine übertragen, wodurch dieses in Drehbewegung mit hohem Drehmoment versetzt wird. Das Filtersystem scheint kaputt zu sein, aber das schmutzige Wasser kann trotzdem eingesammelt werden.'],
        ["label" => "Kosmetiklabor",'icon' => 'lab',"camping" => 9,"min_dist" => 2, "max_dist" => 7, "chance" => 180, "drops" => [
            array('item' => 'pharma_#00','count' => 30),
            array('item' => 'pet_rat_#00','count' => 27),
            array('item' => 'meat_#00','count' => 17),
            array('item' => 'pet_cat_#00','count' => 7),
            array('item' => 'pet_pig_#00','count' => 7),
            array('item' => 'sport_elec_empty_#00','count' => 6),
            array('item' => 'pet_chick_#00','count' => 5),
            array('item' => 'drug_hero_#00','count' => 4),
            array('item' => 'xanax_#00','count' => 4),
            array('item' => 'disinfect_#00','count' => 4),
            array('item' => 'drug_random_#00','count' => 3),
            array('item' => 'pet_snake_#00','count' => 2),
            array('item' => 'angryc_#00','count' => 2),
        ], 'desc' => 'Dieses bedrückende Gebäude diente einst als Einrichtung für Tierversuche (Kaninchen in Zwischenprüfungen etc...). Es riecht nach Kampfer, Äther und verrottenden Kadavern. Und Sie sind noch nicht einmal hineingegangen...'],
        ["label" => "Krankenwagen",'icon' => 'ambulance',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 183, "drops" => [
            array('item' => 'drug_random_#00','count' => 57),
            array('item' => 'pharma_#00','count' => 46),
            array('item' => 'bandage_#00','count' => 17),
            array('item' => 'radius_mk2_part_#00','count' => 5),
            array('item' => 'lilboo_#00','count' => 4),
            array('item' => 'cutcut_#00','count' => 1),
            array('item' => 'saw_tool_part_#00','count' => 1),
        ], 'desc' => 'Dieser Krankenwagen ist mitten auf der Straße stehen geblieben. Er hat keine Reifen mehr und auch der Motor fehlt... Außerdem finden sich keinerlei Anzeichen für einen Kampf oder Unfall... Höchst seltsam...'],
        ["label" => "Lagerhalle",'icon' => 'warehouse',"camping" => 7,"min_dist" => 15, "max_dist" => 26, "chance" => 219, "drops" => [
            array('item' => 'rsc_pack_1_#00','count' => 86),
            array('item' => 'chest_food_#00','count' => 84),
            array('item' => 'chest_tools_#00','count' => 67),
            array('item' => 'home_box_#00','count' => 25),
            array('item' => 'rsc_pack_2_#00','count' => 23),
            array('item' => 'wood_plate_part_#00','count' => 23),
            array('item' => 'book_gen_box_#00','count' => 16),
            array('item' => 'rsc_pack_3_#00','count' => 3),
        ], 'desc' => 'Die letzte Inventur hat hier schon vor einiger Zeit stattgefunden... Die 30 Leichen, die in Halle 2 hängen, lassen darauf vermuten, dass mit den Bilanzen etwas nicht stimmte. Dem Umfang ihrer Bäuche nach zu urteilen, handelt es sich wahrscheinlich um den Verwaltungsrat. War es ein kollektiver Selbstmord? Ihr gefesselten Hände sprechen nicht dafür.'],
        ["label" => "Leeres Parkhaus",'icon' => 'carpark',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 335, "drops" => [
            array('item' => 'metal_beam_#00','count' => 119),
            array('item' => 'repair_one_#00','count' => 38),
            array('item' => 'trestle_#00','count' => 33),
            array('item' => 'chest_#00','count' => 32),
            array('item' => 'tube_#00','count' => 25),
            array('item' => 'plate_raw_#00','count' => 22),
            array('item' => 'chest_tools_#00','count' => 15),
            array('item' => 'meca_parts_#00','count' => 14),
            array('item' => 'concrete_#00','count' => 13),
            array('item' => 'courroie_#00','count' => 9),
            array('item' => 'jerrycan_#00','count' => 6),
            array('item' => 'engine_part_#00','count' => 5),
        ], 'desc' => 'Ein unterirdisches Parkhaus, das fast vollständig vom Sand begraben wurde - der ideale Ort, um alleine zu sterben. Niemand wird dich hören...'],
        ["label" => "Liegengebliebener Kampfpanzer",'icon' => 'tank',"camping" => 9,"min_dist" => 25, "max_dist" => 100, "chance" => 83, "drops" => [
            array('item' => 'chain_#00','count' => 20),
            array('item' => 'home_def_#00','count' => 16),
            array('item' => 'mecanism_#00','count' => 14),
            array('item' => 'powder_#00','count' => 9),
            array('item' => 'electro_box_#00','count' => 7),
            array('item' => 'tagger_#00','count' => 5),
            array('item' => 'gun_#00','count' => 4),
            array('item' => 'explo_#00','count' => 2),
            array('item' => 'deto_#00','count' => 2),
            array('item' => 'repair_kit_part_raw_#00','count' => 1),
            array('item' => 'home_box_xl_#00','count' => 1),
        ], 'desc' => 'Dieses militärische Vehikel ist wie die metaphorische Konservendose. Der Soldat ist drinnen und spielt die Rolle einer Sardine, und hundert Zombies draußen spielen den hungrigen Bürger. Der Bürger gewinnt...'],
        ["label" => "Motel 'Dusk'",'icon' => 'motel',"camping" => 7,"min_dist" => 12, "max_dist" => 21, "chance" => 292, "drops" => [
            // ToDo
        ], 'desc' => 'Beim Anblick des Gebäudes stellst du dir die Frage, wer in diesem schäbigen Motel früher übernachtet hat. Bilder und Szenen verschiedener Roadmovies schießen dir durch den Kopf: Thelma&Louise, Natural Born Killers... Du denkst dir: \'Vielleicht sollte ich als Erstes Zimmer 215 kontrollieren. Man weiß ja nie...\'.'],
        ["label" => "Militärischer Wachposten",'icon' => 'army',"camping" => 9,"min_dist" => 16, "max_dist" => 27, "chance" => 212, "drops" => [
            array('item' => 'coffee_#00','count' => 68),
            array('item' => 'machine_gun_#00','count' => 62),
            array('item' => 'gun_#00','count' => 57),
            array('item' => 'chest_food_#00','count' => 56),
            array('item' => 'fence_#00','count' => 49),
            array('item' => 'rsc_pack_3_#00','count' => 11),
            array('item' => 'wire_#00','count' => 9),
        ], 'desc' => 'Die hier stationierten Soldaten waren auf alles vorbereitet: Waffen, Vorräte und eine 150 m lange Sicherheitszone. Auf alles, außer darauf, dass ihr Leutnant sie während der Nacht verspeiste. Spaß beiseite, mit einer soliden Mauer und einer gesunden Diktatur gibt es (unter dem Gesichtspunkt des Überlebens) nichts Vergleichbares !'],
        ["label" => "Postfiliale",'icon' => 'post',"camping" => 7,"min_dist" => 8, "max_dist" => 16, "chance" => 177, "drops" => [
            array('item' => 'rp_letter_#00','count' => 41),
            array('item' => 'postal_box_#00','count' => 39),
            array('item' => 'book_gen_letter_#00','count' => 34),
            array('item' => 'book_gen_box_#00','count' => 22),
            array('item' => 'chair_basic_#00','count' => 5),
            array('item' => 'money_#00','count' => 3),
            array('item' => 'table_#00','count' => 2),
            array('item' => 'cards_#00','count' => 2),
        ], 'desc' => 'Dieses Gebäude scheint von den turbulenten Ereignissen der Vergangenheit verschont worden zu sein. Es ist noch vollkommen intakt und erinnert an ein klassisches Postbüro mit doppelten Schalterfenstern und durchsiebtem Sprechfenster. Hier wirst du kaum etwas Nützliches finden außer etwas zum Lesen...'],
        ["label" => "Räuberhöhle",'icon' => 'cave3',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 196, "drops" => [
            array('item' => 'chest_citizen_#00','count' => 52),
            array('item' => 'chest_tools_#00','count' => 33),
            array('item' => 'chest_#00','count' => 19),
            array('item' => 'money_#00','count' => 2),
            array('item' => 'chest_xl_#00','count' => 2),
            array('item' => 'chest_hero_#00','count' => 1),
        ], 'desc' => 'Der Zugang zu dieser Höhle ist ein notdürftig abgedecktes Loch in der Erde. Er führt in eine übergroße feuchte Grotte, die mit allerlei Trümmern und Gerümpel gefüllt ist. Höchstwahrscheinlich handelt es sich um Beutegut, das bei der Plünderung einer benachbarten Stadt eingesackt wurde. Vielleicht wurde deine Stadt mit diesem Raubgut errichtet? Und wer weiß: Womöglich haben die ersten Einwohner deiner Stadt an diesen Raubzügen teilgenommen...'],
        ["label" => "Schützengraben",'icon' => 'trench',"camping" => 9,"min_dist" => 5, "max_dist" => 12, "chance" => 216, "drops" => [
            array('item' => 'concrete_#00','count' => 104),
            array('item' => 'bgrenade_empty_#00','count' => 33),
            array('item' => 'gun_#00','count' => 9),
            array('item' => 'machine_gun_#00','count' => 3),
        ], 'desc' => 'Dieser von Einschusskratern und schwarzen getrockneten Blutlachen übersäte Schützengraben lässt erahnen, was sich hier abgespielt hat. Der größte Teil des Grabens ist in sich zusammengestürzt, doch hier und dort erblickst du noch ein paar begehbare Stellen, die sich nach nutzbaren Gegenständen absuchen lassen.'],
        ["label" => "Stadtbücherei",'icon' => 'dll',"camping" => 7,"min_dist" => 6, "max_dist" => 13, "chance" => 204, "drops" => [
            array('item' => 'deco_box_#00','count' => 77),
            array('item' => 'rp_scroll_#00','count' => 16),
            array('item' => 'rp_book_#00','count' => 13),
            array('item' => 'rp_sheets_#00','count' => 11),
            array('item' => 'chair_basic_#00','count' => 9),
            array('item' => 'rp_book2_#00','count' => 8),
            array('item' => 'rp_manual_#00','count' => 5),
            array('item' => 'pet_rat_#00','count' => 4),
            array('item' => 'cigs_#00','count' => 1),
            array('item' => 'lamp_#00','count' => 1),
            array('item' => 'lens_#00','count' => 1),
        ], 'desc' => 'What was once the local library is now a collection of several small houses. Today, the books are mostly torn or burnt, the ground is littered with torn pages and the shelves have been knocked over.'],
        ["label" => "Tante Emma Laden",'icon' => 'emma',"camping" => 7,"min_dist" => 8, "max_dist" => 16, "chance" => 913, "drops" => [
            array('item' => 'cigs_#00','count' => 77),
            array('item' => 'jerrycan_#00','count' => 75),
            array('item' => 'can_#00','count' => 69),
            array('item' => 'drug_#00','count' => 69),
            array('item' => 'money_#00','count' => 69),
            array('item' => 'lights_#00','count' => 65),
            array('item' => 'food_bar1_#00','count' => 63),
            array('item' => 'food_noodles_#00','count' => 51),
            array('item' => 'spices_#00','count' => 26),
            array('item' => 'diode_#00','count' => 15),
            array('item' => 'carpet_#00','count' => 15),
            array('item' => 'poison_part_#00','count' => 12),
            array('item' => 'food_candies_#00','count' => 11),
            array('item' => 'chama_#00','count' => 9),
        ], 'desc' => 'In diesem Geschäft konnte man früher allerlei Produkte des täglichen Bedarfs kaufen: Lebensmittel, Getränke, Reinigungsmittel... An der Tür steht: Rund um die Uhr geöffnet (auch am Wochenende). In der Tat, das klaffenden Loch in der Mauer bestätigt dies.'],
        ["label" => "Truck 'Rathaus auf Rädern'",'icon' => 'mayor',"camping" => 7,"min_dist" => 16, "max_dist" => 27, "chance" => 81, "drops" => [
            // ToDO
        ], 'desc' => 'Ihr Vertreter vor Ihrer Haustür. Die Zombies stimmten diesem Konzept voll und ganz zu, wenn man die Krallenspuren auf den Polstern der Kabine und die überall versprühten menschlichen Überreste bemerkt.'],
        ["label" => "Umgekippter Laster",'icon' => 'lkw',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 177, "drops" => [
            array('item' => 'chest_food_#00','count' => 58),
            array('item' => 'chest_tools_#00','count' => 22),
            array('item' => 'wrench_#00','count' => 8),
            array('item' => 'radius_mk2_part_#00','count' => 7),
            array('item' => 'plate_raw_#00','count' => 6),
            array('item' => 'radio_off_#00','count' => 6),
            array('item' => 'rhum_#00','count' => 5),
            array('item' => 'jerrycan_#00','count' => 4),
            array('item' => 'game_box_#00','count' => 3),
            array('item' => 'mecanism_#00','count' => 2),
            array('item' => 'wire_#00','count' => 2),
        ], 'desc' => 'Es handelt sich um einen Transportlaster der sowjetischen Firma Transtwinï. Die Fahrerkabine hat sich komplett in einem Baum verkeilt. Der aufgeschlitzte Fahrersitz, sowie die großflächigen Blutspuren an den Wänden, lassen darauf schließen, dass der Unfall nicht die Todesursache war...'],
        ["label" => "Verbrannte Grundschule",'icon' => 'school',"camping" => 9,"min_dist" => 3, "max_dist" => 9, "chance" => 165, "drops" => [
            array('item' => 'hmeat_#00','count' => 42),
            array('item' => 'watergun_empty_#00','count' => 21),
            array('item' => 'pile_#00','count' => 13),
            array('item' => 'game_box_#00','count' => 12),
            array('item' => 'bandage_#00','count' => 5),
            array('item' => 'cyanure_#00','count' => 5),
            array('item' => 'watergun_opt_part_#00','count' => 1),
        ], 'desc' => 'Die fröhlichen Kinderzeichnungen an den Wänden stehen im starken Kontrast zu den nicht identifizierbaren menschlichen Überresten am Boden. Du hast das Gefühl, ein dunkles Kichern aus dem Bauschutt zu hören.'],
        ["label" => "Verfallenes Bürogebäude",'icon' => 'office',"camping" => 7,"min_dist" => 10, "max_dist" => 19, "chance" => 519, "drops" => [
            array('item' => 'mecanism_#00','count' => 82),
            array('item' => 'chair_basic_#00','count' => 74),
            array('item' => 'electro_box_#00','count' => 72),
            array('item' => 'money_#00','count' => 39),
            array('item' => 'door_#00','count' => 31),
            array('item' => 'machine_3_#00','count' => 13),
            array('item' => 'iphone_#00','count' => 10),
            array('item' => 'rp_manual_#00','count' => 8),
            array('item' => 'machine_1_#00','count' => 8),
            array('item' => 'machine_2_#00','count' => 8),
            array('item' => 'rp_sheets_#00','count' => 7),
            array('item' => 'water_can_empty_#00','count' => 6),
            array('item' => 'safe_#00','count' => 4),
            array('item' => 'food_armag_#00','count' => 4),
            array('item' => 'cigs_#00','count' => 1),
        ], 'desc' => 'In dieses schöne Gebäude gingen die Menschen früher zur Arbeit. Pünktlichkeit und Dresscode waren Pflicht. Die tägliche Routine bestand darin, mit einer Gruppe unbekannter Kollegen Zielvorgaben zu erreichen und um sein eigenes Überleben zu kämpfen... Hhmmm, wenn du so drüber nachdenkst: So viel hat sich gar nicht geändert - bis auf den Dresscode vielleicht.'],
        ["label" => "Verfallene Villa",'icon' => 'villa',"camping" => 7,"min_dist" => 3, "max_dist" => 9, "chance" => 338, "drops" => [
            array('item' => 'can_#00','count' => 63),
            array('item' => 'pile_#00','count' => 32),
            array('item' => 'chest_citizen_#00','count' => 23),
            array('item' => 'screw_#00','count' => 16),
            array('item' => 'lock_#00','count' => 12),
            array('item' => 'table_#00','count' => 11),
            array('item' => 'door_carpet_#00','count' => 11),
            array('item' => 'pharma_#00','count' => 11),
            array('item' => 'can_opener_#00','count' => 8),
            array('item' => 'repair_kit_#00','count' => 8),
            array('item' => 'sport_elec_empty_#00','count' => 7),
            array('item' => 'chair_basic_#00','count' => 7),
            array('item' => 'chair_#00','count' => 7),
            array('item' => 'bed_#00','count' => 6),
            array('item' => 'lamp_#00','count' => 6),
            array('item' => 'carpet_#00','count' => 4),
            array('item' => 'vodka_de_#00','count' => 3),
            array('item' => 'rhum_#00','count' => 2),
            array('item' => 'pet_dog_#00','count' => 2),
        ], 'desc' => 'Jemand hat hier vor langer Zeit gelebt. Vielleicht jemand, der von einer Familie umgeben war, die ihn liebte und mit der er viele glückliche Stunden zusammen verbrachte ? Heute ist alles, was bleibt, ein wenig Staub und völlige Verwüstung... und gelegentlich eine Leiche, die mit den Zähnen knirschend auf einen zustürmt.'],
        ["label" => "Verlassene Baustelle",'icon' => 'construction',"camping" => 7,"min_dist" => 4, "max_dist" => 10, "chance" => 481, "drops" => [
            array('item' => 'metal_beam_#00','count' => 103),
            array('item' => 'repair_kit_#00','count' => 64),
            array('item' => 'plate_raw_#00','count' => 51),
            array('item' => 'concrete_#00','count' => 50),
            array('item' => 'chest_#00','count' => 23),
            array('item' => 'trestle_#00','count' => 17),
            array('item' => 'screw_#00','count' => 13),
            array('item' => 'wrench_#00','count' => 11),
            array('item' => 'fence_#00','count' => 9),
            array('item' => 'radio_off_#00','count' => 6),
            array('item' => 'electro_box_#00','count' => 6),
            array('item' => 'lock_#00','count' => 6),
            array('item' => 'pocket_belt_#00','count' => 2),
            array('item' => 'chest_xl_#00','count' => 2),
        ], 'desc' => 'Soll das eine Schule, ein Parkhaus oder vielleicht ein Kaufhaus sein? Du kannst es nicht erkennen... Das einzige, was von diesem geheimnisvollen Projekt noch übrig ist, sind ein paar verrostete Metallstrukturen.'],
        ["label" => "Verlassener Brunnen",'icon' => 'well',"camping" => 0,"min_dist" => 17, "max_dist" => 28, "chance" => 221, "drops" => [
            array('item' => 'water_#00','count' => 121),
            array('item' => 'water_cup_part_#00','count' => 38),
            array('item' => 'jerrycan_#00','count' => 11),
        ], 'desc' => 'Wow - das ist ein verdammtes Geschenk des Himmels! Ein Brunnen, der immer noch funktioniert ! Völlig verloren in der Mitte von Nirgendwo gibt es hier niemanden mit seinem Regelwerk, der Ihnen sagt: \'Tun Sie dies nicht, tun Sie das nicht, nehmen Sie nicht zu viel Wasser, bla bla bla bla...\'. Na los, nimm einen Drink, es wird unser kleines Geheimnis sein...'],
        ["label" => "Verlassene Silos",'icon' => 'silo',"camping" => 7,"min_dist" => 8, "max_dist" => 16, "chance" => 482, "drops" => [
            array('item' => 'jerrycan_#00','count' => 321),
        ], 'desc' => 'Ursprünglich zur Lagerung von Getreide konstruiert, aber als die Zeit verging und das Getreide knapp wurde, füllten sich die Tanks mit Regenwasser (und einer toten Ratte). Sie brauchen allerdings den richtigen Bausatz, um sie zu benutzen...'],
        ["label" => "Versperrte Straße",'icon' => 'street',"camping" => 13,"min_dist" => 4, "max_dist" => 10, "chance" => 42, "drops" => [
            array('item' => 'concrete_wall_#00','count' => 18),
            array('item' => 'plate_raw_#00','count' => 9),
            array('item' => 'tube_#00','count' => 5),
            array('item' => 'chest_#00','count' => 3),
            array('item' => 'trestle_#00','count' => 2),
            array('item' => 'meca_parts_#00','count' => 1),
            array('item' => 'courroie_#00','count' => 1),
            array('item' => 'repair_one_#00','count' => 1),
        ], 'desc' => 'Was hier passiert ist erschließt sich dir nicht so ganz... Ein riesiger Felsen ist mit voller Wucht auf die Straße geschleudert worden - doch woher kam er? Rings um dich ist nichts als Wüste...'],
        ["label" => "Verwilderter Park",'icon' => 'park',"camping" => 7,"min_dist" => 4, "max_dist" => 10, "chance" => 102, "drops" => [
            array('item' => 'watergun_empty_#00','count' => 12),
            array('item' => 'vegetable_#00','count' => 11),
            array('item' => 'pet_snake_#00','count' => 5),
            array('item' => 'lawn_part_#00','count' => 5),
            array('item' => 'digger_#00','count' => 5),
            array('item' => 'cutcut_#00','count' => 4),
            array('item' => 'chair_basic_#00','count' => 4),
            array('item' => 'wood2_#00','count' => 3),
            array('item' => 'game_box_#00','count' => 3),
            array('item' => 'ryebag_#00','count' => 1),
            array('item' => 'pet_pig_#00','count' => 1),
        ], 'desc' => 'Ein Ort des Friedens und der Gelassenheit... Wenn Sie bewaffnet und bereit sind, um Ihr Leben zu kämpfen. Die umgebende Vegetation ist unheimlich und riecht stark nach Tod, unidentifizierte Kreaturen lauern im Schatten... Sie haben das überwältigende Gefühl, dass eine Kreatur aus einer Hecke ausbrechen und Sie angreifen wird.'],
        ["label" => "Waffengeschäft Guns'n'Zombies",'icon' => 'guns',"camping" => 7,"min_dist" => 5, "max_dist" => 12, "chance" => 121, "drops" => [
            // ToDo
        ], 'desc' => 'Wenn Sie drohen, verstümmeln oder morden wollen, haben Sie hier die Hauptader getroffen... Die in den Wänden steckenden Schrapnelle, Einschusslöcher und Trümmer ringsum geben Ihnen eine gute Vorstellung davon, welche Art von \'Ereignissen\' sich hier abgespielt haben...'],
        ["label" => "Warenlager",'icon' => 'warehouse',"camping" => 7,"min_dist" => 2, "max_dist" => 7, "chance" => 181, "drops" => [
            array('item' => 'chest_food_#00','count' => 43),
            array('item' => 'chest_citizen_#00','count' => 34),
            array('item' => 'chest_tools_#00','count' => 31),
        ], 'desc' => 'Das Schiebetor dieses Supermarktlagers hat allen Plünderungsversuchen erfolgreich getrotzt. Durch einen etwas versteckten Seiteneingang gelangst du ins Innere und machst dich sofort auf die Suche nach Dingen, die du noch gebrauchen kannst...'],
        ["label" => "Zelt eines Bürgers",'icon' => 'tent',"camping" => 11,"min_dist" => 12, "max_dist" => 21, "chance" => 202, "drops" => [
            array('item' => 'chest_hero_#00','count' => 72),
            array('item' => 'lamp_#00','count' => 36),
            array('item' => 'banned_note_#00','count' => 36),
            array('item' => 'chest_food_#00','count' => 33),
            array('item' => 'rhum_#00','count' => 24),
            array('item' => 'chest_#00','count' => 19),
            array('item' => 'home_box_#00','count' => 18),
            array('item' => 'lights_#00','count' => 17),
            array('item' => 'coffee_#00','count' => 15),
            array('item' => 'rp_letter_#00','count' => 9),
            array('item' => 'xanax_#00','count' => 8),
            array('item' => 'bandage_#00','count' => 8),
            array('item' => 'chest_citizen_#00','count' => 6),
            array('item' => 'watergun_opt_part_#00','count' => 3),
            array('item' => 'door_carpet_#00','count' => 3),
            array('item' => 'vodka_de_#00','count' => 3),
            array('item' => 'chama_tasty_#00','count' => 2),
            array('item' => 'bagxl_#00','count' => 2),
        ], 'desc' => 'Dieses Zelt macht einen wirklich soliden Eindruck und war bestimmt mal ein gutes Versteck. Derjenige, der es aufgestellt hat, wusste wie man sich vor Zombies schützt. Das Zelt verfügt über ein farblich abgestimmtes Tarnnetz, mehrere Ein- und Ausgänge, sowie über ein unterirdisches Notversteck für brenzlige Situation. Bei näherem Hinsehen entdeckst du auf der Zeltplane einen eingestickten Namen: \'Shenji\''],
        ["label" => "Zerstörte Apotheke",'icon' => 'pharma',"camping" => 7,"min_dist" => 4, "max_dist" => 10, "chance" => 458, "drops" => [
            array('item' => 'pharma_#00','count' => 316),
            array('item' => 'cyanure_#00','count' => 37),
            array('item' => 'xanax_#00','count' => 30),
            array('item' => 'drug_#00','count' => 28),
            array('item' => 'disinfect_#00','count' => 21),
            array('item' => 'digger_#00','count' => 19),
            array('item' => 'drug_hero_#00','count' => 16),
            array('item' => 'drug_random_#00','count' => 14),
            array('item' => 'bquies_#00','count' => 2),
        ], 'desc' => 'Mitten in der Wüste entdeckst du eine kleine Stadtviertelapotheke – grotesk! Ein unbeschreibbarer Gestank liegt in der Luft und es riecht nach allem möglichen, außer nach Gesundheit.'],
        ["label" => "ZomBIER Bar",'icon' => 'bar',"camping" => 7,"min_dist" => 5, "max_dist" => 12, "chance" => 432, "drops" => [
            array('item' => 'rhum_#00','count' => 76),
            array('item' => 'meat_#00','count' => 60),
            array('item' => 'food_bag_#00','count' => 26),
            array('item' => 'pet_rat_#00','count' => 22),
            array('item' => 'chair_basic_#00','count' => 20),
            array('item' => 'drug_#00','count' => 17),
            array('item' => 'jerrycan_#00','count' => 16),
            array('item' => 'can_opener_#00','count' => 13),
            array('item' => 'vodka_de_#00','count' => 10)
        ], 'desc' => 'Es sieht eigentlich nicht mehr wie eine Bar aus, aber das halb im Sand vergrabene Schild und das Vorhandensein einiger zerbrochener Optiken lassen keinen großen Zweifel aufkommen. Die meisten Flaschen sind zerbrochen, aber Sie können hier mit ziemlicher Sicherheit etwas Nützliches finden...'],

        // Explorable Ruins.
        ["label" => "Verlassenes Hotel",'icon' => 'deserted_hotel',"camping" => 1,"min_dist" => 5, "max_dist" => 25, "chance" => 0, "explorable" => true, "drops" => [
            array('item' => 'food_pims_#00','count' => 76),
            array('item' => 'food_chick_#00','count' => 60),
            array('item' => 'food_noodles_#00','count' => 60),
            array('item' => 'food_bag_#00','count' => 60),
            array('item' => 'hbplan_u_#00','count' => 26),
            array('item' => 'hbplan_r_#00','count' => 26),
            array('item' => 'hbplan_e_#00','count' => 26),
            array('item' => 'chair_basic_#00','count' => 20),
            array('item' => 'can_#00','count' => 17),
            array('item' => 'bag_#00','count' => 17),
            array('item' => 'table_#00','count' => 17),
            array('item' => 'distri_#00','count' => 17),
            array('item' => 'bed_#00','count' => 16),
            array('item' => 'deco_box_#00','count' => 13),
            array('item' => 'chest_food_#00','count' => 10),
            array('item' => 'rlaunc_#00','count' => 17),
            array('item' => 'bureau_#00','count' => 17),
            array('item' => 'spices_#00','count' => 17),
            array('item' => 'food_bar1_#00','count' => 17),
            array('item' => 'food_bar2_#00','count' => 17),
            array('item' => 'food_bar3_#00','count' => 17),
            array('item' => 'concrete_wall_#00','count' => 17),
            array('item' => 'dish_#00','count' => 17),
            array('item' => 'food_sandw_#00','count' => 17),
        ], 'desc' => 'Es sieht eigentlich nicht mehr wie eine Bar aus, aber das halb im Sand vergrabene Schild und das Vorhandensein einiger zerbrochener Optiken lassen keinen großen Zweifel aufkommen. Die meisten Flaschen sind zerbrochen, aber Sie können hier mit ziemlicher Sicherheit etwas Nützliches finden...'],
    ];

    public static $zone_tags = array(
        'none' => array(
            'label' => '[nights]',
            'icon' => '',
            'ref' => ZoneTag::TagNone),
        'help' => array(
            'label' => 'Notruf',
            'icon' => 'tag_1',
            'ref' => ZoneTag::TagHelp),
        'resources' => array(
            'label' => 'Rohstoff am Boden (Holz, Metall...)',
            'icon' => 'tag_2',
            'ref' => ZoneTag::TagResource),
        'items' => array(
            'label' => 'Verschiedene Gegenstände am Boden',
            'icon' => 'tag_3',
            'ref' => ZoneTag::TagItems),
        'impItem' => array(
            'label' => 'Wichtige(r) Gegenstand/-ände!',
            'icon' => 'tag_4',
            'ref' => ZoneTag::TagImportantItems),
        'depleted' => array(
            'label' => 'Zone leer',
            'icon' => 'tag_5',
            'ref' => ZoneTag::TagDepleted),
        'tempSecure' => array(
            'label' => 'Zone tempörar gesichert',
            'icon' => 'tag_6',
            'ref' => ZoneTag::TagTempSecured),
        'needDig' => array(
            'label' => 'Zone muss freigeräumt werden',
            'icon' => 'tag_7',
            'ref' => ZoneTag::TagRuinDig),
        '5to8zeds' => array(
            'label' => 'Zwichen 5 und 8 Zombies',
            'icon' => 'tag_8',
            'ref' => ZoneTag::Tag5To8Zombies),
        '9zeds' => array(
            'label' => '9 oder mehr Zombies!',
            'icon' => 'tag_9',
            'ref' => ZoneTag::Tag9OrMoreZombies),
        'camping' => array(
            'label' => 'Camping geplant',
            'icon' => 'tag_10',
            'ref' => ZoneTag::TagCamping),
        'exploreRuin' => array(
            'label' => 'Zu untersuchende Ruine',
            'icon' => 'tag_11',
            'ref' => ZoneTag::TagExploreRuin),
        'soul' => array(
            'label' => 'Verlorene Seele',
            'icon' => 'tag_12',
            'ref' => ZoneTag::TagLostSoul),
    );

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_zone_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Zone prototypes: ' . count(static::$zone_class_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$zone_class_data) );

        // Iterate over all entries
        foreach (static::$zone_class_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ZonePrototype::class)->findOneByLabel( $entry['label'] );
            if ($entity === null) $entity = new ZonePrototype();

            // Set property
            $entity
            ->setLabel( $entry['label'] )
            ->setDescription( $entry['desc'] )
            ->setCampingLevel( $entry['camping'] )
            ->setMinDistance( $entry['min_dist'] )
            ->setMaxDistance( $entry['max_dist'] )
            ->setChance( $entry['chance'] )
            ->setIcon( $entry['icon'] )
            ->setDrops( FixtureHelper::createItemGroup( $manager, 'zp_drop_' . substr(md5($entry['label']),0, 24), $entry['drops'] ) )
            ->setExplorable( $entry['explorable'] ?? 0 )
            ;
            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_zone_tags(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Zone tags: ' . count(static::$zone_tags) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$zone_tags) );

        // Iterate over all entries
        foreach (static::$zone_tags as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ZoneTag::class)->findOneByName( $name );
            if ($entity === null) $entity = (new ZoneTag())->setName($name);

            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setRef( $entry['ref'] )
            ;
            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: The World Beyond Content Database</info>' );
        $output->writeln("");

        $this->insert_zone_prototypes( $manager, $output );
        $this->insert_zone_tags( $manager, $output );
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
