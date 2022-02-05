<?php

namespace App\Repository;

use App\Entity\ItemProperty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ItemProperty|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemProperty|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemProperty[]    findAll()
 * @method ItemProperty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemPropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemProperty::class);
    }

    public function findOneByName(string $value): ?ItemProperty
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.name = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return ItemProperty[] Returns an array of ItemProperty objects
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
    public function findOneBySomeField($value): ?ItemProperty
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
