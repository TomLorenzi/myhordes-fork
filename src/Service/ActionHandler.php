<?php


namespace App\Service;


use App\Entity\AffectZone;
use App\Entity\BuildingPrototype;
use App\Entity\CampingActionPrototype;
use App\Entity\CauseOfDeath;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenStatus;
use App\Entity\EscapeTimer;
use App\Entity\HomeActionPrototype;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\PictoPrototype;
use App\Entity\Recipe;
use App\Entity\RequireLocation;
use App\Entity\Requirement;
use App\Entity\Result;
use App\Entity\RolePlayerText;
use App\Entity\Zone;
use App\Structures\ItemRequest;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Asset\Packages;

class ActionHandler
{
    private $entity_manager;
    private $status_factory;
    private $citizen_handler;
    private $death_handler;
    private $inventory_handler;
    private $random_generator;
    private $item_factory;
    private $translator;
    private $game_factory;
    private $town_handler;
    private $zone_handler;
    private $picto_handler;
    private $assets;
    private $log;


    public function __construct(
        EntityManagerInterface $em, StatusFactory $sf, CitizenHandler $ch, InventoryHandler $ih, DeathHandler $dh,
        RandomGenerator $rg, ItemFactory $if, TranslatorInterface $ti, GameFactory $gf, Packages $am, TownHandler $th,
        ZoneHandler $zh, PictoHandler $ph, LogTemplateHandler $lt)
    {
        $this->entity_manager = $em;
        $this->status_factory = $sf;
        $this->citizen_handler = $ch;
        $this->inventory_handler = $ih;
        $this->random_generator = $rg;
        $this->item_factory = $if;
        $this->translator = $ti;
        $this->game_factory = $gf;
        $this->assets = $am;
        $this->town_handler = $th;
        $this->death_handler = $dh;
        $this->zone_handler = $zh;
        $this->picto_handler = $ph;
        $this->log = $lt;
    }

    const ActionValidityNone = 1;
    const ActionValidityHidden = 2;
    const ActionValidityCrossed = 3;
    const ActionValidityAllow = 4;
    const ActionValidityFull = 5;

