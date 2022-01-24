<?php

namespace App\Repository;

use App\Entity\CitizenHomePrototype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CitizenHomePrototype|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenHomePrototype|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenHomePrototype[]    findAll()
 * @method CitizenHomePrototype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenHomePrototypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenHomePrototype::class);
    }

    public function findOneByLevel(int $level): ?CitizenHomePrototype
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.level = :val')
                ->setParameter('val', $level)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenHomePrototype[] Returns an array of CitizenHomePrototype objects
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
    public function findOneBySomeField($value): ?CitizenHomePrototype
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
