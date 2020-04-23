<?php

namespace App\Repository;

use App\Entity\EscortActionGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EscortActionGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method EscortActionGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method EscortActionGroup[]    findAll()
 * @method EscortActionGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EscortActionGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EscortActionGroup::class);
    }

    public function findOneByName(string $value): ?EscortActionGroup
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
    //  * @return EscortActionGroup[] Returns an array of EscortActionGroup objects
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
    public function findOneBySomeField($value): ?EscortActionGroup
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
