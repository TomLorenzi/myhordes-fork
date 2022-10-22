<?php


namespace App\DataFixtures;


use App\Entity\HeroSkillPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Plugins\Fixtures\HeroSkill;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class HeroSkillFixtures extends Fixture {

    private HeroSkill $heroSkill;

    private EntityManagerInterface $entityManager;

    private function insertHeroSkills(ObjectManager $manager, ConsoleOutputInterface $out) {
        $hero_skills = $this->heroSkill->data();
        $out->writeln('<comment>Hero Skills: ' . count($hero_skills) . ' fixture entries available.</comment>');

        $progress = new ProgressBar( $out->section() );
        $progress->start( count($hero_skills) );

        foreach($hero_skills as $entry) {
            $entity = $this->entityManager->getRepository(HeroSkillPrototype::class)->findOneBy(['name' => $entry['name']]);

            if($entity === null) {
                $entity = (new HeroSkillPrototype())->setName($entry['name']);
            }

            $entity->setTitle($entry['title'])
                    ->setDescription($entry['description'])
                    ->setDaysNeeded($entry['daysNeeded'])
                    ->setIcon($entry['icon']);

            $manager->persist($entity);
            $progress->advance();
        }
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
}