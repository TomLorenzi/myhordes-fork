<?php

namespace App\Repository;

use App\Entity\CitizenHomeUpgradeCosts;
use App\Entity\CitizenHomeUpgradePrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenHomeUpgradeCosts|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenHomeUpgradeCosts|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenHomeUpgradeCosts[]    findAll()
 * @method CitizenHomeUpgradeCosts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenHomeUpgradeCostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenHomeUpgradeCosts::class);
    }

    public function findOneByPrototype(CitizenHomeUpgradePrototype $proto, int $level): ?CitizenHomeUpgradeCosts
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.prototype = :proto')->setParameter('proto', $proto)
                ->andWhere('c.level = :lv')->setParameter('lv', $level)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenHomeUpgradeCosts[] Returns an array of CitizenHomeUpgradeCosts objects
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
    public function findOneBySomeField($value): ?CitizenHomeUpgradeCosts
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
