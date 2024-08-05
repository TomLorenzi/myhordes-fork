<?php


namespace App\EventListener\Game\Action;

use App\Entity\ItemPrototype;
use App\Enum\ActionHandler\PointType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use App\Translation\T;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: -10)]
final class VariousItemActionListener implements ServiceSubscriberInterface
{
    use ContainerTypeTrait;

    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            TownHandler::class,
            EntityManagerInterface::class,
            CitizenHandler::class,
            RandomGenerator::class,
        ];
    }

    
    
    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {
            // Increase town temp defense for the watchtower
            case 13: {
                $cn = $this->getService(TownHandler::class)->getBuilding( $event->citizen->getTown(), 'small_watchmen_#00', true );
                $max = $event->townConfig->get( TownConf::CONF_MODIFIER_GUARDTOWER_MAX, 150 );
                $use = $event->townConfig->get( TownConf::CONF_MODIFIER_GUARDTOWER_UNIT, 10 );

                if ($max <= 0) $max = PHP_INT_MAX;

                if ($cn) $cn->setTempDefenseBonus(min($max, $cn->getTempDefenseBonus() + $use));
                break;
            }

            // Fill water weapons
            case 14: {

                $trans = [
                    'watergun_empty_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                    'watergun_2_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                    'watergun_1_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_3_#00']),
                    'watergun_opt_empty_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                    'watergun_opt_4_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                    'watergun_opt_3_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                    'watergun_opt_2_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                    'watergun_opt_1_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'watergun_opt_5_#00']),
                    'grenade_empty_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'grenade_#00']),
                    'bgrenade_empty_#00' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'bgrenade_#00']),
                    'kalach_#01' => $this->getService(EntityManagerInterface::class)->getRepository(ItemPrototype::class)->findOneBy(['name' => 'kalach_#00']),
                ];

                $fill_targets = [];
                $filled = [];

                foreach ($event->citizen->getInventory()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()]))
                    $fill_targets[] = $i;
                foreach ($event->citizen->getHome()->getChest()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()]))
                    $fill_targets[] = $i;

                foreach ($fill_targets as $i) {
                    $i->setPrototype($trans[$i->getPrototype()->getName()]);
                    if (!isset($filled[$i->getPrototype()->getId()])) $filled[$i->getPrototype()->getId()] = [$i];
                    else $filled[$i->getPrototype()->getId()][] = $i;
                    $event->cache->addSpawnedItem($i);
                }

                if (empty($filled)) $event->cache->addTag('fail');
                break;
            }

            // Chance to infect in a contaminated zone
            case 22:
                if ($event->townConfig->get(TownConf::CONF_FEATURE_ALL_POISON, false)) {

                    if ($this->getService(RandomGenerator::class)->chance(0.05) && !$this->getService(CitizenHandler::class)->hasStatusEffect($event->citizen, 'infection')) {

                        $inflict = true;
                        if ($this->getService(CitizenHandler::class)->hasStatusEffect($event->citizen, "tg_infect_wtns")) {
                            $inflict = $this->getService(RandomGenerator::class)->chance(0.5);
                            $this->getService(CitizenHandler::class)->removeStatus( $event->citizen, 'tg_infect_wtns' );
                            $event->cache->addMessage(
                                                   $inflict
                                                       ? T::__('Ein Opfer der Großen Seuche zu sein hat dir diesmal nicht viel gebracht... und es sieht nicht gut aus...', "items")
                                                       : T::__('Da hast du wohl Glück gehabt... Als Opfer der Großen Seuche bist du diesmal um eine unangenehme Infektion herumgekommen.', "items"),
                                translationDomain: 'items'
                            );
                        } else {
                            $event->cache->addMessage(T::__("Schlechte Nachrichten, du hättest das nicht herunterschlucken sollen... du hast dir eine Infektion eingefangen!", "items"), translationDomain: 'items');
                        }

                        if ($inflict && $this->getService(CitizenHandler::class)->inflictStatus($event->citizen, 'infection')) {
                            $event->cache->addTag('stat-up');
                            $event->cache->addTag("stat-up-infection");
                        }

                    }

                }
        }
    }

}