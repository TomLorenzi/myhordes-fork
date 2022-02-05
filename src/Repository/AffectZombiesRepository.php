<?php

namespace App\Repository;

use App\Entity\AffectZombies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectZombies|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectZombies|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectZombies[]    findAll()
 * @method AffectZombies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectZombiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectZombies::class);
    }

    public function findOneByName(string $value): ?AffectZombies
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
    //  * @return AffectZombies[] Returns an array of AffectZombies objects
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
    public function findOneBySomeField($value): ?AffectZombies
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
