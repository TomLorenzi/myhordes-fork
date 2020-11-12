<?php

namespace App\Repository;

use App\Entity\ShoutboxReadMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ShoutboxReadMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShoutboxReadMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShoutboxReadMarker[]    findAll()
 * @method ShoutboxReadMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShoutboxReadMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoutboxReadMarker::class);
    }

    // /**
    //  * @return ShoutboxReadMarker[] Returns an array of ShoutboxReadMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ShoutboxReadMarker
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
