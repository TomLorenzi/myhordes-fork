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
use App\Structures\MyHordesConf;
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
    name: 'app:town:quarantine',
    description: 'Manages quarantine status for a given town.'
)]
class TownQuarantineCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ConfMaster $conf_master,
        private readonly CrowService $crowService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command calculates a town to become quarantined.')

            ->addArgument('town', InputArgument::REQUIRED, 'Town ID')

            ->addOption('lift', null, InputOption::VALUE_NONE, 'Lifts the quarantine instead of setting it.')
            ->addOption('silent', null, InputOption::VALUE_NONE, 'Disables notifications');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $town_id = (int)$input->getArgument('town');

        $lift = $input->getOption('lift');
        $silent = $input->getOption('silent');

        $town = $this->entityManager->getRepository(Town::class)->find($town_id);
        if (!$town) throw new Exception(sprintf('Town with ID %d not found.', $town_id));

        $try_limit = $this->conf_master->getGlobalConf()->get(MyHordesConf::CONF_NIGHTLY_RETRIES, 3);
        $town_was_quarantined = $town->getAttackFails() >= $try_limit;

        if ($lift && !$town_was_quarantined) return 0;
        elseif (!$lift && $town_was_quarantined) return 0;

        if ($lift)
            $town->setAttackFails(0);
        else
            $town->setAttackFails( $try_limit );

        if (!$silent) {
            foreach ($town->getCitizens() as $citizen) if ($citizen->getAlive())
                $this->entityManager->persist(
                    $this->crowService->createPM_townQuarantine( $citizen->getUser(), $town->getName(), !$lift )
                );
        }

        $this->entityManager->persist($town);
        $this->entityManager->flush();

        return 0;
    }
}