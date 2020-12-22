<?php

namespace App\Repository;

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

    // /**
    //  * @return TownRankingProxy[] Returns an array of TownRankingProxy objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TownRankingProxy
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
