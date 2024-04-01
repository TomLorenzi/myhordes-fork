<?php

namespace App\Repository;

use App\Entity\TownJoinNotificationAccumulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TownJoinNotificationAccumulation>
 *
 * @method TownJoinNotificationAccumulation|null find($id, $lockMode = null, $lockVersion = null)
 * @method TownJoinNotificationAccumulation|null findOneBy(array $criteria, array $orderBy = null)
 * @method TownJoinNotificationAccumulation[]    findAll()
 * @method TownJoinNotificationAccumulation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownJoinNotificationAccumulationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TownJoinNotificationAccumulation::class);
    }

//    /**
//     * @return TownJoinNotificationAccumulation[] Returns an array of TownJoinNotificationAccumulation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TownJoinNotificationAccumulation
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
