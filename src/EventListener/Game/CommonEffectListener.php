<?php

namespace App\EventListener\Game;

use App\Entity\Item;
use App\Event\Game\GameInteractionEvent;
use App\Event\Traits\FlashMessageTrait;
use App\Event\Traits\ItemProducerTrait;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: GameInteractionEvent::class, method: 'onProcessCommonEffects', priority: 0)]
final class CommonEffectListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            ItemFactory::class,
            EventProxyService::class,
            TranslatorInterface::class,
            LogTemplateHandler::class,
        ];
    }

    /**
     * @param GameInteractionEvent|ItemProducerTrait $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @noinspection PhpDocSignatureInspection
     */
    private function processItemProducerTrait(GameInteractionEvent $event): void {

        foreach ($event->getPendingInstances() as $prototype)
            $event->addItem( $this->container->get(ItemFactory::class)->createItem( $prototype ) );

        foreach ($event->getCreatedInstances() as $item)
            if (($error = $this->container->get(EventProxyService::class)->transferItem(
                    $event->citizen, $item, to: $event->citizen->getInventory()
                )) !== InventoryHandler::ErrorNone) {
                $event->pushErrorCode( $error )->cancelPersist()->stopPropagation();
                return;

            } else $event->markModified();

        $event->itemCreationCompleted();
    }

    /**
     * @param GameInteractionEvent|FlashMessageTrait $event
     * @param string[] $traits,
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @noinspection PhpDocSignatureInspection
     */
    private function processFlashMessageTrait(GameInteractionEvent $event, array $traits): void {

        $fun_accumulate_items = function(array $items) {
            $a = [];
            /** @var Item $item */
            foreach ($items as $item) {
                $index = $item::class . ($item->getBroken() ? '::broken' : '');
                if (!isset($a[$index])) $a[$index] = ['item' => $item, 'count' => 1];
                else $a[$index]['count']++;
            }

            return array_values($a);
        };

        foreach ( $event->getFlashMessages() as list( $type, $message, $domain, $args, $conditional_success ) )
            if (!$conditional_success || !$event->hasError())
                $event->pushMessage( $this->container->get(TranslatorInterface::class)->trans(
                    $message,
                    array_map( function(mixed $entry) use (&$event, $traits, &$fun_accumulate_items) {

                        if (is_object( $entry )) return $this->container->get(LogTemplateHandler::class)->wrap(
                            $this->container->get(LogTemplateHandler::class)->iconize( $entry ), 'tool');

                        if (is_string( $entry )) return match ( true ) {

                            $entry === ItemProducerTrait::class && in_array( $entry, $traits ) => implode(
                                ', ',
                                array_map( fn( $sub ) => $this->container->get(LogTemplateHandler::class)->wrap(
                                    $this->container->get(LogTemplateHandler::class)->iconize( $sub ), 'tool')
                                , $fun_accumulate_items( $event->getFinishedInstances() ))
                            ),

                            default => $entry
                        };

                        return $entry;

                    }, $args ),
                    $domain
                ), $type);
    }

    /**
     * @param GameInteractionEvent $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onProcessCommonEffects( GameInteractionEvent $event ): void {
        // Cancel if common effects are disabled
        if (!$event->common || !$event->data) return;


        try {
            $traits = array_keys((new \ReflectionClass($event->data::class))->getTraits());
        } catch (\ReflectionException $e) {
            $traits = [];
        }

        if (in_array( ItemProducerTrait::class, $traits )) $this->processItemProducerTrait( $event );

        if (in_array( FlashMessageTrait::class, $traits )) $this->processFlashMessageTrait( $event, $traits );
    }
}