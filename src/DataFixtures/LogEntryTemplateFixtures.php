<?php

namespace App\DataFixtures;

use App\Entity\LogEntryTemplate;
use App\Entity\TownClass;
use App\Entity\TownLogEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class LogEntryTemplateFixtures extends Fixture
{
    public static $log_entry_template_data = [
        ['text'=>'%citizen% hat der Stadt folgendes gespendet: %item%', 'name'=>'bankGive', 'type'=>LogEntryTemplate::TypeBank, 'class'=>LogEntryTemplate::ClassNone, 'secondaryType'=>null, 'variableTypes'=>array("citizen","item")],
        ['text'=>'%citizen% hat folgenden Gegenstand aus der Bank genommen: %item%', 'name'=>'bankTake', 'type'=>LogEntryTemplate::TypeBank, 'class'=>LogEntryTemplate::ClassWarning, 'secondaryType'=>null, 'variableTypes'=>array("citizen","item")],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_town_classes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Log Entry Templates: ' . count(static::$log_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$log_entry_template_data) );

        // Iterate over all entries
        foreach (static::$log_entry_template_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(LogEntryTemplate::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new LogEntryTemplate();

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setClass($entry['class'])
                ->setSecondaryType( $entry['secondaryType'] )
                ->setVariableTypes($entry['variableTypes'])
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

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
