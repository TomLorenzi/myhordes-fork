<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\DigRuinMarker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method DigRuinMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method DigRuinMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method DigRuinMarker[]    findAll()
 * @method DigRuinMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DigRuinMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigRuinMarker::class);
    }

    public function findByCitizen(Citizen $c): ?DigRuinMarker
    {
        if (!$c->getZone()) return null;
        try {
            return $this->createQueryBuilder('d')
                ->andWhere('d.citizen = :ctz')->setParameter('ctz', $c)
                ->andWhere('d.zone = :zne')->setParameter('zne', $c->getZone())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param Citizen $c
     * @return DigRuinMarker[]
     */
    public function findAllByCitizen(Citizen $c)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.citizen = :ctz')->setParameter('ctz', $c)
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return DigRuinMarker[] Returns an array of DigRuinMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DigRuinMarker
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
