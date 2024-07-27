<?php


namespace App\Service;


use App\Controller\Town\TownController;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenStatus;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Entity\RuinZone;
use App\Entity\Town;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\ItemPoisonType;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\UnexpectedResultException;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InventoryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entity_manager,
        private readonly ItemFactory $item_factory,
        private readonly UserHandler $user_handler,
        private readonly DoctrineCacheService $doctrineCache,
    ) {}

    public function getSize( Inventory $inventory ): int {
        if ($inventory->getCitizen()) {
            $hero = $inventory->getCitizen()->getProfession() && $inventory->getCitizen()->getProfession()->getHeroic();
            $base = 4 + $this->countEssentialItems($inventory) + ($hero ? 1 : 0) + $inventory->getCitizen()->property( CitizenProperties::InventorySpaceBonus );

            if (
                !empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'bagxl_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'cart_#00' ) ] ))
            )
                $base += 3;
            else if (!empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'bag_#00' ) ] )))
                $base += 2;

            if (!empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'pocket_belt_#00' ) ] )))
                $base += 2;

            return $base;
        }

        if ($inventory->getHome()) {
            $hero = $inventory->getHome()->getCitizen()->getProfession()->getHeroic();
            $base = $hero ? 5 : 4;

            // Check upgrades
            $upgrade = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
                $inventory->getHome(),
                $this->doctrineCache->getEntityByIdentifier( CitizenHomeUpgradePrototype::class, 'chest')
            );
            /** @var CitizenHomeUpgrade $upgrade */
            if ($upgrade) $base += $upgrade->getLevel();

            $base += $inventory->getHome()->getAdditionalStorage();

            return $base;
        }

        return 0;
    }

    public function getFreeSize( Inventory $inv ): int {
        return ($max = $this->getSize($inv)) > 0 ? ($max - count($inv->getItems())) : PHP_INT_MAX;
    }

    public function findStackPrototype( Inventory $inv, Item $item ): ?Item {
        // Items with the <individual> tag cannot stack
        if ($item->getPrototype()->getIndividual()) return null;
            $items = $inv->getItems();
            foreach($items as $compareItem){
                if($item->getPrototype() === $compareItem->getPrototype()
                    && $item->getId() != $compareItem->getId()
                    && $item->getPoison() == $compareItem->getPoison()
                    && $item->getBroken() == $compareItem->getBroken()
                    && $item->getEssential() == $compareItem->getEssential()
                ){
                    return $compareItem;
                }
            }
        return null;
    }

    /**
     * @param string|string[]|ItemProperty|ItemProperty[] $props
     * @return ItemPrototype[]
     */
    public function resolveItemProperties( $props ): array {
        if (!is_array( $props )) $props = [$props];
        $props = array_map(function($e):ItemProperty {
            if (!is_string($e)) return $e;
            return $this->doctrineCache->getEntityByIdentifier(ItemProperty::class, $e);
        }, $props);

        $tmp = [];
        foreach ($props as $prop)
            foreach ($prop->getItemPrototypes() as $itemPrototype)
                $tmp[$itemPrototype->getId()] = $itemPrototype;
        return array_values($tmp);
    }

    /**
     * @param Inventory|Inventory[] $inventory Inventory(ies) to search into
     * @param ItemPrototype|ItemPrototype[]|string $prototype The item prototype or property we're looking for
     * @param bool $is_property Is the prototype string an item property or an item prototype
     * @param bool|null $broken Filter for broken (true) or unbroken (false) items; disable by setting to null (default)
     * @param bool|null $poison Filter for poisoned (true) or normal (false) items; disable by setting to null (default)
     * @return int Number of item matching the filters
     */
    public function countSpecificItems($inventory, $prototype, bool $is_property = false, ?bool $broken = null, ?bool $poison = null): int {
        if (is_string( $prototype )) $prototype = $is_property
            ? $this->doctrineCache->getEntityByIdentifier(ItemProperty::class, $prototype)->getItemPrototypes()->getValues()
            : $this->doctrineCache->getEntityByIdentifier(ItemPrototype::class, $prototype);

        if (!is_array($prototype)) $prototype = [$prototype];
        if (!is_array($inventory)) $inventory = [$inventory];

        $j = 0;
        foreach($inventory as $inv){
            $j = $j + array_reduce( array_filter( $inv->getItems()->getValues(),
                        fn(Item $k) =>
                            in_array( $k->getPrototype(), $prototype) &&
                            ( $broken === null || $k->getBroken() === $broken ) &&
                            ( $poison === null || $k->getPoison()->poisoned() === $poison )
                    )
                    , fn(int $c, Item $i) => $i->getCount() + $c, 0);
        }

        return $j;
    }

    /**
     * @param Inventory|Inventory[] $inventory
     * @param ItemGroup|ItemRequest[] $requests
     * @return Item[]
     */
    public function fetchSpecificItems(Inventory|array $inventory, ItemGroup|array $requests): array {
        $return = [];

        if (is_array($inventory)) $inventory = array_filter($inventory, function(Inventory $i) { return $i->getId() !== null; } );
        elseif ($inventory->getId() === null && $inventory->getItems()->count() == 0) return [];

        if (is_a( $requests, ItemGroup::class )) {
            $tmp = [];
            foreach ($requests->getEntries() as $entry)
                $tmp[] = new ItemRequest( $entry->getPrototype()->getName(), $entry->getChance(), false, false, false );
            $requests = $tmp;
        }

        foreach ($requests as $request) {
            $id_list = [];
            if ($request->isProperty()) {
                $prop = $this->doctrineCache->getEntityByIdentifier(ItemProperty::class, $request->getItemPropertyName());
                if ($prop) $id_list = array_map(function(ItemPrototype $p): int {
                    return $p->getId();
                }, $prop->getItemPrototypes()->getValues() );
            }

            $qb = $this->entity_manager->createQueryBuilder();
            $qb
                ->select('i.id')->from(Item::class,'i')
                ->leftJoin(ItemPrototype::class, 'p', Join::WITH, 'i.prototype = p.id');
            if (!$request->getAll())
                $qb->setMaxResults( $request->getCount() );
            if (is_array($inventory))
                $qb->where('i.inventory IN (:inv)')->setParameter('inv', $inventory);
            else
                $qb->where('i.inventory = :inv')->setParameter('inv', $inventory);


            if ($request->isProperty())
                $qb
                    ->andWhere('p.id IN (:type)')->setParameter('type', $id_list);
            else $qb
                    ->andWhere('p.name = :type')->setParameter('type', $request->getItemPrototypeName());

            if (!empty($return)) $qb->andWhere('i.id NOT IN (:found)')->setParameter('found', $return);
            if ($request->filterBroken()) $qb->andWhere('i.broken = :isb')->setParameter('isb', $request->getBroken());
            if ($request->filterPoison()) $qb->andWhere($request->getPoison() ? 'i.poison != :isp' : 'i.poison = :isp')->setParameter('isp', ItemPoisonType::None->value);

            $result = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);

            $n = 0;
            $return = array_merge($return, array_map(function(array $a) use (&$n): Item {
                /** @var Item $inst */
                $inst = $this->entity_manager->getRepository(Item::class)->find( (int)$a['id'] );
                $n += $inst->getCount();
                return $inst;
            }, $result));

            if (!$request->getAll() && $n < $request->getCount()) return [];
        }

        return $return;
    }

    public function countHeavyItems(Inventory $inventory): int {
        $c = 0;
        foreach ($inventory->getItems() as $item)
            if ($item->getPrototype()->getHeavy()) $c += $item->getCount();
        return $c;
    }

    public function countEssentialItems(Inventory $inventory): int {
        $c = 0;
        foreach ($inventory->getItems() as $item)
            if ($item->getEssential()) $c += $item->getCount();
        return $c;
    }

    const ErrorNone = 0;
    const ErrorInvalidTransfer      = ErrorHelper::BaseInventoryErrors + 1;
    const ErrorInventoryFull        = ErrorHelper::BaseInventoryErrors + 2;
    const ErrorHeavyLimitHit        = ErrorHelper::BaseInventoryErrors + 3;
    const ErrorBankLimitHit         = ErrorHelper::BaseInventoryErrors + 4;
    const ErrorStealLimitHit        = ErrorHelper::BaseInventoryErrors + 5;
    const ErrorStealBlocked         = ErrorHelper::BaseInventoryErrors + 6;
    const ErrorBankBlocked          = ErrorHelper::BaseInventoryErrors + 7;
    const ErrorExpandBlocked        = ErrorHelper::BaseInventoryErrors + 8;
    const ErrorTransferBlocked      = ErrorHelper::BaseInventoryErrors + 9;
    const ErrorUnstealableItem      = ErrorHelper::BaseInventoryErrors + 10;
    const ErrorEscortDropForbidden  = ErrorHelper::BaseInventoryErrors + 11;
    const ErrorEssentialItemBlocked = ErrorHelper::BaseInventoryErrors + 12;
    const ErrorTooManySouls         = ErrorHelper::BaseInventoryErrors + 13;
    const ErrorBankTheftFailed      = ErrorHelper::BaseInventoryErrors + 14;
    const ErrorTargetChestFull      = ErrorHelper::BaseInventoryErrors + 15;
    const ErrorTransferStealPMBlock = ErrorHelper::BaseInventoryErrors + 16;
    const ErrorTransferStealDropInvalid = ErrorHelper::BaseInventoryErrors + 17;


    public function forceMoveItem( Inventory $to, Item $item, int $count = 1 ): Item {
        if ($item->getInventory() && $item->getInventory()->getId() === $to->getId())
            return $item;

        if ($inv = $item->getInventory()) {
            if ($item->getCount() > $count) {
                $item->setCount( $item->getCount() - $count);
                $this->entity_manager->persist( $item );
                $item = $this->item_factory->createBaseItemCopy( $item )->setCount($count);
            } else
                $item->getInventory()->removeItem( $item );
            $this->entity_manager->persist($inv);
        }

        // This is a bank or a building inventory
        if (($to->getTown() || $to->getBuilding()) && $possible_stack = $this->findStackPrototype( $to, $item )) {
            $possible_stack->setCount( $possible_stack->getCount() + $item->getCount() );
            $this->entity_manager->persist($possible_stack);
            $this->entity_manager->remove( $item );
            $item = $possible_stack;
        } else {
            $to->addItem( $item );
            $this->entity_manager->persist($item);
            $this->entity_manager->persist($to);
        }

        return $item;
    }

    public function forceRemoveItem( Item $item, int $count = 1 ): void {
        if ($item->getCount() > $count) {
            $item->setCount($item->getCount() - $count);
            $this->entity_manager->persist($item);
        } else {
            if ($item->getInventory()) $item->getInventory()->removeItem($item);
            $this->entity_manager->remove( $item );
        }
    }

    public function getAllInventoryIDs( Town $town, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true, bool $buildings = true ): array {
        // Get all inventory IDs
        // We're just getting IDs, because we don't want to actually hydrate the inventory instances
        $q = $this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i');
        if ($bank) $q->leftJoin('i.town', 't')->orWhere('t = :town' )->setParameter('town', $town);
        if ($homes) $q->leftJoin('i.home', 'h')->orWhere('h IN (:homes)')->setParameter('homes', array_map( fn(Citizen $c) => $c->getHome(), $town->getCitizens()->getValues()) );
        if ($rucksack) $q->leftJoin('i.citizen', 'c')->orWhere('c IN (:citizens)')->setParameter('citizens', $town->getCitizens());
        if ($floor) $q->leftJoin('i.zone', 'z')->orWhere('z IN (:zones)')->setParameter('zones', $town->getZones());
		if ($buildings) $q->leftJoin('i.building', 'b')->orWhere('b IN (:buildings)')->setParameter('buildings', $town->getBuildings());
        if ($ruinFloor) $q
            ->leftJoin('i.ruinZone', 'rz')->orWhere('rz IN (:ruinZones)')->setParameter('ruinZones', $this->entity_manager->getRepository(RuinZone::class)->findBy(['zone' => $town->getZones()->getValues()]) )
            ->leftJoin('i.ruinZoneRoom', 'rzr')->orWhere('rzr IN (:ruinZones)');
        return array_column($q->getQuery()->getScalarResult(), 'id');
    }

    /**
     * @param Town $town
     * @param ItemPrototype|ItemProperty[]|string|string[] $prototype
     * @param bool $bank
     * @param bool $homes
     * @param bool $rucksack
     * @param bool $floor
     * @param bool $ruinFloor
	 * @param bool $buildings
     * @return Item[]
     */
    public function getAllItems( Town $town, $prototype, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true, bool $buildings = true ): array {
        if (!is_array($prototype)) $prototype = [$prototype];
        $prototype = array_map( fn($a) => is_string($a) ? $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($a) : $a, $prototype );

        // Get all items
        return $this->entity_manager->createQueryBuilder()
            ->select('i')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory IN (:invs)')->setParameter('invs', $this->getAllInventoryIDs($town, $bank, $homes, $rucksack, $floor, $ruinFloor, $buildings))
            ->andWhere('i.prototype IN (:protos)')->setParameter('protos', $prototype)
            ->getQuery()
            ->getResult();
    }

	/**
	 * @param Town                                         $town
	 * @param ItemPrototype|ItemProperty[]|string|string[] $prototype
	 * @param bool                                         $bank
	 * @param bool                                         $homes
	 * @param bool                                         $rucksack
	 * @param bool                                         $floor
	 * @param bool                                         $ruinFloor
	 * @param bool                                         $buildings
	 * @return int
	 */
    public function countAllItems( Town $town, $prototype, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true, bool $buildings = true ): int {
        if (!is_array($prototype)) $prototype = [$prototype];
        $prototype = array_map( fn($a) => is_string($a) ? $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($a) : $a, $prototype );

        // Get all items
        try {
            return $this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')
                ->from(Item::class, 'i')
                ->andWhere('i.inventory IN (:invs)')->setParameter('invs', $this->getAllInventoryIDs($town, $bank, $homes, $rucksack, $floor, $ruinFloor, $buildings))
                ->andWhere('i.prototype IN (:protos)')->setParameter('protos', $prototype)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) { return 0; }

    }

}
