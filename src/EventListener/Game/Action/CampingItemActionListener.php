<?php


namespace App\EventListener\Game\Action;

use App\Enum\ActionHandler\PointType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use DateTime;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class CampingItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            CitizenHandler::class
        ];
    }

    
    
    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {

            // Set campingTimer
            case 10: {
                $date = new DateTime();
                $event->citizen->setCampingTimestamp( $date->getTimestamp() );
                $event->citizen->setCampingChance( $this->getService(CitizenHandler::class)->getCampingOdds($event->citizen) );
                $dig_timers = $event->citizen->getDigTimers();
                foreach ($dig_timers as $timer)
                    $timer->setPassive(true);

                break;
            }
            // Reset campingTimer
            case 11:
            {
                $event->citizen->setCampingTimestamp(0);
                $event->citizen->setCampingChance(0);
                break;
            }

        }
    }

}