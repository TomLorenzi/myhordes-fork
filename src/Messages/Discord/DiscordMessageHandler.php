<?php

namespace App\Messages\Discord;

use DiscordWebhooks\Client;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DiscordMessageHandler
{
    /**
     * @throws \Exception
     */
    public function __invoke(DiscordMessage $message): void
    {
        $message->client()->send();
    }
}