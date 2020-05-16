<?php


namespace App\Service;


use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenStatus;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemProperty;
use App\Entity\ItemPrototype;
use App\Structures\ItemRequest;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InventoryHandler
{
    private $container;
    private $entity_manager;
    private $item_factory;
    private $bankAbuseService;
    public function __construct( ContainerInterface $c, EntityManagerInterface $em, ItemFactory $if, BankAntiAbuseService $bankAntiAbuseService)
    {
        $this->entity_manager = $em;
        $this->item_factory = $if;
        $this->container = $c;
        $this->bankAbuseService = $bankAntiAbuseService;
    }

    public function getSize( Inventory $inventory ): int {
        if ($inventory->getCitizen()) {
            $hero = $inventory->getCitizen()->getProfession() && $inventory->getCitizen()->getProfession()->getHeroic();
            $base = 4 + $this->countEssentialItems( $inventory ) + ($hero ? 1 : 0);
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
                $this->entity_manager->getRepository( CitizenHomeUpgradePrototype::class )->findOneByName( 'chest' )
            );
            /** @var CitizenHomeUpgrade $upgrade */
            if ($upgrade) $base += $upgrade->getLevel();

            $base += $inventory->getHome()->getAdditionalStorage();

            return $base;
        }


        return 0;
    }

    public function findStackPrototype( Inventory $inv, Item $item ): ?Item {
        try {
            return $this->entity_manager->createQueryBuilder()->select('i')->from('App:Item', 'i')
                ->where('i.inventory = :inv')->setParameter('inv', $inv)
                ->andWhere('i.id != :id')->setParameter( 'id', $item->getId() ?? -1 )
                ->andWhere('i.poison = :p')->setParameter('p', $item->getPoison())
                ->andWhere('i.broken = :b')->setParameter('b', $item->getBroken())
                ->andWhere('i.essential = :e')->setParameter('e', $item->getEssential())
                ->andWhere('i.prototype = :proto')->setParameter('proto', $item->getPrototype())
                ->orderBy('i.count', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param string|string[]|ItemProperty|ItemProperty[] $props
     * @return ItemPrototype[]
     */
    public function resolveItemProperties( $props ): array {
        if (!is_array( $props )) $props = [$props];
        $props = array_map(function($e):ItemProperty {
            if (!is_string($e)) return $e;
            return $this->entity_manager->getRepository(ItemProperty::class)->findOneByName( $e );
        }, $props);

        $tmp = [];
        foreach ($props as $prop)
            foreach ($prop->getItemPrototypes() as $itemPrototype)
                $tmp[$itemPrototype->getId()] = $itemPrototype;
        return array_values($tmp);
    }

    /**
     * @param Inventory|Inventory[] $inventory
     * @param ItemPrototype|ItemPrototype[]|string $prototype
     * @param bool $is_property
     * @return int
     */
    public function countSpecificItems($inventory, $prototype, bool $is_property = false): int {
        if (is_string( $prototype )) $prototype = $is_property
            ? $this->entity_manager->getRepository(ItemProperty::class)->findOneByName( $prototype )->getItemPrototypes()->getValues()
            : $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName( $prototype );
        if (!is_array($prototype)) $prototype = [$prototype];
        if (!is_array($inventory)) $inventory = [$inventory];
        try {
            return (int)$this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')->from('App:Item', 'i')
                ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
                ->where('i.inventory IN (:inv)')->setParameter('inv', $inventory)
                ->andWhere('p.id IN (:type)')->setParameter('type', $prototype)
                ->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            return 0;
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }

    /**
     * @param Inventory|Inventory[] $inventory
     * @param ItemGroup|ItemRequest[] $requests
     * @return Item[]
     */
    public function fetchSpecificItems($inventory, $requests): array {
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
                $prop = $this->entity_manager->getRepository(ItemProperty::class)->findOneByName( $request->getItemPropertyName() );
                if ($prop) $id_list = array_map(function(ItemPrototype $p): int {
                    return $p->getId();
                }, $prop->getItemPrototypes()->getValues() );
            }

            $qb = $this->entity_manager->createQueryBuilder();
            $qb
                ->select('i.id')->from('App:Item','i')
                ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id');
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
            if ($request->filterPoison()) $qb->andWhere('i.poison = :isp')->setParameter('isp', $request->getPoison());

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

    public function fetchHeavyItems(Inventory $inventory) {
        $qb = $this->entity_manager->createQueryBuilder();
        $qb
            ->select('i.id')->from('App:Item','i')
            ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory)
            ->andWhere('p.heavy = :hv')->setParameter('hv', true);

        $result = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        return array_map(function(array $a): Item { return $this->entity_manager->getRepository(Item::class)->find( $a['id'] ); }, $result);
    }

    public function countHeavyItems(Inventory $inventory): int {
        try {
            return (int)$this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')->from('App:Item', 'i')
                ->leftJoin('App:ItemPrototype', 'p', Join::WITH, 'i.prototype = p.id')
                ->where('i.inventory = :inv')->setParameter('inv', $inventory)
                ->andWhere('p.heavy = :hv')->setParameter('hv', true)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function countEssentialItems(Inventory $inventory): int {
        try {
            return $this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')->from('App:Item', 'i')
                ->where('i.inventory = :inv')->setParameter('inv', $inventory)
                ->andWhere('i.essential = :ev')->setParameter('ev', true)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    const TransferTypeUnknown  = 0;
    const TransferTypeSpawn    = 1;
    const TransferTypeConsume  = 2;
    const TransferTypeRucksack = 3;
    const TransferTypeBank     = 4;
    const TransferTypeHome     = 5;
    const TransferTypeSteal    = 6;
    const TransferTypeLocal    = 7;
    const TransferTypeEscort   = 8;
    const TransferTypeTamer    = 9;
    const TransferTypeImpound  = 10;

    protected function validateTransferTypes( Item &$item, int $target, int $source ): bool {
        $valid_types = [
            self::TransferTypeSpawn => [ self::TransferTypeRucksack, self::TransferTypeBank, self::TransferTypeHome, self::TransferTypeLocal ],
            self::TransferTypeRucksack => [ self::TransferTypeBank, self::TransferTypeLocal, self::TransferTypeHome, self::TransferTypeConsume, self::TransferTypeSteal, self::TransferTypeTamer ],
            self::TransferTypeBank => [ self::TransferTypeRucksack, self::TransferTypeConsume ],
            self::TransferTypeHome => [ self::TransferTypeRucksack, self::TransferTypeConsume ],
            self::TransferTypeSteal => [ self::TransferTypeRucksack, self::TransferTypeHome ],
            self::TransferTypeLocal => [ self::TransferTypeRucksack, self::TransferTypeEscort, self::TransferTypeConsume ],
            self::TransferTypeEscort => [ self::TransferTypeLocal, self::TransferTypeConsume ],
            self::TransferTypeImpound => [ self::TransferTypeTamer ],
        ];

        // Essential items can not be transferred; only allow spawn and consume
        if ($item->getEssential() && $source !== self::TransferTypeSpawn && $target !== self::TransferTypeConsume)
            return false;

        return isset($valid_types[$source]) && in_array($target, $valid_types[$source]);
    }

    protected function singularTransferType( ?Citizen $citizen, ?Inventory $inventory ): int {
        if (!$citizen || !$inventory) return self::TransferTypeUnknown;

        $citizen_is_at_home = $citizen->getZone() === null;

        // Check if the inventory belongs to a town, and if the town is the same town as that of the citizen
        if ($inventory->getTown() && $inventory->getTown()->getId() === $citizen->getTown()->getId())
            return $citizen_is_at_home ? self::TransferTypeBank : self::TransferTypeTamer;

        // Check if the inventory belongs to a house, and if the house is owned by the citizen
        if ($inventory->getHome() && $inventory->getHome()->getId() === $citizen->getHome()->getId())
            return $citizen_is_at_home ? self::TransferTypeHome : self::TransferTypeImpound;

        // Check if the inventory belongs to a house, and if the house is owned by a different citizen of the same town
        if ($inventory->getHome() && $inventory->getHome()->getId() !== $citizen->getHome()->getId() && $inventory->getHome()->getCitizen()->getTown()->getId() === $citizen->getTown()->getId())
            return $citizen_is_at_home ? self::TransferTypeSteal : self::TransferTypeUnknown;

        // Check if the inventory belongs directly to the citizen
        if ($inventory->getCitizen() && $inventory->getCitizen()->getId() === $citizen->getId())
            return self::TransferTypeRucksack;

        // Check if the inventory belongs directly to the citizen
        if ($inventory->getCitizen() && $inventory->getCitizen()->getId() !== $citizen->getId() &&
            $inventory->getCitizen()->getEscortSettings() && $inventory->getCitizen()->getEscortSettings()->getLeader() &&
            $inventory->getCitizen()->getEscortSettings()->getLeader()->getId() === $citizen->getId() &&
            $inventory->getCitizen()->getEscortSettings()->getAllowInventoryAccess())
            return self::TransferTypeEscort;

        // Check if the inventory belongs to the citizens current zone
        if ($inventory->getZone() && !$citizen_is_at_home && $inventory->getZone()->getId() === $citizen->getZone()->getId())
            return self::TransferTypeLocal;

        //ToDo: Check escort
        return self::TransferTypeUnknown;
    }

    protected function transferType( Item &$item, Citizen &$citizen, ?Inventory &$target, ?Inventory &$source, ?int &$target_type, ?int &$source_type): bool {
        $source_type = !$source ? self::TransferTypeSpawn   : self::singularTransferType( $citizen, $source );
        $target_type = !$target ? self::TransferTypeConsume : self::singularTransferType( $citizen, $target );
        return $this->validateTransferTypes($item, $target_type, $source_type);
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

    const ModalityNone             = 0;
    const ModalityTamer            = 1;
    const ModalityImpound          = 2;
    const ModalityEnforcePlacement = 3;

    public function transferItem( ?Citizen &$actor, Item &$item, ?Inventory &$from, ?Inventory &$to, $modality = self::ModalityNone, $allow_extra_bag = false): int {
        // Block Transfer if citizen is hiding
        if ($actor->getZone() && $modality !== self::ModalityImpound && ($actor->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_hide' )) || $actor->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' )))) {
            return self::ErrorTransferBlocked;
        }

        // Check if the source is valid
        if ($item->getInventory() && ( !$from || $from->getId() !== $item->getInventory()->getId() ) )
            return self::ErrorInvalidTransfer;

        if (!$this->transferType($item, $actor, $to, $from, $type_to, $type_from ))
            return $item->getEssential() ? self::ErrorEssentialItemBlocked : self::ErrorInvalidTransfer;

        // Check inventory size
        if ($modality !== self::ModalityEnforcePlacement && ($to && ($max_size = $this->getSize($to)) > 0 && count($to->getItems()) >= $max_size ) ) return self::ErrorInventoryFull;

        // Check exp_b items already in inventory
        // This snippet restores original Hordes functionality, but was intentionally left out.

        if(!$allow_extra_bag){
            if (($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) &&
              (in_array($item->getPrototype()->getName(), ['bagxl_#00', 'bag_#00', 'cart_#00']) &&
              (
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'bagxl_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'bag_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'cart_#00' ) ] ))
              ))) {
              return self::ErrorExpandBlocked;
            }
        }

        // Check Heavy item limit
        if ($item->getPrototype()->getHeavy() &&
            ($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) &&
            $this->countHeavyItems($to)
        ) return self::ErrorHeavyLimitHit;

        // Check Soul limit
        $soul_name = array("soul_blue_#00", "soul_blue_#01");
        if($type_to === self::TransferTypeRucksack && in_array($item->getPrototype()->getName(), $soul_name) &&
            !$actor->hasRole("shaman") && 
            $this->countSpecificItems($to, $item->getPrototype()) > 0){
            return self::ErrorTooManySouls;
        }

        if ($type_from === self::TransferTypeEscort) {
            // Prevent undroppable items
            if ($item->getEssential() || $item->getPrototype()->hasProperty('esc_fixed')) return self::ErrorEscortDropForbidden;
        }

        if ($type_from === self::TransferTypeBank) {
            if ($actor->getBanished()) return self::ErrorBankBlocked;

            //Bank Anti abuse system
            if (!$this->bankAbuseService->allowedToTake($actor))
            {
                return InventoryHandler::ErrorBankLimitHit;
            }

            $this->bankAbuseService->increaseBankCount($actor);

        }

        if ( $type_to === self::TransferTypeSteal && !$to->getHome()->getCitizen()->getAlive())
            return self::ErrorInvalidTransfer;

        if ($type_from === self::TransferTypeSteal || $type_to === self::TransferTypeSteal) {
            if (!$actor->getTown()->getChaos() && $actor->getStatus()->contains( $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_steal' ) ))
                return self::ErrorStealLimitHit;

            $victim = $type_from === self::TransferTypeSteal ? $from->getHome()->getCitizen() : $to->getHome()->getCitizen();
            if ($victim->getAlive()) {
                $ch = $this->container->get(CitizenHandler::class);
                if ($ch->houseIsProtected( $victim )) return self::ErrorStealBlocked;
                if ($item->getPrototype() === $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName("trapma_#00"))
                    return self::ErrorUnstealableItem;
            }
        }

        if ($type_from === self::TransferTypeRucksack && $type_to === self::TransferTypeTamer && $modality !== self::ModalityTamer && $modality !== self::ModalityImpound)
            return self::ErrorInvalidTransfer;

        if ($type_from === self::TransferTypeImpound && $type_to === self::TransferTypeTamer && $modality !== self::ModalityImpound)
            return self::ErrorInvalidTransfer;

        if ($to)
            $this->forceMoveItem( $to, $item );
        else $this->forceRemoveItem( $item );

        return self::ErrorNone;
    }

    /**
     * @param Citizen $citizen
     * @param Item $item
     * @param Inventory[] $inventories
     * @param bool $force
     * @return Inventory|null
     */
    public function placeItem( Citizen $citizen, Item $item, array $inventories, bool $force = false ): ?Inventory {
        $source = null;
        foreach ($inventories as $inventory)
            if ($this->transferItem( $citizen, $item, $source, $inventory, $force ? self::ModalityEnforcePlacement : self::ModalityNone ) == self::ErrorNone)
                return $inventory;
        return null;
    }

    /**
     * @param Citizen $citizen
     * @param Inventory $source
     * @param Item $item
     * @param Inventory[] $inventories
     * @return bool
     */
    public function moveItem( Citizen $citizen, Inventory $source, Item $item, array $inventories ): bool {
        foreach ($inventories as $inventory)
            if ($this->transferItem( $citizen, $item, $source, $inventory ) == self::ErrorNone)
                return true;
        return false;
    }

    public function forceMoveItem( Inventory $to, Item $item ): Item {
        if ($item->getInventory() && $item->getInventory()->getId() === $to->getId())
            return $item;

        if ($item->getInventory()) {
            $inv = $item->getInventory();
            if ($item->getCount() > 1) {
                $item->setCount( $item->getCount() - 1);
                $this->entity_manager->persist( $item );
                $item = $this->item_factory->createBaseItemCopy( $item );
            } else
                $item->getInventory()->removeItem( $item );
            $this->entity_manager->persist($inv);
        }

        // This is a bank inventory
        if ($to->getTown() && $possible_stack = $this->findStackPrototype( $to, $item )) {
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

    public function forceRemoveItem( Item $item, int $count = 1 ) {
        if ($item->getCount() > $count) {
            $item->setCount($item->getCount() - $count);
            $this->entity_manager->persist($item);
        } else {
            $item->getInventory()->removeItem($item);
            $this->entity_manager->remove( $item );
        }
    }

}