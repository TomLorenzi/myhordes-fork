<?php


namespace MyHordes\Prime\EventListener\Items;

use App\Entity\ItemPrototype;
use App\Enum\Game\TransferItemOption;
use App\Enum\Game\TransferItemType;
use App\Event\Game\Items\TransferItemEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: TransferItemEvent::class, method: 'onProtectGarland', priority: 95)]
final class PrimeTransferItemListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EntityManagerInterface::class,
            TranslatorInterface::class,
        ];
    }

    public function onProtectGarland( TransferItemEvent $event ): void {
        // If a previous event invocation has already set an error code, cancel execution
        if ($event->hasError()) return;

        // Get transfer options
        $opt_enforce_placement = in_array( TransferItemOption::EnforcePlacement, $event->options );


        if ( !$opt_enforce_placement && $event->item->getPrototype()->getName() === 'xmas_gift_#01' ) {

            // Can't take an installed garland from your own home
            if ( $event->type_from === TransferItemType::Home ) {
                $event->stopPropagation();
                $event->pushError( ErrorHelper::ErrorActionNotAvailable );
                $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du musst die Girlande zuerst <strong>abnehmen</strong>, bevor du sie <strong>mitnehmen</strong> kannst.', [], 'game'), 'error');
            }
            // When stealing a garland, convert it back to an uninstalled garland
            elseif ( $event->type_from === TransferItemType::Steal ) {
                $base_garland = $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneByName('xmas_gift_#00');
                if ($base_garland) $event->item->setPrototype( $base_garland );
            }

        }

        // Can't take an installed garland from your own home
        if (
            !$opt_enforce_placement && $event->item->getPrototype()->getName() === 'xmas_gift_#01' &&
            ( $event->type_from === TransferItemType::Home || $event->type_from === TransferItemType::Steal )
        ) {
            $event->stopPropagation();
            $event->pushError( ErrorHelper::ErrorActionNotAvailable );
            $event->pushMessage($this->getService(TranslatorInterface::class)->trans('Du musst die Girlande zuerst <strong>abnehmen</strong>, bevor du sie <strong>mitnehmen</strong> kannst.', [], 'game'), 'error');
        }
    }
}