<?php

namespace App\DataFixtures;

use App\Entity\ExternalApp;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ExternalFixtures extends Fixture
{
    public static $apps = [
        [
            'name' => 'BigBroth\'Hordes',
            'active' => 1,
            'url' => 'http://bbh.fred26.fr',
            'icon' => 'bbh',
            'contact' => 'bbh@fred26.fr',
            'owner' => 'fred26',
            'key' => '',
        ],
        [
            'name' => 'Fata Morgana',
            'active' => 1,
            'url' => 'http://fm.broon.eu',
            'icon' => 'fm',
            'contact' => 'countcount.cc@gmail.com',
            'owner' => 'CountCount',
            'key' => 'b395dad5c26be2c9',
        ],
        [
            'name' => 'From Dusk Till Dawn',
            'active' => 1,
            'url' => 'http://d2n.duskdawn.net/',
            'icon' => 'fdtd',
            'contact' => 'berzerg.d2n@gmail.com',
            'owner' => 'BerZerg',
            'key' => '',
        ],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_apps(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>External Apps: ' . count(static::$apps) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$apps) );

        $cache = [];

        // Iterate over all entries
        foreach (static::$apps as $entry) {

            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ExternalApp::class)->findOneByName($entry['name']);
            if ($entity === null) $entity = new ExternalApp();

            // Set property
            $entity
                ->setName($entry['name'])
                ->setActive($entry['active'])
                ->setUrl($entry['url'])
                ->setIcon($entry['icon'])
                ->setContact($entry['contact'])
                //->setOwner($entity = $this->entityManager->getRepository(ExternalApp::class)->findOneByName($entry['owner']) ?? null)
                ->setSecret($entry['key'] ?? substr(sha1(mt_rand() . $entry['url'] . time()), 0, 16))
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

        $this->insert_apps($manager, $output);
        $output->writeln("");
    }
}
