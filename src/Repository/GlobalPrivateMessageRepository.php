<?php

namespace App\Repository;

use App\Entity\GlobalPrivateMessage;
use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GlobalPrivateMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method GlobalPrivateMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method GlobalPrivateMessage[]    findAll()
 * @method GlobalPrivateMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GlobalPrivateMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalPrivateMessage::class);
    }

    public function findByGroup(UserGroup $group, int $last_id = 0, int $num = 0)
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.receiverGroup = :group')->setParameter('group', $group)
            ->orderBy('g.timestamp', 'DESC')->orderBy('g.id', 'DESC');

        if ($last_id > 0) $qb->andWhere('g.id < :id')->setParameter('id', $last_id);
        if ($num > 0) $qb ->setMaxResults($num);

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return GlobalPrivateMessage[] Returns an array of GlobalPrivateMessage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GlobalPrivateMessage
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
