<?php

namespace App\Traits\Controller;


use App\Event\Game\GameInteractionEvent;
use App\Response\AjaxResponse;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

trait EventChainProcessor
{
    abstract protected function addFlash(string $type, mixed $message): void;

    /**
     * @param EventFactory $ef
     * @param EventDispatcherInterface $ed
     * @param EntityManagerInterface $em
     * @param string|GameInteractionEvent $firstEvent
     * @param string|GameInteractionEvent|string[]|GameInteractionEvent[] $subsequentEvents
     * @return int|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function processEventChainUsing(
        EventFactory $ef, EventDispatcherInterface $ed, EntityManagerInterface $em,
        string|GameInteractionEvent $firstEvent, array|string|GameInteractionEvent $subsequentEvents = [],
        bool $autoFlush = true, ?array &$error_messages = [], ?GameInteractionEvent &$lastEvent = null
    ): ?int {

        $processedEvents = [];

        if (!is_array($subsequentEvents)) $subsequentEvents = [$subsequentEvents];
        else $subsequentEvents = array_reverse( $subsequentEvents );

        $currentEvent = $firstEvent;
        while ($currentEvent) {
            // Instantiate the event if it is a class name
            if (is_string($currentEvent)) {
                $currentEvent = $ef->gameInteractionEvent( $currentEvent );
                // Set up with previous event if one exists
                if (!empty( $processedEvents )) $currentEvent->setup(end($processedEvents));
            }

            // Dispatch and flush
            $ed->dispatch( $currentEvent );
            if (!$currentEvent->isPropagationStopped()) $ed->dispatch( $currentEvent, GameInteractionEvent::class );
            if ($autoFlush && $currentEvent->wasModified() && $currentEvent->shouldPersist()) $em->flush();

            // Add event to processed list
            $processedEvents[] = $currentEvent;

            // Fetch the next event
            $currentEvent = $currentEvent->hasError() ? null : array_pop($subsequentEvents);
        }

        $lastEvent = end($processedEvents);

        // Extract error codes and flash messages from the processed event chain
        list($hasError, $error, $messages) = array_reduce( $processedEvents,
            fn(array $carry, GameInteractionEvent $e) => [
                $carry[0] || $e->hasError(),
                $carry[1] ?? $e->getErrorCode(),
                array_merge( $carry[2], $e->getMessages() )
            ], [false,null,[]] );

        $error_messages = [];
        if ($hasError) {
            foreach ($messages as list($type, $message))
                if ($type === 'error') $error_messages[] = $message;
                else $this->addFlash($type, $message);

            return $error ?? ErrorHelper::ErrorInternalError;
        } else {
            foreach ($messages as list($type,$message))
                $this->addFlash($type, $message);

            return null;
        }
    }
}