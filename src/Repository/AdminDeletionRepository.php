<?php

namespace App\Repository;

use App\Entity\AdminDeletion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AdminDeletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdminDeletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdminDeletion[]    findAll()
 * @method AdminDeletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdminDeletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminDeletion::class);
    }

    // /**
    //  * @return AdminDeletion[] Returns an array of AdminDeletion objects
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
    public function findOneBySomeField($value): ?AdminDeletion
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
