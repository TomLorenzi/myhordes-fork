<?php


namespace App\EventSubscriber\Deflect;

use App\Annotations\GateKeeperProfile;
use App\Controller\HookedInterfaceController;

/**
 * Sets the _domain_recorded and _domain_incarnated request attribute when the respective properties are enabled on the
 * gatekeeper profile
 * This enables faster processing in subsequent listeners who no longer need to evaluate the entire gatekeeper
 * property
 */
class GameplayDomainSubscriber extends DeflectorCore
{

    const PRIORITY = -81;

    protected function handle(GateKeeperProfile $gateKeeperProfile): void
    {
        $this->event->getRequest()->attributes->set('_domain_recorded', $gateKeeperProfile->getRecordUserActivity());
        $this->event->getRequest()->attributes->set('_domain_incarnated', $gateKeeperProfile->onlyWhenIncarnated());
        if ($gateKeeperProfile->executeHook() && $this->event->getRequest()->attributes->has('_active_controller')) {
            $this->event->getRequest()->attributes->set('_domain_hooked', is_a(
                $this->event->getRequest()->attributes->get('_active_controller', null),
                HookedInterfaceController::class
            ));
        }
    }
}