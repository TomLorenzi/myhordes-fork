<?php

namespace App\Controller\Admin;

use App\Entity\Citizen;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\Complaint;
use App\Entity\ExpeditionRoute;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\Zone;
use App\Response\AjaxResponse;
use App\Service\CrowService;
use App\Service\CitizenHandler;
use App\Service\ErrorHelper;
use App\Service\GameFactory;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\NightlyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 */
class AdminTownController extends AdminActionController
{
    /**
     * @Route("jx/admin/town/list", name="admin_town_list")
     * @return Response
     */
    public function town_list(): Response
    {
        return $this->render( 'ajax/admin/towns/list.html.twig', $this->addDefaultTwigArgs('towns', [
            'towns' => $this->entity_manager->getRepository(Town::class)->findAll(),
            'citizen_stats' => $this->entity_manager->getRepository(Citizen::class)->getStatByLang()
        ]));
    }

    /**
     * @Route("jx/admin/town/list/old/{page}", name="admin_old_town_list", requirements={"page"="\d+"})
     * @param int $page The page we're viewing
     * @return Response
     */
    public function old_town_list($page = 1): Response
    {
        if ($page <= 0) $page = 1;

        // build the query for the doctrine paginator
        $query = $this->entity_manager->getRepository(TownRankingProxy::class)->createQueryBuilder('t')
            ->andWhere('t.end IS NOT NULL')
            ->orWhere('t.imported = 1')
            ->orderBy('t.id', 'ASC')
            ->getQuery();

        // Get the paginator
        $paginator = new Paginator($query);

        $pageSize = 20;
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $pageSize);

