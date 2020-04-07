<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\HeroicActionPrototype;
use App\Entity\Town;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\GameFactory;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    private $kernel;

    private $game_factory;
    private $entity_manager;
    private $citizen_handler;
    private $randomizer;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em, RandomGenerator $rg, CitizenHandler $ch)
    {
        $this->kernel = $kernel;

        $this->game_factory = $gf;
        $this->entity_manager = $em;
        $this->randomizer = $rg;
        $this->citizen_handler = $ch;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Performs migrations to update content after a version update.')
            ->setHelp('Migrations.')

            ->addOption('assign-heroic-actions-all', null, InputOption::VALUE_NONE, 'Resets the heroic actions for all citizens in all towns.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('assign-heroic-actions-all')) {
            $heroic_actions = $this->entity_manager->getRepository(HeroicActionPrototype::class)->findAll();
            foreach ($this->entity_manager->getRepository(Citizen::class)->findAll() as $citizen) {
                foreach ($heroic_actions as $heroic_action)
                    /** @var $heroic_action HeroicActionPrototype */
                    $citizen->addHeroicAction( $heroic_action );
                $this->entity_manager->persist( $citizen );
            }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        return 1;
    }
}