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
use App\Response\AjaxResponse;
use App\Service\Actions\Cache\CalculateBlockTimeAction;
use App\Service\Actions\Cache\InvalidateLogCacheAction;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\DoctrineCacheService;
use App\Service\HTMLService;
use App\Service\InventoryHandler;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\UserHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
class InventoryController extends CustomAbstractCoreController
{

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

    #[Route(path: '/{id}', name: 'inventory_get', methods: ['GET'])]
    public function inventory(Inventory $inventory, EntityManagerInterface $em, InventoryHandler $handler): JsonResponse {
        $citizen = $this->getUser()->getActiveCitizen();

        if (!self::canEnumerate($citizen, $inventory))
            return new JsonResponse([], Response::HTTP_NOT_FOUND);

        $foreign_chest = false;

        // Special case - foreign chest
        if ($inventory->getHome() && $inventory->getHome()->getCitizen() !== $citizen && $inventory->getHome()->getCitizen()->getAlive()) {

            $hidden = $inventory->getHome()->getCitizenHomeUpgrades()->findFirst( fn(CitizenHomeUpgrade $c) => $c->getPrototype()->getName() === 'curtain' ) !== null;
            $intrusion = $hidden && $em->getRepository(HomeIntrusion::class)->findOneBy(['intruder' => $citizen, 'victim' => $inventory->getHome()->getCitizen()]);

            if ($hidden && !$intrusion) return new JsonResponse([], Response::HTTP_NOT_FOUND);
            $foreign_chest = true;
        }

        $show_banished_hidden = $citizen->getBanished() || $citizen->getTown()->getChaos();
        return new JsonResponse([
            'size' => $handler->getSize( $inventory ),
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
                ] )->getValues()
        ]);
    }
}
