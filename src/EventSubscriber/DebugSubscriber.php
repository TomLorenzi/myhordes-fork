<?php


namespace App\EventSubscriber;

use App\Annotations\GateKeeperProfile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebugSubscriber implements EventSubscriberInterface
{

    public function onKernelResponse(ResponseEvent $event) {
        if ($event->getRequest()->isXmlHttpRequest())
            $event->getResponse()->headers->set('Symfony-Debug-Toolbar-Replace', 1);
    }

    public function onKernelController(ControllerEvent $event) {
        $controller = is_array($event->getController()) ? $event->getController()[0] : $event->getController();
        if (str_starts_with(get_class($controller), 'Symfony\Bundle\WebProfilerBundle\Controller') )
            $event->getRequest()->attributes->set( '_debug_skip_gk', true );

    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse',
        ];
    }
}