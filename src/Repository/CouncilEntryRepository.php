<?php

namespace App\Repository;

use App\Entity\CouncilEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CouncilEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method CouncilEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method CouncilEntry[]    findAll()
 * @method CouncilEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouncilEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CouncilEntry::class);
    }

    // /**
    //  * @return CouncilEntry[] Returns an array of CouncilEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CouncilEntry
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
