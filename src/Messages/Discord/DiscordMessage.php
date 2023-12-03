<?php

namespace App\Messages\Discord;

use App\Messages\AsyncMessageInterface;
use DiscordWebhooks\Client;

readonly class DiscordMessage implements AsyncMessageInterface
{
    public function __construct(
        protected Client $client
    ) { }

    public function client(): Client
    {
        return $this->client;
    }
}