<?php

namespace App\Repository;

use App\Entity\GlobalPrivateMessage;
use App\Entity\User;
use App\Entity\UserGroup;
use DateTime;
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

    public function findByGroup(UserGroup $group, int $last_id = 0, int $num = 0, int $not_beyond = 0, ?DateTime $not_before = null)
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.receiverGroup = :group')->setParameter('group', $group)
            ->orderBy('g.timestamp', 'DESC')->orderBy('g.id', 'DESC');

        if ($last_id > 0)    $qb->andWhere('g.id < :lid')->setParameter('lid', $last_id);
        if ($not_beyond > 0) $qb->andWhere('g.id <= :bid')->setParameter('bid', $not_beyond);
        if ($num > 0) $qb->setMaxResults($num);
        if ($not_before !== null) $qb->andWhere('g.timestamp > :time')->setParameter('time', $not_before);

        return $qb->getQuery()->getResult();
    }

    public function lastInGroup(UserGroup $group): ?GlobalPrivateMessage
    {
        try {
            return $this->createQueryBuilder('g')
                ->andWhere('g.receiverGroup = :group')->setParameter('group', $group)
                ->orderBy('g.timestamp', 'DESC')->orderBy('g.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()->getOneOrNullResult();
        } catch (\Throwable $t) { return null; }
    }

    public function countUnreadDirectPMsByUser( User $user ): int
    {
        try {
            return $this->createQueryBuilder('g')->select('COUNT(g.id)')
                ->andWhere('g.receiverUser = :user')->setParameter('user', $user)
                ->andWhere('g.receiverGroup IS NULL')
                ->andWhere('g.seen = :seen')->setParameter('seen', false)
                ->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) { return 0; }
    }

    public function getUnreadDirectPMsByUser( User $user, ?DateTime $newer_then = null )
    {
        $qb = $this->createQueryBuilder('g')
                ->andWhere('g.receiverUser = :user')->setParameter('user', $user)
                ->andWhere('g.receiverGroup IS NULL')
                ->andWhere('g.seen = :seen')->setParameter('seen', false)
                ->orderBy('g.timestamp', 'DESC');

        if ($newer_then !== null) $qb->andWhere('g.timestamp > :time')->setParameter('time', $newer_then);

        return $qb->getQuery()->getResult();
    }

    public function getDirectPMsByUser( User $user, int $last_id = 0, int $num = 0 )
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.receiverUser = :user')->setParameter('user', $user)
            ->andWhere('g.receiverGroup IS NULL')
            ->orderBy('g.timestamp', 'DESC')->orderBy('g.id', 'DESC');

        if ($last_id > 0) $qb->andWhere('g.id < :id')->setParameter('id', $last_id);
        if ($num > 0) $qb->setMaxResults($num);

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
