<?php


namespace App\EventSubscriber;

use App\Entity\User;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class AjaxSessionStateSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function addSessionHeaders(ResponseEvent $event) {
        if (!$event->getRequest()->isXmlHttpRequest()) return;

        /** @var User $user */
        $user = $this->security->getUser();
        $citizen = $user?->getActiveCitizen();
        $town = $citizen?->getTown();

        $event->getResponse()->headers->add([
            'X-Session-Domain' => ($user?->getId()??'0') . ':' . ($citizen?->getId()??'0') . ':' . ($town?->getDay()??'0') . ':' . ($citizen?->getZone() ?'1':'0')
        ]);
    }

    /**
     * @inheritDoc
     */
    #[ArrayShape([KernelEvents::CONTROLLER_ARGUMENTS => "array", KernelEvents::RESPONSE => "array"])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE   => ['addSessionHeaders', -20],
        ];
    }
}