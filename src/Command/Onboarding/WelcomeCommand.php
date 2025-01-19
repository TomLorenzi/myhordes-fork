<?php


namespace App\Command\Onboarding;

use Exception;
use Shivas\VersioningBundle\Service\VersionManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

#[AsCommand(
    name: 'app:onboarding:welcome',
    description: 'Performs initial setup of a new MyHordes installation.'
)]
class WelcomeCommand extends Command
{
    private readonly InputInterface $input;
    private readonly OutputInterface $output;
    private readonly HelperInterface $helper;

    public function __construct(
        private readonly VersionManagerInterface $versionManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Welcome!')

            //->addOption('topic', null, InputOption::VALUE_REQUIRED, 'The topic', 'myhordes://live/concerns/authorized')
            //->addOption('message', null, InputOption::VALUE_REQUIRED, 'The message', 'test')
            //->addOption('data', null, InputOption::VALUE_REQUIRED, 'Data to transmit', '{}')
        ;
    }

    private function task(
        string $name,
        string $command, array $options = [],
        bool $required = true,
    ): bool {
        $command = new ArrayInput([
            ...$options,
            'command' => $command,
        ]);
        $command->setInteractive(false);
        $buffer = new BufferedOutput();

        try {
            $this->output->write("<fg=yellow>❯</> $name ...");
            $returnCode = $this->getApplication()->doRun($command, $buffer);
            if ($returnCode !== Command::SUCCESS) throw new Exception("Command {$command} failed with code {$returnCode}.", $returnCode);
            $this->output->writeln("\r<fg=green>❯</> $name <fg=green>✓  </>");
        } catch (Throwable $e) {
            $this->output->writeln("\r<fg=red>!</> $name <fg=red>FAILED</>");
            $this->output->writeln($buffer->fetch());
            $this->output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            $returnCode = $e->getCode() ?: Command::FAILURE;
        }

        if ($returnCode && $returnCode !== Command::SUCCESS)
            exit(Command::FAILURE);

        return $returnCode === Command::SUCCESS;
    }

    private function step(int $num, string $name, ?string $desc = null): void {
        $this->output->writeln("\n<fg=yellow>Step $num</> $name.");
        if ($desc)
            $this->output->writeln("Let's start with setting up the database structure and initial data needed for MyHordes.");
        $this->output->writeln('');

        if (!$this->helper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion('Continue? <fg=gray>(y/n)</> ', true))
        ) exit(Command::SUCCESS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->helper = $this->getHelper('question');

        $this->output->writeln("<fg=black;bg=white>Welcome to the MyHordes setup process!</>");
        $this->output->writeln("Version: <fg=yellow>{$this->versionManager->getVersion()}</>");

        $this->step( 1, 'Setting up the database',
                     "Let's start with setting up the database structure and initial data needed for MyHordes." );
        $this->task(
            'Creating database',
            'doctrine:database:create', ['--if-not-exists' => true],
        );
        $this->task(
            'Creating database schema',
            'doctrine:schema:update', ['--force' => true],
        );
        $this->task(
            'Writing fixtures',
            'doctrine:fixtures:load', ['--append' => true],
        );
        $this->task(
            'Fixing current patch level',
            'app:migrate', ['-p' => true, '--mark-completed' => true],
        );

        return Command::SUCCESS;
    }

    protected function executeDatabaseSetup(): bool {

    }
}
