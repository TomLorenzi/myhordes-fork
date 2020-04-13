<?php

namespace App\DataFixtures;

use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class PictoFictures extends Fixture
{
    public static $pictos = [
        [
            'label' => 'Heldentaten',
            'description' => 'Anzahl deiner wirklich außergewöhnlichen Heldentaten.',
            'image' => 'r_heroac'
        ]
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_pictos(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>Pictos : ' . count(static::$pictos) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$pictos) );

        $entry_unique_id = $data['img'] . '_#' . str_pad($cache[$data['img']],2,'0',STR_PAD_LEFT);

        // Iterate over all entries
        foreach (static::$pictos as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName($name);
            if ($entity === null) $entity = new PictoPrototype();

            // Set property
            $entity
                ->setName($entry_unique_id)
                ->setLabel($entry['label'])
                ->setDescription($entry['description'])
                ->setIcon($entry['image'])
            ;

            $manager->persist($entity);
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln('<info>Installing fixtures: Pictos content database</info>');
        $output->writeln("");

        $this->insert_pictos($manager, $output);
        $output->writeln("");
    }
}
