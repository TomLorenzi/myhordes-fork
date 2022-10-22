<?php

namespace App\DataFixtures;

use App\Entity\HordesFact;
use MyHordes\Plugins\Fixtures\HordesFact as HordesFactData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class HordesFactFixtures extends Fixture {

    private EntityManagerInterface $entityManager;

    private HordesFactData $hordesFact;

    private function insertFacts(ObjectManager $manager, ConsoleOutputInterface $out) {
        $facts_data = $this->hordesFact->data();
        $out->writeln('<comment>HordesFact: ' . count($facts_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($facts_data) );

       foreach($facts_data as $entry) {
            $entity = $this->entityManager->getRepository(HordesFact::class)->findOneBy(['name' => $entry['name']]);

            if($entity === null) {
                $entity = new HordesFact();
                $entity->setName($entry['name']);
            }

            $entity
                ->setAuthor($entry['author'])
                ->setContent($entry['content'])
                ->setLang($entry['lang']);

            $manager->persist($entity);
            $progress->advance();
        }
        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em, HordesFactData $hordesFact) {
        $this->entityManager = $em;
        $this->hordesFact = $hordesFact;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: HordesFacts Database</info>' );
        $output->writeln("");

        $this->insertFacts($manager, $output);
        $output->writeln("");
    }
}
