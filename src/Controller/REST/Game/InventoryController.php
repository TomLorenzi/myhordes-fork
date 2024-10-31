<?php

namespace App\Controller\REST\Game;

use App\Annotations\GateKeeperProfile;
use App\Annotations\Toaster;
use App\Controller\BeyondController;
use App\Controller\CustomAbstractCoreController;
use App\Entity\ActionCounter;
use App\Entity\Citizen;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\HomeIntrusion;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\LogEntryTemplate;
use App\Entity\RuinExplorerStats;
use App\Entity\Town;
use App\Entity\TownLogEntry;
use App\Entity\Zone;
use App\Enum\Configuration\CitizenProperties;
use App\Enum\Game\LogHiddenType;
use App\Enum\Game\TransferItemModality;
use App\Enum\Game\TransferItemOption;
use App\Event\Game\Items\TransferItemEvent;
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\CalculateBlockTimeAction;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\DoctrineCacheService;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\HTMLService;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use App\Structures\TownConf;
use App\Traits\Controller\EventChainProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
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
     * @return JsonResponse
     */
    #[Route(path: '', name: 'base', methods: ['GET'])]
    #[Route(path: '/index', name: 'base_index', methods: ['GET'])]
    #[GateKeeperProfile('skip')]
    public function index(Packages $asset): JsonResponse {
        return new JsonResponse([
            'type' => [
                'rucksack' => $this->translator->trans('Rucksack', [], 'game'),
                'chest' => $this->translator->trans('Truhe', [], 'game'),
                'bank' => $this->translator->trans('Bank', [], 'game'),
            ],
            'props' => [
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
                'down-all-home' => $this->translator->trans('Rucksack ausleeren', [], 'game'),
                'down-all-icon' => $asset->getUrl('build/images/item/item_bag.gif'),
            ]
        ]);
    }

    protected static function canEnumerate(Citizen $citizen, Inventory $inventory): bool {
        return
            // Rucksack
            ($inventory->getCitizen() === $citizen) ||
            // Chest
            ($inventory->getHome()?->getCitizen() === $citizen && $citizen->getZone() === null) ||
            // Bank
            ($inventory->getTown() === $citizen->getTown() && $citizen->getZone() === null) ||
            // Foreign chest
            ($inventory->getHome()?->getCitizen()?->getTown() === $citizen->getTown() && $citizen->getZone() === null) ||
            // Zone floor
            ($inventory->getZone() && $inventory->getZone() === $citizen->getZone()) ||
            // Ruin floor
            ($inventory->getRuinZone() && $citizen->getExplorerStats()->findFirst(fn(RuinExplorerStats $a) => $a->getActive())->isAt($inventory->getRuinZone())) ||
            // Ruin room floor
            ($inventory->getRuinZoneRoom() && $citizen->getExplorerStats()->findFirst(fn(RuinExplorerStats $a) => $a->getActive())->isAt($inventory->getRuinZoneRoom()));
    }

    protected static function renderInventory(Citizen $citizen, Inventory $inventory, InventoryHandler $handler, EventProxyService $proxy, TranslatorInterface $trans): array {
        $foreign_chest = false;

        // Special case - foreign chest
        if ($inventory->getHome() && $inventory->getHome()->getCitizen() !== $citizen && $inventory->getHome()->getCitizen()->getAlive())
            $foreign_chest = true;

        $show_banished_hidden = $citizen->getBanished() || $citizen->getTown()->getChaos();
        return [
            'size' => $handler->getSize( $inventory ),
            'mods' => [
                'has_drunk' => $citizen->hasStatus('hasdrunk')
            ],
            'items' => $inventory->getItems()
                ->filter( fn(Item $i) => $show_banished_hidden || !$i->getHidden() )
                ->filter( fn(Item $i) => !$foreign_chest || !$i->getPrototype()->getHideInForeignChest() )
                ->map( fn(Item $i) => [
                    'i' => $i->getId(),
                    'p' => $i->getPrototype()->getId(),
                    'c' => $i->getCount(),
                    'b' => $i->getBroken(),
                    'h' => $i->getHidden(),
                    'e' => $i->getEssential(),
                    'w' => $i->getPrototype()->getWatchpoint() <> 0 ? $trans->trans('{watchpoint} pkt attacke', ['watchpoint' => $proxy->buildingQueryNightwatchDefenseBonus( $citizen->getTown(), $i )], 'items') : null,
                    's' => [
                        $i->getEssential() ? 1 : 0,
                        $i->getPrototype()->getSort(),
                        $i->getPrototype()->getId(),
                        mt_rand(),
                    ]
                ] )->getValues()
        ];
    }

    #[Route(path: '/{id}', name: 'inventory_get', methods: ['GET'])]
    public function inventory(Inventory $inventory, EntityManagerInterface $em, InventoryHandler $handler, EventProxyService $proxy): JsonResponse {
        $citizen = $this->getUser()->getActiveCitizen();

        if (!self::canEnumerate($citizen, $inventory))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        // Special case - foreign chest
        if ($inventory->getHome() && $inventory->getHome()->getCitizen() !== $citizen && $inventory->getHome()->getCitizen()->getAlive()) {

            $hidden = $inventory->getHome()->getCitizenHomeUpgrades()->findFirst( fn(CitizenHomeUpgrade $c) => $c->getPrototype()->getName() === 'curtain' ) !== null;
            $intrusion = $hidden && $em->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $citizen, 'victim' => $inventory->getHome()->getCitizen()]);

            if ($hidden && !$intrusion) return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $data = self::renderInventory( $citizen, $inventory, $handler, $proxy, $this->translator );

        return new JsonResponse($data);
    }

    #[Route(path: '/{inventory}/{item}', name: 'inventory_move', methods: ['PATCH'])]
    #[Route(path: '/{inventory}', name: 'inventory_all', methods: ['PATCH'])]
    public function moveItem(
        #[MapEntity(id: 'inventory')] Inventory $inventory,
        #[MapEntity(id: 'item')] ?Item $item,
        InventoryHandler $handler, EventProxyService $proxy,
        JSONRequestParser $parser,
        EntityManagerInterface $em,
        EventFactory $ef, EventDispatcherInterface $ed,
    ): JsonResponse
    {
        $citizen = $this->getUser()->getActiveCitizen();

        if (!self::canEnumerate($citizen, $inventory))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $direction = $parser->get('d', null, ['up','down','down-all']);
        if (!$direction) return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        if ($direction !== 'down-all' && !$item) return new JsonResponse([], Response::HTTP_BAD_REQUEST);

        $mod = $parser->get('mod', null, ['theft', 'hide']) >= 1;

        $to = $parser->get_int('to', -1);
        $target_inventory = $em->getRepository(Inventory::class)->find($to);
        if (!$target_inventory || !self::canEnumerate($citizen, $target_inventory))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $carrier_items = ['bag_#00','bagxl_#00','cart_#00','pocket_belt_#00'];

        $drop_all = $direction === 'down-all' || (
                // dropping item ...
                $direction === 'down' &&
                // item is carrier...
                in_array($item->getPrototype()->getName(), $carrier_items) &&
                // item is single...
                $item->getCount() === 1 &&
                // no other carriers...
                $inventory->getItems()->filter(fn(Item $i) => $i->getId() !== $item->getId() && in_array($i->getPrototype()->getName(), $carrier_items))->isEmpty()
            );


        $items = match(true) {
            $drop_all && $direction === 'down-all' => $inventory->getItems()->filter(fn(Item $i) => !$i->getEssential() && !in_array($i->getPrototype()->getName(), $carrier_items))->getValues(),
            $drop_all => $inventory->getItems()->filter(fn(Item $i) => !$i->getEssential())->getValues(),
            $item !== null => [$item],
            default => [],
        };

        $target_citizen = $target_inventory->getCitizen() ?? $inventory->getCitizen() ?? $citizen;

        $errors = [];
        foreach ($items as $current_item) if ($citizen->getAlive() && $target_citizen->getAlive()) {

            if (($error = $this->processEventChainUsing( $ef, $ed, $em,
                                                         $ef->gameInteractionEvent( TransferItemEvent::class )->setup($current_item, $citizen, $inventory, $target_inventory, match (true) {
                                                             $mod === 'theft' => TransferItemModality::BankTheft,
                                                             $mod === 'hide' => TransferItemModality::HideItem,
                                                             default => TransferItemModality::None
                                                         }, $this->conf->getTownConfiguration($citizen->getTown())->get(TownConf::CONF_MODIFIER_CARRY_EXTRA_BAG, false) ? [ TransferItemOption::AllowExtraBag ] : [] )
                    , autoFlush: false, error_messages: $error_messages )) !== null)

                $errors[] = $error;
        }

        try {
            $em->flush();
        } catch (\Throwable $t) {
            return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $processed = max(0, count($items) - count($errors));
        if (empty($errors) || count($errors) < count($items) || empty($items)) {
            return new JsonResponse([
                'success' => true,
                'source' => self::renderInventory( $citizen, $inventory, $handler, $proxy, $this->translator ),
                'target' => self::renderInventory( $citizen, $target_inventory, $handler, $proxy, $this->translator ),
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
                'messages' => implode('<hr/>', $error_messages ?? []),
                'source' => self::renderInventory( $citizen, $inventory, $handler, $proxy, $this->translator ),
                'target' => self::renderInventory( $citizen, $target_inventory, $handler, $proxy, $this->translator ),
            ]);
        }
    }
}
