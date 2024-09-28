<?php


namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;


use App\Annotations\GateKeeperProfile;

/**
 * Initialized the GateKeeperProfile configuration. If the configuration is not applicable or set to "skip", the
 * configuration is removed from the request to make processing easier for subsequent subscribers.
 */
class GateKeeperInitializationSubscriber implements EventSubscriberInterface
{
    public function initialize(ControllerEvent $event) {

        $gk_profile = $event->getRequest()->attributes->get('_GateKeeperProfile') ?? new GateKeeperProfile();
        if ($gk_profile->skipGateKeeper() || $event->getRequest()->attributes->get('_debug_skip_gk')) {
            $event->getRequest()->attributes->remove('_GateKeeperProfile');
            return;
        }

        $controller = $event->getController();
        if (is_array($controller)) $controller = $controller[0];
        if (!str_starts_with( get_class($controller) ?? '', 'App\\' ) && !str_starts_with( get_class($controller) ?? '', 'MyHordes\\' )) {
            $event->getRequest()->attributes->remove('_GateKeeperProfile');
            return;
        }

        $event->getRequest()->attributes->set('_active_controller', $controller);

        if (!$event->getRequest()->attributes->has('_GateKeeperProfile'))
            $event->getRequest()->attributes->set('_GateKeeperProfile', $gk_profile);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['initialize', -50],
        ];
    }
}