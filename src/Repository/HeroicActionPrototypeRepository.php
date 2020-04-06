<?php

namespace App\Repository;

use App\Entity\HeroicActionPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method HeroicActionPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeroicActionPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method HeroicActionPrototype[]    findAll()
 * @method HeroicActionPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeroicActionPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeroicActionPrototype::class);
    }

    public function findOneByName(string $value): ?HeroicActionPrototype
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
    //  * @return HeroicActionPrototype[] Returns an array of HeroicActionPrototype objects
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
    public function findOneBySomeField($value): ?HeroicActionPrototype
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
