<?php


namespace App\EventSubscriber\Deflect;

use App\Entity\AccountRestriction;
use App\Entity\User;
use App\Annotations\GateKeeperProfile;
use App\Service\UserHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Redirects users with a gameplay ban
 */
class BanDeflectorSubscriber extends DeflectorCore
{
    public function __construct(
        private readonly Security $security,
        private readonly UserHandler $userHandler,
        private readonly UrlGeneratorInterface $generator,
    ){ }

    const PRIORITY = -61;

    protected function handle(GateKeeperProfile $gateKeeperProfile): void
    {
        if ($gateKeeperProfile->onlyWhenIncarnated() && $user = $this->security->getUser())
            /** @var User $user */
            if ($this->userHandler->isRestricted( $user, AccountRestriction::RestrictionGameplay ))
                $this->redirectRequest($this->generator, 'soul_disabled');
    }
}