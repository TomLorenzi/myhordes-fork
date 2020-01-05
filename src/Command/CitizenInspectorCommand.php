<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\CitizenStatus;
use App\Service\StatusFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class CitizenInspectorCommand extends Command
{
    protected static $defaultName = 'app:citizen';

    private $entityManager;
    private $statusFactory;

    public function __construct(EntityManagerInterface $em, StatusFactory $sf)
    {
        $this->entityManager = $em;
        $this->statusFactory = $sf;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulates and lists information about a single citizen.')
            ->setHelp('This command allows you work on single citizen.')
            ->addArgument('CitizenID', InputArgument::REQUIRED, 'The citizen ID')

            ->addOption('set-ap', 'ap',InputOption::VALUE_REQUIRED, 'Sets the current AP.', -1)
            ->addOption('add-status','sn',InputOption::VALUE_REQUIRED, 'Adds a new status.', '')
            ->addOption('remove-status',null,InputOption::VALUE_REQUIRED, 'Removes an existing status.', '')
        ;
    }

    protected function info(Citizen $citizen, OutputInterface $output) {
        $output->writeln("This is a citizen of '<info>{$citizen->getTown()->getName()}</info>'.");

        $output->writeln('<comment>Citizen info</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['Active?', 'Alive?','UID', 'TID', 'InvID', 'HomeID', 'HomeInvID', 'AP', 'Status'] );

        $table->addRow([
            (int)$citizen->getActive(),
            (int)$citizen->getAlive(),
            (int)$citizen->getUser()->getId(),
            (int)$citizen->getTown()->getId(),
            (int)$citizen->getInventory()->getId(),
            (int)$citizen->getHome()->getId(),
            (int)$citizen->getHome()->getChest()->getId(),
            (int)$citizen->getAp(),
            implode("\n", array_map( function(CitizenStatus $s): string { return $s->getLabel(); }, $citizen->getStatus()->getValues() ) )
        ]);
        $table->render();

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $citizen = $this->entityManager->getRepository(Citizen::class)->find( (int)$input->getArgument('CitizenID') );

        $updated = false;

        if (!$citizen) {
            $output->writeln("<error>The selected citizen could not be found.</error>");
            return 1;
        }

        $set_ap = $input->getOption('set-ap');
        if ($set_ap >= 0) {
            $citizen->setAp( $set_ap );
            $updated = true;
        }

        if ($new_status = $input->getOption('add-status')) {

            $status = $this->statusFactory->createStatus( $new_status );
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Adding status '<info>{$status->getName()}</info>'.\n" );
            $citizen->addStatus( $status );

            $updated = true;
        }

        if ($rem_status = $input->getOption('remove-status')) {

            $status = $this->statusFactory->createStatus( $rem_status );
            if (!$status) {
                $output->writeln("<error>The selected status could not be found.</error>");
                return 1;
            }

            $output->writeln( "Removing status '<info>{$status->getName()}</info>'.\n" );
            $citizen->removeStatus( $status );

            $updated = true;
        }

        if ($updated) {
            $this->entityManager->persist($citizen);
            $this->entityManager->flush();
        }

        return $this->info($citizen, $output);
    }
}