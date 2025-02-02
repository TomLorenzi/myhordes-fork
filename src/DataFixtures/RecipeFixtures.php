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
use MyHordes\Fixtures\DTO\Buildings\BuildingPrototypeDataContainer;
use MyHordes\Fixtures\DTO\Buildings\BuildingPrototypeDataElement;
use MyHordes\Plugins\Fixtures\Building;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use MyHordes\Plugins\Fixtures\Recipe as RecipeFixturesData;
use function PHPUnit\Framework\containsEqual;

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

    public function insert_buildings(ObjectManager $manager, ConsoleOutputInterface $out) {
        $building_data = (new BuildingPrototypeDataContainer( $this->building_data->data() ))->all();
        // Set up console
        $progress = new ProgressBar( $out->section() );
        $progress->start( count($building_data) );

        /** @var BuildingPrototype[] $available_parents */
        $available_parents = [];

        $cache = [];
        while (!empty($building_data)) {
            foreach ($building_data as $id => $building)
                try {
                    //$this->create_building($manager, $building, $cache);
                    if (array_key_exists($id, $available_parents)) continue;
                    if ($building->parentBuilding !== null && !array_key_exists($building->parentBuilding, $available_parents))
                        continue;

                    $object =
                        $this->entityManager->getRepository(BuildingPrototype::class)->findOneByName($id, false) ??
                        (new BuildingPrototype())->setName($id);

                    $building->toEntity($this->entityManager, $id, $object);
                    $object->getChildren()->clear();
                    if ($building->parentBuilding !== null) {
                        $object->setParent($available_parents[$building->parentBuilding]);
                        $available_parents[$building->parentBuilding]->addChild($object);
                    } else $object->setParent(null);
                    $this->entityManager->persist($available_parents[$id] = $object);


                    $progress->advance();
                } catch (Exception $e) {
                    $out->writeln("<error>{$e->getMessage()}</error>");
                    return;
                }

            $c = count($building_data);
            $building_data = array_filter( $building_data, fn($a) => !array_key_exists( $a, $available_parents ), ARRAY_FILTER_USE_KEY );
            if (count($building_data) >= $c) throw new Exception('Dependency chain for building prototypes is broken: The following buildings can not be inserted: ' . implode( ',', array_keys($building_data) ));
        }

        $manager->flush();

        foreach ($this->entityManager->getRepository(BuildingPrototype::class)->findAll() as $existing)
            if ($existing->getBlueprint() < 6 && !array_key_exists($existing->getName(), $available_parents)) {
                $out->writeln("Retiring the building <info>{$existing->getName()}</info>.");
                $this->entityManager->persist($existing->setBlueprint(6));
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
            $recipe->setTooltipString($recipe_data['tooltip'] ?? null);
            $recipe->setMultiOut($recipe_data['multi_out'] ?? false);
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
