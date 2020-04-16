<?php

namespace App\Repository;

use App\Entity\ActionCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ActionCounter|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActionCounter|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActionCounter[]    findAll()
 * @method ActionCounter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionCounter::class);
    }

    // /**
    //  * @return ActionCounter[] Returns an array of ActionCounter objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ActionCounter
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
