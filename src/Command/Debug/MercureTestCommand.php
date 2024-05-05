<?php


namespace App\Command\Debug;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

            ->addArgument('topic', InputArgument::OPTIONAL, 'The topic', 'myhordes://live/concerns/authorized')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->hub->publish(new Update(
            topics: $input->getArgument('topic'),
            data: json_encode(['data' => 'MercureTest']),
            private: true
        ));

        $output->writeln("Message ID is <fg=yellow>$response</>");
        return 0;
    }
}
