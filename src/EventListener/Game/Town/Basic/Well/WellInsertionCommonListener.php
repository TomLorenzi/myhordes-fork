<?php


namespace App\EventListener\Game\Town\Basic\Well;


use App\Controller\Town\TownController;
use App\Entity\ItemPrototype;
use App\Event\Game\Town\Basic\Well\WellInsertionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellInsertionExecuteEvent;
use App\Service\ErrorHelper;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Structures\ItemRequest;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: WellInsertionCheckEvent::class, method: 'onCheckPumpExtension', priority: -10)]
#[AsEventListener(event: WellInsertionCheckEvent::class, method: 'onCheckItems', priority: -20)]

#[AsEventListener(event: WellInsertionExecuteEvent::class, method: 'onSanity', priority: 255)]
#[AsEventListener(event: WellInsertionExecuteEvent::class, method: 'onItemHandling', priority: 100)]
#[AsEventListener(event: WellInsertionExecuteEvent::class, method: 'onLogMessages', priority: 90)]
#[AsEventListener(event: WellInsertionExecuteEvent::class, method: 'onFlashMessages', priority: 80)]
#[AsEventListener(event: WellInsertionExecuteEvent::class, method: 'onUpdateCounters', priority: 70)]
final class WellInsertionCommonListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EventProxyService::class,
            EntityManagerInterface::class,
            LogTemplateHandler::class,
        ];
    }

    public function onCheckPumpExtension(WellInsertionCheckEvent $event ): void {
        if (!$event->pump_is_built)
            $event->pushErrorCode( ErrorHelper::ErrorActionNotAvailable )->stopPropagation();
    }

    /**
     * @param WellInsertionCheckEvent $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onCheckItems( WellInsertionCheckEvent $event ): void {
        $handler = $this->container->get(InventoryHandler::class);

        $items =
            $handler->fetchSpecificItems( $event->citizen->getInventory(), [new ItemRequest('water_#00', poison: false)] ) ?:
            $handler->fetchSpecificItems( $event->citizen->getInventory(), [new ItemRequest('water_can_1_#00', poison: false)] ) ?:
            $handler->fetchSpecificItems( $event->citizen->getInventory(), [new ItemRequest('water_can_2_#00', poison: false)] ) ?:
            $handler->fetchSpecificItems( $event->citizen->getInventory(), [new ItemRequest('water_can_3_#00', poison: false)] ) ?:
            $handler->fetchSpecificItems( $event->citizen->getInventory(), [new ItemRequest('potion_#00', poison: false)] );

        if (empty($items)) $event->pushErrorCode( TownController::ErrorWellNoWater )->stopPropagation();
        else {
            $event->consumable = $items[0];
            $event->water_content = 1;
        }
    }



    public function onSanity(WellInsertionExecuteEvent $event ): void {
        if ($event->check->water_content <= 0) $event->stopPropagation();
    }

    /**
     * @param WellInsertionExecuteEvent $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onItemHandling(WellInsertionExecuteEvent $event ): void {
        if (!$event->check->consumable) return;

        switch ($event->check->consumable->getPrototype()->getName()) {
            case 'water_can_3_#00':
                $event->check->consumable->setPrototype( $this->container->get(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('water_can_2_#00') );
                $this->container->get(EntityManagerInterface::class)->persist( $event->check->consumable );
                break;
            case 'water_can_2_#00':
                $event->check->consumable->setPrototype( $this->container->get(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('water_can_1_#00') );
                $this->container->get(EntityManagerInterface::class)->persist( $event->check->consumable );
                break;
            case 'water_can_1_#00':
                $event->check->consumable->setPrototype( $this->container->get(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('water_can_empty_#00') );
                $this->container->get(EntityManagerInterface::class)->persist( $event->check->consumable );
                break;
            default:
                if (($error = $this->container->get(EventProxyService::class)->transferItem(
                        $event->citizen, $event->check->consumable,
                        $event->check->consumable->getInventory()
                    )) !== InventoryHandler::ErrorNone) $event->pushErrorCode( $error )->stopPropagation();
        }

        $event->markModified();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onLogMessages(WellInsertionExecuteEvent $event ): void {
        $this->container->get(EntityManagerInterface::class)->persist(
            $this->container->get(LogTemplateHandler::class)->wellAdd( $event->citizen, $event->original_prototype, $event->check->water_content )
        );

        $event->markModified();
    }

    public function onFlashMessages(WellInsertionExecuteEvent $event ): void {
        $event->addFlashMessage(
            T::__('Du hast das Wasser aus {item} in den Brunnen geschüttet (<strong>+{water} Einheit(en)</strong>)', 'game'), 'notice',
            'game', ['item' => $event->original_prototype, 'water' => $event->check->water_content]);
    }

    public function onUpdateCounters(WellInsertionExecuteEvent $event ): void {
        $event->town->setWell( $event->town->getWell() + $event->check->water_content );
        $event->markModified();
    }
}