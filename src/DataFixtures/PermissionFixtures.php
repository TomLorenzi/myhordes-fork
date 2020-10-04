<?php

namespace App\DataFixtures;

use App\Entity\TownClass;
use App\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class PermissionFixtures extends Fixture
{
    public static $base_group_data = [
        ['name'=>'[users]',    'type'=> UserGroup::GroupTypeDefaultUserGroup],
        ['name'=>'[elevated]', 'type'=> UserGroup::GroupTypeDefaultElevatedGroup],
        ['name'=>'[mods]',     'type'=> UserGroup::GroupTypeDefaultModeratorGroup],
        ['name'=>'[admins]',   'type'=> UserGroup::GroupTypeDefaultAdminGroup],
    ];

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    protected function insert_base_user_groups(ObjectManager $manager, ConsoleOutputInterface $out) {
        $out->writeln( '<comment>User Groups: ' . count(static::$base_group_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count(static::$base_group_data) );

        // Iterate over all entries
        foreach (static::$base_group_data as $entry) {
            // Get existing entry, or create new one
            $entity = null;
            $entities = $this->entityManager->getRepository(UserGroup::class)->findBy(['type' => $entry['type']]);
            if (count($entities) > 1) throw new Exception('Multiple base type entities found! Cannot proceed.');
            elseif (count($entities) === 1) $entity = $entities[0];
            else $entity = new UserGroup();

            // Set property
            $entity
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setRef1( null )
                ->setRef2( null );
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Default User Group Database</info>' );
        $output->writeln("");

        $this->insert_base_user_groups( $manager, $output );
        $output->writeln("");
    }
}
