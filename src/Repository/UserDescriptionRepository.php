<?php

namespace App\Repository;

use App\Entity\UserDescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserDescription|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDescription|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDescription[]    findAll()
 * @method UserDescription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDescription::class);
    }

    // /**
    //  * @return UserDescription[] Returns an array of UserDescription objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserDescription
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
