<?php

namespace App\Repository;

use App\Entity\DailyUpgradeVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DailyUpgradeVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyUpgradeVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyUpgradeVote[]    findAll()
 * @method DailyUpgradeVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyUpgradeVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyUpgradeVote::class);
    }

    // /**
    //  * @return DailyUpgradeVote[] Returns an array of DailyUpgradeVote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DailyUpgradeVote
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
