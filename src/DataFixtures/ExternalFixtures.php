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
            'name' => 'Fata Morgana',
            'active' => 1,
            'app_url' => 'http://fm.broon.eu',
            'app_icon' => '',
            'contact_email' => 'countcount.cc@gmail.com',
            'owner' => 'CountCount',
            'key' => 'b395dad5c26be2c9',
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
                ->setAppUrl($entry['app_url'])
                ->setAppIcon($entry['app_icon'])
                ->setContactEmail($entry['contact_email'])
                ->setOwner($entity = $this->entityManager->getRepository(ExternalApp::class)->findOneByName($entry['owner']))
                ->setSecret($entry['key'] ?? substr(sha1(mt_rand() . $entry['app_url'] . time()), 0, 16))
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
