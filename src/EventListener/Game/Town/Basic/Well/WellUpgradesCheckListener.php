<?php


namespace App\EventListener\Game\Town\Basic\Well;


use App\Controller\Town\TownController;
use App\Entity\ActionCounter;
use App\Event\Game\Town\Basic\Well\WellExtractionCheckEvent;
use App\Event\Game\Town\Basic\Well\WellExtractionExecuteEvent;
use App\Event\Game\Town\Basic\Well\WellInsertionCheckEvent;
use App\Event\Traits\ItemProducerTrait;
use App\Service\BankAntiAbuseService;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use App\Service\TownHandler;
use App\Translation\T;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: WellExtractionCheckEvent::class, method: 'onCheckWellExtensions', priority: -5)]
#[AsEventListener(event: WellInsertionCheckEvent::class, method: 'onCheckWellExtensions', priority: -5)]
final class WellUpgradesCheckListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TownHandler::class,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function onCheckWellExtensions(WellExtractionCheckEvent|WellInsertionCheckEvent $event ): void {
        $event->pump_is_built = (bool)$this->container->get(TownHandler::class)->getBuilding($event->town, 'small_water_#00', true);
    }
}