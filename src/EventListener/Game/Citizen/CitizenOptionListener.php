<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\Recipe;
use App\Event\Game\Citizen\CitizenWorkshopOptionsEvent;
use App\EventListener\ContainerTypeTrait;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CitizenWorkshopOptionsEvent::class, method: 'onPopulateWorkshopRecipes',  priority: 0)]
final class CitizenOptionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [TranslatorInterface::class];
    }

    public function onPopulateWorkshopRecipes( CitizenWorkshopOptionsEvent $event ): void {
        $event->pushOption( Recipe::WorkshopType );
        if ($event->citizen->getProfession()->getName() === "shaman")
            $event->pushOption(
                Recipe::WorkshopTypeShamanSpecific,
                section: $this->getService(TranslatorInterface::class)->trans('Schamanenkreis', [], 'game')
            );
    }

}