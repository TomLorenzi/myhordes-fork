<?php

namespace App\DataFixtures;

use App\Entity\TownClass;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class TownFixtures extends Fixture
{
    public static $town_class_data = [
        ['name'=>'small'  ,'label'=>'Kleine Stadt'      ,'preset' => true, 'ranked' => false, 'orderBy'  =>  2, 'help' => 'Der Schwierigkeitsgrad ist in dieser Stadt geringer. Deswegen gilt sie nicht für das Saison-Ranking. Die Außenwelt ist durchschnittlich 13x13 Felder groß.'],
        ['name'=>'remote' ,'label'=>'Entfernte Regionen','preset' => true, 'ranked' => true, 'orderBy'   =>  1, 'help' => 'Bei den entfernten Regionen handelt es sich um Städte, die nur von sehr erfahrenen Spielern gespielt werden dürfen (<strong>{splimit} Seelenpunkte</strong>). <p>Diese Städte sind den Veteranen vorbehalten.</p><p>Die entsprechenden Karten der entfernten Regionen sind <strong>weitaus größer</strong> und es gibt mehr zu erforschen.</p>'],
        ['name'=>'panda'  ,'label'=>'Pandämonium'       ,'preset' => true, 'ranked' => true, 'orderBy'   =>  0, 'help' => 'Pandämoniumstädte sind <strong>weitaus schwieriger</strong> als normale Städte! Dementsprechend sind auch die Belohnungen und Gewinnmöglichkeiten besser.<br /><strong>Du benötigst mindestens {splimit} Seelenpunkte</strong>, um in diesen Städten spielen zu dürfen.'],
        ['name'=>'custom' ,'label'=>'Private Stadt'     ,'preset' => false, 'ranked' => false, 'orderBy' => -1],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_town_classes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Town classes: ' . count(static::$town_class_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$town_class_data) );

        // Iterate over all entries
        foreach (static::$town_class_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(TownClass::class)->findOneByName( $entry['name'] );
            if ($entity === null) $entity = new TownClass();

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setLabel( $entry['label'] )
                ->setHasPreset( $entry['preset'])
                ->setRanked( $entry['ranked'] )
                ->setOrderBy( $entry['orderBy'] )
                ->setHelp( $entry['help'] ?? null )
            ;

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
