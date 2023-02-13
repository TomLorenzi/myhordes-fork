<?php


namespace App\Command\Utils;


use App\Command\LanguageCommand;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\RuinExplorerStats;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CitizenHandler;
use App\Service\CommandHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\StatusFactory;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:utils:teardown',
    description: 'Command that is to be executed before switching the server into maintenance mode.'
)]
class TeardownCommand extends LanguageCommand
{
    private EntityManagerInterface $entityManager;
    private ItemFactory $itemFactory;
    private InventoryHandler $inventoryHandler;
    private CitizenHandler $citizenHandler;
    private UserHandler $userHandler;

    public function __construct(EntityManagerInterface $em, ItemFactory $if, InventoryHandler $ih, CitizenHandler $ch, CommandHelper $comh, UserHandler $uh)
    {
        $this->entityManager = $em;
        $this->inventoryHandler = $ih;
        $this->itemFactory = $if;
        $this->citizenHandler = $ch;
        $this->helper = $comh;
        $this->userHandler = $uh;
        parent::__construct();
    }

    protected function executeExplorableRuinSessionReset(OutputInterface $output) {
        $this->helper->leChunk($output, RuinExplorerStats::class, 1, ['active' => true], false, false, function(RuinExplorerStats $session) use ($output) {
            $citizen = $session->getCitizen();
            $this->citizenHandler->setAP( $citizen, true, 1 );
            $citizen->removeExplorerStat( $session );
            $this->entityManager->remove( $session );
            $output->writeln("Removing citizen <info>{$citizen->getName()}</info> <debug>[{$citizen->getId()}]</debug> from an explorable ruin.");
        }, true);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->executeExplorableRuinSessionReset($output);
        $output->writeln('<info>Teardown complete</info>');

        return 0;
    }
}
