<?php


namespace App\Command\Debug;

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
        ;
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
        if (empty($paths)) {
            $output->writeln("<fg=green;bg=gray> ### Output ### </>");
            $rendered = print_r($data, true);
            $output->writeln( $rendered );
        } else foreach ($paths as $path) {
            $output->writeln("<fg=green;bg=gray> ### $path ### </>");
            $rendered = print_r( Arr::get( $data, $path ) , true);
            $output->writeln( $rendered );
        }

        return 0;
    }
}
