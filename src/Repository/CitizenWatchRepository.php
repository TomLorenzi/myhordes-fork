<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\CitizenWatch;
use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CitizenWatch|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenWatch|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenWatch[]    findAll()
 * @method CitizenWatch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenWatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenWatch::class);
    }

    public function findCurrentWatchers(Town $town){
         return $this->findWatchersOfDay( $town, $town->getDay() );
    }

    public function findWatchersOfDay(Town $town, int $day){
         return $this->createQueryBuilder('c')
            ->andWhere('c.town = :town')
            ->andWhere('c.day = :day')
            ->setParameter('town', $town)
            ->setParameter('day', $day)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findWatchOfCitizenForADay(Citizen $citizen, int $day): ?CitizenWatch {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.town = :town')
                ->andWhere('c.citizen = :citizen')
                ->andWhere('c.day = :day')
                ->setParameter('town', $citizen->getTown())
                ->setParameter('citizen', $citizen)
                ->setParameter('day', $day)
                ->orderBy('c.day', 'ASC')
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenWatch[] Returns an array of CitizenWatch objects
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
    public function findOneBySomeField($value): ?CitizenWatch
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