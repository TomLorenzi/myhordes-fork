<?php


namespace App\EventSubscriber;

use App\Annotations\Semaphore;
use App\Enum\SemaphoreScope;
use App\Service\Globals\ResponseGlobal;
use App\Service\Locksmith;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockInterface;
use Symfony\Bundle\SecurityBundle\Security;


readonly class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private ResponseGlobal $responseGlobal)
    {    }


    public function attachSignals(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Client-Signals', $this->responseGlobal->getSignals(), false);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE   => ['attachSignals', -100],
        ];
    }
}