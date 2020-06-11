<?php

namespace App\Repository;

use App\Entity\BuildingVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BuildingVote|null find($id, $lockMode = null, $lockVersion = null)
 * @method BuildingVote|null findOneBy(array $criteria, array $orderBy = null)
 * @method BuildingVote[]    findAll()
 * @method BuildingVote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BuildingVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BuildingVote::class);
    }

    // /**
    //  * @return BuildingVote[] Returns an array of BuildingVote objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BuildingVote
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
