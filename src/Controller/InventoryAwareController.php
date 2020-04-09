<?php

namespace App\Controller;

use App\Entity\CampingActionPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\ExpeditionRoute;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemAction;
use App\Entity\ItemGroupEntry;
use App\Entity\ItemPrototype;
use App\Entity\ItemTargetDefinition;
use App\Entity\Recipe;
use App\Entity\TownLogEntry;
use App\Interfaces\RandomGroup;
use App\Response\AjaxResponse;
use App\Service\ActionHandler;
use App\Service\CitizenHandler;
use App\Service\DeathHandler;
use App\Service\ErrorHelper;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Structures\BankItem;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryAwareController extends AbstractController implements GameInterfaceController, GameProfessionInterfaceController, GameAliveInterfaceController
{
    protected $entity_manager;
    protected $inventory_handler;
    protected $citizen_handler;
    protected $action_handler;
    protected $translator;
    protected $log;
    protected $time_keeper;
    protected $random_generator;

    public function __construct(
        EntityManagerInterface $em, InventoryHandler $ih, CitizenHandler $ch, ActionHandler $ah, DeathHandler $dh,
        TranslatorInterface $translator, LogTemplateHandler $lt, TimeKeeperService $tk, RandomGenerator $rd)
    {
        $this->entity_manager = $em;
        $this->inventory_handler = $ih;
        $this->citizen_handler = $ch;
        $this->citizen_handler->upgrade( $dh );
        $this->action_handler = $ah;
        $this->translator = $translator;
        $this->log = $lt;
        $this->time_keeper = $tk;
        $this->random_generator = $rd;
    }

    protected function addDefaultTwigArgs( ?string $section = null, ?array $data = null ): array {
        $data = $data ?? [];
        $data['menu_section'] = $section;

        $data['clock'] = [
            'desc'      => $this->getActiveCitizen()->getTown()->getName(),
            'day'       => $this->getActiveCitizen()->getTown()->getDay(),
            'timestamp' => new DateTime('now'),
            'attack'    => $this->time_keeper->secondsUntilNextAttack(null, true)
        ];
        $data['ap'] = $this->getActiveCitizen()->getAp();
        $data['max_ap'] = $this->citizen_handler->getMaxAP( $this->getActiveCitizen() );
        $data['banished'] = $this->getActiveCitizen()->getBanished();
        $data['bp'] = $this->getActiveCitizen()->getBp();
        $data['max_bp'] = $this->citizen_handler->getMaxBP( $this->getActiveCitizen() );
        $data['status'] = $this->getActiveCitizen()->getStatus();
        $data['rucksack'] = $this->getActiveCitizen()->getInventory();
        $data['rucksack_size'] = $this->inventory_handler->getSize( $this->getActiveCitizen()->getInventory() );
        return $data;
    }

    protected function getActiveCitizen(): Citizen {
        return $this->entity_manager->getRepository(Citizen::class)->findActiveByUser($this->getUser());
    }

    protected function renderLog( ?int $day, $citizen = null, $zone = null, ?int $type = null, ?int $max = null ): Response {
        return $this->render( 'ajax/game/log_content.html.twig', [
            'entries' => $this->entity_manager->getRepository(TownLogEntry::class)->findByFilter(
                $this->getActiveCitizen()->getTown(),
                $day, $citizen, $zone, $type, $max
            )
        ] );
    }

    /**
     * @param Inventory[] $inventories
     * @param ItemTargetDefinition $definition
     * @return array
     */
    private function decodeActionItemTargets( array $inventories, ItemTargetDefinition $definition ) {
        $targets = [];

        switch ($definition->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:
                foreach ($inventories as &$inv)
                    foreach ($inv->getItems() as &$item)
                        if ($this->action_handler->targetDefinitionApplies($item,$definition))
                            $targets[] = [ $item->getId(), $this->translator->trans( $item->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$item->getPrototype()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemTypeSelectionType:
                if ($definition->getTag())
                    foreach ($this->inventory_handler->resolveItemProperties($definition->getTag()) as &$prop)
                        /** @var $prop ItemPrototype */
                        $targets[] = [ $prop->getId(), $this->translator->trans( $prop->getLabel(), [], 'items' ), "build/images/item/item_{$prop->getIcon()}.gif" ];

                if ($definition->getPrototype())
                    $targets[] = [ $definition->getPrototype()->getId(), $this->translator->trans( $definition->getPrototype()->getLabel(), [], 'items' ), "build/images/item/item_{$definition->getPrototype()->getIcon()}.gif" ];

                break;
            case ItemTargetDefinition::ItemHeroicRescueType:

                foreach ($this->getActiveCitizen()->getTown()->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen->getZone() && round( sqrt(pow($citizen->getZone()->getX(),2 ) + pow($citizen->getZone()->getY(),2 )) ) <= 2)
                        $targets[] = [ $citizen->getId(), $citizen->getUser()->getUsername(), "build/images/professions/{$citizen->getProfession()->getIcon()}.gif" ];

                break;
        }

        return $targets;
    }

    protected function getHeroicActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $this->action_handler->getAvailableIHeroicActions( $this->getActiveCitizen(),  $available, $crossed );
        if (empty($available) && empty($crossed)) return [];

        foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'action' => $a->getAction(), 'targets' => $a->getAction()->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getAction()->getTarget() ) : null, 'target_mode' => $a->getAction()->getTarget() ? $a->getAction()->getTarget()->getSpawner() : 0, 'crossed' => false ];
        foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'action' => $c->getAction(), 'targets' => null, 'target_mode' => 0, 'crossed' => true ];

        return $ret;
    }

    protected function getCampingActions(): array {
      $ret = [];

      $this->action_handler->getAvailableCampingActions( $this->getActiveCitizen(), $available, $crossed );

      foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'item' => null, 'action' => $a, 'targets' => null, 'crossed' => false ];
      foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'item' => null, 'action' => $c, 'targets' => null, 'crossed' => true ];

      return $ret;
    }

    protected function getItemActions(): array {
        $ret = [];

        $av_inv = [$this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest()];

        $items = [];
        foreach ($this->getActiveCitizen()->getInventory()->getItems() as $item) $items[] = $item;
        if ($this->getActiveCitizen()->getZone() === null) foreach ($this->getActiveCitizen()->getHome()->getChest()->getItems() as $item) $items[] = $item;

        foreach ($items as $item) if (!$item->getBroken()) {

            $this->action_handler->getAvailableItemActions( $this->getActiveCitizen(), $item, $available, $crossed );
            if (empty($available) && empty($crossed)) continue;

            foreach ($available as $a) $ret[] = [ 'id' => $a->getId(), 'item' => $item, 'action' => $a, 'targets' => $a->getTarget() ? $this->decodeActionItemTargets( $av_inv, $a->getTarget() ) : null, 'crossed' => false ];
            foreach ($crossed as $c)   $ret[] = [ 'id' => $c->getId(), 'item' => $item, 'action' => $c, 'targets' => null, 'crossed' => true ];
        }

        return $ret;
    }

    protected function getItemCombinations(bool $inside): array {
        $town = $this->getActiveCitizen()->getTown();
        $source_inv = [ $this->getActiveCitizen()->getInventory(), $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getFloor() : $this->getActiveCitizen()->getHome()->getChest() ];

        $recipes = $this->entity_manager->getRepository(Recipe::class)->findByType( [Recipe::ManualAnywhere, $inside ? Recipe::ManualInside : Recipe::ManualOutside] );
        $out = [];
        $source_db = [];
        foreach ($recipes as $recipe) {
            /** @var Recipe $recipe */
            $found_provoking = false;
            foreach ($recipe->getProvoking() as $proto)
                if ($this->inventory_handler->countSpecificItems( $source_inv, $proto )) {
                    $found_provoking = true;
                    break;
                }

            if (!$found_provoking) continue;
            $out[] = $recipe;

            if ($recipe->getSource())
                foreach ($recipe->getSource()->getEntries() as $entry)
                    /** @var ItemGroupEntry $entry */
                    if (!isset( $source_db[ $entry->getPrototype()->getId() ] ))
                        $source_db[ $entry->getPrototype()->getId() ] = $this->inventory_handler->countSpecificItems( $source_inv, $entry->getPrototype() );
        }

        return [ 'recipes' => $out, 'source_items' => $source_db ];
    }

    protected function renderInventoryAsBank( Inventory $inventory ) {
        $qb = $this->entity_manager->createQueryBuilder();
        $data = $qb
            ->select('i.id', 'c.label as l1', 'cr.label as l2', 'SUM(i.count) as n')->from('App:Item','i')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory)
            ->groupBy('i.prototype', 'i.broken')
            ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
            ->leftJoin('App:ItemCategory', 'c', Join::WITH, 'p.category = c.id')
            ->leftJoin('App:ItemCategory', 'cr', Join::WITH, 'c.parent = cr.id')
            ->orderBy('cr.ordering','ASC')->addOrderBy('c.ordering', 'ASC')->addOrderBy('p.id', 'ASC')->addOrderBy('i.id', 'ASC')
            ->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $final = [];
        foreach ($data as $entry) {
            $label = $entry['l2'] ?? $entry['l1'] ?? 'Sonstiges';
            if (!isset($final[$label])) $final[$label] = [];
            $final[$label][] = new BankItem( $this->entity_manager->getRepository(Item::class)->find( $entry['id'] ), $entry['n'] );
        }
        return $final;
    }

    public function generic_item_api(Inventory &$up_target, Inventory &$down_target, bool $allow_down_all, JSONRequestParser $parser, InventoryHandler $handler): Response {
        $item_id = (int)$parser->get('item', -1);
        $direction = $parser->get('direction', '');
        $allowed_directions = ['up','down'];
        if ($allow_down_all) $allowed_directions[] = 'down-all';
        $item = $item_id < 0 ? null : $this->entity_manager->getRepository(Item::class)->find( $item_id );

        $carrier_items = ['bag_#00','bagxl_#00','cart_#00','pocket_belt_#00'];

        $drop_carriers = false;
        if ($direction === 'down' && $allow_down_all && in_array($item->getPrototype()->getName(), $carrier_items)) {
            $direction = 'down-all';
            $drop_carriers = true;
        }

        if (in_array($direction, $allowed_directions)) {
            $citizen = $this->getActiveCitizen();
            $inv_source = $direction === 'up' ? $down_target : $up_target;
            $inv_target = $direction !== 'up' ? $down_target : $up_target;

            $items = [];
            if ($direction !== 'down-all') {
                $item = $this->entity_manager->getRepository(Item::class)->find( $item_id );
                if ($item && $item->getInventory()) $items = [$item];
            } else
                $items = $drop_carriers ? $citizen->getInventory()->getItems() : array_filter($citizen->getInventory()->getItems()->getValues(), function(Item $i) use ($carrier_items) {
                    return !in_array($i->getPrototype()->getName(), $carrier_items);
                });

            $bank_up = null;
            if ($inv_source->getTown()) $bank_up = true;
            if ($inv_target->getTown()) $bank_up = false;

            $floor_up = null;
            if ($inv_source->getZone()) $floor_up = true;
            if ($inv_target->getZone()) $floor_up = false;

            $steal_up = null;
            if ($inv_source->getHome() && $inv_source->getHome()->getId() !== $citizen->getHome()->getId()) $steal_up = true;
            if ($inv_target->getHome() && $inv_target->getHome()->getId() !== $citizen->getHome()->getId()) $steal_up = false;

            $errors = [];
            $item_count = count($items);
            foreach ($items as $current_item)
                if (($error = $handler->transferItem(
                        $citizen,
                        $current_item,$inv_source, $inv_target
                    )) === InventoryHandler::ErrorNone) {
                    if ($bank_up !== null)  $this->entity_manager->persist( $this->log->bankItemLog( $citizen, $current_item, !$bank_up ) );
                    if ($floor_up !== null) $this->entity_manager->persist( $this->log->beyondItemLog( $citizen, $current_item, !$floor_up ) );
                    if ($steal_up !== null) {

                        $this->citizen_handler->inflictStatus( $citizen, 'tg_steal' );
                        $victim_home = $steal_up ? $inv_source->getHome() : $inv_target->getHome();

                        if ($this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
                            $victim_home,
                            $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( 'alarm' ) ))
                        {
                            $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item, $steal_up ) );
                            $this->citizen_handler->inflictStatus( $citizen, 'terror' );
                            $this->addFlash( 'notice', $this->translator->trans('%victim%s Alarmanlage hat die halbe Stadt aufgeweckt und dich zu Tode erschreckt!', ['%victim%' => $victim_home->getCitizen()->getUser()->getUsername()], 'game') );
                        } elseif ($this->random_generator->chance(0.5)) {
                            $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), $citizen, $current_item, $steal_up ) );
                            $this->addFlash( 'notice', $this->translator->trans('Mist, dein Einbruch bei %victim% ist aufgeflogen...', ['%victim%' => $victim_home->getCitizen()->getUser()->getUsername()], 'game') );
                        } else {
                            $this->entity_manager->persist( $this->log->townSteal( $victim_home->getCitizen(), null, $current_item, $steal_up ) );
                            $this->addFlash( 'notice', $this->translator->trans('Sehr gut, niemand hat dich bei deinem Einbruch bei %victim% beobachtet.', ['%victim%' => $victim_home->getCitizen()->getUser()->getUsername()], 'game') );
                        }

                    }
                    if ($current_item->getInventory())
                        $this->entity_manager->persist($current_item);
                    else $this->entity_manager->remove($current_item);
                } else $errors[] = $error;

            if (count($errors) < $item_count) {
                try {
                    $this->entity_manager->flush();
                } catch (Exception $e) {
                    return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
                }
                return AjaxResponse::success();
            } else
                return AjaxResponse::error($errors[0]);
        }
        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    public function generic_recipe_api(JSONRequestParser $parser, ActionHandler $handler, ?callable $trigger_after = null): Response {
        $citizen = $this->getActiveCitizen();
        $town = $citizen->getTown();

        if (!$parser->has_all(['id'], true))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $id = (int)$parser->get('id');

        /** @var Recipe $recipe */
        $recipe = $this->entity_manager->getRepository(Recipe::class)->find( $id );
        if ($recipe === null || !in_array($recipe->getType(), [Recipe::ManualAnywhere, Recipe::ManualOutside, Recipe::ManualInside]))
            return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (($error = $handler->execute_recipe( $citizen, $recipe, $remove, $message )) !== ActionHandler::ErrorNone )
            return AjaxResponse::error( $error );
        else try {

            if ($trigger_after) $trigger_after($recipe);

            $this->entity_manager->persist($town);
            $this->entity_manager->persist($citizen);
            foreach ($remove as $e) $this->entity_manager->remove( $e );
            $this->entity_manager->flush();
            if ($message) $this->addFlash( 'notice', $message );
            return AjaxResponse::success();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException );
        }
    }

    public function get_map_blob(): array {
        $zones = []; $range_x = [PHP_INT_MAX,PHP_INT_MIN]; $range_y = [PHP_INT_MAX,PHP_INT_MIN];
        foreach ($this->getActiveCitizen()->getTown()->getZones() as $zone) {
            $x = $zone->getX();
            $y = $zone->getY();

            $range_x = [ min($range_x[0], $x), max($range_x[1], $x) ];
            $range_y = [ min($range_y[0], $y), max($range_y[1], $y) ];

            if (!isset($zones[$x])) $zones[$x] = [];
            $zones[$x][$y] = $zone;

        }

        return [
            'zones' =>  $zones,
            'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown( $this->getActiveCitizen()->getTown() ),
            'pos_x'  => $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getX() : 0,
            'pos_y'  => $this->getActiveCitizen()->getZone() ? $this->getActiveCitizen()->getZone()->getY() : 0,
            'map_x0' => $range_x[0],
            'map_x1' => $range_x[1],
            'map_y0' => $range_y[0],
            'map_y1' => $range_y[1],
        ];
    }

    /**
     * @param int $id
     * @param ItemTargetDefinition|null $target
     * @param Inventory[] $inventories
     * @param object|null $return
     * @return bool
     */
    private function extract_target_object(int $id, ?ItemTargetDefinition $target, array $inventories, ?object &$return): bool {
        $return = null;
        if (!$target) return true;

        switch ($target->getSpawner()) {
            case ItemTargetDefinition::ItemSelectionType:
                $return = $this->entity_manager->getRepository(Item::class)->find( $id );
                if (!$return) return false;

                foreach ($inventories as $inventory)
                    if ($inventory->getItems()->contains( $return ))
                        return true;

                return false;
            case ItemTargetDefinition::ItemTypeSelectionType:
                $return = $this->entity_manager->getRepository(ItemPrototype::class)->find( $id );
                if (!$return) return false;
                return true;
            case ItemTargetDefinition::ItemHeroicRescueType:
                $return = $this->entity_manager->getRepository(Citizen::class)->find( $id );
                if ($return->getTown()->getId() !== $this->getActiveCitizen()->getTown()->getId()) {
                    $return = null;
                    return false;
                }
                return true;
                break;

            default: return false;
        }
    }

    public function generic_heroic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var HeroicActionPrototype|null $heroic */
        $heroic = ($action_id < 0) ? null : $this->entity_manager->getRepository(HeroicActionPrototype::class)->find( $action_id );

        if ( !$heroic ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if ($zone && $zone->getX() === 0 && $zone->getY() === null ) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if (!$citizen->getProfession()->getHeroic() || !$citizen->getHeroicActions()->contains( $heroic )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (!$this->extract_target_object( $target_id, $heroic->getAction()->getTarget(), [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $item = null;
        if (($error = $this->action_handler->execute( $citizen, $item, $target, $heroic->getAction(), $msg, $remove )) === ActionHandler::ErrorNone) {

            $heroic_action = $heroic->getAction();
            if ($trigger_after) $trigger_after($heroic_action);
            $citizen->removeHeroicAction( $heroic );

            $this->entity_manager->persist($citizen);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }

    public function generic_action_api(JSONRequestParser $parser, ?callable $trigger_after = null): Response {
        $item_id =   (int)$parser->get('item',   -1);
        $target_id = (int)$parser->get('target', -1);
        $action_id = (int)$parser->get('action', -1);

        /** @var Item|null $item */
        $item   = ($item_id < 0)   ? null : $this->entity_manager->getRepository(Item::class)->find( $item_id );
        /** @var Item|ItemPrototype|null $target */
        $target = null;
        /** @var ItemAction|null $action */
        $action = ($action_id < 0) ? null : $this->entity_manager->getRepository(ItemAction::class)->find( $action_id );

        if ( !$item || !$action || $item->getBroken() ) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        $citizen = $this->getActiveCitizen();

        $zone = $citizen->getZone();
        if ($zone && $zone->getX() === 0 && $zone->getY() === null ) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        $secondary_inv = $zone ? $zone->getFloor() : $citizen->getHome()->getChest();
        if (!$citizen->getInventory()->getItems()->contains( $item ) && !$secondary_inv->getItems()->contains( $item )) return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );
        if (!$this->extract_target_object( $target_id, $action->getTarget(), [ $citizen->getInventory(), $zone ? $zone->getFloor() : $citizen->getHome()->getChest() ], $target ))
            return AjaxResponse::error( ErrorHelper::ErrorActionNotAvailable );

        if (($error = $this->action_handler->execute( $citizen, $item, $target, $action, $msg, $remove )) === ActionHandler::ErrorNone) {

            if ($trigger_after) $trigger_after($action);

            $this->entity_manager->persist($citizen);
            if ($item->getInventory())
                $this->entity_manager->persist($item);
            foreach ($remove as $remove_entry)
                $this->entity_manager->remove($remove_entry);
            try {
                $this->entity_manager->flush();
            } catch (Exception $e) {
                return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['msg' => $e->getMessage()] );
            }

            if ($msg) $this->addFlash( 'notice', $msg );
        } elseif ($error === ActionHandler::ErrorActionForbidden) {
            if (!empty($msg)) $msg = $this->translator->trans($msg, [], 'game');
            return AjaxResponse::error($error, ['message' => $msg]);
        }
        else return AjaxResponse::error( $error );

        return AjaxResponse::success();
    }
}
