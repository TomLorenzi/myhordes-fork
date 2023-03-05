<?php

namespace App\Controller\Soul;

use App\Annotations\GateKeeperProfile;
use App\Controller\CustomAbstractController;
use App\Entity\AccountRestriction;
use App\Entity\AdminReport;
use App\Entity\Announcement;
use App\Entity\AntiSpamDomains;
use App\Entity\Award;
use App\Entity\CauseOfDeath;
use App\Entity\Changelog;
use App\Entity\Citizen;
use App\Entity\CitizenRankingProxy;
use App\Entity\ExternalApp;
use App\Entity\FeatureUnlock;
use App\Entity\FeatureUnlockPrototype;
use App\Entity\ForumPollAnswer;
use App\Entity\FoundRolePlayText;
use App\Entity\GlobalPoll;
use App\Entity\HeroSkillPrototype;
use App\Entity\OfficialGroup;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\RememberMeTokens;
use App\Entity\ShoutboxEntry;
use App\Entity\ShoutboxReadMarker;
use App\Entity\SocialRelation;
use App\Entity\Statistic;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\TwinoidImport;
use App\Entity\User;
use App\Entity\RolePlayTextPage;
use App\Entity\Season;
use App\Entity\UserDescription;
use App\Entity\UserGroupAssociation;
use App\Entity\UserPendingValidation;
use App\Entity\UserReferLink;
use App\Entity\UserSponsorship;
use App\Enum\AdminReportSpecification;
use App\Enum\DomainBlacklistType;
use App\Enum\StatisticType;
use App\Enum\UserSetting;
use App\Response\AjaxResponse;
use App\Service\ConfMaster;
use App\Service\CrowService;
use App\Service\ErrorHelper;
use App\Service\EternalTwinHandler;
use App\Service\HTMLService;
use App\Service\JSONRequestParser;
use App\Service\RandomGenerator;
use App\Service\RateLimitingFactoryProvider;
use App\Service\UserFactory;
use App\Service\UserHandler;
use App\Service\AdminHandler;
use App\Service\CitizenHandler;
use App\Service\InventoryHandler;
use App\Service\TimeKeeperService;
use App\Structures\MyHordesConf;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

/**
 * @Route("/",condition="request.isXmlHttpRequest()")
 * @method User getUser
 */
class SoulRankingController extends SoulController
{

    protected function resolveSeasonIdentifier( string|int $seasonId, bool $forceSeason = false ): Season|string|null|bool {
        return match ($seasonId) {
            'all', 'myh' => $forceSeason ? null : $seasonId,
            'c' => $this->entity_manager->getRepository(Season::class)->findOneBy(['current' => true]),
            'a' => null,
            default => $this->entity_manager->getRepository(Season::class)->find($seasonId) ?? ($forceSeason ? null : false)
        };
    }

