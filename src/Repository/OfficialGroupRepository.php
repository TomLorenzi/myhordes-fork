<?php

namespace App\Repository;

use App\Entity\OfficialGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OfficialGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfficialGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfficialGroup[]    findAll()
 * @method OfficialGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfficialGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfficialGroup::class);
    }

    // /**
    //  * @return OfficialGroup[] Returns an array of OfficialGroup objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OfficialGroup
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
