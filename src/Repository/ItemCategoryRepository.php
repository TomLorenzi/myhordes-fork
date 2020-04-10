<?php

namespace App\Repository;

use App\Entity\ItemCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ItemCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemCategory[]    findAll()
 * @method ItemCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemCategory::class);
    }

    public function findOneByName(string $value): ?ItemCategory
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

    public function findRootCategories()
    {
        return $this->createQueryBuilder('i')
            ->Where('i.parent is null')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return ItemCategory[] Returns an array of ItemCategory objects
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
    public function findOneBySomeField($value): ?ItemCategory
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
