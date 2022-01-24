<?php

namespace App\Repository;

use App\Entity\ItemGroupEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ItemGroupEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ItemGroupEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ItemGroupEntry[]    findAll()
 * @method ItemGroupEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemGroupEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemGroupEntry::class);
    }

    // /**
    //  * @return ItemGroupEntry[] Returns an array of ItemGroupEntry objects
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
    public function findOneBySomeField($value): ?ItemGroupEntry
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
