<?php

namespace App\Repository;

use App\Entity\Forum;
use App\Entity\Thread;
use App\Entity\ThreadReadMarker;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
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

    public function countByForum( Forum $forum, bool $include_hidden = false, ?bool $pinned = null, ?Thread $before = null ): int {
        try {
            $qb = $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->andWhere('t.forum = :forum')->setParameter('forum', $forum);
            if (!$include_hidden)
                $qb->andWhere('t.hidden = false OR t.hidden is NULL');
            if ($pinned !== null)
                $qb->andWhere('t.pinned = :pinned')->setParameter('pinned', $pinned);
            if ($before)
                $qb->andWhere('t.lastPost > :deadline')->setParameter('deadline', $before->getLastPost());

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

    /**
     * @param Forum $forum
     * @param int $threadsPerPage
     * @return int[]
     */
    public function firstPageThreadIDs( Forum $forum, int $threadsPerPage ): array {
        return array_merge(
            $this->createQueryBuilder('t')
                ->select('t.id')
                ->andWhere('t.pinned = false')
                ->andWhere('t.forum = :forum')->setParameter('forum', $forum)
                ->andWhere('t.hidden = false OR t.hidden is NULL')
                ->orderBy('t.lastPost', 'DESC')
                ->setMaxResults($threadsPerPage)
                ->getQuery()->getSingleColumnResult(),
            $this->createQueryBuilder('t')
                ->select('t.id')
                ->andWhere('t.pinned = true')
                ->andWhere('t.forum = :forum')->setParameter('forum', $forum)
                ->andWhere('t.hidden = false OR t.hidden is NULL')
                ->getQuery()->getSingleColumnResult(),
        );

    }

    public function countThreadsWithUnreadPosts(User $user, array|Forum $threadIDs, bool $includeHidden = false) {
        $global_limit = $this->getEntityManager()->getRepository(ThreadReadMarker::class)->findGlobalAndUser($user)?->getPost()->getId() ?? 0;

        $q = $this->createQueryBuilder('t')
            ->innerJoin('t.posts', 'p')
            ->leftJoin( 't._readMarkers', 'r', Join::WITH, 'r.thread = t.id AND r.user = :user')->setParameter('user', $user)
            ->select('MAX(p.id) AS pid', 't.id AS tid', 'IDENTITY(r.post) AS rid')
            ->groupBy('t.id')
            ->having( '(:global < pid) AND (rid IS NULL OR rid < pid)' )->setParameter('global', $global_limit)
        ;

        if (is_a( $threadIDs, Forum::class ))
            $q->andWhere( 't.forum = :forum' )->setParameter('forum', $threadIDs);
        else $q->andWhere( 't.id IN (:threads)' )->setParameter('threads', $threadIDs);

        if (!$includeHidden)
            $q->andWhere('p.hidden = false');

        return count($q->getQuery()->getScalarResult());
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
