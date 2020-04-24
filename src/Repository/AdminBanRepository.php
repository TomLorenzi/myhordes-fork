<?php

namespace App\Repository;

use App\Entity\AdminBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdminBan|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminBan|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminBan[]    findAll()
 * @method AdminBan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminBanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminBan::class);
    }

    // /**
    //  * @return AdminBan[] Returns an array of AdminBan objects
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
    public function findOneBySomeField($value): ?AdminBan
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
