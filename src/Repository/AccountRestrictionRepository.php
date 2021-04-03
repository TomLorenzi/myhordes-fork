<?php

namespace App\Repository;

use App\Entity\AccountRestriction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccountRestriction|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccountRestriction|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccountRestriction[]    findAll()
 * @method AccountRestriction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccountRestrictionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountRestriction::class);
    }

    // /**
    //  * @return AccountRestriction[] Returns an array of AccountRestriction objects
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
    public function findOneBySomeField($value): ?AccountRestriction
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
