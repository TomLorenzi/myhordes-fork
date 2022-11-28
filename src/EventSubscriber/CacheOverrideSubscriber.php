<?php


namespace App\EventSubscriber;

use App\Annotations\Semaphore;
use App\Enum\SemaphoreScope;
use App\Service\Locksmith;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Security\Core\Security;


class CacheOverrideSubscriber implements EventSubscriberInterface
{
    public function response(ResponseEvent $event) {
        /* @var $cache Cache|mixed */
        $cache = $event->getRequest()->attributes->get('_cache');

        if (is_a($cache, Cache::class))
            if ($cache->isPublic()) $event->getResponse()->headers->add([
                AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER => 'true'
            ]);

    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE   => ['response', 0],
        ];
    }
}