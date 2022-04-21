<?php

namespace App\Repository;

use App\Entity\EventAnnouncementMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventAnnouncementMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventAnnouncementMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventAnnouncementMarker[]    findAll()
 * @method EventAnnouncementMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventAnnouncementMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventAnnouncementMarker::class);
    }

    // /**
    //  * @return EventAnnouncementMarker[] Returns an array of EventAnnouncementMarker objects
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
    public function findOneBySomeField($value): ?EventAnnouncementMarker
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
