<?php

namespace App\Repository;

use App\Entity\SpecialActionPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SpecialActionPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method SpecialActionPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method SpecialActionPrototype[]    findAll()
 * @method SpecialActionPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialActionPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecialActionPrototype::class);
    }

    public function findOneByName(string $value): ?SpecialActionPrototype
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return SpecialActionPrototype[] Returns an array of SpecialActionPrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SpecialActionPrototype
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
