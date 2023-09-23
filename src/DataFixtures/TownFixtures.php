<?php

namespace App\DataFixtures;

use App\Entity\TownClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Plugins\Fixtures\Town;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TownFixtures extends Fixture
{
    private EntityManagerInterface $entityManager;

    private Town $town_class_data;

    public function __construct(EntityManagerInterface $em, Town $fx_town )
    {
        $this->entityManager = $em;
        $this->town_class_data = $fx_town;
    }

    protected function insert_town_classes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $town_class_data = $this->town_class_data->data();

        $out->writeln( '<comment>Town classes: ' . count($town_class_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($town_class_data) );

        // Iterate over all entries
        foreach ($town_class_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(TownClass::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new TownClass();

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] )
                ->setHasPreset( $entry['preset'])
                ->setOrderBy( $entry['orderBy'] )
                ->setHelp( $entry['help'] ?? null )
            ;

            if (empty($entry['ranked'])) $entity->setRanked(false)->setRankingTop(0)->setRankingMid(0)->setRankingLow(0);
            else {
                if (!is_array( $entry['ranked'] )) $entry['ranked'] = [];
                $entity->setRanked(true)
                    ->setRankingTop($entry['ranked'][0] ??  1)
                    ->setRankingMid($entry['ranked'][1] ?? 15)
                    ->setRankingLow($entry['ranked'][2] ?? 35);
            }

            $manager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Town Content Database</info>' );
        $output->writeln("");

        $this->insert_town_classes( $manager, $output );
        $output->writeln("");
    }
}
