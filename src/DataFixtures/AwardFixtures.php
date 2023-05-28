<?php


namespace App\DataFixtures;


use App\Entity\AwardPrototype;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\PictoPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Fixtures\DTO\Awards\AwardIconPrototypeDataContainer;
use MyHordes\Fixtures\DTO\Awards\AwardTitlePrototypeDataContainer;
use MyHordes\Plugins\Fixtures\AwardFeature;
use MyHordes\Plugins\Fixtures\AwardIcon;
use MyHordes\Plugins\Fixtures\AwardTitle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class AwardFixtures extends Fixture implements DependentFixtureInterface {

    private EntityManagerInterface $entityManager;

    private AwardTitle $data_aw_title;
    private AwardIcon $data_aw_icon;
    private AwardFeature $data_aw_feature;

    private function insertAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $award_data = (new AwardTitlePrototypeDataContainer( $this->data_aw_title->data() ))->all();
        $out->writeln('<comment>Awards: ' . count($award_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($award_data) );

        $titles = [];

        foreach($award_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)->getAwardByTitle($entry->title) ?? new AwardPrototype();

            $entry->toEntity( $this->entityManager, $entity );
            $titles[] = $entry->title;

            $manager->persist($entity);
            $progress->advance();
        }

        // Remove obsolete entries
        $entities_to_delete = $this->entityManager->getRepository(AwardPrototype::class)->createQueryBuilder('a')
            ->andWhere('a.title NOT IN (:titles)')->andWhere('a.title IS NOT NULL')->setParameter('titles', $titles)->getQuery()->execute();
        foreach ($entities_to_delete as $entity)
            $this->entityManager->remove($entity);


        $manager->flush();
        $progress->finish();
    }

    private function insertIconAwards(ObjectManager $manager, ConsoleOutputInterface $out) {
        $icon_data = (new AwardIconPrototypeDataContainer( $this->data_aw_icon->data() ))->all();
        $out->writeln('<comment>Icon Awards: ' . count($icon_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($icon_data) );

        $icons = [];

        foreach($icon_data as $entry) {
            $entity = $this->entityManager->getRepository(AwardPrototype::class)->getAwardByIcon($entry->icon) ?? new AwardPrototype();

            $entry->toEntity( $this->entityManager, $entity );
            $icons[] = $entry->icon;

            $manager->persist($entity);
            $progress->advance();
        }

        // Remove obsolete entries
        $entities_to_delete = $this->entityManager->getRepository(AwardPrototype::class)->createQueryBuilder('a')
            ->andWhere('a.icon NOT IN (:icons)')->andWhere('a.icon IS NOT NULL')->setParameter('icons', $icons)->getQuery()->execute();
        foreach ($entities_to_delete as $entity)
            $this->entityManager->remove($entity);

        $manager->flush();
        $progress->finish();
    }

    private function insertFeatureUnlocks(ObjectManager $manager, ConsoleOutputInterface $out) {
        $feature_data = $this->data_aw_feature->data();
        $out->writeln('<comment>Unlockable Features: ' . count($feature_data) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($feature_data) );

        $names = [];

        foreach($feature_data as $name => $entry) {
            $names[] = $name;
            $entity = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => $name]) ?? new FeatureUnlockPrototype();

            $entity
                ->setName( $name )
                ->setLabel( $entry['label'] )
                ->setIcon( $entry['icon'] )
                ->setDescription( $entry['desc'] )
                ->setChargedByUse( $entry['byUse'] ?? false );

            $manager->persist($entity);
            $progress->advance();
        }

        // Remove obsolete entries
        $entities_to_delete = $this->entityManager->getRepository(FeatureUnlockPrototype::class)->createQueryBuilder('f')
            ->andWhere('f.name NOT IN (:names)')->setParameter('names', $names)->getQuery()->execute();
        foreach ($entities_to_delete as $entity)
            $this->entityManager->remove($entity);

        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em, AwardTitle $d_title, AwardIcon $d_icon, AwardFeature $d_feature) {
        $this->entityManager = $em;
        $this->data_aw_title = $d_title;
        $this->data_aw_icon = $d_icon;
        $this->data_aw_feature = $d_feature;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: AwardPrototype Database</info>' );
        $output->writeln("");

        $this->insertAwards($manager, $output);
        $this->insertIconAwards($manager, $output);
        $this->insertFeatureUnlocks($manager, $output);
        $output->writeln("");
    }

    /**
     * @inheritDoc
     */
    public function getDependencies()
    {
        return [ PictoFixtures::class ];
    }
}
