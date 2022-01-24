<?php

namespace App\Repository;

use App\Entity\CitizenHomeUpgradePrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenHomeUpgradePrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenHomeUpgradePrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenHomeUpgradePrototype[]    findAll()
 * @method CitizenHomeUpgradePrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenHomeUpgradePrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenHomeUpgradePrototype::class);
    }

    public function findOneByName($value): ?CitizenHomeUpgradePrototype
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
    //  * @return CitizenHomeUpgrade[] Returns an array of CitizenHomeUpgradePrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CitizenHomeUpgradePrototype
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
