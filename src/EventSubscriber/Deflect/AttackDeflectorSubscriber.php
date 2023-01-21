<?php


namespace App\EventSubscriber\Deflect;

use App\Service\TimeKeeperService;
use App\Annotations\GateKeeperProfile;

/**
 * During the attack, only whitelisted controllers and functions are available. This subscriber uses the
 * GateKeeperProfile annotation to check if a controller is allowed to execute during the nightly attack and terminates
 * requests to unavailable controllers during the attack.
 */
class AttackDeflectorSubscriber extends DeflectorCore
{
    public function __construct(
        private readonly TimeKeeperService $timeKeeper
    ){ }

    const PRIORITY = -60;

    protected function handle(GateKeeperProfile $gateKeeperProfile): void
    {
        if (!$gateKeeperProfile->getAllowDuringAttack() && $this->timeKeeper->isDuringAttack())
            $this->ajaxReset();
    }
}