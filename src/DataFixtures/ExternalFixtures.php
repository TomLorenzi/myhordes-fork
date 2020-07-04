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
            'key' => 'bf6ec30728002df7',
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
            'key' => 'a153246385a7111',
        ],
        [
            'name' => 'Gest\'Hordes',
            'active' => 1,
            'url' => 'https://gest-hordes2.eragaming.fr/',
            'icon' => 'gest',
            'contact' => 'gesthordes@eragaming.fr',
            'owner' => 'Eragony',
            'key' => '84013b00ab338778',
        ],
        [
            'name' => 'HTools',
            'active' => 1,
            'url' => 'https://hordestools.000webhostapp.com/',
            'icon' => 'htools',
            'contact' => 'ordealisium@gmail.com',
            'owner' => 'Koya',
            'key' => 'fe00156994102897',
        ],
        [
            'name' => 'Hordes-la-loi',
            'active' => 1,
            'url' => 'http://myh.hordes-la-loi.fr/receive.php',
            'icon' => 'null',
            'contact' => 'xemaro@hordes-la-loi.fr',
            'owner' => 'Xemaro',
            'key' => 'b4af912cbe75debe',
        ],
        [
            'name' => 'Test',
            'active' => 1,
            'testing' => true,
            'url' => 'localhost:8888/',
            'icon' => 'null',
            'contact' => '',
            'owner' => 'devwwm',
            'key' => '8e7015b69abe9b90',
        ],
        [
            'name' => 'The Argordien',
            'active' => 1,
            'url' => 'https://argordien.dev.ctruillet.eu/',
            'icon' => 'null',
            'contact' => '',
            'owner' => 'Teasch',
            'key' => null,
            'linkOnly' => true,
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
                ->setTesting($entry['testing'] ?? false)
                ->setLinkOnly($entry['linkOnly'] ?? false)
                //->setOwner($entity = $this->entityManager->getRepository(ExternalApp::class)->findOneByName($entry['owner']) ?? null)
                ->setSecret($entry['key'] != '' ? $entry['key'] : substr(sha1(mt_rand() . $entry['url'] . time()), 0, 16))
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
