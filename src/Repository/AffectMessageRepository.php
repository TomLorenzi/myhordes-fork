<?php

namespace App\Repository;

use App\Entity\AffectMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AffectMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectMessage[]    findAll()
 * @method AffectMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectMessage::class);
    }

    // /**
    //  * @return AffectMessage[] Returns an array of AffectMessage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AffectMessage
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
