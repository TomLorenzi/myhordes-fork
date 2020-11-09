<?php

namespace App\Repository;

use App\Entity\ShoutboxEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ShoutboxEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShoutboxEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShoutboxEntry[]    findAll()
 * @method ShoutboxEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShoutboxEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoutboxEntry::class);
    }

    // /**
    //  * @return ShoutboxEntry[] Returns an array of ShoutboxEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ShoutboxEntry
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
