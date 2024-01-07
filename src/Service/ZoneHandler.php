<?php


namespace App\Service;

use App\Entity\Citizen;
use App\Entity\DigTimer;
use App\Entity\EscapeTimer;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemPrototype;
use App\Entity\PictoPrototype;
use App\Entity\RuinExplorerStats;
use App\Entity\RuinZone;
use App\Entity\RuinZonePrototype;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Entity\ZoneActivityMarker;
use App\Entity\ZonePrototype;
use App\Enum\Configuration\TownSetting;
use App\Enum\ScavengingActionType;
use App\Enum\ZoneActivityMarkerType;
use App\Structures\EventConf;
use App\Structures\TownConf;
use App\Translation\T;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

class ZoneHandler
{
    private EntityManagerInterface $entity_manager;
    private ItemFactory $item_factory;
    private StatusFactory $status_factory;
    private RandomGenerator $random_generator;
    private InventoryHandler $inventory_handler;
    private CitizenHandler $citizen_handler;
    private PictoHandler $picto_handler;
    private TranslatorInterface $trans;
    private LogTemplateHandler $log;
    private ConfMaster $conf;
    private Packages $asset;
    private TownHandler $town_handler;
    private GameProfilerService $gps;
    private EventProxyService $proxy;

    public function __construct(
        EntityManagerInterface $em, ItemFactory $if, LogTemplateHandler $lh, TranslatorInterface $t,
        StatusFactory $sf, RandomGenerator $rg, InventoryHandler $ih, CitizenHandler $ch, PictoHandler $ph, Packages $a,
        ConfMaster $conf, TownHandler $th, GameProfilerService $gps, EventProxyService $proxy)
    {
        $this->entity_manager = $em;
        $this->item_factory = $if;
        $this->status_factory = $sf;
        $this->random_generator = $rg;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
        $this->picto_handler = $ph;
        $this->trans = $t;
        $this->log = $lh;
        $this->asset = $a;
        $this->conf = $conf;
        $this->town_handler = $th;
        $this->gps = $gps;
        $this->proxy = $proxy;
    }

    public function updateRuinZone(?RuinExplorerStats $ex): ?string {
        if ($ex === null || !$ex->getActive()) return false;

        $eject = $ex->getTimeout()->getTimestamp() < time()/* || $this->citizen_handler->isWounded( $ex->getCitizen() ) || $this->citizen_handler->hasStatusEffect($ex->getCitizen(), 'terror')*/;
        $wound = $ex->getTimeout()->getTimestamp() < time();

        if ($eject) {
            $citizen = $ex->getCitizen();
            $ruinZone = $this->entity_manager->getRepository(RuinZone::class)->findOneByPosition($citizen->getZone(), $ex->getX(), $ex->getY(), $ex->getZ());

            foreach ($citizen->getInventory()->getItems() as $item)
                if (!$item->getEssential())
                    $this->inventory_handler->forceMoveItem( $ruinZone->getFloor(), $item );

            if ($wound) $this->citizen_handler->inflictWound( $citizen );

            $ex->setActive( false );

            $this->entity_manager->persist( $citizen );
            $this->entity_manager->persist( $ex );
            $this->entity_manager->persist( $ruinZone );

            return $this->trans->trans('Die Atmosphäre wird unerträglich... Du kannst so nicht weitermachen; ohne zu wissen wie, du findest den Ausgang, aber du verletzt dich bei der Flucht.', [], 'game');
        } else return null;
    }

