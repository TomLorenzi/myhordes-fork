<?php

namespace App\Repository;

use App\Entity\AutomaticEventForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutomaticEventForecast>
 *
 * @method AutomaticEventForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method AutomaticEventForecast|null findOneBy(array $criteria, array $orderBy = null)
 * @method AutomaticEventForecast[]    findAll()
 * @method AutomaticEventForecast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AutomaticEventForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutomaticEventForecast::class);
    }

//    /**
//     * @return AutomaticEventForecast[] Returns an array of AutomaticEventForecast objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AutomaticEventForecast
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
