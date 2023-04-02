<?php


namespace App\EventListener\Game\Town\Basic\Well;


use App\Controller\Town\TownController;
use App\Entity\ActionCounter;
use App\Event\Game\Town\Basic\Well\WellExtractionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellExtractionExecuteEvent;
use App\Service\BankAntiAbuseService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckDefaults', priority: 0)]
#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckPumpExtension', priority: -10)]
#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckWellLevel', priority: -100)]
#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckBanishment', priority: -110)]
#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckAllowedRations', priority: -120)]
#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckBankAntiAbuse', priority: -130)]

#[AsEventListener(event: WellExtractionExecuteEvent::class, method: 'onSanity', priority: 255)]
#[AsEventListener(event: WellExtractionExecuteEvent::class, method: 'onItemTransfer', priority: 100)]
#[AsEventListener(event: WellExtractionExecuteEvent::class, method: 'onLogMessages', priority: 90)]
#[AsEventListener(event: WellExtractionExecuteEvent::class, method: 'onFlashMessages', priority: 80)]
#[AsEventListener(event: WellExtractionExecuteEvent::class, method: 'onUpdateCounters', priority: 70)]
final class WellExtractionCommonListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            BankAntiAbuseService::class,
            TownHandler::class,
            BankAntiAbuseService::class,
            ItemFactory::class,
            InventoryHandler::class,
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            TranslatorInterface::class
        ];
    }

    public function onCheckDefaults( WellExtractionCheckEvent $event ): void {
        $event->allowed_to_take = 1;
        $event->already_taken = $event->citizen->getSpecificActionCounter(ActionCounter::ActionTypeWell)->getCount();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onCheckPumpExtension(WellExtractionCheckEvent $event ): void {
        if ($this->container->get(TownHandler::class)->getBuilding($event->town, 'small_water_#00', true))
            $event->allowed_to_take += $event->town->getChaos() ? 2 : 1;
    }

    public function onCheckWellLevel( WellExtractionCheckEvent $event ): void {
        if ($event->trying_to_take > $event->town->getWell())
            $event->pushErrorCode( TownController::ErrorWellEmpty )->stopPropagation();
    }

    public function onCheckBanishment( WellExtractionCheckEvent $event ): void {
        if ($event->already_taken > 0 && $event->citizen->getBanished())
            $event->pushErrorCode( ErrorHelper::ErrorActionNotAvailableBanished )->stopPropagation();
    }

    public function onCheckAllowedRations( WellExtractionCheckEvent $event ): void {
        if (($event->already_taken + $event->trying_to_take) > $event->allowed_to_take)
            $event->pushErrorCode( TownController::ErrorWellLimitHit )->stopPropagation();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onCheckBankAntiAbuse(WellExtractionCheckEvent $event ): void {
        if ($event->already_taken > 0) {
            $ba = $this->container->get(BankAntiAbuseService::class);
            if (!$ba->allowedToTake( $event->citizen )) {
                $ba->increaseBankCount( $event->citizen );
                $event->markModified()->pushErrorCode( InventoryHandler::ErrorBankLimitHit )->stopPropagation();
            }
        }
    }

    public function onSanity(WellExtractionExecuteEvent $event ): void {
        if ($event->check->trying_to_take <= 0) $event->stopPropagation();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onItemTransfer(WellExtractionExecuteEvent $event ): void {

        $factory = $this->container->get(ItemFactory::class);
        $handler = $this->container->get(InventoryHandler::class);

        for ($i = 0; $i < $event->check->trying_to_take; ++$i) {
            $event->addItem( $item = $factory->createItem( 'water_#00' ) );
            if (($error = $handler->transferItem(
                $event->citizen,
                $item,null, $event->citizen->getInventory()
            )) !== InventoryHandler::ErrorNone) {
                $event->pushErrorCode( $error )->stopPropagation();
            }
        }

        $event->markModified();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onLogMessages(WellExtractionExecuteEvent $event ): void {
        for ($i = 0; $i < $event->check->trying_to_take; ++$i)
            $this->container->get(EntityManagerInterface::class)->persist(
                $this->container->get(LogTemplateHandler::class)->wellLog( $event->citizen, ($event->check->already_taken + $i) >= 1 )
            );

        $event->markModified();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onFlashMessages(WellExtractionExecuteEvent $event ): void {
        $log = $this->container->get(LogTemplateHandler::class);
        $i = 0;
        foreach ($event->created_items as $item)
            if (($event->check->already_taken + ($i++)) >= 1)
                $event->pushMessage( $this->container->get(TranslatorInterface::class)->trans("Du hast eine weitere {item} genommen. Die anderen Bürger der Stadt wurden informiert. Sei nicht zu gierig...",
                                                                                  ['{item}' => $log->wrap($log->iconize($item), 'tool')], 'game') );
            else
                $event->pushMessage( $this->container->get(TranslatorInterface::class)->trans("Du hast deine tägliche Ration erhalten: {item}",
                                                                                              ['{item}' => $log->wrap($log->iconize($item), 'tool')], 'game') );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onUpdateCounters(WellExtractionExecuteEvent $event ): void {
        $ba = $this->container->get(BankAntiAbuseService::class);
        $em = $this->container->get(EntityManagerInterface::class);
        $counter = $event->citizen->getSpecificActionCounter(ActionCounter::ActionTypeWell);

        $em->persist( $counter->increment( $event->check->trying_to_take ) );
        $event->town->setWell( max(0,$event->town->getWell() - $event->check->trying_to_take) );
        for ($i = 0; $i < $event->check->trying_to_take; ++$i)
            $ba->increaseBankCount( $event->citizen );

        $em->persist( $event->citizen );
        $em->persist( $event->town );

        $event->markModified();
    }
}