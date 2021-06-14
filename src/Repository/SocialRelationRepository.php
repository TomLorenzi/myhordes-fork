<?php

namespace App\Repository;

use App\Entity\SocialRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SocialRelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method SocialRelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method SocialRelation[]    findAll()
 * @method SocialRelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SocialRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SocialRelation::class);
    }

    // /**
    //  * @return SocialRelation[] Returns an array of SocialRelation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SocialRelation
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
