<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use App\Entity\BuildingPrototype;
use App\Entity\ItemGroup;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MyHordes\Plugins\Fixtures\Building;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\Recipe as RecipeFixturesData;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    private EntityManagerInterface $entityManager;

    private Building $building_data;
    private RecipeFixturesData $recipe_data;

    public function __construct(EntityManagerInterface $em, Building $building_data, RecipeFixturesData $recipe_data)
    {
        $this->entityManager = $em;
        $this->building_data = $building_data;
        $this->recipe_data = $recipe_data;
    }

    /**
     * @param ObjectManager $manager
     * @param array $data
     * @param array $cache
     * @return BuildingPrototype
     * @throws Exception
     */
    public function create_building(ObjectManager &$manager, array $data, array &$cache): BuildingPrototype {
        // Set up the icon cache
        if (!isset($cache[$data['img']])) $cache[$data['img']] = 0;
        else $cache[$data['img']]++;

        // Generate unique ID
        $entry_unique_id = $data['img'] . '_#' . str_pad($cache[$data['img']],2,'0',STR_PAD_LEFT);

        $object = $manager->getRepository(BuildingPrototype::class)->findOneByName( $entry_unique_id, false );
        if ($object) {
            if (!empty($object->getResources())) $manager->remove($object->getResources());
        } else $object = (new BuildingPrototype())->setName( $entry_unique_id );

        $object
            ->setLabel( $data['name'] )
            ->setTemp( $data['temporary'] > 0 )
            ->setAp( $data['ap'] )
            ->setBlueprint( $data['bp'] )
            ->setDefense( $data['vp'] )
            ->setIcon( $data['img'] )
            ->setHp($data['hp'])
            ->setImpervious( $data['impervious'] ?? false );

        if(isset($data['desc'])){
        	$object->setDescription($data['desc']);
        }

        if (isset($data['maxLevel'])) {
            $object->setMaxLevel( $data['maxLevel'] );
            $object->setZeroLevelText( $data['lv0text'] ?? null );
            if ($data['upgradeTexts']) $object->setUpgradeTexts( $data['upgradeTexts'] );
        }

        if(isset($data['orderby'])){
            $object->setOrderBy( $data['orderby'] );
        }

        if (!empty($data['rsc'])) {

            $group = (new ItemGroup())->setName( "{$entry_unique_id}_rsc" );
            foreach ($data['rsc'] as $item_name => $count) {

                $item = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item_name] );
                if (!$item) throw new Exception( "Item class not found: " . $item_name );

                $group->addEntry( (new ItemGroupEntry())->setPrototype( $item )->setChance( $count ) );
            }

            $object->setResources( $group );
        }

        $object->getChildren()->clear();
        $object->setParent(null);

        if (!empty($data['children']))
            foreach ($data['children'] as $child)
                $object->addChild( $this->create_building( $manager, $child, $cache ) );

        $manager->persist( $object );
        return $object;

    }

    public function insert_buildings(ObjectManager $manager, ConsoleOutputInterface $out) {
        $building_data = $this->building_data->data();
        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($building_data) );

        $cache = [];
        foreach ($building_data as $building)
            try {
                $this->create_building($manager, $building, $cache);
                $progress->advance();
            } catch (Exception $e) {
                $out->writeln("<error>{$e->getMessage()}</error>");
                return;
            }

        $manager->flush();
        $progress->finish();
    }

    /**
     * @param ObjectManager $manager
     * @param ConsoleOutputInterface $out
     * @throws Exception
     */
    public function insert_recipes(ObjectManager $manager, ConsoleOutputInterface $out) {
        $recipe_fixture_data = $this->recipe_data->data();

        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($recipe_fixture_data) );

        $recipes = $this->entityManager->getRepository(Recipe::class)->findAll();
        foreach ($recipes as $recipe){
            if (!in_array($recipe->getName(), array_keys($recipe_fixture_data))) {
                $this->entityManager->remove($recipe);
            }
        }
        $this->entityManager->flush();

        $cache = [];
        foreach ($recipe_fixture_data as $name => $recipe_data) {
            $recipe = $manager->getRepository(Recipe::class)->findOneBy( ['name' => $name] );
            if ($recipe === null) $recipe = (new Recipe())->setName( $name );

            if ($recipe->getSource()) { $manager->remove( $recipe->getSource() ); $recipe->setSource( null ); }
            if ($recipe->getResult()) { $manager->remove( $recipe->getResult() ); $recipe->setResult( null ); }
            $recipe->getProvoking()->clear();
            $recipe->getKeep()->clear();

            $unpack = function( $data ): array {
                if (!is_array($data)) return [ $data => 1 ];
                $cache = [];
                foreach ( $data as $entry ) {
                    if (is_array($entry))
                        list($id,$count) = $entry;
                    else {
                        $id = $entry;
                        $count = 1;
                    }

                    if (!isset( $cache[$id] )) $cache[$id] = 0;
                    $cache[$id] += $count;
                }
                return $cache;
            };

            $in =  $unpack( $recipe_data['in']  );
            $out_rc = $unpack( $recipe_data['out'] );

            $provoking = null;
            if (isset($recipe_data['provoking'])) $provoking = is_array( $recipe_data['provoking'] ) ? $recipe_data['provoking'] : [$recipe_data['provoking']];
            elseif ( count($in) === 1 ) $provoking = [ array_keys($in)[0] ];

            if ($provoking === null || empty($out_rc) || empty($in))
                throw new Exception("Entry '$name' is incomplete!");

            $in_group = (new ItemGroup())->setName("rc_{$name}_in");
            foreach ( $in as $id => $count ) {
                $proto = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $id] );
                if (!$proto) throw new Exception("Item prototype not found: '$id'");
                $in_group->addEntry( (new ItemGroupEntry())->setChance( $count )->setPrototype( $proto ) );
            }
            $recipe->setSource($in_group);

            $out_group = (new ItemGroup())->setName("rc_{$name}_out");
            foreach ( $out_rc as $id => $count ) {
                $proto = $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $id] );
                if (!$proto) throw new Exception("Item prototype not found: '$id'");
                $out_group->addEntry( (new ItemGroupEntry())->setChance( $count )->setPrototype( $proto ) );
            }
            $recipe->setResult($out_group);

            foreach ($provoking as $item)
                $recipe->addProvoking( $manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $item] ) );

            $recipe->setType( $recipe_data['type'] )->setStealthy( $recipe_data['stealthy'] ?? false );
            if (array_key_exists('action', $recipe_data)) {
              $recipe->setAction($recipe_data['action']);
            }

            if(isset($recipe_data['picto'])){
                $recipe->setPictoPrototype($manager->getRepository(PictoPrototype::class)->findOneBy(['name' => $recipe_data['picto']]));
            }

            if(isset($recipe_data['keep'])){
                foreach ($recipe_data['keep'] as $item)
                    $recipe->addKeep( $manager->getRepository(ItemPrototype::class)->findOneBy(['name' => $item]));
            }
            $manager->persist($recipe);

            $progress->advance();
        }

        foreach ($this->entityManager->getRepository(Recipe::class)->findAll() as $rc) if (!isset($recipe_fixture_data[$rc->getName()])) {
            $out->writeln("Removing outdated recipe: <info>{$rc->getName()}</info>" );
            $this->entityManager->remove($rc);
        }

        $manager->flush();
        $progress->finish();
    }

    public function load(ObjectManager $manager) {

        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Buildings & recipes</info>' );
        $output->writeln("");

        try {
            $this->insert_buildings( $manager, $output );
            $this->insert_recipes( $manager, $output );
        } catch (Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }

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
