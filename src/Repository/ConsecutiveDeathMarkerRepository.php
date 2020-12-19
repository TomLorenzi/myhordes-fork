<?php

namespace App\Repository;

use App\Entity\ConsecutiveDeathMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConsecutiveDeathMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConsecutiveDeathMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConsecutiveDeathMarker[]    findAll()
 * @method ConsecutiveDeathMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConsecutiveDeathMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsecutiveDeathMarker::class);
    }

    // /**
    //  * @return ConsecutiveDeathMarker[] Returns an array of ConsecutiveDeathMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ConsecutiveDeathMarker
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
