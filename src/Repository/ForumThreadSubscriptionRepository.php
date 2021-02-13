<?php

namespace App\Repository;

use App\Entity\ForumThreadSubscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForumThreadSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumThreadSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumThreadSubscription[]    findAll()
 * @method ForumThreadSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumThreadSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumThreadSubscription::class);
    }

    // /**
    //  * @return ForumTreadSubscription[] Returns an array of ForumTreadSubscription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ForumTreadSubscription
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
