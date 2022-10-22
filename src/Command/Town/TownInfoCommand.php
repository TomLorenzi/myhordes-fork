<?php


namespace App\Command\Town;


use App\Entity\Inventory;
use App\Entity\Town;
use App\Entity\TownClass;
use App\Service\GameValidator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'app:town:list',
    description: 'Lists information about towns.'
)]
class TownInfoCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private GameValidator $gameValidator;

    public function __construct(EntityManagerInterface $em, GameValidator $v)
    {
        $this->entityManager = $em;
        $this->gameValidator = $v;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setHelp('This command allows you list towns.')

            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Town type [all, ' . implode(', ', $this->gameValidator->getValidTownTypes()) . '], default is \'all\'');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getOption('type') ?: 'all';
        if ($type !== 'all' && !in_array($type, $this->gameValidator->getValidTownTypes())) {
            $output->writeln('<error>The given town type is invalid. Check the help for a list of valid types.</error>');
            return -1;
        }

        $towns = $type === 'all'
            ? $this->entityManager->getRepository(Town::class)->findAll()
            : $this->entityManager->getRepository(TownClass::class)->findOneByName( $type )->getTowns();

        $table = new Table( $output );
        $table->setHeaders( ['ID', 'Open?', 'Lang', 'Name', 'Population', 'Type', 'Day'] );
        foreach ($towns as $town) {
            $table->addRow([
                $town->getId(),
                $town->isOpen(),
                $town->getLanguage(),
                $town->getName(),
                $town->getCitizenCount() . '/' . $town->getPopulation(),
                $town->getType()->getLabel(),
                $town->getDay()
            ]);
        }
        $table->render();
        $output->writeln('Found a total of <info>' . count($towns) . '</info> towns.');

        return 0;
    }
}