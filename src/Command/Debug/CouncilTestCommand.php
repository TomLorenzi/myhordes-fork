<?php


namespace App\Command\Debug;

use App\Entity\Citizen;
use App\Entity\CouncilEntry;
use App\Entity\CouncilEntryTemplate;
use App\Entity\Town;
use App\Service\CommandHelper;
use App\Service\GazetteService;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\KernelInterface;

class CouncilTestCommand extends Command
{
    protected static $defaultName = 'app:debug:council';

    private KernelInterface $kernel;
    private CommandHelper $helper;
    private Translator $trans;
    private RandomGenerator $rand;
    private EntityManagerInterface $entity_manager;
    private GazetteService $gazette_service;

    public function __construct(KernelInterface $kernel, Translator $translator, CommandHelper $helper,
                                RandomGenerator $rand, GazetteService $gazetteService, EntityManagerInterface $em)
    {
        $this->kernel = $kernel;

        $this->trans = $translator;
        $this->helper = $helper;
        $this->rand = $rand;
        $this->entity_manager = $em;
        $this->gazette_service = $gazetteService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Tests the city council')
            ->setHelp('Debug CityCouncil.')

            ->addArgument('TownID', InputArgument::REQUIRED, 'The town ID')
            ->addArgument('CouncilRootNodeID', InputArgument::REQUIRED, 'Council Root Node ID')

            ->addOption('clear-previous', null, InputOption::VALUE_REQUIRED, 'Clears all existing council texts before generating new ones.', false)
            ->addOption('count-voted', null, InputOption::VALUE_REQUIRED, 'Number of citizens that received votes. Excludes the winner. Only valid for election councils.', 6)
            ->addOption('structure', null, InputOption::VALUE_NONE, 'Show empty structural nodes')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output) {
        $valid_nodes = array_filter( (new ReflectionClass(CouncilEntryTemplate::class))->getConstants(), function($value, $key) {
            return !($value < 990000 || $value > 999999 || !str_starts_with( $key, 'CouncilNodeRoot' ));
        }, ARRAY_FILTER_USE_BOTH );

        $node = $input->getArgument('CouncilRootNodeID');
        if ($node === null)
            $output->writeln('<comment>A root node ID is required to generate a council conversation.</comment>');
        elseif (!in_array((int)$node, $valid_nodes)) {
            $output->writeln('<comment>The provided root node ID does not seem to resolve to a valid council root node.</comment>');
            $node = null;
        }

        if ($node === null) {
            $helper = $this->getHelper('question');
            $q = new ChoiceQuestion('Please select a council root node: ', array_keys($valid_nodes));
            $input->setArgument('CouncilRootNodeID', $valid_nodes[$helper->ask($input,$output, $q)]);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Town $town */
        $town = $this->helper->resolve_string($input->getArgument('TownID'), Town::class, 'Town', $this->getHelper('question'), $input, $output);
        if (!$town) {
            $output->writeln("<error>The given town ID is not valid.</error>");
            return -1;
        }

        $this->trans->setLocale($town->getLanguage() ?? 'de');
        $node = (int)$input->getArgument('CouncilRootNodeID');

        $partition = [];
        $all_citizens = array_filter( $town->getCitizens()->getValues(), fn(Citizen $c) => $c->getAlive() );

        if ((int)$input->getOption('clear-previous')) {
            foreach ($this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()]) as $existing)
                $this->entity_manager->remove($existing);
            $this->entity_manager->flush();
        }

        switch ($node) {
            case CouncilEntryTemplate::CouncilNodeRootGuideFirst: case CouncilEntryTemplate::CouncilNodeRootGuideNext:
            case CouncilEntryTemplate::CouncilNodeRootShamanFirst: case CouncilEntryTemplate::CouncilNodeRootShamanNext:
                $partition['_mc']      = $this->rand->draw( $all_citizens, 1, true );
                $partition['_winner']  = $this->rand->draw( $all_citizens, 1, true );
                $partition['voted']   = $this->rand->draw( $all_citizens, (int)$input->getOption('count-voted'), true );
                $partition['_council?'] = $all_citizens;

                break;
            default:
                throw new Exception('No partition generator for the given root node ID was found. Cannot proceed.');
        }

        $output->writeln('Using the following partition:');
        foreach ($partition as $key => $list) {
            $output->writeln("<info>$key</info>");
            foreach ($list as $citizen)
                $output->writeln("\t<comment>{$citizen->getName()}</comment>");
        }
        $output->writeln('');

        $output->write('Now generating the council... ');
        $this->gazette_service->generateCouncilNodeList($town, $town->getDay(), $node, $partition);
        $this->entity_manager->flush();
        $output->writeln("<info>OK!</info>");
        $output->writeln('');

        $output->writeln('Current council:');
        foreach ($this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()], ['ord' => 'ASC']) as $councilEntry)
            if ($councilEntry->getTemplate()->getText() === null) {
                if ($input->getOption('structure'))
                    $output->writeln("<comment>[Empty Semantic Context Stack] [{$councilEntry->getTemplate()->getSemantic()}] [{$councilEntry->getTemplate()->getName()}]</comment>");
            } else {
                if ($councilEntry->getCitizen() && $councilEntry->getTemplate()->getVocal())
                    $output->write("<info>{$councilEntry->getCitizen()->getName()}:</info> ");
                $output->writeln( strip_tags( $this->gazette_service->parseCouncilLog( $councilEntry ) ) );
            }

        return 0;
    }
}
