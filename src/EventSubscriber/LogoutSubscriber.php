<?php


namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function removeRememberMeToken(LogoutEvent $event) {
        $event->getResponse()->headers->clearCookie('myhordes_remember_me');
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['removeRememberMeToken',-1],
        ];
    }
}