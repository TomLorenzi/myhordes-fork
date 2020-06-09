<?php

namespace App\Repository;

use App\Entity\RuinExplorerStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuinExplorerStats|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuinExplorerStats|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuinExplorerStats[]    findAll()
 * @method RuinExplorerStats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuinExplorerStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuinExplorerStats::class);
    }

    // /**
    //  * @return RuinExplorerStats[] Returns an array of RuinExplorerStats objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RuinExplorerStats
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
