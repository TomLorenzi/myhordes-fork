<?php

namespace App\Repository;

use App\Entity\UserSponsorship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserSponsorship|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSponsorship|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSponsorship[]    findAll()
 * @method UserSponsorship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSponsorshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSponsorship::class);
    }

    // /**
    //  * @return UserSponsorship[] Returns an array of UserSponsorship objects
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
    public function findOneBySomeField($value): ?UserSponsorship
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