    public function updateZone( Zone $zone, ?DateTime $up_to = null, ?Citizen $active = null ): ?string {

        $now = new DateTime();
        if ($up_to === null || $up_to > $now) $up_to = $now;

        $chances_by_player = 0;
        $chances_by_escorts = 0;
        $found_by_player = [];
        $found_by_escorts = [];
        $ret_str = [];

        $valid_timers = [];
        $timers_to_remove = [];
        foreach ($zone->getEscapeTimers() as $timer)
            if ($timer->getTime() < $up_to) $timers_to_remove[] = $timer;
            else $valid_timers[] = $timer;

        $longest_timer = null;
        foreach ($timers_to_remove as $timer) {
            if (!$timer->getCitizen() && ( $longest_timer == null || $timer->getTime() > $longest_timer ))
                $longest_timer = $timer->getTime();
            $this->entity_manager->remove( $timer );
        }

        if ($longest_timer !== null && empty($valid_timers) && !$this->isZoneUnderControl($zone) && !empty($zone->getCitizens()))
            $this->entity_manager->persist( $this->log->zoneEscapeTimerExpired($zone, $longest_timer) );

        /** @var DigTimer[] $all_dig_timers */
        $all_dig_timers = [];
        $cp = 0;

        foreach ($zone->getCitizens() as $citizen) {
            $timer = $citizen->getCurrentDigTimer();
            if ($timer && !$timer->getPassive())
                $all_dig_timers[] = $timer;
            $cp += $this->citizen_handler->getCP( $citizen );
        }

        $dig_timers_due      = array_filter($all_dig_timers, fn(DigTimer $timer) => $timer->getTimestamp() < $up_to);
        $dig_timers_relevant = array_filter($all_dig_timers, fn(DigTimer $timer) => (($timer->getTimestamp() < $up_to) || !empty($timer->getDigCache())));

        if ($cp < $zone->getZombies()) {
            foreach ($all_dig_timers as $timer) {
                $timer->setPassive(true);
                $this->entity_manager->persist($timer);
            }

            return null;
        }

        $sort_func = function(DigTimer $a, DigTimer $b): int {
            return $a->getTimestamp()->getTimestamp() == $b->getTimestamp()->getTimestamp() ? 0 :
                ($a->getTimestamp() < $b->getTimestamp() ? -1 : 1);
        };

        $empty_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneBy(['name' => 'empty_dig']);
        $base_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneBy(['name' => 'base_dig']);
        $event_group = null;

        // Get event specific items
        $events = [];
        foreach ($this->conf->getCurrentEvents($zone->getTown()) as $e)
            if ($e->get(EventConf::EVENT_DIG_DESERT_GROUP, null) && $e->get(EventConf::EVENT_DIG_DESERT_CHANCE, 1.0) > 0)
                $events[] = $e;

        if (!empty($events)) {
            $e = $this->random_generator->pick( $events );
            $event = $e->get(EventConf::EVENT_DIG_DESERT_GROUP, null);
            $event_chance = $e->get(EventConf::EVENT_DIG_DESERT_CHANCE, 1.0);
            $event_cap = $e->get(EventConf::EVENT_DIG_DESERT_CHANCE_CAP, 0.9);
        } else {
            $event = null;
            $event_chance = 0.0;
            $event_cap = 0.9;
        }

        if ($event && $event_chance > 0) $event_group = $this->entity_manager->getRepository(ItemGroup::class)->findOneBy(['name' => $event]);

        $wrap = function(array $a) {
            return implode(', ', array_map(function(ItemPrototype $p) {
                return "<span class='tool'><img alt='' src='{$this->asset->getUrl( "build/images/item/item_{$p->getIcon()}.gif" )}'> {$this->trans->trans($p->getLabel(), [], 'items')}</span>";
            }, $a));
        };

        $conf = $this->conf->getTownConfiguration( $zone->getTown() );

        $zone_update = false;

        $not_up_to_date = !empty($dig_timers_due);

        while ($not_up_to_date) {
            usort( $dig_timers_due, $sort_func );
            foreach ($dig_timers_due as &$timer)
                if ($timer->getTimestamp() < $up_to) {
                    /*$factor = $this->getDigChanceFactor($timer->getCitizen(), $zone);

                    $total_dig_chance = $factor * ($zone->getDigs() > 0 ? 0.6 : 0.35 );*/

					$total_dig_chance = $this->proxy->citizenQueryDigChance( $timer->getCitizen(), $zone, ScavengingActionType::Dig, $conf->isNightMode( $timer->getTimestamp() ) );

                    $found_item = $this->random_generator->chance($total_dig_chance, 0.1, 0.9);
                    $found_event_item = (!$found_item && $event_group && $zone->getDigs() > 0 && $this->random_generator->chance($total_dig_chance * $event_chance, 0.1, $event_cap ));

                    $cache = $timer->getDigCache() ?? [];
                    if ($found_item || $found_event_item) {

                        $cache[$timer->getTimestamp()->getTimestamp()] = $found_event_item ? 2 : ( ($zone->getDigs() > 0) ? 1 : 0 );

                        if ($found_item) $zone->setDigs(max(0, $zone->getDigs() - 1));
                        $zone_update = true;

                    } else $cache[$timer->getTimestamp()->getTimestamp()] = -1;

                    $timer->setDigCache($cache);

                    try {
                        $timer->setTimestamp(
                            (new DateTime())->setTimestamp(
                                $timer->getTimestamp()->getTimestamp()
                            )->add(DateInterval::createFromDateString(
                                $conf->get( $timer->getCitizen()->getProfession()->getName() === 'collec'
                                                ? TownSetting::TimingDiggingCollector
                                                : TownSetting::TimingDiggingDefault
                                ))) );

                    } catch (Exception $e) {
                        $timer->setTimestamp( new DateTime('+1min') );
                    }
                }
            usort( $dig_timers_due, $sort_func );
            $not_up_to_date = $dig_timers_due[0]->getTimestamp() < $up_to;
        }

        $active_dig_timers = $active ? array_filter($dig_timers_relevant, function(DigTimer $t) use ($active) {
            return $active === $t->getCitizen() || ($t->getCitizen()->getEscortSettings() && $t->getCitizen()->getEscortSettings()->getLeader() === $active);
        }) : $dig_timers_relevant;

        $all_events = $this->conf->getCurrentEvents( $zone->getTown() );

        foreach ($active_dig_timers as &$executable_timer ) {

            $current_citizen = $executable_timer->getCitizen();
            if (!$active)
                continue;
            if ($active !== $current_citizen && (!$current_citizen->getEscortSettings() || $current_citizen->getEscortSettings()->getLeader() !== $active))
                continue;

            if (empty($executable_timer->getDigCache()) || !is_array($executable_timer->getDigCache()))
                $executable_timer->setDigCache(null);
            else foreach ($executable_timer->getDigCache() as $time => $mode) {

                $redraw = false; $redraw_count = 0; $itemMarkerType = null;

                do {
                    $redraw_count++;
                    $item_prototype = match ($mode) {
                        -1 => null,
                        0 => $this->random_generator->pickItemPrototypeFromGroup($empty_group, $conf, $all_events),
                        1 => $this->random_generator->pickItemPrototypeFromGroup($base_group, $conf, $all_events),
                        2 => $this->random_generator->pickItemPrototypeFromGroup($event_group ?? $base_group, $conf, $all_events),
                        default => null,
                    };

                    $itemMarkerType = $item_prototype ? ZoneActivityMarkerType::scavengedItemIncurs( $item_prototype ) : null;
                    $itemLimit = $itemMarkerType?->configuredLimit( $conf ) ?? -1;

                    if ($itemLimit >= 0 && $itemMarkerType)
                        $redraw = $zone->getTown()->getTownZone()->getActivityMarkersFor( $itemMarkerType )->count() >= $itemLimit;
                    else $redraw = false;

                    if ($redraw && $redraw_count >= 10) $item_prototype = null;

                } while ($redraw && $redraw_count < 10);

                if ($itemMarkerType && $item_prototype) $this->entity_manager->persist($zone->getTown()->getTownZone()->addActivityMarker( (new ZoneActivityMarker())
                    ->setCitizen( $current_citizen )
                    ->setTimestamp( new DateTime() )
                    ->setType( $itemMarkerType )
                ));

                $zone_update = true;

                if ($active && $current_citizen->getId() === $active->getId()) {
                    $chances_by_player++;
                    if ($item_prototype)
                        $found_by_player[] = $item_prototype;
                }

                if ($active && $current_citizen->getEscortSettings() && $current_citizen->getEscortSettings()->getLeader() && $current_citizen->getEscortSettings()->getLeader()->getId() === $active->getId()) {
                    $chances_by_escorts++;
                    if ($item_prototype)
                        $found_by_escorts[] = $item_prototype;
                }

                $this->gps->recordDigResult($item_prototype, $current_citizen, null, 'scavenge', match ($mode) {
                    -1, 0, 1 => false,
                    2 => (bool)$event_group
                });

                if ($item_prototype) {
                    // If we get a Chest XL, we earn a picto
                    if ($item_prototype->getName() == 'chest_xl_#00') {
                        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneBy(['name' => "r_chstxl_#00"]);
                        $this->picto_handler->give_picto($current_citizen, $pictoPrototype);
                    }

                    $item = $this->item_factory->createItem($item_prototype);
                    $this->gps->recordItemFound( $item_prototype, $current_citizen, null );
                    if ($inventoryDest = $this->proxy->placeItem( $current_citizen, $item, [ $current_citizen->getInventory(), $executable_timer->getZone()->getFloor() ] )) {
                        if($inventoryDest->getId() === $executable_timer->getZone()->getFloor()->getId()){
                            $this->entity_manager->persist($this->log->beyondItemLog($current_citizen, $item->getPrototype(), true));
                            if ($active && $current_citizen->getEscortSettings() && $current_citizen->getEscortSettings()->getLeader() && $current_citizen->getEscortSettings()->getLeader() === $active)
                                $ret_str[] = $this->trans->trans('Er kann den Gegenstand momentan nicht aufnehmen und hat ihn auf dem Boden abgelegt.', [], 'game');
                            elseif ($active && $current_citizen === $active)
                                $ret_str[] = $this->trans->trans('Der Gegenstand, den du soeben gefunden hast, passt nicht in deinen Rucksack, darum bleibt er erstmal am Boden...', [], 'game');
                        }
                        $this->entity_manager->persist( $item );
                        $this->entity_manager->persist( $current_citizen->getInventory() );
                        $this->entity_manager->persist( $executable_timer->getZone()->getFloor() );
                    }
                } else {
                    // Uncomment to have the dig message show up when the dig happened, not when the user logged back in
                    if (!$executable_timer->isNonAutomatic())
                        $this->entity_manager->persist( $this->log->outsideDig( $current_citizen, $item_prototype/*, (new DateTime())->setTimestamp($time) */) );
                }

                // Banished citizen's stash check
                if(!$executable_timer->getCitizen()->getBanished() && $this->hasHiddenItem($executable_timer->getZone()) && $this->random_generator->chance(0.05)){
                    $items = $executable_timer->getZone()->getFloor()->getItems();
                    $itemsproto = array_map( function($e) {return $e->getPrototype(); }, $items->toArray() );
                    $executable_timer->getZone()->setItemsHiddenAt(null);
                    if ($active && $current_citizen->getEscortSettings() && $current_citizen->getEscortSettings()->getLeader() && $current_citizen->getEscortSettings()->getLeader() === $active)
                        $ret_str[] = $this->trans->trans('Beim Graben ist {citizen} auf eine Art... geheimes Versteck mit {items} gestoßen! Es wurde vermutlich von einem verbannten Mitbürger angelegt...', ['{items}' => $wrap($itemsproto), '{citizen}' => $current_citizen ], 'game');
                    elseif ($active && $current_citizen === $active)
                        $ret_str[] = $this->trans->trans('Beim Graben bist du auf eine Art... geheimes Versteck mit {items} gestoßen! Es wurde vermutlich von einem verbannten Mitbürger angelegt...', ['{items}' => $wrap($itemsproto) ], 'game');
                    foreach ($items as $item) {
                        if ($item->getHidden()){
                            $item->setHidden(false);
                            $this->entity_manager->persist($item);
                        }
                    }
                }

            }

            $executable_timer->setDigCache(null);
        }

        if ($zone_update) $this->entity_manager->persist($zone);
        foreach ($all_dig_timers as $timer) $this->entity_manager->persist( $timer->setNonAutomatic(false) );

        if ($chances_by_player > 0) {
            if (empty($found_by_player)){
                if ($this->citizen_handler->hasStatusEffect( $active, 'wound5' ))
                    array_unshift($ret_str, $this->trans->trans( 'Deine Verletzung am Auge macht dir die Suche nicht gerade leichter.', [], 'game'));
                if ($this->citizen_handler->hasStatusEffect( $active, 'drunk' ))
                    array_unshift($ret_str, $this->trans->trans( 'Dein <strong>Trunkenheitszustand</strong> hilft dir wirklich nicht weiter. Das ist nicht gerade einfach, wenn sich alles dreht und du nicht mehr klar siehst.', [], 'game'));
                array_unshift($ret_str, $this->trans->trans( 'Trotz all deiner Anstrengungen hast du hier leider nichts gefunden ...', [], 'game' ));
            }
            elseif (count($found_by_player) === 1)
                array_unshift($ret_str, $this->trans->trans( 'Nach einigen Anstrengungen hast du folgendes gefunden: {item}!', [
                    '{item}' => $wrap($found_by_player)
                ], 'game' ));
            else array_unshift($ret_str, $this->trans->trans( 'Du gräbst schon seit einiger Zeit und hast mehrere Gegenstände gefunden: {items}', ['{items}' => $wrap($found_by_player)], 'game' ));
        }

        if ($chances_by_escorts > 0) {
            if (empty($found_by_escorts) && $chances_by_escorts === 1) array_unshift($ret_str, $this->trans->trans( 'Trotz all seiner Anstrengungen hat dein Freund hier leider nichts gefunden...', [], 'game' ));
            elseif (empty($found_by_escorts) && $chances_by_escorts > 1) array_unshift($ret_str, $this->trans->trans( 'Trotz all ihrer Anstrengungen hat deine Expedition hier leider nichts gefunden...', [], 'game' ));
            elseif ($chances_by_escorts === 1) array_unshift($ret_str, $this->trans->trans( 'Nach einigen Anstrengungen hat dein Freund folgendes gefunden: {item}!', ['{item}' => $wrap($found_by_escorts)], 'game' ));
            else array_unshift($ret_str, $this->trans->trans( 'Nach einigen Anstrengungen hat deine Expedition folgendes gefunden: {item}!', ['{item}' => $wrap($found_by_escorts)], 'game' ));
        }

        if(($chances_by_player > 0 || $chances_by_escorts > 0) && $zone->getDigs() <= 0) {
            $ret_str[] = $this->trans->trans("Diese Zone ist leergesucht. Du wirst hier keine wertvollen Gegenstände mehr finden können.", [], "game");
        }

        if ($active && $active->getProfession()->getName() === 'collec')
            foreach ([[1,0],[-1,0],[0,1],[0,-1]] as $n) {
                $nzone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($zone->getTown(),$zone->getX() + $n[0], $zone->getY() + $n[1]);
                if ($nzone && !$nzone->getCitizens()->isEmpty()) $this->updateZone($nzone,$up_to,null);
            }


        $ret_str = array_unique($ret_str);

        return empty($ret_str) ? null : implode('<hr />', $ret_str);

    }

