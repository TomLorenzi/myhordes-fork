<?php

namespace App\DataFixtures;

use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\Picto;

class PictoFixtures extends Fixture
{
    private Picto $picto_data;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em, Picto $picto_data)
    {
        $this->entityManager = $em;
        $this->picto_data = $picto_data;
    }

    protected function insert_pictos(ObjectManager $manager, ConsoleOutputInterface $out) {
        $pictos = $this->picto_data->data();
        $out->writeln( '<comment>Pictos : ' . count($pictos) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($pictos) );

        $cache = [];

        // Iterate over all entries
        foreach ($pictos as $name => $entry) {
            // Set up the icon cache
            if (!isset($cache[$entry['icon']])) $cache[$entry['icon']] = 0;
            else $cache[$entry['icon']]++;
            
            $entry_unique_id = $entry['icon'] . '_#' . str_pad($cache[$entry['icon']],2,'0',STR_PAD_LEFT);

            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(PictoPrototype::class)->findOneByName($entry_unique_id);
            if ($entity === null) $entity = new PictoPrototype();

            // Set property
            $entity
                ->setName($entry_unique_id)
                ->setLabel($entry['label'])
                ->setDescription($entry['description'])
                ->setIcon($entry['icon'])
                ->setRare($entry['rare'])
                ->setPriority($entry['priority'] ?? 0)
                ->setCommunity($entry['community'] ?? false)
                ->setSpecial($entry['special'] ?? false)
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
