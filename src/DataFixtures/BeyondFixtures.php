<?php

namespace App\DataFixtures;

use App\Entity\ItemPrototype;
use App\Entity\NamedItemGroup;
use App\Entity\RuinZonePrototype;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Enum\ArrayMergeDirective;
use MyHordes\Plugins\Fixtures\ZoneTag as ZoneTagFixture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyHordes\Plugins\Fixtures\Ruin;
use MyHordes\Plugins\Fixtures\RuinRoom;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class BeyondFixtures extends Fixture implements DependentFixtureInterface
{
    private Ruin $ruin_data;

    private RuinRoom $ruin_room_data;

    private ZoneTagFixture $zone_tag_data;

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em, Ruin $ruin_data, RuinRoom $ruin_room_data, ZoneTagFixture $zone_tag_data)
    {
        $this->entityManager = $em;
        $this->ruin_data = $ruin_data;
        $this->ruin_room_data = $ruin_room_data;
        $this->zone_tag_data = $zone_tag_data;
    }

    protected function insert_zone_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $zone_class_data = $this->ruin_data->data();
        $out->writeln( '<comment>Zone prototypes: ' . count($zone_class_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($zone_class_data) );

        // Iterate over all entries
        foreach ($zone_class_data as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ZonePrototype::class)->findOneBy( ['icon' => $entry['icon']] );
            if ($entity === null) $entity = new ZonePrototype();

            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setDescription( $entry['desc'] )
                ->setCampingLevel( $entry['camping'] ?? '10' ) // It's 10% per default
                ->setMinDistance( $entry['min_dist'] )
                ->setMaxDistance( $entry['max_dist'] )
                ->setChance( $entry['chance'] )
                ->setIcon( $entry['icon'] )
                ->setDrops( FixtureHelper::createItemGroup( $manager, 'zp_drop_' . substr(md5($entry['label']),0, 24), $entry['drops'] ) )
                ->setExplorable( $entry['explorable'] ?? 0 )
                ->setExplorableSkin( $entry['explorable_skin'] ?? 'bunker' )
                ->setExplorableDescription( $entry['explorable_desc'] ?? $entry['desc'] ?? null )
                ->setEmptyDropChance( $entry['empty'] ?? 0.25 )
                ->setCapacity( $entry['capacity'] ?? -1 )
            ;

            foreach ($entity->getNamedDrops() as $existing_drop)
                if (!in_array( $existing_drop->getName(), array_keys( $entry['namedDrops'] ?? [] ) )) {
                    $entity->getNamedDrops()->removeElement( $existing_drop );
                    $this->entityManager->remove( $existing_drop );
                }

            foreach ( ($entry['namedDrops'] ?? []) as $key => $namedDropConfig ) {
                $namedEntity = null;
                foreach ($entity->getNamedDrops() as $drop)
                    if ($drop->getName() === $key) $namedEntity = $drop;

                if ($namedEntity === null)
                    $entity->addNamedDrop( $namedEntity = (new NamedItemGroup())->setName( $key ) );

                $this->entityManager->persist($namedEntity
                    ->setOperator( $namedDropConfig['operator'] ?? ArrayMergeDirective::Overwrite )
                    ->setItemGroup( FixtureHelper::createItemGroup( $manager, 'zp_drop_' . $key . '_' . substr(md5($entry['label']),0, 24), $namedDropConfig['drops'] ) )
                );
            }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_ruin_zone_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $room_prototypes = $this->ruin_room_data->data();
        $out->writeln( '<comment>RuinZone prototypes: ' . count($room_prototypes) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($room_prototypes) );

        // Iterate over all entries
        foreach ($room_prototypes as $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(RuinZonePrototype::class)->findOneBy( ['label' => $entry['label']] );
            if ($entity === null) $entity = new RuinZonePrototype();

            // Items
            $lock_mold = ($entry['lock_mold'] ?? null) ? $this->entityManager->getRepository(ItemPrototype::class)->findOneBy(['name' => $entry['lock_mold']]) : null;
            $lock_item = ($entry['lock_item'] ?? null) ? $this->entityManager->getRepository(ItemPrototype::class)->findOneBy(['name' => $entry['lock_item']]) : null;

            if ( !is_null($entry['lock_mold'] ?? $entry['lock_item'] ?? null) && ($lock_mold === null || $lock_item === null) )
                throw new Exception('Lock configuration invalid.');

            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setLevel( $entry['level'] ?? 0 )
                ->setKeyImprint($lock_mold ?? null)
                ->setKeyItem($lock_item ?? null)
            ;
            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    protected function insert_zone_tags(ObjectManager $manager, ConsoleOutputInterface $out) {
        $zone_tags = $this->zone_tag_data->data();
        $out->writeln( '<comment>Zone tags: ' . count($zone_tags) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($zone_tags) );

        // Iterate over all entries
        foreach ($zone_tags as $name => $entry) {
            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ZoneTag::class)->findOneBy( ['name' => $name] );
            if ($entity === null) $entity = (new ZoneTag())->setName($name);

            // Set property
            $entity
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setRef( $entry['ref'] )
                ->setTemporary( $entry['temp'] )
            ;
            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: The World Beyond Content Database</info>' );
        $output->writeln("");

        $this->insert_zone_prototypes( $manager, $output );
        $this->insert_ruin_zone_prototypes( $manager, $output );
        $this->insert_zone_tags( $manager, $output );
        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ ItemFixtures::class ];
    }
}
