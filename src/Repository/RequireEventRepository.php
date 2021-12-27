<?php

namespace App\Repository;

use App\Entity\RequireEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RequireEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequireEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequireEvent[]    findAll()
 * @method RequireEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequireEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequireEvent::class);
    }

    // /**
    //  * @return RequireEvent[] Returns an array of RequireEvent objects
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
    public function findOneBySomeField($value): ?RequireEvent
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
