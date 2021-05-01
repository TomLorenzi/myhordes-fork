<?php

namespace App\Repository;

use App\Entity\UserReferLink;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserReferLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserReferLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserReferLink[]    findAll()
 * @method UserReferLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserReferLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserReferLink::class);
    }

    // /**
    //  * @return UserReferLink[] Returns an array of UserReferLink objects
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
    public function findOneBySomeField($value): ?UserReferLink
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
