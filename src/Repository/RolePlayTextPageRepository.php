<?php

namespace App\Repository;

use App\Entity\RolePlayTextPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RolePlayTextPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method RolePlayTextPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method RolePlayTextPage[]    findAll()
 * @method RolePlayTextPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RolePlayTextPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePlayTextPage::class);
    }

    /**
    * @return RolePlayTextPage[] Returns an array of RolePlayTextPage objects
    */
    public function findAllByRolePlayText($rp)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.rolePlayText = :rp')
            ->setParameter('rp', $rp)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
    * @return RolePlayTextPage[] Returns an array of RolePlayTextPage objects
    */
    public function findOneByRpAndPageNumber($rp, $page)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.rolePlayText = :rp')
            ->andWhere('r.page_number = :page')
            ->setParameter('rp', $rp)
            ->setParameter('page', $page)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return RolePlayTextPage[] Returns an array of RolePlayTextPage objects
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
    public function findOneBySomeField($value): ?RolePlayTextPage
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
