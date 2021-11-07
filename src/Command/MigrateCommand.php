<?php


namespace App\Command;


use App\Entity\AccountRestriction;
use App\Entity\AdminBan;
use App\Entity\AffectStatus;
use App\Entity\Building;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenStatus;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\FoundRolePlayText;
use App\Entity\GitVersions;
use App\Entity\HeroicActionPrototype;
use App\Entity\Item;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
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
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\Globals\TranslationConfigGlobal;
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
    private CommandHelper $helper;

    private TranslationConfigGlobal $conf_trans;

    protected static $git_script_repository = [
        'ce5c1810ee2bde2c10cc694e80955b110bbed010' => [ ['app:migrate', ['--calculate-score' => true] ] ],
        'e3a28a35e8ade5c767480bb3d8b7fa6daaf69f4e' => [ ['app:migrate', ['--build-forum-search-index' => true] ] ],
        //'d9960996e6d39ecc6431ef576470a048e4b43774' => [ ['app:migrate', ['--migrate-account-bans' => true] ] ],
        'e8fcdebaee7f62d2a74dfdaa1f352d7cbbdeb848' => [ ['app:migrate', ['--assign-awards' => true] ] ],
        'd2e74544059a70b72cb89784544555663e4f0f9e' => [ ['app:migrate', ['--assign-features' => true] ] ],
        '982adb8ebb6f71be8acd2550fc42a8594264ece3' => [ ['app:migrate', ['--count-admin-reports' => true] ] ],
        '1a5f0dbc64f5c185e023d3c655014f59f8c8059d' => [ ['app:migrate', ['--repair-restrictions' => true] ] ],
        'af5ba720e3656e5d6603a43074c3e131ee3debb7' => [ ['app:migrate', ['--set-icu-pref' => true] ] ],
        '513ee3a0646478cf5d9bf363a47f2da56fa0cdca' => [
            ['app:migrate', ['--repair-proxies' => true] ],
            ['app:migrate', ['--update-all-sp' => true] ],
        ],
        '4cf8df846bc5bb6391660cf77401a93c171226f2' => [ ['app:migrate', ['--migrate-oracles' => true] ] ],
        '2ce7f8222a95468ce4bf7b74cda62ef2c026307d' => [
            ['app:debug', ['--add-animactor' => true] ],
            ['app:migrate', ['--repair-permissions' => true] ],
        ],
        'e01e6dea153f67d9a1d7f9f7f7d3c8b2eec5d5ed' => [ ['app:migrate', ['--repair-permissions' => true] ] ],
        'fae118acfc0041183dac9622c142cab01fb10d44' => [ ['app:migrate', ['--fix-forum-posts' => true] ] ],
        'bf6a46f2dc1451658809f55578debd83aca095d3' => [ ['app:migrate', ['--set-old-flag' => true] ] ],
        'f413fb8e0bf8b4f9733b4e00705625e000ff4bf6' => [ ['app:migrate', ['--update-all-sp' => true] ] ],
        'a7fa0be81f35415ca3c2fc5810bdc53acd6331cc' => [ ['app:migrate', ['--prune-rp-texts' => true] ] ],
        '8e7d00f016648b180840f47544d98d337e5c0088' => [ ['app:migrate', ['--fix-ranking-survived-days' => true] ] ],
    ];

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em,
                                RandomGenerator $rg, CitizenHandler $ch, InventoryHandler $ih, ConfMaster $conf,
                                MazeMaker $maze, ParameterBagInterface $params, UserHandler $uh, PermissionHandler $p,
                                UserFactory $uf, TranslationConfigGlobal $conf_trans, CommandHelper $helper)
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

        $this->conf_trans = $conf_trans;

        $this->helper = $helper;

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
            ->addOption("php-bin", null, InputOption::VALUE_OPTIONAL, 'Sets the PHP binary to use')
            ->addOption('skip-backup', null,InputOption::VALUE_NONE, 'If set, no database backup will be created')
            ->addOption('stay-offline', null,InputOption::VALUE_NONE, 'If set, maintenance mode will be kept active after the update')
            ->addOption('release', null,InputOption::VALUE_NONE, 'If set, suppresses commit info from the version string')
            ->addOption('as', null,InputOption::VALUE_REQUIRED, 'If the update is started by the root user, this option specifies the web server user (defaults to www-data)')
            ->addOption('in', null,InputOption::VALUE_REQUIRED, 'If the update is started by the root user, this option specifies the web server group (defaults to www-data)')
            ->addOption('dbservice', null,InputOption::VALUE_REQUIRED, 'If the update is started by the root user, this option specifies the database service name (defaults to mysql)')

            ->addOption('install-db', 'i', InputOption::VALUE_NONE, 'Creates and performs the creation of the database and fixtures.')
            ->addOption('update-db', 'u', InputOption::VALUE_NONE, 'Creates and performs a doctrine migration, updates fixtures.')
            ->addOption('recover', 'r',   InputOption::VALUE_NONE, 'When used together with --update-db, will clear all previous migrations and try again after an error.')
            ->addOption('process-db-git', 'p',   InputOption::VALUE_NONE, 'Processes triggers for automated database actions')

            ->addOption('update-trans', 't', InputOption::VALUE_REQUIRED, 'Updates all translation files for a single language')
            ->addOption('trans-file', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Limits translations to specific files', [])
            ->addOption('trans-disable-php', null, InputOption::VALUE_NONE, 'Disables translation of PHP files')
            ->addOption('trans-disable-db', null, InputOption::VALUE_NONE, 'Disables translation of database content')
            ->addOption('trans-disable-twig', null, InputOption::VALUE_NONE, 'Disables translation of twig files')

            ->addOption('calculate-score', null, InputOption::VALUE_NONE, 'Recalculate the score for each ended town')
            ->addOption('build-forum-search-index', null, InputOption::VALUE_NONE, 'Initializes search structures for the forum')

            ->addOption('repair-proxies', null, InputOption::VALUE_NONE, 'Repairs incomplete CitizenRankingProxie entities.')
            ->addOption('place-explorables', null, InputOption::VALUE_NONE, 'Adds explorable ruins to all towns')
            ->addOption('assign-awards', null, InputOption::VALUE_NONE, 'Assign awards to users')
            ->addOption('assign-features', null, InputOption::VALUE_NONE, 'Assign features')
            ->addOption('update-all-sp', null, InputOption::VALUE_NONE, 'Update all soul points')
            ->addOption('fix-forum-posts', null, InputOption::VALUE_NONE, 'Fix forum post content')
            ->addOption('fix-ranking-survived-days', null, InputOption::VALUE_NONE, 'Fix survived day count in rankings')

            ->addOption('repair-permissions', null, InputOption::VALUE_NONE, 'Makes sure forum permissions and user groups are set up properly')
            ->addOption('migrate-oracles', null, InputOption::VALUE_NONE, 'Moves the Oracle role from account elevation to the special permissions flag')
            ->addOption('repair-restrictions', null, InputOption::VALUE_NONE, '')
            ->addOption('count-admin-reports', null, InputOption::VALUE_NONE, '')
            ->addOption('set-icu-pref', null, InputOption::VALUE_NONE, '')

            ->addOption('set-old-flag', null, InputOption::VALUE_NONE, 'Sets the MH-OLD flag on Pictos')

            ->addOption('prune-rp-texts', null, InputOption::VALUE_NONE, 'Makes sure the amount of unlocked RP texts matches the picto count')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $null = null;
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
            $php    = $input->getOption("php-bin") ?? 'php';
            $dbservice = $input->getOption("dbservice") ?? 'mysql';
            $dbprocess = $dbservice === 'mysql' ? 'mysqld' : $dbservice;


            $running_as = trim($this->helper->bin( 'whoami', $ret )[0]);
            $running_as_root = $running_as === 'root';

            $as = null;
            if ($running_as_root) {
                $output->writeln("<info>We have root permissions, <comment>running in advanced mode</comment>!</info>");
                $as[0] = $input->getOption('as') ?? 'www-data';
                $as[1] = $input->getOption('in') ?? 'www-data';
            } else $output->writeln("<info>Hello $running_as!</info>");

            if (!$this->helper->capsule( "app:migrate --maintenance on", $output, 'Enable maintenance mode... ', true, $null, $php, false, 0, $as )) return -1;

            for ($i = 3; $i > 0; --$i) {
                $output->writeln("Beginning update in <info>{$i}</info> seconds....");
                sleep(1);
            }

            if (!$input->getOption('skip-backup')) {
                if (!$this->helper->capsule('app:cron backup update', $output, 'Creating database backup before upgrading... ', true, $null, $php, false, 0, $as))
                    return 100;
            } else $output->writeln("Skipping <info>database backup</info>.");

            if (!$this->helper->capsule( "git fetch --tags {$remote} {$branch}", $output, 'Retrieving updates from repository... ', false, $null, $php, false, 0, $as )) return 1;
            if (!$this->helper->capsule( "git reset --hard {$remote}/{$branch}", $output, 'Applying changes to filesystem... ', false, $null, $php, false, 0, $as )) return 2;

            if (!$input->getOption('fast')) {
                if ($env === 'dev') {
                    if (!$this->helper->capsule( ($input->getOption('phar') ? "$php composer.phar" : 'composer') . " install", $output, 'Updating composer dependencies...', false, $null, $php, false, 0, $as )) return 3;
                } else if (!$this->helper->capsule( ($input->getOption('phar') ? "$php composer.phar" : 'composer') . " install --no-dev --optimize-autoloader", $output, 'Updating composer production dependencies... ', false, $null, $php, false, 0, $as )) return 4;
                if (!$this->helper->capsule( "yarn install", $output, 'Updating yarn dependencies... ', false, $null, $php, false, 0, $as )) return 5;
            } else $output->writeln("Skipping <info>dependency updates</info>.");

            if (!$input->getOption('fast')) {
                $killed = false;
                if ($running_as_root) {
                    $this->entity_manager->close();

                    $mysqlpid =  (int)trim($this->helper->bin( "pidof $dbprocess", $ret )[0]);
                    if ($mysqlpid > 0) {
                        if (!$this->helper->capsule( "sudo kill -15 $mysqlpid", $output, "Stopping database service (<info>$mysqlpid</info>)... ", false, $null, $php, true, 3 )) return 6;
                        sleep(5);
                        $killed = true;
                    }
                }
                if (!$this->helper->capsule( "yarn encore {$env}", $output, 'Building web assets... ', false, $null, $php, false, 3, $as )) return 6;
                if ($running_as_root && $killed) {
                    if (!$this->helper->capsule( "service $dbservice start", $output, 'Building web assets... ', false, $null, $php, true, 3 )) return 6;
                }
            } else $output->writeln("Skipping <info>web asset updates</info>.");

            $version_lines = $this->helper->bin( 'git describe --tags' . ($input->getOption('release') ? ' --abbrev=0' : ''), $ret );
            if (count($version_lines) >= 1) file_put_contents( 'VERSION', $version_lines[0] );

            if (!$this->helper->capsule( "cache:clear", $output, 'Clearing cache... ', true, $null, $php, false, 0, $as )) return 7;
            if (!$this->helper->capsule( "app:migrate -u -r", $output, 'Updating database... ', true, $null, $php, false, 0, $as )) return 8;
            if (!$this->helper->capsule( "app:migrate -p -v", $output, 'Running post-installation scripts... ', true, $null, $php, true, 0, $as )) return 9;

            if (count($version_lines) >= 1) $output->writeln("Updated MyHordes to version <info>{$version_lines[0]}</info>");

            if (!$input->getOption('stay-offline')) {
                for ($i = 3; $i > 0; --$i) {
                    $output->writeln("Disabling maintenance mode in <info>{$i}</info> seconds....");
                    sleep(1);
                }
                if (!$this->helper->capsule( "app:migrate --maintenance off", $output, 'Disable maintenance mode... ', true, $null, $php, false, 0, $as )) return -1;
            } else $output->writeln("Maintenance is kept active. Disable with '<info>app:migrate --maintenance off</info>'");

            return 0;
        }

        if ($input->getOption('install-db')) {

            $output->writeln("\n\n=== <info>Creating database and loading static content</info> ===\n");

            if (!$this->helper->capsule( 'doctrine:database:create --if-not-exists', $output )) {
                $output->writeln("<error>Unable to create database.</error>");
                return 1;
            }

            if (!$this->helper->capsule( 'doctrine:schema:update --force', $output )) {
                $output->writeln("<error>Unable to create schema.</error>");
                return 2;
            }

            if (!$this->helper->capsule( 'doctrine:fixtures:load --append', $output )) {
                $output->writeln("<error>Unable to update fixtures.</error>");
                return 3;
            }

            $output->writeln("\n\n=== <info>Creating default user accounts and groups</info> ===\n");

            if (!$this->helper->capsule( 'app:migrate --repair-permissions', $output )) {
                $output->writeln("<error>Unable to generate default permission set.</error>");
                return 4;
            }

            if (!$this->helper->capsule( 'app:debug --add-crow', $output )) {
                $output->writeln("<error>Unable to add users and create crow.</error>");
                return 4;
            }

            if (!$this->helper->capsule( 'app:debug --add-animactor', $output )) {
                $output->writeln("<error>Unable to create animactor.</error>");
                return 4;
            }

            $output->writeln("\n\n=== <info>Optional setup steps</info> ===\n");

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create world forums? (Y/n) ", true
            ) );
            if ($result) {
                if (!$this->helper->capsule('app:forum:create "Weltforum" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->helper->capsule('app:forum:create "Forum Monde" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->helper->capsule('app:forum:create "World Forum" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
                if (!$this->helper->capsule('app:forum:create "Foro Mundial" 0 --icon bannerForumDiscuss', $output)) {
                    return 5;
                }
            }

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create a town? (Y/n) ", true
            ) );

            if ($result) {
                if (!$this->helper->capsule('app:town:create remote 40 en', $output)) {
                    $output->writeln("<error>Unable to create english town.</error>");
                    return 5;
                }
            }

            $result = $this->getHelper('question')->ask($input, $output, new ConfirmationQuestion(
                "Would you like to create an administrator account? (Y/n) ", true
            ) );
            if ($result) {
                $name = $this->getHelper('question')->ask($input, $output, new Question(
                    "Please enter the username (default: admin): ", 'admin'
                ) );
                $mail = $this->getHelper('question')->ask($input, $output, new Question(
                    "Please enter the e-mail address (default: admin@localhost): ", 'admin@localhost'
                ) );

                $proceed = false;
                while (!$proceed) {
                    $q = new Question( "Please enter the account password (default: admin): ", 'admin' );
                    $q->setHidden(true);
                    $password1 = $this->getHelper('question')->ask($input, $output, $q );

                    $q = new Question( "Please repeat the account password(default: admin): ", 'admin' );
                    $q->setHidden(true);
                    $password2 = $this->getHelper('question')->ask($input, $output, $q );

                    $proceed = $password1 === $password2;
                    if (!$proceed) $output->writeln('<error>The passwords did not match.</error> Please try again.');
                }

                $new_user = $this->user_factory->createUser( $name, $mail, $password1, true, $error );
                if ($error !== UserFactory::ErrorNone) return -$error;
                $new_user->setRightsElevation(User::USER_LEVEL_SUPER);
                $this->entity_manager->persist($new_user);
                $this->entity_manager->flush();

                $output->writeln('Your user account <info>has been created</info>.');
            }

            return 0;
        }

        if ($input->getOption('update-db')) {

            if (!$this->helper->capsule( 'doctrine:migrations:diff --allow-empty-diff --formatted --no-interaction', $output )) {
                $output->writeln("<error>Unable to create a migration.</error>");
                return 1;
            }

            if (!$this->helper->capsule( 'doctrine:migrations:migrate --all-or-nothing --allow-no-migration --no-interaction', $output )) {

                if ($input->getOption('recover')) {
                    $output->writeln("<warning>Unable to migrate database, attempting recovery.</warning>");

                    $source = "{$this->param->get('kernel.project_dir')}/src/Migrations";
                    foreach (scandir( $source ) as $file)
                        if ($file && $file[0] !== '.') {
                            $output->write("\tDeleting \"<comment>{$file}</comment>\"... ");
                            unlink( "$source/$file" );
                            $output->writeln('<info>Ok!</info>');
                        }

                    if (!$this->helper->capsule( 'doctrine:migrations:version --all --delete --no-interaction', $output )) {
                        $output->writeln("<error>Unable to clean migrations.</error>");
                        return 4;
                    }

                    if (!$this->helper->capsule( 'doctrine:migrations:diff --allow-empty-diff --formatted --no-interaction', $output )) {
                        $output->writeln("<error>Unable to create a migration.</error>");
                        return 1;
                    }

                    if (!$this->helper->capsule( 'doctrine:migrations:migrate --all-or-nothing --allow-no-migration --no-interaction', $output )) {
                        $output->writeln("<error>Unable to migrate database.</error>");
                        return 2;
                    }
                } else {
                    $output->writeln("<error>Unable to migrate database.</error>");
                    return 2;
                }


            }

            if (!$this->helper->capsule( 'doctrine:fixtures:load --append', $output )) {
                $output->writeln("<error>Unable to update fixtures.</error>");
                return 3;
            }

            return 0;
        }

        if ($input->getOption('process-db-git')) {

            $hashes = array_reverse( $this->helper->bin( 'git rev-list HEAD', $ret ) );
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

            /** @var string[] $uninstalled */
            $uninstalled = array_map(fn(GitVersions $g) => $g->getVersion(), $git_repo->findBy(['installed' => false], ['id' => 'ASC']));

            if (count($uninstalled) > 0) $output->writeln('Completing database setup for <info>' . count($uninstalled) . '</info> patches.');
            else $output->writeln('No patches marked for installation.');

            foreach ($uninstalled as $version) {
                if (isset(static::$git_script_repository[$version])) {
                    $this->entity_manager->flush();
                    $output->writeln("\tInstalling <comment>{$version}</comment>...");
                    foreach (static::$git_script_repository[$version] as $script) {

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
                }

                $version_object = $this->entity_manager->getRepository(GitVersions::class)->findOneBy(['version' => $version]);
                $this->entity_manager->persist( $version_object->setInstalled(true) );
                $this->entity_manager->flush();
            }

            $this->entity_manager->flush();

            return 0;
        }

        if ($lang = $input->getOption('update-trans')) {

            $langs = ($lang === 'all') ? ['en','fr','es','de'] : [$lang];
            if (count($langs) === 1) {

                if ($input->getOption('trans-disable-db')) $this->conf_trans->setDatabaseSearch(false);
                if ($input->getOption('trans-disable-php')) $this->conf_trans->setPHPSearch(false);
                if ($input->getOption('trans-disable-twig')) $this->conf_trans->setTwigSearch(false);
                foreach ($input->getOption('trans-file') as $file_name)
                    $this->conf_trans->addMatchedFileName($file_name);

                $command = $this->getApplication()->find('translation:update');

                $output->writeln("Now working on translations for <info>{$lang}</info>...");
                $input = new ArrayInput([
                    'locale' => $lang,
                    '--force' => true,
                    '--sort' => 'asc',
                    '--format' => 'xlf',
                    '--prefix' => '',
                ]);
                $input->setInteractive(false);
                try {
                    $command->run($input, $output);
                } catch (Exception $e) {
                    $output->writeln("Error: <error>{$e->getMessage()}</error>");
                    return 1;
                }

            } elseif (extension_loaded('pthreads')) {
                $output->writeln("Using pthreads !");
                $threads = [];
                foreach ($langs as $current_lang) {

                    $com = "app:migrate -t $current_lang";
                    if ($input->getOption('trans-disable-db')) $com .= " --trans-disable-db";
                    if ($input->getOption('trans-disable-php')) $com .= " --trans-disable-php";
                    if ($input->getOption('trans-disable-twig')) $com .= " --trans-disable-twig";
                    foreach ($input->getOption('trans-file') as $file_name)
                        $com .= " --trans-file $file_name";

                    $threads[] = new class(function () use ($com,$output) {
                        $this->helper->capsule($com, $output);
                    }) extends \Worker {
                        private $_f;
                        public function __construct(callable $fun) { $this->_f = $fun; }
                        public function run() { ($this->_f)(); }
                    };
                }

                foreach ($threads as $thread) $thread->start();
                foreach ($threads as $thread) $thread->join();
            } else foreach ($langs as $current_lang) {
                $com = "app:migrate -t $current_lang";
                if ($input->getOption('trans-disable-db')) $com .= " --trans-disable-db";
                if ($input->getOption('trans-disable-php')) $com .= " --trans-disable-php";
                if ($input->getOption('trans-disable-twig')) $com .= " --trans-disable-twig";
                foreach ($input->getOption('trans-file') as $file_name)
                    $com .= " --trans-file $file_name";

                $this->helper->capsule($com, $output);
            }


            return 0;
        }


        if ($input->getOption('calculate-score')) {
            $this->helper->leChunk($output, TownRankingProxy::class, 5000, ['imported' => false], true, true, function(TownRankingProxy $town) {
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
            $this->helper->leChunk($output, Post::class, 100, ['translate' => false, 'searchForum' => null, 'searchText' => null], false, true, function(Post $post) {
                $post->setSearchText( strip_tags( $post->getText() ) );
                $post->setSearchForum( $post->getThread()->getForum() );
            });

            return 0;
        }

        if ($input->getOption('assign-awards')) {
            $this->helper->leChunk($output, User::class, 200, [], true, true, function(User $user) {
                $this->user_handler->computePictoUnlocks($user);
            }, true);

            return 0;
        }

        if ($input->getOption('set-icu-pref')) {
            $this->helper->leChunk($output, User::class, 100, [], true, true, function(User $user) {
                if ($user->getPreferredPronoun() !== null && $user->getPreferredPronoun() !== User::PRONOUN_NONE)
                    $user->setUseICU(true);
            });

            return 0;
        }

        if ($input->getOption('count-admin-reports')) {
            $this->helper->leChunk($output, Post::class, 1000, [], true, true, function(Post $post) {
                $post->setReported( $post->getAdminReports(false)->count() );
            });

            return 0;
        }

        if ($input->getOption('repair-restrictions')) {
            $this->helper->leChunk($output, AccountRestriction::class, 1000, [], true, true, function(AccountRestriction $r) {
                if ($r->getRestriction() & (1 << 2)) $r->setRestriction( $r->getRestriction() | AccountRestriction::RestrictionBlackboard );
            });

            return 0;
        }

        if ($input->getOption('assign-features')) {

            $season = $this->entity_manager->getRepository(Season::class)->findLatest();

            $p_arma = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_armag_#00');
            $p_cont = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_ginfec_#00');

            $f_arma = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_arma']);
            $f_cont = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_wtns']);
            $f_cam = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_cam']);
            $f_alarm = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_alarm']);
            $f_glory = $this->entity_manager->getRepository(FeatureUnlockPrototype::class)->findOneBy(['name' => 'f_glory']);

            $this->helper->leChunk($output, User::class, 100, [], true, false, function(User $user) use ($season,$p_arma,$p_cont,$f_arma,$f_cont,$f_cam,$f_alarm,$f_glory) {
                if (!$this->user_handler->checkFeatureUnlock($user,$f_cont, false)) {
                    if ($this->entity_manager->getRepository(Picto::class)->count(['prototype' => $p_cont, 'user' => $user, 'persisted' => 2]))
                        $this->entity_manager->persist( (new FeatureUnlock())->setPrototype( $f_cont )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationNone ) );
                }

                if (!$this->user_handler->checkFeatureUnlock($user,$f_arma, false)) {
                    if ($this->entity_manager->getRepository(Picto::class)->count(['prototype' => $p_arma, 'user' => $user, 'persisted' => 2]))
                        $this->entity_manager->persist( (new FeatureUnlock())->setPrototype( $f_arma )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationNone ) );
                }

                if (!$this->user_handler->checkFeatureUnlock($user, $f_cam, false))
                    $this->entity_manager->persist( (new FeatureUnlock())->setPrototype( $f_cam )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationSeason)->setSeason( $season ) );
                if (!$this->user_handler->checkFeatureUnlock($user, $f_alarm, false))
                    $this->entity_manager->persist( (new FeatureUnlock())->setPrototype( $f_alarm )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationTownCount )->setTownCount( 1 ) );
                if (!$this->user_handler->checkFeatureUnlock($user, $f_glory, false))
                    $this->entity_manager->persist( (new FeatureUnlock())->setPrototype( $f_glory )->setUser( $user )->setExpirationMode( FeatureUnlock::FeatureExpirationTimestamp )->setTimestamp( (new \DateTime('now'))->modify('tomorrow+30days') ) );

                return false;
            });

            return 0;
        }

        if ($input->getOption('update-all-sp')) {
            $this->helper->leChunk($output, User::class, 100, [], true, false, function(User $user) use ( $output ): bool {
                $calculated_sp_mh = $this->user_handler->fetchSoulPoints($user,false);
                $calculated_sp_im = $this->user_handler->fetchImportedSoulPoints($user);

                $stored_sp_mh  = $user->getSoulPoints() ?? 0;
                $stored_sp_im  = $user->getImportedSoulPoints() ?? 0;

                if ($calculated_sp_mh !== $stored_sp_mh || $calculated_sp_im !== $stored_sp_im) {
                    $user->setSoulPoints( $calculated_sp_mh )->setImportedSoulPoints( $calculated_sp_im );
                    return true;
                } else return false;
            }, true);
        }

        if ($input->getOption('fix-forum-posts')) {
            $posts = $this->entity_manager->getRepository(Post::class)->findAll();
            foreach ($posts as $post) {
                if (!preg_match('/<div class="cref" x-id="([0-9]+)" x-ajax-href="(@[a-z0-9: ​]+)">/', $post->getText()))
                    continue;

                $text = $post->getText();
                while (preg_match('/<div class="cref" x-id="([0-9]+)" x-ajax-href="(@[a-z0-9: ​]+)">/', $text))
                    $text = preg_replace('/<div class="cref" x-id="([0-9]+)" x-ajax-href="(@[a-z0-9: ​]+)">/', "<div class=\"cref\" x-id=\"$1\" x-ajax-href=\"$2\" x-ajax-target=\"default\">", $text);

                $post->setText($text);
                $this->entity_manager->persist($post);
            }

            return 0;
        }

        //fix-ranking-survived-days
        if ($input->getOption('fix-ranking-survived-days')) {
            $this->helper->leChunk($output, TownRankingProxy::class, 100, ['imported' => false], true, false, function(TownRankingProxy $tp):bool {
                if ($tp->getTown() && $tp->getTown()->getAliveCitizenCount() > 0) return false;
                $this->game_factory->updateTownScore($tp);
                return true;
            });

            return 0;
        }

        if ($input->getOption('repair-proxies')) {
            $this->helper->leChunk($output, CitizenRankingProxy::class, 1000, [], true, false, function(CitizenRankingProxy $cp): bool {
                $b = false;
                if ($cp->getTown()->getImported() && ($cp->getImportID() !== $cp->getTown()->getBaseID() || $cp->getImportLang() !== $cp->getTown()->getLanguage()) ) {
                    $cp->setImportLang($cp->getTown()->getLanguage())->setImportID( $cp->getTown()->getBaseID() );
                    $b = true;
                }

                if ($cp->getTown()->getType() && $cp->getTown()->getType()->getName() === 'custom' && $cp->getPoints() > 0) {
                    $cp->setPoints(0);
                    $b = true;
                }

                return $b;
            });

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
            $g_anim   = $this->entity_manager->getRepository(UserGroup::class)->findOneBy(['type' => UserGroup::GroupTypeDefaultAnimactorGroup]);

            // Fix group associations
            $all_users = $this->entity_manager->getRepository(User::class)->findAll();
            foreach ($all_users as $current_user) {

                if ($current_user->getValidated()) $fun_assoc($current_user, $g_users); else $fun_dis_assoc($current_user, $g_users);
                if ($current_user->getRightsElevation() > User::USER_LEVEL_BASIC) $fun_assoc($current_user, $g_elev); else $fun_dis_assoc($current_user, $g_elev);
                if ($this->user_handler->hasRole($current_user, "ROLE_ORACLE")) $fun_assoc($current_user, $g_oracle); else $fun_dis_assoc($current_user, $g_oracle);
                if ($this->user_handler->hasRole($current_user, "ROLE_CROW"))   $fun_assoc($current_user, $g_mods); else $fun_dis_assoc($current_user, $g_mods);
                if ($this->user_handler->hasRole($current_user, "ROLE_ADMIN"))  $fun_assoc($current_user, $g_admin); else $fun_dis_assoc($current_user, $g_admin);
                if ($this->user_handler->hasRole($current_user, "ROLE_ANIMAC"))  $fun_assoc($current_user, $g_anim); else $fun_dis_assoc($current_user, $g_anim);

            }

            // Fix town groups
            foreach ($this->entity_manager->getRepository(Town::class)->findAll() as $current_town) {
                $town_group = $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $current_town->getId()] );
                if (!$town_group) {
                    $output->writeln("Creating town group for <info>{$current_town->getName()}</info>");
                    $this->entity_manager->persist( $town_group = (new UserGroup())->setName("[town:{$current_town->getId()}]")->setType(UserGroup::GroupTownInhabitants)->setRef1($current_town->getId()) );
                }

                foreach ($all_users as $current_user) {
                    $assoc = false; $allow = false;

                    if ($this->user_handler->hasRole( $current_user, 'ROLE_ANIMAC' )) $allow = true;
                    foreach ( $current_user->getCitizens()->filter(fn(Citizen $c) => $c->getAlive() ) as $alive_citizen )
                            if ($alive_citizen->getTown() === $current_town)
                                $assoc = true;

                    if ($assoc) $fun_assoc($current_user, $town_group);
                    elseif (!$allow) $fun_dis_assoc($current_user, $town_group);
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
            $fun_permissions(null, $g_anim,  ForumUsagePermissions::PermissionPostAsAnim | ForumUsagePermissions::PermissionFormattingOracle);
            $fun_permissions(null, $g_mods,  ForumUsagePermissions::PermissionModerate | ForumUsagePermissions::PermissionFormattingModerator | ForumUsagePermissions::PermissionPostAsCrow);
            $fun_permissions(null, $g_admin, ForumUsagePermissions::PermissionOwn);

            foreach ($this->entity_manager->getRepository(Forum::class)->findAll() as $forum) {

                if ($forum->getTown())
                    $fun_permissions($forum, $this->entity_manager->getRepository(UserGroup::class)->findOneBy( ['type' => UserGroup::GroupTownInhabitants, 'ref1' => $forum->getTown()->getId()] ));

                elseif ($forum->getType() === Forum::ForumTypeDefault || $forum->getType() === null) $fun_permissions($forum, $g_users);
                elseif ($forum->getType() === Forum::ForumTypeElevated) $fun_permissions($forum, $g_elev);
                elseif ($forum->getType() === Forum::ForumTypeMods) $fun_permissions($forum, $g_mods);
                elseif ($forum->getType() === Forum::ForumTypeAdmins) $fun_permissions($forum, $g_admin);
                elseif ($forum->getType() === Forum::ForumTypeAnimac) $fun_permissions($forum, $g_anim);

            }

            $this->entity_manager->flush();
            return 0;

        }

        if ($input->getOption('migrate-oracles')) {
            $this->helper->leChunk($output, User::class, 5000, ['rightsElevation' => 2], false, true, function(User $user) {
                $user->setRightsElevation(0)->addRoleFlag(User::USER_ROLE_ORACLE);
            });

            return 0;
        }

        if ($input->getOption('set-old-flag')) {
            $this->helper->leChunk($output, Picto::class, 1000, ['imported' => false], true, true, function(Picto $picto) {
                $picto->setOld($picto->getTownEntry() && !$picto->getTownEntry()->getImported() && $picto->getTownEntry()->getSeason() === null);
            }, true);

            return 0;
        }

        if ($input->getOption('prune-rp-texts')) {
            $target_picto = null;
            $this->helper->leChunk($output, User::class, 100, [], true, false, function(User $user) use (&$target_picto) {
                $imported = $this->entity_manager->getRepository(Picto::class)->createQueryBuilder('i')
                    ->select('SUM(i.count)')
                    ->andWhere('i.user = :user')->setParameter('user', $user)->andWhere('i.prototype = :rp')->setParameter('rp', $target_picto)
                    ->andWhere('i.persisted = 2')->andWhere('i.imported = true')->andWhere('i.disabled = false')->andWhere('i.old = false')
                    ->getQuery()->getSingleScalarResult() ?? 0;

                $earned = $this->entity_manager->getRepository(Picto::class)->createQueryBuilder('i')
                    ->select('SUM(i.count)')
                    ->andWhere('i.user = :user')->setParameter('user', $user)->andWhere('i.prototype = :rp')->setParameter('rp', $target_picto)
                    ->andWhere('i.persisted = 2')->andWhere('i.imported = false')->andWhere('i.disabled = false')->andWhere('i.old = false')
                    ->getQuery()->getSingleScalarResult() ?? 0;

                $imported_texts = $this->entity_manager->getRepository(FoundRolePlayText::class)->createQueryBuilder('r')
                    ->select('COUNT(r.id)')
                    ->andWhere('r.text IS NOT NULL')
                    ->andWhere('r.user = :user')->setParameter('user', $user)->andWhere('r.imported = true')
                    ->getQuery()->getSingleScalarResult() ?? 0;

                $earned_texts = $this->entity_manager->getRepository(FoundRolePlayText::class)->createQueryBuilder('r')
                    ->select('COUNT(r.id)')
                    ->andWhere('r.text IS NOT NULL')
                    ->andWhere('r.user = :user')->setParameter('user', $user)->andWhere('r.imported = false')
                    ->getQuery()->getSingleScalarResult() ?? 0;

                if (($imported < $imported_texts) || ($earned < $earned_texts)) {

                    if ($imported < $imported_texts) foreach ($this->entity_manager->getRepository(FoundRolePlayText::class)->findBy(['user' => $user, 'imported' => true], ['datefound' => 'DESC'], $imported_texts - $imported) as $to_remove)
                        $this->entity_manager->remove( $to_remove );

                    if ($earned < $earned_texts) foreach ($this->entity_manager->getRepository(FoundRolePlayText::class)->findBy(['user' => $user, 'imported' => false], ['datefound' => 'DESC'], $earned_texts - $earned) as $to_remove)
                        $this->entity_manager->remove( $to_remove );

                    return true;
                } else return false;
            }, true, function () use (&$target_picto) {
                $target_picto = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName('r_rp_#00');
            });

            return 0;
        }

        return 99;
    }
}
