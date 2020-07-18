<?php


namespace App\DataFixtures;


use App\Entity\AwardPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class AwardFixtures extends Fixture {

    private $entityManager;

    protected static $award_data = [
        ['title'=>'Pfadfinder', 'unlockquantity'=>10, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x10'],
        ['title'=>'Ninja', 'unlockquantity'=>25, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x25'],
        ['title'=>'Green Beret', 'unlockquantity'=>75, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x75'],
        ['title'=>'Schattenmann', 'unlockquantity'=>150, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x150'],
        ['title'=>'Wüstenphantom', 'unlockquantity'=>300, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x300'],
        ['title'=>'Solid Snake war gestern...', 'unlockquantity'=>800, 'associatedtag'=>':proscout:', 'associatedpicto'=>'Aufklärer', 'iconpath'=>'build/images/pictos/r_jrangr.gif','titlehovertext'=>'Aufklärer x800'],
        ['title'=>'Sandarbeiter', 'unlockquantity'=>10, 'associatedtag'=>':proscav:', 'associatedpicto'=>'Buddler', 'iconpath'=>'build/images/pictos/r_jcolle.gif','titlehovertext'=>'Buddler x10'],
        ['title'=>'Wüstenspringmaus', 'unlockquantity'=>25, 'associatedtag'=>':proscav:', 'associatedpicto'=>'Buddler', 'iconpath'=>'build/images/pictos/r_jcolle.gif','titlehovertext'=>'Buddler x25'],
        ['title'=>'Großer Ameisenbär', 'unlockquantity'=>75, 'associatedtag'=>':proscav:', 'associatedpicto'=>'Buddler', 'iconpath'=>'build/images/pictos/r_jcolle.gif','titlehovertext'=>'Buddler x75'],
        ['title'=>'Wüstenfuchs', 'unlockquantity'=>150, 'associatedtag'=>':proscav:', 'associatedpicto'=>'Buddler', 'iconpath'=>'build/images/pictos/r_jcolle.gif','titlehovertext'=>'Buddler x150'],
        ['title'=>'Ich sehe Alles!', 'unlockquantity'=>300, 'associatedtag'=>':proscav:', 'associatedpicto'=>'Buddler', 'iconpath'=>'build/images/pictos/r_jcolle.gif','titlehovertext'=>'Buddler x300'],
        ['title'=>'Tierliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':protamer:', 'associatedpicto'=>'Dompteur', 'iconpath'=>'build/images/pictos/r_jtamer.gif','titlehovertext'=>'Dompteur x10'],
        ['title'=>'Malteserzüchter', 'unlockquantity'=>25, 'associatedtag'=>':protamer:', 'associatedpicto'=>'Dompteur', 'iconpath'=>'build/images/pictos/r_jtamer.gif','titlehovertext'=>'Dompteur x25'],
        ['title'=>'Ich bändige Bestien', 'unlockquantity'=>75, 'associatedtag'=>':protamer:', 'associatedpicto'=>'Dompteur', 'iconpath'=>'build/images/pictos/r_jtamer.gif','titlehovertext'=>'Dompteur x75'],
        ['title'=>'Nie ohne meinen Hund!', 'unlockquantity'=>150, 'associatedtag'=>':protamer:', 'associatedpicto'=>'Dompteur', 'iconpath'=>'build/images/pictos/r_jtamer.gif','titlehovertext'=>'Dompteur x150'],
        ['title'=>'Hundewurst schmeckt gar nicht schlecht!', 'unlockquantity'=>300, 'associatedtag'=>':protamer:', 'associatedpicto'=>'Dompteur', 'iconpath'=>'build/images/pictos/r_jtamer.gif','titlehovertext'=>'Dompteur x300'],
        ['title'=>'Wurmfresser', 'unlockquantity'=>10, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x10'],
        ['title'=>'Meister im Würmerfinden', 'unlockquantity'=>25, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x25'],
        ['title'=>'Gefräßiger Bürger', 'unlockquantity'=>75, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x75'],
        ['title'=>'Wüstenwurmzüchter', 'unlockquantity'=>150, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x150'],
        ['title'=>'Ich brauche niemanden!', 'unlockquantity'=>300, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x300'],
        ['title'=>'Heraklit der Außenwelt', 'unlockquantity'=>800, 'associatedtag'=>':prosurv:', 'associatedpicto'=>'Einsiedler', 'iconpath'=>'build/images/pictos/r_jermit.gif','titlehovertext'=>'Einsiedler x800'],
        ['title'=>'Diplomierter Scharlatan', 'unlockquantity'=>10, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_jsham.gif','titlehovertext'=>'Schamane x10'],
        ['title'=>'Schlimmer Finger', 'unlockquantity'=>25, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_jsham.gif','titlehovertext'=>'Schamane x25'],
        ['title'=>'Seelenverwerter', 'unlockquantity'=>75, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_jsham.gif','titlehovertext'=>'Schamane x75'],
        ['title'=>'Mystischer Seher', 'unlockquantity'=>150, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_jsham.gif','titlehovertext'=>'Schamane x150'],
        ['title'=>'Voodoo Sorceror', 'unlockquantity'=>300, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_jsham.gif','titlehovertext'=>'Schamane x300'],
        ['title'=>'Yo, wir schaffen das!', 'unlockquantity'=>10, 'associatedtag'=>':protech:', 'associatedpicto'=>'Techniker', 'iconpath'=>'build/images/pictos/r_jtech.gif','titlehovertext'=>'Techniker x10'],
        ['title'=>'Kleiner Schraubendreher', 'unlockquantity'=>25, 'associatedtag'=>':protech:', 'associatedpicto'=>'Techniker', 'iconpath'=>'build/images/pictos/r_jtech.gif','titlehovertext'=>'Techniker x25'],
        ['title'=>'Schweizer Taschenmesser', 'unlockquantity'=>75, 'associatedtag'=>':protech:', 'associatedpicto'=>'Techniker', 'iconpath'=>'build/images/pictos/r_jtech.gif','titlehovertext'=>'Techniker x75'],
        ['title'=>'Unermüdlicher Schrauber', 'unlockquantity'=>150, 'associatedtag'=>':protech:', 'associatedpicto'=>'Techniker', 'iconpath'=>'build/images/pictos/r_jtech.gif','titlehovertext'=>'Techniker x150'],
        ['title'=>'Seele des Handwerks', 'unlockquantity'=>300, 'associatedtag'=>':protech:', 'associatedpicto'=>'Techniker', 'iconpath'=>'build/images/pictos/r_jtech.gif','titlehovertext'=>'Techniker x300'],
        ['title'=>'Die Mauer', 'unlockquantity'=>10, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x10'],
        ['title'=>'Höllenwächter', 'unlockquantity'=>25, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x25'],
        ['title'=>'Kerberos', 'unlockquantity'=>75, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x75'],
        ['title'=>'Die letzte Verteidigungslinie', 'unlockquantity'=>150, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x150'],
        ['title'=>'Du kommst hier NICHT durch!', 'unlockquantity'=>300, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x300'],
        ['title'=>'Hekatoncheir', 'unlockquantity'=>800, 'associatedtag'=>':proguard:', 'associatedpicto'=>'Wächter', 'iconpath'=>'build/images/pictos/r_jguard.gif','titlehovertext'=>'Wächter x800'],
        ['title'=>'Kantinenkoch', 'unlockquantity'=>10, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x10'],
        ['title'=>'Kleiner Küchenchef', 'unlockquantity'=>25, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x25'],
        ['title'=>'Meister Eintopf', 'unlockquantity'=>50, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x50'],
        ['title'=>'Großer Wüstenkonditor', 'unlockquantity'=>100, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x100'],
        ['title'=>'Begnadeter Wüstenkonditor', 'unlockquantity'=>250, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x250'],
        ['title'=>'Cooking Mama', 'unlockquantity'=>500, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x500'],
        ['title'=>'Meisterhafter Kochlöffelschwinger', 'unlockquantity'=>1000, 'associatedtag'=>':tasty:', 'associatedpicto'=>'Hausmannskost', 'iconpath'=>'build/images/pictos/r_cookr.gif','titlehovertext'=>'Hausmannskost x1000'],
        ['title'=>'Amateur-Laborratte', 'unlockquantity'=>10, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x10'],
        ['title'=>'Kleiner Präparator', 'unlockquantity'=>25, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x25'],
        ['title'=>'Chemiker von um die Ecke', 'unlockquantity'=>50, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x50'],
        ['title'=>'Produkttester', 'unlockquantity'=>100, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x100'],
        ['title'=>'Wüstenstadt-Dealer', 'unlockquantity'=>250, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x250'],
        ['title'=>'X-Men Leser', 'unlockquantity'=>15, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x15'],
        ['title'=>'Aussergewöhnlicher Bürger', 'unlockquantity'=>30, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x30'],
        ['title'=>'Wunder', 'unlockquantity'=>50, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x50'],
        ['title'=>'Werdender Superheld', 'unlockquantity'=>70, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x70'],
        ['title'=>'Volksheld', 'unlockquantity'=>90, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x90'],
        ['title'=>'Neo', 'unlockquantity'=>120, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x120'],
        ['title'=>'Erlöser der Menschheit', 'unlockquantity'=>150, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x150'],
        ['title'=>'Außenweltlegende', 'unlockquantity'=>200, 'associatedtag'=>':hero:', 'associatedpicto'=>'Heldentaten', 'iconpath'=>'build/images/pictos/r_heroac.gif','titlehovertext'=>'Heldentaten x200'],
        ['title'=>'Abstauber', 'unlockquantity'=>20, 'associatedtag'=>':trash:', 'associatedpicto'=>'Autarker Verbannter', 'iconpath'=>'build/images/pictos/r_solban.gif','titlehovertext'=>'Autarker Verbannter x20'],
        ['title'=>'Müllkind', 'unlockquantity'=>100, 'associatedtag'=>':trash:', 'associatedpicto'=>'Autarker Verbannter', 'iconpath'=>'build/images/pictos/r_solban.gif','titlehovertext'=>'Autarker Verbannter x100'],
        ['title'=>'Maulheld', 'unlockquantity'=>10, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x10'],
        ['title'=>'Lump', 'unlockquantity'=>30, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x30'],
        ['title'=>'Schaumschläger', 'unlockquantity'=>40, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x40'],
        ['title'=>'Dieb', 'unlockquantity'=>50, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x50'],
        ['title'=>'Fantomas', 'unlockquantity'=>75, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x75'],
        ['title'=>'Ali Baba', 'unlockquantity'=>100, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x100'],
        ['title'=>'Meisterdieb', 'unlockquantity'=>500, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x500'],
        ['title'=>'Kleptomane', 'unlockquantity'=>1000, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x1000'],
        ['title'=>'Dein Haus ist mein Haus', 'unlockquantity'=>2000, 'associatedtag'=>':thief:', 'associatedpicto'=>'Diebstähle', 'iconpath'=>'build/images/pictos/r_theft.gif','titlehovertext'=>'Diebstähle x2000'],
        ['title'=>'Ästhet', 'unlockquantity'=>100, 'associatedtag'=>':deco:', 'associatedpicto'=>'Hauseinrichtung', 'iconpath'=>'build/images/pictos/r_deco.gif','titlehovertext'=>'Hauseinrichtung x100'],
        ['title'=>'Schaufenstergestalter', 'unlockquantity'=>250, 'associatedtag'=>':deco:', 'associatedpicto'=>'Hauseinrichtung', 'iconpath'=>'build/images/pictos/r_deco.gif','titlehovertext'=>'Hauseinrichtung x250'],
        ['title'=>'Innenarchitekt', 'unlockquantity'=>500, 'associatedtag'=>':deco:', 'associatedpicto'=>'Hauseinrichtung', 'iconpath'=>'build/images/pictos/r_deco.gif','titlehovertext'=>'Hauseinrichtung x500'],
        ['title'=>'Möbelgourmet', 'unlockquantity'=>1000, 'associatedtag'=>':deco:', 'associatedpicto'=>'Hauseinrichtung', 'iconpath'=>'build/images/pictos/r_deco.gif','titlehovertext'=>'Hauseinrichtung x1000'],
        ['title'=>'Ordnung muss sein!', 'unlockquantity'=>1500, 'associatedtag'=>':deco:', 'associatedpicto'=>'Hauseinrichtung', 'iconpath'=>'build/images/pictos/r_deco.gif','titlehovertext'=>'Hauseinrichtung x1500'],
        ['title'=>'Plünderer', 'unlockquantity'=>30, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x30'],
        ['title'=>'Aasgeier', 'unlockquantity'=>60, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x60'],
        ['title'=>'Hyäne', 'unlockquantity'=>100, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x100'],
        ['title'=>'Wegelagerer post mortem', 'unlockquantity'=>200, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x200'],
        ['title'=>'Hungriger Schakal', 'unlockquantity'=>400, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x400'],
        ['title'=>'Kojote der kargen Steppen', 'unlockquantity'=>600, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x600'],
        ['title'=>'Fingerschmied', 'unlockquantity'=>1000, 'associatedtag'=>':pillage:', 'associatedpicto'=>'Häuserplünderungen', 'iconpath'=>'build/images/pictos/r_plundr.gif','titlehovertext'=>'Häuserplünderungen x1000'],
        ['title'=>'Nein, das Ding fasse ich nicht an.', 'unlockquantity'=>50, 'associatedtag'=>':shower:', 'associatedpicto'=>'Leichenwäscher', 'iconpath'=>'build/images/pictos/r_cwater.gif','titlehovertext'=>'Leichenwäscher x50'],
        ['title'=>'Leichenhygieniker', 'unlockquantity'=>100, 'associatedtag'=>':shower:', 'associatedpicto'=>'Leichenwäscher', 'iconpath'=>'build/images/pictos/r_cwater.gif','titlehovertext'=>'Leichenwäscher x100'],
        ['title'=>'Ich liebe den Geruch von gewaschenen Leichen am Morgen.', 'unlockquantity'=>200, 'associatedtag'=>':shower:', 'associatedpicto'=>'Leichenwäscher', 'iconpath'=>'build/images/pictos/r_cwater.gif','titlehovertext'=>'Leichenwäscher x200'],
        ['title'=>'Müllmann', 'unlockquantity'=>60, 'associatedtag'=>':drag:', 'associatedpicto'=>'Leichenentsorger', 'iconpath'=>'build/images/pictos/r_cgarb.gif','titlehovertext'=>'Leichenentsorger x60'],
        ['title'=>'Erfahrener Müllmann', 'unlockquantity'=>100, 'associatedtag'=>':drag:', 'associatedpicto'=>'Leichenentsorger', 'iconpath'=>'build/images/pictos/r_cgarb.gif','titlehovertext'=>'Leichenentsorger x100'],
        ['title'=>'Entsorgungsfachmann', 'unlockquantity'=>200, 'associatedtag'=>':drag:', 'associatedpicto'=>'Leichenentsorger', 'iconpath'=>'build/images/pictos/r_cgarb.gif','titlehovertext'=>'Leichenentsorger x200'],
        ['title'=>'SM Anhänger', 'unlockquantity'=>20, 'associatedtag'=>':maso:', 'associatedpicto'=>'Masochismus', 'iconpath'=>'build/images/pictos/r_maso.gif','titlehovertext'=>'Masochismus x20'],
        ['title'=>'SM Spezialist', 'unlockquantity'=>40, 'associatedtag'=>':maso:', 'associatedpicto'=>'Masochismus', 'iconpath'=>'build/images/pictos/r_maso.gif','titlehovertext'=>'Masochismus x40'],
        ['title'=>'Nenn mich Herrin', 'unlockquantity'=>60, 'associatedtag'=>':maso:', 'associatedpicto'=>'Masochismus', 'iconpath'=>'build/images/pictos/r_maso.gif','titlehovertext'=>'Masochismus x60'],
        ['title'=>'Ich möchte dein Objekt sein.', 'unlockquantity'=>100, 'associatedtag'=>':maso:', 'associatedpicto'=>'Masochismus', 'iconpath'=>'build/images/pictos/r_maso.gif','titlehovertext'=>'Masochismus x100'],
        ['title'=>'Stadtmetzger', 'unlockquantity'=>30, 'associatedtag'=>':butcher:', 'associatedpicto'=>'Metzger', 'iconpath'=>'build/images/pictos/r_animal.gif','titlehovertext'=>'Metzger x30'],
        ['title'=>'Spezialität: Hundesteaks', 'unlockquantity'=>60, 'associatedtag'=>':butcher:', 'associatedpicto'=>'Metzger', 'iconpath'=>'build/images/pictos/r_animal.gif','titlehovertext'=>'Metzger x60'],
        ['title'=>'Miezi? Jaaaa, komm her...', 'unlockquantity'=>150, 'associatedtag'=>':butcher:', 'associatedpicto'=>'Metzger', 'iconpath'=>'build/images/pictos/r_animal.gif','titlehovertext'=>'Metzger x150'],
        ['title'=>'Fleisch ist mein Gemüse', 'unlockquantity'=>300, 'associatedtag'=>':butcher:', 'associatedpicto'=>'Metzger', 'iconpath'=>'build/images/pictos/r_animal.gif','titlehovertext'=>'Metzger x300'],
        ['title'=>'Nervensäge', 'unlockquantity'=>10, 'associatedtag'=>':ban:', 'associatedpicto'=>'Verbannungen', 'iconpath'=>'build/images/pictos/r_ban.gif','titlehovertext'=>'Verbannungen x10'],
        ['title'=>'Sozialschmarotzer', 'unlockquantity'=>20, 'associatedtag'=>':ban:', 'associatedpicto'=>'Verbannungen', 'iconpath'=>'build/images/pictos/r_ban.gif','titlehovertext'=>'Verbannungen x20'],
        ['title'=>'Die Wüste ist mein Zuhause', 'unlockquantity'=>30, 'associatedtag'=>':ban:', 'associatedpicto'=>'Verbannungen', 'iconpath'=>'build/images/pictos/r_ban.gif','titlehovertext'=>'Verbannungen x30'],
        ['title'=>'Immer ein Auge offen', 'unlockquantity'=>20, 'associatedtag'=>':watch:', 'associatedpicto'=>'Stadtwächter', 'iconpath'=>'build/images/pictos/r_guard.gif','titlehovertext'=>'Stadtwächter x20'],
        ['title'=>'Mit offenen Augen schlafen? Kein Problem!', 'unlockquantity'=>40, 'associatedtag'=>':watch:', 'associatedpicto'=>'Stadtwächter', 'iconpath'=>'build/images/pictos/r_guard.gif','titlehovertext'=>'Stadtwächter x40'],
        ['title'=>'Immer alles im Blick', 'unlockquantity'=>80, 'associatedtag'=>':watch:', 'associatedpicto'=>'Stadtwächter', 'iconpath'=>'build/images/pictos/r_guard.gif','titlehovertext'=>'Stadtwächter x80'],
        ['title'=>'Großer Baumeister', 'unlockquantity'=>20, 'associatedtag'=>':extreme:', 'associatedpicto'=>'Absurde Projekte', 'iconpath'=>'build/images/pictos/r_wondrs.gif','titlehovertext'=>'Absurde Projekte x20'],
        ['title'=>'Großer Architekt', 'unlockquantity'=>50, 'associatedtag'=>':extreme:', 'associatedpicto'=>'Absurde Projekte', 'iconpath'=>'build/images/pictos/r_wondrs.gif','titlehovertext'=>'Absurde Projekte x50'],
        ['title'=>'Freimauer', 'unlockquantity'=>100, 'associatedtag'=>':extreme:', 'associatedpicto'=>'Absurde Projekte', 'iconpath'=>'build/images/pictos/r_wondrs.gif','titlehovertext'=>'Absurde Projekte x100'],
        ['title'=>'Terraformingspezialist', 'unlockquantity'=>150, 'associatedtag'=>':extreme:', 'associatedpicto'=>'Absurde Projekte', 'iconpath'=>'build/images/pictos/r_wondrs.gif','titlehovertext'=>'Absurde Projekte x150'],
        ['title'=>'Allmächtiger Schöpfer', 'unlockquantity'=>200, 'associatedtag'=>':extreme:', 'associatedpicto'=>'Absurde Projekte', 'iconpath'=>'build/images/pictos/r_wondrs.gif','titlehovertext'=>'Absurde Projekte x200'],
        ['title'=>'Bauherr ohne Sinn und Verstand', 'unlockquantity'=>1, 'associatedtag'=>':wonder:', 'associatedpicto'=>'Wunderwerke', 'iconpath'=>'build/images/pictos/r_ebuild.gif','titlehovertext'=>'Wunderwerke x1'],
        ['title'=>'Erbauer der verlorenen Zeit', 'unlockquantity'=>3, 'associatedtag'=>':wonder:', 'associatedpicto'=>'Wunderwerke', 'iconpath'=>'build/images/pictos/r_ebuild.gif','titlehovertext'=>'Wunderwerke x3'],
        ['title'=>'Erleuchteter Baumeister', 'unlockquantity'=>7, 'associatedtag'=>':wonder:', 'associatedpicto'=>'Wunderwerke', 'iconpath'=>'build/images/pictos/r_ebuild.gif','titlehovertext'=>'Wunderwerke x7'],
        ['title'=>'Maurer aus Leidenschaft', 'unlockquantity'=>10, 'associatedtag'=>':wonder:', 'associatedpicto'=>'Wunderwerke', 'iconpath'=>'build/images/pictos/r_ebuild.gif','titlehovertext'=>'Wunderwerke x10'],
        ['title'=>'Huldigt den riesigen KVF!', 'unlockquantity'=>5, 'associatedtag'=>':brd:', 'associatedpicto'=>'Wunderwerk: Riesiger KVF', 'iconpath'=>'build/images/pictos/r_ebpmv.gif','titlehovertext'=>'Wunderwerk: Riesiger KVF x5'],
        ['title'=>'Das hast du nicht wirklich gebaut?', 'unlockquantity'=>5, 'associatedtag'=>':castle:', 'associatedpicto'=>'Wunderwerk: Sandschloss', 'iconpath'=>'build/images/pictos/r_ebcstl.gif','titlehovertext'=>'Wunderwerk: Sandschloss x5'],
        ['title'=>'Der Rabe ist gut, der Rabe ist ein Segen für die Stadt. [editiert vom Raben]', 'unlockquantity'=>5, 'associatedtag'=>':crow:', 'associatedpicto'=>'Wunderwerk: Statue des Raben', 'iconpath'=>'build/images/pictos/r_ebcrow.gif','titlehovertext'=>'Wunderwerk: Statue des Raben x5'],
        ['title'=>'Durazell', 'unlockquantity'=>15, 'associatedtag'=>':batgun:', 'associatedpicto'=>'Batteriewerferfabrikant', 'iconpath'=>'build/images/pictos/r_batgun.gif','titlehovertext'=>'Batteriewerferfabrikant x15'],
        ['title'=>'Hüter der Heiligen Batterie', 'unlockquantity'=>25, 'associatedtag'=>':batgun:', 'associatedpicto'=>'Batteriewerferfabrikant', 'iconpath'=>'build/images/pictos/r_batgun.gif','titlehovertext'=>'Batteriewerferfabrikant x25'],
        ['title'=>'Maurerlehrling', 'unlockquantity'=>100, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x100'],
        ['title'=>'Maurer', 'unlockquantity'=>200, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x200'],
        ['title'=>'Polier', 'unlockquantity'=>400, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x400'],
        ['title'=>'Baustellenleiter', 'unlockquantity'=>700, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x700'],
        ['title'=>'Architekt', 'unlockquantity'=>1300, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x1300'],
        ['title'=>'Meisterarchitekt', 'unlockquantity'=>2000, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x2000'],
        ['title'=>'Oscar Niemeyer', 'unlockquantity'=>3000, 'associatedtag'=>':build:', 'associatedpicto'=>'Baustellen', 'iconpath'=>'build/images/pictos/r_buildr.gif','titlehovertext'=>'Baustellen x3000'],
        ['title'=>'Spachtler', 'unlockquantity'=>100, 'associatedtag'=>':rep:', 'associatedpicto'=>'Gebäudereparaturen', 'iconpath'=>'build/images/pictos/r_brep.gif','titlehovertext'=>'Gebäudereparaturen x100'],
        ['title'=>'Gewissenhafter Maurer', 'unlockquantity'=>250, 'associatedtag'=>':rep:', 'associatedpicto'=>'Gebäudereparaturen', 'iconpath'=>'build/images/pictos/r_brep.gif','titlehovertext'=>'Gebäudereparaturen x250'],
        ['title'=>'Meisterwerke in Gefahr', 'unlockquantity'=>500, 'associatedtag'=>':rep:', 'associatedpicto'=>'Gebäudereparaturen', 'iconpath'=>'build/images/pictos/r_brep.gif','titlehovertext'=>'Gebäudereparaturen x500'],
        ['title'=>'Ein Flicken und alles ist wie neu', 'unlockquantity'=>1000, 'associatedtag'=>':rep:', 'associatedpicto'=>'Gebäudereparaturen', 'iconpath'=>'build/images/pictos/r_brep.gif','titlehovertext'=>'Gebäudereparaturen x1000'],
        ['title'=>'Kettensägenmassaker', 'unlockquantity'=>5, 'associatedtag'=>':chain:', 'associatedpicto'=>'Kettensägen', 'iconpath'=>'build/images/pictos/r_tronco.gif','titlehovertext'=>'Kettensägen x5'],
        ['title'=>'Hobby-Heimwerker', 'unlockquantity'=>15, 'associatedtag'=>':repair:', 'associatedpicto'=>'Reparaturen', 'iconpath'=>'build/images/pictos/r_repair.gif','titlehovertext'=>'Reparaturen x15'],
        ['title'=>'Techniker', 'unlockquantity'=>30, 'associatedtag'=>':repair:', 'associatedpicto'=>'Reparaturen', 'iconpath'=>'build/images/pictos/r_repair.gif','titlehovertext'=>'Reparaturen x30'],
        ['title'=>'Meisterbastler ', 'unlockquantity'=>60, 'associatedtag'=>':repair:', 'associatedpicto'=>'Reparaturen', 'iconpath'=>'build/images/pictos/r_repair.gif','titlehovertext'=>'Reparaturen x60'],
        ['title'=>'Sowas wirft man doch nicht weg!', 'unlockquantity'=>150, 'associatedtag'=>':repair:', 'associatedpicto'=>'Reparaturen', 'iconpath'=>'build/images/pictos/r_repair.gif','titlehovertext'=>'Reparaturen x150'],
        ['title'=>'Mac Gyver der Außenwelt', 'unlockquantity'=>400, 'associatedtag'=>':repair:', 'associatedpicto'=>'Reparaturen', 'iconpath'=>'build/images/pictos/r_repair.gif','titlehovertext'=>'Reparaturen x400'],
        ['title'=>'Super Soaker', 'unlockquantity'=>10, 'associatedtag'=>':watergun:', 'associatedpicto'=>'Wasserkanonen', 'iconpath'=>'build/images/pictos/r_watgun.gif','titlehovertext'=>'Wasserkanonen x10'],
        ['title'=>'Archäologielehrling', 'unlockquantity'=>50, 'associatedtag'=>':buried:', 'associatedpicto'=>'Ausgrabungsarbeiten', 'iconpath'=>'build/images/pictos/r_digger.gif','titlehovertext'=>'Ausgrabungsarbeiten x50'],
        ['title'=>'Archäologe', 'unlockquantity'=>300, 'associatedtag'=>':buried:', 'associatedpicto'=>'Ausgrabungsarbeiten', 'iconpath'=>'build/images/pictos/r_digger.gif','titlehovertext'=>'Ausgrabungsarbeiten x300'],
        ['title'=>'Grabungsleiter', 'unlockquantity'=>750, 'associatedtag'=>':buried:', 'associatedpicto'=>'Ausgrabungsarbeiten', 'iconpath'=>'build/images/pictos/r_digger.gif','titlehovertext'=>'Ausgrabungsarbeiten x750'],
        ['title'=>'Furchtloser Camper', 'unlockquantity'=>10, 'associatedtag'=>':zen:', 'associatedpicto'=>'Camper im Jenseits', 'iconpath'=>'build/images/pictos/r_cmplst.gif','titlehovertext'=>'Camper im Jenseits x10'],
        ['title'=>'Waisenkind der Wüste', 'unlockquantity'=>25, 'associatedtag'=>':zen:', 'associatedpicto'=>'Camper im Jenseits', 'iconpath'=>'build/images/pictos/r_cmplst.gif','titlehovertext'=>'Camper im Jenseits x25'],
        ['title'=>'Unglaubliche Entschlossenheit', 'unlockquantity'=>50, 'associatedtag'=>':zen:', 'associatedpicto'=>'Camper im Jenseits', 'iconpath'=>'build/images/pictos/r_cmplst.gif','titlehovertext'=>'Camper im Jenseits x50'],
        ['title'=>'Der letzte Überlebende', 'unlockquantity'=>100, 'associatedtag'=>':zen:', 'associatedpicto'=>'Camper im Jenseits', 'iconpath'=>'build/images/pictos/r_cmplst.gif','titlehovertext'=>'Camper im Jenseits x100'],
        ['title'=>'Lebensmüder Kundschafter', 'unlockquantity'=>5, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'Expertenexpeditionen', 'iconpath'=>'build/images/pictos/r_explo2.gif','titlehovertext'=>'Expertenexpeditionen x5'],
        ['title'=>'Draufgängerischer Expeditionsreisender', 'unlockquantity'=>15, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'Expertenexpeditionen', 'iconpath'=>'build/images/pictos/r_explo2.gif','titlehovertext'=>'Expertenexpeditionen x15'],
        ['title'=>'Nervenkitzelsucher', 'unlockquantity'=>30, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'Expertenexpeditionen', 'iconpath'=>'build/images/pictos/r_explo2.gif','titlehovertext'=>'Expertenexpeditionen x30'],
        ['title'=>'Christopher Kolumbus', 'unlockquantity'=>70, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'Expertenexpeditionen', 'iconpath'=>'build/images/pictos/r_explo2.gif','titlehovertext'=>'Expertenexpeditionen x70'],
        ['title'=>'Neil Armstrong der Außenwelt', 'unlockquantity'=>100, 'associatedtag'=>':dexplo:', 'associatedpicto'=>'Expertenexpeditionen', 'iconpath'=>'build/images/pictos/r_explo2.gif','titlehovertext'=>'Expertenexpeditionen x100'],
        ['title'=>'Wenn ich das nur vorher gewusst hätte...', 'unlockquantity'=>5, 'associatedtag'=>':ruin:', 'associatedpicto'=>'Gebäude erkunden', 'iconpath'=>'build/images/pictos/r_ruine.gif','titlehovertext'=>'Gebäude erkunden x5'],
        ['title'=>'Verwegener Wanderer', 'unlockquantity'=>10, 'associatedtag'=>':ruin:', 'associatedpicto'=>'Gebäude erkunden', 'iconpath'=>'build/images/pictos/r_ruine.gif','titlehovertext'=>'Gebäude erkunden x10'],
        ['title'=>'Tunnelblicker', 'unlockquantity'=>20, 'associatedtag'=>':ruin:', 'associatedpicto'=>'Gebäude erkunden', 'iconpath'=>'build/images/pictos/r_ruine.gif','titlehovertext'=>'Gebäude erkunden x20'],
        ['title'=>'Im Irrgarten', 'unlockquantity'=>50, 'associatedtag'=>':ruin:', 'associatedpicto'=>'Gebäude erkunden', 'iconpath'=>'build/images/pictos/r_ruine.gif','titlehovertext'=>'Gebäude erkunden x50'],
        ['title'=>'Inklusive Kompass', 'unlockquantity'=>100, 'associatedtag'=>':ruin:', 'associatedpicto'=>'Gebäude erkunden', 'iconpath'=>'build/images/pictos/r_ruine.gif','titlehovertext'=>'Gebäude erkunden x100'],
        ['title'=>'Wo klemmt\'s?', 'unlockquantity'=>1, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x1'],
        ['title'=>'Dietrich', 'unlockquantity'=>5, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x5'],
        ['title'=>'Sesam, öffne dich...', 'unlockquantity'=>10, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x10'],
        ['title'=>'Türöffner', 'unlockquantity'=>15, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x15'],
        ['title'=>'Gentleman-Einbrecher', 'unlockquantity'=>20, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x20'],
        ['title'=>'Schlüsseldienst', 'unlockquantity'=>30, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x30'],
        ['title'=>'Zerberus', 'unlockquantity'=>50, 'associatedtag'=>':lock:', 'associatedpicto'=>'Geöffnete Tür', 'iconpath'=>'build/images/pictos/r_door.gif','titlehovertext'=>'Geöffnete Tür x50'],
        ['title'=>'Brutalo', 'unlockquantity'=>100, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x100'],
        ['title'=>'Verstümmler', 'unlockquantity'=>200, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x200'],
        ['title'=>'Killer', 'unlockquantity'=>300, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x300'],
        ['title'=>'Kadaverentsorger', 'unlockquantity'=>800, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x800'],
        ['title'=>'Vernichter', 'unlockquantity'=>2000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x2000'],
        ['title'=>'Schlächter', 'unlockquantity'=>4000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x4000'],
        ['title'=>'Friedensstifter', 'unlockquantity'=>6000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x6000'],
        ['title'=>'Alptraum der Traumlosen', 'unlockquantity'=>10000, 'associatedtag'=>':zombie:', 'associatedpicto'=>'Getötete Zombies', 'iconpath'=>'build/images/pictos/r_killz.gif','titlehovertext'=>'Getötete Zombies x10000'],
        ['title'=>'Hans im Glück', 'unlockquantity'=>5, 'associatedtag'=>':chest:', 'associatedpicto'=>'Glückspilz', 'iconpath'=>'build/images/pictos/r_chstxl.gif','titlehovertext'=>'Glückspilz x5'],
        ['title'=>'Vierklettriges Kleeblatt', 'unlockquantity'=>10, 'associatedtag'=>':chest:', 'associatedpicto'=>'Glückspilz', 'iconpath'=>'build/images/pictos/r_chstxl.gif','titlehovertext'=>'Glückspilz x10'],
        ['title'=>'Fortuna', 'unlockquantity'=>15, 'associatedtag'=>':chest:', 'associatedpicto'=>'Glückspilz', 'iconpath'=>'build/images/pictos/r_chstxl.gif','titlehovertext'=>'Glückspilz x15'],
        ['title'=>'Ultimate Fighter', 'unlockquantity'=>20, 'associatedtag'=>':fight:', 'associatedpicto'=>'Kämpfe um Leben und Tod', 'iconpath'=>'build/images/pictos/r_wrestl.gif','titlehovertext'=>'Kämpfe um Leben und Tod x20'],
        ['title'=>'Stürmischer Kundschafter', 'unlockquantity'=>15, 'associatedtag'=>':explo:', 'associatedpicto'=>'Komplexe Expeditionen', 'iconpath'=>'build/images/pictos/r_explor.gif','titlehovertext'=>'Komplexe Expeditionen x15'],
        ['title'=>'Furchtloser Abenteurer', 'unlockquantity'=>30, 'associatedtag'=>':explo:', 'associatedpicto'=>'Komplexe Expeditionen', 'iconpath'=>'build/images/pictos/r_explor.gif','titlehovertext'=>'Komplexe Expeditionen x30'],
        ['title'=>'Wagemutiger Trapper', 'unlockquantity'=>70, 'associatedtag'=>':explo:', 'associatedpicto'=>'Komplexe Expeditionen', 'iconpath'=>'build/images/pictos/r_explor.gif','titlehovertext'=>'Komplexe Expeditionen x70'],
        ['title'=>'Wikinger', 'unlockquantity'=>150, 'associatedtag'=>':explo:', 'associatedpicto'=>'Komplexe Expeditionen', 'iconpath'=>'build/images/pictos/r_explor.gif','titlehovertext'=>'Komplexe Expeditionen x150'],
        ['title'=>'Außenweltreiseführer', 'unlockquantity'=>200, 'associatedtag'=>':explo:', 'associatedpicto'=>'Komplexe Expeditionen', 'iconpath'=>'build/images/pictos/r_explor.gif','titlehovertext'=>'Komplexe Expeditionen x200'],
        ['title'=>'Außenweltschläfer', 'unlockquantity'=>10, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x10'],
        ['title'=>'Allein in dieser Welt', 'unlockquantity'=>25, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x25'],
        ['title'=>'Der mit den Zombies tanzt', 'unlockquantity'=>50, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x50'],
        ['title'=>'Ich sehe überall Tote!', 'unlockquantity'=>75, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x75'],
        ['title'=>'Ich komme heute Nacht nicht heim...', 'unlockquantity'=>100, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x100'],
        ['title'=>'Ein Mann gegen die Außenwelt', 'unlockquantity'=>150, 'associatedtag'=>':camper:', 'associatedpicto'=>'Mutiger Camper', 'iconpath'=>'build/images/pictos/r_camp.gif','titlehovertext'=>'Mutiger Camper x150'],
        ['title'=>'Neugieriger Leser', 'unlockquantity'=>5, 'associatedtag'=>':rptext:', 'associatedpicto'=>'Textsammler', 'iconpath'=>'build/images/pictos/r_rp.gif','titlehovertext'=>'Textsammler x5'],
        ['title'=>'Eifriger Leser', 'unlockquantity'=>10, 'associatedtag'=>':rptext:', 'associatedpicto'=>'Textsammler', 'iconpath'=>'build/images/pictos/r_rp.gif','titlehovertext'=>'Textsammler x10'],
        ['title'=>'Wüstenhistoriker', 'unlockquantity'=>20, 'associatedtag'=>':rptext:', 'associatedpicto'=>'Textsammler', 'iconpath'=>'build/images/pictos/r_rp.gif','titlehovertext'=>'Textsammler x20'],
        ['title'=>'Bibliothekar', 'unlockquantity'=>30, 'associatedtag'=>':rptext:', 'associatedpicto'=>'Textsammler', 'iconpath'=>'build/images/pictos/r_rp.gif','titlehovertext'=>'Textsammler x30'],
        ['title'=>'Archivar', 'unlockquantity'=>40, 'associatedtag'=>':rptext:', 'associatedpicto'=>'Textsammler', 'iconpath'=>'build/images/pictos/r_rp.gif','titlehovertext'=>'Textsammler x40'],
        ['title'=>'Twinoid ist tabu', 'unlockquantity'=>20, 'associatedtag'=>':clean:', 'associatedpicto'=>'Clean', 'iconpath'=>'build/images/pictos/r_nodrug.gif','titlehovertext'=>'Clean x20'],
        ['title'=>'Keine Macht den Drogen', 'unlockquantity'=>75, 'associatedtag'=>':clean:', 'associatedpicto'=>'Clean', 'iconpath'=>'build/images/pictos/r_nodrug.gif','titlehovertext'=>'Clean x75'],
        ['title'=>'Sowas fasse ICH nicht an', 'unlockquantity'=>150, 'associatedtag'=>':clean:', 'associatedpicto'=>'Clean', 'iconpath'=>'build/images/pictos/r_nodrug.gif','titlehovertext'=>'Clean x150'],
        ['title'=>'Vorbild für die Jugend', 'unlockquantity'=>500, 'associatedtag'=>':clean:', 'associatedpicto'=>'Clean', 'iconpath'=>'build/images/pictos/r_nodrug.gif','titlehovertext'=>'Clean x500'],
        ['title'=>'Champions nehmen keine Drogen!', 'unlockquantity'=>1000, 'associatedtag'=>':clean:', 'associatedpicto'=>'Clean', 'iconpath'=>'build/images/pictos/r_nodrug.gif','titlehovertext'=>'Clean x1000'],
        ['title'=>'Medikamentetester', 'unlockquantity'=>50, 'associatedtag'=>':experimental:', 'associatedpicto'=>'Drogenerfahrungen', 'iconpath'=>'build/images/pictos/r_cobaye.gif','titlehovertext'=>'Drogenerfahrungen x50'],
        ['title'=>'Laborratte', 'unlockquantity'=>100, 'associatedtag'=>':experimental:', 'associatedpicto'=>'Drogenerfahrungen', 'iconpath'=>'build/images/pictos/r_cobaye.gif','titlehovertext'=>'Drogenerfahrungen x100'],
        ['title'=>'Pille palle, 3 Tage wach!', 'unlockquantity'=>150, 'associatedtag'=>':experimental:', 'associatedpicto'=>'Drogenerfahrungen', 'iconpath'=>'build/images/pictos/r_cobaye.gif','titlehovertext'=>'Drogenerfahrungen x150'],
        ['title'=>'Timothy Leary', 'unlockquantity'=>200, 'associatedtag'=>':experimental:', 'associatedpicto'=>'Drogenerfahrungen', 'iconpath'=>'build/images/pictos/r_cobaye.gif','titlehovertext'=>'Drogenerfahrungen x200'],
        ['title'=>'Menschenfleischliebhaber', 'unlockquantity'=>10, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x10'],
        ['title'=>'Hannibalfan', 'unlockquantity'=>40, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x40'],
        ['title'=>'Totmacher', 'unlockquantity'=>80, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x80'],
        ['title'=>'Jeffrey Dahmer', 'unlockquantity'=>120, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x120'],
        ['title'=>'Fido', 'unlockquantity'=>150, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x150'],
        ['title'=>'Fast schon ein Zombie...', 'unlockquantity'=>180, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x180'],
        ['title'=>'Schmeckt am besten wenn\'s noch schreit!', 'unlockquantity'=>250, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x250'],
        ['title'=>'Die Macht der Proteine', 'unlockquantity'=>500, 'associatedtag'=>':cannibal:', 'associatedpicto'=>'Kannibalismus', 'iconpath'=>'build/images/pictos/r_cannib.gif','titlehovertext'=>'Kannibalismus x500'],
        ['title'=>'Zombieliebling', 'unlockquantity'=>10, 'associatedtag'=>':lms:', 'associatedpicto'=>'Letzter Toter', 'iconpath'=>'build/images/pictos/r_surlst.gif','titlehovertext'=>'Letzter Toter x10'],
        ['title'=>'Ich bin in der Hölle aufgewachsen.', 'unlockquantity'=>5, 'associatedtag'=>':hclms:', 'associatedpicto'=>'Letzter Toter in einer Pandämoniumstadt', 'iconpath'=>'build/images/pictos/r_suhard.gif','titlehovertext'=>'Letzter Toter in einer Pandämoniumstadt x5'],
        ['title'=>'Teilweise verrottet', 'unlockquantity'=>20, 'associatedtag'=>':infect:', 'associatedpicto'=>'Tödliche Infektionen', 'iconpath'=>'build/images/pictos/r_dinfec.gif','titlehovertext'=>'Tödliche Infektionen x20'],
        ['title'=>'Virenschleuder', 'unlockquantity'=>40, 'associatedtag'=>':infect:', 'associatedpicto'=>'Tödliche Infektionen', 'iconpath'=>'build/images/pictos/r_dinfec.gif','titlehovertext'=>'Tödliche Infektionen x40'],
        ['title'=>'Gedärmeauskotzer', 'unlockquantity'=>75, 'associatedtag'=>':infect:', 'associatedpicto'=>'Tödliche Infektionen', 'iconpath'=>'build/images/pictos/r_dinfec.gif','titlehovertext'=>'Tödliche Infektionen x75'],
        ['title'=>'Seuchenherd', 'unlockquantity'=>100, 'associatedtag'=>':infect:', 'associatedpicto'=>'Tödliche Infektionen', 'iconpath'=>'build/images/pictos/r_dinfec.gif','titlehovertext'=>'Tödliche Infektionen x100'],
        ['title'=>'Verirrter Ausflügler', 'unlockquantity'=>20, 'associatedtag'=>':night:', 'associatedpicto'=>'Wüstenausflüge', 'iconpath'=>'build/images/pictos/r_doutsd.gif','titlehovertext'=>'Wüstenausflüge x20'],
        ['title'=>'Nächtlicher Spaziergänger', 'unlockquantity'=>100, 'associatedtag'=>':night:', 'associatedpicto'=>'Wüstenausflüge', 'iconpath'=>'build/images/pictos/r_doutsd.gif','titlehovertext'=>'Wüstenausflüge x100'],
        ['title'=>'Vertrauenswürdiger Bürger', 'unlockquantity'=>2, 'associatedtag'=>':ranked:', 'associatedpicto'=>'Gerankte Stadt', 'iconpath'=>'build/images/pictos/r_winbas.gif','titlehovertext'=>'Gerankte Stadt x2'],
        ['title'=>'Sehr erfahrener Bürger', 'unlockquantity'=>5, 'associatedtag'=>':ranked:', 'associatedpicto'=>'Gerankte Stadt', 'iconpath'=>'build/images/pictos/r_winbas.gif','titlehovertext'=>'Gerankte Stadt x5'],
        ['title'=>'Vorbild für das Volk', 'unlockquantity'=>10, 'associatedtag'=>':ranked:', 'associatedpicto'=>'Gerankte Stadt', 'iconpath'=>'build/images/pictos/r_winbas.gif','titlehovertext'=>'Gerankte Stadt x10'],
        ['title'=>'Richtwert der Gemeinschaft', 'unlockquantity'=>15, 'associatedtag'=>':ranked:', 'associatedpicto'=>'Gerankte Stadt', 'iconpath'=>'build/images/pictos/r_winbas.gif','titlehovertext'=>'Gerankte Stadt x15'],
        ['title'=>'Berühmter Veteran', 'unlockquantity'=>20, 'associatedtag'=>':ranked:', 'associatedpicto'=>'Gerankte Stadt', 'iconpath'=>'build/images/pictos/r_winbas.gif','titlehovertext'=>'Gerankte Stadt x20'],
        ['title'=>'Lebender Mythos', 'unlockquantity'=>1, 'associatedtag'=>':legend:', 'associatedpicto'=>'Legendäre Stadt', 'iconpath'=>'build/images/pictos/r_wintop.gif','titlehovertext'=>'Legendäre Stadt x1'],
        ['title'=>'Ich bin eine Legende', 'unlockquantity'=>2, 'associatedtag'=>':legend:', 'associatedpicto'=>'Legendäre Stadt', 'iconpath'=>'build/images/pictos/r_wintop.gif','titlehovertext'=>'Legendäre Stadt x2'],
        ['title'=>'Hör auf mich, wenn du überleben möchtest', 'unlockquantity'=>3, 'associatedtag'=>':legend:', 'associatedpicto'=>'Legendäre Stadt', 'iconpath'=>'build/images/pictos/r_wintop.gif','titlehovertext'=>'Legendäre Stadt x3'],
        ['title'=>'Netter Kerl', 'unlockquantity'=>1, 'associatedtag'=>':goodg:', 'associatedpicto'=>'Netter Kerl', 'iconpath'=>'build/images/pictos/r_goodg.gif','titlehovertext'=>'Netter Kerl x1'],
        ['title'=>'Zeuge der großen Verseuchung', 'unlockquantity'=>1, 'associatedtag'=>':ginfect:', 'associatedpicto'=>'Zeuge der großen Verseuchung', 'iconpath'=>'build/images/pictos/r_ginfect.gif','titlehovertext'=>'Zeuge der großen Verseuchung x1']
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

            $entity->setAssociatedPicto($entry['associatedpicto']);
            $entity->setAssociatedTag($entry['associatedtag']);
            $entity->setIconPath($entry['iconpath']);
            $entity->setTitle($entry['title']);
            $entity->setTitleHoverText($entry['titlehovertext']);
            $entity->setUnlockQuantity($entry['unlockquantity']);

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
}