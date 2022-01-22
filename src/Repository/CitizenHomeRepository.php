<?php

namespace App\Repository;

use App\Entity\CitizenHome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CitizenHome|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenHome|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenHome[]    findAll()
 * @method CitizenHome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenHomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenHome::class);
    }

    // /**
    //  * @return CitizenHome[] Returns an array of CitizenHome objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CitizenHome
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