    public function isZoneUnderControl(Zone $zone, ?int &$cp = null): bool {
        $cp = 0;
        foreach ($zone->getCitizens() as $c)
            if ($c->getAlive())
                $cp += $this->citizen_handler->getCP($c);

        return $cp >= $zone->getZombies();
    }

    public function getZoneControlPoints(Zone $zone): int {
        $cp = 0;
        foreach ($zone->getCitizens() as $c)
            if ($c->getAlive())
                $cp += $this->citizen_handler->getCP($c);

        return $cp;
    }

    /**
     * @param Zone $zone
     * @param $cp_ok_before
     * @param Citizen|null $leaving_citizen
     */
    public function handleCitizenCountUpdate(Zone &$zone, $cp_ok_before, ?Citizen $leaving_citizen = null) {
        // If no citizens remain in a zone, invalidate all associated escape timers and clear the log
        if ($zone->getCitizens()->isEmpty()) {
            foreach ($zone->getEscapeTimers() as $et)
                $this->entity_manager->remove( $et );
            $zone->getEscapeTimers()->clear();
            foreach ($zone->getChatSilenceTimers() as $cst)
                $this->entity_manager->remove( $cst );
            $zone->getChatSilenceTimers()->clear();
            foreach ($this->entity_manager->getRepository(TownLogEntry::class)->findByFilter( $zone->getTown(), zone: $zone ) as $entry) {
                /** @var TownLogEntry $entry */
                if ($entry->getLogEntryTemplate() === null || !$entry->getLogEntryTemplate()->getNonVolatile()) {
                    $entry->setAdminOnly(true);
                    $this->entity_manager->persist($entry);
                }
            }
        }

        // If zombies can take control after leaving the zone and there are citizens remaining, install a grace escape timer
        else if ($cp_ok_before !== null) {
            if ( $cp_ok_before && !$this->isZoneUnderControl( $zone ) ) {
                if ( $leaving_citizen && !$zone->getCitizens()->isEmpty() ) $this->entity_manager->persist( $this->log->zoneLostControlLeaving( $zone, $leaving_citizen ) );
                $zone->addEscapeTimer( (new EscapeTimer())->setTime( new DateTime('+30min') ) );
                // Disable all dig timers
                foreach ($zone->getDigTimers() as $dig_timer) {
                    $has_dt = !$dig_timer->getPassive();
                    $dig_timer->setPassive(true);
                    $this->entity_manager->persist( $dig_timer );
                    if ($has_dt) $this->entity_manager->persist( $this->log->zoneSearchInterrupted( $zone, $dig_timer->getCitizen() ) );
                }


            }
            // If we took back control of the zone, logs it
            elseif (!$cp_ok_before && $this->isZoneUnderControl($zone)) {
                $this->entity_manager->persist($this->log->zoneUnderControl($zone));
            }
        }
    }

