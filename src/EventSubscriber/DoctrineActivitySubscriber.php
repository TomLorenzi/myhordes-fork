<?php


namespace App\EventSubscriber;

use App\Service\DoctrineCacheService;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoctrineActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly DoctrineCacheService $cache)
    { }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::onClear
        ];
    }

    public function onClear(): void
    {
        $this->cache->clearAll();
    }
}