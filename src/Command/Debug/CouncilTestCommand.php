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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:debug:council',
    description: 'Debug command to test the council dialog generator.'
)]
class CouncilTestCommand extends Command
{
    private CommandHelper $helper;
    private Translator $trans;
    private RandomGenerator $rand;
    private EntityManagerInterface $entity_manager;
    private GazetteService $gazette_service;

    public function __construct(Translator $translator, CommandHelper $helper,
                                RandomGenerator $rand, GazetteService $gazetteService, EntityManagerInterface $em)
    {
        $this->trans = $translator;
        $this->helper = $helper;
        $this->rand = $rand;
        $this->entity_manager = $em;
        $this->gazette_service = $gazetteService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Debug CityCouncil.')

            ->addArgument('TownID', InputArgument::REQUIRED, 'The town ID')
            ->addArgument('CouncilRootNodeID', InputArgument::REQUIRED, 'Council Root Node ID')

            ->addOption('clear-previous', null, InputOption::VALUE_REQUIRED, 'Clears all existing council texts before generating new ones.', false)

            ->addOption('count-voted',      null, InputOption::VALUE_REQUIRED, 'Number of citizens that received votes. Excludes the winner. Only valid for election councils.', 6)
            ->addOption('count-discussion', null, InputOption::VALUE_REQUIRED, 'Number of citizens that partake in the discussion. Only valid for election councils.', 999)
            ->addOption('no-mc', null, InputOption::VALUE_REQUIRED, 'If set to any value other than 0, no MC will be generated.', 0)
            ->addOption('same-mc', null, InputOption::VALUE_REQUIRED, 'If set to any value other than 0, the generator will attempt to select the same citizen as MC that was selected before. Has no effect if clear-previous or no-mc are enabled.', 0)

            ->addOption('structure', null, InputOption::VALUE_NONE, 'Show empty structural nodes')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void {
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

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        $previous_mc = null;

        if ((int)$input->getOption('clear-previous')) {
            foreach ($this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()]) as $existing)
                $this->entity_manager->remove($existing);
            $this->entity_manager->flush();
        } else
            foreach ($this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()], ['ord' => 'ASC']) as $existing)
                if ($existing->getTemplate() && $existing->getTemplate()->getSemantic() === CouncilEntryTemplate::CouncilNodeGenericMCIntro)
                    $previous_mc = $existing->getCitizen();

        $flags = [];
        switch ($node) {
            case CouncilEntryTemplate::CouncilNodeRootGuideFirst:  case CouncilEntryTemplate::CouncilNodeRootGuideNext:
            case CouncilEntryTemplate::CouncilNodeRootShamanFirst: case CouncilEntryTemplate::CouncilNodeRootShamanNext:
            case CouncilEntryTemplate::CouncilNodeRootShamanFew:   // case CouncilEntryTemplate::CouncilNodeRootShamanFew:
                if ($previous_mc && in_array( $previous_mc, $all_citizens ) && !(int)$input->getOption('no-mc') && (int)$input->getOption('same-mc')) {
                    $partition['_mc'] = [$previous_mc];
                    $all_citizens = array_filter($all_citizens, fn(Citizen $c) => $c !== $previous_mc);
                    $flags['same_mc'] = true;
                } else $partition['_mc'] = (int)$input->getOption('no-mc') ? [] : $this->rand->draw( $all_citizens, 1, true );
                $partition['_winner']   = $this->rand->draw( $all_citizens, 1, true );
                $partition['voted']     = $this->rand->draw( $all_citizens, (int)$input->getOption('count-voted'), true );
                $partition['_council?'] = array_slice($all_citizens, 0, (int)$input->getOption('count-discussion'));
                break;

            case CouncilEntryTemplate::CouncilNodeRootGuideSingle: case CouncilEntryTemplate::CouncilNodeRootShamanSingle:
                $partition['_winner'] = $this->rand->draw( $all_citizens, 1, true );
                break;

            case CouncilEntryTemplate::CouncilNodeRootGuideNone: case CouncilEntryTemplate::CouncilNodeRootShamanNone:
                break;

            default:
                throw new Exception('No partition generator for the given root node ID was found. Cannot proceed.');
        }

        $output->writeln('Using the following partition:');
        foreach ($partition as $key => $list) {
            $output->writeln("<info>$key</info>");
            $output->writeln( implode(', ', array_map( fn(Citizen $c) => "<comment>{$c->getName()}</comment>", $list ) ) );
        }
        $output->writeln('');

        $output->writeln('Using the following flags:');
        foreach ($flags as $key => $value)
            $output->writeln("<info>$key</info> -> <comment>" . ($value ? 'Y' : 'N') . '</comment>');

        $output->writeln('');

        $output->write('Now generating the council... ');
        $this->gazette_service->generateCouncilNodeList($town, $town->getDay(), $node, $partition, $flags);
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
