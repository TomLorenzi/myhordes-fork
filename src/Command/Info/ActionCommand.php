<?php


namespace App\Command\Info;

use App\Command\LanguageCommand;
use App\Entity\BuildingPrototype;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\Result;
use App\Entity\ZonePrototype;
use App\Service\RandomGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:info:actions',
    description: 'Dumps action information'
)]
class ActionCommand extends LanguageCommand
{
    private EntityManagerInterface $em;
    private RandomGenerator $rand;

    public function __construct(EntityManagerInterface $em, RandomGenerator $rand)
    {
        $this->em = $em;
        $this->rand = $rand;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('what', InputArgument::REQUIRED, 'What is the source of the action (item, workshop, home)')
            ->addArgument('for',  InputArgument::OPTIONAL, 'What object would you like to know about?')
        ;
        parent::configure();
    }

    protected function executeItemActions(ItemPrototype $item, SymfonyStyle $io): int {
        $io->title("Item Actions for <info>" . $this->translate($item->getLabel(), 'items') . "</info>");

        foreach ($item->getActions() as $action) {
            $io->section('Actions');
            $io->writeln("Action label: " . $this->translate($action->getLabel(), "items"));

            foreach ($action->getResults() as $result) {
                $this->displayActions($result, $io);
            }
        }

        return 0;
    }

    protected function displayActions(Result $result, SymfonyStyle $io) {
        $io->section("Action name: <info>{$result->getName()}</info>");

        if ($result->getResultGroup()) {
            $io->writeln($result->getResultGroup()->getName());
            foreach ($result->getResultGroup()->getEntries() as $entry) {
                foreach ($entry->getResults() as $subResult) {
                    $this->displayActions($subResult, $io);
                }
            }
        }
    }

    protected function getPrincipal( string $class, string $label, InputInterface $input, OutputInterface $output ): object {
        if (!$input->hasArgument('for')) throw new \Exception('Subject required.');
        $resolved = $this->helper->resolve_string( $input->getArgument('for') ?? '', $class, $label, $this->getHelper('question'), $input, $output);
        if (!$resolved) throw new \Exception('Subject invalid.');
        return $resolved;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return match ($input->getArgument('what')) {
            'item' => $this->executeItemActions($this->getPrincipal(ItemPrototype::class, 'Item Prototype', $input, $output), new SymfonyStyle($input, $output)),
            default => throw new \Exception('Unknown topic.'),
        };
    }
}
