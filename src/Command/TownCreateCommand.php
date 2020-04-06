<?php


namespace App\Command;

use App\Entity\TownClass;
use App\Service\GameFactory;
use App\Service\GameValidator;
use App\Service\Locksmith;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class TownCreateCommand extends Command
{
    protected static $defaultName = 'app:create-town';

    private $entityManager;
    private $gameFactory;
    private $gameValidator;

    public function __construct(EntityManagerInterface $em, GameFactory $f, GameValidator $v)
    {
        $this->entityManager = $em;
        $this->gameFactory = $f;
        $this->gameValidator = $v;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates a new town.')
            ->setHelp('This command allows you to create a new, empty town.')

            ->addArgument('townClass', InputArgument::REQUIRED, 'Town type [' . implode(', ', $this->gameValidator->getValidTownTypes()) . ']')
            ->addArgument('citizens', InputArgument::REQUIRED, 'Number of citizens [1 - 40]')
            ->addArgument('lang', InputArgument::OPTIONAL, 'Town language')
            ->addArgument('name', InputArgument::OPTIONAL, 'Town name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(['Town Creator','============','']);

        $town_type     = $input->getArgument('townClass');
        $town_citizens = (int)$input->getArgument('citizens');
        $town_name = $input->getArgument('name');
        $town_lang = $input->getArgument('lang');

        $output->writeln("<info>Creating a new '$town_type' town " . ($town_name === null ? '' : "called '$town_name' ") . "with $town_citizens unlucky inhabitants.</info>");
        $town = $this->gameFactory->createTown($town_name, $town_citizens, $town_type, $town_lang);

        if ($town === null) {
            $output->writeln('<error>Town creation service terminated with an error. Please check if the town parameters are valid.</error>');
            return -1;
        }

        $output->write('Persisting ... ');
        try {
            $this->entityManager->persist( $town );
            $this->entityManager->flush();
        } catch (Exception $e) {
            $output->writeln('<error>Failed!</error>');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return -3;
        }
        $output->writeln('<info>OK!</info>');
        $output->writeln("<comment>Empty town '" . $town->getName() . "' was created successfully!</comment>");

        $table = new Table($output);
        $table->setHeaders(['Class','Property','ID']);
        $table->addRow( ['Town',      'town',      $town->getId()] );
        $table->addRow( ['Inventory', 'town.bank', $town->getBank()->getId()] );

        $table->render();

        return 0;
    }
}