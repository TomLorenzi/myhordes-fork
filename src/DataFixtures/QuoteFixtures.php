<?php

namespace App\DataFixtures;

use App\Entity\Quote;
use MyHordes\Plugins\Fixtures\Quote as QuoteFixtureData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class QuoteFixtures extends Fixture {

    private QuoteFixtureData $quote_data;

    private EntityManagerInterface $entityManager;

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $quotes_data = $this->quote_data->data();
        $out->writeln('<comment>Quotes: ' . count($quotes_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($quotes_data) );

       foreach($quotes_data as $entry) {
            $entity = $this->entityManager->getRepository(Quote::class)->findOneBy(['name' => $entry['name']]);

            if($entity === null) {
                $entity = new Quote();
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

    public function __construct(EntityManagerInterface $em, QuoteFixtureData $quote_data) {
        $this->entityManager = $em;
        $this->quote_data = $quote_data;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Quotes Database</info>' );
        $output->writeln("");

        $this->insertAwards($manager, $output);
        $output->writeln("");
    }
}
