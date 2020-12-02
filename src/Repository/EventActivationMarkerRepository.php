<?php

namespace App\Repository;

use App\Entity\EventActivationMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventActivationMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventActivationMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventActivationMarker[]    findAll()
 * @method EventActivationMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventActivationMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventActivationMarker::class);
    }

    // /**
    //  * @return EventActivationMarker[] Returns an array of EventActivationMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventActivationMarker
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
