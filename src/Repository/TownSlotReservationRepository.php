<?php

namespace App\Repository;

use App\Entity\TownSlotReservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TownSlotReservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownSlotReservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownSlotReservation[]    findAll()
 * @method TownSlotReservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownSlotReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownSlotReservation::class);
    }

    // /**
    //  * @return TownSlotReservation[] Returns an array of TownSlotReservation objects
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
    public function findOneBySomeField($value): ?TownSlotReservation
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
