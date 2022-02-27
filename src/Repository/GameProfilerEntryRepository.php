<?php

namespace App\Repository;

use App\Entity\GameProfilerEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GameProfilerEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameProfilerEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameProfilerEntry[]    findAll()
 * @method GameProfilerEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameProfilerEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameProfilerEntry::class);
    }

    // /**
    //  * @return GameProfilerEntry[] Returns an array of GameProfilerEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GameProfilerEntry
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
