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
    private ContainerInterface $container;
    private EntityManagerInterface $entity_manager;
    private ItemFactory $item_factory;
    private BankAntiAbuseService $bankAbuseService;
    private UserHandler $user_handler;
    private ConfMaster $conf;
    private RandomGenerator $rand;

    public function __construct( ContainerInterface $c, EntityManagerInterface $em, ItemFactory $if, BankAntiAbuseService $bankAntiAbuseService, UserHandler $uh, ConfMaster $cm, RandomGenerator $r)
    {
        $this->entity_manager = $em;
        $this->item_factory = $if;
        $this->container = $c;
        $this->bankAbuseService = $bankAntiAbuseService;
        $this->user_handler = $uh;
        $this->conf = $cm;
        $this->rand = $r;
    }

    public function getSize( Inventory $inventory ): int {
        if ($inventory->getCitizen()) {
            $hero = $inventory->getCitizen()->getProfession() && $inventory->getCitizen()->getProfession()->getHeroic();
            $base = 4 + $this->countEssentialItems($inventory) + ($hero ? 1 : 0);

            if (
                !empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'bagxl_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'cart_#00' ) ] ))
            )
                $base += 3;
            else if (!empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'bag_#00' ) ] )))
                $base += 2;

            if (!empty($this->fetchSpecificItems( $inventory, [ new ItemRequest( 'pocket_belt_#00' ) ] )))
                $base += 2;

            if($hero && $this->user_handler->hasSkill($inventory->getCitizen()->getUser(), 'largerucksack1'))
                $base += 1;

            return $base;
        }

        if ($inventory->getHome()) {
            $hero = $inventory->getHome()->getCitizen()->getProfession()->getHeroic();
            $base = $hero ? 5 : 4;

            // Check upgrades
            $upgrade = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->findOneByPrototype(
                $inventory->getHome(),
                $this->entity_manager->getRepository( CitizenHomeUpgradePrototype::class )->findOneBy( ['name' => 'chest'] )
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
        try {
            return $this->entity_manager->createQueryBuilder()->select('i')->from(Item::class, 'i')
                ->where('i.inventory = :inv')->setParameter('inv', $inv)
                ->andWhere('i.id != :id')->setParameter( 'id', $item->getId() ?? -1 )
                ->andWhere('i.poison = :p')->setParameter('p', $item->getPoison()->value)
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
            return $this->entity_manager->getRepository(ItemProperty::class)->findOneBy( ['name' => $e] );
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
            ? $this->entity_manager->getRepository(ItemProperty::class)->findOneBy( ['name' => $prototype] )->getItemPrototypes()->getValues()
            : $this->entity_manager->getRepository(ItemPrototype::class)->findOneBy( ['name' => $prototype] );

        if (!is_array($prototype)) $prototype = [$prototype];
        if (!is_array($inventory)) $inventory = [$inventory];

        if (count( $inventory ) === 1 && is_a( $inventory[0], Inventory::class ))
            return array_reduce( array_filter( $inventory[0]->getItems()->getValues(),
                fn(Item $i) =>
                    in_array( $i->getPrototype(), $prototype) &&
                    ( $broken === null || $i->getBroken() === $broken ) &&
                    ( $poison === null || $i->getPoison()->poisoned() === $poison )
            )
        , fn(int $c, Item $i) => $i->getCount() + $c, 0);

        try {
            $qb = $this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')->from(Item::class, 'i')
                ->leftJoin(ItemPrototype::class, 'p', Join::WITH, 'i.prototype = p.id')
                ->where('i.inventory IN (:inv)')->setParameter('inv', $inventory)
                ->andWhere('p.id IN (:type)')->setParameter('type', $prototype);
            if ($broken !== null) $qb->andWhere('i.broken = :broken')->setParameter('broken', $broken);
            if ($poison !== null) $qb->andWhere('i.poison = :poison')->setParameter('poison', $poison);
            return (int)$qb->getQuery()->getSingleScalarResult();
        } catch (UnexpectedResultException $e) {
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
                $prop = $this->entity_manager->getRepository(ItemProperty::class)->findOneBy( ['name' => $request->getItemPropertyName()] );
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

    public function fetchHeavyItems(Inventory $inventory) {
        $qb = $this->entity_manager->createQueryBuilder();
        $qb
            ->select('i.id')->from(Item::class,'i')
            ->leftJoin(ItemPrototype::class, 'p', Join::WITH, 'i.prototype = p.id')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory)
            ->andWhere('p.heavy = :hv')->setParameter('hv', true);

        $result = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
        return array_map(function(array $a): Item { return $this->entity_manager->getRepository(Item::class)->find( $a['id'] ); }, $result);
    }

    public function countHeavyItems(Inventory $inventory): int {
        $c = 0;
        foreach ($inventory->getItems() as $item)
            if ($item->getPrototype()->getHeavy()) $c += $item->getCount();
        return $c;
    }

    public function countEssentialItems(Inventory $inventory): int {
        try {
            return $this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')->from(Item::class, 'i')
                ->where('i.inventory = :inv')->setParameter('inv', $inventory)
                ->andWhere('i.essential = :ev')->setParameter('ev', true)
                ->getQuery()->getSingleScalarResult() ?? 0;
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
            self::TransferTypeSpawn => [ self::TransferTypeRucksack, self::TransferTypeBank, self::TransferTypeHome, self::TransferTypeLocal, self::TransferTypeTamer ],
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
            return $citizen_is_at_home ? self::TransferTypeHome : self::TransferTypeTamer;

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
        if ($inventory->getZone() && !$citizen_is_at_home &&
            $inventory->getZone()->getId() === $citizen->getZone()->getId() && !$citizen->activeExplorerStats())
            return self::TransferTypeLocal;

        // Check if the inventory belongs to the citizens current ruin zone
        if ($inventory->getRuinZone() && !$citizen_is_at_home &&
            $inventory->getRuinZone()->getZone()->getId() === $citizen->getZone()->getId() &&
            ($ex = $citizen->activeExplorerStats()) && /*!$ex->getInRoom() &&*/
            $ex->getX() === $inventory->getRuinZone()->getX() && $ex->getY() === $inventory->getRuinZone()->getY()  )
            return self::TransferTypeLocal;

        return self::TransferTypeUnknown;
    }

    protected function transferType( Item &$item, Citizen &$citizen, ?Inventory &$target, ?Inventory &$source, ?int &$target_type, ?int &$source_type): bool {
        $source_type = !$source ? self::TransferTypeSpawn   : $this->singularTransferType( $citizen, $source );
        $target_type = !$target ? self::TransferTypeConsume : $this->singularTransferType( $citizen, $target );
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
    const ErrorBankTheftFailed      = ErrorHelper::BaseInventoryErrors + 14;
    const ErrorTargetChestFull      = ErrorHelper::BaseInventoryErrors + 15;
    const ErrorTransferStealPMBlock = ErrorHelper::BaseInventoryErrors + 16;
    const ErrorTransferStealDropInvalid = ErrorHelper::BaseInventoryErrors + 17;

    const ModalityNone             = 0;
    const ModalityTamer            = 1;
    const ModalityImpound          = 2;
    const ModalityEnforcePlacement = 3;
    const ModalityBankTheft        = 4;
    const ModalityAllowMultiHeavy  = 5;

    public function transferItem( ?Citizen &$actor, Item &$item, ?Inventory $from, ?Inventory $to, $modality = self::ModalityNone, $allow_extra_bag = false): int {
        // Block Transfer if citizen is hiding
        if ($actor->getZone() && $modality !== self::ModalityImpound && $modality !== self::ModalityEnforcePlacement && ($actor->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_hide' )) || $actor->getStatus()->contains($this->entity_manager->getRepository(CitizenStatus::class)->findOneByName( 'tg_tomb' )))) {
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
        if (!$allow_extra_bag && $modality !== self::ModalityEnforcePlacement){
            if (($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) &&
              (in_array($item->getPrototype()->getName(), ['bagxl_#00', 'bag_#00', 'cart_#00']) &&
              (
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'bagxl_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'bag_#00' ) ] )) ||
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'cart_#00' ) ] ))
              ))) {
              return self::ErrorExpandBlocked;
            } else if (($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) &&
                $item->getPrototype()->getName() == "pocket_belt_#00" &&
                !empty($this->fetchSpecificItems( $to, [ new ItemRequest( 'pocket_belt_#00' ) ] ))) {
                return self::ErrorExpandBlocked;
            }
        }

        // Check Heavy item limit
        if ($modality !== self::ModalityAllowMultiHeavy && $item->getPrototype()->getHeavy() &&
            ($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) &&
            $this->countHeavyItems($to)
        ) return self::ErrorHeavyLimitHit;

        // Check Soul limit
        $soul_names = array("soul_blue_#00", "soul_blue_#01", "soul_red_#00", "soul_yellow_#00");
        if( ($type_to === self::TransferTypeRucksack || $type_to === self::TransferTypeEscort) && $to->getCitizen() && in_array($item->getPrototype()->getName(), $soul_names) && !$to->getCitizen()->hasRole("shaman") && $to->getCitizen()->getProfession()->getName() !== "shaman"){
            foreach($soul_names as $soul_name) {
                if($this->countSpecificItems($to, $soul_name) > 0) {
                    return self::ErrorTooManySouls;
                }
            }
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
                $this->bankAbuseService->increaseBankCount($actor);
                return InventoryHandler::ErrorBankLimitHit;
            }

            $this->bankAbuseService->increaseBankCount($actor);

            //if ($modality === self::ModalityBankTheft && $this->rand->chance(0.6667))
            //    return InventoryHandler::ErrorBankTheftFailed;
        }

        if ( $type_to === self::TransferTypeSteal && !$to->getHome()->getCitizen()->getAlive())
            return self::ErrorInvalidTransfer;

        if ($type_from === self::TransferTypeSteal || $type_to === self::TransferTypeSteal) {

            if ($type_to === self::TransferTypeSteal && $actor->getTown()->getChaos() )
                return TownController::ErrorTownChaos;

            $victim = $type_from === self::TransferTypeSteal ? $from->getHome()->getCitizen() : $to->getHome()->getCitizen();
            if ($victim->getAlive()) {
                $ch = $this->container->get(CitizenHandler::class);
                if ($ch->houseIsProtected( $victim )) return self::ErrorStealBlocked;
                if ($item->getPrototype()->getName() === 'trapma_#00' && $type_from === self::TransferTypeSteal)
                    return self::ErrorUnstealableItem;
            }
        }

        if ($type_from === self::TransferTypeSpawn && $type_to === self::TransferTypeTamer && $modality !== self::ModalityNone && (!$actor->getZone() || !$actor->getZone()->isTownZone()) )
            return self::ErrorInvalidTransfer;

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
            if ($inventory && $this->transferItem( $citizen, $item, $source, $inventory ) === self::ErrorNone)
                return $inventory;
        if ($force) foreach (array_reverse($inventories) as $inventory)
            if ($inventory && $this->transferItem( $citizen, $item, $source, $inventory, self::ModalityEnforcePlacement ) === self::ErrorNone)
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
            if ($item->getInventory()) $item->getInventory()->removeItem($item);
            $this->entity_manager->remove( $item );
        }
    }

    public function getAllInventoryIDs( Town $town, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true ): array {
        // Get all inventory IDs
        // We're just getting IDs, because we don't want to actually hydrate the inventory instances
        $q = $this->entity_manager->createQueryBuilder()
            ->select('i.id')
            ->from(Inventory::class, 'i');
        if ($bank) $q->leftJoin('i.town', 't')->orWhere('t = :town' )->setParameter('town', $town);
        if ($homes) $q->leftJoin('i.home', 'h')->orWhere('h IN (:homes)')->setParameter('homes', array_map( fn(Citizen $c) => $c->getHome(), $town->getCitizens()->getValues()) );
        if ($rucksack) $q->leftJoin('i.citizen', 'c')->orWhere('c IN (:citizens)')->setParameter('citizens', $town->getCitizens());
        if ($floor) $q->leftJoin('i.zone', 'z')->orWhere('z IN (:zones)')->setParameter('zones', $town->getZones());
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
     * @return Item[]
     */
    public function getAllItems( Town $town, $prototype, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true ): array {
        if (!is_array($prototype)) $prototype = [$prototype];
        $prototype = array_map( fn($a) => is_string($a) ? $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($a) : $a, $prototype );

        // Get all items
        return $this->entity_manager->createQueryBuilder()
            ->select('i')
            ->from(Item::class, 'i')
            ->andWhere('i.inventory IN (:invs)')->setParameter('invs', $this->getAllInventoryIDs($town, $bank, $homes, $rucksack, $floor, $ruinFloor))
            ->andWhere('i.prototype IN (:protos)')->setParameter('protos', $prototype)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Town $town
     * @param ItemPrototype|ItemProperty[]|string|string[] $prototype
     * @param bool $bank
     * @param bool $homes
     * @param bool $rucksack
     * @param bool $floor
     * @param bool $ruinFloor
     * @return int
     */
    public function countAllItems( Town $town, $prototype, bool $bank = true, bool $homes = true, bool $rucksack = true, bool $floor = true, bool $ruinFloor = true ): int {
        if (!is_array($prototype)) $prototype = [$prototype];
        $prototype = array_map( fn($a) => is_string($a) ? $this->entity_manager->getRepository(ItemPrototype::class)->findOneByName($a) : $a, $prototype );

        // Get all items
        try {
            return $this->entity_manager->createQueryBuilder()
                ->select('SUM(i.count)')
                ->from(Item::class, 'i')
                ->andWhere('i.inventory IN (:invs)')->setParameter('invs', $this->getAllInventoryIDs($town, $bank, $homes, $rucksack, $floor, $ruinFloor))
                ->andWhere('i.prototype IN (:protos)')->setParameter('protos', $prototype)
                ->getQuery()->getSingleScalarResult();
        } catch (Exception $e) { return 0; }

    }

}
