<?php

namespace App\Repository;

use App\Entity\ConnectionIdentifier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConnectionIdentifier|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConnectionIdentifier|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConnectionIdentifier[]    findAll()
 * @method ConnectionIdentifier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionIdentifierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectionIdentifier::class);
    }

    // /**
    //  * @return ConnectionIdentifier[] Returns an array of ConnectionIdentifier objects
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
    public function findOneBySomeField($value): ?ConnectionIdentifier
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
