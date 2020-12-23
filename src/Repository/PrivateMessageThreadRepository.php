<?php

namespace App\Repository;

use App\Entity\Citizen;
use App\Entity\PrivateMessageThread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrivateMessageThread|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrivateMessageThread|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrivateMessageThread[]    findAll()
 * @method PrivateMessageThread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrivateMessageThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrivateMessageThread::class);
    }

    /**
     * @return PrivateMessageThread[]
     */
    public function findNonArchived(Citizen $citizen)
    {
        return $this->createQueryBuilder('pmt')
            ->innerJoin('pmt.messages', 'm')
            ->andWhere('m.recipient = :citizen')
            ->andWhere('pmt.archived = false')
            ->setParameter('citizen', $citizen)
            ->orderBy('pmt.lastMessage', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PrivateMessageThread[]
     */
    public function findArchived(Citizen $citizen)
    {
        return $this->createQueryBuilder('pmt')
            ->innerJoin('pmt.messages', 'm')
            ->andWhere('m.recipient = :citizen')
            ->andWhere('pmt.archived = true')
            ->setParameter('citizen', $citizen)
            ->orderBy('pmt.lastMessage', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?PrivateMessageThread
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
