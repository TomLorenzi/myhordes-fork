<?php


namespace MyHordes\Prime\EventListener\Game\Action;

use App\Entity\ItemGroup;
use App\Enum\ScavengingActionType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\Event\Game\Citizen\CitizenPostDeathEvent;
use App\EventListener\ContainerTypeTrait;
use App\EventListener\Game\Citizen\CitizenDeathListener;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Service\GameProfilerService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: 0)]
final class PrimeItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            EventProxyService::class,
            RandomGenerator::class,
            TranslatorInterface::class,
            ConfMaster::class,
            EntityManagerInterface::class,
            ItemFactory::class,
            GameProfilerService::class,
            InventoryHandler::class,
            TownHandler::class
        ];
    }

    public function onCustomAction( CustomActionProcessorEvent $event ): void {

        $execute_info_cache = $event->execute_info_cache;

        switch ($event->type) {

            // Scavenger building action
            case 10101:

                $trans = $this->getService(TranslatorInterface::class);
                $random = $this->getService(RandomGenerator::class);

                $dig_chance = $this->getService(EventProxyService::class)->citizenQueryDigChance( $event->citizen, null, ScavengingActionType::Dig, $event->townConfig->isNightMode() );

                $item = $random->chance( $dig_chance, 0.1, 0.9 )
                    ? $random->pickItemPrototypeFromGroup(
                        $this->getService(EntityManagerInterface::class)->getRepository(ItemGroup::class)->findOneBy(['name' => 'base_dig']),
                        $event->townConfig,
                        $this->getService(ConfMaster::class)->getCurrentEvents( $event->town )
                    )
                    : null;

                if ($item) {

                    $execute_info_cache['items_spawn'][] = $item;
                    $execute_info_cache['message'][] = $trans->trans( 'Deine Anstrengungen in den Buddelgruben haben sich gelohnt! Du hast folgendes gefunden: {items_spawn}!', [], 'game' );

                    $item_instance = $this->getService(ItemFactory::class)->createItem($item);
                    $this->getService(GameProfilerService::class)->recordItemFound( $item, $event->citizen, method: 'scavenge_town' );
                    $this->getService(InventoryHandler::class)->placeItem( $event->citizen, $item_instance, [ $event->citizen->getInventory() ] );

                } else {
                    $execute_info_cache['message'][] = $trans->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' );
                    if ($event->citizen->hasStatus('drunk'))
                        $execute_info_cache['message'][] = $trans->trans( 'Dein <strong>Trunkenheitszustand</strong> hilft dir wirklich nicht weiter. Das ist nicht gerade einfach, wenn sich alles dreht und du nicht mehr klar siehst.', [], 'game' );
                    if ($event->citizen->hasStatus('wound5'))
                        $execute_info_cache['message'][] = $trans->trans( 'Deine Verletzung am Auge macht dir die Suche nicht gerade leichter.', [], 'game' );
                }

                break;

            // Guard building action (anyone)
            case 10201:
                $cn = $this->getService(TownHandler::class)->getBuilding( $event->citizen->getTown(), 'small_watchmen_#00', true );
                $max = $event->townConfig->get( TownConf::CONF_MODIFIER_GUARDTOWER_MAX, 150 );
                $use = round($event->townConfig->get( TownConf::CONF_MODIFIER_GUARDTOWER_UNIT, 10 ) / 2);

                if ($max <= 0) $max = PHP_INT_MAX;

                if ($cn) {
                    $cn->setTempDefenseBonus(min($max, $cn->getTempDefenseBonus() + $use));
                    $this->getService(EntityManagerInterface::class)->persist($cn);
                }
                break;
        }

        $event->execute_info_cache = $execute_info_cache;
    }

}