<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Service\TwinoidHandler;
use App\Service\UserHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class DebugCommand extends Command
{
    protected static $defaultName = 'app:debug';

    private KernelInterface $kernel;

    private GameFactory $game_factory;
    private EntityManagerInterface $entity_manager;
    private CitizenHandler $citizen_handler;
    private RandomGenerator $randomizer;
    private Translator $trans;
    private InventoryHandler $inventory_handler;
    private ItemFactory $item_factory;
    private UserPasswordEncoderInterface $encoder;
    private ConfMaster $conf;
    private TownHandler $townHandler;
    private CommandHelper $helper;
    private TwinoidHandler $twin;
    private UserHandler $user_handler;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em,
                                RandomGenerator $rg, CitizenHandler $ch, Translator $translator, InventoryHandler $ih,
                                ItemFactory $if, UserPasswordEncoderInterface $passwordEncoder, ConfMaster $c,
                                TownHandler $th, CommandHelper $h, TwinoidHandler $t, UserHandler $uh)
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
        $this->conf = $c;
        $this->townHandler = $th;
        $this->helper = $h;
        $this->twin = $t;
        $this->user_handler = $uh;

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
            ->addOption('fill-town', null, InputOption::VALUE_REQUIRED, 'Sends as much users as possible to a town.')
            ->addOption('no-default', null, InputOption::VALUE_NONE, 'When used with --fill-town, disable joining the town without a job')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Will detach debug users when used with fill-town.')
            ->addOption('fill-bank', null, InputOption::VALUE_REQUIRED, 'Places 500 of each item type in the bank of a given town.')
            ->addOption('confirm-deaths', null, InputOption::VALUE_NONE, 'Confirms death of every account having an email ending on @localhost.')
            ->addOption('test-town-names', null, InputOption::VALUE_REQUIRED, 'Will generate 50 town names')

            ->addOption('compact-active-towns', null, InputOption::VALUE_NONE, 'Deletes non-empty towns without living citizens, but keeps the ranking intact.')
            ->addOption('purge-active-towns', null, InputOption::VALUE_NONE, 'Will end all current towns, but keep their rankings intact.')
            ->addOption('purge-rankings', null, InputOption::VALUE_NONE, 'Will end all current towns and completely wipe the ranking.')
            ->addOption('keep-imported', null, InputOption::VALUE_NONE, 'When used together with --purge-rankings, imported entries will not be deleted.')
            ->addOption('chunk-size', null, InputOption::VALUE_REQUIRED, 'When used together with --purge-active-towns or --purge-rankings, determines how many towns are deleted for each flush (default: 1).')

            ->addOption('update-events', null, InputOption::VALUE_NONE, 'Will check the current event schedule and process hooks accordingly')
            ->addOption('reapply-twinoid-data', null, InputOption::VALUE_NONE, 'Re-applies the stored twinoid data for all users')

            ->addOption('current-event', null, InputOption::VALUE_OPTIONAL, 'Shows the current event.', false)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('add-debug-users') | $input->getOption('add-crow')) {

            if ($input->getOption('add-crow')) {
                /** @var User $crow */
                $crow = $this->entity_manager->getRepository(User::class)->find(66);
                if (!isset($crow)) {
                    $command = $this->getApplication()->find('app:user:create');
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
                    $crow = $this->entity_manager->getRepository(User::class)->find(66);
                }

                if ($crow->getRightsElevation() > User::ROLE_USER || !strstr($crow->getEmail(), "@localhost") === "@localhost") {
                    $output->writeln('<error>User 66 is not a debug user. Will not proceed.</error>');
                    return -1;
                }
                $crow
                    ->setName("Der Rabe")
                    ->setEmail("crow")
                    ->setRightsElevation(User::ROLE_CROW);

                $this->user_handler->setUserBaseAvatar($crow, file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/crow.png"), UserHandler::ImageProcessingPreferImagick, 'png', 100, 100);
                $this->user_handler->setUserSmallAvatar($crow, file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/crow.small.png"));

                try {
                    $crow->setPassword($this->encoder->encodePassword($crow, bin2hex(random_bytes(16))));
                } catch (\Exception $e) {
                    $output->writeln('<error>Unable to generate a random password.</error>');
                    return -1;
                }
                $this->entity_manager->persist($crow);
                $this->entity_manager->flush();               
                
                return 0;
            }

            $command = $this->getApplication()->find('app:user:create');
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
            $town = $this->helper->resolve_string($tid, Town::class, 'Town', $this->getHelper('question'), $input, $output);
            $statusHasDrunk = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => "hasdrunk"]);
            $statusThirst = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => "thirst1"]);
            $statusDehydrated = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => "thirst2"]);

            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }

            $citizens = $town->getCitizens();
            foreach ($citizens as $citizen) {
                if(!$citizen->getAlive()) continue;
                $citizen->addStatus($statusHasDrunk);
                $citizen->removeStatus($statusThirst);
                $citizen->removeStatus($statusDehydrated);
                $this->entity_manager->persist( $citizen );
            }
            $this->entity_manager->flush();
            $output->writeln("All citizen from <info>{$town->getName()}</info> are full of water now.");
            return 0;
        }

        if ($tid = $input->getOption('fill-town')) {
            /** @var Town $town */
            $town = $this->helper->resolve_string($tid, Town::class, 'Town', $this->getHelper('question'), $input, $output);
            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }
            $this->trans->setLocale($town->getLanguage() ?? 'de');

            $force = $input->getOption('force');

            $no_default = $input->getOption("no-default");
            if($no_default)
                $professions = $this->entity_manager->getRepository( CitizenProfession::class )->findSelectable();
            else
                $professions = $this->entity_manager->getRepository( CitizenProfession::class )->findAll();

            $users = $this->entity_manager->getRepository(User::class)->findAll();

            for ($i = 0; $i < $town->getPopulation() - $town->getCitizenCount(); $i++) {
                foreach ($users as $user) {
                    /** @var Citizen $citizen */

                    $citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($user);
                    if ($citizen && $citizen->getTown() !== $town && (!$citizen->getAlive() || $force)) {
                        $citizen->setActive(false);

                        $this->entity_manager->persist($citizen);
                        $this->entity_manager->flush();
                        $citizen = null;
                    }

                    if (!$citizen) {
                        $citizen = $this->entity_manager->getRepository(Citizen::class)->findInTown($user, $town);
                        if ($citizen) $citizen->setActive(true);
                        else $citizen = $this->game_factory->createCitizen($town, $user, $error);
                    } else continue;

                    if (!$citizen) continue;

                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

                    /** @var CitizenProfession $pro */
                    $pro = $this->randomizer->pick($professions);
                    $this->citizen_handler->applyProfession($citizen, $pro);

                    $this->entity_manager->persist($town);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

                    $ii = $i + $town->getCitizenCount() + 1;
                    $output->writeln("<comment>{$user_name}</comment> joins <comment>{$town->getName()}</comment> and fills slot {$ii}/{$town->getPopulation()} as a <comment>{$pro->getLabel()}</comment>.");
                    break;
                }
            }

            $this->entity_manager->flush();
            if (!$town->isOpen()){
                // Target town is closed, let's add special voting actions !
                $roles = $this->entity_manager->getRepository(CitizenRole::class)->findVotable();
                /** @var CitizenRole $role */
                foreach ($roles as $role){
                    /** @var SpecialActionPrototype $special_action */
                    $special_action = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => 'special_vote_' . $role->getName()]);
                    /** @var Citizen $citizen */
                    foreach ($town->getCitizens() as $citizen){
                        if(!$citizen->getProfession()->getHeroic()) continue;

                        if(!$citizen->getSpecialActions()->contains($special_action)) {
                            $citizen->addSpecialAction($special_action);
                            $this->entity_manager->persist($citizen);
                        }
                    }
                }
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
                "multi" => array(
                    "remote" => 100,
                    "panda" => 100,
                    "small" => 100
                ),
            );
            foreach ($openTowns as $openTown) {
                if($openTown->getType()->getName() === 'custom') continue;
                $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
            }

            $current_event = $this->conf->getCurrentEvent();
            foreach ($count as $townLang => $array) {
                foreach ($array as $townClass => $openCount) {
                    if($openCount < 1){
                        $newTown = $this->game_factory->createTown(null, $townLang, null, $townClass);
                        $this->entity_manager->persist($newTown);
                        $this->entity_manager->flush();

                        if ($current_event->active()) {
                            if (!$this->townHandler->updateCurrentEvent($newTown, $current_event))
                                $this->entity_manager->clear();
                            else {
                                $this->entity_manager->persist($newTown);
                            }
                        }
                    }
                }
            }
            $this->entity_manager->flush();
        }

        if ($input->getOption('update-events')) {
            $current_event = $this->conf->getCurrentEvent();
            $towns = $this->entity_manager->getRepository(Town::class)->findAll();
            foreach ($towns as $town) {

                $town_event = $this->conf->getCurrentEvent($town);
                if ($town_event->name() !== $current_event->name()) {

                    $output->write("Town '<info>{$town->getName()}</info>' (<info>{$town->getId()}</info>): Changing currently registered event '<info>{$town_event->name()}</info>' to '<info>{$current_event->name()}</info>'... ");
                    if (!$this->townHandler->updateCurrentEvent($town, $current_event)) {
                        $this->entity_manager->clear();
                        $output->writeln('<error>Failed!</error>');
                    } else {
                        $this->entity_manager->persist($town);
                        $this->entity_manager->flush();
                        $output->writeln('<info>OK.</info>');
                    }

                }

            }
        }

        if ($input->getOption('reapply-twinoid-data')) {
            /** @var TwinoidImport[] $all_imports */

            $tc = $this->entity_manager->getRepository(TwinoidImport::class)->count([]);
            $tc_chunk = 0;

            $output->writeln("Processing <info>$tc</info> entries...");
            $progress = new ProgressBar( $output->section() );
            $progress->start( $tc );

            while ($tc_chunk < $tc) {

                $all_imports = $this->entity_manager->getRepository(TwinoidImport::class)->findBy([], ['id' => 'ASC'], 50, $tc_chunk);
                foreach ($all_imports as $import)
                    if ($this->twin->importData($import->getUser(), $import->getScope(), $import->getData($this->entity_manager), $import->getMain())) {
                        $this->entity_manager->persist($import->getUser());
                        $tc_chunk++;
                    }
                $this->entity_manager->flush();
                $progress->setProgress($tc_chunk);
            }


            $progress->finish();
            $output->writeln("OK.");
        }

        if ($tid = $input->getOption('fill-bank')) {
            $town = $this->helper->resolve_string($tid, Town::class, 'Town', $this->getHelper('question'), $input, $output);
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

        if ($lang = $input->getOption('test-town-names'))
            for ($i = 0; $i < 50; $i++)
                $output->writeln($this->game_factory->createTownName($lang));

        if ($input->getOption('compact-active-towns'))
            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $town)
                if ($this->game_factory->compactTown($town)) {
                    $output->write("<info>Compacting</info> town '<comment>{$town->getName()}</comment>' (<comment>{$town->getId()}</comment>)... ");
                    $this->entity_manager->flush();
                    $output->writeln('<info>OK!</info>');
                } else $output->writeln("<info>Skipped</info> town '<comment>{$town->getName()}</comment>' (<comment>{$town->getId()}</comment>).");

        if ($input->getOption('purge-rankings') || $input->getOption('purge-active-towns')) {

            $purge_rankings = $input->getOption('purge-rankings');

            $c_size = (int)$input->getOption('chunk-size');
            if ($c_size <= 0) $c_size = 1;

            $c = 0;
            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $town) {
                $output->write("Purging active town '<comment>{$town->getName()}</comment>' (<comment>{$town->getId()}</comment>)... ");
                if ($purge_rankings && $town->getRankingEntry()) $this->entity_manager->remove($town->getRankingEntry());
                $this->entity_manager->remove($town);

                if (++$c >= $c_size) {
                    $this->entity_manager->flush();
                    $output->writeln('<info>OK!</info>');
                    $c = 0;
                } else $output->writeln('');

            }

            if ($c > 0) {
                $this->entity_manager->flush();
                $output->writeln('<info>OK!</info>');
                $c = 0;
            }

            if ($purge_rankings) {
                $target_proxies = $input->getOption('keep-imported') ? $this->entity_manager->getRepository(TownRankingProxy::class)->findBy(['imported' => false]) : $this->entity_manager->getRepository(TownRankingProxy::class)->findAll();
                foreach ($target_proxies as $town) {
                    $output->write("Purging archived town '<comment>{$town->getName()}</comment>' (<comment>{$town->getBaseID()}</comment>)... ");
                    $this->entity_manager->remove($town);
                    if (++$c >= $c_size) {
                        $this->entity_manager->flush();
                        $output->writeln('<info>OK!</info>');
                        $c = 0;
                    } else $output->writeln('');
                }

                if ($c > 0) {
                    $this->entity_manager->flush();
                    $output->writeln('<info>OK!</info>');
                }
            }

        }

        if (($date = $input->getOption('current-event')) !== false) {
            $m = null;
            try {
                $dateTime = (new DateTime($date ?? 'now'));
            } catch (Exception $e) {
                $output->writeln('<error>Invalid date.</error>');
                return 1;
            }


            $event = $this->conf->getCurrentEvent(null,$m, $dateTime);
            if ($event->active()) $output->writeln("<comment>{$dateTime->format('c')}:</comment> Current event: <info>{$event->name()}</info>");
            else $output->writeln("<comment>{$dateTime->format('c')}:</comment> There is <info>no current event</info>.");
        }

        return 0;
    }
}