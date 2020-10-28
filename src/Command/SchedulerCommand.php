<?php


namespace App\Command;


use App\Entity\AttackSchedule;
use App\Entity\Citizen;
use App\Entity\Picto;
use App\Entity\ThreadReadMarker;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Service\AntiCheatService;
use App\Service\ConfMaster;
use App\Service\GameFactory;
use App\Service\Locksmith;
use App\Service\NightlyHandler;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class SchedulerCommand extends Command
{
    protected static $defaultName = 'app:schedule';

    private $entityManager;
    private $night;
    private $locksmith;
    private $trans;
    private $conf;
    private $conf_master;
    private $anti_cheat;
    private $gameFactory;

    public function __construct(EntityManagerInterface $em, NightlyHandler $nh, Locksmith $ls, Translator $translator, ConfMaster $conf, AntiCheatService $acs, GameFactory $gf)
    {
        $this->entityManager = $em;
        $this->night = $nh;
        $this->locksmith = $ls;
        $this->trans = $translator;
        $this->conf_master = $conf;
        $this->conf = $conf->getGlobalConf();
        $this->anti_cheat = $acs;
        $this->gameFactory = $gf;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Hook for a scheduler')
            ->setHelp('This command controls scheduled actions.')

            ->addOption('now',  null, InputOption::VALUE_NONE,     'Check the schedule and execute if possible.')
            ->addOption('delay',null, InputOption::VALUE_REQUIRED, 'Delays the execution by a number of seconds.')
            ->addOption('next', null, InputOption::VALUE_REQUIRED, 'If the schedule is executed, automatically schedule the next execution at the given time point.')
            ->addOption('add',  null, InputOption::VALUE_REQUIRED, 'Adds a new schedule point at the given time.')
            ->addOption('clear',null, InputOption::VALUE_NONE,     'Clears not yet executed schedules. Clearing will be performed before evaluating --next or --add.')
            ->addOption('info', null, InputOption::VALUE_NONE,     'Shows the next scheduled attacks.')
            ;
    }

    private function execute_now( InputInterface $input, OutputInterface $output ): bool {
        if (!$input->getOption('now')) return false;
        /** @var AttackSchedule|null $s */
        $s = $this->entityManager->getRepository(AttackSchedule::class)->findNextUncompleted();
        if ($s && $s->getTimestamp() < new DateTime('now')) {

            $output->writeln( 'A schedule has been <info>activated</info>!' );
            if ($input->getOption('delay'))
                sleep( (int)$input->getOption('delay') );
            $output->writeln( 'Beginning <info>execution</info>...' );

            $towns = $this->entityManager->getRepository(Town::class)->findAll();

            // Set up console
            $progress = new ProgressBar( $output->section() );
            $progress->start( count($towns) );

            foreach ( $towns as $town ) {

                // This makes sure we don't carry anything over from the last town that was processed
                // Since we clear the entityManager, we need to re-fetch the Town and AttackSchedule entity
                $this->entityManager->clear();
                $town = $this->entityManager->getRepository(Town::class)->find($town->getId());
                $s = $this->entityManager->getRepository(AttackSchedule::class)->find($s->getId());

                if ($town->getAttackFails() >= 3 || ($town->getLastAttack() && $town->getLastAttack()->getId() === $s->getId()))
                    continue;

                $town_conf = $this->conf_master->getTownConfiguration($town);

                if ($town->getLanguage() === 'multi') $this->trans->setLocale('en');
                else $this->trans->setLocale($town->getLanguage() ?? 'de');

                try {
                    /** @var Town $town */
                    $town->setAttackFails($town->getAttackFails() + 1);

                    $last_op = 'pre';
                    $this->entityManager->persist($town);
                    $this->entityManager->flush();

                    if ($this->night->advance_day($town)) {
                        foreach ($this->night->get_cleanup_container() as $c) $this->entityManager->remove($c);

                        $town->setLastAttack($s)->setAttackFails(0);

                        $last_op = 'adv';
                        $this->entityManager->persist($town);
                        $this->entityManager->flush();

                    } else {

                        // In case a log entry has been written to the town log during the cancelled attack,
                        // we want to make sure everything is persisted before we proceed.
                        $last_op = 'stay';
                        $this->entityManager->persist($town);
                        $this->entityManager->flush();

                        $limit = (int)$town_conf->get( TownConf::CONF_CLOSE_TOWN_AFTER, -1 );

                        if ($town->isOpen() && $limit >= 0 && $town->getDayWithoutAttack() > $limit) {
                            $last_op = 'del';
                            $this->gameFactory->nullifyTown($town, true);

                        } elseif (!$town->isOpen() && $town->getAliveCitizenCount() == 0) {
                            $last_op = 'com';
                            $town->setAttackFails(0);
                            if (!$this->gameFactory->compactTown($town)) {
                                $this->entityManager->persist($town);
                            }
                        } else {
                            $town->setAttackFails(0);
                            $this->entityManager->persist($town);
                        }
                        $this->entityManager->flush();
                    }
                } catch (Exception $e) {

                    $output->writeln("<error>Failed to process town {$town->getId()} (@{$last_op})!</error>");
                    $output->writeln($e->getMessage());

                    $fmt = $this->conf->get(MyHordesConf::CONF_FATAL_MAIL_TARGET, null);
                    $fms = $this->conf->get(MyHordesConf::CONF_FATAL_MAIL_SOURCE, 'fatalmail@localhost');
                    if ($fmt) {
                        $message = "-- Automatic Report --\r\n\r\n" .
                            "Fatal Error during nightly attack on MyHordes\r\n\r\n" .
                            "Unable to process town `{$town->getId()}`\r\n\r\n" .
                            "`{$e->getMessage()}` in `{$e->getFile()} [{$e->getLine()}]`\r\n\r\n" .
                            "```{$e->getTraceAsString()}```";

                        mail(
                            $fmt,
                            "MH-FatalMail {$town->getId()} {$town->getDay()}-{$town->getAttackFails()}", $message,
                            [
                                'MIME-Version' => '1.0',
                                'Content-type' => 'text/plain; charset=UTF-8',
                                'From' => $fms
                            ]
                        );
                    }

                    throw $e;
                }

                $progress->advance();
            }

            $s->setCompleted( true );
            $this->entityManager->persist($s);
            $this->anti_cheat->cleanseConnectionIdentifiers();  // Delete old connection identifiers
            $progress->finish();

            return true;
        } else return false;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    private function execute_next( InputInterface $input, OutputInterface $output ) {
        if (!$input->getOption('next')) return;

        $new_date = (new DateTime('now'))->modify( $input->getOption( 'next' ) );
        if ($new_date === false) throw new Exception('Invalid date.');

        $output->writeln( "The next attack will be scheduled for <info>{$new_date->format('d.m.Y H:i:s')}</info>." );
        $this->entityManager->persist( (new AttackSchedule())->setTimestamp( $new_date ) );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    private function execute_add( InputInterface $input, OutputInterface $output ) {
        if (!$input->getOption('add')) return;

        $new_date = new DateTime($input->getOption('add'));
        if ($new_date === false) throw new Exception('Invalid date.');

        $output->writeln( "A new attack has been scheduled for <info>{$new_date->format('d.m.Y H:i:s')}</info>." );
        $this->entityManager->persist( (new AttackSchedule())->setTimestamp( $new_date ) );
    }

    private function execute_clear( InputInterface $input, OutputInterface $output ) {
        if (!$input->getOption('clear')) return;
        foreach ( $this->entityManager->getRepository(AttackSchedule::class)->findByCompletion( false ) as $s ) {
            $this->entityManager->remove($s);
            $output->writeln( "Removing event <info>{$s->getId()}</info>.", OutputInterface::VERBOSITY_VERBOSE );
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->locksmith->getAcquiredLock('scheduler');
        if (!$lock) {
            $output->writeln('<error>Another scheduler instance is already running.</error>');
            return 0;
        }

        $has_executed = $this->execute_now($input,$output);
        $this->execute_clear($input,$output);
        if ($has_executed) $this->execute_next($input,$output);
        $this->execute_add($input,$output);

        $this->entityManager->flush();

        if ($input->getOption('info'))
            foreach ( $this->entityManager->getRepository(AttackSchedule::class)->findByCompletion( false ) as $s ) {
                /** @var $s AttackSchedule */
                $output->writeln( "Scheduled Attack: <info>{$s->getTimestamp()->format('d.m.Y H:i:s')}</info>." );
            }


        return 0;
    }
}