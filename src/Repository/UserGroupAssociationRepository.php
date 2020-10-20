<?php

namespace App\Repository;

use App\Entity\UserGroupAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserGroupAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGroupAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGroupAssociation[]    findAll()
 * @method UserGroupAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGroupAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroupAssociation::class);
    }

    // /**
    //  * @return UserGroupAssociation[] Returns an array of UserGroupAssociation objects
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
    public function findOneBySomeField($value): ?UserGroupAssociation
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
