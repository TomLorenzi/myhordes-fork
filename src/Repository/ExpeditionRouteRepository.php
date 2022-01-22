<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\ExpeditionRoute;
use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ExpeditionRoute|null find($id, $lockMode = null, $lockVersion = null)
 * @method ExpeditionRoute|null findOneBy(array $criteria, array $orderBy = null)
 * @method ExpeditionRoute[]    findAll()
 * @method ExpeditionRoute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpeditionRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpeditionRoute::class);
    }

    /**
     * @param Town $town
     * @return ExpeditionRoute[]
     */
    public function findByTown(Town $town)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.owner IN (:val)')->setParameter('val', $town->getCitizens())
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Citizen|Citizen[] $citizens
     * @return ExpeditionRoute[]
     */
    public function findByCitizens($citizens)
    {
        if (!is_array($citizens)) $citizens = [$citizens];
        return $this->createQueryBuilder('e')
            ->andWhere('e.owner IN (:val)')->setParameter('val', $citizens)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return ExpeditionRoute[] Returns an array of ExpeditionRoute objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ExpeditionRoute
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