    public function getSoulZones( Town $town ) {
        // Get all soul items within these inventories
        $soul_items = $this->inventory_handler->getAllItems($town, ['soul_blue_#00','soul_blue_#01','soul_red_#00'], false, false, false, true, true, false);

        $cache = []; $found_zone_ids = [];
        foreach ($soul_items as $item)
            if (!isset($cache[$item->getInventory()->getId()])) {
                $z = null;
                if ($item->getInventory()->getZone()) $z = $item->getInventory()->getZone();
                elseif ($item->getInventory()->getRuinZone()) $z = $item->getInventory()->getRuinZone()->getZone();
                elseif ($item->getInventory()->getRuinZoneRoom()) $z = $item->getInventory()->getRuinZoneRoom()->getZone();

                if ($z !== null && !isset($found_zone_ids[$z->getId()])) {
                    $cache[$item->getInventory()->getId()] = $z;
                    $found_zone_ids[$z->getId()] = true;
                }
            }

        return array_values($cache);
    }

    public function hasHiddenItem(Zone $zone){
        // get hidden item count
        $query = $this->entity_manager->createQueryBuilder()
            ->select('SUM(i.count)')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory = :invs')->setParameter('invs', $zone->getFloor())
            ->andWhere('i.hidden = true')
            ->getQuery();

        return $query->getSingleScalarResult() > 0;
    }

