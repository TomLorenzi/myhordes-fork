<?php

namespace App\Repository;

use App\Entity\ZoneTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ZoneTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method ZoneTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method ZoneTag[]    findAll()
 * @method ZoneTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZoneTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZoneTag::class);
    }

    // /**
    //  * @return ZoneTag[] Returns an array of ZoneTag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('z.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ZoneTag
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
