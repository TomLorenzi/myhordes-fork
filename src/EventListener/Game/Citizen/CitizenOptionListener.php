<?php


namespace App\EventListener\Game\Citizen;

use App\Entity\ActionCounter;
use App\Entity\Recipe;
use App\Event\Game\Citizen\CitizenWorkshopOptionsEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\TownHandler;
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
        return [
            TownHandler::class,
            TranslatorInterface::class
        ];
    }

    public function onPopulateWorkshopRecipes( CitizenWorkshopOptionsEvent $event ): void {
        $event->pushOption( Recipe::WorkshopType );
        if ($event->citizen->getProfession()->getName() === "shaman")
            $event->pushOption(
                Recipe::WorkshopTypeShamanSpecific,
                section: $this->getService(TranslatorInterface::class)->trans('Schamanenkreis', [], 'game')
            );

        if ($this->getService(TownHandler::class)->getBuilding( $event->town, 'small_techtable_#00' )) {
            $trans = $this->getService(TranslatorInterface::class);
            $counter = $event->citizen->getSpecificActionCounterValue( ActionCounter::ActionTypeSpecialActionTech );
            $event->pushOption(
                Recipe::WorkshopTypeTechSpecific,
                $counter >= 1,
                $event->citizen->getProfession()->getName() == "tech" ? 4 : 6,
                $trans->trans('Techniker-Werkstatt', [], 'buildings'),
                $counter >= 1
                    ? $trans->trans('Du hast die Techniker-Werkbank <strong>heute bereits verwendet</strong>. Komm morgen wieder.', [], 'game')
                    : $trans->trans('<strong>Achtung:</strong> Du kannst die Techniker-Werkbank nur <strong>ein mal pro Tag</strong> nutzen, denke also nach bevor du handelst.', [], 'game'),
            );
        }
    }

}