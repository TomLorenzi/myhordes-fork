<?php

namespace App\Repository;

use App\Entity\NamedItemGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NamedItemGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method NamedItemGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method NamedItemGroup[]    findAll()
 * @method NamedItemGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NamedItemGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NamedItemGroup::class);
    }

    // /**
    //  * @return NamedItemGroup[] Returns an array of NamedItemGroup objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NamedItemGroup
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