        return $this->render( 'ajax/admin/towns/old_towns_list.html.twig', $this->addDefaultTwigArgs('old_towns', [
            'towns' => $paginator
                ->getQuery()
                ->setFirstResult($pageSize * ($page-1)) // set the offset
                ->setMaxResults($pageSize)
                ->getResult(),
            'currentPage' => $page,
            'pagesCount' => $pagesCount
        ]));
    }

    /**
     * @Route("jx/admin/town/{id<\d+>}/{tab?}", name="admin_town_explorer")
     * @param int $id
     * @param string|null $tab The tab we want to display
     * @return Response
     */
    public function town_explorer(int $id, ?string $tab): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if ($town === null) $this->redirect( $this->generateUrl( 'admin_town_list' ) );

        $explorables = [];

        foreach ($town->getZones() as $zone)
            /** @var Zone $zone */
            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $explorables[ $zone->getId() ] = ['rz' => [], 'z' => $zone, 'x' => $zone->getExplorerStats(), 'ax' => $zone->activeExplorerStats()];
                if ($zone->activeExplorerStats()) $explorables[ $zone->getId() ][ 'axt' ] = max(0,$zone->activeExplorerStats()->getTimeout()->getTimestamp() - time());
                $rz = $zone->getRuinZones();
                foreach ($rz as $r)
                    $explorables[ $zone->getId() ]['rz'][] = $r;
            }
        
        $pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($pictoProtos, function($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $itemPrototypes = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        usort($itemPrototypes, function($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'items'), $this->translator->trans($b->getLabel(), [], 'items'));
        });

        $citizenStati = $this->entity_manager->getRepository(CitizenStatus::class)->findAll();
        usort($citizenStati, function($a, $b) {
          return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

      $citizenRoles = $this->entity_manager->getRepository(CitizenRole::class)->findAll();
      usort($citizenRoles, function($a, $b) {
        return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
      });

        $complaints = [];

        foreach($town->getCitizens() as $citizen) {
            $comp = $this->entity_manager->getRepository(Complaint::class)->findBy(['culprit' => $citizen]);
            if (count($comp) > 0)
                $complaints[$citizen->getUser()->getName()] = $comp;
        }

        return $this->render( 'ajax/admin/towns/explorer.html.twig', $this->addDefaultTwigArgs(null, array_merge([
            'town' => $town,
            'conf' => $this->conf->getTownConfiguration( $town ),
            'explorables' => $explorables,
            'log' => $this->renderLog( -1, $town, false )->getContent(),
            'day' => $town->getDay(),
            'bank' => $this->renderInventoryAsBank( $town->getBank() ),
            'itemPrototypes' => $itemPrototypes,
            'pictoPrototypes' => $pictoProtos,
            'citizenStati' => $citizenStati,
            'citizenRoles' => $citizenRoles,
            'tab' => $tab,
            'complaints' => $complaints,
        ], $this->get_map_blob($town))));
    }

    /**
     * @Route("jx/admin/town/old/{id<\d+>}/{tab?}", name="admin_old_town_explorer")
     * @param int $id
     * @param string|null $tab the tab we want to display
     * @return Response
     */
    public function old_town_explorer(int $id, ?string $tab): Response
    {
        $town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
        if ($town === null) $this->redirect( $this->generateUrl( 'admin_old_town_list' ) );

        $pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($pictoProtos, function($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        return $this->render( 'ajax/admin/towns/old_town_explorer.html.twig', $this->addDefaultTwigArgs('old_explorer', [
            'town' => $town,
            'day' => $town->getDays(),
            'pictoPrototypes' => $pictoProtos,
            'tab' => $tab
        ]));
    }

    /**
     * @Route("api/admin/town/{id}/do/{action}", name="admin_town_manage", requirements={"id"="\d+"})
     * @param int $id The ID of the town
     * @param string $action The action to perform
     * @param NightlyHandler $night
     * @param GameFactory $gameFactory
     * @return Response
     */
    public function town_manager(int $id, string $action, NightlyHandler $night, GameFactory $gameFactory, CrowService $crowService): Response
    {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        if (in_array($action, [ 'release', 'quarantine', 'advance', 'nullify' ]) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error( ErrorHelper::ErrorPermissionError );

        switch ($action) {
            case 'release':
                if ($town->getAttackFails() >= 3)
                    foreach ($town->getCitizens() as $citizen)
                        if ($citizen->getAlive())
                            $this->entity_manager->persist(
                                $crowService->createPM_townQuarantine( $citizen->getUser(), $town->getName(), false )
                            );
                $town->setAttackFails(0);
                $this->entity_manager->persist($town);
                break;
            case 'quarantine':
                if ($town->getAttackFails() < 3)
                    foreach ($town->getCitizens() as $citizen)
                        if ($citizen->getAlive())
                            $this->entity_manager->persist(
                                $crowService->createPM_townQuarantine( $citizen->getUser(), $town->getName(), true )
                            );
                $town->setAttackFails(4);
                $this->entity_manager->persist($town);
                break;
            case 'advance':
                if ($night->advance_day($town, $this->conf->getCurrentEvent( $town ))) {
                    foreach ($night->get_cleanup_container() as $c) $this->entity_manager->remove($c);
                    $town->setAttackFails(0);
                    $this->entity_manager->persist( $town );
                }
                break;
            case 'nullify':
                foreach ($town->getCitizens() as $citizen)
                    $this->entity_manager->persist(
                        $crowService->createPM_townNegated( $citizen->getUser(), $town->getName(), false )
                    );
                $gameFactory->nullifyTown($town, true);
                break;

            default: return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error( ErrorHelper::ErrorDatabaseException, ['message' => $e->getMessage()] );
        }

        return AjaxResponse::success();
    }

    public function get_map_blob(Town $town): array {
        $zones = []; $range_x = [PHP_INT_MAX,PHP_INT_MIN]; $range_y = [PHP_INT_MAX,PHP_INT_MIN];
        $zones_classes = [];

        $soul_zones_ids = array_map(function(Zone $z) { return $z->getId(); },$this->zone_handler->getSoulZones( $town ) );

        foreach ($town->getZones() as $zone) {
            $x = $zone->getX();
            $y = $zone->getY();

            $range_x = [ min($range_x[0], $x), max($range_x[1], $x) ];
            $range_y = [ min($range_y[0], $y), max($range_y[1], $y) ];

            if (!isset($zones[$x])) $zones[$x] = [];
            $zones[$x][$y] = $zone;

            if (!isset($zones_attributes[$x])) $zones_attributes[$x] = [];
            $zones_classes[$x][$y] = $this->zone_handler->getZoneClasses(
                $town,
                $zone,
                null,
                in_array($zone->getId(), $soul_zones_ids),
                true
            );
        }

        return [
            'map_data' => [
                'zones' =>  $zones,
                'zones_classes' =>  $zones_classes,
                'town_devast' => $town->getDevastated(),
                'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown( $town ),
                'pos_x'  => 0,
                'pos_y'  => 0,
                'map_x0' => $range_x[0],
                'map_x1' => $range_x[1],
                'map_y0' => $range_y[0],
                'map_y1' => $range_y[1],
            ]
        ];
    }

    /**
     * @Route("/api/admin/town/{id}/bank/item", name="admin_bank_item", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Add or remove an item from the bank
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    public function bank_item_action(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if(!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $item_id = $parser->get('item');
        $change = $parser->get('change');

        $item = $this->entity_manager->getRepository(Item::class)->find($item_id);

        if ($change == 'add') {
            $handler->forceMoveItem( $town->getBank(), $itemFactory->createItem( $item->getPrototype()->getName()) );
        } else {
            $handler->forceRemoveItem($item);
        }

        $this->entity_manager->persist($town->getBank());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/bank/spawn_item", name="admin_bank_spawn_item", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Add or remove an item from the bank
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    public function bank_spawn_item(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if(!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number');
        $poison = $parser->get('poison', false);
        $broken = $parser->get('broken', false);
        $essential = $parser->get('essential', false);

        /** @var ItemPrototype $itemPrototype */
        $itemPrototype = $this->entity_manager->getRepository(ItemPrototype::class)->find($prototype_id);

        for ($i = 0 ; $i < $number ; $i++){
            /** @var Item $item */
            $item = $itemFactory->createItem($itemPrototype->getName(), $broken, $poison);
            $item->setEssential($essential);
            $handler->forceMoveItem($town->getBank(), $item);
        }

        $this->entity_manager->persist($town->getBank());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/citizen/spawn_item", name="admin_citizen_spawn_item", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Add or remove an item from the bank
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    public function citizen_spawn_item(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if(!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number');
        $targets = $parser->get('targets', "");
        $poison = $parser->get('poison', false);
        $broken = $parser->get('broken', false);
        $essential = $parser->get('essential', false);

        if(empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var ItemPrototype $itemPrototype */
        $itemPrototype = $this->entity_manager->getRepository(ItemPrototype::class)->find($prototype_id);
        
        $targets = explode(",", $targets);
        foreach ($targets as $target) {
            $infos = explode("-", $target);
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($infos[1]);

            for ($i = 0 ; $i < $number ; $i++) {
                /** @var Item $item */
                $item = $itemFactory->createItem($itemPrototype->getName(), $broken, $poison);
                $item->setEssential($essential);
                $handler->forceMoveItem($infos[0] == "r" ? $citizen->getInventory() : $citizen->getHome()->getChest(), $item);
            }
            
            $this->entity_manager->persist($citizen);
            $this->entity_manager->persist($citizen->getHome()->getChest());
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/picto/give", name="admin_town_give_picto", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Give picto to all citizens of a town
     * @param int $id User ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    public function town_give_picto(int $id, JSONRequestParser $parser): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        $townRanking = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
        /** @var Town $town */
        if(!$town && !$townRanking) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if(!$town && $townRanking)
            $town = $townRanking;

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number', 1);

        /** @var PictoPrototype $pictoPrototype */
        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->find($prototype_id);

        foreach ($town->getCitizens() as $citizen){
            /** @var Citizen $citizen */
            // if(!$citizen->getAlive()) continue;

            $picto = $this->entity_manager->getRepository(Picto::class)->findByUserAndTownAndPrototype($citizen->getUser(), $town, $pictoPrototype);
            if (null === $picto) {
                $picto = new Picto();
                $picto->setPrototype($pictoPrototype)
                    ->setPersisted(2)
                    ->setUser($citizen->getUser());
                if(is_a($town, Town::class))
                    $picto->setTown($town);
                else
                    $picto->setTownEntry($town);
                $citizen->getUser()->addPicto($picto);
                $this->entity_manager->persist($citizen->getUser());
            }

            $picto->setCount($picto->getCount() + $number);

            $this->entity_manager->persist($picto);
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

  /**
   * @Route("/api/admin/town/{id}/status/give", name="admin_town_give_status", requirements={"id"="\d+"})
   * @Security("is_granted('ROLE_ADMIN')")
   * Give status to selected citizens of a town
   * @param int $id User ID
   * @param JSONRequestParser $parser The Request Parser
   * @param CitizenHandler $handler
   * @return Response
   */
  public function town_give_status(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
  {
    $town = $this->entity_manager->getRepository(Town::class)->find($id);
    if(!$town) {
      return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    $status_id = $parser->get('status');
    $targets = $parser->get('targets', "");

    /** @var CitizenStatus $citizenStatus */
    $citizenStatus = $this->entity_manager->getRepository(CitizenStatus::class)->find($status_id);

    $targets = explode(",", $targets);
    foreach ($targets as $target) {
      $infos = explode("-", $target);
      /** @var Citizen $citizen */
      $citizen = $this->entity_manager->getRepository(Citizen::class)->find($infos[1]);

      $citizen->addStatus($citizenStatus);

      $this->entity_manager->persist($citizen);
    }

    $this->entity_manager->flush();

    return AjaxResponse::success();
  }

  /**
   * @Route("/api/admin/town/{id}/status/take", name="admin_town_take_status", requirements={"id"="\d+"})
   * @Security("is_granted('ROLE_ADMIN')")
   * Give status to selected citizens of a town
   * @param int $id User ID
   * @param JSONRequestParser $parser The Request Parser
   * @param CitizenHandler $handler
   * @return Response
   */
  public function town_take_status(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
  {
    $town = $this->entity_manager->getRepository(Town::class)->find($id);
    if(!$town) {
      return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    $status_id = $parser->get('status');
    $targets = $parser->get('targets', "");

    /** @var CitizenStatus $citizenStatus */
    $citizenStatus = $this->entity_manager->getRepository(CitizenStatus::class)->find($status_id);

    $targets = explode(",", $targets);
    foreach ($targets as $target) {
      $infos = explode("-", $target);
      /** @var Citizen $citizen */
      $citizen = $this->entity_manager->getRepository(Citizen::class)->find($infos[1]);

      $citizen->removeStatus($citizenStatus);

      $this->entity_manager->persist($citizen);
    }

    $this->entity_manager->flush();

    return AjaxResponse::success();
  }

  /**
   * @Route("/api/admin/town/{id}/role/give", name="admin_town_give_role", requirements={"id"="\d+"})
   * @Security("is_granted('ROLE_ADMIN')")
   * Give status to selected citizens of a town
   * @param int $id User ID
   * @param JSONRequestParser $parser The Request Parser
   * @param CitizenHandler $handler
   * @return Response
   */
  public function town_give_role(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
  {
    $town = $this->entity_manager->getRepository(Town::class)->find($id);
    if(!$town) {
      return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    $role_id = $parser->get('role');
    $targets = $parser->get('targets', "");

    /** @var CitizenRole $citizenRole */
    $citizenRole = $this->entity_manager->getRepository(CitizenRole::class)->find($role_id);

    $targets = explode(",", $targets);
    foreach ($targets as $target) {
      $infos = explode("-", $target);
      /** @var Citizen $citizen */
      $citizen = $this->entity_manager->getRepository(Citizen::class)->find($infos[1]);

      $citizen->addRole($citizenRole);

      $this->entity_manager->persist($citizen);
    }

    $this->entity_manager->flush();

    return AjaxResponse::success();
  }

  /**
   * @Route("/api/admin/town/{id}/role/take", name="admin_town_take_role", requirements={"id"="\d+"})
   * @Security("is_granted('ROLE_ADMIN')")
   * Give status to selected citizens of a town
   * @param int $id User ID
   * @param JSONRequestParser $parser The Request Parser
   * @param CitizenHandler $handler
   * @return Response
   */
  public function town_take_role(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
  {
    $town = $this->entity_manager->getRepository(Town::class)->find($id);
    if(!$town) {
      return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
    }

    $role_id = $parser->get('role');
    $targets = $parser->get('targets', "");

    /** @var CitizenRole $citizenRole */
    $citizenRole = $this->entity_manager->getRepository(CitizenRole::class)->find($role_id);

    $targets = explode(",", $targets);
    foreach ($targets as $target) {
      $infos = explode("-", $target);
      /** @var Citizen $citizen */
      $citizen = $this->entity_manager->getRepository(Citizen::class)->find($infos[1]);

      $citizen->removeRole($citizenRole);

      $this->entity_manager->persist($citizen);
    }

    $this->entity_manager->flush();

    return AjaxResponse::success();
  }

    /**
     * @Route("jx/admin/towns/old/fuzzyfind", name="admin_old_towns_fuzzyfind")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function old_towns_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $towns = $em->getRepository(TownRankingProxy::class)->findByNameContains($parser->get('name'));

        return $this->render( 'ajax/admin/towns/townlist.html.twig', $this->addDefaultTwigArgs("admin_towns", [
            'towns' => $towns,
            'nohref' => $parser->get('no-href',false),
            'target' => 'admin_old_town_explorer'
        ]));
    }

    /**
     * @Route("jx/admin/towns/fuzzyfind", name="admin_towns_fuzzyfind")
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function towns_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $towns = $em->getRepository(Town::class)->findByNameContains($parser->get('name'));

        return $this->render( 'ajax/admin/towns/townlist.html.twig', $this->addDefaultTwigArgs("admin_towns", [
            'towns' => $towns,
            'nohref' => $parser->get('no-href',false),
            'target' => 'admin_town_explorer'
        ]));
    }
}