    /**
     * @param Town $town
     * @return Zone[]
     */
    public function getZoneWithHiddenItems( Town $town ): array {
        // Get all zone inventory IDs
        // We're just getting IDs, because we don't want to actually hydrate the inventory instances
        $zone_invs = array_column($this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i')
            ->join("i.zone", "z")
            ->andWhere('z.id IN (:zones)')->setParameter('zones', $town->getZones())
            ->getQuery()
            ->getScalarResult(), 'id');

        // Get all hidden items within these inventories
        $hidden_items = $this->entity_manager->createQueryBuilder()
            ->select('i')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory IN (:invs)')->setParameter('invs', $zone_invs)
            ->andWhere('i.hidden = true')
            ->getQuery()
            ->getResult();

        $cache = [];
        /** @var Item $item */
        foreach ($hidden_items as $item)
            if (!isset($cache[$item->getInventory()->getId()]))
                $cache[$item->getInventory()->getId()] = $item->getInventory()->getZone();
        return array_values($cache);
    }

    public function getZoneDangerLevelNumber( Zone $zone, int $seed = 0, bool $map_upgrade = false ): int {

        $modA = $seed % 2 != 0;
        $modB = $seed < 0;

        return match ($zone->getZombies()) {
            0 => 0,
            1 => 1,
            2 => $modA ? 1 : 2,
            3 => 2,
            4, 5 => $modB ? 2 : 3,
            6, 7, 8 => 3,
            default => (!$map_upgrade) ? 3 : 4,
        };
    }

