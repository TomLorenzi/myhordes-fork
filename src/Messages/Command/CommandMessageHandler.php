<?php

namespace App\Messages\Command;

use App\Service\CommandHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CommandMessageHandler
{
    public function __construct(
        private CommandHelper $helper
    ) {}

    /**
     * @throws \Exception
     */
    public function __invoke(CommandMessage $message): void
    {
        if (!$this->helper->capsule( $message->command, new NullOutput() ))
            throw new \Exception("Command execution failed: {$message->command}");
    }
}