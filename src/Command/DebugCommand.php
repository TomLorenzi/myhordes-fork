<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenProfession;
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

class DebugCommand extends Command
{
    protected static $defaultName = 'app:debug';

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
            ->setDescription('Debug options.')
            ->setHelp('Debug options.')

            ->addOption('add-debug-users', null, InputOption::VALUE_NONE, 'Creates 80 validated users.')
            ->addOption('fill-town', null, InputOption::VALUE_REQUIRED, 'Sends as much debug users as possible to a town.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('add-debug-users')) {
            $command = $this->getApplication()->find('app:create-user');
            for ($i = 1; $i <= 80; $i++) {
                $user_name = 'user_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $nested_input = new ArrayInput([
                    'name' => $user_name,
                    'email' => $user_name . '@localhost',
                    'password' => $user_name,
                    '--validated' => true,
                ]);
                $command->run($nested_input, $output);
            }
            return 0;
        }

        if ($tid = $input->getOption('fill-town')) {
            /** @var Town $town */
            $town = $this->entity_manager->getRepository(Town::class)->find( $tid );
            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }
            $professions = $this->entity_manager->getRepository( CitizenProfession::class )->findAll();
            for ($i = $town->getCitizenCount(); $i < $town->getPopulation(); $i++)
                for ($u = 1; $u <= 80; $u++) {
                    $user_name = 'user_' . str_pad($u, 3, '0', STR_PAD_LEFT);
                    $user = $this->entity_manager->getRepository(User::class)->findOneByName( $user_name );
                    if (!$user) continue;
                    $citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser( $user );
                    if (!$citizen)
                        $citizen = $this->game_factory->createCitizen($town,$user,$error);
                    else continue;
                    if (!$citizen) continue;

                    /** @var CitizenProfession $pro */
                    $pro = $this->randomizer->pick( $professions );
                    $this->citizen_handler->applyProfession( $citizen, $pro );

                    $this->entity_manager->persist($town);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

                    $ii = $i+1;
                    $output->writeln("<comment>{$user_name}</comment> joins <comment>{$town->getName()}</comment> and fills slot {$ii}/{$town->getPopulation()} as a <comment>{$pro->getLabel()}</comment>.");
                    break;
                }
        }

        return 1;
    }
}