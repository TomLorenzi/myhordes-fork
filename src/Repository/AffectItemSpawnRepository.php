<?php

namespace App\Repository;

use App\Entity\AffectAP;
use App\Entity\AffectItemSpawn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectItemSpawn|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectItemSpawn|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectItemSpawn[]    findAll()
 * @method AffectItemSpawn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectItemSpawnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectItemSpawn::class);
    }

    public function findOneByName(string $value): ?AffectItemSpawn
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
    //  * @return AffectItemSpawn[] Returns an array of AffectItemSpawn objects
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
    public function findOneBySomeField($value): ?AffectItemSpawn
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