    /**
     * @Route("/jx/soul/ranking/{type<\d+>}/{season<\d+|c|all|a>}", name="soul_season")
     * @param JSONRequestParser $parser
     * @param null $type Type of town we're looking the ranking for
     * @param null $season
     * @return Response
     */
    public function soul_season(JSONRequestParser $parser, $type = null, $season = null): Response
    {
        $user = $this->getUser();

        if (($currentSeason = $this->resolveSeasonIdentifier( $season ?? $parser->get('season', 'c'), true )) === false)
            return $this->redirect($this->generateUrl( 'soul_season', ['type' => $type, 'season' => 'c'] ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $seasons = $this->entity_manager->getRepository(Season::class)->matching((Criteria::create())
            ->orWhere(Criteria::expr()->gt('number', 0))
            ->orWhere(Criteria::expr()->gt('subNumber', 14))
        );

        if ($type === null)
            $currentType = $this->entity_manager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC'])[0];
        else
            $currentType = $this->entity_manager->getRepository(TownClass::class)->find($type);

        if ($currentType === null)
            return $this->redirect($this->generateUrl('soul_season'));

        $towns = $this->entity_manager->getRepository(TownRankingProxy::class)->findTopOfSeason($currentSeason, $currentType);
        $played = [];
        foreach ($towns as $town) {
            /* @var TownRankingProxy $town */
            foreach ($town->getCitizens() as $citizen) {
                /* @var CitizenRankingProxy $citizen */
                if($citizen->getUser() === $user) {
                    $played[$town->getId()] = true;
                    break;
                }
            }
        }

        return $this->render( 'ajax/soul/ranking/towns.html.twig', $this->addDefaultTwigArgs("soul_season", [
            'seasons' => $seasons,
            'currentSeason' => $currentSeason,
            'virtualSeason' => false,
            'towns' => $towns,
            'townTypes' => $this->entity_manager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC']),
            'currentType' => $currentType,
            'played' => $played,
            'user' => $user
        ]) );
    }

    /**
     * @Route("/jx/soul/ranking/soul/{page}/{season<\d+|c|all|myh|a>}", name="soul_season_solo")
     * @param JSONRequestParser $parser
     * @param int $page
     * @param null $season
     * @return Response
     */
    public function soul_season_solo(JSONRequestParser $parser, int $page = 1, $season = null): Response
    {
        $resultsPerPage = 30;
        $offset = $resultsPerPage * ($page - 1);

        $user = $this->getUser();

        $seasonId = $season ?? $parser->get('season', 'all');
        if (($currentSeason = $this->resolveSeasonIdentifier( $seasonId )) === false)
            return $this->redirect($this->generateUrl( 'soul_season_solo', ['season' => 'c'] ));

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $seasons = $this->entity_manager->getRepository(Season::class)->matching((Criteria::create())
            ->orWhere(Criteria::expr()->gt('number', 0))
            ->orWhere(Criteria::expr()->gt('subNumber', 14))
        );

        if ($currentSeason === 'all' || $currentSeason === 'myh') {
            $ranking = $this->entity_manager->getRepository(User::class)->getGlobalSoulRankingPage($offset, $resultsPerPage, $currentSeason === 'myh');
            $pages = $this->entity_manager->getRepository(User::class)->countGlobalSoulRankings($currentSeason === 'myh');
            $this->entity_manager->getRepository(User::class)->getGlobalSoulRankingUserStats($user, $currentSeason === 'myh', $user_sp, $user_rank);
        } else {
            $ranking = $this->entity_manager->getRepository(User::class)->getSeasonSoulRankingPage($offset, $resultsPerPage, $currentSeason);
            $pages = $this->entity_manager->getRepository(User::class)->countSeasonSoulRankings($currentSeason);
            $this->entity_manager->getRepository(User::class)->getSeasonSoulRankingUserStats($user, $currentSeason, $user_sp, $user_rank);
        }

        //if (!$ranking || !$pages)
        //    return $this->redirect($this->generateUrl( 'soul_season' ));

        return $this->render( 'ajax/soul/ranking/solo.html.twig', $this->addDefaultTwigArgs("soul_season", [
            'seasons' => $seasons,
            'currentSeason' => $seasonId === 'a' ? 'a' : $currentSeason,
            'virtualSeason' => is_string($currentSeason) || $seasonId === 'a',
            'ranking' => $ranking,
            'currentType' => 0,
            'soloType' => 'soul',
            'page' => $page,
            'pages' => ceil($pages / $resultsPerPage),
            'townTypes' => $this->entity_manager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC']),
            'offset' => $offset,
            'user' => $user,
            'user_sp' => $user_sp,
            'user_rank' => $user_rank,
            'page_size' => $resultsPerPage,
        ]) );
    }

    /**
     * @Route("/jx/soul/ranking/distinctions/{season<\d+|c|all|myh|a>}", name="soul_season_distinction_overview")
     * @param JSONRequestParser $parser
     * @param null $season
     * @return Response
     */
    public function soul_season_distinction_overview(JSONRequestParser $parser, $season = null): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $seasonId = $season ?? $parser->get('season', 'c');
        if (($currentSeason = $this->resolveSeasonIdentifier( $seasonId )) === false)
            return $this->redirect($this->generateUrl( 'soul_season_solo', ['season' => 'c'] ));

        $seasons = $this->entity_manager->getRepository(Season::class)->matching((Criteria::create())
            ->orWhere(Criteria::expr()->gt('number', 0))
            ->orWhere(Criteria::expr()->gt('subNumber', 14))
        );


        $cache = new FilesystemAdapter();

        try {
            $ranking = $cache->get("mh_distinction_ranking_$seasonId", function (ItemInterface $item) use ($currentSeason) {
                $item->expiresAfter(43200);

                $add_season_filters = function (QueryBuilder $q) use ($currentSeason): QueryBuilder {
                    if ($currentSeason === 'myh')
                        $q->andWhere('t.imported = false')->andWhere('p.old = false');
                    elseif ($currentSeason && is_a($currentSeason, Season::class))
                        $q->andWhere('t.season = :season')->setParameter('season', $currentSeason);
                    elseif ($currentSeason === null)
                        $q->andWhere('p.old = true');
                    else $q->andWhere('p.old = false');

                    return $q;
                };

                return array_map( fn(PictoPrototype $p) => [
                    'prototype' => $p->getId(),
                    'ranking' => $add_season_filters($this->entity_manager->createQueryBuilder()
                        ->from(Picto::class, 'p')
                        ->select('u.id as user', 'SUM(p.count) as count')
                        ->where('p.prototype = :proto')->setParameter('proto', $p)
                        ->andWhere('p.persisted = 2')
                        ->andWhere('p.disabled = false')
                        ->leftJoin(User::class, 'u', Join::WITH, 'p.user = u.id')
                        ->leftJoin(TownRankingProxy::class, 't', Join::WITH, 'p.townEntry = t.id')
                        ->groupBy('p.user')
                        ->orderBy('count', 'DESC')
                        ->setMaxResults(3)
                    )->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY)
                ], $this->entity_manager->getRepository(PictoPrototype::class)->findBy(
                    ['id' => $this->entity_manager->createQueryBuilder()
                        ->from(Picto::class, 'p')
                        ->select('pp.id')
                        ->groupBy('p.prototype')
                        ->andWhere('p.persisted = 2')
                        ->andWhere('p.disabled = false')
                        ->andWhere('p.count > 0')
                        ->leftJoin(PictoPrototype::class, 'pp', Join::WITH, 'p.prototype = pp.id')
                        ->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)],
                    ['rare' => 'DESC', 'priority' => 'DESC', 'id' => 'ASC']
                ) );
            }/*, INF*/);
        } catch (\Throwable $e) {
            $ranking = [];
        }

