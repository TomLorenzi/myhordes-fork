<?php

namespace App\Repository;

use App\Entity\ForumPoll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForumPoll|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumPoll|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumPoll[]    findAll()
 * @method ForumPoll[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumPollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPoll::class);
    }

    // /**
    //  * @return ForumPoll[] Returns an array of ForumPoll objects
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
    public function findOneBySomeField($value): ?ForumPoll
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
