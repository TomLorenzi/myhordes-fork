<?php

namespace App\Repository;

use App\Entity\CitizenProperties;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CitizenProperties>
 *
 * @method CitizenProperties|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenProperties|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenProperties[]    findAll()
 * @method CitizenProperties[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenPropertiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenProperties::class);
    }

    //    /**
    //     * @return CitizenProperties[] Returns an array of CitizenProperties objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CitizenProperties
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
