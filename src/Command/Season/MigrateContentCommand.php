<?php


namespace App\Command\Season;

use App\Entity\Town;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Event\Game\Town\Maintenance\TownContentMigrationEvent;
use App\Service\CommandHelper;
use App\Service\EventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:season:migrate',
    description: 'Migrates existing town data after a season change'
)]
class MigrateContentCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommandHelper $helper,
        private readonly EventDispatcherInterface $ed,
        private readonly EventFactory $ef
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('town', null, InputOption::VALUE_REQUIRED, 'Process town')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate process')
        ;
    }

    protected function execute_for_town(int $town_id, InputInterface $input, OutputInterface $output): int
    {
        $dry_run = $input->getOption('dry-run');
        $town = $this->entityManager->getRepository(Town::class)->find( $town_id );
        if (!$town) throw new Exception("Town {$town_id} not found!");
        $this->ed->dispatch( $this->ef->gameEvent( TownContentMigrationEvent::class, $town )->setup( $output ) );
        if (!$dry_run) $this->entityManager->flush();
        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dry_run = $input->getOption('dry-run');
        if ($tid = $input->getOption('town')) return $this->execute_for_town($tid, $input, $output);

        $town_ids = array_column($this->entityManager->createQueryBuilder()
            ->select('t.id')
            ->from(Town::class, 't')
            ->getQuery()
            ->getScalarResult(), 'id');

        if ($dry_run) $output->writeln('<bg=yellow>DRY RUN</>');

        foreach ( $town_ids as $id )
            if (!$this->helper->capsule( "app:season:migrate --town $id" . ($dry_run ? ' --dry-run' : '') , $output, "Processing content migration for town <fg=green>$id</>... "))
                return 1;

        return 0;
    }
}
