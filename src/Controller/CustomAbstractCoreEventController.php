<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\Game\GameInteractionEvent;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Structures\MyHordesConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class CustomAbstractCoreController
 * @method User getUser
 */
class CustomAbstractCoreEventController extends AbstractController {

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly EventDispatcherInterface $ed,
        protected readonly EventFactory $ef,
    ) { }

    /**
     * @param string|GameInteractionEvent $firstEvent
     * @param string|GameInteractionEvent|string[]|GameInteractionEvent[] $subsequentEvents
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function processEventChain(string|GameInteractionEvent $firstEvent, array|string|GameInteractionEvent $subsequentEvents ): JsonResponse {

        $processedEvents = [];

        if (!is_array($subsequentEvents)) $subsequentEvents = [$subsequentEvents];
        else $subsequentEvents = array_reverse( $subsequentEvents );

        $currentEvent = $firstEvent;
        while ($currentEvent) {

            // Instantiate the event if it is a class name
            if (is_string($currentEvent)) {
                $currentEvent = $this->ef->gameInteractionEvent( $currentEvent );
                // Set up with previous event if one exists
                if (!empty( $processedEvents )) $currentEvent->setup(end($processedEvents));
            }

            // Dispatch and flush
            $this->ed->dispatch( $currentEvent );
            if (!$currentEvent->isPropagationStopped()) $this->ed->dispatch( $currentEvent, GameInteractionEvent::class );
            if ($currentEvent->wasModified() && $currentEvent->shouldPersist()) $this->em->flush();

            // Add event to processed list
            $processedEvents[] = $currentEvent;

            // Fetch the next event
            $currentEvent = $currentEvent->hasError() ? null : array_pop($subsequentEvents);
        }

        // Extract error codes and flash messages from the processed event chain
        list($hasError, $error, $messages) = array_reduce( $processedEvents,
            fn(array $carry, GameInteractionEvent $e) => [
                $carry[0] || $e->hasError(),
                $carry[1] ?? $e->getErrorCode(),
                array_merge( $carry[2], $e->getMessages() )
            ], [false,null,[]] );


        if ($hasError) {
            $error_messages = [];
            foreach ($messages as list($type, $message))
                if ($type === 'error') $error_messages[] = $message;
                else $this->addFlash($type, $message);

            return AjaxResponse::error(empty($error_messages)
                ? ($error ?? ErrorHelper::ErrorInternalError)
                : 'message',[
                'message' => empty($error_messages) ? null : implode('<hr/>', $error_messages)
            ]);
        } else {
            foreach ($messages as list($type,$message))
                $this->addFlash($type, $message);

            return AjaxResponse::success();
        }
    }
}