    protected function evaluate( Citizen $citizen, ?Item $item, $target, ItemAction $action, ?string &$message ): int {

        if ($item && !$item->getPrototype()->getActions()->contains( $action )) return self::ActionValidityNone;
        if ($target && (!$action->getTarget() || !$this->targetDefinitionApplies($target, $action->getTarget())))
            return self::ActionValidityNone;

        $current_state = self::ActionValidityFull;
        foreach ($action->getRequirements() as $meta_requirement) {

            $last_state = $current_state;

            $this_state = self::ActionValidityNone;
            switch ($meta_requirement->getFailureMode()) {
                case Requirement::MessageOnFail: $this_state = self::ActionValidityAllow; break;
                case Requirement::CrossOnFail: $this_state = self::ActionValidityCrossed; break;
                case Requirement::HideOnFail: $this_state = self::ActionValidityHidden; break;
            }

            if ($status = $meta_requirement->getStatusRequirement()) {
                if ($status->getStatus() !== null && $status->getEnabled() !== null) {
                    $status_is_active = $citizen->getStatus()->contains( $status->getStatus() );
                    if ($status_is_active !== $status->getEnabled()) $current_state = min( $current_state, $this_state );
                }

                if ($status->getProfession() !== null && $citizen->getProfession()->getId() !== $status->getProfession()->getId())
                    $current_state = min( $current_state, $this_state );
            }

            if ($home = $meta_requirement->getHome()) {
                if ($home->getUpgrade() === null) {
                    if ($home->getMinLevel() !== null && $citizen->getHome()->getPrototype()->getLevel() < $home->getMinLevel()) $current_state = min( $current_state, $this_state );
                    if ($home->getMaxLevel() !== null && $citizen->getHome()->getPrototype()->getLevel() > $home->getMaxLevel()) $current_state = min( $current_state, $this_state );
                } else {
                    /** @var CitizenHomeUpgrade|null $target_home_upgrade */
                    $target_home_upgrade = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype($citizen->getHome(), $home->getUpgrade());
                    $target_home_upgrade_level = $target_home_upgrade ? $target_home_upgrade->getLevel() : 0;
                    if ($home->getMinLevel() !== null && $target_home_upgrade_level < $home->getMinLevel()) $current_state = min( $current_state, $this_state );
                    if ($home->getMaxLevel() !== null && $target_home_upgrade_level > $home->getMaxLevel()) $current_state = min( $current_state, $this_state );
                }
            }

            if ($zone = $meta_requirement->getZone()) {
                if ($zone->getMaxLevel() !== null && $citizen->getZone() && ( $citizen->getZone()->getImprovementLevel() ) >= $zone->getMaxLevel()) $current_state = min( $current_state, $this_state );
            }

            if ($ap = $meta_requirement->getAp()) {
                $max = $ap->getRelativeMax() ? ($this->citizen_handler->getMaxAP( $citizen ) + $ap->getMax()) : $ap->getMax();
                if ($citizen->getAp() < $ap->getMin() || $citizen->getAp() > $max) $current_state = min( $current_state, $this_state );
            }

            if ($counter = $meta_requirement->getCounter()) {
                $counter_value = $citizen->getSpecificActionCounterValue( $counter->getType() );
                if ($counter->getMin() !== null && $counter_value < $counter->getMin()) $current_state = min( $current_state, $this_state );
                if ($counter->getMax() !== null && $counter_value > $counter->getMax()) $current_state = min( $current_state, $this_state );
            }

            if ($item_condition = $meta_requirement->getItem()) {
                $item_str = ($is_prop = (bool)$item_condition->getProperty())
                    ? $item_condition->getProperty()->getName()
                    : $item_condition->getPrototype()->getName();

                $source = $citizen->getZone() ? [$citizen->getInventory(), $citizen->getZone()->getFloor()] : [$citizen->getInventory(), $citizen->getHome()->getChest()];

                if (empty($this->inventory_handler->fetchSpecificItems( $source,
                    [new ItemRequest($item_str, $item_condition->getCount() ?? 1, false, null, $is_prop)]
                ))) $current_state = min( $current_state, $this_state );
            }

            if ($location_condition = $meta_requirement->getLocation()) {
                switch ( $location_condition->getLocation() ) {
                    case RequireLocation::LocationInTown:
                        if ( $citizen->getZone() ) $current_state = min( $current_state, $this_state );
                        break;
                    case RequireLocation::LocationOutside:case RequireLocation::LocationOutsideFree:
                    case RequireLocation::LocationOutsideRuin:case RequireLocation::LocationOutsideBuried:
                        if ( !$citizen->getZone() ) $current_state = min( $current_state, $this_state );
                        else {
                            if     ( $location_condition->getLocation() === RequireLocation::LocationOutsideFree   &&  $citizen->getZone()->getPrototype() ) $current_state = min( $current_state, $this_state );
                            elseif ( $location_condition->getLocation() === RequireLocation::LocationOutsideRuin   && !$citizen->getZone()->getPrototype() ) $current_state = min( $current_state, $this_state );
                            elseif ( $location_condition->getLocation() === RequireLocation::LocationOutsideBuried && (!$citizen->getZone()->getPrototype() || !$citizen->getZone()->getBuryCount()) ) $current_state = min( $current_state, $this_state );

                            if ($location_condition->getMinDistance() !== null || $location_condition->getMaxDistance() !== null) {
                                $dist = round(sqrt( pow($citizen->getZone()->getX(),2) + pow($citizen->getZone()->getY(),2) ));
                                if ( ($location_condition->getMinDistance() !== null && $dist < $location_condition->getMinDistance() ) || ($location_condition->getMaxDistance() !== null && $dist > $location_condition->getMaxDistance() ) )
                                    $current_state = min( $current_state, $this_state );
                            }
                        }
                        break;

                    default:
                        break;
                }
            }


            if ($zombie_condition = $meta_requirement->getZombies()) {
                $cp = 0;
                $current_zeds = $citizen->getZone() ? $citizen->getZone()->getZombies() : 0;
                if ( $citizen->getZone() ) foreach ( $citizen->getZone()->getCitizens() as $c )
                    $cp += $this->citizen_handler->getCP( $c );

                if ($zombie_condition->getMustBlock() !== null) {

                    if ($zombie_condition->getMustBlock() && $cp >= $current_zeds) $current_state = min( $current_state, $this_state );
                    elseif (!$zombie_condition->getMustBlock() && $cp < $current_zeds) {

                        if (!$zombie_condition->getTempControlAllowed() || !$this->entity_manager->getRepository( EscapeTimer::class )->findActiveByCitizen($citizen))
                            $current_state = min( $current_state, $this_state );

                    }

                }

                if ($zombie_condition->getNumber() > $current_zeds) $current_state = min( $current_state, $this_state );
            }

            if ($building_condition = $meta_requirement->getBuilding()) {
                $town = $citizen->getTown();
                $building = $this->town_handler->getBuilding($town, $building_condition->getBuilding(), false);

                if ($building) {
                    if ($building_condition->getComplete() !== null && $building_condition->getComplete() !== $building->getComplete()) $current_state = min( $current_state, $this_state );
                    if ($building->getComplete()) {
                        if ($building_condition->getMinLevel() > $building->getLevel()) $current_state = min( $current_state, $this_state );
                        if ($building_condition->getMaxLevel() < $building->getLevel()) $current_state = min( $current_state, $this_state );
                    }
                    elseif ($building_condition->getMinLevel() !== null) $current_state = min( $current_state, $this_state );
                    elseif ($building_condition->getMaxLevel() !== null) $current_state = min( $current_state, $this_state );
                }
                elseif ($building_condition->getComplete() === true) $current_state = min( $current_state, $this_state );
                elseif ($building_condition->getMinLevel() !== null) $current_state = min( $current_state, $this_state );
                elseif ($building_condition->getMaxLevel() !== null) $current_state = min( $current_state, $this_state );
            }


            if ($current_state < $last_state) $message = $this->translator->trans($meta_requirement->getFailureText(), [], 'items');

        }

        return $current_state;

    }

    /**
     * @param ItemPrototype[] $list
     * @return array
     */
    private function reformat_prototype_list(array $list): array {

        $cache = [];
        foreach ( $list as $entry ) {
            if (!isset( $cache[$entry->getId()] )) $cache[$entry->getId()] = [1,$entry];
            else $cache[$entry->getId()][0]++;
        }

        return $cache;

    }

