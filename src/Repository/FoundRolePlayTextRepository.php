<?php

namespace App\Repository;

use App\Entity\FoundRolePlayText;
use App\Entity\RolePlayText;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
     * @param User $user
     * @return FoundRolePlayText[] Returns an array of FoundRolePlayText objects
     */
    public function findByUser(User $user)
    {
        return $this->createQueryBuilder('f')
            ->join("f.text", "t")
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.title')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param User $user
     * @param RolePlayText $text
     * @return FoundRolePlayText[] Returns an array of FoundRolePlayText objects
     * @throws NonUniqueResultException
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

    /**
     * @param User $user
     * @param RolePlayText $text
     * @return FoundRolePlayText Returns an array of FoundRolePlayText objects
     * @throws NonUniqueResultException
     */
    public function findNextUnreadText(User $user)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.new = :new')
            ->setParameter('user', $user)
            ->setParameter('new', true)
            ->setMaxResults(1)
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
