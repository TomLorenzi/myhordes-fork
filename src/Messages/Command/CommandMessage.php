<?php

namespace App\Messages\Command;

use App\Messages\AsyncMessageLowInterface;

readonly class CommandMessage implements AsyncMessageLowInterface
{
    public function __construct(
        public string $command
    ) {}
}