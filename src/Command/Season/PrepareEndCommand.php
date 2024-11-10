<?php


namespace App\Command\Season;


use App\Entity\CitizenRankingProxy;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Season;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Service\CommandHelper;
use App\Service\GameFactory;
use App\Service\TownHandler;
use App\Service\UserHandler;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:season:end',
    description: 'Prepares the current season for ending'
)]
class PrepareEndCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper $commandHelper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Auto-confirm');
    }

    /**
     * @param Town[] $towns
     * @param OutputInterface $output
     * @return void
     */
    protected function printTownList(array $towns, OutputInterface $output) {
        $table = new Table( $output );
        $table->setHeaders( ['ID', 'Lang', 'Name', 'Type', 'Day'] );
        foreach ($towns as $town) {
            $table->addRow([
                               $town->getId(),
                               $town->getLanguage(),
                               $town->getName(),
                               $town->getType()->getLabel(),
                               $town->getDay()
                           ]);
        }
        $table->render();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentSeason = $this->entityManager->getRepository(Season::class)->findOneBy(['current' => true]);
        $currentTowns = $this->entityManager->getRepository(Town::class)->findBy(['season' => $currentSeason]);

        $townsToClear = array_filter( $currentTowns, fn( Town $town ) => $town->getDay() < 2 );
        $townsToUnrank = array_filter( $currentTowns, fn( Town $town ) => $town->getDay() >= 2 && !$town->getRankingEntry()->getDisabled() && !$town->getRankingEntry()->hasDisableFlag(TownRankingProxy::DISABLE_RANKING) );

        $output->writeln("<fg=red>The following towns will be deleted:</>");
        $this->printTownList( $townsToClear, $output );

        $output->writeln("\n<fg=yellow>The following towns will be excluded from the ranking:</>");
        $this->printTownList( $townsToUnrank, $output );

        $output->writeln('');
        if (!$this->commandHelper->interactiveConfirm( $this->getHelper('question'), $input, $output ))
            return -1;


        $progressBar = new ProgressBar($output);
        $output->writeln("<fg=yellow>Unranking towns</>");
        foreach ($progressBar->iterate($townsToUnrank) as $town) {
            /** @var Town $town */
            $town->getRankingEntry()->addDisableFlag( TownRankingProxy::DISABLE_RANKING );
            $this->entityManager->persist( $town->getRankingEntry() );
        }
        $this->entityManager->flush();

        $townsToClear = array_map( fn(Town $town) => $town->getId(), $townsToClear );
        $this->entityManager->clear();


        $progressBar = new ProgressBar($output);
        $output->writeln("\n\n<fg=yellow>Removing towns</>");
        foreach ($progressBar->iterate($townsToClear) as $townID) {
            /** @var Town $town */
            $town = $this->entityManager->getRepository(Town::class)->find($townID);
            if ($town->getRankingEntry()) $this->entityManager->remove($town->getRankingEntry());
            $this->entityManager->remove($town);
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
        $output->writeln("\n\n");

        return 0;

        //$this->entityManager->flush();
    }
}
