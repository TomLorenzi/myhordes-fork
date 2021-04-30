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
        ['title'=>'Der unsichtbare Mann', 'unlockquantity'=>1500, 'associatedtag'=>':proscout:', 'associatedpicto'=>'r_jrangr_#00'],
        ['title'=>'Sandarbeiter', 'unlockquantity'=>10, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Wüstenspringmaus', 'unlockquantity'=>25, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Großer Ameisenbär', 'unlockquantity'=>75, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Wüstenfuchs', 'unlockquantity'=>150, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Ich sehe Alles!', 'unlockquantity'=>300, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Ein BMW? Kein Problem!', 'unlockquantity'=>800, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Où est Larchi ?', 'unlockquantity'=>1500, 'associatedtag'=>':proscav:', 'associatedpicto'=>'r_jcolle_#00'],
        ['title'=>'Tierliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Malteserzüchter', 'unlockquantity'=>25, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Ich bändige Bestien', 'unlockquantity'=>75, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Nie ohne meinen Hund!', 'unlockquantity'=>150, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Hundewurst schmeckt gar nicht schlecht!', 'unlockquantity'=>300, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Meser (halb Mensch, halb Malteser)', 'unlockquantity'=>800, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Personalisierter Titel - Dompteur (T-ID: 1725642)', 'unlockquantity'=>1500, 'associatedtag'=>':protamer:', 'associatedpicto'=>'r_jtamer_#00'],
        ['title'=>'Wurmfresser', 'unlockquantity'=>10, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Meister im Würmerfinden', 'unlockquantity'=>25, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Gefräßiger Bürger', 'unlockquantity'=>75, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Wüstenwurmzüchter', 'unlockquantity'=>150, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Ich brauche niemanden!', 'unlockquantity'=>300, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Heraklit der Außenwelt', 'unlockquantity'=>800, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
        ['title'=>'Gepanzerter Fakir', 'unlockquantity'=>1500, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'r_jermit_#00'],
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
        ['title'=>'Seele eines Profis', 'unlockquantity'=>800, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Personalisierter Titel - Techniker (T-ID: 631944)', 'unlockquantity'=>1500, 'associatedtag'=>':protech:', 'associatedpicto'=>'r_jtech_#00'],
        ['title'=>'Die Mauer', 'unlockquantity'=>10, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Höllenwächter', 'unlockquantity'=>25, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Kerberos', 'unlockquantity'=>75, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Die letzte Verteidigungslinie', 'unlockquantity'=>150, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Du kommst hier NICHT durch!', 'unlockquantity'=>300, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Hekatoncheir', 'unlockquantity'=>800, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Holy F***ing Iceberg', 'unlockquantity'=>1500, 'associatedtag'=>':proguard:', 'associatedpicto'=>'r_jguard_#00'],
        ['title'=>'Kantinenkoch', 'unlockquantity'=>10, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Kleiner Küchenchef', 'unlockquantity'=>25, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Meister Eintopf', 'unlockquantity'=>50, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Großer Wüstenkonditor', 'unlockquantity'=>100, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Begnadeter Wüstenkonditor', 'unlockquantity'=>250, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Cooking Mama', 'unlockquantity'=>500, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Meisterhafter Kochlöffelschwinger', 'unlockquantity'=>1000, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Meisterkoch der Sterne', 'unlockquantity'=>2000, 'associatedtag'=>':tasty:', 'associatedpicto'=>'r_cookr_#00'],
        ['title'=>'Amateur-Laborratte', 'unlockquantity'=>10, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Kleiner Präparator', 'unlockquantity'=>25, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Chemiker von um die Ecke', 'unlockquantity'=>50, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Produkttester', 'unlockquantity'=>100, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Wüstenstadt-Dealer', 'unlockquantity'=>250, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Ich koste alle meine Produkte', 'unlockquantity'=>500, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'Rauchts? Dann ist es meins.', 'unlockquantity'=>1000, 'associatedtag'=>':lab:', 'associatedpicto'=>'r_drgmkr_#00'],
        ['title'=>'X-Men Leser', 'unlockquantity'=>15, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Aussergewöhnlicher Bürger', 'unlockquantity'=>30, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Wunder', 'unlockquantity'=>50, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Werdender Superheld', 'unlockquantity'=>70, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Volksheld', 'unlockquantity'=>90, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Neo', 'unlockquantity'=>120, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Erlöser der Menschheit', 'unlockquantity'=>150, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Außenweltlegende', 'unlockquantity'=>200, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Messie der verlorenen Welt', 'unlockquantity'=>500, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Mein Name ist Nobody', 'unlockquantity'=>850, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Ein Sternbild trägt meinen Namen', 'unlockquantity'=>1000, 'associatedtag'=>':hero:', 'associatedpicto'=>'r_heroac_#00'],
        ['title'=>'Abstauber', 'unlockquantity'=>20, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Müllkind', 'unlockquantity'=>100, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Autonomer Müll', 'unlockquantity'=>200, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Freier Bürger', 'unlockquantity'=>300, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Alles für meinen Mund', 'unlockquantity'=>400, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Ich brauche niemanden', 'unlockquantity'=>550, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Unabhängiger Großeinsiedler', 'unlockquantity'=>700, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
        ['title'=>'Che Guevara der Wüste', 'unlockquantity'=>1000, 'associatedtag'=>':trash:', 'associatedpicto'=>'r_solban_#00'],
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
        ['title'=>'Ein schöneres Leben', 'unlockquantity'=>2000, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
        ['title'=>'Großer E-KEA Katalog', 'unlockquantity'=>2500, 'associatedtag'=>':deco:', 'associatedpicto'=>'r_deco_#00'],
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
        ['title'=>'Maccabees-Vertreiber', 'unlockquantity'=>300, 'associatedtag'=>':drag:', 'associatedpicto'=>'r_cgarb_#00'],
        ['title'=>'SM Anhänger', 'unlockquantity'=>20, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'SM Spezialist', 'unlockquantity'=>40, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'Nenn mich Herrin', 'unlockquantity'=>60, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'Ich möchte dein Objekt sein.', 'unlockquantity'=>100, 'associatedtag'=>':maso:', 'associatedpicto'=>'r_maso_#00'],
        ['title'=>'Stadtmetzger', 'unlockquantity'=>30, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Spezialität: Hundesteaks', 'unlockquantity'=>60, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Miezi? Jaaaa, komm her...', 'unlockquantity'=>150, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        ['title'=>'Fleisch ist mein Gemüse', 'unlockquantity'=>300, 'associatedtag'=>':butcher:', 'associatedpicto'=>'r_animal_#00'],
        # ['title'=>'Nervensäge', 'unlockquantity'=>10, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'], #
        # ['title'=>'Sozialschmarotzer', 'unlockquantity'=>20, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'], #
        # ['title'=>'Die Wüste ist mein Zuhause', 'unlockquantity'=>30, 'associatedtag'=>':ban:', 'associatedpicto'=>'r_ban_#00'], #
        ['title'=>'Immer ein Auge offen', 'unlockquantity'=>20, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Mit offenen Augen schlafen? Kein Problem!', 'unlockquantity'=>40, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Immer alles im Blick', 'unlockquantity'=>80, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Wachsam', 'unlockquantity'=>120, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Paranoid', 'unlockquantity'=>200, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Fels in der Brandung', 'unlockquantity'=>400, 'associatedtag'=>':watch:', 'associatedpicto'=>'r_guard_#00'],
        ['title'=>'Großer Baumeister', 'unlockquantity'=>20, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Großer Architekt', 'unlockquantity'=>50, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Freimauer', 'unlockquantity'=>100, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Terraformingspezialist', 'unlockquantity'=>150, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Allmächtiger Schöpfer', 'unlockquantity'=>200, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Archi-Mädlicher Illustrator von Pi', 'unlockquantity'=>250, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Muad\'Dib', 'unlockquantity'=>300, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Am 7. Tag erschuf er den Fleischkäfig', 'unlockquantity'=>350, 'associatedtag'=>':extreme:', 'associatedpicto'=>'r_wondrs_#00'],
        ['title'=>'Bauherr ohne Sinn und Verstand', 'unlockquantity'=>1, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Erbauer der verlorenen Zeit', 'unlockquantity'=>3, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Erleuchteter Baumeister', 'unlockquantity'=>7, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Maurer aus Leidenschaft', 'unlockquantity'=>10, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Das Unnütze mit dem Angenehmen verbinden', 'unlockquantity'=>15, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Verrückter Architekten-Visionär', 'unlockquantity'=>20, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Ich habe einen Plan', 'unlockquantity'=>30, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Hirnverbrannter Baumeister', 'unlockquantity'=>40, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Meister der Nutzlosigkeit', 'unlockquantity'=>50, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Vereister Ingenieur', 'unlockquantity'=>60, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Wundervoll nutzlos', 'unlockquantity'=>80, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Der Bruce Lee des Spachtels', 'unlockquantity'=>100, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Es ist Zeit für Kathedralen', 'unlockquantity'=>120, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Hast du nichts besseres zu tun?', 'unlockquantity'=>150, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Hohepriesterin des Sonnentempels', 'unlockquantity'=>200, 'associatedtag'=>':wonder:', 'associatedpicto'=>'r_ebuild_#00'],
        ['title'=>'Huldigt den riesigen KVF!', 'unlockquantity'=>5, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Obelisk der heutigen Zeit!', 'unlockquantity'=>10, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Lebhafter Tribut', 'unlockquantity'=>15, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Wird in die Annalen eingehen', 'unlockquantity'=>20, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Extra viel Liebe', 'unlockquantity'=>25, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Es ist kleiner als im Katalog', 'unlockquantity'=>30, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Freude der Stärke 8', 'unlockquantity'=>40, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Bewahrer der Freuden', 'unlockquantity'=>50, 'associatedtag'=>':brd:', 'associatedpicto'=>'r_ebpmv_#00'],
        ['title'=>'Das hast du nicht wirklich gebaut?', 'unlockquantity'=>5, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Eimerhalter', 'unlockquantity'=>10, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Sandverkäufer', 'unlockquantity'=>15, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Martinet am Strand', 'unlockquantity'=>20, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Ein Spiel für echte Kinder', 'unlockquantity'=>25, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Dauermitglied in Micky\'s Club', 'unlockquantity'=>30, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Niemals ohne meinen Eimer', 'unlockquantity'=>40, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Ein Korn fehlt noch', 'unlockquantity'=>50, 'associatedtag'=>':castle:', 'associatedpicto'=>'r_ebcstl_#00'],
        ['title'=>'Huldigt den Raben!', 'unlockquantity'=>5, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Taube aus der Unterwelt', 'unlockquantity'=>10, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Corvid-Spezialist', 'unlockquantity'=>15, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Phoenix des Unterholzes', 'unlockquantity'=>20, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Edit Piaf', 'unlockquantity'=>25, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Vogel der schlechten Vorzeichen', 'unlockquantity'=>30, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Kraaaah!', 'unlockquantity'=>40, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Er war Leber ...', 'unlockquantity'=>50, 'associatedtag'=>':crow:', 'associatedpicto'=>'r_ebcrow_#00'],
        ['title'=>'Hey wir können die Zombies von hier oben sehen', 'unlockquantity'=>5, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Fröhlicher Hamster', 'unlockquantity'=>10, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Showman aus der Anderen Welt!', 'unlockquantity'=>15, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'5. Rad am Wagen', 'unlockquantity'=>20, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Mir ist schwindelig', 'unlockquantity'=>25, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Hier oben', 'unlockquantity'=>30, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Auge der Wüste', 'unlockquantity'=>40, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'4000 Zombies starren uns an', 'unlockquantity'=>50, 'associatedtag'=>':wheel:', 'associatedpicto'=>'r_ebgros_#00'],
        ['title'=>'Durazell', 'unlockquantity'=>15, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Hüter der Heiligen Batterie', 'unlockquantity'=>25, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Der Batterienator', 'unlockquantity'=>50, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Ist es ein Vogel? Ist es ein Flugzeug? Nein, es ist der super Batteriewerfer!', 'unlockquantity'=>75, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Aufgeladen und kampfbereit', 'unlockquantity'=>100, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Hey! Willst du meine... Batterie sehen?', 'unlockquantity'=>150, 'associatedtag'=>':batgun:', 'associatedpicto'=>'r_batgun_#00'],
        ['title'=>'Maurerlehrling', 'unlockquantity'=>100, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Maurer', 'unlockquantity'=>200, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Polier', 'unlockquantity'=>400, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Baustellenleiter', 'unlockquantity'=>700, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Architekt (title)', 'unlockquantity'=>1300, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Meisterarchitekt', 'unlockquantity'=>2000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Oscar Niemeyer', 'unlockquantity'=>3000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Zement & Garfunkel', 'unlockquantity'=>4000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Menschliche Ameisenkolonie', 'unlockquantity'=>5000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Arbeiterhelm und Kupferstiefel', 'unlockquantity'=>6000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Arbeit ist Gesundheit', 'unlockquantity'=>8000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Bob der Baumeister', 'unlockquantity'=>10000, 'associatedtag'=>':build:', 'associatedpicto'=>'r_buildr_#00'],
        ['title'=>'Spachtler', 'unlockquantity'=>100, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Gewissenhafter Maurer', 'unlockquantity'=>250, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Meisterwerke in Gefahr', 'unlockquantity'=>500, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Ein Flicken und alles ist wie neu', 'unlockquantity'=>1000, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Pisa-Geraderichter', 'unlockquantity'=>1500, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Großer Renovator', 'unlockquantity'=>2000, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Ein Patch, kein Spiel', 'unlockquantity'=>2500, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Ein Riss? Welcher Riss?', 'unlockquantity'=>3000, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Das braucht noch 10 Jahre!', 'unlockquantity'=>3500, 'associatedtag'=>':rep:', 'associatedpicto'=>'r_brep_#00'],
        ['title'=>'Kettensägenmassaker', 'unlockquantity'=>5, 'associatedtag'=>':chain:', 'associatedpicto'=>'r_tronco_#00'],
        ['title'=>'Fleisch-Schuldner', 'unlockquantity'=>10, 'associatedtag'=>':chain:', 'associatedpicto'=>'r_tronco_#00'],
        ['title'=>'Bruce Campbell', 'unlockquantity'=>30, 'associatedtag'=>':chain:', 'associatedpicto'=>'r_tronco_#00'],
        ['title'=>'Hobby-Heimwerker', 'unlockquantity'=>15, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Techniker', 'unlockquantity'=>30, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Meisterbastler ', 'unlockquantity'=>60, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Sowas wirft man doch nicht weg!', 'unlockquantity'=>150, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Mac Gyver der Außenwelt', 'unlockquantity'=>400, 'associatedtag'=>':repair:', 'associatedpicto'=>'r_repair_#00'],
        ['title'=>'Super Soaker', 'unlockquantity'=>10, 'associatedtag'=>':watergun:', 'associatedpicto'=>'r_watgun_#00'],
        ['title'=>'Wasserwaffen-Spezialist', 'unlockquantity'=>25, 'associatedtag'=>':watergun:', 'associatedpicto'=>'r_watgun_#00'],
        ['title'=>'Hydraulik-Engineering', 'unlockquantity'=>35, 'associatedtag'=>':watergun:', 'associatedpicto'=>'r_watgun_#00'],
        ['title'=>'Abstieg der Atlanteaner', 'unlockquantity'=>60, 'associatedtag'=>':watergun:', 'associatedpicto'=>'r_watgun_#00'],
        ['title'=>'Archäologielehrling', 'unlockquantity'=>50, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Archäologe', 'unlockquantity'=>300, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Grabungsleiter', 'unlockquantity'=>750, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Großer Löcherbuddler', 'unlockquantity'=>1000, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Riesenmaulwurf', 'unlockquantity'=>1500, 'associatedtag'=>':buried:', 'associatedpicto'=>'r_digger_#00'],
        ['title'=>'Furchtloser Camper', 'unlockquantity'=>10, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Waisenkind der Wüste', 'unlockquantity'=>25, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Unglaubliche Entschlossenheit', 'unlockquantity'=>50, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Der letzte Überlebende', 'unlockquantity'=>100, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Ich gehe alleine.', 'unlockquantity'=>150, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Neil Armstrong der Außenwelt', 'unlockquantity'=>200, 'associatedtag'=>':zen:', 'associatedpicto'=>'r_cmplst_#00'],
        ['title'=>'Lebensmüder Kundschafter', 'unlockquantity'=>5, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Draufgängerischer Expeditionsreisender', 'unlockquantity'=>15, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Nervenkitzelsucher', 'unlockquantity'=>30, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Christopher Kolumbus', 'unlockquantity'=>70, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        # ['title'=>'Neil Armstrong der Außenwelt', 'unlockquantity'=>100, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'], #
        ['title'=>'Bis zur Unendlichkeit... und noch viel weiter!', 'unlockquantity'=>150, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Indiana Jones und die Ruinen der Aussenwelt', 'unlockquantity'=>300, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'r_explo2_#00'],
        ['title'=>'Wenn ich das nur vorher gewusst hätte...', 'unlockquantity'=>5, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Verwegener Wanderer', 'unlockquantity'=>10, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Tunnelblicker', 'unlockquantity'=>20, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Im Irrgarten', 'unlockquantity'=>50, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Inklusive Kompass', 'unlockquantity'=>100, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Begrabener Freiwilliger', 'unlockquantity'=>150, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Tomb Raider', 'unlockquantity'=>200, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Labyrinth-Gollum', 'unlockquantity'=>250, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Labyrinth-Meister MB', 'unlockquantity'=>400, 'associatedtag'=>':ruin:', 'associatedpicto'=>'r_ruine_#00'],
        ['title'=>'Wo klemmt\'s?', 'unlockquantity'=>1, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Dietrich', 'unlockquantity'=>5, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Sesam, öffne dich...', 'unlockquantity'=>10, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Türöffner', 'unlockquantity'=>15, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Gentleman-Einbrecher', 'unlockquantity'=>20, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Schlüsseldienst', 'unlockquantity'=>30, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Zerberus', 'unlockquantity'=>50, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
        ['title'=>'Meister der Schlüssel', 'unlockquantity'=>100, 'associatedtag'=>':lock:', 'associatedpicto'=>'r_door_#00'],
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
        ['title'=>'Gontran Happiness', 'unlockquantity'=>20, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Lucky 7', 'unlockquantity'=>30, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Money for Nothing', 'unlockquantity'=>50, 'associatedtag'=>':chest:', 'associatedpicto'=>'r_chstxl_#00'],
        ['title'=>'Ultimate Fighter', 'unlockquantity'=>20, 'associatedtag'=>':fight:', 'associatedpicto'=>'r_wrestl_#00'],
        ['title'=>'Wilde Bestie', 'unlockquantity'=>100, 'associatedtag'=>':fight:', 'associatedpicto'=>'r_wrestl_#00'],
        ['title'=>'Rambos Großvater', 'unlockquantity'=>200, 'associatedtag'=>':fight:', 'associatedpicto'=>'r_wrestl_#00'],
        ['title'=>'Absolut keine Angst!', 'unlockquantity'=>400, 'associatedtag'=>':fight:', 'associatedpicto'=>'r_wrestl_#00'],
        ['title'=>'Stürmischer Kundschafter', 'unlockquantity'=>15, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Furchtloser Abenteurer', 'unlockquantity'=>30, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Wagemutiger Trapper', 'unlockquantity'=>70, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Wikinger', 'unlockquantity'=>150, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Außenweltreiseführer', 'unlockquantity'=>200, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Ich habe die Erde umrundet... zweimal!', 'unlockquantity'=>250, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Internet Explorer', 'unlockquantity'=>300, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Ich hab sogar das Klo abgesucht!', 'unlockquantity'=>350, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Sind wir schon da?', 'unlockquantity'=>400, 'associatedtag'=>':explo:', 'associatedpicto'=>'r_explor_#00'],
        ['title'=>'Außenweltschläfer', 'unlockquantity'=>10, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Allein in dieser Welt', 'unlockquantity'=>25, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Der mit den Zombies tanzt', 'unlockquantity'=>50, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ich sehe überall Tote!', 'unlockquantity'=>75, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ich komme heute Nacht nicht heim...', 'unlockquantity'=>100, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ein Mann gegen die Außenwelt', 'unlockquantity'=>150, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Stadtallergie', 'unlockquantity'=>200, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Wo ich hinguck, seh ich Gegend.', 'unlockquantity'=>250, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Runaway Michael', 'unlockquantity'=>300, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Ohne Dach oder Leber', 'unlockquantity'=>350, 'associatedtag'=>':camper:', 'associatedpicto'=>'r_camp_#00'],
        ['title'=>'Neugieriger Leser', 'unlockquantity'=>5, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Eifriger Leser', 'unlockquantity'=>10, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Wüstenhistoriker', 'unlockquantity'=>20, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Bibliothekar', 'unlockquantity'=>30, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Archivar', 'unlockquantity'=>40, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Erinnerungsträger', 'unlockquantity'=>60, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Erinnerungen an die Zivilisation', 'unlockquantity'=>100, 'associatedtag'=>':rptext:', 'associatedpicto'=>'r_rp_#00'],
        ['title'=>'Twinoid ist tabu', 'unlockquantity'=>20, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Keine Macht den Drogen', 'unlockquantity'=>75, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Sowas fasse ICH nicht an', 'unlockquantity'=>150, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Vorbild für die Jugend', 'unlockquantity'=>500, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Champions nehmen keine Drogen!', 'unlockquantity'=>1000, 'associatedtag'=>':clean:', 'associatedpicto'=>'r_nodrug_#00'],
        ['title'=>'Medikamentetester', 'unlockquantity'=>50, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Laborratte', 'unlockquantity'=>100, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Pille palle, 3 Tage wach!', 'unlockquantity'=>150, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Timothy Leary', 'unlockquantity'=>200, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Ich sehe ein Blauuuuuuues Tal', 'unlockquantity'=>250, 'associatedtag'=>':experimental:', 'associatedpicto'=>'r_cobaye_#00'],
        ['title'=>'Menschenfleischliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Hannibalfan', 'unlockquantity'=>40, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Totmacher', 'unlockquantity'=>80, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Jeffrey Dahmer', 'unlockquantity'=>120, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        # ['title'=>'Fido', 'unlockquantity'=>150, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'], #
        ['title'=>'Fast schon ein Zombie...', 'unlockquantity'=>180, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Schmeckt am besten wenn\'s noch schreit!', 'unlockquantity'=>250, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Die Macht der Proteine', 'unlockquantity'=>500, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Nackt und Zerfleischt', 'unlockquantity'=>1000, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'r_cannib_#00'],
        ['title'=>'Zombieliebling', 'unlockquantity'=>10, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Letzter Überlebender', 'unlockquantity'=>15, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Unzerstörbar', 'unlockquantity'=>30, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Allein gegen die Horde', 'unlockquantity'=>50, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Chuck Norris\' Ebenbild', 'unlockquantity'=>100, 'associatedtag'=>':lms:', 'associatedpicto'=>'r_surlst_#00'],
        ['title'=>'Ich bin in der Hölle aufgewachsen.', 'unlockquantity'=>5, 'associatedtag'=>':hclms:', 'associatedpicto'=>'r_suhard_#00'],
        ['title'=>'Es war wirklich zu einfach.', 'unlockquantity'=>10, 'associatedtag'=>':hclms:', 'associatedpicto'=>'r_suhard_#00'],
        ['title'=>'Ich fresse Nägel und scheiße Kugeln!', 'unlockquantity'=>20, 'associatedtag'=>':hclms:', 'associatedpicto'=>'r_suhard_#00'],
        ['title'=>'Pandämonium? Hah. Einfach.', 'unlockquantity'=>40, 'associatedtag'=>':hclms:', 'associatedpicto'=>'r_suhard_#00'],
        ['title'=>'Teilweise verrottet', 'unlockquantity'=>20, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Virenschleuder', 'unlockquantity'=>40, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Gedärmeauskotzer', 'unlockquantity'=>75, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Seuchenherd', 'unlockquantity'=>100, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Ich habe Magenschmerzen...', 'unlockquantity'=>150, 'associatedtag'=>':infect:', 'associatedpicto'=>'r_dinfec_#00'],
        ['title'=>'Verirrter Ausflügler', 'unlockquantity'=>20, 'associatedtag'=>':night:', 'associatedpicto'=>'r_doutsd_#00'],
        ['title'=>'Nächtlicher Spaziergänger', 'unlockquantity'=>100, 'associatedtag'=>':night:', 'associatedpicto'=>'r_doutsd_#00'],
        ['title'=>'Mir ist kalt hier draußen...', 'unlockquantity'=>250, 'associatedtag'=>':night:', 'associatedpicto'=>'r_doutsd_#00'],
        ['title'=>'Vertrauenswürdiger Bürger', 'unlockquantity'=>2, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Sehr erfahrener Bürger', 'unlockquantity'=>5, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Vorbild für das Volk', 'unlockquantity'=>10, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Richtwert der Gemeinschaft', 'unlockquantity'=>15, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Berühmter Veteran', 'unlockquantity'=>20, 'associatedtag'=>':ranked:', 'associatedpicto'=>'r_winbas_#00'],
        ['title'=>'Anfängerleiche', 'unlockquantity'=>2, 'associatedtag'=>':part:', 'associatedpicto'=>'r_winthi_#00'],
        ['title'=>'Rabenfreund', 'unlockquantity'=>5, 'associatedtag'=>':part:', 'associatedpicto'=>'r_winthi_#00'],
        ['title'=>'Dehydrationskollege', 'unlockquantity'=>10, 'associatedtag'=>':part:', 'associatedpicto'=>'r_winthi_#00'],
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
        ['title'=>'Seelenführer der Gemeinschaft', 'unlockquantity'=>50, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Charismatischer Prophet', 'unlockquantity'=>100, 'associatedtag'=>':bgum:', 'associatedpicto'=>'r_bgum_#00'],
        ['title'=>'Motivierter Messebesucher', 'unlockquantity'=>1, 'associatedtag'=>':fjv2:', 'associatedpicto'=>'r_fjv2_#00'],
        ['title'=>'Verdammt in Saarbrücken', 'unlockquantity'=>1, 'associatedtag'=>':fjvani:', 'associatedpicto'=>'r_fjvani_#00'],
        ['title'=>'FJV\'08 Kanonenfutter', 'unlockquantity'=>1, 'associatedtag'=>':fjv:', 'associatedpicto'=>'r_fjv_#00'],
        ['title'=>'Kleiner Guru', 'unlockquantity'=>1, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Überzeugender Guru', 'unlockquantity'=>3, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Frischfleischhändler', 'unlockquantity'=>5, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Seelenhändler', 'unlockquantity'=>10, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Sehr überzeugender Pate', 'unlockquantity'=>15, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Wüstenspekulant', 'unlockquantity'=>20, 'associatedtag'=>':rrefer:', 'associatedpicto'=>'r_rrefer_#00'],
        ['title'=>'Held der Community!', 'unlockquantity'=>1, 'associatedtag'=>':comu:', 'associatedpicto'=>'r_comu_#00'],
        ['title'=>'Bringt Sachen ins Rollen!', 'unlockquantity'=>1, 'associatedtag'=>':comu2:', 'associatedpicto'=>'r_comu2_#00'],
        ['title'=>'Nobler Spender', 'unlockquantity'=>10, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Immer zur Stelle', 'unlockquantity'=>25, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Samariter', 'unlockquantity'=>50, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Freigiebiger Bürger', 'unlockquantity'=>100, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Messias', 'unlockquantity'=>150, 'associatedtag'=>':share:', 'associatedpicto'=>'r_share_#00'],
        ['title'=>'Der Weihnachtsmann ist ein Schlawiner', 'unlockquantity'=>10, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Santa Klaus ist ein Schummler', 'unlockquantity'=>25, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Santa Klaus ist Abfall', 'unlockquantity'=>50, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Santa Klaus ist Abschaum', 'unlockquantity'=>75, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Hängt Santa Klaus!', 'unlockquantity'=>100, 'associatedtag'=>':santa:', 'associatedpicto'=>'r_santac_#00'],
        ['title'=>'Treffen der 4. Art', 'unlockquantity'=>2, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Hast Du mal Feuer?', 'unlockquantity'=>10, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Medium mit Preisnachlass', 'unlockquantity'=>20, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Amophilis Psychotropes', 'unlockquantity'=>30, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Blauer Feuerspucker', 'unlockquantity'=>50, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'I see dead people 0_0', 'unlockquantity'=>80, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Soul Man', 'unlockquantity'=>120, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Lebenssammler', 'unlockquantity'=>180, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Schnitter der Aussenwelt', 'unlockquantity'=>300, 'associatedtag'=>':collect:', 'associatedpicto'=>'r_collec_#00'],
        ['title'=>'Bei meiner Seel', 'unlockquantity'=>100, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Für immer und seelich', 'unlockquantity'=>500, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Noble Seele', 'unlockquantity'=>1000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Beseelt', 'unlockquantity'=>2000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Reinkarnator', 'unlockquantity'=>3000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Göttliche Seele', 'unlockquantity'=>5000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Open-chakra', 'unlockquantity'=>7000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Tausend-und-ein-Leben', 'unlockquantity'=>9000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Buddha', 'unlockquantity'=>12000, 'associatedtag'=>':ptame:', 'associatedpicto'=>'r_ptame_#00'],
        ['title'=>'Ich bin ein Gott! Ich werde ewig leben!', 'unlockquantity'=>1, 'associatedtag'=>':ermwin:', 'associatedpicto'=>'r_ermwin_#00'],
        ['title'=>'Eine Stadt sie zu knechten!', 'unlockquantity'=>1, 'associatedtag'=>':cott:', 'associatedpicto'=>'r_cott_#00'],
        ['title'=>'Verrückter Skeptiker', 'unlockquantity'=>10, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Seelenknipser', 'unlockquantity'=>25, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Wächter der Seelen', 'unlockquantity'=>75, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Die Wahrheit ist irgendwo da draußen', 'unlockquantity'=>150, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Ich glaube!', 'unlockquantity'=>300, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Sektenmitglied', 'unlockquantity'=>800, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Kosmischer Anführer', 'unlockquantity'=>1500, 'associatedtag'=>':mystic:', 'associatedpicto'=>'r_mystic_#00'],
        ['title'=>'Rauch? Welcher Rauch?', 'unlockquantity'=>10, 'associatedtag'=>':nuke:', 'associatedpicto'=>'r_dnucl_#00'],
        ['title'=>'Leckstopper', 'unlockquantity'=>20, 'associatedtag'=>':nuke:', 'associatedpicto'=>'r_dnucl_#00'],
        ['title'=>'Licht in der Nacht', 'unlockquantity'=>30, 'associatedtag'=>':nuke:', 'associatedpicto'=>'r_dnucl_#00'],
        ['title'=>'RadioactiveMan', 'unlockquantity'=>50, 'associatedtag'=>':nuke:', 'associatedpicto'=>'r_dnucl_#00'],
        ['title'=>'Strahlender Bürger', 'unlockquantity'=>60, 'associatedtag'=>':nuke:', 'associatedpicto'=>'r_dnucl_#00'],
        ['title'=>'Hölle, das esse ich jeden Morgen.', 'unlockquantity'=>50, 'associatedtag'=>':pande:', 'associatedpicto'=>'r_pande_#00'],
        ['title'=>'Abonnent der Pandämoniumsauna', 'unlockquantity'=>150, 'associatedtag'=>':pande:', 'associatedpicto'=>'r_pande_#00'],
        ['title'=>'Pandämonium ist meine Liebe!', 'unlockquantity'=>300, 'associatedtag'=>':pande:', 'associatedpicto'=>'r_pande_#00'],
        ['title'=>'Luzifers Tanzpartner', 'unlockquantity'=>500, 'associatedtag'=>':pande:', 'associatedpicto'=>'r_pande_#00'],
        ['title'=>'Ultimativer Horden-Erkunder', 'unlockquantity'=>1000, 'associatedtag'=>':pande:', 'associatedpicto'=>'r_pande_#00'],
        ['title'=>'Irische Taschen sind prall gefüllt!', 'unlockquantity'=>10, 'associatedtag'=>':lepre:', 'associatedpicto'=>'r_lepre_#00'],
        ['title'=>'Wo ist mein Topf voll Gold, diebische Elster!', 'unlockquantity'=>25, 'associatedtag'=>':lepre:', 'associatedpicto'=>'r_lepre_#00'],
        ['title'=>'Dieser Kobold ist unaufhaltbar.', 'unlockquantity'=>50, 'associatedtag'=>':lepre:', 'associatedpicto'=>'r_lepre_#00'],
        ['title'=>'Verbrennt ihn! Sieh nach ob seine Asche grün wird!', 'unlockquantity'=>75, 'associatedtag'=>':lepre:', 'associatedpicto'=>'r_lepre_#00'],
        ['title'=>'Ein Wohnwagen ohne Räder???', 'unlockquantity'=>100, 'associatedtag'=>':lepre:', 'associatedpicto'=>'r_lepre_#00'],
        ['title'=>'Mitglied des Roten Kreuzes', 'unlockquantity'=>1, 'associatedtag'=>':easter:', 'associatedpicto'=>'r_paques_#00'],
        ['title'=>'Ich bin geheilt', 'unlockquantity'=>1, 'associatedtag'=>':gsp:', 'associatedpicto'=>'r_gsp_#00'],
        ['title'=>'Keine Fehler hi&e%12;r', 'unlockquantity'=>1, 'associatedtag'=>':ripflash:', 'associatedpicto'=>'r_ripflash_#00'],
        ['title'=>'Patient Null', 'unlockquantity'=>1, 'associatedtag'=>':beta2:', 'associatedpicto'=>'r_beta2_#00'],
        ['title'=>'Es ist der Tod der mich fürchten sollte!', 'unlockquantity'=>1, 'associatedtag'=>':derwin:', 'associatedpicto'=>'r_derwin_#00'],
        ['title'=>'Sandmann', 'unlockquantity'=>10, 'associatedtag'=>':sand:', 'associatedpicto'=>'r_sandb_#00'],
        ['title'=>'Sandy Maverick', 'unlockquantity'=>50, 'associatedtag'=>':sand:', 'associatedpicto'=>'r_sandb_#00'],
        ['title'=>'Wegweiser', 'unlockquantity'=>300, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Praktischer Begleiter', 'unlockquantity'=>1000, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Erleuchteter Anführer', 'unlockquantity'=>2500, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Erleuchteter Prediger', 'unlockquantity'=>5000, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Guru der Liebe', 'unlockquantity'=>8000, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Höret meine Stimme', 'unlockquantity'=>15000, 'associatedtag'=>':noob:', 'associatedpicto'=>'r_guide_#00'],
        ['title'=>'Zeuge des Armageddon', 'unlockquantity'=>1, 'associatedtag'=>':arma:', 'associatedpicto'=>'r_armag_#00'],
        ['title'=>'Ewiger Zweiter', 'unlockquantity'=>5, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Ein Wächter stirbt, aber gibt niemals auf', 'unlockquantity'=>10, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Der Zweitletzte Mohikaner', 'unlockquantity'=>20, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Faaaaaaaast, ach verdammt', 'unlockquantity'=>30, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Bis zum Ende und einen Schritt weiter', 'unlockquantity'=>40, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Schließt die Ränke und haltet durch', 'unlockquantity'=>60, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Hallo... ist da draußen jemand?', 'unlockquantity'=>100, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Haltet eure Position!', 'unlockquantity'=>150, 'associatedtag'=>':last:', 'associatedpicto'=>'r_surgrp_#00'],
        ['title'=>'Nur ein Finger', 'unlockquantity'=>30, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Plünderer der Schnapsläden', 'unlockquantity'=>60, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Hennessy Highwayman', 'unlockquantity'=>150, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Barney Gumble der Außenwelt', 'unlockquantity'=>300, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Fass das Saufen lieber sein', 'unlockquantity'=>400, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Gib mir was zu saufen oder es knallt!', 'unlockquantity'=>500, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Alles unter 55% ist Wasser', 'unlockquantity'=>600, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
        ['title'=>'Dialyse', 'unlockquantity'=>800, 'associatedtag'=>':last:', 'associatedpicto'=>'r_alcool_#00'],
    ];

    protected static $icon_data = [
        ['icon'=>'r_heroac', 'unlockquantity'=>15, 'associatedpicto'=>'r_heroac_#00'],
        ['icon'=>'r_cwater', 'unlockquantity'=>50, 'associatedpicto'=>'r_cwater_#00'],
        ['icon'=>'r_solban', 'unlockquantity'=>20, 'associatedpicto'=>'r_solban_#00'],
        ['icon'=>'r_cookr', 'unlockquantity'=>10, 'associatedpicto'=>'r_cookr_#00'],
        ['icon'=>'r_animal', 'unlockquantity'=>30, 'associatedpicto'=>'r_animal_#00'],
        ['icon'=>'r_cmplst', 'unlockquantity'=>10, 'associatedpicto'=>'r_cmplst_#00'],
        ['icon'=>'r_camp', 'unlockquantity'=>10, 'associatedpicto'=>'r_camp_#00'],
        ['icon'=>'r_cannib', 'unlockquantity'=>10, 'associatedpicto'=>'r_cannib_#00'],
        ['icon'=>'r_watgun', 'unlockquantity'=>10, 'associatedpicto'=>'r_watgun_#00'],
        ['icon'=>'r_chstxl', 'unlockquantity'=>5, 'associatedpicto'=>'r_chstxl_#00'],
        ['icon'=>'r_buildr', 'unlockquantity'=>100, 'associatedpicto'=>'r_buildr_#00'],
        ['icon'=>'r_nodrug', 'unlockquantity'=>20, 'associatedpicto'=>'r_nodrug_#00'],
        ['icon'=>'r_collec', 'unlockquantity'=>2, 'associatedpicto'=>'r_collec_#00'],
        ['icon'=>'r_wrestl', 'unlockquantity'=>20, 'associatedpicto'=>'r_wrestl_#00'],
        ['icon'=>'r_ebuild', 'unlockquantity'=>1, 'associatedpicto'=>'r_ebuild_#00'],
        ['icon'=>'r_digger', 'unlockquantity'=>50, 'associatedpicto'=>'r_digger_#00'],
        ['icon'=>'r_deco', 'unlockquantity'=>100, 'associatedpicto'=>'r_deco_#00'],
        ['icon'=>'r_cobaye', 'unlockquantity'=>50, 'associatedpicto'=>'r_cobaye_#00'],
        ['icon'=>'r_ruine', 'unlockquantity'=>5, 'associatedpicto'=>'r_ruine_#00'],
        ['icon'=>'r_explor', 'unlockquantity'=>15, 'associatedpicto'=>'r_explor_#00'],
        ['icon'=>'r_explo2', 'unlockquantity'=>5, 'associatedpicto'=>'r_explo2_#00'],
        ['icon'=>'r_share', 'unlockquantity'=>10, 'associatedpicto'=>'r_share_#00'],
        ['icon'=>'r_guide', 'unlockquantity'=>300, 'associatedpicto'=>'r_guide_#00'],
        ['icon'=>'r_drgmkr', 'unlockquantity'=>10, 'associatedpicto'=>'r_drgmkr_#00'],
        ['icon'=>'r_theft', 'unlockquantity'=>10, 'associatedpicto'=>'r_theft_#00'],
        ['icon'=>'r_maso', 'unlockquantity'=>20, 'associatedpicto'=>'r_maso_#00'],
        ['icon'=>'r_bgum', 'unlockquantity'=>1, 'associatedpicto'=>'r_bgum_#00'],
        ['icon'=>'r_ebcstl', 'unlockquantity'=>5, 'associatedpicto'=>'r_ebcstl_#00'],
        ['icon'=>'r_ebpmv', 'unlockquantity'=>5, 'associatedpicto'=>'r_ebpmv_#00'],
        ['icon'=>'r_ebgros', 'unlockquantity'=>5, 'associatedpicto'=>'r_ebgros_#00'],
        ['icon'=>'r_ebcrow', 'unlockquantity'=>5, 'associatedpicto'=>'r_ebcrow_#00'],
        ['icon'=>'r_jtamer', 'unlockquantity'=>10, 'associatedpicto'=>'r_jtamer_#00'],
        ['icon'=>'r_jrangr', 'unlockquantity'=>10, 'associatedpicto'=>'r_jrangr_#00'],
        ['icon'=>'r_jermit', 'unlockquantity'=>10, 'associatedpicto'=>'r_jermit_#00'],
        ['icon'=>'r_jcolle', 'unlockquantity'=>10, 'associatedpicto'=>'r_jcolle_#00'],
        ['icon'=>'r_jguard', 'unlockquantity'=>10, 'associatedpicto'=>'r_jguard_#00'],
        ['icon'=>'r_jtech', 'unlockquantity'=>10, 'associatedpicto'=>'r_jtech_#00'],
        ['icon'=>'r_dinfec', 'unlockquantity'=>20, 'associatedpicto'=>'r_dinfec_#00'],
        ['icon'=>'r_dnucl', 'unlockquantity'=>10, 'associatedpicto'=>'r_dnucl_#00'],
        ['icon'=>'r_surlst', 'unlockquantity'=>10, 'associatedpicto'=>'r_surlst_#00'],
        ['icon'=>'r_suhard', 'unlockquantity'=>5, 'associatedpicto'=>'r_suhard_#00'],
        ['icon'=>'r_mystic', 'unlockquantity'=>10, 'associatedpicto'=>'r_mystic_#00'],
        ['icon'=>'r_doutsd', 'unlockquantity'=>20, 'associatedpicto'=>'r_doutsd_#00'],
        ['icon'=>'r_door', 'unlockquantity'=>1, 'associatedpicto'=>'r_door_#00'],
        ['icon'=>'r_plundr', 'unlockquantity'=>30, 'associatedpicto'=>'r_plundr_#00'],
        ['icon'=>'r_wondrs', 'unlockquantity'=>20, 'associatedpicto'=>'r_wondrs_#00'],
        ['icon'=>'r_repair', 'unlockquantity'=>15, 'associatedpicto'=>'r_repair_#00'],
        ['icon'=>'r_brep', 'unlockquantity'=>100, 'associatedpicto'=>'r_brep_#00'],
        ['icon'=>'r_rp', 'unlockquantity'=>5, 'associatedpicto'=>'r_rp_#00'],
        ['icon'=>'r_cgarb', 'unlockquantity'=>60, 'associatedpicto'=>'r_cgarb_#00'],
        ['icon'=>'r_batgun', 'unlockquantity'=>15, 'associatedpicto'=>'r_batgun_#00'],
        ['icon'=>'r_pande', 'unlockquantity'=>50, 'associatedpicto'=>'r_pande_#00'],
        ['icon'=>'r_tronco', 'unlockquantity'=>5, 'associatedpicto'=>'r_tronco_#00'],
        ['icon'=>'r_guard', 'unlockquantity'=>20, 'associatedpicto'=>'r_guard_#00'],
        ['icon'=>'r_winbas', 'unlockquantity'=>2, 'associatedpicto'=>'r_winbas_#00'],
        ['icon'=>'r_wintop', 'unlockquantity'=>1, 'associatedpicto'=>'r_wintop_#00'],
        ['icon'=>'r_winthi', 'unlockquantity'=>2, 'associatedpicto'=>'r_winthi_#00'],
        ['icon'=>'r_killz', 'unlockquantity'=>100, 'associatedpicto'=>'r_killz_#00'],
        ['icon'=>'r_beta', 'unlockquantity'=>1, 'associatedpicto'=>'r_beta_#00'],
        ['icon'=>'r_sandb', 'unlockquantity'=>10, 'associatedpicto'=>'r_sandb_#00'],
        ['icon'=>'r_paques', 'unlockquantity'=>1, 'associatedpicto'=>'r_paques_#00'],
        ['icon'=>'r_santac', 'unlockquantity'=>10, 'associatedpicto'=>'r_santac_#00'],
        ['icon'=>'r_armag', 'unlockquantity'=>1, 'associatedpicto'=>'r_armag_#00'],
        ['icon'=>'r_ginfec', 'unlockquantity'=>1, 'associatedpicto'=>'r_ginfec_#00'],
        ['icon'=>'r_ptame', 'unlockquantity'=>100, 'associatedpicto'=>'r_ptame_#00'],
        ['icon'=>'r_jsham', 'unlockquantity'=>10, 'associatedpicto'=>'r_jsham_#00'],
        ['icon'=>'r_rrefer', 'unlockquantity'=>1, 'associatedpicto'=>'r_rrefer_#00'],
        ['icon'=>'r_fjvani', 'unlockquantity'=>1, 'associatedpicto'=>'r_fjvani_#00'],
        ['icon'=>'r_fjv2', 'unlockquantity'=>1, 'associatedpicto'=>'r_fjv2_#00'],
        ['icon'=>'r_fjv', 'unlockquantity'=>1, 'associatedpicto'=>'r_fjv_#00'],
        ['icon'=>'r_comu', 'unlockquantity'=>1, 'associatedpicto'=>'r_comu_#00'],
        ['icon'=>'r_comu2', 'unlockquantity'=>1, 'associatedpicto'=>'r_comu2_#00'],
        ['icon'=>'r_cott', 'unlockquantity'=>1, 'associatedpicto'=>'r_cott_#00'],
        ['icon'=>'r_ermwin', 'unlockquantity'=>1, 'associatedpicto'=>'r_ermwin_#00'],
        ['icon'=>'r_cdhwin', 'unlockquantity'=>1, 'associatedpicto'=>'r_cdhwin_#00'],
        ['icon'=>'r_defwin', 'unlockquantity'=>1, 'associatedpicto'=>'r_defwin_#00'],
        ['icon'=>'r_kohlmb', 'unlockquantity'=>1, 'associatedpicto'=>'r_kohlmb_#00'],
        ['icon'=>'r_lepre', 'unlockquantity'=>10, 'associatedpicto'=>'r_lepre_#00'],
        ['icon'=>'r_goodg', 'unlockquantity'=>1, 'associatedpicto'=>'r_goodg_#00'],
        ['icon'=>'r_surgrp', 'unlockquantity'=>5, 'associatedpicto'=>'r_surgrp_#00'],
        ['icon'=>'r_alcool', 'unlockquantity'=>30, 'associatedpicto'=>'r_alcool_#00'],
        ['icon'=>'r_gsp', 'unlockquantity'=>1, 'associatedpicto'=>'r_gsp_#00'],
        ['icon'=>'r_beta2', 'unlockquantity'=>1, 'associatedpicto'=>'r_beta2_#00'],
        ['icon'=>'r_ripflash', 'unlockquantity'=>1, 'associatedpicto'=>'r_ripflash_#00'],
    ];

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Awards: ' . count(static::$award_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$award_data) );

        $titles = [];

        foreach(static::$award_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)->getAwardByTitle($entry['title']) ?? new AwardPrototype();

            $pp = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $entry['associatedpicto']]);
            if ($pp === null) {
                $out->writeln("<error>Skipping award '{$entry['title']}' because the associated picto '{$entry['associatedpicto']}' does not exist.</error>");
                continue;
            }

            $entity
                ->setAssociatedPicto( $pp )
                ->setAssociatedTag($entry['associatedtag'])
                ->setTitle($entry['title'])
                ->setIcon(null)
                ->setUnlockQuantity($entry['unlockquantity']);

            $titles[] = $entry['title'];

            $manager->persist($entity);
            $progress->advance();
        }

        // Remove obsolete entries
        $entities_to_delete = $this->entityManager->getRepository(AwardPrototype::class)->createQueryBuilder('a')
            ->andWhere('a.title NOT IN (:titles)')->andWhere('a.title IS NOT NULL')->setParameter('titles', $titles)->getQuery()->execute();
        foreach ($entities_to_delete as $entity)
            $this->entityManager->remove($entity);


        $manager->flush();
        $progress->finish();
    }

    private function insertIconAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Icon Awards: ' . count(static::$award_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$award_data) );

        $icons = [];

        foreach(static::$icon_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)->getAwardByIcon($entry['icon']) ?? new AwardPrototype();

            $pp = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => $entry['associatedpicto']]);
            if ($pp === null) {
                $out->writeln("<error>Skipping award '{$entry['icon']}' because the associated picto '{$entry['associatedpicto']}' does not exist.</error>");
                continue;
            }

            $entity
                ->setAssociatedPicto( $pp )
                ->setAssociatedTag(null)
                ->setTitle(null)
                ->setIcon($entry['icon'])
                ->setUnlockQuantity($entry['unlockquantity']);

            $icons[] = $entry['icon'];

            $manager->persist($entity);
            $progress->advance();
        }

        // Remove obsolete entries
        $entities_to_delete = $this->entityManager->getRepository(AwardPrototype::class)->createQueryBuilder('a')
            ->andWhere('a.icon NOT IN (:icons)')->andWhere('a.icon IS NOT NULL')->setParameter('icons', $icons)->getQuery()->execute();
        foreach ($entities_to_delete as $entity)
            $this->entityManager->remove($entity);

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
        $this->insertIconAwards($manager, $output);
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