    public function getZoneClasses(Town $town, Zone $zone, ?Citizen $citizen = null, bool $soul = false, bool $admin = false, $map_upgrade = false, int $seed = 0): array {
        $attributes = ['zone'];

        if ($zone->getX() == 0 && $zone->getY() == 0) {
            $attributes[] = 'town';
        }
        if ($zone->getX() == 0 && $zone->getY() == 0 && $town->getDevastated()) {
            $attributes[] = 'devast';
        }
        if ($citizen && $zone === $citizen->getZone()) {
            $attributes[] = 'active';
        }
        if (!$admin && $zone->getDiscoveryStatus() === Zone::DiscoveryStateNone) {
            $attributes[] = 'unknown';
        } else {
            if (!$admin && $zone->getDiscoveryStatus() === Zone::DiscoveryStatePast) {
                $attributes[] = 'past';
            } 
            if(!$admin && $citizen && !($zone->getX() == 0 && $zone->getY() == 0) && !$citizen->getVisitedZones()->contains($zone)) {
                $attributes[] = 'global';
            }
            if ($zone->getPrototype()) {
                $attributes[] = 'ruin';
                if ($zone->getBuryCount() > 0) {
                    $attributes[] = 'buried';
                }
            }
        }
        if (($zone->getDiscoveryStatus() === Zone::DiscoveryStateCurrent && $zone->getZombieStatus() >= Zone::ZombieStateEstimate) || $admin)
            $attributes[] = "danger-{$this->getZoneDangerLevelNumber($zone,$seed,$map_upgrade || $admin)}";

        if($soul)
            $attributes[] = "soul";

        return $attributes;
    }

