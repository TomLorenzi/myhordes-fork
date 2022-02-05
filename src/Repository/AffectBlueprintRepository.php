<?php

namespace App\Repository;

use App\Entity\AffectBlueprint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectBlueprint|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectBlueprint|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectBlueprint[]    findAll()
 * @method AffectBlueprint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectBlueprintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectBlueprint::class);
    }

    public function findOneByName(string $value): ?AffectBlueprint
    {
        try {
            return $this->createQueryBuilder('i')
                ->andWhere('i.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
    // /**
    //  * @return AffectBlueprint[] Returns an array of AffectBlueprint objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AffectBlueprint
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
