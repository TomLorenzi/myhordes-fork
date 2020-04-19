<?php

namespace App\Repository;

use App\Entity\CitizenEscortSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CitizenEscortSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenEscortSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenEscortSettings[]    findAll()
 * @method CitizenEscortSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenEscortSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenEscortSettings::class);
    }

    // /**
    //  * @return CitizenEscortSettings[] Returns an array of CitizenEscortSettings objects
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
    public function findOneBySomeField($value): ?CitizenEscortSettings
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