    public function getZoneAttributes(Zone $zone, ?Citizen $citizen = null) {
        $attributes = ['zone'];
        $attributes['town'] = ($zone->getX() == 0 && $zone->getY() == 0) ? 1 : 0;
        $attributes['devast'] = ($zone->getX() == 0 && $zone->getY() == 0 && $zone->getTown()->getDevastated()) ? 1 : 0;
        $attributes['active'] = $citizen && $zone === $citizen->getZone() ? 1 : 0;
        $attributes['discovery'] = $zone->getDiscoveryStatus();
        if ($zone->getDiscoveryStatus() && $zone->getPrototype()) {
            if ($zone->getBuryCount() > 0) {
                $attributes['building'] = [
                    'name' => T::__("Ein nicht freigeschaufeltes Gebäude.", "game"),
                    'type' => -1,
                    'dig' => $zone->getBuryCount(),
                    'empty' => ($zone->getRuinDigs() == 0),
                    'blueprint' => $zone->getBlueprint(),
                ];
            }
            else {
                $attributes['building'] = [
                    'name' => $this->trans->trans($zone->getPrototype()->getLabel(), [], "game"),
                    'type' => $zone->getPrototype()->getId(),
                    'dig' => 0,
                    'empty' => ($zone->getRuinDigs() == 0),
                    'blueprint' => $zone->getBlueprint(),
                ];
            }
        }

        if ($zone->getDiscoveryStatus() && $zone->getZombieStatus() === Zone::ZombieStateEstimate) {
            if ($zone->getZombies() == 0) {
                $attributes['danger'] = '0';
            }
            else if ($zone->getZombies() <= 2) {
                $attributes['danger'] = '1';
            }
            else if ($zone->getZombies() <= 5) {
                $attributes['danger'] = '2';
            }
            else {
                $attributes['danger'] = '3';
            }
        }

        return $attributes;
    }

    public function getZoneKm(Zone $zone): int {
        return round(sqrt( pow($zone->getX(),2) + pow($zone->getY(),2) ));
    }

    public function getZoneAp(Zone $zone): int {
        return abs($zone->getX()) + abs($zone->getY());
    }

    public function getZonesWithExplorableRuin($zones): array {
        $explorable_zones = [];
        foreach ($zones as $zone) {
            /** @var Zone $zone */
            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $explorable_zones[] = $zone;
            }
        }

        return $explorable_zones;
    }
}