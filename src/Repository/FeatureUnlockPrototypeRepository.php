<?php

namespace App\Repository;

use App\Entity\FeatureUnlockPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FeatureUnlockPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method FeatureUnlockPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method FeatureUnlockPrototype[]    findAll()
 * @method FeatureUnlockPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeatureUnlockPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureUnlockPrototype::class);
    }

    // /**
    //  * @return FeatureUnlock[] Returns an array of FeatureUnlockPrototype objects
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
    public function findOneBySomeField($value): ?FeatureUnlockPrototype
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
