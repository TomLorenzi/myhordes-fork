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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

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

    public function __construct(EntityManagerInterface $em, NightlyHandler $nh, Locksmith $ls, Translator $translator,
                                ConfMaster $conf, AntiCheatService $acs, GameFactory $gf, UserHandler $uh,
                                TownHandler $th, CrowService $cs, CommandHelper $helper)
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

    protected function module_run_attacks(OutputInterface $output): bool {

        $s = $this->entityManager->getRepository(AttackSchedule::class)->findNextUncompleted();
        if ($s && $s->getTimestamp() < new DateTime('now')) {

            // ToDo: Backup

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

    protected function task_host(InputInterface $input, OutputInterface $output): int {
        // Host task
        $output->writeln( "MyHordes CronJob Interface", OutputInterface::VERBOSITY_VERBOSE );

        $attacks_ran = $this->module_run_attacks($output);
        $output->writeln( "Attack scheduler: <info>" . ($attacks_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

        $clear_ran = $this->module_run_clear();
        $output->writeln( "Cleanup Crew: <info>" . ($clear_ran ? 'Complete' : 'Not scheduled') . "</info>", OutputInterface::VERBOSITY_VERBOSE );

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

                if ($town->isOpen() && $limit >= 0 && $town->getDayWithoutAttack() > $limit && $town->getCitizenCount() < $grace) {
                    $last_op = 'del';
                    foreach ($town->getCitizens() as $citizen)
                        $this->entityManager->persist(
                            $this->crowService->createPM_townNegated( $citizen->getUser(), $town->getName(), true )
                        );
                    $this->gameFactory->nullifyTown($town, true);

                } elseif (!$town->isOpen() && $town->getAliveCitizenCount() == 0) {
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $input->getArgument('task');

        if (!in_array($task, ['host','attack'])) {
            $output->writeln('<error>Invalid task.</error>');
            return -1;
        }

        switch ($task) {
            case 'host': return $this->task_host($input,$output);
            case 'attack': return $this->task_attack($input,$output);
            default: return -1;
        }
    }
}