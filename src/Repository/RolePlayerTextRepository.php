<?php

namespace App\Repository;

use App\Entity\RolePlayerText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method RolePlayerText|null find($id, $lockMode = null, $lockVersion = null)
 * @method RolePlayerText|null findOneBy(array $criteria, array $orderBy = null)
 * @method RolePlayerText[]    findAll()
 * @method RolePlayerText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RolePlayerTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePlayerText::class);
    }

    public function findOneByName(string $value): ?RolePlayerText
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
    * @return RolePlayerText[] Returns an array of RolePlayerText objects
    */
    public function findAllByLang($lang)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.language = :val')
            ->setParameter('val', $lang)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return RolePlayerText[] Returns an array of RolePlayerText objects
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
    public function findOneBySomeField($value): ?RolePlayerText
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
