<?php

namespace App\Repository;

use App\Entity\AffectResultGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectResultGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectResultGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectResultGroup[]    findAll()
 * @method AffectResultGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectResultGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectResultGroup::class);
    }

    public function findOneByName(string $value): ?AffectResultGroup
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

    // /**
    //  * @return AffectResultGroup[] Returns an array of AffectResultGroup objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AffectResultGroup
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
