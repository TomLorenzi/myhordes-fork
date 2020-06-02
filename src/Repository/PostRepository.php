<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\Thread;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function countByThread( Thread $thread ): int {
        try {
            $q = $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('p.thread = :thread')->setParameter('thread', $thread);

            return $q->getQuery()
                ->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function countUnhiddenByThread( Thread $thread ): int {
        try {
             $q = $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('p.thread = :thread')
                ->andWhere('p.hidden = false')
                ->setParameter('thread', $thread);
            return $q->getQuery()
                ->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function getOffsetOfPostByThread( Thread $thread, Post $post ): int {
        try {
            return $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('p.thread = :thread')->setParameter('thread', $thread)
                ->andWhere('p.date < :post')->setParameter('post', $post->getDate())
                ->getQuery()
                ->getSingleScalarResult();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function findByThread(Thread $thread, $number = null, $offset = null)
    {
        $q = $this->createQueryBuilder('p')
            ->andWhere('p.thread = :thread')->setParameter('thread', $thread)
            ->orderBy('p.date', 'ASC');
        if ($number !== null) $q->setMaxResults($number);
        if ($offset !== null) $q->setFirstResult($offset);
        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    public function findUnhiddenByThread(Thread $thread, $number = null, $offset = null)
    {
        $q = $this->createQueryBuilder('p')
            ->andWhere('p.thread = :thread')
            ->andWhere('p.hidden = false')
            ->setParameter('thread', $thread)
            ->orderBy('p.date', 'ASC');
        if ($number !== null) $q->setMaxResults($number);
        if ($offset !== null) $q->setFirstResult($offset);
        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAdminAnnounces(Thread $thread)
    {
        $q = $this->createQueryBuilder('p')
            ->andWhere('p.thread = :thread')
            ->andWhere('p.hidden = false')
            ->andWhere('p.text LIKE :markup')
            ->setParameter('thread', $thread)
            ->setParameter('markup', "%oracleAnnounce%")
            ->orderBy('p.date', 'ASC');
        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    public function findOracleAnnounces(Thread $thread)
    {
        $q = $this->createQueryBuilder('p')
            ->andWhere('p.thread = :thread')
            ->andWhere('p.hidden = false')
            ->andWhere('p.text LIKE :markup')
            ->setParameter('thread', $thread)
            ->setParameter('markup', "%oracleAnnounce%")
            ->orderBy('p.date', 'ASC');
        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Post[] Returns an array of Post objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Post
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
