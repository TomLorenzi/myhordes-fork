<?php

namespace App\Repository;

use App\Entity\BlackboardEdit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BlackboardEdit|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlackboardEdit|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlackboardEdit[]    findAll()
 * @method BlackboardEdit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlackboardEditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlackboardEdit::class);
    }

    // /**
    //  * @return BlackboardEdit[] Returns an array of BlackboardEdit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BlackboardEdit
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
