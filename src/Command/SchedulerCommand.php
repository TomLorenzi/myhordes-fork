<?php


namespace App\Command;


use App\Entity\AttackSchedule;
use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Service\GameValidator;
use App\Service\Locksmith;
use App\Service\NightlyHandler;
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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchedulerCommand extends Command
{
    protected static $defaultName = 'app:schedule';

    private $entityManager;
    private $night;
    private $locksmith;
    private $trans;

    public function __construct(EntityManagerInterface $em, NightlyHandler $nh, Locksmith $ls, Translator $translator)
    {
        $this->entityManager = $em;
        $this->night = $nh;
        $this->locksmith = $ls;
        $this->trans = $translator;
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
                $this->trans->setLocale($town->getLanguage() ?? 'de');

                /** @var Town $town */
                if ($this->night->advance_day($town)) {
                    foreach ($this->night->get_cleanup_container() as $c) $this->entityManager->remove($c);
                    $this->entityManager->persist($town);
                }
                $progress->advance();
            }

            $s->setCompleted( true );
            $this->entityManager->persist($s);
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