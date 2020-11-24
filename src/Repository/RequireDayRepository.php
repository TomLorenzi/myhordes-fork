<?php

namespace App\Repository;

use App\Entity\RequireDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RequireDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method RequireDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method RequireDay[]    findAll()
 * @method RequireDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RequireDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequireDay::class);
    }

    // /**
    //  * @return RequireDay[] Returns an array of RequireDay objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RequireDay
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
