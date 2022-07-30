<?php

namespace App\DataFixtures;

use App\Entity\CouncilEntryTemplate;
use App\Entity\GazetteEntryTemplate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Plugins\Fixtures\CouncilEntry;
use MyHordes\Plugins\Fixtures\GazetteEntry;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class GazetteEntryTemplateFixtures extends Fixture
{
    private GazetteEntry $gazette_data;
    private CouncilEntry $council_data;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em, GazetteEntry $gazette_data, CouncilEntry $council_data)
    {
        $this->entityManager = $em;
        $this->gazette_data = $gazette_data;
        $this->council_data = $council_data;
    }

    protected function insert_gazette_templates(ObjectManager $manager, ConsoleOutputInterface $out) {
        $gazette_entry_template_data = $this->gazette_data->data();
        $out->writeln( '<comment>Gazette Entry Templates: ' . count($gazette_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($gazette_entry_template_data) );

        // Iterate over all entries
        foreach ($gazette_entry_template_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(GazetteEntryTemplate::class)->findOneBy( ['name' => $entry['name']] );
            if ($entity === null) $entity = new GazetteEntryTemplate();

            // Set property
            $entity
                ->setText( $entry['text'] )
                ->setName( $entry['name'] )
                ->setType( $entry['type'] )
                ->setRequirement( $entry['requirement'] )
                ->setVariableTypes($entry['variableTypes'])
                ->setFollowUpType( $entry['fot'] ?? 0 )
            ;

            $manager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_council_templates(ObjectManager $manager, ConsoleOutputInterface $out) {
        $council_entry_template_data = $this->council_data->data();
        $out->writeln( '<comment>Council Entry Templates: ' . count($council_entry_template_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($council_entry_template_data) * 2 );

        $cache = [];
        $index = [];

        $cache_as = function(CouncilEntryTemplate $t, $index) use (&$cache): void {
            if (!isset($cache[$index])) $cache[$index] = [];
            $cache[$index][$t->getName()] = $t;
        };

        $cache_get = null;
        $cache_get = function($index) use (&$cache, &$cache_get): array {
            if (!is_array($index)) return array_values($cache[$index] ?? []);
            $tmp = [];
            foreach ($index as $this_index) $tmp = array_merge( $tmp, $cache_get( $this_index ) );
            return array_unique( $tmp );
        };

        // Iterate over all entries
        foreach ($council_entry_template_data as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(CouncilEntryTemplate::class)->findOneBy( ['name' => $name] );
            if ($entity === null) $entity = new CouncilEntryTemplate();

            $branch_mode = empty($entry['branches']) ? CouncilEntryTemplate::CouncilBranchModeNone : ( $entry['mode'] ?? CouncilEntryTemplate::CouncilBranchModeNone );
            $branch_count = $branch_mode === CouncilEntryTemplate::CouncilBranchModeRandom
                ? (isset( $entry['branch_count'] ) ? ( is_array($entry['branch_count']) ? $entry['branch_count'] : [$entry['branch_count'],$entry['branch_count']] ) : [1,1])
                : [0,0];

            // Set property
            $entity
                ->setName( $name )
                ->setBranchMode( $branch_mode )
                ->setBranchSizeMin( $branch_count[0] )
                ->setBranchSizeMax( $branch_count[1] )
                ->setSemantic( $entry['semantic'] ?? CouncilEntryTemplate::CouncilNodeContextOnly )
                ->setVariableTypes( isset($entry['variables']) ? ($entry['variables']['types'] ?? []) : [] )
                ->setVariableDefinitions( isset($entry['variables']) ? ($entry['variables']['config'] ?? []) : [] )
                ->setText( $entry['text'] ?? null )
                ->setVocal( isset($entry['text']) ? ($entry['vocal'] ?? true) : false )
                ->getBranches()->clear();
            ;

            $index[$entity->getName()] = [$entity, $entry['branches'] ?? []];
            $cache_as($entity, $entity->getName());
            if ( $entity->getSemantic() !== CouncilEntryTemplate::CouncilNodeContextOnly )
                $cache_as($entity, $entity->getSemantic());

            $progress->advance();
        }

        // Iterate over all entries again
        foreach ($index as list($entity,$branches)) {

            foreach ($cache_get( $branches ) as $branch)
                $entity->addBranch( $branch );

            $this->entityManager->persist( $entity );
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Gazette Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_gazette_templates( $manager, $output );
        $output->writeln("");

        $output->writeln( '<info>Installing fixtures: Council Entry Templates Database</info>' );
        $output->writeln("");

        $this->insert_council_templates( $manager, $output );
        $output->writeln("");
    }
}
