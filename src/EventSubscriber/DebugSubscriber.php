<?php


namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebugSubscriber implements EventSubscriberInterface
{

    public function onKernelResponse(ResponseEvent $event) {
        if ($event->getRequest()->isXmlHttpRequest())
            $event->getResponse()->headers->set('Symfony-Debug-Toolbar-Replace', 1);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE   => 'onKernelResponse',
        ];
    }
}