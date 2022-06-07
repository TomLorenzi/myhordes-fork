<?php

namespace App\Repository;

use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method ThreadReadMarker|null find($id, $lockMode = null, $lockVersion = null)
 * @method ThreadReadMarker|null findOneBy(array $criteria, array $orderBy = null)
 * @method ThreadReadMarker[]    findAll()
 * @method ThreadReadMarker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreadReadMarkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ThreadReadMarker::class);
    }

    /**
     * @param User $user
     * @param Thread $thread
     * @return ThreadReadMarker|null
     */
    public function findByThreadAndUser(User $user, Thread $thread): ?ThreadReadMarker
    {
        try {
            return $this->createQueryBuilder('t')
                ->andWhere('t.user = :user')->setParameter('user', $user)
                ->andWhere('t.thread = :thread')->setParameter('thread', $thread)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param User $user
     * @return ThreadReadMarker|null
     */
    public function findGlobalAndUser(User $user): ?ThreadReadMarker
    {
        try {
            return $this->createQueryBuilder('t')
                ->andWhere('t.user = :user')->setParameter('user', $user)
                ->andWhere('t.thread IS NULL')
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    // /**
    //  * @return ThreadReadMarker[] Returns an array of ThreadReadMarker objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ThreadReadMarker
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
