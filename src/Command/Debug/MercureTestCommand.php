<?php


namespace App\Command\Debug;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:debug:mercure',
    description: 'Debug command to test the mercure service.'
)]
class MercureTestCommand extends Command
{
    public function __construct(
        private readonly HubInterface $hub
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Debug Mercure.')

            ->addOption('topic', null, InputOption::VALUE_REQUIRED, 'The topic', 'myhordes://live/concerns/authorized')
            ->addOption('message', null, InputOption::VALUE_REQUIRED, 'The message', 'test')
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Data to transmit', '{}')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->hub->publish(new Update(
            topics: $input->getOption('topic'),
            data: json_encode([
                'message' => $input->getOption('message'),
                ...json_decode( $input->getOption('data'), true, flags: JSON_THROW_ON_ERROR )
            ]),
            private: true
        ));

        $output->writeln("Message ID is <fg=yellow>$response</>");
        return 0;
    }
}
