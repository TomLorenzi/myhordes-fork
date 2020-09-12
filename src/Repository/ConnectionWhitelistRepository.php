<?php

namespace App\Repository;

use App\Entity\ConnectionWhitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ConnectionWhitelist|null find($id, $lockMode = null, $lockVersion = null)
 * @method ConnectionWhitelist|null findOneBy(array $criteria, array $orderBy = null)
 * @method ConnectionWhitelist[]    findAll()
 * @method ConnectionWhitelist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectionWhitelistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConnectionWhitelist::class);
    }

    // /**
    //  * @return ConnectionWhitelist[] Returns an array of ConnectionWhitelist objects
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
    public function findOneBySomeField($value): ?ConnectionWhitelist
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
