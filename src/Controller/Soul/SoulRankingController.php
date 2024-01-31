<?php

namespace App\Controller\Soul;

use App\Entity\CitizenRankingProxy;
use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use App\Entity\Season;
use App\Service\JSONRequestParser;
use ArrayHelpers\Arr;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @method User getUser
 */
#[Route(path: '/', condition: 'request.isXmlHttpRequest()')]
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
     * @param JSONRequestParser $parser
     * @param null $type Type of town we're looking the ranking for
     * @param null $season
     * @return Response
     */
    #[Route(path: '/jx/soul/ranking/{type<\d+>}/{season<\d+|c|all|a>}', name: 'soul_season')]
    public function soul_season(JSONRequestParser $parser, $type = null, $season = null): Response
    {
        $user = $this->getUser();

        if (($currentSeason = $this->resolveSeasonIdentifier( $season ?? $parser->get('season', 'c'), true )) === null)
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

        $range = [1,15,35];
        $additional = 0;
        if ($currentSeason?->getCurrent()) {
            $range = [$currentType->getRankingTop(), $currentType->getRankingMid(), $currentType->getRankingLow()];
            $additional = 10;
        } elseif ( $rangeConf = $currentSeason?->getRankingRange( $currentType ) ) {
            $range = [$rangeConf->getTop(), $rangeConf->getMid(), $rangeConf->getLow()];
            $additional = 10;
        }

        $towns = $this->entity_manager->getRepository(TownRankingProxy::class)->findTopOfSeason($currentSeason, $currentType, $additional);
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
            'user' => $user,
            'range' => $range
        ]) );
    }

    /**
     * @param JSONRequestParser $parser
     * @param int $page
     * @param null $season
     * @return Response
     */
    #[Route(path: '/jx/soul/ranking/soul/{page}/{season<\d+|c|all|myh|a>}', name: 'soul_season_solo')]
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
     * @param JSONRequestParser $parser
     * @param null $season
     * @return Response
     */
    #[Route(path: '/jx/soul/ranking/distinctions/{season<\d+|c|all|myh|a>}', name: 'soul_season_distinction_overview')]
    public function soul_season_distinction_overview(JSONRequestParser $parser, TagAwareCacheInterface $gameCachePool, $season = null): Response
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

        $created = null;

        try {
            $key = "mh_app_distinction_ranking_$seasonId";
            $ranking = $gameCachePool->get($key, function (ItemInterface $item) use ($currentSeason) {
                $item->expiresAfter(43200)->tag(['daily','ranking','distinction_ranking']);

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

                $data = array_map( fn(PictoPrototype $p) => [
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

                return [
                    'payload' => $data,
                    'created' => time(),
                ];
            }/*, INF*/);

            $created = Arr::get( $ranking, 'created' );
            $ranking = Arr::get( $ranking, 'payload', $ranking );

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
            'ranking' => $ranking,
            'created' => $created ? (new DateTime())->setTimestamp($created) : null,
        ]) );
    }


    /**
     * @param PictoPrototype $prototype
     * @param JSONRequestParser $parser
     * @param null $season
     * @return Response
     */
    #[Route(path: '/jx/soul/ranking/distinctions/detail/{id}/{season<\d+|c|all|myh|a>}', name: 'soul_season_distinction_detail')]
    public function soul_season_distinction_detail(PictoPrototype $prototype, JSONRequestParser $parser, TagAwareCacheInterface $gameCachePool, $season = null): Response
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

        $created = null;

        try {
            $ranking = $gameCachePool->get("mh_app_distinction_ranking_{$seasonId}_{$prototype->getId()}", function (ItemInterface $item) use ($currentSeason, $prototype) {
                $item->expiresAfter(43200)->tag(['daily','ranking','distinction_ranking']);
                
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

                $data = $add_season_filters($this->entity_manager->createQueryBuilder()
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

                return [
                    'payload' => $data,
                    'created' => time(),
                ];
            });

            $created = Arr::get( $ranking, 'created' );
            $ranking = Arr::get( $ranking, 'payload', $ranking );
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
            'created' => $created ? (new DateTime())->setTimestamp($created) : null,
            'picto' => [
                'prototype' => $prototype,
                'ranking' => $ranking
            ]
        ]) );
    }
}
