<?php


namespace App\EventListener\Game\Action;

use App\Enum\ActionHandler\PointType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\LogTemplateHandler;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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
            EntityManagerInterface::class,
            LogTemplateHandler::class,
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

                // Remove citizen from escort
                foreach ($event->citizen->getLeadingEscorts() as $escorted_citizen) {
                    $escorted_citizen->getCitizen()->getEscortSettings()->setLeader( null );
                    $this->getService(EntityManagerInterface::class)->persist($escorted_citizen);
                }

                if ($event->citizen->getEscortSettings()) $this->getService(EntityManagerInterface::class)->remove($event->citizen->getEscortSettings());
                $event->citizen->setEscortSettings(null);

                $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->beyondCampingHide($event->citizen));

                break;
            }
            // Reset campingTimer
            case 11:
            {
                $event->citizen->setCampingTimestamp(0);
                $event->citizen->setCampingChance(0);
                $this->getService(EntityManagerInterface::class)->persist($this->getService(LogTemplateHandler::class)->beyondCampingUnhide($event->citizen));
                break;
            }

        }
    }

}