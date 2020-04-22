<?php

namespace App\Repository;

use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method FoundRolePlayText|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoundRolePlayText|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoundRolePlayText[]    findAll()
 * @method FoundRolePlayText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoundRolePlayTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoundRolePlayText::class);
    }

    /**
      * @return FoundRolePlayText[] Returns an array of FoundRolePlayText objects
      */
    public function findByUserAndText(User $user, RolePlayText $text)
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
    //  * @return FoundRolePlayText[] Returns an array of FoundRolePlayText objects
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
    public function findOneBySomeField($value): ?FoundRolePlayText
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
