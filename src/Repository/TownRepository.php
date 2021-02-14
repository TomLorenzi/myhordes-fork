<?php

namespace App\Repository;

use App\Entity\Town;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Town|null find($id, $lockMode = null, $lockVersion = null)
 * @method Town|null findOneBy(array $criteria, array $orderBy = null)
 * @method Town[]    findAll()
 * @method Town[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TownRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Town::class);
    }

    /**
     * @return Town[] Returns an array of Town objects
     */
    
    public function findOpenTown()
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.day = 1')
            ->andHaving('count(c) < t.population')
            ->groupBy('t.id')
            ->leftJoin('t.citizens', 'c')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Town[] Returns an array of User objects
     * @param string $value Value to search for
     */
    public function findByNameContains(string $value)
    {
        return is_numeric($value)
            ? $this->createQueryBuilder('t')
                ->andWhere('t.name LIKE :val OR t.id = :id')->setParameter('val', '%' . $value . '%')->setParameter('id', (int)$value)
                ->getQuery()->getResult()
            : $this->createQueryBuilder('t')
                ->andWhere('t.name LIKE :val')->setParameter('val', '%' . $value . '%')
                ->getQuery()->getResult();
    }

    // /**
    //  * @return Town[] Returns an array of Town objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Town
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
