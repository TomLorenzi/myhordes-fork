<?php

namespace App\Repository;

use App\Entity\ForumPollAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForumPollAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumPollAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumPollAnswer[]    findAll()
 * @method ForumPollAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumPollAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPollAnswer::class);
    }

    // /**
    //  * @return ForumPollAnswer[] Returns an array of ForumPollAnswer objects
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
    public function findOneBySomeField($value): ?ForumPollAnswer
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
