<?php

namespace App\DataFixtures;

use App\Entity\LogEntryTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Plugins\Fixtures\Log;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class LogEntryTemplateFixtures extends Fixture
{
    private EntityManagerInterface $entityManager;
    private Log $log_data;

    public function __construct(EntityManagerInterface $em, Log $log_data)
    {
        $this->entityManager = $em;
        $this->log_data = $log_data;
    }

    protected function insert_town_classes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $log_entry_template_data = $this->log_data->data();
        $out->writeln( '<comment>Log Entry Templates: ' . count($log_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($log_entry_template_data) );

        $names = [];

        // Iterate over all entries
        foreach ($log_entry_template_data as $entry) {

            $names[] = $entry['name'];

            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(LogEntryTemplate::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new LogEntryTemplate();

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setClass($entry['class'])
                ->setNonVolatile( $entry['persistent'] ?? ($entry['class'] === LogEntryTemplate::ClassCritical) )
                ->setSecondaryType( $entry['secondaryType'] )
                ->setVariableTypes($entry['variableTypes'])
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

        foreach ($this->entityManager->getRepository(LogEntryTemplate::class)->findAll() as $candidate)
            if (!in_array($candidate->getName(),$names) )
                $this->entityManager->remove($candidate);

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Log Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_town_classes( $manager, $output );
        $output->writeln("");
    }
}
