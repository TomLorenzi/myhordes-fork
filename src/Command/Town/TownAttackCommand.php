<?php


namespace App\Command\Town;

use App\Entity\AttackSchedule;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\Zone;
use App\Enum\Configuration\TownSetting;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\GameEventService;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\GameValidator;
use App\Service\GazetteService;
use App\Service\Locksmith;
use App\Service\NightlyHandler;
use App\Service\TownHandler;
use App\Structures\EventConf;
use App\Structures\TownSetup;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:town:attack',
    description: 'Calculates the nightly attack for a given town.'
)]
class TownAttackCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfMaster $conf_master,
        private readonly TranslatorInterface $trans,
        private readonly GameFactory $gameFactory,
        private readonly NightlyHandler $night,
        private readonly GazetteService $gazetteService,
        private readonly TownHandler $townHandler,
        private readonly CrowService $crowService,
        private readonly GameEventService $gameEvents,
        private readonly LoggerInterface $log
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command calculates the nightly attack for a single town.')

            ->addArgument('town', InputArgument::REQUIRED, 'Town ID')
            ->addArgument('schedule', InputArgument::OPTIONAL, 'Schedule ID. Can be omitted in dry run mode.')

            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Does not persist any changes.')
            ->addOption('seed', null, InputOption::VALUE_REQUIRED, 'Provides a manual seed value. If unset, a random value will be used.')
            ->addOption('door', null, InputOption::VALUE_REQUIRED, 'Set to 1 to force the door to be open or 0 to force it closed, regardless of actual state');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Attack task
        $output->writeln( "MyHordes Attack Processor", OutputInterface::VERBOSITY_VERBOSE );

        $town_id = (int)$input->getArgument('town');
        $schedule_id = (int)$input->getArgument('schedule') ?? 0;

        $dry_run = $input->getOption('dry-run');
        $seed = $input->getOption('seed') ?? mt_rand();

        $door = $input->getOption('door');
        if ($door !== null)
            $door = !!$door;

        $this->log->info("Attack processor has been invoked. Town: <info>$town_id</info>, Schedule: <info>$schedule_id</info>, Seed: <info>$seed</info>, Dry-Run Mode: <info>" . ($dry_run ? 'yes' : 'no') . "</info>");
        if ($dry_run) $output->writeln( "<bg=yellow;fg=white>Dry Run Mode. No changes will be persisted.</>", OutputInterface::VERBOSITY_VERBOSE );

        $town = $this->entityManager->getRepository(Town::class)->find($town_id);
        $schedule = $this->entityManager->getRepository(AttackSchedule::class)->find($schedule_id);

        if (!$town || (!$schedule && !$dry_run)) return -2;

        if (!$dry_run && $town->getLastAttack()?->getId() === $schedule->getId()) {
            $output->writeln( "<bg=yellow;fg=white>This schedule has already been processed.</> Exiting.", OutputInterface::VERBOSITY_VERBOSE );
            return 0;
        }

        $output->writeln( "Using seed <fg=yellow>$seed</>.", OutputInterface::VERBOSITY_VERBOSE );
        mt_srand($seed);

        if ($door !== null) {
            $town->setDoor($door);
            $output->writeln( "Forcing door " . ($door ? "<bg=yellow;fg=white>open</>" : "<bg=yellow;fg=white>closed</>."), OutputInterface::VERBOSITY_VERBOSE );
        }

        $events = $this->conf_master->getCurrentEvents();
        $town_conf = $this->conf_master->getTownConfiguration($town);

        if ($town->getLanguage() === 'multi') $this->trans->setLocale('en');
        else $this->trans->setLocale($town->getLanguage() ?? 'de');

        try {
            /** @var Town $town */
            $last_op = 'fst';
            if ($town->isOpen() && $town->getForceStartAhead()) {
                $town->setForceStartAhead( false );
                $this->gameFactory->enableStranger( $town );
            }

            $last_op = 'pre';
            if ($this->night->advance_day($town, $town_events = $this->conf_master->getCurrentEvents( $town ))) {

                foreach ($this->night->get_cleanup_container() as $c) $this->entityManager->remove($c);
                if (!$dry_run) $town->setLastAttack($schedule)->setLastAttackProcessedAt(new \DateTimeImmutable())->setAttackFails(0);

                $last_op = 'adv';
                $this->entityManager->persist($town);

                if (!$dry_run) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $town = $this->entityManager->getRepository(Town::class)->find($town_id);
                }

                try {
                    $this->entityManager->persist( $this->gazetteService->ensureGazette($town) );

                    if (!$dry_run) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                        $town = $this->entityManager->getRepository(Town::class)->find($town_id);
                    }
                } catch (Exception $e) {}

                // Enable or disable events
                if (!$town->getManagedEvents() && !$this->conf_master->checkEventActivation($town)) {
                    $last_op = 'ev_a';
                    if ($this->townHandler->updateCurrentEvents($town, $events)) {
                        $this->entityManager->persist($town);
                        if (!$dry_run) $this->entityManager->flush();
                    } elseif (!$dry_run) $this->entityManager->clear();
                }

            } else {

                // In case a log entry has been written to the town log during the cancelled attack,
                // we want to make sure everything is persisted before we proceed.
                $last_op = 'stay';
                $this->entityManager->persist($town);
                if (!$dry_run) $this->entityManager->flush();

                $limit = (int)$town_conf->get( TownSetting::CancelTownAfterDaysWithoutFilling );
                $grace = (int)$town_conf->get( TownSetting::DoNotCancelAfterCitizensReached );

                $stranger_day   = (int)$town_conf->get( TownSetting::SpawnStrangerAfterUnfilledDays );
                $stranger_limit = (int)$town_conf->get( TownSetting::SpawnStrangerAfterCitizenCount );

                $update_events = false;

                if ($town->isOpen() && $town->getAliveCitizenCount() > 0 && !$town->getCitizens()->isEmpty() && $stranger_day >= 0 && $town->getDayWithoutAttack() > $stranger_day && $town->getCitizenCount() >= $stranger_limit && $town->getCitizenCount() < $grace) {
                    $last_op = 'strg';
                    $town->setForceStartAhead(true);
                    $update_events = true;
                    $this->entityManager->persist($town);
                } elseif ($town->isOpen() && $town->getAliveCitizenCount() > 0 && !$town->getCitizens()->isEmpty() && $limit >= 0 && $town->getDayWithoutAttack() > $limit && $town->getCitizenCount() < $grace) {
                    $last_op = 'del';
                    foreach ($town->getCitizens() as $citizen)
                        $this->entityManager->persist(
                            $this->crowService->createPM_townNegated( $citizen->getUser(), $town->getName(), true )
                        );
                    $this->gameFactory->nullifyTown($town, true);
                } elseif ($town->isOpen() && $town->getCitizenCount() > 0 && $town->getAliveCitizenCount() == 0) {
                    $last_op = 'delc';
                    $this->gameFactory->nullifyTown($town, true);
                } elseif ((!$town->isOpen()) && $town->getAliveCitizenCount() == 0) {
                    $last_op = 'com';
                    $town->setAttackFails(0);
                    if (!$this->gameFactory->compactTown($town)) {
                        $this->entityManager->persist($town);
                        $update_events = true;
                    }
                } else {
                    $update_events = true;
                    $town->setAttackFails(0);
                    $this->entityManager->persist($town);
                }

                // Enable or disable events
                if ($update_events) {
                    $running_events = $town_events;
                    if (!$town->getManagedEvents() && !$this->conf_master->checkEventActivation($town)) {
                        $this->entityManager->flush();
                        $last_op = 'ev_s';
                        if ($this->townHandler->updateCurrentEvents($town, $events)) {
                            $this->entityManager->persist($town);
                            $running_events = $events;
                            if (!$dry_run) $this->entityManager->flush();
                        } elseif (!$dry_run) $this->entityManager->clear();
                    }

                    $this->gameEvents->triggerNoAttackHooks( $town, $running_events );
                    $this->entityManager->persist($town);
                    if (!$dry_run) $this->entityManager->flush();
                }

                if (!$dry_run) $this->entityManager->flush();
            }
        } catch (Exception $e) {

            $output->writeln("<error>Failed to process town {$town->getId()} (@{$last_op})!</error>");
            $output->writeln($e->getMessage());

            return -3;
        }

        return 0;
    }
}