    /**
     * @param Citizen $citizen
     * @param Item $item
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableItemActions(Citizen $citizen, Item &$item, ?array &$available, ?array &$crossed ) {

        $available = $crossed = [];
        if ($item->getBroken()) return;

        foreach ($item->getPrototype()->getActions() as $action) {
            $mode = $this->evaluate( $citizen, $item, null, $action, $tx );
            if ($mode >= self::ActionValidityAllow) $available[] = $action;
            else if ($mode >= self::ActionValidityCrossed) $crossed[] = $action;
        }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableCampingActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {

      $available = $crossed = [];
      $campingActions = $this->entity_manager->getRepository(CampingActionPrototype::class)->findAll();

      foreach ($campingActions as $action) {
        $mode = $this->evaluate( $citizen, null, null, $action->getAction(), $tx );
        if ($mode >= self::ActionValidityAllow) $available[] = $action;
        else if ($mode >= self::ActionValidityCrossed) $crossed[] = $action;
      }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableHomeActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {

        $available = $crossed = [];
        $home_actions = $this->entity_manager->getRepository(HomeActionPrototype::class)->findAll();

        foreach ($home_actions as $action) {
            $mode = $this->evaluate( $citizen, null, null, $action->getAction(), $tx );
            if ($mode >= self::ActionValidityAllow) $available[] = $action;
            else if ($mode >= self::ActionValidityCrossed) $crossed[] = $action;
        }

    }

    /**
     * @param Citizen $citizen
     * @param ItemAction[] $available
     * @param ItemAction[] $crossed
     */
    public function getAvailableIHeroicActions(Citizen $citizen, ?array &$available, ?array &$crossed ) {
        $available = $crossed = [];

        if (!$citizen->getProfession()->getHeroic()) return;

         foreach ($citizen->getHeroicActions() as $heroic) {
            $mode = $this->evaluate( $citizen, null, null, $heroic->getAction(), $tx );
            if ($mode >= self::ActionValidityAllow) $available[] = $heroic;
            else if ($mode >= self::ActionValidityCrossed) $crossed[] = $heroic;
         }

    }

    /**
     * @param Item|ItemPrototype|Citizen $target
     * @param ItemTargetDefinition $definition
     * @return bool
     */
    public function targetDefinitionApplies($target, ItemTargetDefinition $definition): bool {

        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:
                if (!is_a( $target, Item::class )) return false;
                if ($definition->getHeavy() !== null && $target->getPrototype()->getHeavy() !== $definition->getHeavy()) return false;
                if ($definition->getBroken() !== null && $target->getBroken() !== $definition->getBroken()) return false;
                if ($definition->getPoison() !== null && $target->getPoison() !== $definition->getPoison()) return false;
                if ($definition->getPrototype() !== null && $target->getPrototype()->getId() !== $definition->getPrototype()->getId()) return false;
                if ($definition->getTag() !== null && !$target->getPrototype()->getProperties()->contains($definition->getTag())) return false;
                break;
            case ItemTargetDefinition::ItemTypeSelectionType:
                if (!is_a( $target, ItemPrototype::class )) return false;
                if ($definition->getPrototype() && $target->getId() !== $definition->getPrototype()->getId()) return false;
                if ($definition->getTag() && !$target->getProperties()->contains( $definition->getTag() ) ) return false;
                break;
            case ItemTargetDefinition::ItemHeroicRescueType:
                if (!is_a( $target, Citizen::class )) return false;
                if (!$target->getZone() || !$target->getAlive()) return false;
                if ( round( sqrt(pow($target->getZone()->getX(),2 ) + pow($target->getZone()->getY(),2 )) ) > 2 ) return false;
                break;

            default: return false;
        }

