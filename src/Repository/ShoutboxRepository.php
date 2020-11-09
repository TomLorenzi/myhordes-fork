<?php

namespace App\Repository;

use App\Entity\Shoutbox;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Shoutbox|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shoutbox|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shoutbox[]    findAll()
 * @method Shoutbox[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShoutboxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shoutbox::class);
    }

    // /**
    //  * @return Shoutbox[] Returns an array of Shoutbox objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Shoutbox
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
