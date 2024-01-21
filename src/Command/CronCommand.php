<?php


namespace App\Command;


use App\Entity\Announcement;
use App\Entity\AttackSchedule;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\EventAnnouncementMarker;
use App\Entity\HeaderStat;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Statistic;
use App\Entity\Town;
use App\Entity\User;
use App\Enum\Configuration\TownSetting;
use App\Enum\StatisticType;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\AdminHandler;
use App\Service\AntiCheatService;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\GameEventService;
use App\Service\GameFactory;
use App\Service\GazetteService;
use App\Service\Locksmith;
use App\Service\NightlyHandler;
use App\Service\Statistics\UserStatCollectionService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\MyHordesConf;
use DateTime;
use DateTimeImmutable;
use DirectoryIterator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Twig\Environment;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

#[AsCommand(
    name: 'app:cron',
    description: 'Cron command'
)]
class CronCommand extends Command implements SelfSchedulingCommand
{
    private KernelInterface $kernel;
    private EntityManagerInterface $entityManager;
    private Locksmith $locksmith;
    private MyHordesConf $conf;
    private ConfMaster $conf_master;
    private AntiCheatService $anti_cheat;
    private UserHandler $userHandler;
    private CrowService $crowService;
    private CommandHelper $helper;
    private ParameterBagInterface $params;
    private Environment $twig;
    private AdminHandler $adminHandler;
    private UserStatCollectionService $userStats;

    private array $db;
    private InvalidateTagsInAllPoolsAction $clearCache;

    public function __construct(array $db, KernelInterface $kernel, Environment $twig,
                                EntityManagerInterface $em, Locksmith $ls,
                                ConfMaster $conf, AntiCheatService $acs, UserHandler $uh,
                                CrowService $cs, CommandHelper $helper, ParameterBagInterface $params,
                                AdminHandler $adminHandler, UserStatCollectionService $us,
                                InvalidateTagsInAllPoolsAction $clearCache)
    {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->entityManager = $em;
        $this->locksmith = $ls;
        $this->conf_master = $conf;
        $this->conf = $conf->getGlobalConf();
        $this->anti_cheat = $acs;
        $this->userHandler = $uh;
        $this->crowService = $cs;
        $this->helper = $helper;
        $this->params = $params;
        $this->adminHandler = $adminHandler;
        $this->userStats = $us;
        $this->clearCache = $clearCache;

        $this->db = $db;
        parent::__construct();
    }

    /**
     * @return void
     */
    public function updateHeaderStats(): void
    {
        $criteria = new Criteria();
        $criteria->andWhere($criteria->expr()->neq('end', null));
        $criteria->orWhere($criteria->expr()->neq('importID', 0));
        $deadCitizenCount = $this->entityManager->getRepository(CitizenRankingProxy::class)->matching($criteria)->count() + $this->entityManager->getRepository(Citizen::class)->count(['alive' => 0]);

        $pictoKillZombies = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_killz_#00']);
        $zombiesKilled = $this->entityManager->getRepository(Picto::class)->countPicto($pictoKillZombies);

        $pictoCanibal = $this->entityManager->getRepository(PictoPrototype::class)->findOneBy(['name' => 'r_cannib_#00']);
        $cannibalismCount = $this->entityManager->getRepository(Picto::class)->countPicto($pictoCanibal);

        $this->entityManager->persist( (new HeaderStat())
            ->setTimestamp( new DateTime() )
            ->setKilledCitizens( $deadCitizenCount )
            ->setKilledZombies( $zombiesKilled )
            ->setCannibalismActs($cannibalismCount)
        );
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This should be run on a regular basis.')

            ->addArgument('task',  InputArgument::OPTIONAL, 'The task to perform. Defaults to "host".', 'host')
            ->addArgument('p1',  InputArgument::OPTIONAL, 'Parameter 1', -1)
            ->addArgument('p2',  InputArgument::OPTIONAL, 'Parameter 2', -1)
            ;
    }

    protected function module_run_collect_stats(): bool {
        $missing_stat = false;
        foreach (StatisticType::playerStatTypes() as $playerStatType) {

            $missing_stat |= $needed = $this->entityManager->getRepository(Statistic::class)->matching(
                (new Criteria())->where(
                    Criteria::expr()->gte( 'created', new DateTime('today') )
                )->andWhere(
                    Criteria::expr()->eq( 'type', $playerStatType )
                )
            )->isEmpty();

            if ($needed)
                $this->entityManager->persist((new Statistic())
                    ->setType($playerStatType)
                    ->setCreated(new DateTime('now'))
                    ->setPayload($this->userStats->collectData($playerStatType->cutoffDate(), ['de', 'en', 'es', 'fr']))
                );
        }

        if ($missing_stat) $this->entityManager->flush();

        return $missing_stat;
    }

