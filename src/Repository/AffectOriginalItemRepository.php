<?php

namespace App\Repository;

use App\Entity\AffectOriginalItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method AffectOriginalItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectOriginalItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectOriginalItem[]    findAll()
 * @method AffectOriginalItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectOriginalItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectOriginalItem::class);
    }

    public function findOneByName(string $value): ?AffectOriginalItem
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
    //  * @return AffectOriginalItem[] Returns an array of AffectOriginalItem objects
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
    public function findOneBySomeField($value): ?AffectOriginalItem
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
