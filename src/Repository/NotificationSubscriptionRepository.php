<?php

namespace App\Repository;

use App\Entity\NotificationSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NotificationSubscription>
 *
 * @method NotificationSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method NotificationSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method NotificationSubscription[]    findAll()
 * @method NotificationSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationSubscription::class);
    }

//    /**
//     * @return NotificationSubscription[] Returns an array of NotificationSubscription objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NotificationSubscription
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
