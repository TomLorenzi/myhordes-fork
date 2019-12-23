<?php


namespace App\Command;


use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class TownInfoCommand extends Command
{
    protected static $defaultName = 'app:towns';

    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
        parent::__construct();
    }

    protected function getValidTownTypes(): array {
        return array_map(function(TownClass $entry) {
            return $entry->getName();
        }, $this->entityManager->getRepository(TownClass::class)->findAll());
    }

    protected function configure()
    {
        $this
            ->setDescription('Lists information about towns.')
            ->setHelp('This command allows you list towns, or get information about a specific town')

            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Town type [all, ' . implode(', ', $this->getValidTownTypes()) . '], default is \'all\'');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type') ?: 'all';
        if ($type !== 'all' && !in_array($type, $this->getValidTownTypes())) {
            $output->writeln('<error>The given town type is invalid. Check the help for a list of valid types.</error>');
            return -1;
        }

        $towns = $type === 'all'
            ? $this->entityManager->getRepository(Town::class)->findAll()
            : $this->entityManager->getRepository(TownClass::class)->findOneByName( $type )->getTowns();

        $table = new Table( $output );
        $table->setHeaders( ['ID', 'Open?', 'Name', 'Population', 'Type', 'Day'] );
        foreach ($towns as $town) {
            $table->addRow([
                $town->getId(),
                count($town->getCitizens()) < $town->getPopulation(),
                $town->getName(),
                count($town->getCitizens()) . '/' . $town->getPopulation(),
                $town->getType()->getLabel(),
                $town->getDay()
            ]);
        }
        $table->render();
        $output->writeln('Found a total of <info>' . count($towns) . '</info> towns.');

        return 0;
    }
}