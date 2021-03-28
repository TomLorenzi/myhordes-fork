<?php

namespace App\Controller\Admin;

use App\Annotations\GateKeeperProfile;
use App\Controller\Town\TownController;
use App\Entity\BankAntiAbuse;
use App\Entity\BlackboardEdit;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenHome;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\CitizenVote;
use App\Entity\Complaint;
use App\Entity\ExpeditionRoute;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
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
use App\Service\RandomGenerator;
use App\Service\TownHandler;
use App\Structures\TownConf;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @GateKeeperProfile(allow_during_attack=true)
 */
class AdminTownController extends AdminActionController
{
    /**
     * @Route("jx/admin/town/list", name="admin_town_list")
     * @return Response
     */
    public function town_list(): Response
    {
        return $this->render('ajax/admin/towns/list.html.twig', $this->addDefaultTwigArgs('towns', [
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

        return $this->render('ajax/admin/towns/old_towns_list.html.twig', $this->addDefaultTwigArgs('old_towns', [
            'towns' => $paginator
                ->getQuery()
                ->setFirstResult($pageSize * ($page - 1)) // set the offset
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
        if ($town === null) $this->redirect($this->generateUrl('admin_town_list'));

        $explorables = [];

        foreach ($town->getZones() as $zone)
            /** @var Zone $zone */
            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $explorables[$zone->getId()] = ['rz' => [], 'z' => $zone, 'x' => $zone->getExplorerStats(), 'ax' => $zone->activeExplorerStats()];
                if ($zone->activeExplorerStats()) $explorables[$zone->getId()]['axt'] = max(0, $zone->activeExplorerStats()->getTimeout()->getTimestamp() - time());
                $rz = $zone->getRuinZones();
                foreach ($rz as $r)
                    $explorables[$zone->getId()]['rz'][] = $r;
            }

        $pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($pictoProtos, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $itemPrototypes = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        usort($itemPrototypes, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'items'), $this->translator->trans($b->getLabel(), [], 'items'));
        });

        $citizenStati = $this->entity_manager->getRepository(CitizenStatus::class)->findAll();
        usort($citizenStati, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $citizenRoles = $this->entity_manager->getRepository(CitizenRole::class)->findAll();

        usort($citizenRoles, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        $disabled_profs = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, []);
        $professions = array_filter($this->entity_manager->getRepository( CitizenProfession::class )->findSelectable(),
            fn(CitizenProfession $p) => !in_array($p->getName(),$disabled_profs)
        );

        $complaints = [];
        $votes = [];
        $roles = [];

        /** @var CitizenRole $votableRole */
        foreach ($this->entity_manager->getRepository(CitizenRole::class)->findVotable() as $votableRole) {
            $votes[$votableRole->getId()] = [];
            $roles[$votableRole->getId()] = $votableRole;
        }

        foreach ($town->getCitizens() as $citizen) {
            $comp = $this->entity_manager->getRepository(Complaint::class)->findBy(['culprit' => $citizen]);
            if (count($comp) > 0)
                $complaints[$citizen->getUser()->getName()] = $comp;

            foreach ($roles as $roleId => $role) {
                /** @var CitizenVote $vote */
                $vote = $this->entity_manager->getRepository(CitizenVote::class)->findOneByCitizenAndRole($citizen, $role);
                if ($vote) {
                    if(isset($votes[$roleId][$vote->getVotedCitizen()->getUser()->getName()])) {
                        $votes[$roleId][$vote->getVotedCitizen()->getUser()->getName()][] = $vote->getAutor();
                    } else {
                        $votes[$roleId][$vote->getVotedCitizen()->getUser()->getName()] = [
                            $vote->getAutor()
                        ];
                    }
                }
            }
        }


        $root = [];
        $dict = [];
        $inTown = [];

        foreach ($this->entity_manager->getRepository(BuildingPrototype::class)->findAll() as $building) {
            /** @var BuildingPrototype $building */
            $dict[$building->getId()] = [];
            if (!$building->getParent())
                $root[] = $building;
        }

        foreach ($this->entity_manager->getRepository(BuildingPrototype::class)->findAll() as $building) {
            /** @var BuildingPrototype $building */
            if ($building->getParent()) {
                $dict[$building->getParent()->getId()][] = $building;
            }

            $available = $this->entity_manager->getRepository(Building::class)->findOneBy(['town' => $town, 'prototype' => $building]);
            if ($available)
                $inTown[$building->getId()] = $available;
        }
        return $this->render('ajax/admin/towns/explorer.html.twig', $this->addDefaultTwigArgs(null, array_merge([
            'town' => $town,
            'conf' => $this->conf->getTownConfiguration($town),
            'explorables' => $explorables,
            'log' => $this->renderLog(-1, $town, false)->getContent(),
            'day' => $town->getDay(),
            'bank' => $this->renderInventoryAsBank($town->getBank()),
            'itemPrototypes' => $itemPrototypes,
            'pictoPrototypes' => $pictoProtos,
            'citizenStati' => $citizenStati,
            'citizenRoles' => $citizenRoles,
            'citizenProfessions' => $professions,
            'tab' => $tab,
            'complaints' => $complaints,
            'dictBuildings' => $dict,
            'rootBuildings' => $root,
            'availBuldings' => $inTown,
            'votes' => $votes,
            'blackboards' => $this->entity_manager->getRepository(BlackboardEdit::class)->findBy([ 'town' => $town ], ['time' => 'DESC'], 100)
        ], $this->get_map_blob($town))));
    }

    public function get_map_blob(Town $town): array
    {
        $zones = [];
        $range_x = [PHP_INT_MAX, PHP_INT_MIN];
        $range_y = [PHP_INT_MAX, PHP_INT_MIN];
        $zones_classes = [];

        $soul_zones_ids = array_map(function (Zone $z) {
            return $z->getId();
        }, $this->zone_handler->getSoulZones($town));

        foreach ($town->getZones() as $zone) {
            $x = $zone->getX();
            $y = $zone->getY();

            $range_x = [min($range_x[0], $x), max($range_x[1], $x)];
            $range_y = [min($range_y[0], $y), max($range_y[1], $y)];

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
                'zones' => $zones,
                'zones_classes' => $zones_classes,
                'town_devast' => $town->getDevastated(),
                'routes' => $this->entity_manager->getRepository(ExpeditionRoute::class)->findByTown($town),
                'pos_x' => 0,
                'pos_y' => 0,
                'map_x0' => $range_x[0],
                'map_x1' => $range_x[1],
                'map_y0' => $range_y[0],
                'map_y1' => $range_y[1],
            ]
        ];
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
        if ($town === null) $this->redirect($this->generateUrl('admin_old_town_list'));

        $pictoProtos = $this->entity_manager->getRepository(PictoPrototype::class)->findAll();
        usort($pictoProtos, function ($a, $b) {
            return strcmp($this->translator->trans($a->getLabel(), [], 'game'), $this->translator->trans($b->getLabel(), [], 'game'));
        });

        return $this->render('ajax/admin/towns/old_town_explorer.html.twig', $this->addDefaultTwigArgs('old_explorer', [
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
     * @param ItemFactory $itemFactory
     * @param RandomGenerator $random
     * @param NightlyHandler $night
     * @param GameFactory $gameFactory
     * @param CrowService $crowService
     * @param KernelInterface $kernel
     * @return Response
     */
    public function town_manager(int $id, string $action, ItemFactory $itemFactory, RandomGenerator $random, NightlyHandler $night, GameFactory $gameFactory, CrowService $crowService, KernelInterface $kernel): Response
    {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (str_starts_with($action, 'dbg_') && $kernel->getEnvironment() !== 'dev')
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (in_array($action, ['release', 'quarantine', 'advance', 'nullify', 'dbg_fill_town', 'dbg_fill_bank', 'dbg_unlock_bank', 'dbg_hydrate', 'dbg_disengage', 'dbg_engage']) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        switch ($action) {
            case 'release':
                if ($town->getAttackFails() >= 3)
                    foreach ($town->getCitizens() as $citizen)
                        if ($citizen->getAlive())
                            $this->entity_manager->persist(
                                $crowService->createPM_townQuarantine($citizen->getUser(), $town->getName(), false)
                            );
                $town->setAttackFails(0);
                $this->entity_manager->persist($town);
                break;
            case 'quarantine':
                if ($town->getAttackFails() < 3)
                    foreach ($town->getCitizens() as $citizen)
                        if ($citizen->getAlive())
                            $this->entity_manager->persist(
                                $crowService->createPM_townQuarantine($citizen->getUser(), $town->getName(), true)
                            );
                $town->setAttackFails(4);
                $this->entity_manager->persist($town);
                break;
            case 'advance':
                if ($night->advance_day($town, $this->conf->getCurrentEvents($town))) {
                    foreach ($night->get_cleanup_container() as $c) $this->entity_manager->remove($c);
                    $town->setAttackFails(0);
                    $this->entity_manager->persist($town);
                }
                break;
            case 'nullify':
                foreach ($town->getCitizens() as $citizen)
                    $this->entity_manager->persist(
                        $crowService->createPM_townNegated($citizen->getUser(), $town->getName(), false)
                    );
                $gameFactory->nullifyTown($town, true);
                break;

            case 'dbg_disengage':
                foreach ($town->getCitizens() as $citizen)
                    if ($citizen->getAlive() && $citizen->getActive())
                        $this->entity_manager->persist($citizen->setActive(false));
                break;

            case 'dbg_engage':
                foreach ($town->getCitizens() as $citizen)
                    if ($citizen->getAlive() && !$citizen->getActive()) {
                        if ($citizen->getUser()->getActiveCitizen())
                            $this->entity_manager->persist($citizen->getUser()->getActiveCitizen()->setActive(false));
                        $this->entity_manager->persist($citizen->setActive(true));
                    }
                break;

            case 'dbg_fill_town':
                $missing = $town->getPopulation() - $town->getCitizenCount();
                if ($missing <= 0) break;

                $users = []; $backup = [];
                for ($i = 1; $i <= 80; $i++) if (count($users) < $missing) {
                    $user_name = 'user_' . str_pad($i, 3, '0', STR_PAD_LEFT);

                    /** @var User $selected_user */
                    $selected_user = $this->entity_manager->getRepository(User::class)->findOneBy(['name' => $user_name]);
                    if ($selected_user === null) continue;
                    if ($selected_user->getActiveCitizen()) $backup[] = $selected_user;
                    else $users[] = $selected_user;
                }

                $disabled_profs = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, []);
                $professions = array_filter($this->entity_manager->getRepository( CitizenProfession::class )->findSelectable(),
                    fn(CitizenProfession $p) => !in_array($p->getName(),$disabled_profs)
                );

                while ($town->getPopulation() > ($town->getCitizenCount() + count($users)) && !empty($backup)) {
                    /** @var User $selected_user */
                    $selected_user = $backup[0]; $backup = array_slice($backup, 1);
                    $this->entity_manager->persist($selected_user->getActiveCitizen()->setActive(false));
                    $users[] = $selected_user;
                }

                foreach ($users as $selected_user) {
                    $citizen = $gameFactory->createCitizen($town, $selected_user, $error);
                    $this->entity_manager->persist($town);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

                    $pro = $random->pick($professions);
                    $this->citizen_handler->applyProfession($citizen, $pro);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->persist($town);
                    $this->entity_manager->flush();
                }

                break;

            case 'dbg_fill_bank':
                $bank = $town->getBank();
                foreach ($this->entity_manager->getRepository(ItemPrototype::class)->findAll() as $repo)
                    $this->inventory_handler->forceMoveItem( $bank, ($itemFactory->createItem( $repo ))->setCount(500) );

                $this->entity_manager->persist( $bank );
                break;

            case 'dbg_unlock_bank':
                foreach ($town->getCitizens() as $citizen) if ($citizen->getBankAntiAbuse())
                    $this->entity_manager->persist($citizen->getBankAntiAbuse()->setNbItemTaken(0));
                break;

            case 'dbg_hydrate':
                $thirst1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('thirst1');
                $thirst2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneByName('thirst2');
                foreach ($town->getCitizens() as $citizen) {
                    $this->citizen_handler->removeStatus( $citizen, $thirst1 );
                    $this->citizen_handler->removeStatus( $citizen, $thirst2 );
                    $this->entity_manager->persist($citizen);
                }
                break;

            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['message' => $e->getMessage()]);
        }

        return AjaxResponse::success();
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
        if (!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $item_id = $parser->get('item');
        $change = $parser->get('change');

        $item = $this->entity_manager->getRepository(Item::class)->find($item_id);

        if ($change == 'add') {
            $handler->forceMoveItem($town->getBank(), $itemFactory->createItem($item->getPrototype()->getName()));
        } else {
            $handler->forceRemoveItem($item);
        }

        $this->entity_manager->persist($town->getBank());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/spawn_item", name="admin_spawn_item", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Add or remove an item from the bank
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    public function spawn_item(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $prototype_id = $parser->get_int('prototype');
        $number = $parser->get_int('number');
        $targets = $parser->get_array('targets');

        $conf = $parser->get_array('conf');
        $poison = $conf['poison'] ?? false;
        $broken = $conf['broken'] ?? false;
        $essential = $conf['essential'] ?? false;
        $hidden = $conf['hidden'] ?? false;

        if (empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var ItemPrototype $itemPrototype */
        $itemPrototype = $this->entity_manager->getRepository(ItemPrototype::class)->find($prototype_id);
        if (!$itemPrototype) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Inventory[] $inventories */
        $inventories = [];

        foreach (array_unique($targets['chest'] ?? []) as $target) {
            /** @var CitizenHome $home */
            $home = $this->entity_manager->getRepository(CitizenHome::class)->find($target);
            if (!$home || $home->getCitizen()->getTown() !== $town)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $inventories[] = $home->getChest();
        }

        foreach (array_unique($targets['rucksack'] ?? []) as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $inventories[] = $citizen->getInventory();
        }

        foreach (array_unique($targets['zone'] ?? []) as $target) {
            /** @var Zone $zone */
            $zone = $this->entity_manager->getRepository(Zone::class)->find($target);
            if (!$zone || $zone->getTown() !== $town)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $inventories[] = $zone->getFloor();
        }

        foreach (array_unique($targets['bank'] ?? []) as $target) {
            if ($target !== $town->getId()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $inventories[] = $town->getBank();
        }

        foreach ($inventories as $inventory) {
            for ($i = 0; $i < $number; $i++)
                $handler->forceMoveItem($inventory, $itemFactory->createItem($itemPrototype->getName(), $broken, $poison)->setEssential($essential)->setHidden($hidden && $inventory->getZone()));
            $this->entity_manager->persist($inventory);
        }

        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/modify_prof", name="admin_modify_profession", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Changes the profession of citizens
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param CitizenHandler $handler
     * @return Response
     */
    public function modify_profession(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $pro_id = $parser->get_int('profession');
        $targets = $parser->get_array('targets');

        $disabled_profs = $this->conf->getTownConfiguration($town)->get(TownConf::CONF_DISABLED_JOBS, []);

        if (empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var CitizenProfession $profession */
        $profession = $this->entity_manager->getRepository(CitizenProfession::class)->find($pro_id);
        if (!$profession || $profession->getName() === CitizenProfession::DEFAULT || in_array($profession->getName(), $disabled_profs))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Inventory[] $inventories */
        $inventories = [];

        foreach (array_unique($targets) as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if ($citizen->getProfession() !== $profession) {
                $handler->applyProfession($citizen, $profession);
                $this->entity_manager->persist($citizen);
            }

        }


        $this->entity_manager->flush();

        return AjaxResponse::success();
    }


    /**
     * @Route("/api/admin/town/{id}/picto/give", name="admin_town_give_picto", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Give picto to all citizens of a town
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    public function town_give_picto(int $id, JSONRequestParser $parser): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        $townRanking = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
        /** @var Town $town */
        if (!$town && !$townRanking) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (!$town && $townRanking)
            $town = $townRanking;

        $prototype_id = $parser->get('prototype');
        $number = $parser->get('number', 1);

        /** @var PictoPrototype $pictoPrototype */
        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->find($prototype_id);

        foreach ($town->getCitizens() as $citizen) {
            /** @var Citizen $citizen */
            // if(!$citizen->getAlive()) continue;

            $picto = $this->entity_manager->getRepository(Picto::class)->findByUserAndTownAndPrototype($citizen->getUser(), $town, $pictoPrototype);
            if (null === $picto) {
                $picto = new Picto();
                $picto->setPrototype($pictoPrototype)
                    ->setPersisted(2)
                    ->setUser($citizen->getUser());
                if (is_a($town, Town::class))
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
     * @Route("/api/admin/town/{id}/status/manage", name="admin_town_manage_status", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Give or take status from selected citizens of a town
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CitizenHandler $handler
     * @return Response
     */
    public function town_manage_status(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $status_id = $parser->get_int('status');
        $targets = $parser->get_array('targets', []);

        $control = $parser->get_int('control', 0) > 0;

        /** @var CitizenStatus $citizenStatus */
        $citizenStatus = $this->entity_manager->getRepository(CitizenStatus::class)->find($status_id);
        if (!$citizenStatus) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach ($targets as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if (!$citizen->getActive()) continue;

            if ($control) $this->citizen_handler->inflictStatus( $citizen, $citizenStatus );
            else $this->citizen_handler->removeStatus( $citizen, $citizenStatus );

            $this->entity_manager->persist($citizen);
        }

        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/role/manage", name="admin_town_manage_role", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Give or take role from selected citizens of a town
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CitizenHandler $handler
     * @return Response
     */
    public function town_manage_role(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $role_id = $parser->get_int('role');
        $targets = $parser->get_array('targets');

        $control = $parser->get_int('control', 0) > 0;

        /** @var CitizenRole $citizenRole */
        $citizenRole = $this->entity_manager->getRepository(CitizenRole::class)->find($role_id);
        if (!$citizenRole) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach ($targets as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if (!$citizen->getActive()) continue;

            if ($control) $this->citizen_handler->addRole($citizen, $citizenRole);
            else $this->citizen_handler->removeRole($citizen, $citizenRole);

            $this->entity_manager->persist($citizen);
        }

        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/pp/alter", name="admin_town_alter_pp", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Change AP/CP/MP of selected citizens of a town
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    public function town_alter_points(int $id, JSONRequestParser $parser): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $point = $parser->get('point', '');
        if (!in_array($point, ['ap','bp','mp'])) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $number = $parser->get_int('num', 6);

        $control = $parser->get_int('control', 0);
        if (!in_array($point, [-1,0,1])) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $targets = $parser->get_array('targets');

        foreach ($targets as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if (!$citizen->getActive()) continue;

            switch ($point) {
                case 'ap': $this->citizen_handler->setAP($citizen, false, ($control === 0) ? $number : $citizen->getAp() + $control * $number); break;
                case 'bp': $this->citizen_handler->setBP($citizen, false, ($control === 0) ? $number : $citizen->getBp() + $control * $number); break;
                case 'mp': $this->citizen_handler->setPM($citizen, false, ($control === 0) ? $number : $citizen->getPm() + $control * $number); break;
                default: break;
            }

            $this->entity_manager->persist($citizen);
        }

        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/buildings/add", name="admin_town_add_building", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Add a building to the town
     * @param int $id ID of the town
     * @param JSONRequestParser $parser The JSON request parser
     * @param TownHandler $th The town handler
     * @return Response
     */
    public function town_add_building(int $id, JSONRequestParser $parser, TownHandler $th)
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (!$parser->has_all(['prototype_id'])) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $proto_id = $parser->get("prototype_id");

        $proto = $this->entity_manager->getRepository(BuildingPrototype::class)->find($proto_id);
        if (!$proto)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $th->addBuilding($town, $proto);

        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @Route("/api/admin/town/{id}/buildings/set-ap", name="admin_town_set_building_ap", requirements={"id"="\d+"})
     * @Security("is_granted('ROLE_ADMIN')")
     * Set AP to a building of a town
     * @param int $id ID of the town
     * @param JSONRequestParser $parser The JSON request parser
     * @param TownHandler $th The town handler
     * @return Response
     */
    public function town_set_building_ap(int $id, JSONRequestParser $parser, TownHandler $th)
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (!$parser->has_all(['building', 'ap'])) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $building_id = $parser->get("building");

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($building_id);
        if (!$building)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $ap = intval($parser->get("ap"));

        if ($ap >= $building->getPrototype()->getAp()) {
            $ap = $building->getPrototype()->getAp();
        }

        $building->setAp($ap);

        $building->setComplete($building->getAp() >= $building->getPrototype()->getAp());

        if ($building->getAp() >= $building->getPrototype()->getAp()) {
            $building->setComplete(true);
            $th->triggerBuildingCompletion($town, $building);
        }

        $this->entity_manager->persist($building);
        $this->entity_manager->persist($town);
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

        return $this->render('ajax/admin/towns/townlist.html.twig', $this->addDefaultTwigArgs("admin_towns", [
            'towns' => $towns,
            'nohref' => $parser->get('no-href', false),
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

        return $this->render('ajax/admin/towns/townlist.html.twig', $this->addDefaultTwigArgs("admin_towns", [
            'towns' => $towns,
            'nohref' => $parser->get('no-href', false),
            'target' => 'admin_town_explorer'
        ]));
    }
}
