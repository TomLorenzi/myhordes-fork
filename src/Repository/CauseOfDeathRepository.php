<?php

namespace App\Repository;

use App\Entity\CauseOfDeath;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method CauseOfDeath|null find($id, $lockMode = null, $lockVersion = null)
 * @method CauseOfDeath|null findOneBy(array $criteria, array $orderBy = null)
 * @method CauseOfDeath[]    findAll()
 * @method CauseOfDeath[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CauseOfDeathRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CauseOfDeath::class);
    }

    public function findOneByRef(int $ref): ?CauseOfDeath
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.ref = :val')
                ->setParameter('val', $ref)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return CauseOfDeath[] Returns an array of CauseOfDeath objects
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
    public function findOneBySomeField($value): ?CauseOfDeath
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
