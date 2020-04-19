<?php

namespace App\Repository;

use App\Entity\HomeActionPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HomeActionPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method HomeActionPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method HomeActionPrototype[]    findAll()
 * @method HomeActionPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HomeActionPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeActionPrototype::class);
    }

    public function findOneByName(string $value): ?HomeActionPrototype
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
    //  * @return HomeActionPrototype[] Returns an array of HomeActionPrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HomeActionPrototype
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
