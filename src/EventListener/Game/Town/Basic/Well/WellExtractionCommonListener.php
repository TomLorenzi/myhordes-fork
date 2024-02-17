<?php


namespace App\EventListener\Game\Town\Basic\Well;


use App\Controller\Town\TownController;
use App\Entity\ActionCounter;
use App\Enum\ItemPoisonType;
use App\Event\Game\Town\Basic\Well\WellExtractionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellExtractionExecuteEvent;
use App\Event\Traits\ItemProducerTrait;
use App\Service\BankAntiAbuseService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Structures\TownConf;
use App\Translation\T;
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
            EntityManagerInterface::class,
            LogTemplateHandler::class,
            TranslatorInterface::class,
            ItemFactory::class
        ];
    }

    public function onCheckDefaults( WellExtractionCheckEvent $event ): void {
        $event->allowed_to_take = 1;
        $event->already_taken = $event->citizen->getSpecificActionCounter(ActionCounter::ActionTypeWell)->getCount();
    }

    public function onCheckPumpExtension(WellExtractionCheckEvent $event ): void {
        if ($event->pump_is_built) $event->allowed_to_take += $event->town->getChaos() ? 2 : 1;
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
            $event->pushError( TownController::ErrorWellLimitHit,
                message: $this->container->get(TranslatorInterface::class)->trans(
                    'Du kannst nicht <strong>mehr als {max} Rationen Wasser pro Tag</strong> entnehmen.',
                    ['max' => $event->allowed_to_take],
                    'game'
                )
            )->stopPropagation();
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

    public function onItemTransfer(WellExtractionExecuteEvent $event ): void {
        for ($i = 0; $i < $event->check->trying_to_take; $i++) {
            $item = $this->container->get(ItemFactory::class)->createItem( 'water_#00' );
            if ($event->townConfig->get( TownConf::CONF_MODIFIER_STRANGE_SOIL, false ))
                $item->setPoison( ItemPoisonType::Strange );
            $event->addItem( $item, $event->check->trying_to_take );
        }
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

    public function onFlashMessages(WellExtractionExecuteEvent $event ): void {
        $event->addFlashMessage(
            ($event->check->already_taken + $event->check->trying_to_take) > 1
            ? T::__('Du hast eine weitere {item} genommen. Die anderen Bürger der Stadt wurden informiert. Sei nicht zu gierig...', 'game')
            : T::__('Du hast deine tägliche Ration erhalten: {item}', 'game')
        ,'notice', 'game', ['item' => ItemProducerTrait::class], conditional_success: true);
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
            if (($i + $event->check->already_taken) > 0)
                $ba->increaseBankCount( $event->citizen );

        $em->persist( $event->citizen );
        $em->persist( $event->town );

        $event->markModified();
    }
}