    protected function module_run_backups(OutputInterface $output): ?string {

        $last_backups = ['any' => (new DateTime())->setTimestamp(0), 'daily' => (new DateTime())->setTimestamp(0), 'weekly' => (new DateTime())->setTimestamp(0), 'monthly' => (new DateTime())->setTimestamp(0)];

        $path = $this->conf->get(MyHordesConf::CONF_BACKUP_PATH, null);
        if ($path === null) $path = "{$this->params->get('kernel.project_dir')}/var/backup";
        if (file_exists($path))
            foreach (new DirectoryIterator($path) as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isDot() || $fileInfo->isLink()) continue;
                elseif ($fileInfo->isFile() && in_array(strtolower($fileInfo->getExtension()), ['sql','xz','gzip','bz2'])) {
                    $segments = explode('_', explode('.',$fileInfo->getFilename())[0]);
                    if (count($segments) !== 3 || !in_array($segments[2], ['daily','weekly','monthly']))
                        continue;

                    $date = date_create_from_format( 'Y-m-d', $segments[0] );
                    if ($date === false) continue;

                    if ($last_backups[$segments[2]] < $date) $last_backups[$segments[2]] = $date;
                    if ($last_backups['any'] < $date) $last_backups['any'] = $date;
                }
            }

        if ($last_backups['any'] >= new DateTime('today')) return null;