        foreach ($ranking as &$line) {
            $line['prototype'] = $this->entity_manager->getRepository(PictoPrototype::class)->find( (int)$line['prototype'] );
            foreach ($line['ranking'] as &$entry)
                $entry['user'] = $this->entity_manager->getRepository(User::class)->find( (int)$entry['user'] );
        }

        $ranking = array_values( array_filter( $ranking, fn($a) => $a['prototype']->getName() !== "r_ptame_#00" && !empty( $a['ranking'] ) && ( $a['prototype']->isSpecial() || $a['ranking'][0]['count'] > 1 || count($a['ranking']) < 2 ) ) );


        //if (!$ranking || !$pages)
        //    return $this->redirect($this->generateUrl( 'soul_season' ));

        return $this->render( 'ajax/soul/ranking/distinctions_overview.html.twig', $this->addDefaultTwigArgs("soul_season", [
            'seasons' => $seasons,
            'currentSeason' => $seasonId === 'a' ? 'a' : $currentSeason,
            'virtualSeason' => is_string($currentSeason) || $seasonId === 'a',
            'soloType' => 'distinctions',
            'townTypes' => $this->entity_manager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC']),
            'currentType' => 0,
            'ranking' => $ranking
        ]) );
    }


    /**
     * @Route("/jx/soul/ranking/distinctions/detail/{id}/{season<\d+|c|all|myh|a>}", name="soul_season_distinction_detail")
     * @param PictoPrototype $prototype
     * @param JSONRequestParser $parser
     * @param null $season
     * @return Response
     */
    public function soul_season_distinction_detail(PictoPrototype $prototype, JSONRequestParser $parser, $season = null): Response
    {
        $user = $this->getUser();

        /** @var CitizenRankingProxy $nextDeath */
        if ($this->entity_manager->getRepository(CitizenRankingProxy::class)->findNextUnconfirmedDeath($user))
            return $this->redirect($this->generateUrl( 'soul_death' ));

        $seasonId = $season ?? $parser->get('season', 'c');
        if (($currentSeason = $this->resolveSeasonIdentifier( $seasonId )) === false)
            return $this->redirect($this->generateUrl( 'soul_season_solo', ['season' => 'c'] ));

        $seasons = $this->entity_manager->getRepository(Season::class)->matching((Criteria::create())
                                                                                     ->orWhere(Criteria::expr()->gt('number', 0))
                                                                                     ->orWhere(Criteria::expr()->gt('subNumber', 14))
        );

        $total_count = $this->entity_manager->createQueryBuilder()
            ->from(Picto::class, 'p')
            ->select('SUM(p.count)')
            ->andWhere('p.prototype = :proto')->setParameter('proto', $prototype)
            ->andWhere('p.persisted = 2')
            ->andWhere('p.disabled = false')
            ->andWhere('p.count > 0')
            ->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);

        if ($total_count <= 0) return $this->redirectToRoute('soul_season_distinction_overview');

        $cache = new FilesystemAdapter();

        try {
            $ranking = $cache->get("mh_distinction_ranking_{$seasonId}_{$prototype->getId()}", function (ItemInterface $item) use ($currentSeason, $prototype) {
                $item->expiresAfter(43200);

                $add_season_filters = function (QueryBuilder $q) use ($currentSeason): QueryBuilder {
                    if ($currentSeason === 'myh')
                        $q->andWhere('t.imported = false')->andWhere('p.old = false');
                    elseif ($currentSeason && is_a($currentSeason, Season::class))
                        $q->andWhere('t.season = :season')->setParameter('season', $currentSeason);
                    elseif ($currentSeason === null)
                        $q->andWhere('p.old = true');
                    else $q->andWhere('p.old = false');

                    return $q;
                };

                return $add_season_filters($this->entity_manager->createQueryBuilder()
                    ->from(Picto::class, 'p')
                    ->select('u.id as user', 'SUM(p.count) as count')
                    ->where('p.prototype = :proto')->setParameter('proto', $prototype)
                    ->andWhere('p.persisted = 2')
                    ->andWhere('p.disabled = false')
                    ->leftJoin(User::class, 'u', Join::WITH, 'p.user = u.id')
                    ->leftJoin(TownRankingProxy::class, 't', Join::WITH, 'p.townEntry = t.id')
                    ->groupBy('p.user')
                    ->orderBy('count', 'DESC')
                    ->setMaxResults(35)
                )->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
            });
        } catch (\Throwable $e) {
            $ranking = [];
        }

        foreach ($ranking as &$entry)
            $entry['user'] = $this->entity_manager->getRepository(User::class)->find( (int)$entry['user'] );

        //if (!$ranking || !$pages)
        //    return $this->redirect($this->generateUrl( 'soul_season' ));

        return $this->render( 'ajax/soul/ranking/distinctions_detail.html.twig', $this->addDefaultTwigArgs("soul_season", [
            'seasons' => $seasons,
            'currentSeason' => $seasonId === 'a' ? 'a' : $currentSeason,
            'virtualSeason' => is_string($currentSeason) || $seasonId === 'a',
            'soloType' => 'distinctions',
            'townTypes' => $this->entity_manager->getRepository(TownClass::class)->findBy(['ranked' => true], ['orderBy' => 'ASC']),
            'currentType' => 0,
            'picto' => [
                'prototype' => $prototype,
                'ranking' => $ranking
            ]
        ]) );
    }
}
