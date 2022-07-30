<?php

namespace App\DataFixtures;

use App\Entity\ItemCategory;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use MyHordes\Plugins\Fixtures\Item;
use MyHordes\Plugins\Fixtures\ItemGroup;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\ItemCategory as ItemCategoryFixtures;
use MyHordes\Plugins\Fixtures\ItemProperty as ItemPropertyFixtures;


class ItemFixtures extends Fixture
{
    private EntityManagerInterface $entityManager;
    private Item $item_data;
    private ItemCategoryFixtures $item_category_data;
    private ItemGroup $item_group_data;
    private ItemPropertyFixtures $item_property_data;

    public function __construct(EntityManagerInterface $em, Item $item_data, ItemCategoryFixtures $item_category_data, ItemGroup $item_group_data, ItemPropertyFixtures $item_property_data)
    {
        $this->entityManager = $em;
        $this->item_data = $item_data;
        $this->item_category_data = $item_category_data;
        $this->item_group_data = $item_group_data;
        $this->item_property_data = $item_property_data;
    }

    protected function insert_item_categories(ObjectManager $manager, ConsoleOutputInterface $out) {
        $item_category_data = $this->item_category_data->data();

        // Mark all entries as "not imported"
        $changed = true;
        $missing_data = $item_category_data;
        $out->writeln( '<comment>Item categories: ' . count($item_category_data) . ' fixture entries available.</comment>' );

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($item_category_data) );

        // As long as the last query performed any changes and we still have not-imported data , continue
        while ($changed && !empty($missing_data)) {
            // Set the current data, and clear state
            $current = $missing_data;
            $missing_data = [];
            $changed = false;

            // For each missing entry
            foreach ($current as $entry) {

                // Check if this entry has a parent, and attempt to fetch the parent from the database
                $parent = null;
                if ($entry['parent'] !== null) {
                    $parent = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['parent']] );
                    // If the entry has a parent, but that parent is missing from the database,
                    // defer the current entry for the next run
                    if ($parent === null) {
                        $missing_data[] = $entry;
                        continue;
                    }
                }

                // Attempt to fetch the current entry from the database; if the entry does not exist, create a new one
                $entity = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['name']] );
                if (!$entity) $entity = new ItemCategory();

                // Set properties
                $entity->setName( $entry['name'] );
                $entity->setLabel( $entry['label'] );
                $entity->setOrdering( $entry['ordering'] );
                $entity->setParent( $entry['parent'] === null ? null :
                    $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['parent']] )
                );

                // Persist entry
                $manager->persist( $entity );
                $progress->advance();
                $changed = true;
            }

            // Flush
            $manager->flush();
            $progress->finish();
        }

        if (!empty($missing_data)) {
            $out->writeln('<error>Unable to insert all fixtures. The following entries are missing:</error>');
            $table2 = new Table( $out->section() );
            $table2->setHeaders( ['Name', 'Label', 'Parent', 'Ordering'] );
            foreach ($missing_data as $entry)
                $table2->addRow( [ $entry['name'], $entry['label'], $entry['parent'], $entry['ordering'] ] );
            $table2->render();
        }
    }

    protected function insert_item_prototypes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $item_prototype_data = $this->item_data->data();
        $item_prototype_properties = $this->item_property_data->data();

        $out->writeln( '<comment>Item prototypes: ' . count($item_prototype_data['items']) . ' fixture entries available.</comment>' );

        // Get misc category
        $misc_category = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => 'Misc'] );
        $cache = [];

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($item_prototype_data['items']) );

        $properties = [];

        // Iterate over all entries
        foreach ($item_prototype_data['items'] as $entry) {
            // Set up the icon cache
            if (!isset($cache[$entry['icon']])) $cache[$entry['icon']] = 0;
            else $cache[$entry['icon']]++;

            // Generate unique ID
            $entry_unique_id = $entry['icon'] . '_#' . str_pad($cache[$entry['icon']],2, '0',STR_PAD_LEFT);

            // Check the category
            $category = $this->entityManager->getRepository(ItemCategory::class)->findOneBy( ['name' => $entry['category']] );
            if ($category === null) {
                $category = $misc_category;
                $out->writeln('<error>Unable to locate category \'' . $entry['category'] . '\' for item \'' .
                    $entry_unique_id . '\'. Assigning MISC category instead.</error>');
            }
            if ($category === null) {
                $out->writeln('<error>Unable to locate category \'' . $entry['category'] . '\' for item \'' .
                    $entry_unique_id . '\'. MISC category could not be located, entry will be skipped!</error>');
                continue;
            }

            // Get existing entry, or create new one
            $entity = $this->entityManager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $entry_unique_id] );
            if ($entity === null) $entity = new ItemPrototype();

            // Set property
            $entity->setName( $entry_unique_id )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setDeco( $entry['deco'] )
                ->setHeavy( $entry['heavy'] )
                ->setCategory( $category )
                ->setSort( $entry['sort'] ?? 0 )
                ->setDescription( $item_prototype_data['descriptions'][ $entry_unique_id ] ?? "" )
                ->setHideInForeignChest( $entry['hideInForeignChest'] ?? false )
                ->setDecoText($entry['deco_text'] ?? null)
                ->setIndividual( $entry['unstackable'] ?? false )
                ->getProperties()->clear();

            $entity
                ->setWatchpoint($entry['watchpoint'] ?? 0)
                ->setFragile( $entry['fragile'] ?? false );

            if (isset($item_prototype_properties[$entry_unique_id]))
                foreach ($item_prototype_properties[$entry_unique_id] as $property) {
                    if (!isset($properties[$property])) {
                        $properties[$property] = $manager->getRepository(ItemProperty::class)->findOneBy( ['name' => $property] );
                        if (!$properties[$property]) {
                            $p = new ItemProperty();
                            $p->setName( $property );
                            $properties[$property] = $p;
                            $manager->persist( $properties[$property] );
                        }
                    }
                    $entity->addProperty( $properties[$property] );
                }

            $manager->persist( $entity );

            // Set table entry
            $progress->advance();
        }

        // Flush
        $manager->flush();
        $progress->finish();
    }

    public function insert_default_item_groups( ObjectManager $manager, ConsoleOutputInterface $out ) {
        $item_groups = $this->item_group_data->data();
        $out->writeln( '<comment>Default item groups: ' . count($item_groups) . ' fixture entries available.</comment>' );

        foreach ($item_groups as $name => $group)
            $manager->persist( FixtureHelper::createItemGroup( $manager, $name, $group ) );

        $manager->flush();

        $out->writeln('<info>Done!</info>');
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Item Database</info>' );
        $output->writeln("");

        $this->insert_item_categories( $manager, $output );
        $output->writeln("");
        $this->insert_item_prototypes( $manager, $output );
        $output->writeln("");
        $this->insert_default_item_groups( $manager, $output );
        $output->writeln("");
    }
}
