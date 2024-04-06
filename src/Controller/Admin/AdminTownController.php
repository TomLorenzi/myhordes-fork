<?php

namespace App\Controller\Admin;

use App\Annotations\AdminLogProfile;
use App\Annotations\GateKeeperProfile;
use App\Entity\ActionEventLog;
use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\Building;
use App\Entity\BuildingPrototype;
use App\Entity\Citizen;
use App\Entity\CitizenEscortSettings;
use App\Entity\CitizenHome;
use App\Entity\CitizenHomePrototype;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use App\Entity\CitizenProfession;
use App\Entity\CitizenRankingProxy;
use App\Entity\CitizenRole;
use App\Entity\CitizenStatus;
use App\Entity\CitizenVote;
use App\Entity\CitizenWatch;
use App\Entity\Complaint;
use App\Entity\ComplaintReason;
use App\Entity\CouncilEntry;
use App\Entity\DigTimer;
use App\Entity\EventActivationMarker;
use App\Entity\ExpeditionRoute;
use App\Entity\HeroicActionPrototype;
use App\Entity\Inventory;
use App\Entity\Item;
use App\Entity\ItemCategory;
use App\Entity\ItemPrototype;
use App\Entity\Picto;
use App\Entity\PictoComment;
use App\Entity\PictoPrototype;
use App\Entity\RuinExplorerStats;
use App\Entity\SpecialActionPrototype;
use App\Entity\Town;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\ZombieEstimation;
use App\Entity\Zone;
use App\Enum\Configuration\TownSetting;
use App\Enum\EventStages\BuildingEffectStage;
use App\Enum\EventStages\BuildingValueQuery;
use App\Enum\ItemPoisonType;
use App\Event\Game\Town\Basic\Buildings\BuildingConstructionEvent;
use App\Response\AjaxResponse;
use App\Service\AdminLog;
use App\Service\CrowService;
use App\Service\CitizenHandler;
use App\Service\ConfMaster;
use App\Service\ErrorHelper;
use App\Service\EventFactory;
use App\Service\EventProxyService;
use App\Service\GameFactory;
use App\Service\GameProfilerService;
use App\Service\GazetteService;
use App\Service\InventoryHandler;
use App\Service\ItemFactory;
use App\Service\JSONRequestParser;
use App\Service\LogTemplateHandler;
use App\Service\Maps\MapMaker;
use App\Service\Maps\MazeMaker;
use App\Service\NightlyHandler;
use App\Service\RandomGenerator;
use App\Service\TimeKeeperService;
use App\Service\TownHandler;
use App\Service\UserHandler;
use App\Service\ZoneHandler;
use App\Structures\BankItem;
use App\Structures\EventConf;
use App\Structures\MyHordesConf;
use App\Structures\TownConf;
use App\Structures\TownSetup;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
#[GateKeeperProfile(allow_during_attack: true)]
class AdminTownController extends AdminActionController
{
    protected function clearTownCaches(Town $town) {
        ($this->clear)("town_{$town->getId()}");
    }

	/**
     * @return Response
     */
    #[Route(path: 'jx/admin/town/list', name: 'admin_town_list')]
    public function town_list(): Response
    {
        return $this->render('ajax/admin/towns/list.html.twig', $this->addDefaultTwigArgs('towns', [
            'towns' => $this->entity_manager->getRepository(Town::class)->findAll(),
            'citizen_stats' => $this->entity_manager->getRepository(Citizen::class)->getStatByLang(),
            'langs' => $this->generatedLangs,
        ]));
    }

