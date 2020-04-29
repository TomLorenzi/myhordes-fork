<?php

namespace App\Repository;

use App\Entity\AdminReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdminReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminReport[]    findAll()
 * @method AdminReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminReport::class);
    }

    // /**
    //  * @return AdminReport[] Returns an array of AdminReport objects
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
    public function findOneBySomeField($value): ?AdminReport
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
