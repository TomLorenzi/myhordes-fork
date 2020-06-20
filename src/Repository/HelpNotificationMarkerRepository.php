<?php

namespace App\Repository;

use App\Entity\HelpNotificationMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HelpNotificationMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method HelpNotificationMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method HelpNotificationMarker[]    findAll()
 * @method HelpNotificationMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HelpNotificationMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HelpNotificationMarker::class);
    }

    public function findOneByName(string $value): ?HelpNotificationMarker
    {
        try {
            return $this->createQueryBuilder('h')
                ->andWhere('h.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return HelpNotificationMarker[] Returns an array of HelpNotificationMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HelpNotificationMarker
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
