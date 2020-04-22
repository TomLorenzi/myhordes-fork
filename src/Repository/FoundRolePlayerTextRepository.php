<?php

namespace App\Repository;

use App\Entity\FoundRolePlayerText;
use App\Entity\RolePlayerText;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method FoundRolePlayerText|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoundRolePlayerText|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoundRolePlayerText[]    findAll()
 * @method FoundRolePlayerText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoundRolePlayerTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoundRolePlayerText::class);
    }

    /**
      * @return FoundRolePlayerText[] Returns an array of FoundRolePlayerText objects
      */
    public function findByUserAndText(User $user, RolePlayerText $text)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.text = :text')
            ->setParameter('user', $user)
            ->setParameter('text', $text)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return FoundRolePlayerText[] Returns an array of FoundRolePlayerText objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FoundRolePlayerText
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
