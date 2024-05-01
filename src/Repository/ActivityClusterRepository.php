<?php

namespace App\Repository;

use App\Entity\ActivityCluster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityCluster>
 *
 * @method ActivityCluster|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityCluster|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityCluster[]    findAll()
 * @method ActivityCluster[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityClusterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityCluster::class);
    }

//    /**
//     * @return ActivityCluster[] Returns an array of ActivityCluster objects
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

//    public function findOneBySomeField($value): ?ActivityCluster
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