        if ($last_backups['monthly'] <= new DateTime('today-1month')) {
            $this->helper->capsule('app:cron backup monthly', $output, 'Creating monthly backup... ', true);
            return 'monthly';
        } elseif ($last_backups['weekly'] <=  new DateTime('today-1week')) {
            $this->helper->capsule('app:cron backup weekly', $output, 'Creating weekly backup... ', true);
            return 'weekly';
        } else {
            $this->helper->capsule('app:cron backup daily', $output, 'Creating daily backup... ', true);
            return 'daily';
        }

    }

    protected function module_run_attacks(OutputInterface $output): bool {

        $s = $this->entityManager->getRepository(AttackSchedule::class)->findNextUncompleted();
        if ($s && $s->getTimestamp() < new DateTime('now')) {

            $this->helper->capsule('app:cron backup nightly', $output, 'Creating database backup before the attack... ', true);

            $try_limit = $this->conf->get(MyHordesConf::CONF_NIGHTLY_RETRIES, 3);
            $schedule_id = $s->getId();

            $this->entityManager->persist(
                $s->setStartedAt( new DateTimeImmutable('now') )
            );
            $this->entityManager->flush();

            $town_ids = array_column($this->entityManager->createQueryBuilder()
                ->select('t.id')
                ->from(Town::class, 't')
                ->andWhere('(t.lastAttack != :last OR t.lastAttack IS NULL)')->setParameter('last', $s->getId())
                ->andWhere('t.attackFails < :trylimit')->setParameter('trylimit', $try_limit)
                ->andWhere('t.scheduledFor IS NULL OR t.scheduledFor < :now')->setParameter('now', new DateTime())
                ->getQuery()
                ->getScalarResult(), 'id');

            $this->entityManager->clear();

            $quarantined_towns = 0;

            $i = 1; $num = count($town_ids);
            foreach ( $town_ids as $town_id ) {

                $failures = [];
                while (count($failures) < $try_limit && !$this->helper->capsule("app:town:attack $town_id $schedule_id", $output, "Processing town <info>{$town_id}</info> <comment>($i/$num)</comment>... ", true, $ret))
                    $failures[] = $ret;

                $i++;

                if (!empty($failures)) {
                    // If we exceed the number of allowed processing tries, quarantine the town
                    if (count($failures) >= $try_limit) {
                        $quarantined_towns++;

                        /** @var Town $town */
                        $town = $this->entityManager->getRepository(Town::class)->find($town_id);
                        $town->setAttackFails( count($failures) );
                        foreach ($town->getCitizens() as $citizen) if ($citizen->getAlive())
                            $this->entityManager->persist(
                                $this->crowService->createPM_townQuarantine( $citizen->getUser(), $town->getName(), true )
                            );
                        $this->entityManager->persist($town);
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                    }

                }

            }

            $s = $this->entityManager->getRepository(AttackSchedule::class)->find($schedule_id);
            $this->entityManager->persist(
                $s->setCompleted(true)->setCompletedAt( new DateTimeImmutable('now') )->setFailures($quarantined_towns)
            );

            $this->updateHeaderStats();
            try {
                ($this->clearCache)('daily');
            } catch (\Throwable $e) {}


            $datemod = $this->conf->get(MyHordesConf::CONF_NIGHTLY_DATEMOD, 'tomorrow');
            if ($datemod !== 'never') {

                $new_date = (new DateTime())->setTimestamp( $s->getTimestamp()->getTimestamp() )->modify($datemod);
                if ($new_date !== false && $new_date > $s->getTimestamp())
                    $this->entityManager->persist( (new AttackSchedule())->setTimestamp( DateTimeImmutable::createFromMutable($new_date) ) );

            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            return true;
        }

        return false;
    }

    protected function module_run_clear(): bool {
        // Delete old connection identifiers
        $this->anti_cheat->cleanseConnectionIdentifiers();

        // Delete users marked for removal
        foreach ($this->entityManager->getRepository(User::class)->findNeedToBeDeleted() as $delete_user)
            $this->userHandler->deleteUser($delete_user);

        $this->entityManager->flush();
        return true;
    }

    protected function module_event_notifications(): bool {
        $now = new DateTime('now');
        $span_end =  (new DateTime('now'))->modify('+8days');
        $schedule = array_filter($this->conf_master->getAllScheduledEvents(
            $now,
            (new DateTime('now'))->modify('+1min')
        ), function($s) use ($now, $span_end) {
            [, $date, $beginning] = $s;
            return $beginning && $date > $now && $date < $span_end;
        });

        if (empty($schedule)) return false;
        $the_crow = $this->entityManager->getRepository(User::class)->find(66);

        if (!$the_crow) return false;

        foreach ($schedule as $entry) {

            $marker = $this->entityManager->getRepository( EventAnnouncementMarker::class )->findOneBy(['name' => $entry[0], 'identifier' => $entry[1]->getTimestamp()]);
            if ($marker || !file_exists( "{$this->kernel->getProjectDir()}/templates/event/{$entry[0]}" )) continue;

            /**
             * @var DateTime $begin
             * @var DateTime $end
             */
            if ($this->conf_master->getEventScheduleByName( $entry[0], $now, $begin, $end )) continue;
            if ($begin === null || $end === null) continue;



            $lang_fallback_path = ['en','fr','de','es'];
            $lang_mapping = [];
            $langs = array_map(function($item) {return $item['code'];}, array_filter($this->conf->get(MyHordesConf::CONF_LANGS), function($item) {
                return $item['generate'];
            }));
            foreach ($langs as $lang)
                if (file_exists( "{$this->kernel->getProjectDir()}/templates/event/{$entry[0]}/$lang.html.twig" ))
                    $lang_mapping[$lang] = $lang;
                else foreach ($lang_fallback_path as $fallback)
                    if (file_exists( "{$this->kernel->getProjectDir()}/templates/event/{$entry[0]}/$fallback.html.twig" )) {
                        $lang_mapping[$lang] = $fallback;
                        break;
                    }

            $vars = [
                'year' => $begin->format('Y'),
                'date_begin' => $begin,
                'date_end' => $end
            ];

            foreach ( $lang_mapping as $lang => $mapping ) try {
                $template = $this->twig->load( "event/{$entry[0]}/$mapping.html.twig" );
                $announcement = (new Announcement())
                    ->setTitle( strip_tags( $template->renderBlock('title', $vars) ) )
                    ->setText( $template->renderBlock('content', $vars) )
                    ->setTimestamp( new DateTime() )
                    ->setLang( $lang )
                    ->setSender( $the_crow )
                    ->setValidated(true);

                $this->entityManager->persist($announcement);

            } catch(\Throwable $t) {echo $t->getMessage();}

            $this->entityManager->persist( (new EventAnnouncementMarker())
                ->setName( $entry[0] )
                ->setIdentifier( $entry[1]->getTimestamp() )
            );
        }

        $this->entityManager->flush();
        return true;
    }

    protected function task_host(InputInterface $input, OutputInterface $output): int {
        // Host task
        $output->writeln( "MyHordes CronJob Interface", OutputInterface::VERBOSITY_VERBOSE );

        $backup_ran = $this->module_run_backups($output);
        $output->writeln( "Backup scheduler: <info>" . ($backup_ran ?? 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $stats_ran = $this->module_run_collect_stats();
        $output->writeln( "Statistics scheduler: <info>" . ($stats_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $attacks_ran = $this->module_run_attacks($output);
        $output->writeln( "Attack scheduler: <info>" . ($attacks_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $clear_ran = $this->module_run_clear();
        $output->writeln( "Cleanup Crew: <info>" . ($clear_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $event_notifier_ran = $this->module_event_notifications();
        $output->writeln( "Event notifications: <info>" . ($event_notifier_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );


        return 0;
    }

    /**
     * @throws Exception
     */
    protected function task_backup(InputInterface $input, OutputInterface $output): int {
        // Backup task
        $output->writeln( "MyHordes CronJob - Backup Processor", OutputInterface::VERBOSITY_VERBOSE );

        list(
            'scheme' => $db_scheme, 'host' => $db_host, 'port' => $db_port,
            'user' => $db_user, 'pass' => $db_pass,
            'path' => $db_name ) = $this->db;

        if ($db_scheme !== 'mysql') throw new Exception('Sorry, only MySQL is supported for backup!');

        if (!isset($this->conf->getData()['backup']['storages']) || count($this->conf->getData()['backup']['storages']) == 0) throw new Exception('No backup storage is defined, cannot store DB backups');

        $storages = $this->conf->getData()['backup']['storages'];

        $domain = $input->getArgument('p1');

        if ($domain === -1) $domain = 'manual';
        if (!in_array($domain,['nightly','daily','weekly','monthly','update','manual']))
            throw new Exception('Invalid backup domain!');

        $path = $this->conf->get(MyHordesConf::CONF_BACKUP_PATH, null);

        if ($path === null) $path = "{$this->params->get('kernel.project_dir')}/var/tmp";
        $path = str_replace("~", $this->params->get('kernel.project_dir'), $path);

        if (!file_exists($path)) mkdir($path, 0700, true);
        $filename = $path . '/' . (new DateTime())->format('Y-m-d_H-i-s-v_') . $domain . '.sql';

        $compression = $this->conf->get(MyHordesConf::CONF_BACKUP_COMPRESSION, null);
        if ($compression === null) $str = "> $filename";
        elseif ($compression === 'xz') $str = "| xz > {$filename}.xz";
        elseif ($compression === 'gzip') $str = "| gzip > {$filename}.gz";
        elseif ($compression === 'bzip2') $str = "| bzip2 > {$filename}.bz2";
        elseif ($compression === 'lbzip2') $str = "| lbzip2 > {$filename}.bz2";
        elseif ($compression === 'pbzip2') $str = "| pbzip2 > {$filename}.bz2";
        else throw new Exception('Invalid compression!');

        $relevant_domain_limit = $this->conf->get(MyHordesConf::CONF_BACKUP_LIMITS_INC . $domain, -1);


        if ($relevant_domain_limit !== 0) {
            $output->writeln("Executing <info>mysqldump</info> on <info>$db_host:$db_port</info>, exporting <info>$db_name</info> <comment>$str</comment>", OutputInterface::VERBOSITY_VERBOSE );
            $this->helper->capsule("mysqldump -h $db_host -P $db_port --user='$db_user' --password='$db_pass' --single-transaction --skip-lock-tables $db_name $str", $output, 'Running database backup... ', false );
        } else {
            $output->writeln("Skipping <info>mysqldump</info> in domain <info>$domain</info> since backups for this domain are turned off.", OutputInterface::VERBOSITY_VERBOSE );
            return 0;
        }

        $success = true;

        // Putting created backup into the different storages
        foreach ($storages as $name => $config) {
            if(!$config['enabled']) continue;

            $output->writeln("Putting newly created backup into {$config['type']} storage '$name'", OutputInterface::VERBOSITY_VERBOSE);
            switch($config['type']) {
                case "local":
                    $targetPath = str_replace("~", $this->params->get('kernel.project_dir'), $config['path']);
                    if (!is_dir($targetPath))
                        if(mkdir($targetPath, 0700, true)) {
                            $output->writeln("Cannot create backup folder $targetPath !");
                            $success = false;
                            break;
                        }
                    $filename .= match ($compression) {
                        "xz" => ".xz",
                        "gzip" => ".gz",
                        "bzip2", "lbzip2", "pbzip2" => ".bz2",
                        null => ""
                    };

                    if(!copy($filename, $targetPath . "/" . basename($filename))) {
                        $output->writeln("Cannot put backup file " . basename($filename) . " into folder $targetPath !");
                        $success = false;
                    }
                    break;
                case "ftp":
                    $ftp_conn = $this->adminHandler->connectToFtp($config['host'], $config['port'], $config['user'], $config['pass'], $config['passive']);;
                    if (!$ftp_conn) {
                        $success = false;
                        $output->writeln("<error>Unable to connect to {$config['type']} storage '$name'</error>");
                        break;
                    }

                    if (!ftp_put($ftp_conn, $config['path'] . '/' .basename($filename), $filename, FTP_BINARY)) {
                        $success = false;
                        $output->writeln("<error>Unable to upload backup file  " . basename($filename) . " to {$config['type']} storage '$name'");
                    }

                    ftp_close($ftp_conn);
                    break;
                case "sftp":
                    $conn = $this->adminHandler->connectToSftp($config['host'], $config['port'], $config['user'], $config['pass']);
                    if(!$conn) {
                        $success = false;
                        $output->writeln("<error>Unable to connect to {$config['type']} storage '$name'</error>");
                        break;
                    }

                    if(!ssh2_scp_send($conn, $filename, $config['path'] . '/' .basename($filename))) {
                        $success = false;
                        $output->writeln("<error>Unable to upload backup file  " . basename($filename) . " to {$config['type']} storage '$name'");
                    }
                    ssh2_disconnect($conn);
                    break;
                default:
                    $output->writeln("<error>Unknown storage type {$config['type']}</error>");
                    break;
            }
        }

        if (!$success) {
            throw new Exception("An error has occured while putting the new backup file into a storage");
        }

        // Processing storages to ensure retention policies
        foreach ($storages as $name => $config) {
            if (!$config['enabled']) continue;

            $output->writeln("Ensuring retention policy on <info>{$config['type']}</info> storage <info>$name</info>", OutputInterface::VERBOSITY_VERBOSE);
            $backup_files = [];

            switch ($config['type']) {
                case "local":
                    $targetPath = str_replace("~", $this->params->get('kernel.project_dir'), $config['path']);
                    foreach (new DirectoryIterator($targetPath) as $fileInfo) {
                        /** @var SplFileInfo $fileInfo */
                        if ($fileInfo->isDot() || $fileInfo->isLink()) continue;

                        if ($fileInfo->isFile() && in_array(strtolower($fileInfo->getExtension()), ['sql', 'xz', 'gzip', 'bz2'])) {
                            $segments = explode('_', explode('.', $fileInfo->getFilename())[0]);
                            if (count($segments) !== 3 || !in_array($segments[2], ['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual']))
                                continue;
                            if (!isset($backup_files[$segments[2]])) $backup_files[$segments[2]] = [];
                            $backup_files[$segments[2]][] = $fileInfo->getRealPath();
                        }
                    }
                    foreach (['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual'] as $sel_domain) {
                        $domain_limit = $this->conf->get(MyHordesConf::CONF_BACKUP_LIMITS_INC . $sel_domain, -1);

                        if (!empty($backup_files[$sel_domain]) && $domain_limit >= 0 && count($backup_files[$sel_domain]) > $domain_limit) {
                            rsort($backup_files[$sel_domain]);
                            while (count($backup_files[$sel_domain]) > $domain_limit) {
                                $f = array_pop($backup_files[$sel_domain]);
                                if ($f === null) break;
                                $output->writeln("Deleting old backup: <info>$f</info>", OutputInterface::VERBOSITY_VERBOSE);
                                unlink($f);
                            }
                        }
                    }
                    break;
                case "ftp":
                    $ftp_conn = $this->adminHandler->connectToFtp($config['host'], $config['port'], $config['user'], $config['pass'], $config['passive']);;
                    if (!$ftp_conn) {
                        $success = false;
                        $output->writeln("<error>Unable to connect to {$config['type']} storage '$name'</error>");
                        break;
                    }

                    $ftpFiles = ftp_mlsd($ftp_conn, $config['path']);
                    foreach ($ftpFiles as $ftpFile){
                        if (in_array($ftpFile['type'], ['cdir', 'pdir', 'dir'])) continue;
                        if (!str_contains($ftpFile['name'], '.')) continue; // No dot, then no extension

                        $details = explode('.', $ftpFile['name']);
                        $ext = $details[count($details) - 1];

                        // Not an SQL dump, ignore it
                        if (!in_array(strtolower($ext), ['sql', 'xz', 'gzip', 'bz2'])) continue;
                        $segments = explode('_', explode('.', $ftpFile['name'])[0]);
                        if (count($segments) !== 3 || !in_array($segments[2], ['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual']))
                            continue;
                        if (!isset($backup_files[$segments[2]])) $backup_files[$segments[2]] = [];
                        $backup_files[$segments[2]][] = $config['path'] . '/' . $ftpFile['name'];
                    }


                    foreach (['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual'] as $sel_domain) {
                        $domain_limit = $this->conf->get(MyHordesConf::CONF_BACKUP_LIMITS_INC . $sel_domain, -1);

                        if (!empty($backup_files[$sel_domain]) && $domain_limit >= 0 && count($backup_files[$sel_domain]) > $domain_limit) {
                            rsort($backup_files[$sel_domain]);
                            while (count($backup_files[$sel_domain]) > $domain_limit) {
                                $f = array_pop($backup_files[$sel_domain]);
                                if ($f === null) break;
                                $output->writeln("Deleting old backup: <info>$f</info>", OutputInterface::VERBOSITY_VERBOSE);
                                ftp_delete($ftp_conn, $f);
                            }
                        }
                    }

                    ftp_close($ftp_conn);
                    break;
                case "sftp":
                    $conn = $this->adminHandler->connectToSftp($config['host'], $config['port'], $config['user'], $config['pass']);
                    if(!$conn) {
                        $success = false;
                        $output->writeln("<error>Unable to connect to {$config['type']} storage '$name'</error>");
                        break;
                    }
                    $sftp_fd = ssh2_sftp($conn);
                    $handle = opendir("ssh2.sftp://$sftp_fd{$config['path']}");

                    while (false != ($entry = readdir($handle))){
                        if (in_array($entry, ['.', '..'])) continue;
                        if (!str_contains($entry, '.')) continue; // No dot, then no extension

                        $details = explode('.', $entry);
                        $ext = $details[count($details) - 1];

                        // Not an SQL dump, ignore it
                        if (!in_array(strtolower($ext), ['sql', 'xz', 'gzip', 'bz2'])) continue;
                        $segments = explode('_', explode('.', $entry)[0]);
                        if (count($segments) !== 3 || !in_array($segments[2], ['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual']))
                            continue;
                        if (!isset($backup_files[$segments[2]])) $backup_files[$segments[2]] = [];
                        $backup_files[$segments[2]][] = $config['path'] . '/' . $entry;
                    }
                    closedir($handle);

                    foreach (['nightly', 'daily', 'weekly', 'monthly', 'update', 'manual'] as $sel_domain) {
                        $domain_limit = $this->conf->get(MyHordesConf::CONF_BACKUP_LIMITS_INC . $sel_domain, -1);

                        if (!empty($backup_files[$sel_domain]) && $domain_limit >= 0 && count($backup_files[$sel_domain]) > $domain_limit) {
                            rsort($backup_files[$sel_domain]);
                            while (count($backup_files[$sel_domain]) > $domain_limit) {
                                $f = array_pop($backup_files[$sel_domain]);
                                if ($f === null) break;
                                $output->writeln("Deleting old backup: <info>$f</info>", OutputInterface::VERBOSITY_VERBOSE);
                                ssh2_sftp_unlink($sftp_fd, $f);
                            }
                        }
                    }

                    ssh2_disconnect($conn);
                    break;
            }
        }

        // We remove the temporary backup file (as it should be stored in the different enabled storages)
        unlink($filename);

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = $input->getArgument('task');

        if (!in_array($task, ['host','attack','backup'])) {
            $output->writeln('<error>Invalid task.</error>');
            return -1;
        }

        $lock = $this->locksmith->waitForLock("cron-$task");

        switch ($task) {
            case 'host': return $this->task_host($input,$output);
            case 'backup': return $this->task_backup($input,$output);
            default: return -1;
        }


    }

    public function schedule(CommandTask $task): void
    {
        $task
            ->everyTenMinutes()
            ->withoutOverlapping(900)
            ->description('Legacy Cron Host')
        ;
    }
}