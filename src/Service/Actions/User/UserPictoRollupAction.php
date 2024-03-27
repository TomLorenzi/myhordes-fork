<?php

namespace App\Service\Actions\User;

use App\Entity\Picto;
use App\Entity\PictoPrototype;
use App\Entity\PictoRollup;
use App\Entity\Season;
use App\Entity\TownRankingProxy;
use App\Entity\User;
use ArrayHelpers\Arr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

readonly class UserPictoRollupAction
{
    public function __construct(
        private EntityManagerInterface $em,
    ) { }

    /**
     * @param PictoPrototype|null $prototype
     * @param QueryBuilder $qb
     * @return array|int[]
     * @throws NonUniqueResultException
     */
    private function collect(PictoPrototype|null $prototype, QueryBuilder $qb): array {
        if ($prototype === null) {
            $d = [];
            foreach ( $qb->groupBy('i.prototype')->getQuery()->getArrayResult() as ['c' => $count, 'p' => $prototype] )
                $d[$prototype] = (int)$count;
            return $d;
        } else try {
            return [$prototype->getId() => (int)$qb->getQuery()->getSingleScalarResult()];
        } catch (NoResultException $e) {
            return [$prototype->getId() => 0];
        }
    }

    /**
     * @param User $user
     * @param PictoPrototype|null $prototype
     * @param bool $imported
     * @param bool $old
     * @return int[]
     * @throws NonUniqueResultException
     */
    private function countPicto(User $user, ?PictoPrototype $prototype, ?Season $season, bool $imported, bool $old): array {
        $qb = $this->em->getRepository(Picto::class)->createQueryBuilder('i')
            ->select('SUM(i.count) as c', $prototype === null ? 'IDENTITY(i.prototype) as p' : null);

        if ($season && !$imported && !$old)
            $qb->innerJoin(TownRankingProxy::class, 't', Join::WITH, 'i.townEntry = t.id AND t.season = :season')
            ->setParameter('season', $season);
        elseif ($season && ($imported || $old))
            $qb->andWhere('false = true');
        elseif (!$season && !$imported && !$old)
            $qb->andWhere('false = true');

        $collection = $this->collect( $prototype, $qb
            ->andWhere('i.user = :user')->setParameter('user', $user)
            ->andWhere('i.persisted = 2')
            ->andWhere('i.disabled = false')
            ->andWhere('i.imported = :imported')->setParameter( 'imported', $imported )
            ->andWhere('i.old = :old')->setParameter( 'old', $old )
        );

        if ($season?->getNumber() === 0 && $season?->getSubNumber() === 15 && !$imported && !$old) {

            $orphans = $this->collect( $prototype, $this->em->getRepository(Picto::class)->createQueryBuilder('i')
                ->select('SUM(i.count) as c', $prototype === null ? 'IDENTITY(i.prototype) as p' : null)
                ->andWhere('i.user = :user')->setParameter('user', $user)
                ->andWhere('i.persisted = 2')
                ->andWhere('i.disabled = false')
                ->andWhere('i.imported = false')
                ->andWhere('i.old = false')
                ->andWhere('i.townEntry IS NULL')
            );

            foreach ($orphans as $p => $c)
                $collection[$p] = ($collection[$p] ?? 0) + $c;

        }

        return $collection;
    }

    /**
     * @param User $user
     * @param PictoPrototype|null $prototype
     * @param bool $imported
     * @param bool $old
     * @return array|int[]
     * @throws NonUniqueResultException
     */
    private function fetchPicto(User $user, ?PictoPrototype $prototype, ?Season $season, bool $imported, bool $old): array {
        return $this->collect( $prototype, $this->em->getRepository(PictoRollup::class)->createQueryBuilder('i')
            ->select('i.count as c', $prototype === null ? 'IDENTITY(i.prototype) as p' : null)
            ->andWhere('i.user = :user')->setParameter('user', $user)
            ->andWhere('i.total = false')
            ->andWhere('i.imported = :imported')->setParameter( 'imported', $imported )
            ->andWhere('i.old = :old')->setParameter( 'old', $old )
            ->andWhere('i.season = :season')->setParameter( 'season', $season )
        );
    }

    public function __invoke(User $user, PictoPrototype|array $prototypes = null, ?Season $season = null, ?bool $imported = false, ?bool $old = false): void
    {
        $count_import = $imported === null ? [false,true] : [$imported];
        $count_old = $old === null ? [false, true] : [$old];

        $settings = [];
        foreach ([false,true] as $ci)
            foreach ([false,true] as $co)
                $settings[] = [$ci,$co,false];
        $settings[] = [false,false,true];

        $data = [];
        $prototype_prop = is_array( $prototypes ) ? null : $prototypes;
        foreach ($settings as $s => [$s_import, $s_old, $s_total])
            if (!$s_total) $data[$s] = [
                $s_old,
                (in_array( $s_import, $count_import ) && in_array( $s_old, $count_old ))
                    ? $this->countPicto( $user, $prototype_prop, $season, $s_import, $s_old )
                    : $this->fetchPicto( $user, $prototype_prop, $season, $s_import, $s_old )
            ];


        foreach (($prototypes ?? $this->em->getRepository(PictoPrototype::class)->findAll()) as $prototype) {

            foreach ($settings as $s => [$s_import, $s_old, $s_total]) {

                $value = $s_total
                    ? array_reduce( $data, fn(int $carry, array $items) => $items[0] ? $carry : (($items[1][$prototype->getId()] ?? 0) + $carry), 0 )
                    : Arr::get( $data, "$s.1.{$prototype->getId()}", 0 );

                $existing = $this->em->getRepository(PictoRollup::class)->findOneBy(
                    ['user' => $user, 'prototype' => $prototype, 'old' => $s_old, 'imported' => $s_import, 'total' => $s_total, 'season' => $season]
                );

                if ($value > 0) {
                    $rollup = $existing ?? (new PictoRollup())->setUser( $user )->setPrototype( $prototype )->setOld( $s_old )->setImported( $s_import )->setTotal( $s_total )->setSeason( $season );
                    $rollup->setCount( $value );
                    $this->em->persist( $rollup );
                } elseif ($existing) $this->em->remove( $existing );
            }
        }
    }
}