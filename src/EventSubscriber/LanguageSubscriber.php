<?php


namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LanguageSubscriber implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event) {
        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $event->getRequest()->attributes->get('_locale')) {
            $event->getRequest()->getSession()->set('_locale', $locale);
        } elseif ($event->getRequest()->getSession()->has('_user_lang')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_user_lang', null));
        } elseif ($event->getRequest()->getSession()->has('_town_lang')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_town_lang', null));
        } elseif ($event->getRequest()->getSession()->has('_locale')) {
            $event->getRequest()->setLocale($event->getRequest()->getSession()->get('_locale', null));
        } elseif ($langs = $event->getRequest()->getLanguages()) {
            $event->getRequest()->setLocale( $langs[0] );
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}