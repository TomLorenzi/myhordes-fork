<?php

namespace App\Repository;

use App\Entity\CitizenRankingProxy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method CitizenRankingProxy|null find($id, $lockMode = null, $lockVersion = null)
 * @method CitizenRankingProxy|null findOneBy(array $criteria, array $orderBy = null)
 * @method CitizenRankingProxy[]    findAll()
 * @method CitizenRankingProxy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CitizenRankingProxyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CitizenRankingProxy::class);
    }

    public function findNextUnconfirmedDeath(User $user): ?CitizenRankingProxy
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.confirmed = false')->andWhere('c.end is not NULL')
                ->andWhere('c.user = :user')->setParameter('user', $user)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }
    }

    // /**
    //  * @return CitizenRankingProxy[] Returns an array of CitizenRankingProxy objects
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
    public function findOneBySomeField($value): ?CitizenRankingProxy
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
