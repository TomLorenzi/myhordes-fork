<?php


namespace App\Command\Info;


use App\Command\LanguageCommand;
use App\Structures\IdentifierSemantic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveCommand extends LanguageCommand
{
    protected static $defaultName = 'app:info:resolve';

    protected function configure()
    {
        $this
            ->setDescription('Resolves a given identifier.')
            ->setHelp('This command resolves a given identifier.')
            ->addArgument('Identifier', InputArgument::REQUIRED, 'The identifier')

            ->addOption('as',   null, InputOption::VALUE_REQUIRED, 'Expected class')
            ->addOption('hint', null, InputOption::VALUE_REQUIRED, 'Expected class')
        ;

        parent::configure();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('Identifier');

        $as = $input->getOption('as') ?? null;
        if ($as !== null && strpos($as, 'App\Entity\\') === false) $as = 'App\Entity\\' . $as;

        $result = $as ? $this->helper->resolve_as($id, $as, $input->getOption('hint') ?? null) : $this->helper->resolve($id);

        $print = function (array $matches, string $label) use ($output,$result) {
            if (empty($matches)) return;

            $output->writeln("<info>$label</info>");
            foreach ($matches as $match)
                $output->writeln("\t" . implode(', ', [
                    $this->helper->printObject($result->getMatchedObject($match)),
                    "[$match]",
                    "<comment>matched by '{$result->getMatchedProperty($match)}'</comment>"
                ]));

            $output->writeln("");
        };

        $print($result->getMatches( IdentifierSemantic::LikelyMatch, true ), 'Likely Exact Matches');
        $print($result->getMatches( IdentifierSemantic::PerfectMatch, true ), 'Exact Matches');
        $print($result->getMatches( IdentifierSemantic::StrongMatch, true ), 'Strong Matches');
        $print($result->getMatches( IdentifierSemantic::WeakMatch, true ), 'Weak Matches');
        $print($result->getMatches( IdentifierSemantic::GuessMatch, true ), 'Guessed Matches');

        return 0;
    }
}
