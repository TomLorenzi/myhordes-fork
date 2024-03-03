<?php


namespace App\Command;


use App\Entity\Avatar;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\ZombieEstimation;
use App\Enum\EventStages\BuildingValueQuery;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Service\TwinoidHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:debug',
    description: 'Debug options.'
)]
class DebugCommand extends LanguageCommand
{
    private KernelInterface $kernel;

    private GameFactory $game_factory;
    private EntityManagerInterface $entity_manager;
    private CitizenHandler $citizen_handler;
    private RandomGenerator $randomizer;
    private Translator $trans;
    private InventoryHandler $inventory_handler;
    private ItemFactory $item_factory;
    private UserPasswordHasherInterface $encoder;
    private ConfMaster $conf;
    private TownHandler $townHandler;
    private TwinoidHandler $twin;
    private GameProfilerService $gps;
	private EventProxyService $events;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em,
                                RandomGenerator $rg, CitizenHandler $ch, Translator $translator, InventoryHandler $ih,
                                ItemFactory $if, UserPasswordHasherInterface $passwordEncoder, ConfMaster $c,
                                TownHandler $th, CommandHelper $h, TwinoidHandler $t, GameProfilerService $gps, EventProxyService $eps)
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
        $this->gps = $gps;
		$this->events = $eps;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Debug options.')
            
            ->addOption('everyone-drink', null, InputOption::VALUE_REQUIRED, 'Unset thirst status of all citizen.')
            ->addOption('add-crow', null, InputOption::VALUE_NONE, 'Creates the crow account. Also creates 80 validated users in case there are less than 66 users.')
            ->addOption('add-animactor', null, InputOption::VALUE_NONE, 'Creates the animactor account. Also creates 80 validated users in case there are less than 66 users.')
            ->addOption('add-debug-users', null, InputOption::VALUE_NONE, 'Creates 80 validated users.')
            ->addOption('fill-town', null, InputOption::VALUE_REQUIRED, 'Sends as much users as possible to a town.')
            ->addOption('no-default', null, InputOption::VALUE_NONE, 'When used with --fill-town, disable joining the town without a job')
            ->addOption('no-dummy', null, InputOption::VALUE_NONE, 'When used with --fill-town, disable joining the town for the dummy users')
            ->addOption('max-users', null, InputOption::VALUE_REQUIRED, 'When used with --fill-town, limit the number of user joining the town')
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

            ->addOption('test-estim', null, InputOption::VALUE_REQUIRED, 'Takes all the citizens in the town and make them go into the watchtower', false)
            ->addOption('keep-estims', null, InputOption::VALUE_NONE, 'If set, leaves the estimations in the DB')
            ->addOption('estim-day', null, InputOption::VALUE_REQUIRED, 'Specific day for estimations.', 0)
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('add-debug-users') | $input->getOption('add-crow') | $input->getOption('add-animactor')) {

            if ($input->getOption('add-crow')) {
                /** @var User $crow */
                $crow = $this->entity_manager->getRepository(User::class)->find(66);
                if ($crow === null) {
                    $command = $this->getApplication()->find('app:user:create');

                    for ($i = 1; $i <= 80; $i++) {
                        $user_name = 'user_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                        $nested_input = new ArrayInput([
                            'name' => $user_name,
                            'email' => $user_name . '@localhost',
                            'password' => $this->randomizer->string( 32 ),
                            '--validated' => true,
                        ]);
                        $command->run($nested_input, $output);
                    }
                    $crow = $this->entity_manager->getRepository(User::class)->find(66);
                }

                if ($crow->getRightsElevation() > User::USER_LEVEL_BASIC || !str_ends_with($crow->getEmail(), "@localhost")) {
                    $output->writeln('<error>User 66 is not a debug user. Will not proceed.</error>');
                    return -1;
                }

                $avatar_data = file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/crow.png");
                $avatar_small_data = file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/crow.small.png");

                $crow
                    ->setName("Der Rabe")
                    ->setEmail("crow")
                    ->setRightsElevation(User::USER_LEVEL_CROW)
                    ->setAvatar( (new Avatar())
                        ->setChanged(new \DateTime())
                        ->setFilename( md5( $avatar_data ) )
                        ->setSmallName( md5( $avatar_small_data ) )
                        ->setFormat( 'png' )
                        ->setImage( $avatar_data )
                        ->setSmallImage( $avatar_small_data )
                        ->setX( 100 )
                        ->setY( 100 )
                    );

                try {
                    $crow->setPassword($this->encoder->hashPassword($crow, bin2hex(random_bytes(16))));
                } catch (\Exception $e) {
                    $output->writeln('<error>Unable to generate a random password.</error>');
                    return -1;
                }
                $this->entity_manager->persist($crow);
                $this->entity_manager->flush();               
                
                return 0;
            }

            if ($input->getOption('add-animactor')) {
                /** @var User $animacteur */
                $animacteur = $this->entity_manager->getRepository(User::class)->find(67);
                if ($animacteur === null) {
                    $command = $this->getApplication()->find('app:user:create');
                    for ($i = 1; $i <= 80; $i++) {
                        $user_name = 'user_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                        $nested_input = new ArrayInput([
                                                           'name' => $user_name,
                                                           'email' => $user_name . '@localhost',
                                                           'password' => $this->randomizer->string( 32 ),
                                                           '--validated' => true,
                                                       ]);
                        $command->run($nested_input, $output);
                    }
                    $animacteur = $this->entity_manager->getRepository(User::class)->find(67);
                }

                if ($animacteur->getRightsElevation() > User::USER_LEVEL_BASIC || !str_ends_with($animacteur->getEmail(), "@localhost")) {
                    $output->writeln('<error>User 67 is not a debug user. Will not proceed.</error>');
                    return -1;
                }

                $avatar_data = file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/anim.gif");
                $avatar_small_data = file_get_contents("{$this->kernel->getProjectDir()}/assets/img/forum/crow/anim.small.gif");

                $animacteur
                    ->setName("Animateur-Team")
                    ->setEmail("anim")
                    ->addRoleFlag( User::USER_ROLE_ANIMAC )
                    ->setAvatar( (new Avatar())
                        ->setChanged(new \DateTime())
                        ->setFilename( md5( $avatar_data ) )
                        ->setSmallName( md5( $avatar_small_data ) )
                        ->setFormat( 'gif' )
                        ->setImage( $avatar_data )
                        ->setSmallImage( $avatar_small_data )
                        ->setX( 100 )
                        ->setY( 100 )
                    );

                try {
                    $animacteur->setPassword($this->encoder->hashPassword($animacteur, bin2hex(random_bytes(16))));
                } catch (\Exception $e) {
                    $output->writeln('<error>Unable to generate a random password.</error>');
                    return -1;
                }
                $this->entity_manager->persist($animacteur);
                $this->entity_manager->flush();

                return 0;
            }

            $command = $this->getApplication()->find('app:user:create');
            for ($i = 1; $i <= 80; $i++) {
                $user_name = 'user_' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $nested_input = new ArrayInput([
                    'name' => $user_name,
                    'email' => $user_name . '@localhost',
                    'password' => $this->randomizer->string( 32 ),
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

            $max_user = $input->getOption("max-users");
            if(!$max_user)
                $max_user = $town->getPopulation() - $town->getCitizenCount();
            else
                $max_user = max(0, $max_user - $town->getCitizenCount());

            $users = $this->entity_manager->getRepository(User::class)->findAll();

            $no_dummy = $input->getOption('no-dummy');

            for ($i = 0; $i < $max_user; $i++) {
                /** @var User $user */
                foreach ($users as $user) {
                    if($no_dummy && strstr($user->getEmail(), "@localhost") === "@localhost" || str_starts_with($user->getEmail(), '$ deleted')) continue;

                    /** @var Citizen $citizen */
                    $citizen = $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($user);
                    if ($citizen && $citizen->getTown() !== $town && (!$citizen->getAlive() || $force)) {
                        $citizen->setActive(false);

                        $this->entity_manager->persist($citizen);
                        $this->entity_manager->flush();
                        $citizen = null;
                    }


                    $all = [];
                    if (!$citizen) {
                        $citizen = $this->entity_manager->getRepository(Citizen::class)->findInTown($user, $town);
                        if ($citizen)
                            $citizen->setActive(true);
                        else {
                            $citizen = $this->game_factory->createCitizen($town, $user, $error, $all);
                        }
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

                    /** @var Citizen $joined_citizen */
                    foreach ( $all as $joined_citizen ) {
                        if ($citizen->getProfession()->getName() !== 'none')
                            $this->gps->recordCitizenProfessionSelected( $joined_citizen );
                        if($joined_citizen !== $citizen) {
                            $output->writeln("Coalition member <comment>{$joined_citizen->getUser()->getName()}</comment> joins <comment>{$town->getName()}</comment> as a <comment>{$this->trans->trans($pro->getLabel(), [], 'game')}</comment>.");
                            $i += 1;
                        }
                    }
                    $this->entity_manager->flush();

                    $ii = $i + $town->getCitizenCount() + 1;

                    $output->writeln("<comment>{$user->getName()}</comment> joins <comment>{$town->getName()}</comment> and fills slot {$ii}/{$town->getPopulation()} as a <comment>{$this->trans->trans($pro->getLabel(), [], 'game')}</comment>.");
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

            $this->entity_manager->flush();
        }

        if ($input->getOption('update-events')) {
            $current_events = $this->conf->getCurrentEvents();
            $towns = $this->entity_manager->getRepository(Town::class)->findAll();
            foreach ($towns as $town) {

                // The town's events are managed manually; skip auto-updating it!
                if ($town->getManagedEvents()) continue;

                $must_enable  = [];
                $must_disable = [];

                if (!$this->conf->checkEventActivation($town, $must_enable, $must_disable)) {

                    $output->write("Town '<info>{$town->getName()}</info>' (<info>{$town->getId()}</info>): Disable events [<info>" . implode('</info>,<info>', $must_disable) . "</info>] and enable [<info>" . implode('</info>,<info>', $must_enable) . "</info>]... ");
                    if (!$this->townHandler->updateCurrentEvents($town, $current_events)) {
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
            $limited = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_LIMITED, false);
            $threshold = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_SP_THRESHOLD, -1);
            $town_threshold = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_THRESHOLD, -1);
            $town_cutoff = $this->conf->getGlobalConf()->get(MyHordesConf::CONF_IMPORT_TW_CUTOFF, -1);
            if ($town_cutoff > 0) $town_cutoff = (new DateTime())->setTimestamp($town_cutoff);
            else $town_cutoff = null;

            $this->helper->leChunk($output, TwinoidImport::class, 50, [], true, true, function(TwinoidImport $import) use ($limited,$threshold,$town_threshold,$town_cutoff) {
                $limit = $limited && ($import->getUser()->getSoulPoints() > $threshold || $this->entity_manager->getRepository(CitizenRankingProxy::class)->countNonAlphaTowns($import->getUser(), $town_cutoff, true) > $town_threshold);
                $this->twin->importData($import->getUser(), $import->getScope(), $import->getData($this->entity_manager), $import->getMain(), $limit);
            }, true);

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
            $criteria = new Criteria();
            $criteria->andWhere($criteria->expr()->contains('email', '@localhost'));

            $users = $this->entity_manager->getRepository(User::class)->matching($criteria);

            /** @var User $user */
            foreach ($users as $user) {
                /** @var CitizenRankingProxy $nextDeath */
                $nextDeaths = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findAllUnconfirmedDeath($user);
                foreach ($nextDeaths as $nextDeath) {
                    if ($nextDeath !== null && ($nextDeath->getCitizen() && !$nextDeath->getCitizen()->getAlive())) {
                        echo "Confirm death of user {$user->getName()} in town {$nextDeath->getTown()->getName()}\n";
                        // Delete not validated picto from DB
                        // Here, every validated picto should have persisted to 2
                        $pendingPictosOfUser = $this->entity_manager->getRepository(Picto::class)->findPendingByUserAndTown($user, $nextDeath->getTown());
                        foreach ($pendingPictosOfUser as $pendingPicto) {
                            $this->entity_manager->remove($pendingPicto);
                        }

                        $nextDeath->setConfirmed(true);
                        $this->entity_manager->persist($nextDeath);
                    }
                }
            }
            $this->entity_manager->flush();
            
            return 0;
        }

        if ($setting = $input->getOption('test-town-names')) {

            [$lang,$mutator] = explode('.', "$setting.");

            $table = new Table( $output );
            $table->setHeaders( ['Schema', 'Name'] );

            for ($i = 0; $i < 50; $i++) {
                $name = $this->game_factory->createTownName($lang, $schema, $mutator ?: null);
                $table->addRow(["<comment>$schema</comment>", $name ]);
            }

            $table->render();
            return 0;
        }

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

            $events = array_map(fn(EventConf $e) => $e->name(), array_filter( $this->conf->getCurrentEvents(null,$m, $dateTime), fn(EventConf $e) => $e->active() ));
            if (!empty($events)) $output->writeln("<comment>{$dateTime->format('c')}:</comment> Current events: [<info>" . implode('</info>,<info>', $events) . "</info>]");
            else $output->writeln("<comment>{$dateTime->format('c')}:</comment> There are <info>no current events</info>.");
        }

        if($tid = $input->getOption('test-estim')) {
            /** @var Town $town */
            $town = $this->helper->resolve_string($tid, Town::class, 'Town', $this->getHelper('question'), $input, $output);
            if (!$town) {
                $output->writeln('<error>Town not found!</error>');
                return 2;
            }

            $citizens = $town->getCitizens();
            $day = (int)$input->getOption('estim-day') ?: $town->getDay();

            $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneBy(['town' => $town, 'day' => $day]);
            if (!$est){
                $output->writeln("<error>There's no estimation for the current town's day !</error>");
                return 1;
            }

            $est->getCitizens()->clear();
            $this->entity_manager->persist($est);
            $this->entity_manager->flush();

			$redsouls = $this->townHandler->get_red_soul_count($town);
			$red_soul_penality = $this->events->queryTownParameter( $town, BuildingValueQuery::NightlyRedSoulPenalty );
			$soulFactor = min(1 + ($red_soul_penality * $redsouls), (float)$this->conf->getTownConfiguration($town)->get(TownConf::CONF_MODIFIER_RED_SOUL_FACTOR, 1.2));

            $output->writeln("Attack for day {$est->getDay()} : <info>{$est->getZombies()}</info>, soul factor is <info>$soulFactor</info>, real attack will be <info>" . ($est->getZombies() * $soulFactor) . "</info>");

            $table = new Table( $output );
            $table->setHeaders( ['Precision', 'Min', 'Max', 'Off Min', 'Off Max'] );

            $new_way = $this->townHandler->get_zombie_estimation($town, $day, 0);
            $estim = round($new_way[0]->getEstimation() * 100);

            $table->addRow([
                $estim,
                $new_way[0]->getMin(),
                $new_way[0]->getMax(),
                round( 100 * $new_way[0]->getMin() / $est->getZombies() ),
                round( 100 * $new_way[0]->getMax() / $est->getZombies() ),
            ]);

            foreach ($citizens as $citizen) {
                if ($est->getCitizens()->contains($citizen)) continue;

                $est->addCitizen($citizen);

                try {
                    $this->entity_manager->persist($est);
                    $this->entity_manager->flush();
                } catch (Exception $e) {
                    $output->writeln("<error>A DB exception occured ! {$e->getMessage()}</error>");
                    return 3;
                }

                $new_way = $this->townHandler->get_zombie_estimation($town, $day, 0);
                $estim = round($new_way[0]->getEstimation() * 100);

                $table->addRow([
                    $estim,
                    $new_way[0]->getMin(),
                    $new_way[0]->getMax(),
                    round( 100 * $new_way[0]->getMin() / $est->getZombies() ),
                    round( 100 * $new_way[0]->getMax() / $est->getZombies() ),
                ]);
                if($new_way[0]->getEstimation() >= 1) break;
            }

            $table->render();

            $est2 = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town, $day + 1);
            $output->writeln("Attack for day {$est2->getDay()} : <info>{$est2->getZombies()}</info>, soul factor is <info>$soulFactor</info>, real attack will be <info>" . ($est2->getZombies() * $soulFactor) . "</info>");

            $est2->getCitizens()->clear();
            $this->entity_manager->persist($est2);
            $this->entity_manager->flush();

            $table = new Table( $output );
            $table->setHeaders( ['Precision', 'Min', 'Max', 'Off Min', 'Off Max'] );

            if(!empty($this->townHandler->getBuilding($town, 'item_tagger_#02'))) {
                foreach ($citizens as $citizen) {
                    if ($est->getCitizens()->contains($citizen)) continue;
                    $est->addCitizen($citizen);

                    try {
                        $this->entity_manager->persist($est);
                        $this->entity_manager->flush();
                    } catch (Exception $e) {
                        $output->writeln("<error>A DB exception occured ! {$e->getMessage()}</error>");
                        return 3;
                    }

                    $new_way = $this->townHandler->get_zombie_estimation($town, $day + 1, 0);
                    $estim = round($new_way[1]->getEstimation() * 100);

                    $table->addRow([
                        $estim,
                        $new_way[1]->getMin(),
                        $new_way[1]->getMax(),
                        round( 100 * $new_way[1]->getMin() / $est2->getZombies() ),
                        round( 100 * $new_way[1]->getMax() / $est2->getZombies() ),
                    ]);
                    if($new_way[1]->getEstimation() >= 1) break;
                }
            }

            $table->render();
            if(!$input->getOption('keep-estims')) {
                $est->getCitizens()->clear();
                $est2?->getCitizens()?->clear();
            }

            $this->entity_manager->persist($est);
            if ($est2) $this->entity_manager->persist($est2);
            $this->entity_manager->flush();
        }

        return 0;
    }
}