        return true;
    }

    /**
     * @param ItemPrototype|BuildingPrototype|Citizen|string $o
     * @param $c
     * @return string
     */
    private function wrap($o, $c=1) :string {
        $s = ''; $i = null;
        if (is_a($o, ItemPrototype::class)) {
            $s = $this->translator->trans($o->getLabel(), [], 'items');
            $i = 'build/images/item/item_' . $o->getIcon() . '.gif';
        } else if (is_a($o, BuildingPrototype::class)) {
            $s =  $this->translator->trans($o->getLabel(), [], 'buildings');
            $i = 'build/images/building/' . $o->getIcon() . '.gif';
        } else if (is_a($o, Citizen::class)) {
            $s =  $o->getUser()->getUsername();
            $i = 'build/images/professions/' . $o->getProfession()->getIcon() . '.gif';
        }
        else if (is_string($o)) $s = $o;
        else if (is_null($o)) $s = 'NULL';
        else $s = '_UNKNOWN_';

        if (!empty($i)) $i = $this->assets->getUrl( $i );
        return '<span>' . ($c > 1 ? "$c x " : '') . ($i ? "<img alt='' src='$i' />" : '') . $s .  '</span>';
    }

    private function wrap_concat(array $c) {
        return implode(', ', array_map(function(array $e): string {
            return $this->wrap( $e[1], $e[0] );
        }, $this->reformat_prototype_list($c)));
    }

    const ErrorNone = 0;
    const ErrorActionUnregistered = ErrorHelper::BaseActionErrors + 1;
    const ErrorActionForbidden    = ErrorHelper::BaseActionErrors + 2;
    const ErrorActionImpossible   = ErrorHelper::BaseActionErrors + 3;

    /**
     * @param Citizen $citizen
     * @param Item|null $item
     * @param Item|ItemPrototype|Citizen|null $target
     * @param ItemAction $action
     * @param string|null $message
     * @param array|null $remove
     * @return int
     */
    public function execute( Citizen &$citizen, ?Item &$item, &$target, ItemAction $action, ?string &$message, ?array &$remove ): int {

        $remove = [];
        $tags = [];

        $kill_by_poison = $item && $item->getPoison() && ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume);
        $spread_poison = false;

        $mode = $this->evaluate( $citizen, $item, $target, $action, $tx );
        if ($mode <= self::ActionValidityNone)    return self::ErrorActionUnregistered;
        if ($mode <= self::ActionValidityCrossed) return self::ErrorActionImpossible;
        if ($mode <= self::ActionValidityAllow) {
            $message = $tx;
            return self::ErrorActionForbidden;
        }
        if ($mode != self::ActionValidityFull) return self::ErrorActionUnregistered;

        $target_item_prototype = null;
        if ($target && is_a( $target, Item::class )) $target_item_prototype = $target->getPrototype();
        if ($target && is_a( $target, ItemPrototype::class )) $target_item_prototype = $target;

        $execute_info_cache = [
            'ap' => 0,
            'item'   => $item ? $item->getPrototype() : null,
            'target' => $target_item_prototype,
            'citizen' => is_a($target, Citizen::class) ? $target : null,
            'item_morph' => [ null, null ],
            'item_target_morph' => [ null, null ],
            'items_consume' => [],
            'items_spawn' => [],
            'bp_spawn' => [],
            'rp_text' => '',
            'casino' => '',
            'zone' => null,
            'well' => 0,
        ];

        $item_in_chest = $item && $item->getInventory() && $item->getInventory()->getId() === $citizen->getHome()->getChest()->getId();

        $execute_result = function(Result &$result) use (&$citizen, &$item, &$target, &$action, &$message, &$remove, &$execute_result, &$execute_info_cache, &$tags, &$kill_by_poison, &$spread_poison, $item_in_chest) {
            if ($status = $result->getStatus()) {
                if ($status->getResetThirstCounter())
                    $citizen->setWalkingDistance(0);

                if ($status->getCounter() !== null)
                    $citizen->getSpecificActionCounter( $status->getCounter() )->increment();

                if ($status->getInitial() && $status->getResult()) {
                    if ($citizen->getStatus()->contains( $status->getInitial() )) {
                        $this->citizen_handler->removeStatus( $citizen, $status->getInitial() );
                        $this->citizen_handler->inflictStatus( $citizen, $status->getResult() );
                        $tags[] = 'stat-change';
                        $tags[] = "stat-change-{$status->getInitial()->getName()}-{$status->getResult()->getName()}";
                    }
                }
                elseif ($status->getInitial()) {
                    if ($citizen->getStatus()->contains( $status->getInitial() ) && $this->citizen_handler->removeStatus( $citizen, $status->getInitial() )) {
                        $tags[] = 'stat-down';
                        $tags[] = "stat-down-{$status->getInitial()->getName()}";
                    }
                }
                elseif ($status->getResult()) {
                    if (!$citizen->getStatus()->contains( $status->getResult() ) && $this->citizen_handler->inflictStatus( $citizen, $status->getResult() )) {
                        $tags[] = 'stat-up';
                        $tags[] = "stat-up-{$status->getResult()->getName()}";
                    }
                }

            }

            if ($ap = $result->getAp()) {
                $old_ap = $citizen->getAp();
                if ($ap->getMax()) {
                    $to = $this->citizen_handler->getMaxAP($citizen) + $ap->getAp();
                    $this->citizen_handler->setAP( $citizen, false, max( $old_ap, $to ), null );
                } else $this->citizen_handler->setAP( $citizen, true, $ap->getAp(), $ap->getAp() < 0 ? null :$ap->getBonus() );

                $execute_info_cache['ap'] += ( $citizen->getAp() - $old_ap );
            }

            if ($death = $result->getDeath()) {
                $this->death_handler->kill( $citizen, $death->getCause(), $r );
                $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
                foreach ($r as $r_entry) $remove[] = $r_entry;
            }

            if ($bp = $result->getBlueprint()) {
                $possible = $this->entity_manager->getRepository(BuildingPrototype::class)->findProspectivePrototypes( $citizen->getTown() );
                $filtered = array_filter( $possible, function(BuildingPrototype $proto) use ($bp) {
                    if ($bp->getType() !== null && $bp->getType() === $proto->getBlueprint() ) return true;
                    else return $bp->getList()->contains( $proto );
                } );

                if (!empty($filtered)) {
                    $pick = $this->random_generator->pick( $filtered );
                    $town = $citizen->getTown();
                    if ($this->town_handler->addBuilding( $town, $pick )) {
                        $tags[] = 'bp_ok';
                        $execute_info_cache['bp_spawn'][] = $pick;
                        $this->entity_manager->persist( $this->log->constructionsNewSite( $citizen, $pick ) );
                    }

                } else $tags[] = 'bp_fail';
            }

            if ($item && $item_result = $result->getItem()) {
                if ($execute_info_cache['item_morph'][0] === null) $execute_info_cache['item_morph'][0] = $item->getPrototype();
                if ($item_result->getConsume()) {
                    $this->inventory_handler->forceRemoveItem( $item );
                    $execute_info_cache['items_consume'][] = $item->getPrototype();
                } else {
                    if ($item_result->getMorph())
                        $item->setPrototype( $execute_info_cache['item_morph'][1] = $item_result->getMorph() );
                    if ($item_result->getBreak()  !== null) $item->setBroken( $item_result->getBreak() );
                    if ($item_result->getPoison() !== null) $item->setPoison( $item_result->getPoison() );
                }
            }

            if ($target && $target_result = $result->getTarget()) {

                if (is_a($target, Item::class)) {
                    if ($execute_info_cache['item_target_morph'][0] === null) $execute_info_cache['item_target_morph'][0] = $target->getPrototype();
                    if ($target_result->getConsume()) {
                        $this->inventory_handler->forceRemoveItem( $target );
                        $execute_info_cache['items_consume'][] = $target->getPrototype();
                    } else {
                        if ($target_result->getMorph())
                            $target->setPrototype( $execute_info_cache['item_target_morph'][1] = $target_result->getMorph() );
                        if ($target_result->getBreak()  !== null) $target->setBroken( $target_result->getBreak() );
                        if ($target_result->getPoison() !== null) $target->setPoison( $target_result->getPoison() );
                    }
                } elseif (is_a($target, ItemPrototype::class)) {
                    if ($this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem( $target ),
                            $citizen->getZone()
                                ? [ $citizen->getZone()->getFloor(), $citizen->getInventory() ]
                                : ( [ $citizen->getHome()->getChest(), $citizen->getInventory(), $citizen->getTown()->getBank() ])
                        )) $execute_info_cache['items_spawn'][] = $target;
                }
            }

            if ($item_spawn = $result->getSpawn()) {
                for ($i = 0; $i < $item_spawn->getCount(); $i++ ) {
                    $proto = null;
                    if ($p = $item_spawn->getPrototype())
                        $proto = $p;
                    elseif ($g = $item_spawn->getItemGroup())
                        $proto = $this->random_generator->pickItemPrototypeFromGroup( $g );

                    if ($proto) $tags[] = 'spawned';


                    if ($proto && $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem( $proto ),
                            $citizen->getZone()
                                ? [ $citizen->getInventory(), $citizen->getZone()->getFloor() ]
                                : ( $item_in_chest ? [ $citizen->getHome()->getChest(), $citizen->getInventory(), $citizen->getTown()->getBank() ] : [ $citizen->getInventory(), $citizen->getHome()->getChest(), $citizen->getTown()->getBank() ])
                        )) $execute_info_cache['items_spawn'][] = $proto;
                }
            }

            if ($item_consume = $result->getConsume()) {
                $source = $citizen->getZone() ? [$citizen->getInventory(), $citizen->getZone()->getFloor()] : [$citizen->getInventory(), $citizen->getHome()->getChest()];
                $items = $this->inventory_handler->fetchSpecificItems( $source,
                    [new ItemRequest( $item_consume->getPrototype()->getName(), $item_consume->getCount() )] );

                foreach ($items as $consume_item) {

                    if ($consume_item->getPoison()) {
                        if ($action->getPoisonHandler() & ItemAction::PoisonHandlerConsume) $kill_by_poison = true;
                        if ($action->getPoisonHandler() & ItemAction::PoisonHandlerTransgress) $spread_poison = true;
                    }

                    $this->inventory_handler->forceRemoveItem( $consume_item );
                    $execute_info_cache['items_consume'][] = $consume_item->getPrototype();
                }
            }

            if ($zombie_kill = $result->getZombies()) {

                if ($citizen->getZone()) {
                    $kills = min($citizen->getZone()->getZombies(), mt_rand( $zombie_kill->getMin(), $zombie_kill->getMax() ));
                    if ($kills > 0) {
                        $citizen->getZone()->setZombies( $citizen->getZone()->getZombies() - $kills );
                        $this->entity_manager->persist( $this->log->zombieKill( $citizen, $item, $kills ) );
                    }
                }

            }

            if ($home_set = $result->getHome()) {

                $citizen->getHome()->setAdditionalStorage( $citizen->getHome()->getAdditionalStorage() + $home_set->getAdditionalStorage() );
                $citizen->getHome()->setAdditionalDefense( $citizen->getHome()->getAdditionalDefense() + $home_set->getAdditionalDefense() );

            }

            if (($zoneEffect = $result->getZone()) && $base_zone = $citizen->getZone()) {
                if ($zoneEffect->getUncoverZones())
                    for ($x = -1; $x <= 1; $x++)
                        for ($y = -1; $y <= 1; $y++) {
                            /** @var Zone $zone */
                            $zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($citizen->getTown(), $base_zone->getX() + $x, $base_zone->getY() + $y);
                            if ($zone) {
                                $zone->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                                $zone->setZombieStatus( max( $zone->getZombieStatus(), Zone::ZombieStateEstimate ) );
                            }
                        }

                if ($zoneEffect->getUncoverRuin()) {
                    $base_zone->setBuryCount( max(0, $base_zone->getBuryCount() - mt_rand(2,3)) );
                    if ($base_zone->getPrototype())
                        $this->entity_manager->persist( $this->log->outsideUncover( $citizen ) );
                }

                if ($zoneEffect->getEscape() !== null && $zoneEffect->getEscape() > 0)
                    $base_zone->addEscapeTimer( (new EscapeTimer())->setTime( new DateTime("+{$zoneEffect->getEscape()}sec") ) );

              if ($zoneEffect->getImproveLevel()) {
                $base_zone->setImprovementLevel( $base_zone->getImprovementLevel() + $zoneEffect->getImproveLevel() );
              }
            }

            if ($well = $result->getWell()) {

                $add = mt_rand( $well->getFillMin(), $well->getFillMax() );
                $citizen->getTown()->setWell( $citizen->getTown()->getWell() + $add );
                $execute_info_cache['well'] += $add;

                if ($add > 0)
                    $this->entity_manager->persist( $this->log->wellAdd( $citizen, $item, $add) );
            }

            if ($result->getRolePlayerText()) {
                /** @var RolePlayerText|null $text */
                $text = $this->random_generator->pick( $this->entity_manager->getRepository(RolePlayerText::class)->findAll() );
                if ($text && $citizen->getUser()->getFoundTexts()->contains($text))
                    $tags[] = 'rp_fail';
                else {
                    $tags[] = 'rp_ok';
                    $execute_info_cache['rp_text'] = $text->getTitle();
                    $citizen->getUser()->getFoundTexts()->add( $text );
                }
            }

            if($picto = $result->getPicto()){
                $this->picto_handler->give_picto($citizen, $picto->getPrototype());
            }

            if ($result->getCustom())
            {
                $ap     = false;
                $terror = false;
                switch ($result->getCustom()) {
                    // Dice
                    case 1:
                        $dice = [ mt_rand(1, 6), mt_rand(1, 6), mt_rand(1, 6) ];
                        $cmg = $this->translator->trans('Du hast folgendes gewürfelt: {dc1}, {dc2} und {dc3}.', [
                            '{dc1}' => "<b>{$dice[0]}</b>",
                            '{dc2}' => "<b>{$dice[1]}</b>",
                            '{dc3}' => "<b>{$dice[2]}</b>",
                        ], 'items');
                        sort($dice);

                        if ( $dice[0] === $dice[1] && $dice[0] === $dice[2] ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast einen Trippel geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === ($dice[1]-1) && $dice[0] === ($dice[2]-2) ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast eine Straße geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === 1 && $dice[0] === 2 && $dice[2] === 4 ) {
                            $ap = true;
                            $cmg .= ' ' . $this->translator->trans('Wow, du hast beim ersten Versuch eine 4-2-1 geworfen. Das hat so viel Spaß gemacht, dass du 1AP gewinnst!', [], 'items');
                        } else if ( $dice[0] === $dice[1] || $dice[1] === $dice[2] )
                            $cmg .= ' ' . $this->translator->trans('Nicht schlecht, du hast einen Pasch geworfen.', [], 'items');
                        else $cmg .= ' ' . $this->translator->trans('Was für ein Spaß!', [], 'items');

                        $execute_info_cache['casino'] = $cmg;
                        break;
                    // Cards
                    case 2:
                        $card = mt_rand(0, 53);
                        $color = floor($card / 13);
                        $value = $card - ( $color * 13 );

                        if ( $color > 3 ) {
                            if ($value === 0) {
                                $terror = true;
                                $cmg = $this->translator->trans('Du ziehst eine Karte... und stellst fest, dass dein Name darauf mit Blut geschrieben steht! Du erstarrst vor Schreck!', [], 'items');
                            } else {
                                $ap = true;
                                $cmg = $this->translator->trans('Du ziehst eine Karte... und stellst fest, dass du die Karte mit den Spielregeln gezogen hast! Das erheitert dich so sehr, dass du 1AP gewinnst.', [], 'items');
                            }
                        } else {

                            $s_color = $this->translator->trans((['Kreuz','Pik','Herz','Karo'])[$color], [], 'items');
                            $s_value = $value < 9 ? ('' . ($value+2)) : $this->translator->trans((['Bube','Dame','König','Ass'])[$value-9], [], 'items');

                            $cmg = $this->translator->trans('Du ziehst eine Karte... es ist: {color} {value}.', [
                                '{color}' => "<b>{$s_color}</b>",
                                '{value}' => "<b>{$s_value}</b>",
                            ], 'items');

                            if ( $value === 12 ) {
                                $ap = true;
                                $cmg .= ' ' . $this->translator->trans('Das muss ein Zeichen sein! In dieser Welt ist kein Platz für Moral... du erhälst 1AP.', [], 'items');
                            } else if ($value === 10 && $color === 2) {
                                $ap = true;
                                $cmg .= ' ' . $this->translator->trans('Das Symbol der Liebe... dein Herz schmilzt dahin und du erhälst 1AP.', [], 'items');
                            }
                        }

                        $execute_info_cache['casino'] = $cmg;
                        break;
                    // Guitar
                    case 3:
                        $count = 0;
                        foreach ($citizen->getTown()->getCitizens() as $target_citizen) {
                            $this->citizen_handler->inflictStatus( $citizen, 'tg_guitar' );
                            if ($target_citizen->getZone()) continue;
                            else if ($this->citizen_handler->hasStatusEffect($target_citizen, ['drunk','drugged'], false)) {
                                $this->citizen_handler->setAP($target_citizen, true, 2, 0);
                                $count+=2;
                            } else {
                                $this->citizen_handler->setAP($target_citizen, true, 1, 0);
                                $count++;
                            }
                        }
                        $execute_info_cache['casino'] = $this->translator->trans('Mit deiner Gitarre hast du die Stadt gerockt! Die Bürger haben {ap} AP erhalten.', ['{ap}' => $count], 'items');
                        break;

                    // Tamer
                    case 4:case 5: {
                        $heavy = $result->getCustom() === 5;

                        $source = $citizen->getInventory();
                        $bank = $citizen->getTown()->getBank();

                        foreach ( $citizen->getInventory()->getItems() as &$target_item )
                            if ($heavy || !$target_item->getPrototype()->getHeavy())
                                $this->inventory_handler->transferItem($citizen,$target_item,$source,$bank, InventoryHandler::ModalityTamer);

                        break;
                    }

                    // Survivalist
                    case 6:case 7: {
                        $drink = $result->getCustom() === 6;
                        $can_fail = $citizen->getTown()->getDay() > 4;

                        if (!$can_fail || $this->random_generator->chance(0.85)) {

                            if ($drink) $citizen->setWalkingDistance(0);

                            if ($drink && $this->citizen_handler->hasStatusEffect($citizen, 'dehydrated')) {
                                $this->citizen_handler->removeStatus($citizen, 'thirst2');
                                $this->citizen_handler->inflictStatus($citizen, 'thirst1');
                            } else {
                                if (!$drink || !$this->citizen_handler->hasStatusEffect($citizen, 'hasdrunk')) {
                                    $old_ap = $citizen->getAp();
                                    $this->citizen_handler->setAP($citizen, false, 6, 0);
                                    $execute_info_cache['ap'] += ( $citizen->getAp() - $old_ap );
                                }
                                if ($drink) $this->citizen_handler->removeStatus($citizen, 'thirst1');
                                $this->citizen_handler->inflictStatus($citizen, $drink ? 'hasdrunk' : 'haseaten');

                                $execute_info_cache['casino'] = $this->translator->trans($drink ? 'Äußerst erfrischend, und sogar mit einer leichten Note von Cholera.' : 'Immer noch besser als das Zeug, was die Köche in der Stadt zubereiten....', [], 'items');
                            }

                        } else $execute_info_cache['casino'] = $this->translator->trans('Trotz intensiver Suche hast du nichts verwertbares gefunden...', [], 'items');
                        break;
                    }
                    // Heroic teleport action
                    case 8:case 9: {
                        $zone = null; $cp_ok = true;
                        $jumper = null;
                        if ($result->getCustom() === 8 && $citizen->getZone()) {
                            $zone = $citizen->getZone();
                            $cp_ok = $this->zone_handler->check_cp( $zone );
                            $citizen->setZone(null);
                            $zone->removeCitizen( $citizen );
                            $jumper = $citizen;
                        }

                        if ($result->getCustom() === 9 && is_a( $target, Citizen::class )) {
                            $zone = $target->getZone();
                            $this->zone_handler->updateZone( $zone );
                            $cp_ok = $this->zone_handler->check_cp( $zone );
                            $target->setZone(null);
                            $zone->removeCitizen( $target );
                            $jumper = $target;
                        }

                        if (!$zone) break;
                        $others_are_here = $zone->getCitizens()->count() > 0;

                        if ( $zone->getX() !== 0 || $zone->getY() !== 0 ) {
                            $zero_zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition( $zone->getTown(), 0, 0 );
                            if ($others_are_here) $this->entity_manager->persist( $this->log->outsideMove( $jumper, $zone, $zero_zone, true ) );
                            $this->entity_manager->persist( $this->log->outsideMove( $jumper, $zero_zone, $zone, false ) );
                        }
                        $this->entity_manager->persist( $this->log->doorPass( $jumper, true ) );
                        $this->zone_handler->handleCitizenCountUpdate( $zone, $cp_ok );

                        break;
                    }
                    // Set campingTimer
                    case 10: {
                        $date = new DateTime();
                        $citizen->setCampingTimestamp( $date->getTimestamp() );
                        $citizen->setCampingChance( $this->citizen_handler->getCampingChance($citizen) );
                        $dig_timers = $citizen->getDigTimers();
                        foreach ($dig_timers as $timer) {
                            $timer->setPassive(true);
                        }
                        if ($citizen->getEscortSettings()) {
                            $remove[] = $citizen->getEscortSettings();
                            $citizen->setEscortSettings(null);
                        }


                        break;
                    }
                    // Reset campingTimer
                    case 11:
                    {
                        $citizen->setCampingTimestamp(0);
                        $citizen->setCampingChance(0);
                        break;
                    }

                    // Discover a random ruin
                    case 12:
                    {
                        $list = [];
                        foreach ($citizen->getTown()->getZones() as $zone)
                            if ($zone->getDiscoveryStatus() === Zone::DiscoveryStateNone && $zone->getPrototype())
                                $list[] = $zone;

                        $selected = $this->random_generator->pick($list);
                        if ($selected) {
                            $execute_info_cache['zone'] = $selected;
                            $tags[] = 'zone';
                            $selected->setDiscoveryStatus( Zone::DiscoveryStateCurrent );
                        }
                        break;

                    }

                    // Increase town temp defense for the watchtower by ten
                    case 13: {
                        $cn = $this->town_handler->getBuilding( $citizen->getTown(), 'small_watchmen_#00', true );
                        if ($cn) $cn->setTempDefenseBonus( $cn->getTempDefenseBonus() + 10 );
                        break;
                    }

                    // Fill water weapons
                    case 14: {

                        $trans = [
                            'watergun_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'watergun_3_#00' ),
                            'watergun_opt_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'watergun_opt_5_#00' ),
                            'grenade_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'grenade_#00' ),
                            'bgrenade_empty_#00' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'bgrenade_#00' ),
                            'kalach_#01' => $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( 'kalach_#00' ),
                        ];

                        foreach ($citizen->getInventory()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()])) $i->setPrototype( $trans[$i->getPrototype()->getName()] );
                        foreach ($citizen->getHome()->getChest()->getItems() as $i) if (isset($trans[$i->getPrototype()->getName()])) $i->setPrototype( $trans[$i->getPrototype()->getName()] );

                        break;
                    }
                }

                if ($ap) {
                    $this->citizen_handler->setAP( $citizen, true, 1, 1 );
                    $execute_info_cache['ap'] += 1;
                }

                if ($terror)
                    $this->citizen_handler->inflictStatus( $citizen, 'error' );
            }

            if ($result_group = $result->getResultGroup()) {
                $r = $this->random_generator->pickResultsFromGroup( $result_group );
                foreach ($r as &$sub_result) $execute_result( $sub_result );
            }
        };

        foreach ($action->getResults() as &$result) $execute_result( $result );

        if ($spread_poison) $item->setPoison( true );
        if ($kill_by_poison && $citizen->getAlive()) {
            $this->death_handler->kill( $citizen, CauseOfDeath::Posion, $r );
            foreach ($r as $r_entry) $remove[] = $r_entry;
            $this->entity_manager->persist( $this->log->citizenDeath( $citizen ) );
        }

        if ($action->getMessage() && !$kill_by_poison) {
            $message = $this->translator->trans( $action->getMessage(), [
                '{ap}'        => $execute_info_cache['ap'],
                '{minus_ap}'  => -$execute_info_cache['ap'],
                '{well}'      => $execute_info_cache['well'],
                '{item}'      => $this->wrap($execute_info_cache['item']),
                '{target}'    => $execute_info_cache['target'] ? $this->wrap($execute_info_cache['target']) : "-",
                '{citizen}'   => $execute_info_cache['citizen'] ? $this->wrap($execute_info_cache['citizen']) : "-",
                '{item_from}' => $execute_info_cache['item_morph'][0] ? ($this->wrap($execute_info_cache['item_morph'][0])) : "-",
                '{item_to}'   => $execute_info_cache['item_morph'][1] ? ($this->wrap($execute_info_cache['item_morph'][1])) : "-",
                '{target_from}' => $execute_info_cache['item_target_morph'][0] ? ($this->wrap($execute_info_cache['item_target_morph'][0])) : "-",
                '{target_to}'   => $execute_info_cache['item_target_morph'][1] ? ($this->wrap($execute_info_cache['item_target_morph'][1])) : "-",
                '{items_consume}' => $this->wrap_concat($execute_info_cache['items_consume']),
                '{items_spawn}'   => $this->wrap_concat($execute_info_cache['items_spawn']),
                '{bp_spawn}'      => $this->wrap_concat($execute_info_cache['bp_spawn']),
                '{rp_text}'       => $this->wrap( $execute_info_cache['rp_text'] ),
                '{zone}'          => $execute_info_cache['zone'] ? $this->wrap( "{$execute_info_cache['zone']->getX()} / {$execute_info_cache['zone']->getY()}" ) : '',
                '{casino}'        => $execute_info_cache['casino'],
            ], 'items' );

            do {
                $message = preg_replace_callback( '/<t-(.*?)>(.*?)<\/t-\1>/' , function(array $m) use ($tags): string {
                    [, $tag, $text] = $m;
                    return in_array( $tag, $tags ) ? $text : '';
                }, $message, -1, $c);
                $message = preg_replace_callback( '/<nt-(.*?)>(.*?)<\/nt-\1>/' , function(array $m) use ($tags): string {
                    [, $tag, $text] = $m;
                    return !in_array( $tag, $tags ) ? $text : '';
                }, $message, -1, $d);
            } while ($c > 0 || $d > 0);
        }


        return self::ErrorNone;
    }

    public function execute_recipe( Citizen &$citizen, Recipe &$recipe, ?array &$remove, ?string &$message ): int {
        $town = $citizen->getTown();
        $c_inv = $citizen->getInventory();
        $t_inv = $citizen->getTown()->getBank();

        switch ( $recipe->getType() ) {
            case Recipe::WorkshopType:case Recipe::ManualInside:
                if ($citizen->getZone()) return ErrorHelper::ErrorActionNotAvailable;
                break;
            case Recipe::ManualOutside:
                if (!$citizen->getZone()) return ErrorHelper::ErrorActionNotAvailable;
                break;
            default: break;
        }

        $remove = [];

        if ($recipe->getType() === Recipe::WorkshopType) {
            $have_saw  = $this->inventory_handler->countSpecificItems( $c_inv, $this->entity_manager->getRepository( ItemPrototype::class )->findOneByName( 'saw_tool_#00' ) ) > 0;
            $have_manu = $this->town_handler->getBuilding($town, 'small_factory_#00', true) !== null;

            $ap = 3 - ($have_saw ? 1 : 0) - ($have_manu ? 1 : 0);
        } else $ap = 0;


        if ( ($citizen->getAp() + $citizen->getBp()) < $ap || $this->citizen_handler->isTired( $citizen ) )
            return ErrorHelper::ErrorNoAP;

        $source_inv = $recipe->getType() === Recipe::WorkshopType ? [ $t_inv ] : ($citizen->getZone() ? [$c_inv,$citizen->getZone()->getFloor()] : [$c_inv, $citizen->getHome()->getChest() ]);
        $target_inv = $recipe->getType() === Recipe::WorkshopType ? [ $t_inv ] : ($citizen->getZone() ? [$c_inv,$citizen->getZone()->getFloor()] : [$c_inv, $citizen->getHome()->getChest(), $t_inv]);

        $items = $this->inventory_handler->fetchSpecificItems( $source_inv, $recipe->getSource() );
        if (empty($items)) return ErrorHelper::ErrorItemsMissing;

        $list = [];
        foreach ($items as $item) {
            $r = $recipe->getSource()->findEntry( $item->getPrototype()->getName() );
            $this->inventory_handler->forceRemoveItem( $item, $r->getChance() );
            $list[] = $item->getPrototype();
        }

        $this->citizen_handler->deductAPBP( $citizen, $ap );

        $new_item = $this->random_generator->pickItemPrototypeFromGroup( $recipe->getResult() );
        $this->inventory_handler->placeItem( $citizen, $this->item_factory->createItem( $new_item ) , $target_inv );

        if ($recipe->getType() === Recipe::WorkshopType)
            $this->entity_manager->persist( $this->log->workshopConvert( $citizen, $items, [$new_item] ) );

        switch ( $recipe->getType() ) {
            case Recipe::WorkshopType:
              switch($recipe->getAction()) {
                case "Öffnen":
                  $base = 'Du hast %item_list% in der Werkstatt geöffnet und erhälst %item%.';
                  break;
                case "Zerlegen":
                  $base = 'Du hast %item_list% in der Werkstatt zu %item% zerlegt.';
                  break;

                default:
                  $base = 'Du hast %item_list% in der Werkstatt zu %item% umgewandelt.';
              }
              $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->findOneByName("r_refine_#00");
              $this->picto_handler->give_picto($citizen, $pictoPrototype);
              break;
            case Recipe::ManualOutside:case Recipe::ManualInside:case Recipe::ManualAnywhere:default:
                $base = 'Du hast %item_list% zu %item% umgewandelt.';
                if ($recipe->getPictoPrototype()) {
                    $this->picto_handler->give_picto($citizen, $recipe->getPictoPrototype());
                }
                break;
        }

        $message = $this->translator->trans( $base, [
            '%item_list%' => $this->wrap_concat( $list ),
            '%item%' => $this->wrap( $new_item ),
        ], 'game' );

        return self::ErrorNone;
    }
}
