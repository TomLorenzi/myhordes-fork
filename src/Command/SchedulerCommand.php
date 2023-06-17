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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(
    name: 'app:schedule',
    description: 'Hook for a scheduler'
)]
class SchedulerCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command controls scheduled actions.')

            ->addOption('add',  null, InputOption::VALUE_REQUIRED, 'Adds a new schedule point at the given time.')
            ->addOption('clear',null, InputOption::VALUE_NONE,     'Clears not yet executed schedules. Clearing will be performed before evaluating --next or --add.')
            ->addOption('info', null, InputOption::VALUE_NONE,     'Shows the next scheduled attacks.')
            ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    private function execute_add( InputInterface $input, OutputInterface $output ) {
        if (!$input->getOption('add')) return;

        $new_date = date_create($input->getOption('add'));
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->execute_clear($input,$output);
        $this->execute_add($input,$output);

        $this->entityManager->flush();

        if ($input->getOption('info'))
            foreach ( $this->entityManager->getRepository(AttackSchedule::class)->findByCompletion( false ) as $s )
                /** @var $s AttackSchedule */
                $output->writeln( "Scheduled Attack: N.<info>{$s->getId()} @ {$s->getTimestamp()->format('d.m.Y H:i:s')}</info>." );

        return 0;
    }
}