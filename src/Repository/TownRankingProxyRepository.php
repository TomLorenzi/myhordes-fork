<?php

namespace App\Repository;

use App\Entity\Season;
use App\Entity\TownClass;
use App\Entity\TownRankingProxy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TownRankingProxy|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownRankingProxy|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownRankingProxy[]    findAll()
 * @method TownRankingProxy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownRankingProxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownRankingProxy::class);
    }

    /**
     * @return TownRankingProxy[] Returns an array of TownRankingProxy objects
     * @param string $value Value to search for
     */
    public function findByNameContains(string $value)
    {
        return is_numeric($value)
            ? $this->createQueryBuilder('t')
                ->andWhere('t.name LIKE :val OR t.id = :id OR t.baseID = :id')->setParameter('val', '%' . $value . '%')->setParameter('id', (int)$value)
                ->getQuery()->getResult()
            : $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :val')->setParameter('val', '%' . $value . '%')
            ->getQuery()->getResult();
    }

    /**
     * @param Season|null $season
     * @param TownClass $class
     * @param int $additional
     * @param int|null $fixed_limit
     * @return TownRankingProxy[] Returns an array of TownRankingProxy objects
     */
    public function findTopOfSeason(?Season $season, TownClass $class, int $additional = 0, ?int $fixed_limit = null): array
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere('BIT_AND(t.disableFlag, :flag) <> :flag')->setParameter('flag', TownRankingProxy::DISABLE_RANKING)
            ->andWhere('t.event = false');

        if ($season === null) $q->andWhere('t.season IS NULL');
        else $q->andWhere('t.season = :season')->setParameter('season', $season);

        return $q->andWhere('t.type = :type')->setParameter('type', $class)
            ->andWhere('t.end IS NOT NULL')
            ->setMaxResults( $fixed_limit ?? ($additional + ($season?->getCurrent() ? $class->getRankingLow() : ($season?->getRankingRange($class)?->getLow() ?? 35))))
            ->addOrderBy('t.score', 'desc')
            ->addOrderBy('t.days', 'desc')
            ->addOrderBy('t.end', 'asc')
            ->addOrderBy('t.id', 'asc')
            ->getQuery()->getResult();
    }
}
