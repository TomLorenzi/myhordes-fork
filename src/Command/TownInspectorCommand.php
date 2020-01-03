<?php


namespace App\Command;


use App\Entity\Citizen;
use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Entity\WellCounter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class TownInspectorCommand extends Command
{
    protected static $defaultName = 'app:town';

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Manipulates and lists information about a single town.')
            ->setHelp('This command allows you work on single towns.')
            ->addArgument('TownID', InputArgument::REQUIRED, 'The town ID')

            ->addOption('reset-well-lock', null, InputOption::VALUE_NONE, 'Resets the well lock.')

            ->addOption('citizen', 'c', InputOption::VALUE_REQUIRED, 'When used together with --reset-well-lock, only the lock of the given citizen is released.', -1)
            ;
    }

    protected function info(Town $town, OutputInterface $output) {
        $output->writeln('<comment>Common town data</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['ID', 'Open?', 'Name', 'Population', 'Type', 'Day'] );
        $table->addRow([
            $town->getId(),
            $town->isOpen(),
            $town->getName(),
            $town->getCitizenCount() . '/' . $town->getPopulation(),
            $town->getType()->getLabel(),
            $town->getDay()
        ]);
        $table->render();
        $output->writeln("\n");

        $output->writeln('<comment>Citizen list</comment>');
        $table = new Table( $output );
        $table->setHeaders( ['UID', 'CID', 'Name', 'Job', 'Alive?','HomeID','InvIDs'] );
        foreach ($town->getCitizens() as $citizen) {
            $table->addRow([
                $citizen->getUser()->getId(),
                $citizen->getId(),
                $citizen->getActive() ? "<comment>{$citizen->getUser()->getUsername()}</comment>" : $citizen->getUser()->getUsername(),
                $citizen->getProfession()->getLabel(),
                (int)$citizen->getAlive(),
                $citizen->getHome()->getId(),
                "<comment>H</comment>:{$citizen->getHome()->getChest()->getId()} <comment>R</comment>:{$citizen->getInventory()->getId()}"
            ]);
        }
        $table->render();

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $town = $this->entityManager->getRepository(Town::class)->find( (int)$input->getArgument('TownID') );
        if (!$town) {
            $output->writeln("<error>The given town ID is not valid.</error>");
            return -1;
        }

        $citizen = null;
        $citizen_id = (int)$input->getOption('citizen');
        if ($citizen_id > 0) {
            $citizen =  $this->entityManager->getRepository(Citizen::class)->find((int)$input->getOption('citizen'));
            if (!$citizen || $citizen->getTown()->getId() !== $town->getId()) {
                $output->writeln("<error>The given citizen ID is not valid.</error>");
                return -1;
            }
        }

        $changes = false;

        if ($input->getOption('reset-well-lock')) {

            /** @var WellCounter[] $wells */
            $wells = array_map( function (Citizen $c): WellCounter {
                return $c->getWellCounter();
            }, $citizen ? [$citizen] : $town->getCitizens()->getValues() );

            $num = 0;
            foreach ($wells as $well) {
                if ($well->getTaken() == 0) continue;
                $well->setTaken(0);
                $this->entityManager->persist($well);
                $num++;
            }
            $changes = $num > 0;
            $output->writeln("<comment>{$num}</comment> well counter/s have been reset.");
        }

        if ($changes) $this->entityManager->flush();

        return $this->info($town, $output);
    }
}