    /**
     * @param int $page The page we're viewing
     * @return Response
     */
    #[Route(path: 'jx/admin/town/list/old/{page}', name: 'admin_old_town_list', requirements: ['page' => '\d+'])]
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
            'page' => $page,
            'pages' => $pagesCount
        ]));
    }

    protected function renderInventoryAsBank( Inventory $inventory ) {
        $qb = $this->entity_manager->createQueryBuilder();
        $qb
            ->select('i.id', 'c.label as l1', 'cr.label as l2', 'SUM(i.count) as n')->from(Item::class,'i')
            ->where('i.inventory = :inv')->setParameter('inv', $inventory)
            ->groupBy('i.prototype', 'i.broken', 'i.poison')
            ->leftJoin(ItemPrototype::class, 'p', Join::WITH, 'i.prototype = p.id')
            ->leftJoin(ItemCategory::class, 'c', Join::WITH, 'p.category = c.id')
            ->leftJoin(ItemCategory::class, 'cr', Join::WITH, 'c.parent = cr.id')
            ->addOrderBy('c.ordering','ASC')
            ->addOrderBy('p.icon', 'DESC')
            ->addOrderBy('i.id', 'ASC');

        $data = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $final = [];
        $cache = [];

        foreach ($data as $entry) {
            $label = $entry['l2'] ?? $entry['l1'] ?? 'Sonstiges';
            if (!isset($final[$label])) $final[$label] = [];
            $final[$label][] = [ $entry['id'], $entry['n'] ];
            $cache[] = $entry['id'];
        }

        $item_list = $this->entity_manager->getRepository(Item::class)->findAllByIds($cache);
        foreach ( $final as $label => &$entries )
            $entries = array_map(function( array $entry ) use (&$item_list): BankItem { return new BankItem( $item_list[$entry[0]], $entry[1] ); }, $entries);

        return $final;
    }

	/**
  * @param int $id The internal ID of the town
  * @param TownHandler $townHandler
  * @return Response
  */
 #[Route(path: 'jx/admin/town/dash/{id<\d+>}', name: 'admin_town_dashboard')]
 public function town_explorer_dash(int $id, TownHandler $townHandler): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

		return $this->render('ajax/admin/towns/explorer_dash.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'itemPrototypes' => $this->getOrderedItemPrototypes($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'tab' => "dash",
			'events' => $this->conf->getAllEvents(),
			'current_event' => $this->conf->getCurrentEvents($town),
			'langs' => array_merge($this->generatedLangsCodes, ['multi']),
			'map_public_json' => json_encode($townHandler->get_public_map_blob($town, null, 'door-planner', 'day', "admin/{$town->getId()}", true))
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/bank/{id<\d+>}', name: 'admin_town_bank')]
 public function town_explorer_bank(int $id): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));


		return $this->render('ajax/admin/towns/explorer_bank.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'itemPrototypes' => $this->getOrderedItemPrototypes($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'tab' => "bank",
			'bank' => $this->renderInventoryAsBank($town->getBank()),
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/citizens/{id<\d+>}', name: 'admin_town_citizens')]
 public function town_explorer_citizens(int $id): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

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

		$all_complaints = array_map( fn(ActionEventLog $a) => [
			'on' => $a->getType() === ActionEventLog::ActionEventComplaintIssued,
			'from' => $a->getCitizen(),
			'to' => $this->entity_manager->getRepository(Citizen::class)->find($a->getOpt1()),
			'reason' => $this->entity_manager->getRepository(ComplaintReason::class)->find($a->getOpt2()),
			'time' => $a->getTimestamp()
		], $this->entity_manager->getRepository(ActionEventLog::class)->findBy([
			'type' => [ActionEventLog::ActionEventComplaintIssued,ActionEventLog::ActionEventComplaintRedacted],
			'citizen' => $town->getCitizens()->getValues(),
		], ['timestamp' => 'DESC']));

		$langs = [];
		$langs_alive = [];
		foreach ($town->getCitizens() as $citizen) {
			$lang = $citizen->getUser()->getLanguage() ?? 'multi';
			if (!isset($langs[$lang]))
				$langs[$lang] = $langs_alive[$lang] = 0;
			$langs[$lang]++;
			if ($citizen->getActive()) $langs_alive[$lang]++;
		}

		return $this->render('ajax/admin/towns/explorer_citizen.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'tab' => "citizens",
			"itemPrototypes" => $this->getOrderedItemPrototypes($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'citizenStati' => $this->getOrderedCitizenStatus($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'citizenRoles' => $this->getOrderedCitizenRoles($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'pictoPrototypes' => $this->getOrderedPictoPrototypes($this->getUser()->getAdminLang() ?? $this->getUser()->getLanguage()),
			'citizenProfessions' => $professions,
			'citizen_langs' => $langs,
			'citizen_langs_alive' => $langs_alive,
			'complaints' => $complaints,
			'all_complaints' => $all_complaints,
			'votes' => $votes,
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @param GazetteService $gazetteService
  * @return Response
  */
 #[Route(path: 'jx/admin/town/register/{id<\d+>}', name: 'admin_town_register')]
 public function town_explorer_register(int $id, GazetteService $gazetteService): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

		return $this->render('ajax/admin/towns/explorer_register.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'tab' => "register",
			'gazette' => $gazetteService->renderGazette( $town, $town->getDay(), true),
			'council' => array_map( fn(CouncilEntry $c) => [$gazetteService->parseCouncilLog( $c ), $c->getCitizen()], array_filter( $this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $town->getDay()], ['ord' => 'ASC']),
				fn(CouncilEntry $c) => ($c->getTemplate() && $c->getTemplate()->getText() !== null)
			)),
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/blackboard/{id<\d+>}/{highlight<\d+>}', name: 'admin_town_blackboard')]
 public function town_explorer_blackboard(int $id, int $highlight = 0): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

        $blackboards = $this->entity_manager->getRepository(BlackboardEdit::class)->findBy([ 'town' => $town ], ['time' => 'DESC'], $highlight > 0 ? 500 : 100);
        $reports_q = $this->entity_manager->getRepository(AdminReport::class)->findBy(['blackBoard' => $blackboards]);

        $reports = [];
        foreach ($blackboards as $b) $reports[$b->getId()] = [];
        foreach ($reports_q as $r) $reports[$r->getBlackBoard()->getId()][] = $r;

		return $this->render('ajax/admin/towns/explorer_blackboard.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'tab' => "blackboard",
			'highlight' => $highlight,
			'blackboards' => $blackboards,
			'reports' => $reports,
		])));
	}

	/**
  * @param Town $town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/estimations/{id<\d+>}', name: 'admin_town_estimations')]
 public function town_explorer_estimations(Town $town, EventProxyService $proxy): Response {
        $maxAttacks = [];
        foreach ($town->getZombieEstimations() as $estimation) {
            $day = $estimation->getDay();
            $alive_citizens = $town->getCitizens()->filter( fn(Citizen $c) => $c->getAlive() || $c->getDayOfDeath() >= $day )->count();
            $maxAttacks[$day] = [ $alive_citizens, $proxy->queryTownParameter( $town, BuildingValueQuery::MaxActiveZombies, [$alive_citizens, $day] ) ];
        }

		return $this->render('ajax/admin/towns/explorer_estimations.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
            'active' => $maxAttacks,
			'tab' => "estimations",
		])));
	}

    /**
  * @param EventProxyService $events
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/buildings/{id<\d+>}', name: 'admin_town_buildings')]
 public function town_explorer_buildings(EventProxyService $events, Town $town): Response {
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

        $workshopBonus = $events->queryTownParameter( $town, BuildingValueQuery::ConstructionAPRatio );
        $hpToAp = $events->queryTownParameter( $town, BuildingValueQuery::RepairAPRatio );

		return $this->render('ajax/admin/towns/explorer_buildings.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'tab' => "buildings",
			'dictBuildings' => $dict,
			'rootBuildings' => $root,
			'availBuldings' => $inTown,
			'workshopBonus' => $workshopBonus,
			'hpToAp' => $hpToAp
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/eruins_explorer/{id<\d+>}', name: 'admin_town_eruins_explorer')]
 public function town_explorer_eruins_explorer(int $id): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

        $conf_self = $this->conf->getTownConfiguration($town);

		$explorables = [];
		foreach ($town->getZones() as $zone)
			/** @var Zone $zone */
			if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
				$explorables[$zone->getId()] = ['rz' => [], 'z' => $zone, 'x' => $zone->getExplorerStats(), 'ax' => $zone->activeExplorerStats()];
				if ($zone->activeExplorerStats()) $explorables[$zone->getId()]['axt'] = max(0, $zone->activeExplorerStats()->getTimeout()->getTimestamp() - time());
				$rz = $zone->getRuinZones();
				foreach ($rz as $r) {
					if (!isset( $explorables[$zone->getId()]['rz'][$r->getZ()] ))
						$explorables[$zone->getId()]['rz'][$r->getZ()] = [];
					$explorables[$zone->getId()]['rz'][$r->getZ()][] = $r;
				}
				ksort($explorables[$zone->getId()]['rz']);
			}

		return $this->render('ajax/admin/towns/explorer_eruins_explorer.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'conf' => $conf_self,
			'day' => $town->getDay(),
			'tab' => "eruins_explorer",
			'explorables' => $explorables,
            'town_conf' => $this->conf->getTownConfiguration( $town )
		])));
	}

	/**
  * @param int $id The internal ID of the town
  * @return Response
  */
 #[Route(path: 'jx/admin/town/config/{id<\d+>}/{conf?}', name: 'admin_town_config')]
 public function town_explorer_config(int $id, ?string $conf): Response {
		/** @var Town $town */
		$town = $this->entity_manager->getRepository(Town::class)->find($id);
		if ($town === null) return $this->redirect($this->generateUrl('admin_town_list'));

		$conf_self = $this->conf->getTownConfiguration($town);
		$conf_compare = match($conf) {
			'small', 'remote', 'panda', 'default' => $this->conf->getTownConfigurationByType($conf),
			default => null,
		};

		return $this->render('ajax/admin/towns/explorer_config.html.twig', $this->addDefaultTwigArgs(null, array_merge([
			'town' => $town,
			'day' => $town->getDay(),
			'tab' => "config",
			'opt_conf' => $conf,
			'conf' => $conf_self,
			'conf_self' => $conf_self,
			'conf_compare' => $conf_compare,
			'conf_keys' => array_unique( array_merge( array_keys( $conf_self->raw() ), array_keys( $conf_compare?->raw() ?? [] ) ) ),
		])));
	}

	/**
     * @param int $id
     * @param int $day
     * @param GazetteService $gazetteService
     * @return Response
     */
    #[Route(path: 'jx/admin/town/{id<\d+>}/gazette/{day<\d+>}', name: 'admin_town_explorer_gazette', priority: 1)]
    public function api_explore_gazette(int $id, int $day, GazetteService $gazetteService): Response {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if ($town === null || $day > $town->getDay()) return AjaxResponse::error( ErrorHelper::ErrorInvalidRequest );

        return $this->render('ajax/game/gazette_widget.html.twig', [
            'gazette' => $gazetteService->renderGazette( $town, $day, true ),
            'council' => array_map( fn(CouncilEntry $c) => [$gazetteService->parseCouncilLog( $c ), $c->getCitizen()], array_filter( $this->entity_manager->getRepository(CouncilEntry::class)->findBy(['town' => $town, 'day' => $day], ['ord' => 'ASC']),
                fn(CouncilEntry $c) => ($c->getTemplate() && $c->getTemplate()->getText() !== null)
            ))
        ]);
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
                true,
                seed: -1
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
     * @param int $id
     * @param string|null $tab the tab we want to display
     * @return Response
     */
    #[Route(path: 'jx/admin/town/old/{id<\d+>}/{tab?}', name: 'admin_old_town_explorer')]
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
	 * @param int $id Town ID
	 * @param JSONRequestParser $parser
	 * @return Response
	 */
	#[Route(path: 'api/admin/town/old/{id}/get_citizen_infos', name: 'get_old_citizen_infos', requirements: ['id' => '\d+'])]
	#[IsGranted('ROLE_ADMIN')]
	#[AdminLogProfile(enabled: true)]
	public function get_old_citizen_infos(int $id, JSONRequestParser  $parser): Response{
		$town = $this->entity_manager->getRepository(TownRankingProxy::class)->find($id);
		if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

		$citizen_id = $parser->get('citizen_id', -1);
		$citizen = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($citizen_id);

		if(!$citizen || $citizen->getTown() !== $town)
			return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

		$pictos = $this->renderView("ajax/admin/towns/distinctions.html.twig", [
			'pictos' => $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($citizen->getUser(), $citizen->getTown()),
		]);

		return AjaxResponse::success(true, [
			'pictos' => $pictos,
		]);
	}

    /**
     * @param int $id The ID of the town
     * @param string $action The action to perform
     */
    #[Route(path: 'api/admin/town/{id}/do/{action}', name: 'admin_town_manage', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function town_manager(int $id, string $action, ItemFactory $itemFactory, RandomGenerator $random,
                                 NightlyHandler $night, GameFactory $gameFactory, CrowService $crowService,
                                 KernelInterface $kernel, JSONRequestParser $parser, TownHandler $townHandler,
                                 GameProfilerService $gps, MapMaker $mapMaker): Response
    {

        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ((str_starts_with($action, 'dbg_') || in_array($action, ['ex_inf'])) && $kernel->getEnvironment() !== 'dev')
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (in_array($action, [
                'release', 'quarantine', 'advance', 'nullify', 'pw_change',
                'ex_del', 'ex_co+', 'ex_co-', 'ex_ref', 'ex_inf',
                'dbg_fill_town', 'dbg_fill_bank', 'dgb_empty_bank', 'dbg_unlock_bank', 'dbg_hydrate', 'dbg_disengage', 'dbg_engage',
                'dbg_set_well', 'dbg_unlock_buildings', 'dbg_map_progress', 'dbg_map_zombie_set', 'dbg_adv_days',
                'dbg_set_attack', 'dbg_toggle_chaos', 'dbg_toggle_devas', 'dbg_enable_stranger', 'dropall',
            ]) && !$this->isGranted('ROLE_ADMIN'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        if (in_array($action, [
                'set_name',
            ]) && !($this->isGranted('ROLE_ADMIN') || $town->getType()->getName() === 'custom'))
            return AjaxResponse::error(ErrorHelper::ErrorPermissionError);

        $this->logger->invoke("[town_manager] Admin <info>{$this->getUser()->getName()}</info> did the action <info>$action</info> in the town <info>{$town->getName()}</info> (id: {$town->getId()})");
        $this->clearTownCaches($town);

        $param = $parser->get('param');

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
            case 'pw_change':
                if (!$town->isOpen()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $town->setPassword( empty(trim($param)) ? null : $param );
                break;
            case 'nullify':
                foreach ($town->getCitizens() as $citizen)
                    $this->entity_manager->persist(
                        $crowService->createPM_townNegated($citizen->getUser(), $town->getName(), false)
                    );
                $gameFactory->nullifyTown($town, true);
                break;
            case 'clear_bb':
                $town->setWordsOfHeroes("");
                $this->entity_manager->persist((new BlackboardEdit())->setText("")->setTime(new \DateTime())->setTown($town)->setUser($this->getUser()));
                $this->entity_manager->persist($town);
                break;
            case 'set_name': case 'dice_name':
                $old_name = $town->getName();
                $schema = null;
                $new_name = $action === 'dice_name'
                    ? $gameFactory->createTownName( $town->getLanguage(), $schema )
                    : trim($param ?? '');
                if (empty($new_name)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $town->setName( $new_name )->setNameSchema( $schema );
                $town->getRankingEntry()->setName( $new_name );
                $town->getForum()?->setTitle( $new_name );
                $this->entity_manager->persist($town);
                $this->entity_manager->persist($town->getRankingEntry());
                foreach ($town->getCitizens() as $citizen)
                    $this->entity_manager->persist($this->crow_service->createPM_moderation( $citizen->getUser(), CrowService::ModerationActionDomainRanking, CrowService::ModerationActionTargetGameName, CrowService::ModerationActionEdit, $town, $old_name ));
                break;
            case 'toggle_lockdown':
                $town->setLockdown(!$town->getLockdown());
                if($town->getLockdown()) {
                    $town->setDoor(false);
                }
                $this->entity_manager->persist($town);
                break;
            case 'toggle_broken_door':
                $town->setBrokenDoor(!$town->getBrokenDoor());
                if($town->getBrokenDoor()) {
                    $town->setDoor(true);
                }
                $this->entity_manager->persist($town);
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

                $this->entity_manager->flush();

                $null = null;
                foreach ($users as $selected_user) {
                    $citizen = $gameFactory->createCitizen($town, $selected_user, $error, $null, true);
                    if ($citizen === null) continue;
                    $this->entity_manager->persist($town);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->flush();

                    $pro = $random->pick($professions);
                    $this->citizen_handler->applyProfession($citizen, $pro);
                    $this->entity_manager->persist($citizen);
                    $this->entity_manager->persist($town);

                    if ($citizen->getProfession()->getName() !== 'none')
                        $gps->recordCitizenProfessionSelected( $citizen );

                    $this->entity_manager->flush();
                }

                break;

            case 'dbg_fill_bank':
                $bank = $town->getBank();
                foreach ($this->entity_manager->getRepository(ItemPrototype::class)->findAll() as $repo)
                    $this->inventory_handler->forceMoveItem( $bank, ($itemFactory->createItem( $repo ))->setCount(500) );

                $this->entity_manager->persist( $bank );
                break;

            case 'dbg_empty_bank':
                $bank = $town->getBank();
                foreach ($bank->getItems() as $item)
                    $this->inventory_handler->forceRemoveItem($item, $item->getCount());

                $this->entity_manager->persist( $bank );
                break;

            case 'dbg_unlock_bank':
                foreach ($town->getCitizens() as $citizen) {
                    $bank_lock = $this->entity_manager->getRepository(ActionEventLog::class)->findBy(['citizen' => $citizen, 'type' => [ActionEventLog::ActionEventTypeBankTaken, ActionEventLog::ActionEventTypeBankLock]]);
                    foreach ($bank_lock as $lock) $this->entity_manager->remove($lock);
                }
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

            case 'dbg_set_well':
                if (!is_numeric($param)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $town->setWell( max(0,$param));
                $this->entity_manager->persist($town);
                break;

            case 'dbg_unlock_buildings':
                do {
                    $possible = array_filter( $this->entity_manager->getRepository(BuildingPrototype::class)->findProspectivePrototypes( $town ), fn(BuildingPrototype $p) => $p->getBlueprint() === null || $p->getBlueprint() < 5 );
                    $found = !empty($possible);
                    foreach ($possible as $proto) {
                        $townHandler->addBuilding($town, $proto);
                        $gps->recordBuildingDiscovered( $proto, $town, null, 'debug' );
                    }
                } while ($found);
                $this->entity_manager->persist( $town );
                break;

            case 'dbg_map_progress':
                if (empty($param)) $d = null;
                else {
                    if (!is_numeric($param) || (int)$param <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                    $d = (int)$param;
                }
                $mapMaker->dailyZombieSpawn( $town, 1, MapMaker::RespawnModeAuto, $d );
                $this->entity_manager->persist( $town );
                break;

            case 'dbg_map_zombie_set':
                $param_base = explode(',',$param);
                if (count($param_base) !== 2) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                if (trim($param_base[1]) === 'today') $zeds = -1;
                elseif (trim($param_base[1]) === 'initial') $zeds = -2;
                else {
                    if (!is_numeric(trim($param_base[1]))) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                    $zeds = (int)trim($param_base[1]);
                    if ($zeds < 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                }

                if ($param_base[0] === 'all') {
                    foreach ($town->getZones() as $zone) if (!$zone->isTownZone())
                        $zone
                            ->setZombies( $zeds === -1 ? $zone->getInitialZombies() : ( $zeds === -2 ? $zone->getStartZombies() : $zeds ) )
                            ->setInitialZombies( $zeds === -2 ? $zone->getStartZombies() : $zone->getInitialZombies() );

                } else {
                    $param_vals = explode(':',$param_base[0]);
                    if (count($param_vals) === 1) $param_vals[] = $param_vals[0];
                    elseif (count($param_vals) > 2) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $pair = explode( '/', $param_vals[0] );
                    if (count($pair) !== 2 || !is_numeric(trim($pair[0])) || !is_numeric(trim($pair[1]))) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $from_x = (int)trim($pair[0]);
                    $from_y = (int)trim($pair[1]);

                    $pair = explode( '/', $param_vals[1] );
                    if (count($pair) !== 2 || !is_numeric(trim($pair[0])) || !is_numeric(trim($pair[1]))) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $to_x = (int)trim($pair[0]);
                    $to_y = (int)trim($pair[1]);

                    for ($x = min($from_x,$to_x); $x <= max($from_x,$to_x); $x++)
                        for ($y = min($from_y,$to_y); $y <= max($from_y,$to_y); $y++)
                            if (($zone = $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$x,$y)) && !$zone->isTownZone())
                                $zone
                                    ->setZombies( $zeds === -1 ? $zone->getInitialZombies() : ( $zeds === -2 ? $zone->getStartZombies() : $zeds ) )
                                    ->setInitialZombies( $zeds === -2 ? $zone->getStartZombies() : $zone->getInitialZombies() );                }


                $this->entity_manager->persist( $town );
                break;

            case 'dbg_adv_days':
                $days = (int)$param;
                if ($days <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                for ($i = 0; $i < $days; $i++)
                    if ($night->advance_day($town, $this->conf->getCurrentEvents($town))) {
                        foreach ($night->get_cleanup_container() as $c) $this->entity_manager->remove($c);
                        $town->setAttackFails(0);
                        $this->entity_manager->persist($town);
                        foreach ($town->getCitizens() as $c)
                            if ($c->getAlive()) $this->citizen_handler->removeStatus($c, 'thirst2');
                        $this->entity_manager->flush();
                    } else break;

                break;

            case 'dbg_set_attack':
                if (empty($param)) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $list = explode(':', $param);
                if (count($list) === 1) $list = [ $town->getDay(), (int)$list[0] ];
                else $list = [ (int)$list[0], (int)$list[1] ];

                if ($list[0] < $town->getDay() || $list[1] <= 0) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest, [$list[0],$town->getDay(),$list[1]]);
                $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneByTown($town,$list[0]);
                if ($est === null) {
                    $off_min = mt_rand( 10, 24 );
                    $off_max = 34 - $off_min;
                    $town->addZombieEstimation(
                        $est = (new ZombieEstimation())
                            ->setDay( $list[0] )
                            ->setZombies( $list[1] )
                            ->setOffsetMin( $off_min )
                            ->setOffsetMax( $off_max )
                    );
                } else $est->setZombies($list[1]);

                $this->entity_manager->persist($est);
                break;

            case 'dbg_toggle_chaos':
                $on = $param === '1';
                if (($town->getChaos() === $on) || ($town->getDevastated() && !$on))
                    return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $town->setChaos($on);
                if ($on) foreach ($town->getCitizens() as $target_citizen)
                    $target_citizen->setBanished(false);
                break;

            case 'dbg_toggle_devas':
                $on = $param === '1';
                if ($town->getDevastated() === $on) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                if ($on)
                    $townHandler->devastateTown($town);
                else $town->setDevastated(false);
                break;

            case 'ex_del': case 'ex_co+': case 'ex_co-':case 'ex_ref':case 'ex_inf':
                /** @var RuinExplorerStats $session */
                $session = $this->entity_manager->getRepository(RuinExplorerStats::class)->find($param);
                if (!$session || $session->getCitizen()->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                if ($action !== 'ex_del' && !$session->getActive()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                switch ($action) {
                    case 'ex_del':
                        $session->getCitizen()->removeExplorerStat( $session );
                        $this->entity_manager->remove( $session );
                        break;
                    case 'ex_co+':
                        $session->setTimeout(new \DateTime())->setActive(false);
                        $this->entity_manager->persist($session);
                        break;
                    case 'ex_co-':
                        $session->setTimeout(new \DateTime());
                        $this->entity_manager->persist($session);
                        break;
                    case 'ex_ref':
                        $session->setTimeout(clone $session->getTimeout()->modify('+1min'));
                        $this->entity_manager->persist($session);
                        break;
                    case 'ex_inf':
                        $session->setTimeout(clone $session->getTimeout()->modify('+24hours'));
                        $this->entity_manager->persist($session);
                        break;
                    default: break;
                }

            case 'dbg_enable_stranger':
                $gameFactory->enableStranger( $town );
                break;

            case 'dropall':
                foreach ($town->getCitizens() as $citizen) {
                    if (!$citizen->getAlive()) continue;
                    foreach ($citizen->getInventory()->getItems() as $item)
                        if (!$item->getEssential())
                            $this->inventory_handler->forceMoveItem( ($citizen->getZone()?->isTownZone() ? $town->getBank() : $citizen->getZone()?->getFloor()) ?? $town->getBank(), $item );
                    foreach ($citizen->getHome()->getChest()->getItems() as $item)
                        $this->inventory_handler->forceMoveItem( $town->getBank(), $item );
                }
                break;
            case 'admin_regenerate_ruins':

                break;
            case 'set_town_base_def':
                $town->setBaseDefense($param);
                break;
            case 'set_town_temp_def':
                $town->setTempDefenseBonus($param);
                break;

            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        try {
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['e' => $e->getMessage()]);
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id The ID of the town
     * @param JSONRequestParser $parser
     * @param TownHandler $townHandler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/set_event', name: 'admin_town_set_event', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function admin_town_set_event(int $id, JSONRequestParser $parser, TownHandler $townHandler): Response {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $eventName = $parser->get('param');

        $town->setManagedEvents($eventName !== "");

        if($eventName !== "" && $eventName !== null){
            $this->logger->invoke("[admin_town_set_event] Admin <info>{$this->getUser()->getName()}</info> enabled the event <info>$eventName</info> in the town <info>{$town->getName()}</info> (id: {$town->getId()})");
            $townHandler->updateCurrentEvents($town, [$this->conf->getEvent($eventName)]);
        } else {
            $this->logger->invoke("[admin_town_set_event] Admin <info>{$this->getUser()->getName()}</info> disabled the events in the town <info>{$town->getName()}</info> (id: {$town->getId()})");
            $currentEvents = $this->conf->getCurrentEvents($town, $markers);
            foreach ($markers as $marker) {
                /** @var EventActivationMarker $marker */
                $marker->setActive(false);
                $this->entity_manager->persist($marker);
            }
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id The ID of the town
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/set_lang', name: 'admin_town_set_lang', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function admin_town_set_lang(int $id, JSONRequestParser $parser): Response {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $newLang = $parser->get('param');
        if (!in_array( $newLang, array_merge($this->generatedLangsCodes, [ 'multi' ]) ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $town->setLanguage( $newLang );
        $town->getRankingEntry()->setLanguage( $newLang );

        $this->clearTownCaches($town);
        $this->entity_manager->persist($town);
        $this->entity_manager->persist($town->getRankingEntry());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param GameFactory $gameFactory
     * @param TownHandler $townHandler
     * @param GameProfilerService $gps
     * @return Response
     */
    #[Route(path: 'api/admin/town/new', name: 'admin_new_town')]
    #[AdminLogProfile(enabled: true)]
    public function add_default_town( JSONRequestParser $parser, GameFactory $gameFactory, TownHandler $townHandler, GameProfilerService $gps): Response {

        $town_name = $parser->get('name', null) ?: null;
        $town_type = $parser->get('type', '');
        $town_lang = $parser->get('lang', 'de');
        $town_time = $parser->get('time', '');

        try {
            $town_time = empty($town_time) ? null : new \DateTime($town_time);
            if ($town_time <= new \DateTime()) $town_time = null;
        } catch (\Throwable) {
            $town_time = null;
        }


        if (!in_array($town_lang, array_merge($this->generatedLangsCodes, ['multi'])))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $this->logger->invoke("[add_default_town] Admin <info>{$this->getUser()->getName()}</info> created a <info>$town_lang</info> town (custom name: '<info>$town_name</info>'), which is of type <info>$town_type</info>");

        $current_events = $this->conf->getCurrentEvents();
        $name_changers = array_values(
            array_map( fn(EventConf $e) => $e->get( EventConf::EVENT_MUTATE_NAME ), array_filter($current_events,fn(EventConf $e) => $e->active() && $e->get( EventConf::EVENT_MUTATE_NAME )))
        );

        $town = $gameFactory->createTown( new TownSetup( $town_type, name: $town_name, language: $town_lang, nameMutator: $name_changers[0] ?? null ));
        if (!$town) {
            $this->logger->invoke("Town creation failed!");
            return AjaxResponse::error(ErrorHelper::ErrorInternalError);
        }

        $town->setScheduledFor( $town_time );

        try {
            $this->entity_manager->persist( $town );
            $this->entity_manager->flush();
            $gps->recordTownCreated( $town, $this->getUser(), 'manual' );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException, ['e' => $e->getMessage()]);
        }

        $current_event_names = array_map(fn(EventConf $e) => $e->name(), array_filter($current_events, fn(EventConf $e) => $e->active()));
        if (!empty($current_event_names)) {
            if (!$townHandler->updateCurrentEvents($town, $current_events)) {
                $this->entity_manager->clear();
            } else {
                $this->entity_manager->persist($town);
                $this->entity_manager->flush();
            }
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/item', name: 'admin_town_item', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_item_action(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $item_id = $parser->get('item');
        $change = $parser->get('change');
        $qty = $parser->get('qty', 1);
        if($qty <= 0)
            $qty = 1;

        $item = $this->entity_manager->getRepository(Item::class)->find($item_id);

        if ($change == 'add') {
            for($i = 0 ; $i < $qty ; $i++)
                $handler->forceMoveItem($town->getBank(), $itemFactory->createItem($item->getPrototype()->getName()));
        } else {
            $handler->forceRemoveItem($item, $qty);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($town->getBank());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param ZoneHandler $handler
     * @param TownHandler $townHandler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/teleport', name: 'admin_teleport_citizen', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function teleport_citizen(int $id, JSONRequestParser $parser, ZoneHandler $handler, TownHandler $townHandler): Response
    {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $targets = $parser->get_array('targets');
        if (empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $to = $parser->get('to');
        if ($to !== 'town' && ( !is_array($to) || count($to) !== 2 ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $target_zone = $to === 'town' ? null : $this->entity_manager->getRepository(Zone::class)->findOneByPosition($town,$to[0],$to[1]);
        if ($target_zone === null && $to !== 'town') return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $old_zones = [];
        $cp_target_zone = !$target_zone || $handler->isZoneUnderControl($target_zone);

        $escort = $parser->get('escort', false);

        foreach (array_unique($targets) as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);

            if (!$citizen || $citizen->getTown() !== $town)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if ($citizen->getZone() === $target_zone) continue;

            $movers = [$citizen];

            if($escort) {
                foreach ($citizen->getValidLeadingEscorts() as $escort)
                    $movers[] = $escort->getCitizen();
            } else {
                foreach ($citizen->getLeadingEscorts() as $escort) {
                    $escort->getCitizen()->getEscortSettings()->setLeader(null);
                    $this->entity_manager->persist($escort->getCitizen());
                }
            }

            foreach ($movers as $mover){
                if ($mover->getZone()) {
                    if (!isset($old_zones[$mover->getZone()->getId()]))
                        $old_zones[$mover->getZone()->getId()] = [$mover->getZone(), $handler->isZoneUnderControl( $mover->getZone() )];

                    if ($dig_timer = $mover->getCurrentDigTimer()) {
                        $dig_timer->setPassive(true);
                        $this->entity_manager->persist( $dig_timer );
                    }

                    $mover->getZone()->removeCitizen( $mover );
                }

                if ($target_zone) $target_zone->addCitizen( $mover );
                $this->entity_manager->persist($mover);
            }
        }

        foreach ($old_zones as $old_zone_data) {
            $handler->handleCitizenCountUpdate($old_zone_data[0],$old_zone_data[1]);
            $this->entity_manager->persist($old_zone_data[0]);
        }

        if ($target_zone) {
            $upgraded_map = $townHandler->getBuilding($town,'item_electro_#00', true);
            $target_zone
                ->setDiscoveryStatus( Zone::DiscoveryStateCurrent )
                ->setZombieStatus( max($upgraded_map ? Zone::ZombieStateExact : Zone::ZombieStateEstimate, $target_zone->getZombieStatus() ) );
            $handler->handleCitizenCountUpdate($target_zone,$cp_target_zone);
            $this->entity_manager->persist($target_zone);
        }

        $this->clearTownCaches($town);

        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param ZoneHandler $handler
     * @param TownHandler $townHandler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/alias', name: 'admin_alias_citizen', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function alias_citizen(int $id, JSONRequestParser $parser, ConfMaster $cf, TownHandler $townHandler): Response
    {
        /** @var Town $town */
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $alias = $parser->trimmed('alias');
        $targets = $parser->get_array('targets');
        if ($alias != null && !$alias)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        if (count($targets) > 1)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var Citizen $citizen */
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find($targets[0]);

        $town_conf = $cf->getTownConfiguration($citizen->getTown());

        $citizen_alias_active = $town_conf->get(TownConf::CONF_FEATURE_CITIZEN_ALIAS, false);

        if(!$citizen_alias_active)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen->setAlias($alias);
        $this->clearTownCaches($town);

        try {
            $this->entity_manager->persist( $citizen );
            $this->entity_manager->flush();
        } catch (Exception $e) {
            return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
        }
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param InventoryHandler $handler
     * @param ItemFactory $itemFactory
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/spawn_item', name: 'admin_spawn_item', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function spawn_item(int $id, JSONRequestParser $parser, InventoryHandler $handler, ItemFactory $itemFactory): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $prototype_id = $parser->get('prototype');
        $number = $parser->get_int('number');
        $targets = $parser->get_array('targets');

        $conf = $parser->get_array('conf');
        $poison = $conf['poison'] ?? false;
        if ($poison > 1) $poison = ItemPoisonType::from( $poison );
        $broken = $conf['broken'] ?? false;
        $essential = $conf['essential'] ?? false;
        $hidden = $conf['hidden'] ?? false;

        if (empty($targets))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        /** @var ItemPrototype $itemPrototype */
        if ($prototype_id == "all") {
            $itemPrototype = $this->entity_manager->getRepository(ItemPrototype::class)->findAll();
        } else {
            $itemPrototype = $this->entity_manager->getRepository(ItemPrototype::class)->find($prototype_id);
            if (!$itemPrototype) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (!is_array($itemPrototype))
            $itemPrototype = [$itemPrototype];

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
            for ($i = 0; $i < $number; $i++) {
                if ($hidden && $inventory->getZone()) $inventory->getZone()->setItemsHiddenAt( new \DateTimeImmutable() );
                foreach ($itemPrototype as $proto) {
                    $handler->forceMoveItem($inventory, $itemFactory->createItem($proto->getName(), $broken, $poison)->setEssential($essential)->setHidden($hidden && $inventory->getZone()));
                }

            }
            if ($hidden && $inventory->getZone()) $this->entity_manager->persist($inventory->getZone());
            $this->entity_manager->persist($inventory);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/get_zone_infos', name: 'get_zone_infos', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function get_zone_infos(int $id, JSONRequestParser  $parser): Response{
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $zone_id = $parser->get('zone_id', -1);
		/** @var Zone $zone */
        $zone = $this->entity_manager->getRepository(Zone::class)->find($zone_id);

        if(!$zone || $zone->getTown() !== $town)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $view = $this->renderView("ajax/game/inventory.html.twig", [
            'size' => 0,
            'items' => $zone->getFloor()->getItems()
        ]);

        return AjaxResponse::success(true, [
            'view' => $view,
			'zone_coords' => ["x" => $zone->getX(), "y" => $zone->getY()],
            'zone_digs' => $zone->getDigs(),
            'ruin_digs' => $zone->getPrototype() !== null ? $zone->getRuinDigs() : 0,
            'ruin_bury' => $zone->getBuryCount(),
            'camp_levl' => $zone->getImprovementLevel(),
            'ruin_camp' => $zone->getPrototype()?->getCampingLevel(),
        ]);
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/get_citizen_infos', name: 'get_citizen_infos', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function get_citizen_infos(int $id, JSONRequestParser  $parser): Response{
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen_id = $parser->get('citizen_id', -1);
        $citizen = $this->entity_manager->getRepository(Citizen::class)->find($citizen_id);

        if(!$citizen || $citizen->getTown() !== $town)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $rucksack = $this->renderView("ajax/game/inventory.html.twig", [
            'size' => $this->inventory_handler->getSize( $citizen->getInventory() ),
            'items' => $citizen->getInventory()->getItems()
        ]);

        $chest = $this->renderView("ajax/game/inventory.html.twig", [
            'size' => $this->inventory_handler->getSize( $citizen->getHome()->getChest() ),
            'items' => $citizen->getHome()->getChest()->getItems()
        ]);

        $pictos = $this->renderView("ajax/admin/towns/distinctions.html.twig", [
            'pictos' => $this->entity_manager->getRepository(Picto::class)->findPictoByUserAndTown($citizen->getUser(), $citizen->getTown()),
        ]);

        return AjaxResponse::success(true, [
            'desc' => $citizen->getAlive() ? $citizen->getHome()->getDescription() : $citizen->getRankingEntry()->getLastWords(),
            'rucksack' => $rucksack,
            'chest' => $chest,
            'pictos' => $pictos,
        ]);
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/clear_citizen_attribs', name: 'clear_citizen_attribs', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function clear_citizen_attribs(int $id, JSONRequestParser  $parser) {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $id = $parser->get_int('id');
        $clear = $parser->get('clear');

        $citizen = $this->entity_manager->getRepository(Citizen::class)->find($id);
        if (!$citizen || $citizen->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        switch ($clear) {
            case 'citizen-custom-message':
                $this->entity_manager->persist( $citizen->getHome()->setDescription( null ) );
                $this->entity_manager->persist( $citizen->setLastWords( '' ) );
                $this->entity_manager->persist( $citizen->getRankingEntry()->setLastWords( null ) );
                break;
            default:
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/set_zone_attribs', name: 'set_zone_attribs', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function set_zone_attribs(int $id, JSONRequestParser  $parser): Response{
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $zone_id = $parser->get('zone_id', -1);
        $zone = $this->entity_manager->getRepository(Zone::class)->find($zone_id);

        if(!$zone || $zone->getTown() !== $town)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $target = $parser->get("target");
        $value = $parser->get_num('value', 0);

        switch ($target) {
            case 'zone':
                $zone->setDigs( max(0, $value) );
                break;
            case 'ruin':
                if (!$zone->getPrototype())
                    return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $zone->setRuinDigs( max(0, $value) );
                break;
            case 'bury':
                if (!$zone->getPrototype())
                    return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                $zone->setBuryCount( max(0, $value) );
                break;
            case 'camp':
                $zone->setImprovementLevel( max(0, $value) );
                break;
            default: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($zone);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser
     * @param CitizenHandler $handler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/modify_prof', name: 'admin_modify_profession', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
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

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $tid
     * @param int $act
     * @return Response
     */
    #[Route(path: 'api/admin/town/{tid}/event-tag/{act}', name: 'admin_town_event_tag_control', requirements: ['tid' => '\d+', 'act' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function ranking_event_toggle_town(int $tid, int $act): Response
    {
        $town_proxy = $this->entity_manager->getRepository(TownRankingProxy::class)->find($tid);
        if (!$town_proxy) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $town_proxy->setEvent( $act !== 0 );
        $this->entity_manager->persist($town_proxy);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $tid
     * @param int $act
     * @param JSONRequestParser $request
     * @return Response
     */
    #[Route(path: 'api/admin/town/{tid}/unrank/{act}', name: 'admin_town_town_ranking_control', requirements: ['tid' => '\d+', 'act' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function ranking_toggle_town(int $tid, int $act, JSONRequestParser $request): Response
    {
        $town_proxy = $this->entity_manager->getRepository(TownRankingProxy::class)->find($tid);
        if (!$town_proxy) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $flag = $request->get("flag") ?? TownRankingProxy::DISABLE_RANKING;

        //$town_proxy->setDisabled( $act !== 0 );
        if($act)
            $town_proxy->addDisableFlag($flag);
        else
            $town_proxy->removeDisableFlag($flag);

        $this->entity_manager->persist($town_proxy);
        $this->entity_manager->flush();

        foreach ($town_proxy->getCitizens() as $citizen) {
            if(($flag & TownRankingProxy::DISABLE_SOULPOINTS) === TownRankingProxy::DISABLE_SOULPOINTS) {
                $this->entity_manager->persist($citizen->getUser()
                    ->setSoulPoints($this->user_handler->fetchSoulPoints($citizen->getUser(), false))
                    ->setImportedSoulPoints($this->user_handler->fetchImportedSoulPoints($citizen->getUser()))
                );
            }
            if(($flag & TownRankingProxy::DISABLE_PICTOS) === TownRankingProxy::DISABLE_PICTOS) {
                foreach ($this->entity_manager->getRepository(Picto::class)->findNotPendingByUserAndTown($citizen->getUser(), $town_proxy) as $picto)
                    if (!$picto->isManual())
                        $this->entity_manager->persist($picto->setDisabled($citizen->hasDisableFlag(CitizenRankingProxy::DISABLE_PICTOS) || $town_proxy->hasDisableFlag(TownRankingProxy::DISABLE_PICTOS)));
            }
        }


        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $tid
     * @param JSONRequestParser $parser
     * @param GameFactory $gameFactory
     * @return Response
     */
    #[Route(path: 'api/admin/town/{tid}/relang', name: 'admin_town_town_lang_control', requirements: ['tid' => '\d+', 'act' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function switch_town_lang(int $tid, JSONRequestParser $parser, GameFactory $gameFactory): Response
    {
        /** @var TownRankingProxy $town_proxy */
        $town_proxy = $this->entity_manager->getRepository(TownRankingProxy::class)->find($tid);
        if (!$town_proxy || $town_proxy->getImported()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $lang = $parser->get('lang');
        $rename = $parser->get( 'rename' );

        if ($lang !== ($town_proxy->getLanguage() ?? '') && !in_array( $lang, array_merge($this->generatedLangsCodes, [ 'multi' ]) ))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($lang !== ($town_proxy->getLanguage() ?? '')) {
            $town_proxy->setLanguage( $lang );
            $town_proxy->getTown()?->setLanguage($lang);
        }

        if ($rename) {
            $old_name = $town_proxy->getName( );
            $name = $gameFactory->createTownName( $lang, $schema );
            $town_proxy->setName( $name );
            $town_proxy->getTown()?->setName($name)?->setNameSchema($schema);

            foreach ($town_proxy->getCitizens() as $citizen)
                $this->entity_manager->persist($this->crow_service->createPM_moderation( $citizen->getUser(), CrowService::ModerationActionDomainRanking, CrowService::ModerationActionTargetGameName, CrowService::ModerationActionEdit, $town_proxy, $old_name ));
        }

		if ($town_proxy->getTown() !== null)
        	$this->clearTownCaches($town_proxy->getTown());
        $this->entity_manager->persist($town_proxy);
        if ($town_proxy->getTown()) $this->entity_manager->persist($town_proxy->getTown());
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $tid
     * @param int $cid
     * @param int $act
     * @param JSONRequestParser $parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{tid}/unrank_single/{cid}/{act}', name: 'admin_town_citizen_ranking_control', requirements: ['tid' => '\d+', 'cid' => '\d+', 'act' => '\d+'])]
    #[IsGranted('ROLE_CROW')]
    #[AdminLogProfile(enabled: true)]
    public function ranking_toggle_citizen(int $tid, int $cid, int $act, JSONRequestParser $parser): Response
    {
        $town_proxy = $this->entity_manager->getRepository(TownRankingProxy::class)->find($tid);
        if (!$town_proxy) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $citizen_proxy = $this->entity_manager->getRepository(CitizenRankingProxy::class)->find($cid);
        if (!$citizen_proxy) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (!$town_proxy->getCitizens()->contains($citizen_proxy))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $flag = $parser->get('flag');
        if (!$flag)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if ($act) {
            $citizen_proxy->addDisableFlag($flag);
        } else {
            $citizen_proxy->removeDisableFlag($flag);
        }

        if (!$citizen_proxy->hasDisableFlag(CitizenRankingProxy::DISABLE_NOTHING) && $citizen_proxy->getResetMarker()) {
            $this->entity_manager->remove( $citizen_proxy->getResetMarker() );
            $citizen_proxy->setResetMarker(null);
        }
        $this->entity_manager->persist($citizen_proxy);
        $this->entity_manager->flush();

        if(($flag & CitizenRankingProxy::DISABLE_SOULPOINTS) === CitizenRankingProxy::DISABLE_SOULPOINTS) {
            $this->entity_manager->persist($citizen_proxy->getUser()
                ->setSoulPoints( $this->user_handler->fetchSoulPoints( $citizen_proxy->getUser(), false ) )
                ->setImportedSoulPoints( $this->user_handler->fetchImportedSoulPoints( $citizen_proxy->getUser() ) )
            );
        }
        if(($flag & CitizenRankingProxy::DISABLE_PICTOS) === CitizenRankingProxy::DISABLE_PICTOS) {
            foreach ($this->entity_manager->getRepository(Picto::class)->findNotPendingByUserAndTown($citizen_proxy->getUser(), $town_proxy) as $picto)
                if (!$picto->isManual())
                    $this->entity_manager->persist($picto->setDisabled($citizen_proxy->hasDisableFlag(CitizenRankingProxy::DISABLE_PICTOS)));
        }

        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/picto/give', name: 'admin_town_give_picto', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_give_picto(int $id, JSONRequestParser $parser, EventProxyService $proxy): Response
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
        $to = $parser->get_array( 'to' );
        $text = $parser->trimmed( 'text' );

        /** @var PictoPrototype $pictoPrototype */
        $pictoPrototype = $this->entity_manager->getRepository(PictoPrototype::class)->find($prototype_id);

        foreach ($town->getCitizens() as $citizen) {
            /** @var Citizen $citizen */
            if (!in_array( $citizen->getId(), $to )) continue;

            $picto = $this->entity_manager->getRepository(Picto::class)->findByUserAndTownAndPrototype($citizen->getUser(), $town, $pictoPrototype);
            if (null === $picto) {
                $picto = new Picto();
                $picto->setPrototype($pictoPrototype)
                    ->setPersisted(2)
                    ->setUser($citizen->getUser());
                if (is_a($town, Town::class))
                    $picto->setOld($town->getSeason() === null)->setTown($town);
                else
                    $picto->setTownEntry($town);
                $citizen->getUser()->addPicto($picto);
            }

            $picto->setCount($picto->getCount() + $number)->setDisabled(false)->setManual(true);

            if (!empty($text)) {

                $comment = ($picto->getId() !== null ? $this->entity_manager->getRepository(PictoComment::class)->findOneBy(['picto' => $picto]) : null)
                    ?? (new PictoComment())->setPicto( $picto )->setOwner( $citizen->getUser() )->setDisplay( true );

                $comment->setText( $text );
                $this->entity_manager->persist($comment);
            }

            $this->entity_manager->persist($citizen->getUser());
            $this->entity_manager->persist($picto);
        }

        $this->entity_manager->flush();

        foreach ($town->getCitizens() as $citizen) {
            /** @var Citizen $citizen */
            if (!in_array($citizen->getId(), $to)) continue;

            $proxy->pictosPersisted( $citizen->getUser(), $town->getSeason() );
        }

        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CitizenHandler $handler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/home/manage', name: 'admin_town_manage_home', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_manage_home(int $id, JSONRequestParser $parser, CitizenHandler $handler): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $target   = $parser->get('target');
        $citizens = $parser->get_array('citizen');
        $dif      = $parser->get_int('dif', 0);
        $t_id     = $parser->get_int('id', -1);

        if ($dif === 0) return AjaxResponse::success();

        foreach ($citizens as $cid) {

            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($cid);
            if (!$citizen || $citizen->getTown() !== $town || !$citizen->getAlive()) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if (!$citizen->getHome()->getPrototype()->getAllowSubUpgrades() && in_array($target, ['proto','sub']))
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            switch ($target) {

                case 'home':
                    $new_proto = $this->entity_manager->getRepository(CitizenHomePrototype::class)->findOneByLevel( $citizen->getHome()->getPrototype()->getLevel() + $dif );
                    if (!$new_proto) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $citizen->getHome()->setPrototype( $new_proto );
                    $this->entity_manager->persist($citizen->getHome());
                    break;

                case 'sub':
                    $upgrade = $this->entity_manager->getRepository(CitizenHomeUpgrade::class)->find($t_id);
                    if ($upgrade === null || $upgrade->getHome() !== $citizen->getHome())
                        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    if ($upgrade->getLevel() + $dif <= 0) {
                        $citizen->getHome()->removeCitizenHomeUpgrade($upgrade);
                        $upgrade->setHome(null);
                        $this->entity_manager->persist($citizen->getHome());
                        $this->entity_manager->remove($upgrade);
                    } else {
                        $level_proto = $this->entity_manager->getRepository(CitizenHomeUpgradeCosts::class)->findOneBy(['prototype' => $upgrade->getPrototype(), 'level' => $upgrade->getLevel() + $dif]);

                        if ($level_proto === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
                        $this->entity_manager->persist($upgrade->setLevel( $upgrade->getLevel() + $dif ));
                    }

                    break;

                case 'proto':
                    $upgrade_proto = $this->entity_manager->getRepository(CitizenHomeUpgradePrototype::class)->find($t_id);
                    if ($upgrade_proto === null)
                        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    foreach ($citizen->getHome()->getCitizenHomeUpgrades() as $upgrade) if ($upgrade->getPrototype() === $upgrade_proto)
                        return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $level_proto = $this->entity_manager->getRepository(CitizenHomeUpgradeCosts::class)->findOneBy(['prototype' => $upgrade_proto, 'level' => $dif]);

                    if ($level_proto === null) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                    $new_upgrade = (new CitizenHomeUpgrade())->setLevel($dif)->setPrototype($upgrade_proto);
                    $citizen->getHome()->addCitizenHomeUpgrade($new_upgrade);

                    $this->entity_manager->persist($citizen->getHome());
                    $this->entity_manager->persist($new_upgrade);
                    break;

                default: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
            }
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CitizenHandler $handler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/status/manage', name: 'admin_town_manage_status', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
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

            if ($control) $this->citizen_handler->inflictStatus( $citizen, $citizenStatus );
            else $this->citizen_handler->removeStatus( $citizen, $citizenStatus );

            $this->entity_manager->persist($citizen);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    private function town_manage_pseudo_role(Town $town, JSONRequestParser $parser, TownHandler $townHandler): Response {
        $targets = $parser->get_array('targets');
        $control = $parser->get_int('control', 0) > 0;

        $citizens = [];
        foreach ($targets as $target) {
            /** @var Citizen $citizen */
            $citizen = $this->entity_manager->getRepository(Citizen::class)->find($target);
            if (!$citizen || $citizen->getTown() !== $town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            $citizens[] = $citizen;
        }

        switch ($parser->get('role')) {

            case '_ban_':
                $null = null;
                foreach ($citizens as $citizen) {
                    if($control) {
                        $this->citizen_handler->updateBanishment($citizen, null, null, $null, true);
                    } else {
                        $citizen->setBanished(false);
                    }
                    $this->entity_manager->persist($citizen);
                }
                break;
            case '_esc_':
                $c1 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_hide']);
                $c2 = $this->entity_manager->getRepository(CitizenStatus::class)->findOneBy(['name' => 'tg_tomb']);

                foreach ($citizens as $citizen) {
                    if (!$citizen->getZone() || !$citizen->getAlive() || $citizen->activeExplorerStats() || $citizen->getStatus()->contains($c1) || $citizen->getStatus()->contains($c2)) continue;

                    if (!$control) {
                        if ($citizen->getEscortSettings()) $this->entity_manager->remove($citizen->getEscortSettings());
                        $citizen->setEscortSettings(null);

                    } elseif (!$citizen->getEscortSettings())
                        $citizen->setEscortSettings((new CitizenEscortSettings())->setCitizen($citizen)->setAllowInventoryAccess(true)->setForceDirectReturn(false));
                    else $citizen->getEscortSettings()->setAllowInventoryAccess(true)->setForceDirectReturn(false);
                    $this->entity_manager->persist($citizen);
                }
                break;
            case '_nw_':
                $watchers = $this->entity_manager->getRepository(CitizenWatch::class)->findCurrentWatchers($town);
                foreach ($citizens as $citizen) {
                    $activeCitizenWatcher = null;

                    foreach ($watchers as $watcher)
                        if ($watcher->getCitizen() === $citizen){
                            $activeCitizenWatcher = $watcher;
                            break;
                        }

                    if ($control) {
                        if ($activeCitizenWatcher) continue;
                        $citizenWatch = (new CitizenWatch())->setCitizen($citizen)->setDay($town->getDay());
                        $town->addCitizenWatch($citizenWatch);
                        $this->entity_manager->persist($citizenWatch);
                    } else {
                        if ($activeCitizenWatcher === null) continue;
                        $town->removeCitizenWatch($activeCitizenWatcher);
                        $citizen->removeCitizenWatch($activeCitizenWatcher);
                        $this->entity_manager->remove($activeCitizenWatcher);
                    }

                    $this->entity_manager->persist($citizen);
                }
                break;
            case '_sh_':
                $armag_day   = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_d"]);
                $armag_night = $this->entity_manager->getRepository(SpecialActionPrototype::class)->findOneBy(['name' => "special_armag_n"]);

                foreach ($this->entity_manager->getRepository(HeroicActionPrototype::class)->findAll() as $heroic_action)
                    foreach ($citizens as $citizen) {
                        $citizen->addHeroicAction( $heroic_action );
                        $this->citizen_handler->removeStatus($citizen,'tg_hero');

                        $citizen->addSpecialAction($armag_day);
                        $citizen->addSpecialAction($armag_night);

                        $this->entity_manager->persist( $citizen );
                    }
                break;
            case '_wt_':
                if (!$townHandler->getBuilding($town,'item_tagger_#00'))
                    return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

                /** @var ZombieEstimation $est */
                $est = $this->entity_manager->getRepository(ZombieEstimation::class)->findOneBy(['town' => $town, 'day' => $town->getDay()]);
                if (!$est) return AjaxResponse::error( ErrorHelper::ErrorInternalError );

                foreach ($citizens as $citizen) {
                    if (!$control)
                        $est->removeCitizen($citizen);
                    else $est->addCitizen($citizen);
                }

                $this->entity_manager->persist($est);

                break;
            case '_rst_':
                if ($control)
                    foreach ($citizens as $citizen) {
                        $locked = $citizen->hasStatus( 'tg_stats_locked' );
                        if ($locked)
                            $this->citizen_handler->removeStatus( $citizen, 'tg_stats_locked' );

                        foreach ($citizen->getStatus() as $status)
                            if (!$status->getHidden()) $this->citizen_handler->removeStatus( $citizen, $status );

                        if ($locked) $this->citizen_handler->inflictStatus( $citizen, 'tg_stats_locked' );
                        $this->entity_manager->persist($citizen);
                    }

                break;
            case '_dig_':
                if ($control)
                    foreach ($citizens as $citizen) {
                        $dig = ($citizen->getZone() && !$citizen->getZone()->isTownZone())
                            ? ($citizen->getCurrentDigTimer() ?? (new DigTimer())->setZone( $citizen->getZone() )->setCitizen( $citizen ))
                            : null;
                        if ($dig) {
                            $dig->setTimestamp(new \DateTime('now - 24hours'));
                            $this->entity_manager->persist($dig);
                        }

                    }
                break;
            default: return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @param CitizenHandler $handler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/role/manage', name: 'admin_town_manage_role', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_manage_role(int $id, JSONRequestParser $parser, TownHandler $handler): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        if (in_array($parser->get('role'), ['_ban_','_esc_','_nw_','_sh_','_wt_','_rst_', '_dig_'] ))
            return $this->town_manage_pseudo_role($town,$parser,$handler);

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

            if ($control) $this->citizen_handler->addRole($citizen, $citizenRole);
            else $this->citizen_handler->removeRole($citizen, $citizenRole);

            $this->entity_manager->persist($citizen);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id Town ID
     * @param JSONRequestParser $parser The Request Parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/pp/alter', name: 'admin_town_alter_pp', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_alter_points(int $id, JSONRequestParser $parser): Response
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $point = $parser->get('point', '');
        if (!in_array($point, ['ap','bp','mp','gh','cc','cn'])) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $number = $parser->get_int('num', 6);

        $control = $parser->get_int('control', 0);
        if (!in_array($control, [-1,0,1])) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

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
                case 'gh': $citizen->setGhulHunger( max(0, ($control === 0) ? $number : $citizen->getGhulHunger() + $control * $number) ); break;
                case 'cc': $citizen->setCampingChance( min(100,max(0, ($control === 0) ? $number : $citizen->getCampingChance() + $control * $number)) / 100.0 ); break;
                case 'cn': $citizen->setCampingCounter( max(0, ($control === 0) ? $number : $citizen->getCampingCounter() + $control * $number) ); break;
                default: break;
            }

            $this->entity_manager->persist($citizen);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->flush();
        return AjaxResponse::success();
    }

    /**
     * @param int $id ID of the town
     * @param JSONRequestParser $parser The JSON request parser
     * @param TownHandler $th The town handler
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/buildings/add', name: 'admin_town_add_building', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_add_building(Town $town, JSONRequestParser $parser, TownHandler $th, GameProfilerService $gps)
    {
        if (!$parser->has_all(['prototype_id', 'act'])) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $proto_id = $parser->get("prototype_id");
        $act = $parser->get('act');
        if(!is_array($proto_id)) {
            $proto_id = [$proto_id];
        }

        foreach ($proto_id as $pid) {
            $proto = $this->entity_manager->getRepository(BuildingPrototype::class)->find($pid);
            if (!$proto)
                return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

            if($act) {
                $th->addBuilding($town, $proto);
                $gps->recordBuildingDiscovered($proto, $town, null, 'debug');
            } else {
                $th->removeBuilding($town, $proto);
            }
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id ID of the town
     * @param JSONRequestParser $parser The JSON request parser
     * @param EventProxyService $events
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/admin/town/{id}/buildings/set-ap', name: 'admin_town_set_building_ap', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_set_building_ap(int $id, JSONRequestParser $parser, EventProxyService $events)
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
        if (!$building || $building->getTown() !== $town)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $ap = intval($parser->get("ap"));

        if ($ap >= $building->getPrototype()->getAp()) {
            $ap = $building->getPrototype()->getAp();
        }

        $building->setAp($ap);

        if ($building->getAp() >= $building->getPrototype()->getAp())
            $events->buildingConstruction( $building, 'debug' );
        elseif ($building->getAp() <= 0) {
            $events->buildingDestruction($building, 'debug', false);
            $events->buildingDestruction($building, 'debug', true);
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($building);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param int $id ID of the town
     * @param JSONRequestParser $parser The JSON request parser
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/buildings/set-hp', name: 'admin_town_set_building_hp', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_set_building_hp(int $id, JSONRequestParser $parser, EventProxyService $events)
    {
        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        if (!$parser->has_all(['building', 'hp'])) {
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);
        }

        $building_id = $parser->get("building");

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($building_id);
        if (!$building)
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $workshopBonus = $events->queryTownParameter( $town, BuildingValueQuery::ConstructionAPRatio );
        $hpToAp = $events->queryTownParameter( $town, BuildingValueQuery::RepairAPRatio );

        $hp = $parser->get_int("hp") * $hpToAp;

        if ($hp >= $building->getPrototype()->getHp()) {
            $hp = $building->getPrototype()->getHp();
        }

        $impervious = $building->getPrototype()->getImpervious();
        if (in_array($building->getPrototype()->getName(), ['small_arma_#00'])) $impervious = false;

        if (!$building->getComplete() || ($hp < $building->getPrototype()->getHp() && $impervious))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $building->setHp($hp);

        if ($building->getHp() <= 0) {
            $events->buildingDestruction($building, 'debug', false);
            $events->buildingDestruction($building, 'debug', true);
        } else {
			if($building->getPrototype()->getDefense() > 0) {
				$newDef = min($building->getPrototype()->getDefense(), $building->getPrototype()->getDefense() * $building->getHp() / $building->getPrototype()->getHp());
				$building->setDefense((int)floor($newDef));
			}
		}

        $this->clearTownCaches($town);
        $this->entity_manager->persist($building);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param Town $town
     * @param JSONRequestParser $parser The JSON request parser
     * @param EventProxyService $events
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/admin/town/{id}/buildings/set-level', name: 'admin_town_set_building_level', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_set_building_level(Town $town, JSONRequestParser $parser, EventProxyService $events): Response
    {
        if (!$parser->has_all(['building', 'level']))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $building_id = $parser->get("building");

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($building_id);
        if (!$building || $building->getTown() !== $town || !$building->getComplete())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $level = $parser->get_int("level");

        if ($level < 0 || $level > $building->getPrototype()->getMaxLevel())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $upgrade = $level > $building->getLevel();

        $building->setLevel($level);

        if ($upgrade) {
            $events->buildingUpgrade( $building, true );
            $events->buildingUpgrade( $building, false );
        }

        $this->clearTownCaches($town);
        $this->entity_manager->persist($building);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param Town $town
     * @param JSONRequestParser $parser The JSON request parser
     * @param EventProxyService $events
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route(path: 'api/admin/town/{id}/buildings/exec-nightly', name: 'admin_town_trigger_building_nightly_effect', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[AdminLogProfile(enabled: true)]
    public function town_trigger_building_nightly_effect(Town $town, JSONRequestParser $parser, EventProxyService $events): Response
    {
        if (!$parser->has_all(['building']))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $building_id = $parser->get("building");

        /** @var Building $building */
        $building = $this->entity_manager->getRepository(Building::class)->find($building_id);
        if (!$building || $building->getTown() !== $town || !$building->getComplete())
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        foreach (BuildingEffectStage::cases() as $stage)
            $events->buildingEffect( $building, null, $stage );

        $this->clearTownCaches($town);
        $this->entity_manager->persist($building);
        $this->entity_manager->persist($town);
        $this->entity_manager->flush();

        return AjaxResponse::success();
    }

    /**
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/admin/towns/old/fuzzyfind', name: 'admin_old_towns_fuzzyfind')]
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
     * @param JSONRequestParser $parser
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route(path: 'jx/admin/towns/fuzzyfind', name: 'admin_towns_fuzzyfind')]
    public function towns_fuzzyfind(JSONRequestParser $parser, EntityManagerInterface $em): Response
    {
        if (!$parser->has_all(['name'], true))
            return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $towns = $em->getRepository(Town::class)->findByNameContains($parser->get('name'));

        return $this->render('ajax/admin/towns/townlist.html.twig', $this->addDefaultTwigArgs("admin_towns", [
            'towns' => $towns,
            'nohref' => $parser->get('no-href', false),
            'target' => 'admin_town_dashboard'
        ]));
    }


	/**
     * @param int               $id The ID of the town
     * @param JSONRequestParser $parser
     * @param MazeMaker         $mazeMaker
     * @param AdminLog          $logger
     * @return Response
     */
    #[Route(path: 'api/admin/town/{id}/admin_regenerate_ruins', name: 'admin_regenerate_ruins', requirements: ['id' => '\d+'])]
    #[AdminLogProfile(enabled: true)]
    public function admin_regenerate_ruins(int $id, JSONRequestParser $parser, MazeMaker $mazeMaker, AdminLog $logger): Response {
        /** @var Town $town */

        $town = $this->entity_manager->getRepository(Town::class)->find($id);
        if (!$town) return AjaxResponse::error(ErrorHelper::ErrorInvalidRequest);

        $explorables = [];

        foreach ($town->getZones() as $zone)
        {
            /** @var Zone $zone */
            if ($zone->getPrototype() && $zone->getPrototype()->getExplorable()) {
                $explorables[$zone->getId()] = $zone;
            }
        }


        $conf = $this->conf->getTownConfiguration( $town );

        foreach ($explorables as $zone)
        {

            $mazeMaker->setTargetZone($zone);
            $zone->setExplorableFloors($conf->get(TownSetting::ERuinSpaceFloors));

            $mazeMaker->createField();
            $mazeMaker->generateCompleteMaze();

            try {
                $this->entity_manager->persist($town);
                $this->entity_manager->flush();
            } catch (Exception $e) {
                $logger->invoke(strval($e));
                return AjaxResponse::error(ErrorHelper::ErrorDatabaseException);
            }
        }

        $this->clearTownCaches($town);
        return AjaxResponse::success();
    }
}
