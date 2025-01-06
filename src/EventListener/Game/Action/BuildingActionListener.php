<?php


namespace App\EventListener\Game\Action;

use App\Entity\ActionCounter;
use App\Entity\CitizenWatch;
use App\Entity\ItemGroup;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Enum\ActionHandler\CountType;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\ScavengingActionType;
use App\Enum\ZoneActivityMarkerType;
use App\Event\Game\Actions\CustomActionProcessorEvent;
use App\EventListener\ContainerTypeTrait;
use App\Service\ConfMaster;
use App\Service\EventProxyService;
use App\Service\GameProfilerService;
use App\Service\ItemFactory;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use App\Translation\T;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

#[AsEventListener(event: CustomActionProcessorEvent::class, method: 'onCustomAction',  priority: 0)]
final class BuildingActionListener implements ServiceSubscriberInterface
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
            ConfMaster::class,
            EntityManagerInterface::class,
            ItemFactory::class,
            GameProfilerService::class,
            TownHandler::class,
            LogTemplateHandler::class,
        ];
    }

    public function onCustomAction( CustomActionProcessorEvent $event ): void {
        switch ($event->type) {
            // Pool
            case 100:
                $counter = $event->citizen->getSpecificActionCounter( ActionCounter::ActionTypePool );
                $previousWatches = $this->getService(EntityManagerInterface::class)->getRepository(CitizenWatch::class)->matching((new Criteria())
                    ->andWhere(Criteria::expr()->eq('citizen', $event->citizen))
                    ->andWhere(Criteria::expr()->lt('day', $event->town->getDay()))
                )->count() + 2;

                if ($event->citizen->property( CitizenProperties::EnableProWatchman ))
                    $watchMap = [0, 1, 4, 9, 15, 20, 30, 40, 50, 60, 75, 90];
                else
                    $watchMap = [0, 1, 4, 9, 20, 30, 42, 56, 72, 90];

                $limit = $watchMap[min($previousWatches, count($watchMap))];

                if ($limit > $counter->getCount())
                    $this->getService(EntityManagerInterface::class)->persist( $counter->increment() );

                break;

            // Scavenger building action
            case 10101:
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

                    $event->cache->addSpawnedItem($item);
                    $event->cache->addMessage(T::__( 'Deine Anstrengungen in den Buddelgruben haben sich gelohnt! Du hast folgendes gefunden: {items_spawn}!', 'game' ));

                    $item_instance = $this->getService(ItemFactory::class)->createItem($item);
                    $this->getService(GameProfilerService::class)->recordItemFound( $item, $event->citizen, method: 'scavenge_town' );
                    $this->getService(EventProxyService::class)->placeItem( $event->citizen, $item_instance, [ $event->citizen->getInventory(), $event->citizen->getHome()->getChest(), $event->town->getBank() ] );

                } else {
                    $event->cache->addMessage(T::__( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', 'game' ));
                    if ($event->citizen->hasStatus('drunk'))
                        $event->cache->addMessage(T::__( 'Dein <strong>Trunkenheitszustand</strong> hilft dir wirklich nicht weiter. Das ist nicht gerade einfach, wenn sich alles dreht und du nicht mehr klar siehst.', 'game' ));
                    if ($event->citizen->hasStatus('wound5'))
                        $event->cache->addMessage(T::__( 'Deine Verletzung am Auge macht dir die Suche nicht gerade leichter.', 'game' ));
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

            // Survivalist building action
            case 10301: case 10302:
                $random = $this->getService(RandomGenerator::class);

                $event->cache->addToCounter( CountType::Well, $water_count = $random->pickEntryFromRawRandomArray( $event->type === 10301
                    ? [ [1], [2], [3] ]
                    : [ [0,25], [1,75] ]
                ) );

                if ($water_count > 0) {
                    $event->town->setWell($event->town->getWell() + $water_count);
                    $this->getService(EntityManagerInterface::class)->persist(
                        $this->getService(LogTemplateHandler::class)->wellAdd( $event->citizen, count: $water_count )
                    );
                    $event->cache->addMessage(T::__( 'Zur "Freude" deiner Mitbürger ist es dir gelungen, den Brunnen um {well} Rationen Wasser aufzufüllen!', 'game' ));
                } else $event->cache->addMessage(T::__( 'Leider ist es dir nicht gelungen, dem Brunnen eine substantielle Menge an Wasser hinzuzufügen ...', 'game' ));

                break;

            // OLD Scout building action
            case 10401:

                $random = $this->getService(RandomGenerator::class);
                $em = $this->getService(EntityManagerInterface::class);

                $event->cache->addTranslationKey('casino', $zones_count = mt_rand(3, 6));
                $close_zones_count = mt_rand( floor($zones_count / 2.0), $zones_count );
                $distant_zones_count = $zones_count - $close_zones_count;

                $potential_zones = array_filter( $event->town->getZones()->getValues(), fn(Zone $zone) => $zone->getScoutLevel() < 3 );
                $close_zones = array_filter( $potential_zones, fn(Zone $zone) => $zone->getDistance() <= 11 );
                $distant_zones = array_filter( $potential_zones, fn(Zone $zone) => $zone->getDistance() > 11 );

                $zones = array_merge(
                    $random->pick( $close_zones, $close_zones_count, true ),
                    $random->pick( $distant_zones, $distant_zones_count, true ),
                );

                foreach ($zones as $zone) {
                    /** @var Zone $zone */
                    $zone->addActivityMarker((new ZoneActivityMarker())
                                                 ->setCitizen($event->citizen)
                                                 ->setTimestamp(new DateTime())
                                                 ->setType(ZoneActivityMarkerType::ScoutMarker)
                    );
                    $em->persist($zone);
                }

                $markings_visible = $event->citizen->getProfession()->getName() === 'hunter' || $event->citizen->hasRole('guide');
                if (count($zones) === 0) $event->cache->addMessage(T::__( 'Leider ist es dir trotz größter Anstrengungen nicht gelungen, etwas neues in der Umgebung zu entdecken.', 'game' ));
                elseif ($markings_visible)
                    $event->cache->addMessage(T::__( 'Nichts entgeht deinem geschulten Blick! Du hast neue Informationen über {casino} Zonen gesammelt!', 'game' ));
                else $event->cache->addMessage(T::__( 'Frenetisch schreibst du alles auf was du in der Umgebung entdeckst und sammelst neue Informationen über {casino} Zonen. Jetzt brauchst du nur noch einen Experten, der ermitteln kann, wo genau diese Zonen liegen ...', 'game' ));
            break;

            // Additional soul purification effects
            case 11001:
                $hammam_level = $this->getService(TownHandler::class)->getBuilding( $event->town, 'item_soul_blue_static_#00' )?->getLevel() ?? 0;

                // 5 additional defense per cleansed soul (the original action already gives 5, so in total we give 10)
                if ($hammam_level > 0) $event->town->setSoulDefense( $event->town->getSoulDefense() + 5 );

                // Bonus score (2) and +10 additional defense
                if ($hammam_level >= 3) {
                    $event->town->setSoulDefense( $event->town->getSoulDefense() + 10 );
                    $event->town->setBonusScore($event->town->getBonusScore() + 2);
                }


                break;
        }
    }

}