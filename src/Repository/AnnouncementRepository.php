<?php

namespace App\Repository;

use App\Entity\Announcement;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method Announcement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Announcement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Announcement[]    findAll()
 * @method Announcement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnouncementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Announcement::class);
    }

    public function findByLang(string $lang, array $skip = [], int $limit = 0)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.lang = :lang')->setParameter('lang', $lang)
            ->orderBy('a.timestamp', 'DESC');

        if (!empty($skip)) $qb->andWhere('a.id NOT IN (:skip)')->setParameter('skip', $skip);
        if ($limit > 0) $qb->setMaxResults( $limit );

        return $qb->getQuery()->getResult();
    }

    public function countUnreadByUser(User $user, string $lang)
    {
        $qb = $this->createQueryBuilder('a')->select('COUNT(a.id)')
            ->andWhere(':user NOT MEMBER OF a.readBy')->setParameter('user', $user)
            ->andWhere('a.lang = :lang')->setParameter('lang', $lang)
            ->andWhere('a.timestamp > :cut')->setParameter('cut', new \DateTime('-60days'));

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (Exception $e) { return 0; }
    }

    public function getUnreadByUser(User $user, string $lang, ?DateTime $newer_then = null, int $limit = 0)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere(':user NOT MEMBER OF a.readBy')->setParameter('user', $user)
            ->andWhere('a.lang = :lang')->setParameter('lang', $lang)
            ->andWhere('a.timestamp > :cut')->setParameter('cut', $newer_then ?? new DateTime('-60days'))
            ->orderBy('a.timestamp', 'DESC');

        if ($limit > 0) $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    // /**
    //  * @return Announcement[] Returns an array of Announcement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Announcement
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
