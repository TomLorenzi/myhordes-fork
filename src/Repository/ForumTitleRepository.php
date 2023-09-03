<?php

namespace App\Repository;

use App\Entity\ForumTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumTitle>
 *
 * @method ForumTitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumTitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumTitle[]    findAll()
 * @method ForumTitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumTitle::class);
    }

//    /**
//     * @return ForumTitle[] Returns an array of ForumTitle objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ForumTitle
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
