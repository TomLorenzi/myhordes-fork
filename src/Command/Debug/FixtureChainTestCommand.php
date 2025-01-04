<?php


namespace App\Command\Debug;

use Adbar\Dot;
use ArrayHelpers\Arr;
use MyHordes\Plugins\Interfaces\FixtureChainInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[AsCommand(
    name: 'app:debug:fixtures',
    description: 'Processes a fixture chain and dumps the result.'
)]
class FixtureChainTestCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Processes a fixture chain.')
            ->addArgument('chain', InputArgument::REQUIRED, 'The chain generator class')
            ->addOption('path', 'p',InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limits the output to certain paths')
            ->addOption('flat', null,InputOption::VALUE_NONE, 'Outputs using dot notation.')
            ->addOption('keep-structure', null,InputOption::VALUE_NONE, 'Keeps full structure of the array intact when using paths')
        ;
    }

    protected function print(InputInterface $input, OutputInterface $output, array $data, string $title = 'Output'): void {
        $output->writeln("<fg=green;bg=gray> ### $title ### </>");

        if ($input->getOption('flat')) {
            $dot = new Dot($data);
            $data = $dot->flatten();
            ksort($data);
        }

        $rendered = print_r($data, true);
        $output->writeln( $rendered );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var FixtureChainInterface $chain */
        /** @noinspection MissingService */
        $chain = $this->container->get("MyHordes\\Plugins\\Fixtures\\{$input->getArgument('chain')}" );
        $data = $chain->data();

        $paths = $input->getOption('path');
        $structure = $input->getOption('keep-structure');

        if (empty($paths))
            $this->print($input, $output, $data);
        elseif ($structure) {
            $data = array_filter(
                (new Dot($data))->flatten(),
                fn($value, string $key) => array_reduce($paths, fn ($carry, $path) => $carry || str_starts_with($key, "{$path}."), false),
                ARRAY_FILTER_USE_BOTH
            );
            $this->print($input, $output, (new Dot($data, true))->all());
        } else
            foreach ($paths as $path)
                $this->print($input, $output, Arr::get( $data, $path ), $path);

        return 0;
    }
}
