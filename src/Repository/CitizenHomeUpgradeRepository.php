<?php

namespace App\Repository;

use App\Entity\CitizenHome;
use App\Entity\CitizenHomeUpgrade;
use App\Entity\CitizenHomeUpgradePrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenHomeUpgrade|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenHomeUpgrade|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenHomeUpgrade[]    findAll()
 * @method CitizenHomeUpgrade[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenHomeUpgradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenHomeUpgrade::class);
    }

    public function findOneByPrototype(CitizenHome $home, CitizenHomeUpgradePrototype|string|null $proto): ?CitizenHomeUpgrade
    {
        if ($proto === null) return null;
        elseif (is_string($proto)) return $this->findOneByPrototype( $home, $this->getEntityManager()->getRepository(CitizenHomeUpgradePrototype::class)->findOneByName( $proto ) );
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.prototype = :val')->setParameter('val', $proto)
                ->andWhere('c.home = :hm')->setParameter('hm', $home)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenHomeUpgrade[] Returns an array of CitizenHomeUpgrade objects
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
    public function findOneBySomeField($value): ?CitizenHomeUpgrade
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
