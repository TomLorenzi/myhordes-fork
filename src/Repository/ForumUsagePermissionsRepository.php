<?php

namespace App\Repository;

use App\Entity\ForumUsagePermissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForumUsagePermissions|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumUsagePermissions|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumUsagePermissions[]    findAll()
 * @method ForumUsagePermissions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumUsagePermissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumUsagePermissions::class);
    }

    // /**
    //  * @return ForumUsagePermissions[] Returns an array of ForumUsagePermissions objects
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
    public function findOneBySomeField($value): ?ForumUsagePermissions
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
