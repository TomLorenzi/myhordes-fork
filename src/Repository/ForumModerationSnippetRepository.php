<?php

namespace App\Repository;

use App\Entity\ForumModerationSnippet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForumModerationSnippet|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumModerationSnippet|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumModerationSnippet[]    findAll()
 * @method ForumModerationSnippet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumModerationSnippetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumModerationSnippet::class);
    }

    // /**
    //  * @return ForumModerationSnippet[] Returns an array of ForumModerationSnippet objects
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
    public function findOneBySomeField($value): ?ForumModerationSnippet
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
