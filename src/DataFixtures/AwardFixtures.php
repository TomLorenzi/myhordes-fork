<?php


namespace App\DataFixtures;


use App\Entity\AwardPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class AwardFixtures extends Fixture implements DependentFixtureInterface {

    private $entityManager;

    protected static $award_data = [
        ['title'=>'Pfadfinder', 'unlockquantity'=>10, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Ninja', 'unlockquantity'=>25, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Green Beret', 'unlockquantity'=>75, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Schattenmann', 'unlockquantity'=>150, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Wüstenphantom', 'unlockquantity'=>300, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Solid Snake war gestern...', 'unlockquantity'=>800, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Sandarbeiter', 'unlockquantity'=>10, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Wüstenspringmaus', 'unlockquantity'=>25, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Großer Ameisenbär', 'unlockquantity'=>75, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Wüstenfuchs', 'unlockquantity'=>150, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Ich sehe Alles!', 'unlockquantity'=>300, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Tierliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Malteserzüchter', 'unlockquantity'=>25, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Ich bändige Bestien', 'unlockquantity'=>75, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Nie ohne meinen Hund!', 'unlockquantity'=>150, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Hundewurst schmeckt gar nicht schlecht!', 'unlockquantity'=>300, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Wurmfresser', 'unlockquantity'=>10, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Meister im Würmerfinden', 'unlockquantity'=>25, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Gefräßiger Bürger', 'unlockquantity'=>75, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Wüstenwurmzüchter', 'unlockquantity'=>150, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Ich brauche niemanden!', 'unlockquantity'=>300, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Heraklit der Außenwelt', 'unlockquantity'=>800, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Diplomierter Scharlatan', 'unlockquantity'=>10, 'associatedtag'=>':prosham:', 'associatedpicto'=>'r_jsham_#00'],
        ['title'=>'Schlimmer Finger', 'unlockquantity'=>25, 'associatedtag'=>':prosham:', 'associatedpicto'=>'r_jsham_#00'],
        ['title'=>'Seelenverwerter', 'unlockquantity'=>75, 'associatedtag'=>':prosham:', 'associatedpicto'=>'r_jsham_#00'],
        ['title'=>'Mystischer Seher', 'unlockquantity'=>150, 'associatedtag'=>':prosham:', 'associatedpicto'=>'r_jsham_#00'],
        ['title'=>'Voodoo Sorceror', 'unlockquantity'=>300, 'associatedtag'=>':prosham:', 'associatedpicto'=>'r_jsham_#00'],
        ['title'=>'Yo, wir schaffen das!', 'unlockquantity'=>10, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Kleiner Schraubendreher', 'unlockquantity'=>25, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Schweizer Taschenmesser', 'unlockquantity'=>75, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Unermüdlicher Schrauber', 'unlockquantity'=>150, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Seele des Handwerks', 'unlockquantity'=>300, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Die Mauer', 'unlockquantity'=>10, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Höllenwächter', 'unlockquantity'=>25, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Kerberos', 'unlockquantity'=>75, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Die letzte Verteidigungslinie', 'unlockquantity'=>150, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Du kommst hier NICHT durch!', 'unlockquantity'=>300, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Hekatoncheir', 'unlockquantity'=>800, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Kantinenkoch', 'unlockquantity'=>10, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Kleiner Küchenchef', 'unlockquantity'=>25, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Meister Eintopf', 'unlockquantity'=>50, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Großer Wüstenkonditor', 'unlockquantity'=>100, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Begnadeter Wüstenkonditor', 'unlockquantity'=>250, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Cooking Mama', 'unlockquantity'=>500, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Meisterhafter Kochlöffelschwinger', 'unlockquantity'=>1000, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Amateur-Laborratte', 'unlockquantity'=>10, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Kleiner Präparator', 'unlockquantity'=>25, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Chemiker von um die Ecke', 'unlockquantity'=>50, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Produkttester', 'unlockquantity'=>100, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Wüstenstadt-Dealer', 'unlockquantity'=>250, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'X-Men Leser', 'unlockquantity'=>15, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Aussergewöhnlicher Bürger', 'unlockquantity'=>30, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Wunder', 'unlockquantity'=>50, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Werdender Superheld', 'unlockquantity'=>70, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Volksheld', 'unlockquantity'=>90, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Neo', 'unlockquantity'=>120, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Erlöser der Menschheit', 'unlockquantity'=>150, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Außenweltlegende', 'unlockquantity'=>200, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Abstauber', 'unlockquantity'=>20, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Müllkind', 'unlockquantity'=>100, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Maulheld', 'unlockquantity'=>10, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Lump', 'unlockquantity'=>30, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Schaumschläger', 'unlockquantity'=>40, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Dieb', 'unlockquantity'=>50, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Fantomas', 'unlockquantity'=>75, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Ali Baba', 'unlockquantity'=>100, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Meisterdieb', 'unlockquantity'=>500, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Kleptomane', 'unlockquantity'=>1000, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Dein Haus ist mein Haus', 'unlockquantity'=>2000, 'associatedtag'=>':thief:', 'associatedpicto'=>'r_theft_#00'],
        ['title'=>'Ästhet', 'unlockquantity'=>100, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Schaufenstergestalter', 'unlockquantity'=>250, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Innenarchitekt', 'unlockquantity'=>500, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Möbelgourmet', 'unlockquantity'=>1000, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Ordnung muss sein!', 'unlockquantity'=>1500, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Plünderer', 'unlockquantity'=>30, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Aasgeier', 'unlockquantity'=>60, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Hyäne', 'unlockquantity'=>100, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Wegelagerer post mortem', 'unlockquantity'=>200, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Hungriger Schakal', 'unlockquantity'=>400, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Kojote der kargen Steppen', 'unlockquantity'=>600, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Fingerschmied', 'unlockquantity'=>1000, 'associatedtag'=>':pillage:', 'associatedpicto'=>'r_plundr_#00'],
        ['title'=>'Nein, das Ding fasse ich nicht an.', 'unlockquantity'=>50, 'associatedtag'=>':shower:', 'associatedpicto'=>'r_cwater_#00'],
        ['title'=>'Leichenhygieniker', 'unlockquantity'=>100, 'associatedtag'=>':shower:', 'associatedpicto'=>'r_cwater_#00'],
        ['title'=>'Ich liebe den Geruch von gewaschenen Leichen am Morgen.', 'unlockquantity'=>200, 'associatedtag'=>':shower:', 'associatedpicto'=>'r_cwater_#00'],
        ['title'=>'Müllmann', 'unlockquantity'=>60, 'associatedtag'=>':drag:', 'associatedpicto'=>'r_cgarb_#00'],
        ['title'=>'Erfahrener Müllmann', 'unlockquantity'=>100, 'associatedtag'=>':drag:', 'associatedpicto'=>'r_cgarb_#00'],
        ['title'=>'Entsorgungsfachmann', 'unlockquantity'=>200, 'associatedtag'=>':drag:', 'associatedpicto'=>'r_cgarb_#00'],
        ['title'=>'SM Anhänger', 'unlockquantity'=>20, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'SM Spezialist', 'unlockquantity'=>40, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'Ich möchte dein Objekt sein.', 'unlockquantity'=>100, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'Stadtmetzger', 'unlockquantity'=>30, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Spezialität: Hundesteaks', 'unlockquantity'=>60, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Miezi? Jaaaa, komm her...', 'unlockquantity'=>150, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Fleisch ist mein Gemüse', 'unlockquantity'=>300, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Nervensäge', 'unlockquantity'=>10, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'],
        ['title'=>'Sozialschmarotzer', 'unlockquantity'=>20, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'],
        ['title'=>'Die Wüste ist mein Zuhause', 'unlockquantity'=>30, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'],
        ['title'=>'Immer ein Auge offen', 'unlockquantity'=>20, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Mit offenen Augen schlafen? Kein Problem!', 'unlockquantity'=>40, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Immer alles im Blick', 'unlockquantity'=>80, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Großer Baumeister', 'unlockquantity'=>20, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Großer Architekt', 'unlockquantity'=>50, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Freimauer', 'unlockquantity'=>100, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Terraformingspezialist', 'unlockquantity'=>150, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Allmächtiger Schöpfer', 'unlockquantity'=>200, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Bauherr ohne Sinn und Verstand', 'unlockquantity'=>1, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Erbauer der verlorenen Zeit', 'unlockquantity'=>3, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Erleuchteter Baumeister', 'unlockquantity'=>7, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Maurer aus Leidenschaft', 'unlockquantity'=>10, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Huldigt den riesigen KVF!', 'unlockquantity'=>5, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Das hast du nicht wirklich gebaut?', 'unlockquantity'=>5, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Der Rabe ist gut, der Rabe ist ein Segen für die Stadt. [editiert vom Raben]', 'unlockquantity'=>5, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Durazell', 'unlockquantity'=>15, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Hüter der Heiligen Batterie', 'unlockquantity'=>25, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Maurerlehrling', 'unlockquantity'=>100, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Maurer', 'unlockquantity'=>200, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Polier', 'unlockquantity'=>400, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Baustellenleiter', 'unlockquantity'=>700, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Architekt', 'unlockquantity'=>1300, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Meisterarchitekt', 'unlockquantity'=>2000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Oscar Niemeyer', 'unlockquantity'=>3000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Spachtler', 'unlockquantity'=>100, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Gewissenhafter Maurer', 'unlockquantity'=>250, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Meisterwerke in Gefahr', 'unlockquantity'=>500, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Ein Flicken und alles ist wie neu', 'unlockquantity'=>1000, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Kettensägenmassaker', 'unlockquantity'=>5, 'associatedtag'=>':chain:', 'associatedpicto'=>'r_tronco_#00'],
        ['title'=>'Hobby-Heimwerker', 'unlockquantity'=>15, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Techniker', 'unlockquantity'=>30, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Meisterbastler ', 'unlockquantity'=>60, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Sowas wirft man doch nicht weg!', 'unlockquantity'=>150, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Mac Gyver der Außenwelt', 'unlockquantity'=>400, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Super Soaker', 'unlockquantity'=>10, 'associatedtag'=>':watergun:', 'associatedpicto'=>'r_watgun_#00'],
        ['title'=>'Archäologielehrling', 'unlockquantity'=>50, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Archäologe', 'unlockquantity'=>300, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Grabungsleiter', 'unlockquantity'=>750, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Furchtloser Camper', 'unlockquantity'=>10, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Waisenkind der Wüste', 'unlockquantity'=>25, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Unglaubliche Entschlossenheit', 'unlockquantity'=>50, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Der letzte Überlebende', 'unlockquantity'=>100, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Lebensmüder Kundschafter', 'unlockquantity'=>5, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Draufgängerischer Expeditionsreisender', 'unlockquantity'=>15, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Nervenkitzelsucher', 'unlockquantity'=>30, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Christopher Kolumbus', 'unlockquantity'=>70, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Neil Armstrong der Außenwelt', 'unlockquantity'=>100, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Wenn ich das nur vorher gewusst hätte...', 'unlockquantity'=>5, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Verwegener Wanderer', 'unlockquantity'=>10, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Tunnelblicker', 'unlockquantity'=>20, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Im Irrgarten', 'unlockquantity'=>50, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Inklusive Kompass', 'unlockquantity'=>100, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Wo klemmt\'s?', 'unlockquantity'=>1, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Dietrich', 'unlockquantity'=>5, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Sesam, öffne dich...', 'unlockquantity'=>10, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Türöffner', 'unlockquantity'=>15, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Gentleman-Einbrecher', 'unlockquantity'=>20, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Schlüsseldienst', 'unlockquantity'=>30, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Zerberus', 'unlockquantity'=>50, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Brutalo', 'unlockquantity'=>100, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Verstümmler', 'unlockquantity'=>200, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Killer', 'unlockquantity'=>300, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Kadaverentsorger', 'unlockquantity'=>800, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Vernichter', 'unlockquantity'=>2000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Schlächter', 'unlockquantity'=>4000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Friedensstifter', 'unlockquantity'=>6000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Alptraum der Traumlosen', 'unlockquantity'=>10000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'r_killz_#00'],
        ['title'=>'Hans im Glück', 'unlockquantity'=>5, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Vierklettriges Kleeblatt', 'unlockquantity'=>10, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Fortuna', 'unlockquantity'=>15, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Ultimate Fighter', 'unlockquantity'=>20, 'associatedtag'=>':fight:', 'associatedpicto'=>'r_wrestl_#00'],
        ['title'=>'Stürmischer Kundschafter', 'unlockquantity'=>15, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Furchtloser Abenteurer', 'unlockquantity'=>30, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Wagemutiger Trapper', 'unlockquantity'=>70, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Wikinger', 'unlockquantity'=>150, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Außenweltreiseführer', 'unlockquantity'=>200, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Außenweltschläfer', 'unlockquantity'=>10, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Allein in dieser Welt', 'unlockquantity'=>25, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Der mit den Zombies tanzt', 'unlockquantity'=>50, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ich sehe überall Tote!', 'unlockquantity'=>75, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ich komme heute Nacht nicht heim...', 'unlockquantity'=>100, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ein Mann gegen die Außenwelt', 'unlockquantity'=>150, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Neugieriger Leser', 'unlockquantity'=>5, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Eifriger Leser', 'unlockquantity'=>10, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Wüstenhistoriker', 'unlockquantity'=>20, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Bibliothekar', 'unlockquantity'=>30, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Archivar', 'unlockquantity'=>40, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Twinoid ist tabu', 'unlockquantity'=>20, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Keine Macht den Drogen', 'unlockquantity'=>75, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Sowas fasse ICH nicht an', 'unlockquantity'=>150, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Vorbild für die Jugend', 'unlockquantity'=>500, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Champions nehmen keine Drogen!', 'unlockquantity'=>1000, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Medikamentetester', 'unlockquantity'=>50, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Laborratte', 'unlockquantity'=>100, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Pille palle, 3 Tage wach!', 'unlockquantity'=>150, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Timothy Leary', 'unlockquantity'=>200, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Menschenfleischliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Hannibalfan', 'unlockquantity'=>40, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Totmacher', 'unlockquantity'=>80, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Jeffrey Dahmer', 'unlockquantity'=>120, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Fido', 'unlockquantity'=>150, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Fast schon ein Zombie...', 'unlockquantity'=>180, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Schmeckt am besten wenn\'s noch schreit!', 'unlockquantity'=>250, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Die Macht der Proteine', 'unlockquantity'=>500, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Zombieliebling', 'unlockquantity'=>10, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Ich bin in der Hölle aufgewachsen.', 'unlockquantity'=>5, 'associatedtag'=>':hclms:', 'associatedpicto'=>'r_suhard_#00'],
        ['title'=>'Teilweise verrottet', 'unlockquantity'=>20, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Virenschleuder', 'unlockquantity'=>40, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Gedärmeauskotzer', 'unlockquantity'=>75, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Seuchenherd', 'unlockquantity'=>100, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Verirrter Ausflügler', 'unlockquantity'=>20, 'associatedtag'=>':night:', 'associatedpicto'=>'r_doutsd_#00'],
        ['title'=>'Nächtlicher Spaziergänger', 'unlockquantity'=>100, 'associatedtag'=>':night:', 'associatedpicto'=>'r_doutsd_#00'],
        ['title'=>'Vertrauenswürdiger Bürger', 'unlockquantity'=>2, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Sehr erfahrener Bürger', 'unlockquantity'=>5, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Vorbild für das Volk', 'unlockquantity'=>10, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Richtwert der Gemeinschaft', 'unlockquantity'=>15, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Berühmter Veteran', 'unlockquantity'=>20, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Lebender Mythos', 'unlockquantity'=>1, 'associatedtag'=>':legend:', 'associatedpicto'=>'r_wintop_#00'],
        ['title'=>'Ich bin eine Legende', 'unlockquantity'=>2, 'associatedtag'=>':legend:', 'associatedpicto'=>'r_wintop_#00'],
        ['title'=>'Hör auf mich, wenn du überleben möchtest', 'unlockquantity'=>3, 'associatedtag'=>':legend:', 'associatedpicto'=>'r_wintop_#00'],
        ['title'=>'Netter Kerl', 'unlockquantity'=>1, 'associatedtag'=>':goodg:', 'associatedpicto'=>'r_goodg_#00'],
        ['title'=>'Zeuge der großen Verseuchung', 'unlockquantity'=>1, 'associatedtag'=>':ginfec:', 'associatedpicto'=>'r_ginfec_#00'],
        ['title'=>'Alter Hase', 'unlockquantity'=>1, 'associatedtag'=>':beta:', 'associatedpicto'=>'r_beta_#00'],
        ['title'=>'Jedes Los gewinnt', 'unlockquantity'=>1, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Zinker', 'unlockquantity'=>5, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Bingo', 'unlockquantity'=>10, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Sudoku ist anders', 'unlockquantity'=>15, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Siebenseitiger Würfel', 'unlockquantity'=>20, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Einarmiger Bandit', 'unlockquantity'=>30, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Motivierter Messebesucher', 'unlockquantity'=>1, 'associatedtag'=>':fjv2:', 'associatedpicto'=>'r_fjv2_#00'],
        ['title'=>'Verdammt in Saarbrücken', 'unlockquantity'=>1, 'associatedtag'=>':fjvani:', 'associatedpicto'=>'r_fjvani_#00'],
        ['title'=>'Kleiner Guru', 'unlockquantity'=>1, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Überzeugender Guru', 'unlockquantity'=>3, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Frischfleischhändler', 'unlockquantity'=>5, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Held der Community!', 'unlockquantity'=>1, 'associatedtag'=>':comu:', 'associatedpicto'=>'r_comu_#00'],
        ['title'=>'Nobler Spender', 'unlockquantity'=>10, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Immer zur Stelle', 'unlockquantity'=>25, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Samariter', 'unlockquantity'=>50, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Der Weihnachtsmann ist ein Schlawiner', 'unlockquantity'=>10, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Treffen der 4. Art', 'unlockquantity'=>2, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Hast Du mal Feuer?', 'unlockquantity'=>10, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Medium mit Preisnachlass', 'unlockquantity'=>20, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Amophilis Psychotropes', 'unlockquantity'=>30, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Blauer Feuerspucker', 'unlockquantity'=>50, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'I see dead people 0_0', 'unlockquantity'=>80, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Soul Man', 'unlockquantity'=>120, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Bei meiner Seel', 'unlockquantity'=>100, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Für immer und seelich', 'unlockquantity'=>500, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Noble Seele', 'unlockquantity'=>1000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Beseelt', 'unlockquantity'=>2000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Reinkarnator', 'unlockquantity'=>3000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Göttliche Seele', 'unlockquantity'=>5000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Open-chakra', 'unlockquantity'=>7000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Tausend-und-ein-Leben', 'unlockquantity'=>9000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Buddha', 'unlockquantity'=>12000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Messie der verlorenen Welt', 'unlockquantity'=>500, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Ich bin ein Gott! Ich werde ewig leben!', 'unlockquantity'=>1, 'associatedtag'=>':ermwin:', 'associatedpicto'=>'r_ermwin_#00'],
        ['title'=>'Eine Stadt sie zu knechten!', 'unlockquantity'=>1, 'associatedtag'=>':cott:', 'associatedpicto'=>'r_cott_#00'],
    ];

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Awards: ' . count(static::$award_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$award_data) );

       foreach(static::$award_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)->getAwardByTitle($entry['title']);

            if($entity === null) {
                $entity = new AwardPrototype();
            }

            $pp = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName($entry['associatedpicto']);
            if ($pp === null) {
                $out->writeln("<error>Skipping award '{$entry['title']}' because the associated picto '{$entry['associatedpicto']}' does not exist.</error>");
                continue;
            }

            $entity
                ->setAssociatedPicto( $pp )
                ->setAssociatedTag($entry['associatedtag'])
                ->setTitle($entry['title'])
                ->setUnlockQuantity($entry['unlockquantity']);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em) {
        $this->entityManager = $em;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: AwardPrototype Database</info>' );
        $output->writeln("");

        $this->insertAwards($manager, $output);
        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ PictoFixtures::class ];
    }
}
