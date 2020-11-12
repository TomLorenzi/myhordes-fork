<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\Thread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Exception;

/**
 * @method Thread|null find($id, $lockMode = null, $lockVersion = null)
 * @method Thread|null findOneBy(array $criteria, array $orderBy = null)
 * @method Thread[]    findAll()
 * @method Thread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ThreadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Thread::class);
    }

    public function countByForum( Forum $forum, bool $include_hidden = false ): int {
        try {
            $qb = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->andWhere('t.forum = :forum')->setParameter('forum', $forum);
            if (!$include_hidden)
                $qb->andWhere('t.hidden = false OR t.hidden is NULL');

            return $qb->getQuery()
                ->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function findByForumSemantic(Forum $forum, int $semantic, bool $include_hidden = false): ?Thread
    {
        try {
            $qb = $this->createQueryBuilder('t')
                ->andWhere('t.semantic = :semantic')->setParameter('semantic', $semantic)
                ->andWhere('t.forum = :forum')->setParameter('forum', $forum)
                ->orderBy('t.lastPost', 'DESC')
                ->setMaxResults(1);

            if (!$include_hidden)
                $qb->andWhere('t.hidden = false OR t.hidden is NULL');

            return $qb->getQuery()
                ->getOneOrNullResult();
        } catch (Exception $e) {
            return null;
        }
    }

    public function findByForum(Forum $forum, $number = null, $offset = null, bool $include_hidden = false)
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere('t.pinned = false')
            ->andWhere('t.forum = :forum')->setParameter('forum', $forum)
            ->orderBy('t.lastPost', 'DESC');
        if ($number !== null) $q->setMaxResults($number);
        if ($offset !== null) $q->setFirstResult($offset);
        if (!$include_hidden)
            $q->andWhere('t.hidden = false OR t.hidden is NULL');

        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    public function findPinnedByForum(Forum $forum, $number = null, $offset = null, bool $include_hidden = false)
    {
        $q = $this->createQueryBuilder('t')
            ->andWhere('t.pinned = true')
            ->andWhere('t.forum = :forum')->setParameter('forum', $forum)
            ->orderBy('t.lastPost', 'DESC');
        if ($number !== null) $q->setMaxResults($number);
        if ($offset !== null) $q->setFirstResult($offset);
        if (!$include_hidden)
            $q->andWhere('t.hidden = false OR t.hidden is NULL');
        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Thread[] Returns an array of Thread objects
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
    public function findOneBySomeField($value): ?Thread
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
