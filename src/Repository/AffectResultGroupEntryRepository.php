<?php

namespace App\Repository;

use App\Entity\AffectResultGroupEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AffectResultGroupEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectResultGroupEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectResultGroupEntry[]    findAll()
 * @method AffectResultGroupEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectResultGroupEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectResultGroupEntry::class);
    }

    // /**
    //  * @return AffectResultGroupEntry[] Returns an array of AffectResultGroupEntry objects
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
    public function findOneBySomeField($value): ?AffectResultGroupEntry
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
