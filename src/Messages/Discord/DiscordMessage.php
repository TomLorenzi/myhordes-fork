<?php

namespace App\Messages\Discord;

use App\Messages\AsyncMessageInterface;
use DiscordWebhooks\Client;

class DiscordMessage implements AsyncMessageInterface
{
    public function __construct(
        protected readonly Client $client
    ) { }

    public function client(): Client
    {
        return $this->client;
    }
}