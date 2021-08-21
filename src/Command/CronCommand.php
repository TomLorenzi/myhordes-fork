<?php


namespace App\Command;


use App\Entity\AttackSchedule;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\ThreadReadMarker;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\User;
use App\Service\AntiCheatService;
use App\Service\CommandHelper;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\GameFactory;
use App\Service\Locksmith;
use App\Service\NightlyHandler;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateTime;
use DirectoryIterator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CronCommand extends Command
{
    protected static $defaultName = 'app:cron';

    private EntityManagerInterface $entityManager;
    private NightlyHandler $night;
    private Locksmith $locksmith;
    private Translator $trans;
    private MyHordesConf $conf;
    private ConfMaster $conf_master;
    private AntiCheatService $anti_cheat;
    private GameFactory $gameFactory;
    private UserHandler $userHandler;
    private TownHandler $townHandler;
    private CrowService $crowService;
    private CommandHelper $helper;
    private ParameterBagInterface $params;

    private array $db;

    public function __construct(array $db,
                                EntityManagerInterface $em, NightlyHandler $nh, Locksmith $ls, Translator $translator,
                                ConfMaster $conf, AntiCheatService $acs, GameFactory $gf, UserHandler $uh,
                                TownHandler $th, CrowService $cs, CommandHelper $helper, ParameterBagInterface $params)
    {
        $this->entityManager = $em;
        $this->night = $nh;
        $this->locksmith = $ls;
        $this->trans = $translator;
        $this->conf_master = $conf;
        $this->conf = $conf->getGlobalConf();
        $this->anti_cheat = $acs;
        $this->gameFactory = $gf;
        $this->userHandler = $uh;
        $this->townHandler = $th;
        $this->crowService = $cs;
        $this->helper = $helper;
        $this->params = $params;

        $this->db = $db;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Cron command')
            ->setHelp('This should be run on a regular basis.')

            ->addArgument('task',  InputArgument::OPTIONAL, 'The task to perform. Defaults to "host".', 'host')
            ->addArgument('p1',  InputArgument::OPTIONAL, 'Parameter 1', -1)
            ->addArgument('p2',  InputArgument::OPTIONAL, 'Parameter 2', -1)
            ;
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
            $fmt = $this->conf->get(MyHordesConf::CONF_FATAL_MAIL_TARGET, null);
            $fms = $this->conf->get(MyHordesConf::CONF_FATAL_MAIL_SOURCE, 'fatalmail@localhost');

            $town_ids = array_column($this->entityManager->createQueryBuilder()
                ->select('t.id')
                ->from(Town::class, 't')
                ->andWhere('(t.lastAttack != :last OR t.lastAttack IS NULL)')->setParameter('last', $s->getId())
                ->andWhere('t.attackFails < :trylimit')->setParameter('trylimit', $try_limit)
                ->getQuery()
                ->getScalarResult(), 'id');

            $this->entityManager->clear();

            $i = 1; $num = count($town_ids);
            foreach ( $town_ids as $town_id ) {

                $failures = [];
                while (count($failures) < $try_limit && !$this->helper->capsule("app:cron attack $town_id $schedule_id", $output, "Processing town <info>{$town_id}</info> <comment>($i/$num)</comment>... ", true, $ret))
                    $failures[] = $ret;

                $i++;

                if (!empty($failures)) {
                    // Send mail
                    if ($fmt) mail(
                        $fmt,
                        "MH-FatalMail {$town_id} {$schedule_id}",
                        "-- Automatic Report --\r\n\r\n" .
                        "Fatal Error during nightly attack on MyHordes\r\n\r\n" .
                        "Unable to process town `{$town_id}`\r\n\r\n" .
                        implode("\r\n", $failures),
                        [
                            'MIME-Version' => '1.0',
                            'Content-type' => 'text/plain; charset=UTF-8',
                            'From' => $fms
                        ]
                    );

                    // If we exceed the number of allowed processing tries, quarantine the town
                    if (count($failures) >= $try_limit) {
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
            $this->entityManager->persist($s->setCompleted(true));

            $datemod = $this->conf->get(MyHordesConf::CONF_NIGHTLY_DATEMOD, 'tomorrow');
            if ($datemod !== 'never') {

                $new_date = (new DateTime())->setTimestamp( $s->getTimestamp()->getTimestamp() )->modify($datemod);
                if ($new_date !== false && $new_date > $s->getTimestamp())
                    $this->entityManager->persist( (new AttackSchedule())->setTimestamp( $new_date ) );

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

    protected function module_ensure_towns(): bool {
        // Let's check if there is enough opened town
        $openTowns = $this->entityManager->getRepository(Town::class)->findOpenTown();

        $count = [];
        $langs = $this->conf->get( MyHordesConf::CONF_TOWNS_AUTO_LANG, [] );
        foreach ($langs as $lang) $count[$lang] = [];
        foreach ($openTowns as $openTown) {
            if (!isset($count[$openTown->getLanguage()])) continue;
            if (!isset($count[$openTown->getLanguage()][$openTown->getType()->getName()])) $count[$openTown->getLanguage()][$openTown->getType()->getName()] = 0;
            $count[$openTown->getLanguage()][$openTown->getType()->getName()]++;
        }

        $minOpenTown = [
            'small'  => $this->conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_SMALL, 1 ),
            'remote' => $this->conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_REMOTE, 1 ),
            'panda'  => $this->conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_PANDA, 1 ),
            'custom' => $this->conf->get( MyHordesConf::CONF_TOWNS_OPENMIN_CUSTOM, 0 ),
        ];

        $created_towns = false;
        foreach ($langs as $lang)
            foreach ($minOpenTown as $type => $min) {
                $current = $count[$lang][$type] ?? 0;
                while ($current < $min) {
                    $this->entityManager->persist($newTown = $this->gameFactory->createTown(null, $lang, null, $type));
                    $this->entityManager->flush();

                    $current_events = $this->conf_master->getCurrentEvents();
                    if (!empty(array_filter($current_events,fn(EventConf $e) => $e->active()))) {
                        if (!$this->townHandler->updateCurrentEvents($newTown, $current_events))
                            $this->entityManager->clear();
                        else {
                            $this->entityManager->persist($newTown);
                            $this->entityManager->flush();
                        }
                    }

                    $created_towns = true;
                    $current++;
                }
            }

        return $created_towns;
    }

    protected function task_host(InputInterface $input, OutputInterface $output): int {
        // Host task
        $output->writeln( "MyHordes CronJob Interface", OutputInterface::VERBOSITY_VERBOSE );

        $backup_ran = $this->module_run_backups($output);
        $output->writeln( "Backup scheduler: <info>" . ($backup_ran ?? 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $attacks_ran = $this->module_run_attacks($output);
        $output->writeln( "Attack scheduler: <info>" . ($attacks_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $clear_ran = $this->module_run_clear();
        $output->writeln( "Cleanup Crew: <info>" . ($clear_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $town_maker_ran = $this->module_ensure_towns();
        $output->writeln( "Town creator: <info>" . ($town_maker_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        return 0;
    }

    protected function task_attack(InputInterface $input, OutputInterface $output): int {
        // Attack task
        $output->writeln( "MyHordes CronJob - Attack Processor", OutputInterface::VERBOSITY_VERBOSE );

        $town_id = (int)$input->getArgument('p1');
        $schedule_id = (int)$input->getArgument('p2');

        if ($town_id <= 0 || $schedule_id <= 0) return -1;

        $town = $this->entityManager->getRepository(Town::class)->find($town_id);
        $schedule = $this->entityManager->getRepository(AttackSchedule::class)->find($schedule_id);

        if ($town === null || $schedule === 0) return -2;

        $events = $this->conf_master->getCurrentEvents();
        $town_conf = $this->conf_master->getTownConfiguration($town);

        if ($town->getLanguage() === 'multi') $this->trans->setLocale('en');
        else $this->trans->setLocale($town->getLanguage() ?? 'de');

        try {
            /** @var Town $town */
            $last_op = 'pre';

            if ($this->night->advance_day($town, $town_events = $this->conf_master->getCurrentEvents( $town ))) {

                foreach ($this->night->get_cleanup_container() as $c) $this->entityManager->remove($c);
                $town->setLastAttack($schedule)->setAttackFails(0);

                $last_op = 'adv';
                $this->entityManager->persist($town);
                $this->entityManager->flush();

                // Enable or disable events
                if (!$this->conf_master->checkEventActivation($town)) {
                    $last_op = 'ev_a';
                    if ($this->townHandler->updateCurrentEvents($town, $events)) {
                        $this->entityManager->persist($town);
                        $this->entityManager->flush();
                    } else $this->entityManager->clear();
                }

            } else {

                // In case a log entry has been written to the town log during the cancelled attack,
                // we want to make sure everything is persisted before we proceed.
                $last_op = 'stay';
                $this->entityManager->persist($town);
                $this->entityManager->flush();

                $limit = (int)$town_conf->get( TownConf::CONF_CLOSE_TOWN_AFTER, -1 );
                $grace = (int)$town_conf->get( TownConf::CONF_CLOSE_TOWN_GRACE, 40 );

                if ($town->isOpen() && !$town->getCitizens()->isEmpty() && $limit >= 0 && $town->getDayWithoutAttack() > $limit && $town->getCitizenCount() < $grace) {
                    $last_op = 'del';
                    foreach ($town->getCitizens() as $citizen)
                        $this->entityManager->persist(
                            $this->crowService->createPM_townNegated( $citizen->getUser(), $town->getName(), true )
                        );
                    $this->gameFactory->nullifyTown($town, true);
                } elseif ($town->isOpen() && $town->getAliveCitizenCount() == 0) {
                    $last_op = 'delc';
                    $this->gameFactory->nullifyTown($town, true);
                } elseif ((!$town->isOpen()) && $town->getAliveCitizenCount() == 0) {
                    $last_op = 'com';
                    $town->setAttackFails(0);
                    if (!$this->gameFactory->compactTown($town))
                        $this->entityManager->persist($town);
                } else {
                    $town->setAttackFails(0);
                    $this->entityManager->persist($town);

                    // Enable or disable events
                    $running_events = $town_events;
                    if (!$town->getManagedEvents() && !$this->conf_master->checkEventActivation($town)) {
                        $this->entityManager->flush();
                        $last_op = 'ev_s';
                        if ($this->townHandler->updateCurrentEvents($town, $events)) {
                            $this->entityManager->persist($town);
                            $running_events = $events;
                            $this->entityManager->flush();
                        } else $this->entityManager->clear();
                    }

                    foreach ($running_events as $running_event)
                        $running_event->hook_nightly_none( $town );
                    $this->entityManager->persist($town);
                    $this->entityManager->flush();
                }
                $this->entityManager->flush();
            }
        } catch (Exception $e) {

            $output->writeln("<error>Failed to process town {$town->getId()} (@{$last_op})!</error>");
            $output->writeln($e->getMessage());

            return -3;
        }

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

        $domain = $input->getArgument('p1');

        if ($domain === -1) $domain = 'manual';
        if (!in_array($domain,['nightly','daily','weekly','monthly','update','manual']))
            throw new Exception('Invalid backup domain!');

        $path = $this->conf->get(MyHordesConf::CONF_BACKUP_PATH, null);
        if ($path === null) $path = "{$this->params->get('kernel.project_dir')}/var/backup";
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
            $this->helper->capsule("mysqldump -h $db_host -P $db_port --user='$db_user' --password='$db_pass' --databases $db_name --single-transaction --skip-lock-tables $str", $output, 'Running database backup... ', false );
        } else
            $output->writeln("Skipping <info>mysqldump</info> in domain <info>$domain</info> since backups for this domain are turned off.", OutputInterface::VERBOSITY_VERBOSE );

        $backup_files = [];

        foreach (new DirectoryIterator($path) as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isDot() || $fileInfo->isLink()) continue;
            elseif ($fileInfo->isFile() && in_array(strtolower($fileInfo->getExtension()), ['sql','xz','gzip','bz2'])) {
                $segments = explode('_', explode('.',$fileInfo->getFilename())[0]);
                if (count($segments) !== 3 || !in_array($segments[2], ['nightly','daily','weekly','monthly','update','manual']))
                    continue;
                if (!isset($backup_files[$segments[2]])) $backup_files[$segments[2]] = [];
                $backup_files[$segments[2]][] = $fileInfo->getRealPath();
            }
        }

        foreach (['nightly','daily','weekly','monthly','update','manual'] as $sel_domain) {
            $domain_limit = $this->conf->get(MyHordesConf::CONF_BACKUP_LIMITS_INC . $sel_domain, -1);
            if (!empty($backup_files[$sel_domain]) && $domain_limit >= 0 && count($backup_files[$sel_domain]) > $domain_limit) {
                rsort($backup_files[$sel_domain]);
                while (count($backup_files[$sel_domain]) > $domain_limit) {
                    $f = array_pop($backup_files[$sel_domain]);
                    if ($f === null) break;
                    $output->writeln("Deleting old backup: <info>$f</info>", OutputInterface::VERBOSITY_VERBOSE );
                    unlink( $f );
                }
            }
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getArgument('task');

        if (!in_array($task, ['host','attack','backup'])) {
            $output->writeln('<error>Invalid task.</error>');
            return -1;
        }

        $lock = $this->locksmith->waitForLock("cron-$task");

        switch ($task) {
            case 'host': return $this->task_host($input,$output);
            case 'attack': return $this->task_attack($input,$output);
            case 'backup': return $this->task_backup($input,$output);
            default: return -1;
        }


    }
}