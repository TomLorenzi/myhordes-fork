<?php

namespace App\Repository;

use App\Entity\TwinoidImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TwinoidImport|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwinoidImport|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwinoidImport[]    findAll()
 * @method TwinoidImport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwinoidImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwinoidImport::class);
    }

    // /**
    //  * @return TwinoidImport[] Returns an array of TwinoidImport objects
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
    public function findOneBySomeField($value): ?TwinoidImport
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
