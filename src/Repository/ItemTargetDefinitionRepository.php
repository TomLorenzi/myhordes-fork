<?php

namespace App\Repository;

use App\Entity\ItemTargetDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ItemTargetDefinition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemTargetDefinition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemTargetDefinition[]    findAll()
 * @method ItemTargetDefinition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemTargetDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemTargetDefinition::class);
    }

    // /**
    //  * @return ItemTargetDefinition[] Returns an array of ItemTargetDefinition objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ItemTargetDefinition
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
