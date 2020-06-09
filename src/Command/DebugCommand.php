<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenStatus;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\Town;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
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
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class DebugCommand extends Command
{
    protected static $defaultName = 'app:debug';

    private $kernel;

    private $game_factory;
    private $entity_manager;
    private $citizen_handler;
    private $randomizer;
    private $trans;
    private $inventory_handler;
    private $item_factory;
    private $encoder;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em, RandomGenerator $rg, CitizenHandler $ch, Translator $translator, InventoryHandler $ih, ItemFactory $if, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->kernel = $kernel;

        $this->game_factory = $gf;
        $this->entity_manager = $em;
        $this->randomizer = $rg;
        $this->citizen_handler = $ch;
        $this->trans = $translator;
        $this->inventory_handler = $ih;
        $this->item_factory = $if;
        $this->encoder = $passwordEncoder;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Debug options.')
            ->setHelp('Debug options.')
            
            ->addOption('everyone-drink', null, InputOption::VALUE_REQUIRED, 'Unset thirst status of all citizen.')
            ->addOption('add-crow', null, InputOption::VALUE_NONE, 'Creates the crow account. Also creates 80 validated users in case there are less than 66 users.')
            ->addOption('add-debug-users', null, InputOption::VALUE_NONE, 'Creates 80 validated users.')
            ->addOption('fill-town', null, InputOption::VALUE_REQUIRED, 'Sends as much debug users as possible to a town.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Will detach debug users when used with fill-town.')
            ->addOption('fill-bank', null, InputOption::VALUE_REQUIRED, 'Places 500 of each item type in the bank of a given town.')
            ->addOption('confirm-deaths', null, InputOption::VALUE_NONE, 'Confirms death of every account having an email ending on @localhost.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('add-debug-users') | $input->getOption('add-crow')) {

            if ($input->getOption('add-crow')) {
                /** @var User $crow */
                $crow = $this->entity_manager->getRepository(User::class)->find(66);
                if (!isset($crow)) {
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
                        $crow = $this->entity_manager->getRepository(User::class)->find(66);
                    }                                       
                }

                if ($crow->getRightsElevation() > User::ROLE_USER || !strstr($crow->getEmail(), "@localhost") === "@localhost") {
                    $output->writeln('<error>User 66 is not a debug user. Will not proceed.</error>');
                    return -1;
                }
                $crow->setName("Der Rabe");
                $crow->setEmail("crow");
                $crow->setPassword( $this->encoder->encodePassword($crow, '5%[9Wqc@"px.&er{thxCt)7Un^-.~K~B;E7b`,#L0"3?3Mcu:x$|8\-h.3JQ*$') );
                $this->entity_manager->persist($crow);
                $this->entity_manager->flush();               
                
                return 0;
            }

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

        if ($tid = $input->getOption('everyone-drink')) {
            /** @var Town $town */
            $town = $this->entity_manager->getRepository(Town::class)->find( $tid );
            $statusHasDrunk = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("hasdrunk");
            $statusThirst = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("thirst1");
            $statusDehydrated = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("thirst2");

            $citizens = $town->getCitizens();
            foreach ($citizens as $citizen) {
                $citizen->addStatus($statusHasDrunk);
                $citizen->removeStatus($statusThirst);
                $citizen->removeStatus($statusDehydrated);
                $this->entity_manager->persist( $citizen );
            }
            $this->entity_manager->flush();
            $output->writeln("All citizen from <info>{$town->getName()}</info> are full of water now.");
        }

        if ($tid = $input->getOption('fill-town')) {
            /** @var Town $town */
            $town = $this->entity_manager->getRepository(Town::class)->find( $tid );
            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }
            $this->trans->setLocale($town->getLanguage() ?? 'de');

            $force = $input->getOption('force');

            $professions = $this->entity_manager->getRepository( CitizenProfession::class )->findAll();
            for ($i = $town->getCitizenCount(); $i < $town->getPopulation(); $i++)
                for ($u = 1; $u <= 80; $u++) {
                    $user_name = 'user_' . str_pad($u, 3, '0', STR_PAD_LEFT);
                    $user = $this->entity_manager->getRepository(User::class)->findOneByName( $user_name );
                    if (!$user) continue;
                    /** @var Citizen $citizen */

                    $citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser( $user );
                    if ($citizen && $citizen->getTown() !== $town && (!$citizen->getAlive() || $force)) {
                        $citizen->setActive(false);

                        $this->entity_manager->persist($citizen);
                        $this->entity_manager->flush();
                        $citizen = null;
                    }

                    if (!$citizen) {
                        $citizen = $this->entity_manager->getRepository(Citizen::class)->findInTown($user,$town);
                        if ($citizen) $citizen->setActive(true);
                        else $citizen = $this->game_factory->createCitizen($town,$user,$error);
                    } else continue;

                    if (!$citizen) continue;

                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

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

            // Ensure we still have an open town after filling it with dumb users

            $openTowns = $this->entity_manager->getRepository(Town::class)->findOpenTown();
            $count = array(
                "fr" => array(
                    "remote" => 0,
                    "panda" => 0,
                    "small" => 0
                ),
                "de" => array(
                    "remote" => 0,
                    "panda" => 0,
                    "small" => 0
                ),
                "en" => array(
                    "remote" => 0,
                    "panda" => 0,
                    "small" => 0
                ),
                "es" => array(
                    "remote" => 0,
                    "panda" => 0,
                    "small" => 0
                ),
            );
            foreach ($openTowns as $openTown) {
                $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
            }

            foreach ($count as $townLang => $array) {
                foreach ($array as $townClass => $openCount) {
                    if($openCount < 1){
                        $newTown = $this->game_factory->createTown(null, $townLang, null, $townClass);
                        $this->entity_manager->persist($newTown);
                        $this->entity_manager->flush();
                        //$this->game_factory->createExplorableMaze($newTown);
                    }
                }
            }
        }

        if ($tid = $input->getOption('fill-bank')) {
            $town = $this->entity_manager->getRepository(Town::class)->find( $tid );
            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }

            $bank = $town->getBank();
            foreach ($this->entity_manager->getRepository(ItemPrototype::class)->findAll() as $repo)
                $this->inventory_handler->forceMoveItem( $bank, ($this->item_factory->createItem( $repo ))->setCount(500) );

            $this->entity_manager->persist( $bank );
            $this->entity_manager->flush();
            $output->writeln("OK.");

        }

        if ($tid = $input->getOption('confirm-deaths')) {
            $users = $this->entity_manager->getRepository(User::class)->findAll();

            foreach ($users as $user) {
                if (strstr($user->getEmail(), "@localhost") === "@localhost") {
                    $activeCitizen = $user->getActiveCitizen();
                    if(isset($activeCitizen)) {
                        if (!$activeCitizen->getAlive()) {                           
                            $activeCitizen->setActive(false);
    
                            // Delete not validated picto from DB
                            // Here, every validated picto should have persisted to 2
                            $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUser($user);
                            foreach ($pendingPictosOfUser as $pendingPicto) {
                                $this->entity_manager->remove($pendingPicto);
                            }
    
                            $this->entity_manager->persist( $activeCitizen );                            
                        }
                    }
                }
            }           
            $this->entity_manager->flush();
            
            return 0;
        }

        return 1;
    }
}