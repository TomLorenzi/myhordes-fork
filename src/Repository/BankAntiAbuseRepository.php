<?php

namespace App\Repository;

use App\Entity\BankAntiAbuse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BankAntiAbuse|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankAntiAbuse|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankAntiAbuse[]    findAll()
 * @method BankAntiAbuse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankAntiAbuseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankAntiAbuse::class);
    }

    // /**
    //  * @return BankAntiAbuse[] Returns an array of BankAntiAbuse objects
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
    public function findOneBySomeField($value): ?BankAntiAbuse
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
