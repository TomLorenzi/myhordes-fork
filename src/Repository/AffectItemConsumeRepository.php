<?php

namespace App\Repository;

use App\Entity\AffectItemConsume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectItemConsume|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectItemConsume|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectItemConsume[]    findAll()
 * @method AffectItemConsume[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectItemConsumeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectItemConsume::class);
    }

    public function findOneByName(string $value): ?AffectItemConsume
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
    //  * @return AffectItemConsume[] Returns an array of AffectItemConsume objects
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
    public function findOneBySomeField($value): ?AffectItemConsume
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
