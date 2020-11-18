<?php


namespace App\Command;


use App\Entity\AffectStatus;
use App\Entity\Building;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenStatus;
use App\Entity\Forum;
use App\Entity\ForumUsagePermissions;
use App\Entity\HeroicActionPrototype;
use App\Entity\Item;
use App\Entity\Picto;
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
use App\Service\UserHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    private $kernel;

    private $game_factory;
    private $entity_manager;
    private $citizen_handler;
    private $randomizer;
    private $inventory_handler;
    private $conf;
    private $maze;
    private $param;
    private $user_handler;
    private $perm;

    public function __construct(KernelInterface $kernel, GameFactory $gf, EntityManagerInterface $em,
                                RandomGenerator $rg, CitizenHandler $ch, InventoryHandler $ih, ConfMaster $conf,
                                MazeMaker $maze, ParameterBagInterface $params, UserHandler $uh, PermissionHandler $p)
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

            ->addOption('update-db', 'u', InputOption::VALUE_NONE, 'Creates and performs a doctrine migration, updates fixtures.')
            ->addOption('recover', 'r',   InputOption::VALUE_NONE, 'When used together with --update-db, will clear all previous migrations and try again after an error.')

            ->addOption('update-trans', 't', InputOption::VALUE_REQUIRED, 'Updates all translation files for a single language')

            ->addOption('assign-heroic-actions-all', null, InputOption::VALUE_NONE, 'Resets the heroic actions for all citizens in all towns.')
            ->addOption('init-item-stacks', null, InputOption::VALUE_NONE, 'Sets item count for items without a counter to 1')
            ->addOption('delete-legacy-logs', null, InputOption::VALUE_NONE, 'Deletes legacy log entries')

            ->addOption('set-default-zonetag', null, InputOption::VALUE_NONE, 'Set the default tag to all zones')
            ->addOption('assign-building-hp', null, InputOption::VALUE_NONE, 'Give HP to all buildings (so they can be attacked by zeds)')
            ->addOption('assign-building-defense', null, InputOption::VALUE_NONE, 'Give defense to all buildings (so they can be attacked by zeds)')
            ->addOption('update-ranking-entries', null, InputOption::VALUE_NONE, 'Update ranking values')
            ->addOption('update-shaman-immune', null, InputOption::VALUE_NONE, 'Changes status tg_immune to tg_shaman_immune')
            ->addOption('place-explorables', null, InputOption::VALUE_NONE, 'Adds explorable ruins to all towns')

            ->addOption('repair-permissions', null, InputOption::VALUE_NONE, 'Makes sure forum permissions and user groups are set up properly')
            ->addOption('repair-causesofdeath', null, InputOption::VALUE_NONE, 'Change the cause of deaths number to be like Hordes\' one')
        ;
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
            if (count($version_lines) >= 1) {
                file_put_contents( 'VERSION', $version_lines[0] );
                $output->writeln("Updated MyHordes to version <info>{$version_lines[0]}</info>");
            }

            if (!$this->capsule( "cache:clear", $output, 'Clearing cache... ', true )) return 7;
            if (!$this->capsule( "app:migrate -u -r", $output, 'Updating database... ', true )) return 8;

            if (!$input->getOption('stay-offline')) {
                for ($i = 3; $i > 0; --$i) {
                    $output->writeln("Disabling maintenance mode in <info>{$i}</info> seconds....");
                    sleep(1);
                }
                if (!$this->capsule( "app:migrate --maintenance off", $output, 'Disable maintenance mode... ', true )) return -1;
            } else $output->writeln("Maintenance is kept active. Disable with '<info>app:migrate --maintenance off</info>'");

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

        if ($input->getOption('delete-legacy-logs')) {
            /** @var TownLogEntry[] $log_entries */
            $log_entries = $this->entity_manager->getRepository(TownLogEntry::class)->findAll();
            foreach ($log_entries as $entry)
                if ($entry->getLogEntryTemplate() === null)
                $this->entity_manager->remove( $entry );
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('set-default-zonetag')) {
            /** @var Zone[] $zones */
            $zones = $this->entity_manager->getRepository(Zone::class)->findAll();
            $defaultTag = $this->entity_manager->getRepository(ZoneTag::class)->findOneByRef(0);
            foreach ($zones as $entry)
                if ($entry->getTag() === null){
                    $entry->setTag($defaultTag);
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('assign-building-hp')) {
            /** @var Building[] $buildings */
            $building = $this->entity_manager->getRepository(Building::class)->findAll();
            foreach ($building as $entry)
                if ($entry->getComplete()){
                    $entry->setHp($entry->getPrototype()->getHp());
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

            return 0;
        }

        if ($input->getOption('assign-building-defense')) {
            /** @var Building[] $buildings */
            $building = $this->entity_manager->getRepository(Building::class)->findAll();
            foreach ($building as $entry)
                if ($entry->getComplete()){
                    $entry->setDefense($entry->getPrototype()->getDefense());
                    $this->entity_manager->persist($entry);
                }
            $this->entity_manager->flush();
            $output->writeln('OK!');

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

        if ($input->getOption('update-shaman-immune')) {
            /** @var Town[] $towns */
            $old_immune_status = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName("tg_immune");
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

        }

        if ($input->getOption('repair-causesofdeath')) {
            $mappingRefs = array (
                1 => 10,
                2 => 6,
                3 => 5,
                4 => 1,
                5 => 14,
                6 => 7,
                7 => 8,
                8 => 3,
                9 => 11,
                10 => 12,
                11 => 13,
                12 => 4,
                13 => 15,
                14 => 2,
                15 => 9,
                16 => 19,
                17 => 18,
                18 => 17,
                19 => 16,
            );
            $deadCitizens = $this->entity_manager->getRepository(Citizen::class)->findBy(['alive' => 0]);
            foreach($deadCitizens as $deadCitizen){
                /** @var Citizen $deadCitizen */
                $deadCitizen->setCauseOfDeath($this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => $mappingRefs[$deadCitizen->getCauseOfDeath()->getRef()]]));
                $this->entity_manager->persist($deadCitizen);
            }

            $deadCitizens = $this->entity_manager->getRepository(CitizenRankingProxy::class)->findAll();
            foreach($deadCitizens as $deadCitizen){
                /** @var CitizenRankingProxy $deadCitizen */
                if($deadCitizen->getCod() === null) continue;
                $deadCitizen->setCod($this->entity_manager->getRepository(CauseOfDeath::class)->findOneBy(['ref' => $mappingRefs[$deadCitizen->getCod()->getRef()]]));
                $this->entity_manager->persist($deadCitizen);
            }

            $this->entity_manager->flush();
        }

        return 1;
    }
}
