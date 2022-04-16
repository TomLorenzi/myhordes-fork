<?php


namespace App\Service;

use App\Entity\AdminBan;
use App\Entity\AdminDeletion;
use App\Entity\AdminReport;
use App\Entity\Citizen;
use App\Entity\CauseOfDeath;
use App\Entity\CitizenRankingProxy;
use App\Entity\Forum;
use App\Entity\Picto;
use App\Entity\Post;
use App\Entity\Thread;
use App\Entity\User;
use App\Service\DeathHandler;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

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