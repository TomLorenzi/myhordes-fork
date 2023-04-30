<?php


namespace App\EventSubscriber;

use App\Annotations\Toaster;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class ToastSubscriber implements EventSubscriberInterface
{
    public function checkToast(ControllerEvent $event): void
    {

        /** @var Toaster $toast */
        $toast = $event->getRequest()->attributes->get('_Toaster', null);
        if ($toast === null) return;

        $sent_key = $event->getRequest()->headers->get('X-Toaster', '' );
        $needed_key = $event->getRequest()->getSession()->get('token');

        if ($needed_key === null) return;

        if (!$toast->fullSecurity()) {
            $sent_key = substr( $sent_key, 0, 8 );
            $needed_key = substr( $needed_key, 0, 8 );
        }

        if ($sent_key !== $needed_key) {
            $event->stopPropagation();
            $event->setController( fn() => new Response("TOAST FAILURE.", 400) );
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['checkToast', -30]
        ];
    }
}