<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Semaphore;
use App\Controller\CustomAbstractCoreController;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\HomeIntrusion;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use App\Entity\RuinExplorerStats;
use App\Enum\ActionHandler\PointType;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Enum\ItemPoisonType;
use App\Event\Game\Items\TransferItemEvent;
use App\Service\Actions\Cache\InvalidateTagsInAllPoolsAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\TownHandler;
use App\Service\ZoneHandler;
use App\Structures\TownConf;
use App\Traits\Controller\EventChainProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/rest/v1/game/inventory', name: 'rest_game_inventory_', condition: "request.headers.get('Accept') === 'application/json'")]
#[IsGranted('ROLE_USER')]
#[GateKeeperProfile(allow_during_attack: false, record_user_activity: false, only_alive: true, only_with_profession: true)]
class InventoryController extends CustomAbstractCoreController
{
    use EventChainProcessor;

    public function __construct(
        ConfMaster $conf,
        TranslatorInterface $translator,
        //private readonly TagAwareCacheInterface $gameCachePool,
    )
    {
        parent::__construct($conf, $translator);
    }

    /**
     * @param Packages $asset
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[Route(path: '/index', name: 'base_index', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(Packages $asset, EntityManagerInterface $em): JsonResponse {
        return new JsonResponse([
            'global' => [
                'abort' => $this->translator->trans('Abbrechen', [], 'global'),
                'warning' => $this->translator->trans('Achtung', [], 'global'),
                'help' => $this->translator->trans('Hilfe', [], 'global'),
            ],
            'type' => [
                'rucksack' => $this->translator->trans('Rucksack', [], 'game'),
                'chest' => $this->translator->trans('Truhe', [], 'game'),
                'bank' => $this->translator->trans('Bank', [], 'game'),
                'desert' => $this->translator->trans('Am Boden', [], 'game'),
            ],
            'categories' => array_map(fn(ItemCategory $c) => [$c->getId(), $this->translator->trans($c->getLabel(), [], 'items'), $c->getOrdering()],
                $em->getRepository(ItemCategory::class)->findAll()),
            'props' => [
                'nothing' => $this->translator->trans('Nichts Interessantes...', [], 'game'),
                'broken' => $this->translator->trans('Kaputt', [], 'items'),
                'drink-done' => $this->translator->trans('Beachte: Du hast heute bereits getrunken und deine Aktionspunkte für diesen Tag bereits erhalten.', [], 'items'),
                'essential' => $this->translator->trans('Berufsgegenstand', [], 'items'),
                'single_use' => $this->translator->trans('Mal pro Tag verwendbar', [], 'items'),
                'heavy' => $this->translator->trans('Schwerer Gegenstand', [], 'items'),
                'deco' => $this->translator->trans('Einrichtungsgegenstand', [], 'items'),
                'defence' => $this->translator->trans('Verteidigungsgegenstand', [], 'items'),
                'weapon' => $this->translator->trans('Waffe', [], 'items'),
                'nw-weapon' => $this->translator->trans('Nachtwache-Waffen', [], 'items')
            ],
            'actions' => [
                'more' => $asset->getUrl('build/images/icons/small_more2.gif'),
                'more-btn' => $asset->getUrl('build/images/icons/small_more.gif'),
                'search' => $this->translator->trans('Gegenstände suchen', [], 'items'),

                'pickup' => $this->translator->trans('{citizen} bitten etwas aufzuheben.', [], 'game'),

                'uncloak-warn' => $this->translator->trans('Wenn du einen Gegenstand aufnimmst oder ablegst, verlierst du deine Tarnung!', [], 'items'),
                'uncloak-icon' => $asset->getUrl('build/images/icons/uncloak.gif'),

                'steal-btn' => $this->translator->trans('Versuchen etwas zu stehlen', [], 'game'),
                'steal-icon' => $asset->getUrl('build/images/icons/theft.gif'),
                'steal-tooltip' => $this->translator->trans('Diebstahlsversuche können nur <strong>während der Nacht</strong> unternommen werden: Sie haben eine reduzierte Erfolgschance und jeder Fehlschlag wird im Register vermerkt!', [], 'game'),
                'steal-box' => $this->translator->trans('Wähle einen Gegenstand aus der Bank aus...', [], 'game'),
                'steal-confirm' => $this->translator->trans('Achtung! Deinen Mitbürgern wird das gar nicht gefallen.', [], 'game'),

                'hide-btn' => $this->translator->trans('Rucksack verstecken', [], 'game'),
                'hide-icon' => $asset->getUrl('build/images/icons/small_ban.gif'),
                'hide-tooltip' => $this->translator->trans('Mit dieser Aktion kannst du alle Gegenstände in deinem Rucksack in der Zone verstecken. <strong>Sie werden nur für andere verbannte Bürger sichtbar sein.</strong> Eine nicht verbannte Person hat jedoch eine kleine Chance, sie beim Graben zu finden.', [], 'game'),
                'hide-confirm' => $this->translator->trans('Bestätigen?', [], 'global'),
                'hidden-help1' => $this->translator->trans('Ein oder mehrere Gegenstände wurden in dieser Zone von einem verbannten Bürger versteckt.', [], 'game'),
                'hidden-help2' => $this->translator->trans('Nur verbannte Bürger können Gegenstände sehen und aufheben, wenn diese von anderen verbannten Bürgern versteckt wurden.', [], 'game'),

                'down-all-any' => $this->translator->trans('Rucksack ausleeren', [], 'game'),
                'down-all-desert' => $this->translator->trans('Rucksack auf dem Boden ausleeren', [], 'game'),
                'down-all-bank' => $this->translator->trans('Rucksack in der Bank ausleeren', [], 'game'),
                'down-all-icon' => $asset->getUrl('build/images/item/item_bag.gif'),

                'show-categories' => $this->translator->trans('Kategorien anzeigen', [], 'game'),
            ]
        ]);
    }

    protected static function canEnumerate(Citizen $citizen, Inventory $inventory, &$restrictive = false): bool {
        $restrictive = false;
        return
            // Rucksack
            ($inventory->getCitizen() === $citizen) ||
            // Rucksack (escort)
            ($restrictive = ($inventory->getCitizen()?->getEscortSettings()?->getAllowInventoryAccess() && $inventory->getCitizen()?->getEscortSettings()?->getLeader() === $citizen)) ||
            // Chest
            ($inventory->getHome()?->getCitizen() === $citizen && $citizen->getZone() === null) ||
            // Bank
            ($inventory->getTown() === $citizen->getTown() && $citizen->getZone() === null) ||
            // Foreign chest
            ($restrictive = ($inventory->getHome()?->getCitizen()?->getTown() === $citizen->getTown() && $citizen->getZone() === null)) ||
            // Zone floor
            ($inventory->getZone() && $inventory->getZone() === $citizen->getZone()) ||
            // Ruin floor
            ($inventory->getRuinZone() && $citizen->getExplorerStats()->findFirst(fn(int $k, RuinExplorerStats $a) => $a->getActive())?->isAt($inventory->getRuinZone())) ||
            // Ruin room floor
            ($inventory->getRuinZoneRoom() && $citizen->getExplorerStats()->findFirst(fn(int $k, RuinExplorerStats $a) => $a->getActive())?->isAt($inventory->getRuinZoneRoom()));
    }

    protected function renderBagInventory(Citizen $citizen, Inventory $inventory, InventoryHandler $handler, EventProxyService $proxy): array {
        $foreign_chest = false;

        // Special case - foreign chest
        if ($inventory->getHome() && $inventory->getHome()->getCitizen() !== $citizen && $inventory->getHome()->getCitizen()->getAlive())
            $foreign_chest = true;

        $show_banished_hidden = $citizen->getBanished() || $citizen->getTown()->getChaos();
        return [
            'bank' => false,
            'rsc' => false,
            'size' => $foreign_chest ? 0 : $handler->getSize( $inventory ),
            'mods' => [
                'has_drunk' => $citizen->hasStatus('hasdrunk')
            ],
            'items' => $inventory->getItems()
                ->filter( fn(Item $i) => $show_banished_hidden || !$i->getHidden() )
                ->filter( fn(Item $i) => !$foreign_chest || !$i->getPrototype()->getHideInForeignChest() )
                ->map( fn(Item $i) => $this->renderItem( $citizen, $i, $proxy ) )->getValues()
        ];
    }

    protected function renderItem(Citizen $citizen, Item $i, EventProxyService $proxy, ?int $override_count = null, ?int $custom_sort = null): array {
        return [
            'i' => $i->getId(),
            'p' => $i->getPrototype()->getId(),
            'c' => $override_count ?? $i->getCount(),
            'b' => $i->getBroken(),
            'h' => $i->getHidden(),
            'e' => $i->getEssential(),
            'w' => $i->getPrototype()->getWatchpoint() <> 0 ? $this->translator->trans('{watchpoint} pkt attacke', ['watchpoint' => $proxy->buildingQueryNightwatchDefenseBonus( $citizen->getTown(), $i )], 'items') : null,
            's' => [
                ...($custom_sort ? [$custom_sort] : []),
                $i->getEssential() ? 1 : 0,
                $i->getPrototype()->getSort(),
                -$i->getPrototype()->getId(),
                mt_rand(0,9),
            ]
        ];
    }

    protected function renderResourceInventory(Citizen $citizen, Inventory $inventory, InventoryHandler $handler, EventProxyService $proxy, TranslatorInterface $trans, EntityManagerInterface $em, array $rsc = []): array {
        $qb = $em->createQueryBuilder();

        // Select fields - item ID, item count,
        $qb
            ->select('IDENTITY(i.prototype) as p', 'SUM(i.count) as c')->from(Item::class,'i', 'i.prototype')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory)
            ->andWhere('i.prototype IN (:list)')->setParameter('list', $rsc)
            ->andWhere('i.broken = false')
            ->groupBy('i.prototype');

        // Add group by poison state if poison stacking is disabled
        if (!$this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_POISON_STACK, false))
            $qb->andWhere('i.poison = :poison')->setParameter('poison', ItemPoisonType::None->value);

        $data = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        $found = array_map( fn(array $element) => $element['p'], $data );
        foreach ($rsc as $pid)
            if (!in_array($pid, $found))
                $data[] = ['p' => $pid, 'c' => 0];

        return [
            'bank' => false,
            'rsc' => true,
            'mods' => [],
            'items' => $data
        ];
    }


    protected function renderBankInventory(Citizen $citizen, Inventory $inventory, InventoryHandler $handler, EventProxyService $proxy, TranslatorInterface $trans, EntityManagerInterface $em): array {
        $qb = $em->createQueryBuilder();

        // Select fields - item ID, item count, category IDs and orderings
        $qb
            ->select('i.id', 'c.id as l1', 'cr.id as l2','c.ordering as lo1', 'cr.ordering as lo2', 'SUM(i.count) as n')->from(Item::class,'i')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory);

        // Add group by to separate broken/unbroken items, additionally group by poison state if poison stacking is disabled
        if ($this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_POISON_STACK, false))
            $qb->groupBy('i.prototype', 'i.broken');
        else $qb->groupBy('i.prototype', 'i.broken', 'i.poison');

        // Join with category and prototype table
        $qb
            ->leftJoin(ItemPrototype::class, 'p', Join::WITH, 'i.prototype = p.id')
            ->leftJoin(ItemCategory::class, 'c', Join::WITH, 'p.category = c.id')
            ->leftJoin(ItemCategory::class, 'cr', Join::WITH, 'c.parent = cr.id');

        // Request results as array
        $data = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $final = [];
        $cache = [];

        foreach ($data as $entry) {
            // Get category ID ()
            $cid = $entry['l2'] ?? $entry['l1'] ?? -1;

            // Group items by category
            $final[$cid] = [
                ...($final[$cid] ?? []),
                [ $entry['id'], $entry['n'],  ]
            ];

            // Add item ID to cache
            $cache[$entry['id']] = true;
        }

        // Get all items by their ID
        $item_list = $em->getRepository(Item::class)->findAllByIds(array_keys($cache));
        $classic_bank_sort = $this->getUser()?->getClassicBankSort();

        return [
            'bank' => true,
            'rsc' => false,
            'mods' => [
                'has_drunk' => $citizen->hasStatus('hasdrunk')
            ],
            'categories' => array_map( fn($entry, $id) => [
                'id' => $id,
                'items' => (new ArrayCollection($entry))
                    ->map(fn(array $data) => $this->renderItem(
                        $citizen,
                        $item_list[$data[0]],
                        $proxy,
                        $data[1],
                        $classic_bank_sort ? $data[1] : null,
                    ) )
                    ->getValues()
            ], $final, array_keys($final) ),
        ];
    }

    protected function renderInventory(Citizen $citizen, Inventory $inventory, InventoryHandler $handler, EventProxyService $proxy, TranslatorInterface $trans, EntityManagerInterface $em, ?array $rsc = []): array {
        return $inventory->getTown()
            ? (
                empty($rsc)
                    ? $this->renderBankInventory( $citizen, $inventory, $handler, $proxy, $trans, $em )
                    : $this->renderResourceInventory( $citizen, $inventory, $handler, $proxy, $trans, $em, $rsc )
            )
            : $this->renderBagInventory( $citizen, $inventory, $handler, $proxy, $trans );
    }

    #[Route(path: '/{id}', name: 'inventory_get', methods: ['GET'])]
    public function inventory(Request $request, Inventory $inventory, EntityManagerInterface $em, InventoryHandler $handler, EventProxyService $proxy): JsonResponse {
        $citizen = $this->getUser()->getActiveCitizen();

        if (!self::canEnumerate($citizen, $inventory))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        // Special case - foreign chest
        if ($inventory->getHome() && $inventory->getHome()->getCitizen() !== $citizen && $inventory->getHome()->getCitizen()->getAlive()) {

            $hidden = $inventory->getHome()->getCitizenHomeUpgrades()->findFirst( fn(int $k, CitizenHomeUpgrade $c) => $c->getPrototype()->getName() === 'curtain' ) !== null;
            $intrusion = $hidden && $em->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $citizen, 'victim' => $inventory->getHome()->getCitizen()]);

            if ($hidden && !$intrusion) return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $rsc = array_map(fn(string $s) => (int)$s, array_filter(
            explode(',', $request->query->get('rsc', '')),
            fn(string $e) => !empty($e) && is_numeric($e)
        ));

        $data = $this->renderInventory( $citizen, $inventory, $handler, $proxy, $this->translator, $em, $rsc );

        return new JsonResponse($data);
    }

    public function renderIncidentals(Inventory $inventory, CitizenHandler $ch, TownHandler $th, InventoryHandler $ih): array {
        return match (true) {
            $inventory->getHome() !== null => [
                'deco' => $ch->getDecoPoints( $inventory ),
                'homedef' => $th->calculate_home_def( $inventory->getHome() )
            ],
            $inventory->getTown() !== null => [
                'towndef' => $th->calculate_town_def($inventory->getTown(), $defSummary),
                'towndef_items' => $defSummary->item_defense,
                'towndef_item_count' => $ih->countSpecificItems($inventory, $th->getPrototypesForDefenceItems(), false, false),
            ],
            default => [],
        };
    }

    #[Route(path: '/{inventory}/{item}', name: 'inventory_move', methods: ['PATCH'])]
    #[Route(path: '/{inventory}', name: 'inventory_all', methods: ['PATCH'])]
    #[Semaphore('town', scope: 'town')]
    public function moveItem(
        #[MapEntity(id: 'inventory')] Inventory $inventory,
        #[MapEntity(id: 'item')] ?Item $item,
        InventoryHandler $handler, EventProxyService $proxy,
        JSONRequestParser $parser, ZoneHandler $zh,
        CitizenHandler $ch, TownHandler $th, InventoryHandler $ih,
        EntityManagerInterface $em,
        EventFactory $ef, EventDispatcherInterface $ed,
        InvalidateTagsInAllPoolsAction $clearAction,
    ): JsonResponse
    {
        $citizen = $this->getUser()->getActiveCitizen();

        if (!self::canEnumerate($citizen, $inventory, $restrictive_from))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $direction = $parser->get('d', null, ['up','down','down-all']);
        if (!$direction) return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        if ($direction !== 'down-all' && !$item) return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $mod = $parser->get('mod', null, ['theft', 'hide']);

        if ($mod === 'hide' && ($item || $direction !== 'down-all'))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $to = $parser->get_int('to', -1);
        $target_inventory = $em->getRepository(Inventory::class)->find($to);
        if (!$target_inventory || !self::canEnumerate($citizen, $target_inventory, $restrictive_to))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $restrictive = $restrictive_from || $restrictive_to;
        if ($restrictive && ($mod !== null || $direction === 'down-all'))
            return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $reload = false;
        $carrier_items = ['bag_#00','bagxl_#00','cart_#00','pocket_belt_#00'];

        $drop_all = !$restrictive && ($direction === 'down-all' || (
                // dropping item ...
                $direction === 'down' &&
                // item is carrier...
                in_array($item->getPrototype()->getName(), $carrier_items) &&
                // item is single...
                $item->getCount() === 1 &&
                // no other carriers...
                $inventory->getItems()->filter(fn(Item $i) => $i->getId() !== $item->getId() && in_array($i->getPrototype()->getName(), $carrier_items))->isEmpty()
            ));

        $items = match(true) {
            $mod === 'hide' => $inventory->getItems()->filter(fn(Item $i) => !$i->getEssential())->getValues(),
            $drop_all && $direction === 'down-all' => $inventory->getItems()->filter(fn(Item $i) => !$i->getEssential() && !in_array($i->getPrototype()->getName(), $carrier_items))->getValues(),
            $drop_all => $inventory->getItems()->filter(fn(Item $i) => !$i->getEssential())->getValues(),
            $item !== null => [$item],
            default => [],
        };

        $should_hide = $mod === 'hide';
        if (empty($items))  return new JsonResponse([
            'success' => false,
            'messages' => $this->translator->trans('Du hast keine Gegenstände, die du verstecken könntest.', [], 'game'),
        ]);
        $hide_should_succeed = $should_hide && (
            $citizen->getTown()->getChaos() ||
            $citizen->getZone()->getCitizens()->filter(fn(Citizen $c) => !$c->getBanished())->isEmpty()
        );

        if ($should_hide && $citizen->getPoints(PointType::AP) < 2) return new JsonResponse([
            'success' => false,
            'errors' => [ErrorHelper::ErrorNoAP],
        ]);

        $target_citizen = $target_inventory->getCitizen() ?? $inventory->getCitizen() ?? $citizen;

        $errors = [];
        foreach ($items as $current_item) if ($citizen->getAlive() && $target_citizen->getAlive()) {

            if (($error = $this->processEventChainUsing( $ef, $ed, $em,
                                                         $ef->gameInteractionEvent( TransferItemEvent::class )->setup($current_item, $citizen, $inventory, $target_inventory, match (true) {
                                                             $mod === 'theft' => TransferItemModality::BankTheft,
                                                             ($mod === 'hide' && $hide_should_succeed) => TransferItemModality::HideItem,
                                                             default => TransferItemModality::None
                                                         }, $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_CARRY_EXTRA_BAG, false) ? [ TransferItemOption::AllowExtraBag ] : [] )
                    , autoFlush: false, error_messages: $error_messages, lastEvent: $event )) !== null)

                $errors[] = $error;
            /** @var TransferItemEvent $event */
            $reload = $reload || $event->hasSideEffects;
        }

        $success = empty($errors) || count($errors) < count($items) || empty($items);

        if ($success && $should_hide) {
            $ch->setAP($citizen, true, -2);
            $reload = true;

            if (!$hide_should_succeed)
                $this->addFlash('notice', $this->translator->trans('Ein oder mehrere nicht-verbannte Bürger in der Umgebung haben dich dabei beobachtet.<hr/>Du hast 2 Aktionspunkte verbraucht.', [], 'game'));
            else {
                $citizen->getZone()?->setItemsHiddenAt( new \DateTimeImmutable() );
                $em->persist($citizen);
                $this->addFlash('notice', $this->translator->trans('Du brauchst eine Weile, bis du alle Gegenstände versteckt hast, die du bei dir trägst... Ha Ha... Du hast 2 Aktionspunkte verbraucht.', [], 'game'));
            }
        }

        if ($success && $citizen->getZone() && !$citizen->getExplorerStats()->findFirst(fn(int $k, RuinExplorerStats $a) => $a->getActive()) && !$zh->isZoneUnderControl( $citizen->getZone() ) && $ch->getEscapeTimeout( $citizen ) < 0 && $ch->uncoverHunter($citizen)) {
            $this->addFlash('notice', $this->translator->trans('Deine <strong>Tarnung ist aufgeflogen</strong>!', [], 'game'));
            $reload = true;
        }

        try {
            $em->flush();
        } catch (\Throwable $t) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($inventory->getZone()) ($clearAction)("town_{$citizen->getTown()->getId()}_zones_{$inventory->getZone()->getX()}_{$inventory->getZone()->getY()}");
        if ($target_inventory->getZone()) ($clearAction)("town_{$citizen->getTown()->getId()}_zones_{$target_inventory->getZone()->getX()}_{$target_inventory->getZone()->getY()}");

        return new JsonResponse([
            'success' => $success,
            'errors' => $success ? [] : array_unique($errors),
            'reload' => $reload,
            'incidentals' => $reload ? null : [
                ...($this->renderIncidentals($inventory, $ch, $th, $ih)),
                ...($this->renderIncidentals($target_inventory, $ch, $th, $ih)),
            ],
            'messages' => implode('<hr/>', [...($error_messages ?? []), ...$this->renderAllFlashMessages(false)]),
            'source' => $reload ? null : $this->renderInventory( $citizen, $inventory, $handler, $proxy, $this->translator, $em ),
            'target' => $reload ? null : $this->renderInventory( $citizen, $target_inventory, $handler, $proxy, $this->translator, $em ),
        ]);
    }
}
