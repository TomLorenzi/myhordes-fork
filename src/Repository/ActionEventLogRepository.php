<?php

namespace App\Repository;

use App\Entity\ActionEventLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ActionEventLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActionEventLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActionEventLog[]    findAll()
 * @method ActionEventLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionEventLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionEventLog::class);
    }

    // /**
    //  * @return ActionEventLog[] Returns an array of ActionEventLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ActionEventLog
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
