<?php

namespace App\Repository;

use App\Entity\ActivityClusterEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityClusterEntry>
 *
 * @method ActivityClusterEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityClusterEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityClusterEntry[]    findAll()
 * @method ActivityClusterEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityClusterEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityClusterEntry::class);
    }

    //    /**
    //     * @return ActivityClusterEntry[] Returns an array of ActivityClusterEntry objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActivityClusterEntry
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
