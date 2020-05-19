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
        ['title'=>'', 'unlockquantity'=>0, 'associatedtag'=>'', 'associatedpicto'=>'', 'iconpath'=>'','titlehovertext'=>'']
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