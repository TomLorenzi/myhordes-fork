<?php

namespace App\Service\Actions\Mercure;

use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class BroadcastAnnouncementUpdateViaMercureAction
{
    public function __construct(
        private BroadcastViaMercureAction $mercure
    ) { }

    public function __invoke(string $language, int $newMessages = 1): ?string
    {
        return ($this->mercure)('domains.pm.new', ['number' => $newMessages, 'language' => $language], public: true);
    }
}