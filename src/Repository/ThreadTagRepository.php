<?php

namespace App\Repository;

use App\Entity\ThreadTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ThreadTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method ThreadTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method ThreadTag[]    findAll()
 * @method ThreadTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreadTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThreadTag::class);
    }

    // /**
    //  * @return ThreadTag[] Returns an array of ThreadTag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ThreadTag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
