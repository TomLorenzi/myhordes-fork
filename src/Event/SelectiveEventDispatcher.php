<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Debug\WrappedListener;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SelectiveEventDispatcher extends EventDispatcher
{
    public function callListeners(iterable $listeners, string $eventName, object $event): void
    {
        // Implement our own logic for Event types
        if (is_a($event, Event::class))
            foreach ($listeners as $listener) {
                // If we're getting a wrapped listener in the dev environment, it must be unwrapped first
                $unwrapped = is_a($listener, WrappedListener::class) ? $listener->getWrappedListener() : $listener;

                // Extract class and method name from the listener
                // This can only work if the listener is an array, not if it is a callable
                [$class, $method] = match (true) {
                    is_array($unwrapped) => [is_object($unwrapped[0]) ? get_class($unwrapped[0]) : $unwrapped[0], $unwrapped[1]],
                    default => ['_undef','_undef']
                };

                // Only call the listener if it is not blacklisted by the event
                if ($event->shouldPropagateTo($class,$method)) $listener($event, $eventName, $this);
            }
        // For all other events, fall back to the base implementation
        else parent::callListeners($listeners, $eventName, $event);
    }
}