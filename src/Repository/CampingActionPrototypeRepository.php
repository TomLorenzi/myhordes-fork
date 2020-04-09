<?php

namespace App\Repository;

use App\Entity\CampingActionPrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CampingActionPrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method CampingActionPrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method CampingActionPrototype[]    findAll()
 * @method CampingActionPrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampingActionPrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampingActionPrototype::class);
    }

    public function findOneByName(string $value): ?CampingActionPrototype
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
    //  * @return CampingActionPrototype[] Returns an array of CampingActionPrototype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CampingActionPrototype
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
