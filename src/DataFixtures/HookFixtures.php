<?php

namespace App\DataFixtures;

use App\Entity\Hook;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Plugins\Fixtures\HookData;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class HookFixtures extends Fixture
{
    private HookData $hook_data;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em, HookData $hook_data)
    {
        $this->entityManager = $em;
        $this->hook_data = $hook_data;
    }

    protected function insert_hooks(ObjectManager $manager, ConsoleOutputInterface $out) {
        $hooks = $this->hook_data->data();
        $out->writeln( '<comment>Hooks : ' . count($hooks) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($hooks) );

        // Iterate over all entries
        foreach ($hooks as $name => $entry) {
            $entity = $this->entityManager->getRepository(Hook::class)->findOneBy(['name' => $name]);
            if ($entity === null) $entity = (new Hook())->setName($name);

            // Set property
            $entity
				->setHookname($entry['hookname'])
				->setClassname($entry['classname'])
                ->setActive($entry['active'])
				->setPosition($entry['position'])
                ->setFuncName($entry['function'] ?? null)
            ;

            $manager->persist($entity);
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln('<info>Installing fixtures: Hooks content database</info>');
        $output->writeln("");

        $this->insert_hooks($manager, $output);
        $output->writeln("");
    }
}
