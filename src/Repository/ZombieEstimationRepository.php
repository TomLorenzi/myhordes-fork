<?php

namespace App\Repository;

use App\Entity\Town;
use App\Entity\ZombieEstimation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

/**
 * @method ZombieEstimation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ZombieEstimation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ZombieEstimation[]    findAll()
 * @method ZombieEstimation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ZombieEstimationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZombieEstimation::class);
    }

    public function findOneByTown(Town $town, int $day): ?ZombieEstimation
    {
        try {
            return $this->createQueryBuilder('z')
                ->andWhere('z.town = :t')->setParameter('t', $town)
                ->andWhere('z.day = :d')->setParameter('d', $day)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }
    }

    // /**
    //  * @return ZombieEstimation[] Returns an array of ZombieEstimation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('z.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ZombieEstimation
    {
        return $this->createQueryBuilder('z')
            ->andWhere('z.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
