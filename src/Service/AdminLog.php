<?php


namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AdminLog
{
    private LoggerInterface $logger;
    private Security $security;
    private bool $invoked = false;


    public function __construct( LoggerInterface $logger, Security $security)
    {
        $this->logger = $logger;
        $this->security = $security;
    }

    public function has_been_invoked(): bool {
        return $this->invoked;
    }

    public function invoke(string $message, array $context = []): void {
        $this->invoked = true;

        /** @var User $user */
        $user = $this->security->getUser();

        $role = '[DEFAULT]';
        if     ($user->getRightsElevation() >= User::USER_LEVEL_SUPER) $role = '[<fg=#f7ff0e>SUPER</fg=#f7ff0e>]';
        elseif ($user->getRightsElevation() >= User::USER_LEVEL_ADMIN) $role = '[<fg=#00d27a>ADMIN</fg=#00d27a>]';
        elseif ($user->getRightsElevation() >= User::USER_LEVEL_CROW)  $role = '[<fg=#ff0000>CROW</fg=#ff0000>]';

        $this->logger->info( "$role {$user->getName()} ({$user->getId()}): $message", $context );
    }
}