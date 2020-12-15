<?php

namespace App\Repository;

use App\Entity\ChatSilenceTimer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ChatSilenceTimer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatSilenceTimer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatSilenceTimer[]    findAll()
 * @method ChatSilenceTimer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatSilenceTimerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatSilenceTimer::class);
    }

    // /**
    //  * @return ChatSilenceTimer[] Returns an array of ChatSilenceTimer objects
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
    public function findOneBySomeField($value): ?ChatSilenceTimer
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
