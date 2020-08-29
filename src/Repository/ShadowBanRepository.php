<?php

namespace App\Repository;

use App\Entity\ShadowBan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ShadowBan|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShadowBan|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShadowBan[]    findAll()
 * @method ShadowBan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShadowBanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShadowBan::class);
    }

    // /**
    //  * @return ShadowBan[] Returns an array of ShadowBan objects
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
    public function findOneBySomeField($value): ?ShadowBan
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
