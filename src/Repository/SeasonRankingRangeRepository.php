<?php

namespace App\Repository;

use App\Entity\SeasonRankingRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeasonRankingRange>
 *
 * @method SeasonRankingRange|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeasonRankingRange|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeasonRankingRange[]    findAll()
 * @method SeasonRankingRange[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonRankingRangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeasonRankingRange::class);
    }

//    /**
//     * @return SeasonRankingRange[] Returns an array of SeasonRankingRange objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SeasonRankingRange
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
