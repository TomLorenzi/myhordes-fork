<?php

namespace App\Repository;

use App\Entity\RememberMeTokens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RememberMeTokens|null find($id, $lockMode = null, $lockVersion = null)
 * @method RememberMeTokens|null findOneBy(array $criteria, array $orderBy = null)
 * @method RememberMeTokens[]    findAll()
 * @method RememberMeTokens[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RememberMeTokensRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RememberMeTokens::class);
    }

    // /**
    //  * @return RememberMeTokens[] Returns an array of RememberMeTokens objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RememberMeTokens
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
