<?php


namespace App\Command;


use App\Entity\AccountRestriction;
use App\Entity\AdminBan;
use App\Entity\AffectStatus;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\GitVersions;
use App\Entity\HeroicActionPrototype;
use App\Entity\Item;
use App\Entity\Picto;
use App\Entity\Post;
use App\Entity\RuinZone;
use App\Entity\Season;
use App\Entity\ShadowBan;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\Zone;
use App\Entity\ZonePrototype;
use App\Entity\ZoneTag;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\MazeMaker;
use App\Service\PermissionHandler;
use App\Service\RandomGenerator;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    private $kernel;

    private GameFactory $game_factory;
    private EntityManagerInterface $entity_manager;
    private CitizenHandler $citizen_handler;
    private RandomGenerator $randomizer;
    private InventoryHandler $inventory_handler;
    private ConfMaster $conf;
    private MazeMaker $maze;
    private ParameterBagInterface $param;
    private UserHandler $user_handler;
    private UserFactory $user_factory;
    private PermissionHandler $perm;

    protected static $git_script_repository = [
        'ce5c1810ee2bde2c10cc694e80955b110bbed010' => [ ['app:migrate', ['--calculate-score' => true] ] ],
        'e3a28a35e8ade5c767480bb3d8b7fa6daaf69f4e' => [ ['app:migrate', ['--build-forum-search-index' => true] ] ],
        'd9960996e6d39ecc6431ef576470a048e4b43774' => [ ['app:migrate', ['--migrate-account-bans' => true] ] ],
        '2fd50ce43146b72886d94077a044bc22b94f3ef6' => [ ['app:migrate', ['--assign-awards' => true] ] ]
    ];

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em,
                                RandomGenerator $rg, CitizenHandler $ch, InventoryHandler $ih, ConfMaster $conf,
                                MazeMaker $maze, ParameterBagInterface $params, UserHandler $uh, PermissionHandler $p,
                                UserFactory $uf)
    {
        $this->kernel = $kernel;

        $this->game_factory = $gf;
        $this->entity_manager = $em;
        $this->randomizer = $rg;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;
        $this->conf = $conf;
        $this->maze = $maze;
        $this->param = $params;
        $this->user_handler = $uh;
        $this->perm = $p;
        $this->user_factory = $uf;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Performs migrations to update content after a version update.')
            ->setHelp('Migrations.')

            ->addOption('maintenance', 'm', InputOption::VALUE_REQUIRED, 'Enables (on) or disables (off) maintenance mode')

            ->addOption('from-git', 'g',    InputOption::VALUE_NONE, 'Switches to the given git branch and updates everything.')
            ->addOption('remote', null,     InputOption::VALUE_REQUIRED, 'Sets the git remote for --from-git')
            ->addOption('branch', null,     InputOption::VALUE_REQUIRED, 'Sets the git branch for --from-git')
            ->addOption('environment', null,InputOption::VALUE_REQUIRED, 'Sets the symfony environment to build assets for')
            ->addOption('phar', null,InputOption::VALUE_NONE, 'If set, composer will be invoked using a composer.phar file')
            ->addOption('fast', null,InputOption::VALUE_NONE, 'If set, composer and yarn updates will be skipped')
            ->addOption('stay-offline', null,InputOption::VALUE_NONE, 'If set, maintenance mode will be kept active after the update')

            ->addOption('install-db', 'i', InputOption::VALUE_NONE, 'Creates and performs the creation of the database and fixtures.')
            ->addOption('update-db', 'u', InputOption::VALUE_NONE, 'Creates and performs a doctrine migration, updates fixtures.')
            ->addOption('recover', 'r',   InputOption::VALUE_NONE, 'When used together with --update-db, will clear all previous migrations and try again after an error.')
            ->addOption('process-db-git', 'p',   InputOption::VALUE_NONE, 'Processes triggers for automated database actions')

            ->addOption('update-trans', 't', InputOption::VALUE_REQUIRED, 'Updates all translation files for a single language')

            ->addOption('assign-heroic-actions-all', null, InputOption::VALUE_NONE, 'Resets the heroic actions for all citizens in all towns.')
            ->addOption('assign-special-actions-all', null, InputOption::VALUE_NONE, 'Resets the special actions for all citizens in all towns.')
            ->addOption('assign-town-season', null, InputOption::VALUE_NONE, 'Assigns the towns with no season to the latest available.')
            ->addOption('init-item-stacks', null, InputOption::VALUE_NONE, 'Sets item count for items without a counter to 1')
            ->addOption('calculate-score', null, InputOption::VALUE_NONE, 'Recalculate the score for each ended town')
            ->addOption('build-forum-search-index', null, InputOption::VALUE_NONE, 'Initializes search structures for the forum')
            ->addOption('migrate-account-bans', null, InputOption::VALUE_NONE, 'Migrates old account bans to the new system')

            ->addOption('update-ranking-entries', null, InputOption::VALUE_NONE, 'Update ranking values')
            ->addOption('fix-ruin-inventories', null, InputOption::VALUE_NONE, 'Move each items belonging to a RuinRoom to its corresponding RuinZone')
            ->addOption('update-shaman-immune', null, InputOption::VALUE_NONE, 'Changes status tg_immune to tg_shaman_immune')
            ->addOption('place-explorables', null, InputOption::VALUE_NONE, 'Adds explorable ruins to all towns')
            ->addOption('assign-awards', null, InputOption::VALUE_NONE, 'Adds explorable ruins to all towns')

            ->addOption('repair-permissions', null, InputOption::VALUE_NONE, 'Makes sure forum permissions and user groups are set up properly')
        ;
    }

    protected function leChunk( OutputInterface $output, string $repository, int $chunkSize, array $filter, bool $manualChain, bool $alwaysPersist, callable $handler) {
        $tc = $this->entity_manager->getRepository($repository)->count($filter);
        $tc_chunk = 0;

        $output->writeln("Processing <info>$tc</info> <comment>$repository</comment> entities...");
        $progress = new ProgressBar( $output->section() );
        $progress->start($tc);

        while ($tc_chunk < $tc) {
            $entities = $this->entity_manager->getRepository($repository)->findBy($filter,['id' => 'ASC'], $chunkSize, $manualChain ? $tc_chunk : 0);
            foreach ($entities as $entity) {
                if ($alwaysPersist) {
                    $handler($entity);
                    $this->entity_manager->persist($entity);
                } else if ($handler($entity)) $this->entity_manager->persist($entity);
                $tc_chunk++;
            }
            $this->entity_manager->flush();
            $progress->setProgress($tc_chunk);
        }

        $output->writeln('OK!');
    }

    /**
     * @param string $command
     * @param int|null $ret
     * @param bool|false $detach
     * @param OutputInterface|null $output
     * @return string[]
     */
    protected function bin( string $command, ?int &$ret = null, bool $detach = false, ?OutputInterface $output = null ): array {
        $process_handle = popen( $command, 'r' );

        $lines = [];
        if (!$detach) while (($line = fgets( $process_handle )) !== false) {
            if ($output) $output->write( "> {$line}" );
            $lines[] = $line;
        }

        $ret = pclose($process_handle);
        return $lines;
    }

    protected function capsule( string $command, OutputInterface $output, ?string $note = null, bool $bin_console = true ): bool {
        $run_command = $bin_console ? "php bin/console $command 2>&1" : "$command 2>&1";

        $verbose = $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;

        $output->write($note !== null ? $note : ("<info>Executing " . ($bin_console ? 'encapsulated' : '') . " command \"<comment>$command</comment>\"... </info>"));
        $lines = $this->bin( $run_command, $ret, false, $verbose ? $output : null );

        if ($ret !== 0) {
            $output->writeln('');
            if ($note !== null) $output->writeln("<info>Command was \"<comment>{$run_command}</comment>\"</info>");
            if (!$verbose) foreach ($lines as $line) $output->write( "> {$line}" );
            $output->writeln("<error>Command exited with error code {$ret}</error>");
        } else $output->writeln("<info>Ok.</info>");

        return $ret === 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($m = $input->getOption('maintenance')) {

            $file = "{$this->param->get('kernel.project_dir')}/public/maintenance/.active";
            if ($m === 'on') file_put_contents($file, "");
            else if ($m === 'off') unlink( $file );
            else {
                $output->writeln("<error>Unknown command: '$m'.</error>");
                return 1;
            }

            $output->writeln("Ok.");
            return 0;

        }

        if ($input->getOption('from-git')) {

            $remote = $input->getOption('remote');
            $branch = $input->getOption('branch');
            $env    = $input->getOption('environment');

            if (!$this->capsule( "app:migrate --maintenance on", $output, 'Enable maintenance mode... ', true )) return -1;

            for ($i = 3; $i > 0; --$i) {
                $output->writeln("Beginning update in <info>{$i}</info> seconds....");
                sleep(1);
            }

            if (!$this->capsule( "git fetch --tags {$remote} {$branch}", $output, 'Retrieving updates from repository... ', false )) return 1;
            if (!$this->capsule( "git reset --hard {$remote}/{$branch}", $output, 'Applying changes to filesystem... ', false )) return 2;

            if (!$input->getOption('fast')) {
                if ($env === 'dev') {
                    if (!$this->capsule( ($input->getOption('phar') ? 'php composer.phar' : 'composer') . " install", $output, 'Updating composer dependencies...', false )) return 3;
                } else if (!$this->capsule( ($input->getOption('phar') ? 'php composer.phar' : 'composer') . " install --no-dev --optimize-autoloader", $output, 'Updating composer production dependencies... ', false )) return 4;
                if (!$this->capsule( "yarn install", $output, 'Updating yarn dependencies... ', false )) return 5;
            } else $output->writeln("Skipping <info>dependency updates</info>.");

            if (!$input->getOption('fast')) {
                if (!$this->capsule( "yarn encore {$env}", $output, 'Building web assets... ', false )) return 6;
            } else $output->writeln("Skipping <info>web asset updates</info>.");

            $version_lines = $this->bin( 'git describe --tags', $ret );
            if (count($version_lines) >= 1) file_put_contents( 'VERSION', $version_lines[0] );

            if (!$this->capsule( "cache:clear", $output, 'Clearing cache... ', true )) return 7;
            if (!$this->capsule( "app:migrate -u -r", $output, 'Updating database... ', true )) return 8;
            if (!$this->capsule( "app:migrate -p", $output, 'Running post-installation scripts... ', true )) return 9;

            if (count($version_lines) >= 1) $output->writeln("Updated MyHordes to version <info>{$version_lines[0]}</info>");

            if (!$input->getOption('stay-offline')) {
                for ($i = 3; $i > 0; --$i) {
                    $output->writeln("Disabling maintenance mode in <info>{$i}</info> seconds....");
                    sleep(1);
                }
                if (!$this->capsule( "app:migrate --maintenance off", $output, 'Disable maintenance mode... ', true )) return -1;
            } else $output->writeln("Maintenance is kept active. Disable with '<info>app:migrate --maintenance off</info>'");

        }

        if ($input->getOption('install-db')) {

            $output->writeln("\n\n=== <info>Creating database and loading static content</info> ===\n");

            if (!$this->capsule( 'doctrine:database:create', $output )) {
                $output->writeln("<error>Unable to create database.</error>");
                return 1;
            }

            if (!$this->capsule( 'doctrine:schema:update --force', $output )) {
                $output->writeln("<error>Unable to create schema.</error>");
                return 2;
            }

            if (!$this->capsule( 'doctrine:fixtures:load --append', $output )) {
                $output->writeln("<error>Unable to update fixtures.</error>");
                return 3;
            }

            $output->writeln("\n\n=== <info>Creating default user accounts and groups</info> ===\n");

            if (!$this->capsule( 'app:migrate --repair-permissions', $output )) {
                $output->writeln("<error>Unable to generate default permission set.</error>");
                return 4;
            }

            if (!$this->capsule( 'app:debug --add-crow', $output )) {
                $output->writeln("<error>Unable to add users and create crow.</error>");
                return 4;
            }

            $output->writeln("\n\n=== <info>Optional setup steps</info> ===\n");

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create world forums? (y/n) ", true
            ) );
            if ($result) {
                if (!$this->capsule('app:forum:create "Weltforum" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->capsule('app:forum:create "Forum Monde" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->capsule('app:forum:create "World Forum" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->capsule('app:forum:create "Foro Mundial" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
            }

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create a town? (y/n) ", true
            ) );
            if ($result) {
                if (!$this->capsule('app:town:create remote 40 en', $output)) {
                    $output->writeln("<error>Unable to create english town.</error>");
                    return 5;
                }
            }

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create an administrator account? (y/n) ", true
            ) );
            if ($result) {
                $name = $this->getHelper('question')->ask($input, $output, new Question(
                    "Please enter the username: ", 'admin'
                ) );
                $mail = $this->getHelper('question')->ask($input, $output, new Question(
                    "Please enter the e-mail address: ", 'admin@localhost'
                ) );

                $proceed = false;
                while (!$proceed) {
                    $q = new Question( "Please enter the account password: ", '' );
                    $q->setHidden(true);
                    $password1 = $this->getHelper('question')->ask($input, $output, $q );

                    $q = new Question( "Please repeat the account password: ", '' );
                    $q->setHidden(true);
                    $password2 = $this->getHelper('question')->ask($input, $output, $q );

                    $proceed = $password1 === $password2;
                    if (!$proceed) $output->writeln('<error>The passwords did not match.</error> Please try again.');
                }

                $new_user = $this->user_factory->createUser( $name, $mail, $password1, true, $error );
                if ($error !== UserFactory::ErrorNone) return -$error;
                $new_user->setRightsElevation(User::ROLE_SUPER);
                $this->entity_manager->persist($new_user);
                $this->entity_manager->flush();

                $output->writeln('Your user account <info>has been created</info>.');
            }

            return 0;
        }

        if ($input->getOption('update-db')) {

            if (!$this->capsule( 'doctrine:migrations:diff --allow-empty-diff --formatted --no-interaction', $output )) {
                $output->writeln("<error>Unable to create a migration.</error>");
                return 1;
            }

            if (!$this->capsule( 'doctrine:migrations:migrate --all-or-nothing --allow-no-migration --no-interaction', $output )) {

                if ($input->getOption('recover')) {
                    $output->writeln("<warning>Unable to migrate database, attempting recovery.</warning>");

                    $source = "{$this->param->get('kernel.project_dir')}/src/Migrations";
                    foreach (scandir( $source ) as $file)
                        if ($file && $file[0] !== '.') {
                            $output->write("\tDeleting \"<comment>{$file}</comment>\"... ");
                            unlink( "$source/$file" );
                            $output->writeln('<info>Ok!</info>');
                        }

                    if (!$this->capsule( 'doctrine:migrations:version --all --delete --no-interaction', $output )) {
                        $output->writeln("<error>Unable to clean migrations.</error>");
                        return 4;
                    }

                    if (!$this->capsule( 'doctrine:migrations:diff --allow-empty-diff --formatted --no-interaction', $output )) {
                        $output->writeln("<error>Unable to create a migration.</error>");
                        return 1;
                    }

                    if (!$this->capsule( 'doctrine:migrations:migrate --all-or-nothing --allow-no-migration --no-interaction', $output )) {
                        $output->writeln("<error>Unable to migrate database.</error>");
                        return 2;
                    }
                } else {
                    $output->writeln("<error>Unable to migrate database.</error>");
                    return 2;
                }


            }

            if (!$this->capsule( 'doctrine:fixtures:load --append', $output )) {
                $output->writeln("<error>Unable to update fixtures.</error>");
                return 3;
            }

            return 0;
        }

        if ($input->getOption('process-db-git')) {

            $hashes = array_reverse( $this->bin( 'git rev-list HEAD', $ret ) );
            $output->writeln('Found <info>' . count($hashes) . '</info> installed patches.');

            $new = 0;
            $git_repo = $this->entity_manager->getRepository(GitVersions::class);
            foreach ($hashes as $hash)
                if ($git_repo->count(['version' => trim($hash)]) === 0) {
                    $new++;
                    $this->entity_manager->persist((new GitVersions())->setVersion(trim($hash))->setInstalled(false));
                }

            if ($new > 0) {
                $this->entity_manager->flush();
                $output->writeln("<info>$new</info> patches have been newly discovered.");
            } else $output->writeln("<info>No</info> patches have been newly discovered.");

            /** @var GitVersions[] $uninstalled */
            $uninstalled = $git_repo->findBy(['installed' => false], ['id' => 'ASC']);

            if (count($uninstalled) > 0) $output->writeln('Completing database setup for <info>' . count($uninstalled) . '</info> patches.');
            else $output->writeln('No patches marked for installation.');

            foreach ($uninstalled as $version) {
                if (isset(static::$git_script_repository[$version->getVersion()])) {
                    $this->entity_manager->flush();
                    $output->writeln("\tInstalling <comment>{$version->getVersion()}</comment>...");
                    foreach (static::$git_script_repository[$version->getVersion()] as $script) {

                        $input = new ArrayInput($script[1]);
                        $input->setInteractive(false);

                        try {
                            $this->getApplication()->find($script[0])->run($input, $output);
                        } catch (Exception $e) {
                            $output->writeln("Error: <error>{$e->getMessage()}</error>");
                            return 1;
                        }
                    }

                    $output->writeln("\t<info>OK!</info>");
                    $this->entity_manager->persist( $version->setInstalled(true) );
                    $this->entity_manager->flush();
                } else {
                    $this->entity_manager->persist( $version->setInstalled(true) );
                }
            }

            $this->entity_manager->flush();

            return 0;
        }

        if ($lang = $input->getOption('update-trans')) {

            $langs = ($lang === 'all') ? ['de','en','fr','es'] : [$lang];
            if (count($langs) === 1) {

                $command = $this->getApplication()->find('translation:update');

                $output->writeln("Now working on translations for <info>{$lang}</info>...");
                $input = new ArrayInput([
                    'locale' => $lang,
                    '--force' => true,
                    '--sort' => 'asc',
                    '--output-format' => 'xlf2',
                    '--prefix' => '',
                ]);
                $input->setInteractive(false);
                try {
                    $command->run($input, new NullOutput());
                } catch (Exception $e) {
                    $output->writeln("Error: <error>{$e->getMessage()}</error>");
                    return 1;
                }

            } elseif (extension_loaded('pthreads')) {
                $threads = [];
                foreach ($langs as $current_lang) {

                    $threads[] = new class(function () use ($current_lang,$output) {
                        $this->capsule("app:migrate -t $current_lang", $output);
                    }) extends \Worker {
                        private $_f;
                        public function __construct(callable $fun) { $this->_f = $fun; }
                        public function run() { ($this->_f)(); }
                    };
                }

                foreach ($threads as $thread) $thread->start();
                foreach ($threads as $thread) $thread->join();
            } else foreach ($langs as $current_lang)
                $this->capsule("app:migrate -t $current_lang", $output);

            return 0;
        }

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

        if ($input->getOption('assign-special-actions-all')) {
            $special_actions = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findAll();
            foreach ($this->entity_manager->getRepository(Citizen::class)->findAll() as $citizen) {
                /** @var Citizen $citizen */
                foreach ($special_actions as $special_action)
                    /** @var SpecialActionPrototype $special_action */
                    $citizen->addSpecialAction( $special_action );
                $this->entity_manager->persist( $citizen );
            }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption("assign-town-season")) {
            $towns = $this->entity_manager->getRepository(TownRankingProxy::class)->findBy(['season' => null]);
            /* @var Season $latestSeason */
            $latestSeason = $this->entity_manager->getRepository(Season::class)->findLatest();
            foreach ($towns as $town) {
                /*  @var TownRankingProxy $town */
                $town->setSeason($latestSeason);
                $latestSeason->addRankedTown($town);
                $this->entity_manager->persist($town);
            }

            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('init-item-stacks')) {
            foreach ($this->entity_manager->getRepository(Item::class)->findAll() as $item) {
                /** @var $item Item */
                if ($item->getCount() == 0) {
                    $item->setCount( 1 );
                    $this->entity_manager->persist( $item );
                }
            }
            $this->entity_manager->flush();

            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $town) {
                /** @var $town Town*/
                foreach ($town->getBank()->getItems() as $item)
                    if ($item->getCount() <= 1) {
                        $target = $this->inventory_handler->findStackPrototype( $town->getBank(), $item );
                        if ($target) {
                            $target->setCount( $target->getCount() + 1);
                            $this->inventory_handler->forceRemoveItem( $item );
                            $this->entity_manager->persist($target);
                        }

                    }
            }

            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('calculate-score')) {
            $this->leChunk($output, TownRankingProxy::class, 5000, ['imported' => false], true, true, function(TownRankingProxy $town) {
                $score = 0;
                $latestDay = 0;
                foreach ($town->getCitizens() as $citizen) {
                    /* @var CitizenRankingProxy $citizen */
                    $score += $citizen->getDay();
                    if($latestDay < $citizen->getDay())
                        $latestDay = $citizen->getDay();
                }
                $town->setScore($score);
                $town->setDays($latestDay);
            });
            return 0;
        }

        if ($input->getOption('build-forum-search-index')) {
            $this->leChunk($output, Post::class, 100, ['translate' => false, 'searchForum' => null, 'searchText' => null], false, true, function(Post $post) {
                $post->setSearchText( strip_tags( $post->getText() ) );
                $post->setSearchForum( $post->getThread()->getForum() );
            });

            return 0;
        }

        if ($input->getOption('assign-awards')) {
            $this->leChunk($output, User::class, 100, [], true, true, function(User $user) {
                $this->user_handler->computePictoUnlocks($user);
            });

            return 0;
        }

        if ($input->getOption('migrate-account-bans')) {
            $this->leChunk($output, AdminBan::class, 100, [], false, false, function(AdminBan $ban): bool {
                $this->entity_manager->remove($ban);
                $this->entity_manager->persist( (new AccountRestriction())
                    ->setUser( $ban->getUser() )
                    ->setCreated( $ban->getBanStart() )
                    ->setExpires( $ban->getBanEnd() )
                    ->setOriginalDuration($ban->getBanEnd()->getTimestamp() - $ban->getBanStart()->getTimestamp() )
                    ->setRestriction( AccountRestriction::RestrictionSocial )
                    ->setPublicReason( $ban->getReason() )
                    ->setInternalReason("[migrated from deprecated AdminBan instance (#{$ban->getId()})]" . ($ban->getLifted() && $ban->getLiftUser() ? " [lifted by {$ban->getLiftUser()->getName()}]" : ""))
                    ->setModerator( $ban->getSourceUser() )
                    ->addConfirmedBy( $ban->getSourceUser() )
                    ->setActive( !$ban->getLifted() )
                    ->setConfirmed( true )
                );
                $ban->getUser()->getBannings()->removeElement($ban);
                $this->entity_manager->persist($ban->getUser());
                return false;
            });

            $this->leChunk($output, ShadowBan::class, 100, [], false, false, function(ShadowBan $ban): bool {
                $this->entity_manager->remove($ban);
                $this->entity_manager->persist( (new AccountRestriction())
                    ->setUser( $ban->getUser() )
                    ->setCreated( $ban->getCreated() )
                    ->setExpires( null )
                    ->setOriginalDuration(-1 )
                    ->setRestriction( AccountRestriction::RestrictionGameplay )
                    ->setPublicReason( $ban->getReason() )
                    ->setInternalReason("[migrated from deprecated ShadowBan instance (#{$ban->getId()})]")
                    ->setModerator( $ban->getAdmin() )
                    ->addConfirmedBy( $ban->getAdmin() )
                    ->setActive( true )
                    ->setConfirmed( true )
                );
                $ban->getUser()->setShadowBan(null);
                $this->entity_manager->persist($ban->getUser());
                return false;
            });

            return 0;
        }

        if ($input->getOption('update-ranking-entries')) {
            /** @var Town[] $towns */
            $towns = $this->entity_manager->getRepository(Town::class)->findAll();
            foreach ($towns as $town)
                $this->entity_manager->persist( TownRankingProxy::fromTown( $town, true ));
            $this->entity_manager->flush();
            $output->writeln('Towns updated!');

            /** @var Citizen[] $citizens */
            $citizens = $this->entity_manager->getRepository(Citizen::class)->findAll();
            foreach ($citizens as $citizen)
                $this->entity_manager->persist( CitizenRankingProxy::fromCitizen( $citizen, true ));
            $this->entity_manager->flush();
            $output->writeln('Citizens updated!');

            /** @var Picto[] $pictos */
            $pictos = $this->entity_manager->getRepository(Picto::class)->findAll();
            foreach ($pictos as $picto)
                if ($picto->getTownEntry() === null && $picto->getTown() && $picto->getTown()->getRankingEntry())
                    $this->entity_manager->persist( $picto->setTownEntry( $picto->getTown()->getRankingEntry() ) );
            $this->entity_manager->flush();
            $output->writeln('Pictos updated!');

            return 0;
        }

        if ($input->getOption('fix-ruin-inventories')) {
            $ruinZones = $this->entity_manager->getRepository(RuinZone::class)->findAll();
            foreach ($ruinZones as $ruinZone) {
                /** @var RuinZone $ruinZone */
                if ($ruinZone->getRoomFloor() === null) continue;

                foreach ($ruinZone->getRoomFloor()->getItems() as $item) {
                    for ($i = 0 ; $i < $item->getCount() ; $i++) {
                        $output->writeln("Moving item {$item->getPrototype()->getName()} into the Ruin's Floor");
                        $this->inventory_handler->forceMoveItem($ruinZone->getFloor(), $item);
                        $this->entity_manager->persist($ruinZone);
                        $this->entity_manager->persist($ruinZone->getFloor());
                        $this->entity_manager->persist($item);
                    }
                }
            }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('update-shaman-immune')) {
            /** @var Town[] $towns */
            $old_immune_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('tg_immune');
            $new_immune_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("tg_shaman_immune");

            if($old_immune_status === null){
                $output->writeln("Old tg_immune status has been already migrated !");
                return 0;
            }

            $citizens = $this->entity_manager->getRepository(Citizen::class)->findCitizensWithStatus($old_immune_status);

            $output->writeln(count($citizens) . " citizens to update");

            foreach ($citizens as $citizen) {
                $citizen->removeStatus($old_immune_status);
                $citizen->addStatus($new_immune_status);
                $this->entity_manager->persist($citizen);
            }

            $output->writeln('Citizens status updated!');

            $affectStatuses = $this->entity_manager->getRepository(AffectStatus::class)->findByResult($old_immune_status);

            $output->writeln(count($affectStatuses) . " AffectStatuses' results to update");

            foreach ($affectStatuses as $affectStatus) {
                $affectStatus->setResult($new_immune_status);
                $this->entity_manager->persist($affectStatus);
            }

            $output->writeln('AffectStatuses\' results updated!');

            $affectStatuses = $this->entity_manager->getRepository(AffectStatus::class)->findByInitial($old_immune_status);

            $output->writeln(count($affectStatuses) . " AffectStatuses' initial to update");

            foreach ($affectStatuses as $affectStatus) {
                $affectStatus->setInitial($new_immune_status);
                $this->entity_manager->persist($affectStatus);
            }

            $output->writeln('AffectStatuses\' initial updated!');

            $this->entity_manager->remove($old_immune_status);

            $output->writeln('Old tg_immune status removed!');

            $this->entity_manager->flush();

            return 0;
        }

        if ($input->getOption('place-explorables')) {

            $explorable_ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findBy( ['explorable' => true] );

            /** @var Town[] $towns */
            $towns = $this->entity_manager->getRepository(Town::class)->findAll();
            foreach ($towns as $town) {

                $output->writeln("Checking town <info>{$town->getId()}</info>");
                $n = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_NUM_EXPLORABLE_RUINS);

                $ex = 0;
                foreach ($town->getZones() as $zone)
                    if ($zone->getPrototype() && $zone->getPrototype()->getExplorable())
                        $ex++;

                $output->writeln("Town has <info>{$ex}</info> explorable ruins and needs to have <info>{$n}</info>");
                $changed = $n > $ex;

                while ($ex < $n) {
                    $explorable_ruins = $this->entity_manager->getRepository(ZonePrototype::class)->findBy( ['explorable' => true] );
                    shuffle($explorable_ruins);

                    /** @var ZonePrototype $spawning_ruin */
                    $spawning_ruin = array_shift($explorable_ruins);

                    $zone_list = array_filter($town->getZones()->getValues(), function(Zone $z) {return !$z->getPrototype() && ($z->getX() !== 0 || $z->getY() !== 0);});
                    shuffle($zone_list);

                    $spawn_zone = $this->randomizer->pickLocationBetweenFromList($zone_list, $spawning_ruin->getMinDistance(), $spawning_ruin->getMaxDistance());

                    if ($spawn_zone) {
                        $output->writeln("Spawning <info>{$spawning_ruin->getLabel()}</info> at <info>{$spawn_zone->getX()} / {$spawn_zone->getY()}</info>");
                        $spawn_zone->setPrototype($spawning_ruin);
                        $this->maze->createField( $spawn_zone );
                        $this->maze->generateMaze( $spawn_zone );
                    }

                    $ex++;

                }

                if ($changed) {
                    $this->entity_manager->persist($town);
                    $this->entity_manager->flush();
                }

            }

        }


        if ($input->getOption('repair-permissions')) {

            $fun_assoc = function (User $user, UserGroup $group) use ($output) {
                if (!$this->perm->userInGroup( $user, $group )) {
                    $output->writeln("Adding <info>{$user->getUsername()}</info> to group <info>{$group->getName()}</info>");
                    $this->perm->associate( $user, $group );
                }
            };

            $fun_dis_assoc = function (User $user, UserGroup $group) use ($output) {
                if ($this->perm->userInGroup( $user, $group )) {
                    $output->writeln("Removing <info>{$user->getUsername()}</info> from group <info>{$group->getName()}</info>");
                    $this->perm->disassociate( $user, $group );
                }
            };

            $fun_permissions = function( ?Forum $forum, UserGroup $group, int $grant = ForumUsagePermissions::PermissionReadWrite, int $deny = ForumUsagePermissions::PermissionNone) use ($output) {
                $po = $this->entity_manager->getRepository(ForumUsagePermissions::class)->findOneBy(['principalUser' => null, 'forum' => $forum, 'principalGroup' => $group]);
                if (!$po) {
                    if ($forum)
                        $output->writeln("Creating group <info>{$group->getName()}</info> permission object for forum <info>{$forum->getTitle()}</info>: <comment>[+{$grant} | -{$deny}]</comment>");
                    else $output->writeln("Creating group <info>{$group->getName()}</info> default permission object: <comment>[+{$grant} | -{$deny}]</comment>");
                    $this->entity_manager->persist( (new ForumUsagePermissions())->setForum($forum)->setPrincipalGroup($group)->setPermissionsGranted($grant)->setPermissionsDenied($deny) );
                } elseif ($po->getPermissionsGranted() !== $grant || $po->getPermissionsDenied() !== $deny) {
                    if ($forum)
                        $output->writeln("Resetting group <info>{$group->getName()}</info> permission object for forum <info>{$forum->getTitle()}</info>: <comment>[+{$grant} | -{$deny}]</comment>");
                    else $output->writeln("Resetting group <info>{$group->getName()}</info> default permission object: <comment>[+{$grant} | -{$deny}]</comment>");
                    $this->entity_manager->persist( $po->setPermissionsGranted($grant)->setPermissionsDenied($deny) );
                }
            };

            $g_users  = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultUserGroup]);
            $g_elev   = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultElevatedGroup]);
            $g_oracle = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultOracleGroup]);
            $g_mods   = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultModeratorGroup]);
            $g_admin  = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAdminGroup]);

            // Fix group associations
            $all_users = $this->entity_manager->getRepository(User::class)->findAll();
            foreach ($all_users as $current_user) {

                if ($current_user->getValidated()) $fun_assoc($current_user, $g_users); else $fun_dis_assoc($current_user, $g_users);
                if ($current_user->getRightsElevation() > User::ROLE_USER) $fun_assoc($current_user, $g_elev); else $fun_dis_assoc($current_user, $g_elev);
                if ($this->user_handler->hasRole($current_user, "ROLE_ORACLE")) $fun_assoc($current_user, $g_oracle); else $fun_dis_assoc($current_user, $g_oracle);
                if ($this->user_handler->hasRole($current_user, "ROLE_CROW"))   $fun_assoc($current_user, $g_mods); else $fun_dis_assoc($current_user, $g_mods);
                if ($this->user_handler->hasRole($current_user, "ROLE_ADMIN"))  $fun_assoc($current_user, $g_admin); else $fun_dis_assoc($current_user, $g_admin);

            }

            // Fix town groups
            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $current_town) {
                $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $current_town->getId()] );
                if (!$town_group) {
                    $output->writeln("Creating town group for <info>{$current_town->getName()}</info>");
                    $this->entity_manager->persist( $town_group = (new UserGroup())->setName("[town:{$current_town->getId()}]")->setType(UserGroup::GroupTownInhabitants)->setRef1($current_town->getId()) );
                }

                foreach ($all_users as $current_user) {
                    if ($current_user->getAliveCitizen() && $current_user->getAliveCitizen()->getTown() === $current_town)
                        $fun_assoc($current_user, $town_group);
                    else $fun_dis_assoc($current_user, $town_group);
                }
            }

            foreach ($this->entity_manager->getRepository(UserGroup::class)->findBy(['type' => UserGroup::GroupTownInhabitants]) as $town_group)
                if (!$this->entity_manager->getRepository(Town::class)->find( $town_group->getRef1() )) {
                    $output->writeln("Removing obsolete town group <info>{$town_group->getName()}</info>");
                    $this->entity_manager->remove($town_group);
                }

            $this->entity_manager->flush();

            // Fix permissions
            $fun_permissions(null, $g_oracle,  ForumUsagePermissions::PermissionFormattingOracle);
            $fun_permissions(null, $g_mods,  ForumUsagePermissions::PermissionModerate | ForumUsagePermissions::PermissionFormattingModerator | ForumUsagePermissions::PermissionPostAsCrow);
            $fun_permissions(null, $g_admin, ForumUsagePermissions::PermissionOwn);

            foreach ($this->entity_manager->getRepository(Forum::class)->findAll() as $forum) {

                if ($forum->getTown())
                    $fun_permissions($forum, $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $forum->getTown()->getId()] ));

                elseif ($forum->getType() === Forum::ForumTypeDefault || $forum->getType() === null) $fun_permissions($forum, $g_users);
                elseif ($forum->getType() === Forum::ForumTypeElevated) $fun_permissions($forum, $g_elev);
                elseif ($forum->getType() === Forum::ForumTypeMods) $fun_permissions($forum, $g_mods);
                elseif ($forum->getType() === Forum::ForumTypeAdmins) $fun_permissions($forum, $g_admin);

            }

            $this->entity_manager->flush();
            return 0;

        }

        return 1;
    }
}
