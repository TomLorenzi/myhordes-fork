<?php


namespace App\DataFixtures;


use App\Entity\HeroicActionPrototype;
use App\Entity\HeroSkillPrototype;
use App\Entity\ItemPrototype;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use MyHordes\Plugins\Fixtures\HeroSkill;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class HeroSkillFixtures extends Fixture implements DependentFixtureInterface {

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

			$entity->getStartItems()->clear();

			if (isset($entry['items'])) {
				foreach ($entry['items'] as $itemPrototype) {
					$proto = $this->entityManager->getRepository(ItemPrototype::class)->findOneBy(['name' => $itemPrototype]);
					if (!$proto) {
						$out->writeln("<error>The item prototype <info>{$itemPrototype}</info> cannot be found.</error>");
						return -1;
					}
					$entity->addStartItem($proto);
				}
			}

			if (isset($entry['action'])) {
				$proto = $this->entityManager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $entry['action']]);
				if (!$proto) {
					$out->writeln("<error>The Heroic Action <info>{$entry['action']}</info> cannot be found.</error>");
					return -1;
				}
				$replacedProto = null;
				if ($proto->getReplacedAction() !== null) {
					$replacedProto = $this->entityManager->getRepository(HeroicActionPrototype::class)->findOneBy(['name' => $proto->getReplacedAction()]);
					if (!$replacedProto) {
						$out->writeln("<error>The Heroic Action <info>{$proto->getReplacedAction()}</info> cannot be found.</error>");
						return -1;
					}
				}

				$entity->setUnlockedAction($proto);
			}

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

	public function getDependencies(): array {
		return [ActionFixtures::class, ItemFixtures::class];
	}
}