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
        ['title'=>'Diplomierter Scharlatan', 'unlockquantity'=>10, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_chaman.gif','titlehovertext'=>'Schamane x10'],
        ['title'=>'Schlimmer Finger', 'unlockquantity'=>25, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_chaman.gif','titlehovertext'=>'Schamane x25'],
        ['title'=>'Seelenverwerter', 'unlockquantity'=>75, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_chaman.gif','titlehovertext'=>'Schamane x75'],
        ['title'=>'Mystischer Seher', 'unlockquantity'=>150, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_chaman.gif','titlehovertext'=>'Schamane x150'],
        ['title'=>'Voodoo Sorceror', 'unlockquantity'=>300, 'associatedtag'=>':prosham:', 'associatedpicto'=>'Schamane', 'iconpath'=>'build/images/pictos/r_chaman.gif','titlehovertext'=>'Schamane x300'],
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
        ['title'=>'Wüstenstadt-Dealer', 'unlockquantity'=>250, 'associatedtag'=>':lab:', 'associatedpicto'=>'Laborant', 'iconpath'=>'build/images/pictos/r_drgmkr.gif','titlehovertext'=>'Laborant x250']
    ];

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln('<comment>Awards: ' . count(static::$award_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$award_data) );

        foreach(static::$award_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)
                ->getIndividualAward($entry['associatedpicto'], $entry['unlockquantity']);

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
        $output->writeln( '<info>Installing fixtures: Emotes Database</info>' );
        $output->writeln("");

        $this->insertAwards($manager, $output);
        $output->writeln("");
    }
}