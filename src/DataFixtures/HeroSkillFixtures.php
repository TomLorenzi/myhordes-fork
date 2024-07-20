<?php


namespace App\DataFixtures;


use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Fixtures\DTO\HeroicExperience\HeroicExperienceDataContainer;
use MyHordes\Plugins\Fixtures\HeroSkill;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class HeroSkillFixtures extends Fixture implements DependentFixtureInterface {

    private HeroSkill $heroSkill;

    private EntityManagerInterface $entityManager;

    private function insertHeroSkills(ObjectManager $manager, ConsoleOutputInterface $out) {
        $hero_skills = (new HeroicExperienceDataContainer($this->heroSkill->data()))->all();
        $out->writeln('<comment>Hero Skills: ' . count($hero_skills) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($hero_skills) );

        $known = [];

        foreach($hero_skills as $entry) {
            $entity = $this->entityManager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $entry->name]) ??
                (new HeroSkillPrototype())->setName($entry->name);

            $known[] = $entry->name;

            $entry->toEntity( $this->entityManager, $entity );

            $manager->persist($entity);
            $progress->advance();
        }

        /** @var HeroSkillPrototype[] $unknown */
        $unknown = $this->entityManager->getRepository(HeroSkillPrototype::class)->createQueryBuilder('s')
            ->where( 's.name NOT IN (:known)' )->setParameter( 'known', $known )
            ->getQuery()->execute();

        foreach ($unknown as $entity)
            $this->entityManager->persist( $entity->setEnabled(false) );

        $manager->flush();
        $progress->finish();
    }

    public function __construct(EntityManagerInterface $em, HeroSkill $heroSkill) {
        $this->entityManager = $em;
        $this->heroSkill = $heroSkill;
    }

    public function load(ObjectManager $manager) {
        $output = new ConsoleOutput();
        $output->writeln( '<info>Installing fixtures: Hero Skills Database</info>' );
        $output->writeln("");

        $this->insertHeroSkills($manager, $output);
        $output->writeln("");
    }

	public function getDependencies(): array {
		return [ActionFixtures::class, ItemFixtures::class];
	}
}